import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  // 默认路由重定向到仪表板
  {
    path: '/',
    redirect: '/dashboard',
  },

  // 登录页面路由
  {
    path: '/login',
    component: () => import('layouts/AuthLayout.vue'),
    children: [
      {
        path: '',
        name: 'LoginPage',
        component: () => import('pages/Login.vue'),
        meta: { requiresGuest: true },
      },
    ],
  },

  // 主应用路由
  {
    path: '/dashboard',
    component: () => import('layouts/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('pages/Dashboard.vue'),
      },
    ],
  },

  // 个人资料页面路由
  {
    path: '/profile',
    component: () => import('layouts/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Profile',
        component: () => import('pages/Profile.vue'),
      },
    ],
  },

  // 系统设置页面路由
  {
    path: '/settings',
    component: () => import('layouts/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Settings',
        component: () => import('pages/Settings.vue'),
      },
    ],
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue'),
  },
];

export default routes;
