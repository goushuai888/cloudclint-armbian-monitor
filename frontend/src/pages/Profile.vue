<template>
  <q-page class="profile-page">
    <div class="container">
      <!-- 页面标题 -->
      <div class="page-header">
        <div class="header-content">
          <div class="title-section">
            <h3 class="page-title">
              <q-icon name="person" class="title-icon" />
              个人资料
            </h3>
            <p class="page-subtitle">管理您的个人信息和账户设置</p>
          </div>
          <q-btn
            color="primary"
            icon="refresh"
            label="刷新信息"
            outline
            @click="loadUserProfile"
            class="refresh-btn"
          />
        </div>
      </div>

      <div class="profile-layout">
        <!-- 左侧：头像和基本信息 -->
        <div class="profile-sidebar">
          <q-card class="avatar-card">
            <q-card-section class="avatar-section">
              <div class="avatar-container">
                <q-avatar size="100px" class="user-avatar">
                  <img v-if="profileForm.avatar" :src="profileForm.avatar" alt="用户头像" />
                  <q-icon v-else name="person" size="50px" color="grey-6" />
                </q-avatar>

                <q-btn
                  round
                  color="primary"
                  icon="camera_alt"
                  size="sm"
                  class="avatar-upload-btn"
                  @click="handleAvatarUpload"
                >
                  <q-tooltip>更换头像</q-tooltip>
                </q-btn>
              </div>

              <div class="user-info">
                <div class="user-name">{{ authStore.userName || '未知用户' }}</div>
                <q-chip
                  :color="authStore.userRole === 'admin' ? 'red' : 'blue'"
                  text-color="white"
                  size="sm"
                  class="role-chip"
                >
                  {{ authStore.userRole === 'admin' ? '管理员' : '普通用户' }}
                </q-chip>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section class="account-stats">
              <div class="stat-item">
                <q-icon name="schedule" color="primary" />
                <div class="stat-content">
                  <div class="stat-label">注册时间</div>
                  <div class="stat-value">{{ formatDate(profileForm.createdAt) }}</div>
                </div>
              </div>

              <div class="stat-item">
                <q-icon name="login" color="green" />
                <div class="stat-content">
                  <div class="stat-label">最后登录</div>
                  <div class="stat-value">{{ formatDate(profileForm.lastLoginAt) }}</div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- 右侧：表单区域 -->
        <div class="profile-content">
          <!-- 基本信息表单 -->
          <q-card class="info-card">
            <q-card-section>
              <div class="card-header">
                <div class="card-title">
                  <q-icon name="edit" class="card-icon" />
                  基本信息
                </div>
                <q-btn flat round icon="info" size="sm" color="grey-6">
                  <q-tooltip>编辑您的基本个人信息</q-tooltip>
                </q-btn>
              </div>

              <q-form @submit="handleUpdateProfile" class="profile-form">
                <div class="form-grid">
                  <div class="form-group">
                    <q-input
                      v-model="profileForm.username"
                      label="用户名"
                      outlined
                      readonly
                      class="form-input"
                      :rules="[(val) => !!val || '用户名不能为空']"
                    >
                      <template v-slot:prepend>
                        <q-icon name="account_circle" color="primary" />
                      </template>
                      <template v-slot:append>
                        <q-icon name="lock" color="grey-5" size="sm">
                          <q-tooltip>用户名不可修改</q-tooltip>
                        </q-icon>
                      </template>
                    </q-input>
                  </div>

                  <div class="form-group">
                    <q-input
                      v-model="profileForm.email"
                      label="邮箱地址"
                      type="email"
                      outlined
                      class="form-input"
                      :rules="[
                        (val) => !!val || '邮箱不能为空',
                        (val) => /.+@.+\..+/.test(val) || '请输入有效的邮箱地址',
                      ]"
                    >
                      <template v-slot:prepend>
                        <q-icon name="email" color="primary" />
                      </template>
                    </q-input>
                  </div>

                  <div class="form-group">
                    <q-input
                      v-model="profileForm.realName"
                      label="真实姓名"
                      outlined
                      class="form-input"
                      placeholder="请输入您的真实姓名"
                    >
                      <template v-slot:prepend>
                        <q-icon name="badge" color="primary" />
                      </template>
                    </q-input>
                  </div>

                  <div class="form-group">
                    <q-input
                      v-model="profileForm.phone"
                      label="联系电话"
                      outlined
                      class="form-input"
                      placeholder="请输入您的联系电话"
                    >
                      <template v-slot:prepend>
                        <q-icon name="phone" color="primary" />
                      </template>
                    </q-input>
                  </div>

                  <div class="form-group form-group-full">
                    <q-select
                      v-model="profileForm.role"
                      label="用户角色"
                      :options="roleOptions"
                      outlined
                      readonly
                      emit-value
                      map-options
                      class="form-input"
                    >
                      <template v-slot:prepend>
                        <q-icon name="admin_panel_settings" color="primary" />
                      </template>
                      <template v-slot:append>
                        <q-icon name="lock" color="grey-5" size="sm">
                          <q-tooltip>角色由管理员分配</q-tooltip>
                        </q-icon>
                      </template>
                    </q-select>
                  </div>
                </div>

                <div class="form-actions">
                  <q-btn
                    flat
                    label="重置"
                    color="grey-7"
                    @click="loadUserProfile"
                    class="action-btn"
                  />
                  <q-btn
                    type="submit"
                    color="primary"
                    label="保存更改"
                    :loading="updating"
                    unelevated
                    class="action-btn primary-btn"
                    :disable="!isProfileFormValid"
                  />
                </div>
              </q-form>
            </q-card-section>
          </q-card>

          <!-- 修改密码卡片 -->
          <q-card class="password-card">
            <q-card-section>
              <div class="card-header">
                <div class="card-title">
                  <q-icon name="lock" class="card-icon" />
                  修改密码
                </div>
                <q-btn flat round icon="security" size="sm" color="grey-6">
                  <q-tooltip>为了账户安全，请定期更换密码</q-tooltip>
                </q-btn>
              </div>

              <q-form @submit="handleChangePassword" class="password-form">
                <div class="password-grid">
                  <div class="password-group">
                    <q-input
                      v-model="passwordForm.currentPassword"
                      label="当前密码"
                      type="password"
                      outlined
                      class="password-input"
                      :rules="[(val) => !!val || '请输入当前密码']"
                    >
                      <template v-slot:prepend>
                        <q-icon name="lock_open" color="orange" />
                      </template>
                    </q-input>
                  </div>

                  <div class="password-group">
                    <q-input
                      v-model="passwordForm.newPassword"
                      label="新密码"
                      type="password"
                      outlined
                      class="password-input"
                      :rules="[
                        (val) => !!val || '请输入新密码',
                        (val) => val.length >= 6 || '密码长度至少6位',
                      ]"
                    >
                      <template v-slot:prepend>
                        <q-icon name="lock" color="green" />
                      </template>
                    </q-input>
                  </div>

                  <div class="password-group">
                    <q-input
                      v-model="passwordForm.confirmPassword"
                      label="确认新密码"
                      type="password"
                      outlined
                      class="password-input"
                      :rules="[
                        (val) => !!val || '请确认新密码',
                        (val) => val === passwordForm.newPassword || '两次输入的密码不一致',
                      ]"
                    >
                      <template v-slot:prepend>
                        <q-icon name="verified_user" color="green" />
                      </template>
                    </q-input>
                  </div>
                </div>

                <div class="password-actions">
                  <q-btn
                    flat
                    label="清空"
                    color="grey-7"
                    @click="resetPasswordForm"
                    class="action-btn"
                  />
                  <q-btn
                    type="submit"
                    color="red"
                    label="修改密码"
                    :loading="changingPassword"
                    :disable="!isPasswordFormValid"
                    unelevated
                    class="action-btn danger-btn"
                  />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import { useAuthStore } from '@/stores/auth';

// 定义组件名称
defineOptions({
  name: 'ProfilePage',
});

// 状态管理
const $q = useQuasar();
const authStore = useAuthStore();

// 响应式数据
const updating = ref(false);
const changingPassword = ref(false);

// 个人信息表单
const profileForm = ref({
  username: '',
  email: '',
  realName: '',
  phone: '',
  role: '',
  avatar: '',
  createdAt: '',
  lastLoginAt: '',
});

// 密码修改表单
const passwordForm = ref({
  currentPassword: '',
  newPassword: '',
  confirmPassword: '',
});

// 角色选项
const roleOptions = [
  { label: '管理员', value: 'admin' },
  { label: '普通用户', value: 'user' },
  { label: '访客', value: 'guest' },
];

// 计算属性
const isProfileFormValid = computed(() => {
  return (
    profileForm.value.username &&
    profileForm.value.email &&
    /.+@.+\..+/.test(profileForm.value.email)
  );
});

const isPasswordFormValid = computed(() => {
  return (
    passwordForm.value.currentPassword &&
    passwordForm.value.newPassword &&
    passwordForm.value.confirmPassword &&
    passwordForm.value.newPassword === passwordForm.value.confirmPassword &&
    passwordForm.value.newPassword.length >= 6
  );
});

// 格式化日期
const formatDate = (dateString: string) => {
  if (!dateString) return '-';
  try {
    const date = new Date(dateString);
    return date.toLocaleString('zh-CN', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return '-';
  }
};

// 处理个人信息更新
const handleUpdateProfile = () => {
  if (!isProfileFormValid.value) return;

  try {
    updating.value = true;

    authStore.updateProfile({
      email: profileForm.value.email,
    });

    $q.notify({
      type: 'positive',
      message: '个人信息更新成功',
      position: 'top',
      timeout: 2000,
    });
  } catch (error) {
    // 更新个人信息失败
    $q.notify({
      type: 'negative',
      message: error instanceof Error ? error.message : '更新失败，请重试',
      position: 'top',
      timeout: 2000,
    });
  } finally {
    updating.value = false;
  }
};

// 处理密码修改
const handleChangePassword = () => {
  if (!isPasswordFormValid.value) return;

  try {
    changingPassword.value = true;

    authStore.changePassword(
      passwordForm.value.currentPassword,
      passwordForm.value.newPassword,
    );

    // 清空表单
    passwordForm.value = {
      currentPassword: '',
      newPassword: '',
      confirmPassword: '',
    };

    $q.notify({
      type: 'positive',
      message: '密码修改成功',
      position: 'top',
      timeout: 2000,
    });
  } catch (error) {
    // 修改密码失败
    $q.notify({
      type: 'negative',
      message: error instanceof Error ? error.message : '密码修改失败，请重试',
      position: 'top',
      timeout: 2000,
    });
  } finally {
    changingPassword.value = false;
  }
};

// 重置密码表单
const resetPasswordForm = () => {
  passwordForm.value = {
    currentPassword: '',
    newPassword: '',
    confirmPassword: '',
  };
};

// 处理头像上传
const handleAvatarUpload = () => {
  $q.notify({
    type: 'info',
    message: '头像上传功能暂未开放',
    position: 'top',
    timeout: 2000,
  });
};

// 加载用户信息
const loadUserProfile = async () => {
  try {
    await authStore.fetchUserInfo();

    // 填充表单数据
    const user = authStore.user;
    if (user) {
      profileForm.value = {
        username: user.username || '',
        email: user.email || '',
        realName: '',
        phone: '',
        role: user.role || '',
        avatar: '',
        createdAt: user.created_at || '',
        lastLoginAt: user.last_login || '',
      };
    }
  } catch (error) {
    // 加载用户信息失败
    console.error('加载用户信息失败:', error);
    $q.notify({
      type: 'negative',
      message: '加载用户信息失败',
      position: 'top',
      timeout: 2000,
    });
  }
};

// 组件挂载时加载数据
onMounted(() => {
  void loadUserProfile();
});
</script>

<style scoped>
.profile-page {
  padding: 20px;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 32px;
  padding: 24px;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 16px;
  backdrop-filter: blur(10px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.title-section {
  flex: 1;
}

.page-title {
  margin: 0 0 8px 0;
  color: #1976d2;
  font-weight: 600;
  font-size: 2rem;
  display: flex;
  align-items: center;
  gap: 12px;
}

.title-icon {
  font-size: 2.2rem;
}

.refresh-btn {
  border-radius: 12px;
  padding: 8px 16px;
  font-weight: 500;
}

.profile-layout {
  display: grid;
  grid-template-columns: 350px 1fr;
  gap: 24px;
  align-items: start;
}

/* 左侧边栏样式 */
.profile-sidebar {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.avatar-card {
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
}

.avatar-section {
  text-align: center;
  padding: 32px 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  position: relative;
}

.avatar-container {
  position: relative;
  display: inline-block;
  margin-bottom: 20px;
}

.user-avatar {
  border: 4px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.avatar-upload-btn {
  position: absolute;
  bottom: -5px;
  right: -5px;
  background: #fff;
  color: #1976d2;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.user-info {
  text-align: center;
}

.user-name {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 8px;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.role-chip {
  font-weight: 500;
  border-radius: 20px;
}

.account-stats {
  padding: 24px;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 0;
  border-bottom: 1px solid #f0f0f0;
}

.stat-item:last-child {
  border-bottom: none;
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: #666;
  margin-bottom: 4px;
}

.stat-value {
  font-weight: 500;
  color: #333;
}

/* 右侧内容区域 */
.profile-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.info-card,
.password-card {
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 2px solid #f0f0f0;
}

.card-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #333;
  display: flex;
  align-items: center;
  gap: 8px;
}

.card-icon {
  color: #1976d2;
}

/* 表单样式 */
.profile-form {
  padding: 0;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.form-group-full {
  grid-column: 1 / -1;
}

.form-input {
  border-radius: 12px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid #f0f0f0;
}

.action-btn {
  border-radius: 12px;
  padding: 10px 24px;
  font-weight: 500;
  min-width: 100px;
}

.primary-btn {
  background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
  box-shadow: 0 4px 15px rgba(25, 118, 210, 0.3);
}

/* 密码表单样式 */
.password-form {
  padding: 0;
}

.password-grid {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.password-input {
  border-radius: 12px;
}

.password-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid #f0f0f0;
}

.danger-btn {
  background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
  box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
}

/* 响应式设计 */
@media (max-width: 1024px) {
  .profile-layout {
    grid-template-columns: 1fr;
    gap: 20px;
  }

  .profile-sidebar {
    order: 2;
  }

  .profile-content {
    order: 1;
  }
}

@media (max-width: 768px) {
  .profile-page {
    padding: 16px;
  }

  .page-header {
    padding: 20px;
    margin-bottom: 24px;
  }

  .header-content {
    flex-direction: column;
    align-items: stretch;
  }

  .page-title {
    font-size: 1.75rem;
    text-align: center;
  }

  .refresh-btn {
    align-self: center;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-actions,
  .password-actions {
    flex-direction: column;
  }

  .action-btn {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .avatar-section {
    padding: 24px 16px;
  }

  .user-avatar {
    width: 80px;
    height: 80px;
  }

  .user-name {
    font-size: 1.25rem;
  }

  .card-header {
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
  }
}

.page-subtitle {
  margin: 0;
  color: #666;
  font-size: 14px;
}

.profile-card,
.avatar-card,
.password-card {
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

.avatar-container {
  position: relative;
  display: inline-block;
}

.avatar {
  border: 4px solid #e0e0e0;
}

.avatar-upload-btn {
  position: absolute;
  bottom: 0;
  right: 0;
}

.account-info {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.info-item .text-caption {
  min-width: 60px;
}

@media (max-width: 768px) {
  .profile-page {
    padding: 16px;
  }

  .row {
    margin: 0;
  }

  .col-12 {
    padding: 0;
    margin-bottom: 16px;
  }
}
</style>
