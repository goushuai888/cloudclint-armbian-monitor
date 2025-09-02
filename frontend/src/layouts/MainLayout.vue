<template>
  <q-layout view="hHh lpR fFf">
    <q-header elevated>
      <q-toolbar>
        <q-toolbar-title
          class="clickable-title cursor-pointer"
          @click="goToDashboard"
        >
          设备管理系统
        </q-toolbar-title>

        <q-space />
        
        <!-- 用户菜单 -->
        <q-btn-dropdown
          v-if="authStore.isAuthenticated"
          flat
          no-caps
          class="user-menu-btn"
          :label="authStore.userName || '用户'"
          icon="account_circle"
        >
          <q-list>
            <q-item clickable v-close-popup @click="goToProfile">
              <q-item-section avatar>
                <q-icon name="person" />
              </q-item-section>
              <q-item-section>
                <q-item-label>个人资料</q-item-label>
              </q-item-section>
            </q-item>

            <q-item clickable v-close-popup @click="goToSettings">
              <q-item-section avatar>
                <q-icon name="settings" />
              </q-item-section>
              <q-item-section>
                <q-item-label>系统设置</q-item-label>
              </q-item-section>
            </q-item>

            <q-separator />

            <q-item clickable v-close-popup @click="handleLogout">
              <q-item-section avatar>
                <q-icon name="logout" color="negative" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-negative">退出登录</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-btn-dropdown>

        <!-- 未登录状态 -->
        <q-btn
          v-else
          flat
          no-caps
          label="登录"
          icon="login"
          @click="goToLogin"
        />
      </q-toolbar>
    </q-header>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const $q = useQuasar()
const authStore = useAuthStore()

// 跳转到设备管理页面（仪表板）
const goToDashboard = () => {
  void router.push('/dashboard')
}

// 跳转到个人资料页面
const goToProfile = () => {
  void router.push('/profile')
}

// 跳转到系统设置页面
const goToSettings = () => {
  void router.push('/settings')
}

// 跳转到登录页面
const goToLogin = () => {
  void router.push('/login')
}

// 处理退出登录
const handleLogout = () => {
  $q.dialog({
    title: '确认退出',
    message: '您确定要退出登录吗？',
    cancel: true,
    persistent: true
  }).onOk(() => {
    void (async () => {
      try {
        const result = await authStore.logout()
        if (result?.shouldRedirect) {
          await router.push('/login')
        }
      } catch (error) {
        console.error('退出登录失败:', error)
      }
    })()
  })
}
</script>

<style scoped>
.user-menu-btn {
  min-width: 120px;
}

.clickable-title {
  transition: color 0.2s ease;
}

.clickable-title:hover {
  color: #42a5f5 !important;
}
</style>
