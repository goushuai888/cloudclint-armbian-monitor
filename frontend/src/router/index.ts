import { defineRouter } from '#q-app/wrappers';
import {
  createMemoryHistory,
  createRouter,
  createWebHashHistory,
  createWebHistory,
} from 'vue-router';
import routes from './routes';
import { useAuthStore } from 'src/stores/auth';

/*
 * If not building with SSR mode, you can
 * directly export the Router instantiation;
 *
 * The function below can be async too; either use
 * async/await or return a Promise which resolves
 * with the Router instance.
 */

export default defineRouter(function (/* { store, ssrContext } */) {
  const createHistory = process.env.SERVER
    ? createMemoryHistory
    : process.env.VUE_ROUTER_MODE === 'history'
      ? createWebHistory
      : createWebHashHistory;

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,

    // Leave this as is and make changes in quasar.conf.js instead!
    // quasar.conf.js -> build -> vueRouterMode
    // quasar.conf.js -> build -> publicPath
    history: createHistory(process.env.VUE_ROUTER_BASE),
  });

  // 路由守卫 - 异步守卫确保认证状态已初始化
  Router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    
    // 确保认证状态已经初始化（这很重要！）
    if (!authStore.token && !authStore.user) {
      // 检查本地存储中是否有认证信息
      const storedToken = localStorage.getItem('auth_token');
      const storedUser = localStorage.getItem('user_info');
      
      if (storedToken && storedUser) {
        // 如果有存储的认证信息，先初始化认证状态
        await authStore.initAuth();
      }
    }

    // 检查是否需要认证
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
      // 未登录用户访问需要认证的页面，重定向到登录页
      console.log('未登录用户访问受保护页面，重定向到登录页');
      next({ name: 'LoginPage' });
      return;
    }

    // 检查是否需要游客状态（如登录页面）
    if (to.meta.requiresGuest && authStore.isAuthenticated) {
      // 已登录用户访问登录页面，重定向到设备管理页面
      console.log('已登录用户访问登录页，重定向到仪表板');
      next('/dashboard');
      return;
    }

    next();
  });

  return Router;
});
