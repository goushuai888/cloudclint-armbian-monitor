<template>
  <q-layout view="lHh Lpr lFf">
    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

// 组件挂载时检查认证状态
onMounted(() => {
  // 如果用户已经登录，重定向到主页面
  if (authStore.isAuthenticated) {
    router.push('/').catch(() => {});
  }
});
</script>

<style scoped>
/* 认证布局的全局样式 */
.q-layout {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style>
