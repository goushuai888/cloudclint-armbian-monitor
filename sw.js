/**
 * CloudClint Service Worker
 * 提供缓存策略和离线支持
 */

const CACHE_NAME = 'cloudclint-v1.0.0';
const STATIC_CACHE = 'cloudclint-static-v1.0.0';
const DYNAMIC_CACHE = 'cloudclint-dynamic-v1.0.0';
const API_CACHE = 'cloudclint-api-v1.0.0';

// 需要缓存的静态资源
const STATIC_ASSETS = [
    '/',
    '/assets/css/cloudclint-ui.css',
    '/assets/css/cloudclint-components.css',
    '/assets/css/performance.css',
    '/assets/js/cloudclint-ui.js',
    '/assets/js/performance-monitor.js',
    '/assets/images/logo.png',
    '/assets/fonts/bootstrap-icons.woff2',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'
];

// 需要网络优先的资源
const NETWORK_FIRST = [
    '/api/',
    '/ajax/',
    '/index.php'
];

// 需要缓存优先的资源
const CACHE_FIRST = [
    '/assets/',
    '.css',
    '.js',
    '.png',
    '.jpg',
    '.jpeg',
    '.gif',
    '.svg',
    '.woff',
    '.woff2'
];

// 安装事件 - 缓存静态资源
self.addEventListener('install', event => {
    console.log('Service Worker: Installing...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => {
                console.log('Service Worker: Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => {
                console.log('Service Worker: Static assets cached');
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('Service Worker: Failed to cache static assets', error);
            })
    );
});

// 激活事件 - 清理旧缓存
self.addEventListener('activate', event => {
    console.log('Service Worker: Activating...');
    
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== STATIC_CACHE && 
                            cacheName !== DYNAMIC_CACHE && 
                            cacheName !== API_CACHE) {
                            console.log('Service Worker: Deleting old cache', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('Service Worker: Activated');
                return self.clients.claim();
            })
    );
});

// 拦截请求
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    
    // 跳过非GET请求
    if (request.method !== 'GET') {
        return;
    }
    
    // 跳过chrome-extension和其他协议
    if (!url.protocol.startsWith('http')) {
        return;
    }
    
    event.respondWith(handleRequest(request));
});

// 处理请求的主要逻辑
async function handleRequest(request) {
    const url = new URL(request.url);
    
    try {
        // API请求 - 网络优先，短期缓存
        if (isApiRequest(url.pathname)) {
            return await networkFirstStrategy(request, API_CACHE, 300000); // 5分钟缓存
        }
        
        // 静态资源 - 缓存优先
        if (isStaticAsset(url.pathname)) {
            return await cacheFirstStrategy(request, STATIC_CACHE);
        }
        
        // HTML页面 - 网络优先，备用缓存
        if (isHtmlRequest(request)) {
            return await networkFirstStrategy(request, DYNAMIC_CACHE, 60000); // 1分钟缓存
        }
        
        // 其他请求 - 网络优先
        return await networkFirstStrategy(request, DYNAMIC_CACHE);
        
    } catch (error) {
        console.error('Service Worker: Request failed', error);
        
        // 返回离线页面或缓存的响应
        if (isHtmlRequest(request)) {
            return await getOfflinePage();
        }
        
        throw error;
    }
}

// 网络优先策略
async function networkFirstStrategy(request, cacheName, maxAge = 0) {
    try {
        const response = await fetch(request);
        
        // 只缓存成功的响应
        if (response.ok) {
            const cache = await caches.open(cacheName);
            const responseClone = response.clone();
            
            // 添加时间戳用于缓存过期检查
            if (maxAge > 0) {
                const headers = new Headers(responseClone.headers);
                headers.set('sw-cached-at', Date.now().toString());
                headers.set('sw-max-age', maxAge.toString());
                
                const modifiedResponse = new Response(responseClone.body, {
                    status: responseClone.status,
                    statusText: responseClone.statusText,
                    headers: headers
                });
                
                cache.put(request, modifiedResponse);
            } else {
                cache.put(request, responseClone);
            }
        }
        
        return response;
    } catch (error) {
        // 网络失败，尝试从缓存获取
        const cachedResponse = await getCachedResponse(request, cacheName, maxAge);
        if (cachedResponse) {
            return cachedResponse;
        }
        throw error;
    }
}

// 缓存优先策略
async function cacheFirstStrategy(request, cacheName) {
    const cachedResponse = await getCachedResponse(request, cacheName);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    // 缓存未命中，从网络获取
    const response = await fetch(request);
    if (response.ok) {
        const cache = await caches.open(cacheName);
        cache.put(request, response.clone());
    }
    
    return response;
}

// 获取缓存响应（带过期检查）
async function getCachedResponse(request, cacheName, maxAge = 0) {
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);
    
    if (!cachedResponse) {
        return null;
    }
    
    // 检查缓存是否过期
    if (maxAge > 0) {
        const cachedAt = cachedResponse.headers.get('sw-cached-at');
        const cacheMaxAge = cachedResponse.headers.get('sw-max-age');
        
        if (cachedAt && cacheMaxAge) {
            const age = Date.now() - parseInt(cachedAt);
            if (age > parseInt(cacheMaxAge)) {
                // 缓存已过期，删除并返回null
                await cache.delete(request);
                return null;
            }
        }
    }
    
    return cachedResponse;
}

// 获取离线页面
async function getOfflinePage() {
    const cache = await caches.open(STATIC_CACHE);
    const offlinePage = await cache.match('/');
    
    if (offlinePage) {
        return offlinePage;
    }
    
    // 返回简单的离线页面
    return new Response(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>CloudClint - 离线模式</title>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                .offline { color: #666; }
                .retry { margin-top: 20px; }
                button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
            </style>
        </head>
        <body>
            <div class="offline">
                <h1>CloudClint</h1>
                <p>您当前处于离线模式</p>
                <p>请检查网络连接后重试</p>
                <div class="retry">
                    <button onclick="window.location.reload()">重新加载</button>
                </div>
            </div>
        </body>
        </html>
    `, {
        headers: {
            'Content-Type': 'text/html; charset=utf-8'
        }
    });
}

// 判断是否为API请求
function isApiRequest(pathname) {
    return NETWORK_FIRST.some(pattern => pathname.includes(pattern)) ||
           pathname.includes('ajax') ||
           pathname.includes('api');
}

// 判断是否为静态资源
function isStaticAsset(pathname) {
    return CACHE_FIRST.some(pattern => pathname.includes(pattern));
}

// 判断是否为HTML请求
function isHtmlRequest(request) {
    const acceptHeader = request.headers.get('Accept') || '';
    return acceptHeader.includes('text/html');
}

// 消息处理
self.addEventListener('message', event => {
    const { type, payload } = event.data;
    
    switch (type) {
        case 'SKIP_WAITING':
            self.skipWaiting();
            break;
            
        case 'CLEAR_CACHE':
            clearAllCaches().then(() => {
                event.ports[0].postMessage({ success: true });
            }).catch(error => {
                event.ports[0].postMessage({ success: false, error: error.message });
            });
            break;
            
        case 'GET_CACHE_SIZE':
            getCacheSize().then(size => {
                event.ports[0].postMessage({ size });
            });
            break;
    }
});

// 清理所有缓存
async function clearAllCaches() {
    const cacheNames = await caches.keys();
    return Promise.all(
        cacheNames.map(cacheName => caches.delete(cacheName))
    );
}

// 获取缓存大小
async function getCacheSize() {
    const cacheNames = await caches.keys();
    let totalSize = 0;
    
    for (const cacheName of cacheNames) {
        const cache = await caches.open(cacheName);
        const requests = await cache.keys();
        
        for (const request of requests) {
            const response = await cache.match(request);
            if (response) {
                const blob = await response.blob();
                totalSize += blob.size;
            }
        }
    }
    
    return Math.round(totalSize / 1024 / 1024 * 100) / 100; // MB
}

// 后台同步（如果支持）
if ('sync' in self.registration) {
    self.addEventListener('sync', event => {
        if (event.tag === 'background-sync') {
            event.waitUntil(doBackgroundSync());
        }
    });
}

// 后台同步逻辑
async function doBackgroundSync() {
    try {
        // 这里可以添加后台同步逻辑
        // 例如：同步离线时的操作、更新缓存等
        console.log('Service Worker: Background sync completed');
    } catch (error) {
        console.error('Service Worker: Background sync failed', error);
    }
}

console.log('Service Worker: Loaded');