<template>
  <q-page class="settings-page">
    <div class="container">
      <!-- 页面标题 -->
      <div class="page-header">
        <div class="header-content">
          <div class="title-section">
            <div class="title-wrapper">
              <div class="title-icon-wrapper">
                <q-icon name="settings" class="title-icon" />
              </div>
              <div class="title-text">
                <h1 class="page-title">系统设置</h1>
                <p class="page-subtitle">管理系统配置和偏好设置</p>
              </div>
            </div>
          </div>
          <div class="header-actions">
            <q-btn
              color="primary"
              icon="save"
              label="保存设置"
              :loading="saving"
              @click="handleSaveSettings"
              class="action-btn primary-btn"
              unelevated
            />
            <q-btn
              color="grey-7"
              icon="restore"
              label="重置"
              outline
              @click="handleResetSettings"
              class="action-btn secondary-btn"
            />
          </div>
        </div>
      </div>

      <!-- 设置卡片网格 -->
      <div class="settings-grid">
        <!-- 第一行：界面和显示设置 -->
        <div class="settings-row primary-row">
          <!-- 显示设置卡片 -->
          <q-card class="setting-card display-card primary-card">
            <q-card-section class="card-header">
              <div class="card-title-wrapper">
                <div class="card-icon-wrapper display-icon">
                  <q-icon name="display_settings" class="card-icon" />
                </div>
                <div class="card-title-content">
                  <h3 class="card-title">显示设置</h3>
                  <p class="card-subtitle">界面显示和主题配置</p>
                </div>
              </div>
              <q-btn flat round icon="info_outline" size="sm" color="grey-6" class="info-btn">
                <q-tooltip>配置界面显示和主题</q-tooltip>
              </q-btn>
            </q-card-section>

            <q-separator />

            <q-card-section class="card-content">
              <div class="setting-items">
                <!-- 主题模式 -->
                <div class="setting-item featured-item">
                  <div class="setting-icon-wrapper theme-icon">
                    <q-icon name="palette" />
                  </div>
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">主题模式</h4>
                      <q-chip
                        :label="themeOptions.find((opt) => opt.value === systemSettings.theme)?.label"
                        color="accent"
                        text-color="white"
                        size="sm"
                      />
                    </div>
                    <p class="setting-description">选择界面的主题外观</p>
                    <q-btn-toggle
                      v-model="systemSettings.theme"
                      :options="themeOptions"
                      outline
                      class="theme-toggle"
                      @update:model-value="handleThemeChange"
                    />
                  </div>
                </div>

                <!-- 语言设置 -->
                <div class="setting-item">
                  <div class="setting-icon-wrapper language-icon">
                    <q-icon name="language" />
                  </div>
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">语言设置</h4>
                      <q-chip
                        :label="
                          languageOptions.find((opt) => opt.value === systemSettings.language)?.label
                        "
                        color="info"
                        text-color="white"
                        size="sm"
                      />
                    </div>
                    <p class="setting-description">选择界面显示语言</p>
                    <q-select
                      v-model="systemSettings.language"
                      :options="languageOptions"
                      outlined
                      dense
                      emit-value
                      map-options
                      class="setting-control"
                      @update:model-value="handleSettingChange"
                    />
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>

          <!-- 系统性能卡片 -->
          <q-card class="setting-card performance-card primary-card">
            <q-card-section class="card-header">
              <div class="card-title-wrapper">
                <div class="card-icon-wrapper performance-icon">
                  <q-icon name="speed" class="card-icon" />
                </div>
                <div class="card-title-content">
                  <h3 class="card-title">性能设置</h3>
                  <p class="card-subtitle">系统性能和数据配置</p>
                </div>
              </div>
              <q-btn flat round icon="info_outline" size="sm" color="grey-6" class="info-btn">
                <q-tooltip>配置系统性能参数</q-tooltip>
              </q-btn>
            </q-card-section>

            <q-separator />

            <q-card-section class="card-content">
              <div class="setting-items">
                <!-- 自动刷新间隔 -->
                <div class="setting-item featured-item">
                  <div class="setting-icon-wrapper refresh-icon">
                    <q-icon name="refresh" />
                  </div>
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">自动刷新间隔</h4>
                      <q-chip
                        :label="
                          refreshIntervalOptions.find(
                            (opt) => opt.value === systemSettings.refreshInterval,
                          )?.label
                        "
                        color="primary"
                        text-color="white"
                        size="sm"
                      />
                    </div>
                    <p class="setting-description">设置数据自动刷新的时间间隔</p>
                    <q-select
                      v-model="systemSettings.refreshInterval"
                      :options="refreshIntervalOptions"
                      outlined
                      dense
                      emit-value
                      map-options
                      class="setting-control"
                      @update:model-value="handleSettingChange"
                    />
                  </div>
                </div>

                <!-- 每页显示数量 -->
                <div class="setting-item">
                  <div class="setting-icon-wrapper list-icon">
                    <q-icon name="view_list" />
                  </div>
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">每页显示数量</h4>
                      <q-chip
                        :label="systemSettings.pageSize.toString()"
                        color="secondary"
                        text-color="white"
                        size="sm"
                      />
                    </div>
                    <p class="setting-description">设置列表页面每页显示的项目数量</p>
                    <q-select
                      v-model="systemSettings.pageSize"
                      :options="pageSizeOptions"
                      outlined
                      dense
                      emit-value
                      map-options
                      class="setting-control"
                      @update:model-value="handleSettingChange"
                    />
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- 第二行：通知和安全设置 -->
        <div class="settings-row secondary-row">
          <!-- 通知设置卡片 -->
          <q-card class="setting-card notification-card secondary-card">
            <q-card-section class="card-header">
              <div class="card-title-wrapper">
                <div class="card-icon-wrapper notification-icon">
                  <q-icon name="notifications" class="card-icon" />
                </div>
                <div class="card-title-content">
                  <h3 class="card-title">通知设置</h3>
                  <p class="card-subtitle">系统通知和告警配置</p>
                </div>
              </div>
              <q-btn flat round icon="info_outline" size="sm" color="grey-6" class="info-btn">
                <q-tooltip>配置系统通知和告警</q-tooltip>
              </q-btn>
            </q-card-section>

            <q-separator />

            <q-card-section class="card-content">
              <div class="setting-items">
                <!-- 设备离线通知 -->
                <div class="setting-item toggle-item">
                  <div class="setting-icon-wrapper warning-icon">
                    <q-icon name="warning" />
                  </div>
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">设备离线通知</h4>
                      <q-toggle
                        v-model="notificationSettings.deviceOffline"
                        color="warning"
                        size="md"
                        @update:model-value="handleNotificationChange"
                      />
                    </div>
                    <p class="setting-description">当设备离线时发送通知提醒</p>
                  </div>
                </div>

                <!-- 资源使用率告警 -->
                <div class="setting-item toggle-item">
                  <div class="setting-icon-wrapper error-icon">
                    <q-icon name="memory" />
                  </div>
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">资源使用率告警</h4>
                      <q-toggle
                        v-model="notificationSettings.resourceAlert"
                        color="negative"
                        size="md"
                        @update:model-value="handleNotificationChange"
                      />
                    </div>
                    <p class="setting-description">当CPU或内存使用率过高时告警</p>
                  </div>
                </div>

                <!-- 温度告警 -->
                <div class="setting-item toggle-item">
                  <div class="setting-icon-wrapper temperature-icon">
                    <q-icon name="thermostat" />
                  </div>
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">温度告警</h4>
                      <q-toggle
                        v-model="notificationSettings.temperatureAlert"
                        color="deep-orange"
                        size="md"
                        @update:model-value="handleNotificationChange"
                      />
                    </div>
                    <p class="setting-description">当设备温度异常时发送告警</p>
                  </div>
                </div>

                <!-- 邮件通知 -->
                <div class="setting-item toggle-item">
                  <div class="setting-icon-wrapper email-icon">
                    <q-icon name="email" />
                  </div>
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">邮件通知</h4>
                      <q-toggle
                        v-model="notificationSettings.emailNotification"
                        color="info"
                        size="md"
                        @update:model-value="handleNotificationChange"
                      />
                    </div>
                    <p class="setting-description">通过邮件接收重要通知</p>
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>

          <!-- 安全设置卡片 -->
          <q-card class="setting-card security-card secondary-card">
            <q-card-section class="card-header">
              <div class="card-title-wrapper">
                <div class="card-icon-wrapper security-icon">
                  <q-icon name="security" class="card-icon" />
                </div>
                <div class="card-title-content">
                  <h3 class="card-title">安全设置</h3>
                  <p class="card-subtitle">系统安全和认证配置</p>
                </div>
              </div>
              <q-btn flat round icon="info_outline" size="sm" color="grey-6" class="info-btn">
                <q-tooltip>配置系统安全策略</q-tooltip>
              </q-btn>
            </q-card-section>

            <q-separator />

            <q-card-section class="card-content">
              <div class="setting-items">
                <!-- 会话超时时间 -->
                <div class="setting-item">
                  <div class="setting-icon-wrapper primary-icon">
                    <q-icon name="schedule" />
                  </div>
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">会话超时时间</h4>
                      <q-chip
                        :label="
                          sessionTimeoutOptions.find(
                            (opt) => opt.value === securitySettings.sessionTimeout,
                          )?.label
                        "
                        color="primary"
                        text-color="white"
                        size="sm"
                      />
                    </div>
                    <p class="setting-description">设置用户会话的自动超时时间</p>
                    <q-select
                      v-model="securitySettings.sessionTimeout"
                      :options="sessionTimeoutOptions"
                      outlined
                      dense
                      emit-value
                      map-options
                      class="setting-control"
                      @update:model-value="handleSecurityChange"
                    />
                  </div>
                </div>

                <!-- 自动锁定 -->
                <div class="setting-item toggle-item">
                  <div class="setting-icon-wrapper success-icon">
                    <q-icon name="lock" />
                  </div>
                  <div class="setting-content">
                    <div class="setting-header">
                      <h4 class="setting-title">自动锁定</h4>
                      <q-toggle
                        v-model="securitySettings.autoLock"
                        color="positive"
                        size="md"
                        @update:model-value="handleSecurityChange"
                      />
                    </div>
                    <p class="setting-description">启用自动锁定功能保护系统安全</p>
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- 第三行：系统管理 -->
        <div class="settings-row management-row">
          <!-- 数据管理卡片 -->
          <q-card class="setting-card data-card management-card">
            <q-card-section class="card-header">
              <div class="card-title-wrapper">
                <div class="card-icon-wrapper data-icon">
                  <q-icon name="storage" class="card-icon" />
                </div>
                <div class="card-title-content">
                  <h3 class="card-title">数据管理</h3>
                  <p class="card-subtitle">数据备份和清理功能</p>
                </div>
              </div>
              <q-btn flat round icon="info_outline" size="sm" color="grey-6" class="info-btn">
                <q-tooltip>管理系统数据和缓存</q-tooltip>
              </q-btn>
            </q-card-section>

            <q-separator />

            <q-card-section class="card-content">
              <div class="management-actions">
                <!-- 导出配置 -->
                <div class="management-item">
                  <div class="management-icon-wrapper export-icon">
                    <q-icon name="download" />
                  </div>
                  <div class="management-content">
                    <h4 class="management-title">导出配置</h4>
                    <p class="management-description">导出当前所有配置</p>
                    <q-btn
                      color="info"
                      label="导出"
                      icon="download"
                      size="sm"
                      outline
                      @click="handleExportSettings"
                      class="management-btn"
                    />
                  </div>
                </div>

                <!-- 清除缓存 -->
                <div class="management-item">
                  <div class="management-icon-wrapper cache-icon">
                    <q-icon name="clear_all" />
                  </div>
                  <div class="management-content">
                    <h4 class="management-title">清除缓存</h4>
                    <p class="management-description">清理系统缓存数据</p>
                    <q-btn
                      color="warning"
                      label="清除"
                      icon="clear_all"
                      size="sm"
                      outline
                      @click="handleClearCache"
                      class="management-btn"
                    />
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>

          <!-- 系统操作卡片 -->
          <q-card class="setting-card system-operations-card management-card">
            <q-card-section class="card-header">
              <div class="card-title-wrapper">
                <div class="card-icon-wrapper system-icon">
                  <q-icon name="build" class="card-icon" />
                </div>
                <div class="card-title-content">
                  <h3 class="card-title">系统操作</h3>
                  <p class="card-subtitle">系统维护和管理操作</p>
                </div>
              </div>
              <q-btn flat round icon="info_outline" size="sm" color="grey-6" class="info-btn">
                <q-tooltip>系统维护和管理操作</q-tooltip>
              </q-btn>
            </q-card-section>

            <q-separator />

            <q-card-section class="card-content">
              <div class="operations-grid">
                <!-- 保存所有设置 -->
                <div class="operation-item featured-operation">
                  <div class="operation-icon-wrapper save-icon">
                    <q-icon name="save" />
                  </div>
                  <div class="operation-content">
                    <h4 class="operation-title">保存设置</h4>
                    <p class="operation-description">保存所有配置更改</p>
                    <q-btn
                      color="primary"
                      label="保存"
                      icon="save"
                      class="operation-btn"
                      unelevated
                      :loading="saving"
                      @click="handleSaveSettings"
                    />
                  </div>
                </div>

                <!-- 重置为默认 -->
                <div class="operation-item">
                  <div class="operation-icon-wrapper reset-icon">
                    <q-icon name="restore" />
                  </div>
                  <div class="operation-content">
                    <h4 class="operation-title">重置设置</h4>
                    <p class="operation-description">恢复所有默认配置</p>
                    <q-btn
                      color="secondary"
                      label="重置"
                      icon="restore"
                      class="operation-btn"
                      outline
                      @click="handleResetSettings"
                    />
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import EncryptedStorage from '../utils/encryption';

// 定义组件名称
defineOptions({
  name: 'SettingsPage',
});

// 状态管理
const $q = useQuasar();

// 响应式数据
const saving = ref(false);

// 系统设置
const systemSettings = ref({
  refreshInterval: 30,
  pageSize: 20,
  theme: 'auto',
  language: 'zh-CN',
});

// 通知设置
const notificationSettings = ref({
  deviceOffline: true,
  resourceAlert: true,
  temperatureAlert: true,
  emailNotification: false,
});

// 安全设置
const securitySettings = ref({
  sessionTimeout: 3600,
  autoLock: true,
});

// 选项配置
const refreshIntervalOptions = [
  { label: '10秒', value: 10 },
  { label: '30秒', value: 30 },
  { label: '1分钟', value: 60 },
  { label: '5分钟', value: 300 },
  { label: '10分钟', value: 600 },
];

const pageSizeOptions = [
  { label: '10', value: 10 },
  { label: '20', value: 20 },
  { label: '50', value: 50 },
  { label: '100', value: 100 },
  { label: '200', value: 200 },
  { label: '500', value: 500 },
];

const themeOptions = [
  { label: '自动', value: 'auto', icon: 'brightness_auto' },
  { label: '浅色', value: 'light', icon: 'light_mode' },
  { label: '深色', value: 'dark', icon: 'dark_mode' },
];

const languageOptions = [
  { label: '简体中文', value: 'zh-CN' },
  { label: 'English', value: 'en-US' },
];

const sessionTimeoutOptions = [
  { label: '30分钟', value: 1800 },
  { label: '1小时', value: 3600 },
  { label: '2小时', value: 7200 },
  { label: '4小时', value: 14400 },
  { label: '8小时', value: 28800 },
];

// 处理设置变更
const handleSettingChange = () => {
  // 保存到本地存储
  EncryptedStorage.setItem('systemSettings', JSON.stringify(systemSettings.value));

  $q.notify({
    type: 'positive',
    message: '设置已更新',
    position: 'top',
    timeout: 2000,
  });
};

const handleNotificationChange = () => {
  EncryptedStorage.setItem('notificationSettings', JSON.stringify(notificationSettings.value));

  $q.notify({
    type: 'positive',
    message: '通知设置已更新',
    position: 'top',
    timeout: 2000,
  });
};

const handleSecurityChange = () => {
  EncryptedStorage.setItem('securitySettings', JSON.stringify(securitySettings.value));

  $q.notify({
    type: 'positive',
    message: '安全设置已更新',
    position: 'top',
    timeout: 2000,
  });
};

const handleThemeChange = (theme: string) => {
  // 应用主题
  if (theme === 'dark') {
    $q.dark.set(true);
  } else if (theme === 'light') {
    $q.dark.set(false);
  } else {
    $q.dark.set('auto');
  }

  handleSettingChange();
};

// 保存所有设置
const handleSaveSettings = async () => {
  try {
    saving.value = true;

    // 模拟API调用
    await new Promise((resolve) => setTimeout(resolve, 1000));

    // 保存到本地存储
    EncryptedStorage.setItem('systemSettings', JSON.stringify(systemSettings.value));
    EncryptedStorage.setItem('notificationSettings', JSON.stringify(notificationSettings.value));
    EncryptedStorage.setItem('securitySettings', JSON.stringify(securitySettings.value));

    $q.notify({
      type: 'positive',
      message: '所有设置已保存',
      position: 'top',
      timeout: 2000,
    });
  } catch (error) {
    // 保存设置失败
    console.error('保存设置失败:', error);
    $q.notify({
      type: 'negative',
      message: '保存设置失败，请重试',
      position: 'top',
      timeout: 2000,
    });
  } finally {
    saving.value = false;
  }
};

// 重置设置
const handleResetSettings = () => {
  $q.dialog({
    title: '确认重置',
    message: '您确定要重置所有设置为默认值吗？此操作不可撤销。',
    cancel: true,
    persistent: true,
  }).onOk(() => {
    // 重置为默认值
    systemSettings.value = {
      refreshInterval: 30,
      pageSize: 20,
      theme: 'auto',
      language: 'zh-CN',
    };

    notificationSettings.value = {
      deviceOffline: true,
      resourceAlert: true,
      temperatureAlert: true,
      emailNotification: false,
    };

    securitySettings.value = {
      sessionTimeout: 3600,
      autoLock: true,
    };

    // 清除本地存储
    EncryptedStorage.removeItem('systemSettings');
    EncryptedStorage.removeItem('notificationSettings');
    EncryptedStorage.removeItem('securitySettings');

    // 重置主题
    $q.dark.set('auto');

    $q.notify({
      type: 'positive',
      message: '设置已重置为默认值',
      position: 'top',
      timeout: 2000,
    });
  });
};

// 导出配置
const handleExportSettings = () => {
  const settings = {
    system: systemSettings.value,
    notification: notificationSettings.value,
    security: securitySettings.value,
    exportTime: new Date().toISOString(),
  };

  const dataStr = JSON.stringify(settings, null, 2);
  const dataBlob = new Blob([dataStr], { type: 'application/json' });
  const url = URL.createObjectURL(dataBlob);

  const link = document.createElement('a');
  link.href = url;
  link.download = `settings-${new Date().toISOString().split('T')[0]}.json`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);

  $q.notify({
    type: 'positive',
    message: '配置已导出',
    position: 'top',
    timeout: 2000,
  });
};

// 清除缓存
const handleClearCache = () => {
  $q.dialog({
    title: '确认清除',
    message: '您确定要清除所有缓存数据吗？',
    cancel: true,
    persistent: true,
  }).onOk(() => {
    // 清除相关缓存
    const keysToKeep = [
      'systemSettings',
      'notificationSettings',
      'securitySettings',
      'auth_token',
      'auth_user',
    ];
    const keysToRemove = [];

    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      if (key && !keysToKeep.includes(key)) {
        keysToRemove.push(key);
      }
    }

    keysToRemove.forEach((key) => localStorage.removeItem(key));

    $q.notify({
      type: 'positive',
      message: '缓存已清除',
      position: 'top',
      timeout: 2000,
    });
  });
};

// 加载设置
const loadSettings = () => {
  try {
    // 加载系统设置
    const savedSystemSettings = EncryptedStorage.getItem('systemSettings');
    if (savedSystemSettings) {
      systemSettings.value = { ...systemSettings.value, ...JSON.parse(savedSystemSettings) };
    }

    // 加载通知设置
    const savedNotificationSettings = EncryptedStorage.getItem('notificationSettings');
    if (savedNotificationSettings) {
      notificationSettings.value = {
        ...notificationSettings.value,
        ...JSON.parse(savedNotificationSettings),
      };
    }

    // 加载安全设置
    const savedSecuritySettings = EncryptedStorage.getItem('securitySettings');
    if (savedSecuritySettings) {
      securitySettings.value = { ...securitySettings.value, ...JSON.parse(savedSecuritySettings) };
    }

    // 应用主题设置
    handleThemeChange(systemSettings.value.theme);
  } catch {
    // 加载设置失败
  }
};

// 组件挂载时加载设置
onMounted(() => {
  loadSettings();
});
</script>

<style scoped>
.settings-page {
  padding: 24px;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 32px;
  padding: 24px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}

.title-section {
  flex: 1;
}

.title-wrapper {
  display: flex;
  align-items: center;
  gap: 16px;
}

.title-icon-wrapper {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #1976d2, #42a5f5);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.title-icon {
  font-size: 24px;
  color: white;
}

.title-text {
  flex: 1;
}

.page-title {
  margin: 0 0 4px 0;
  color: #1976d2;
  font-weight: 600;
  font-size: 28px;
  line-height: 1.2;
}

.page-subtitle {
  margin: 0;
  color: #666;
  font-size: 16px;
  opacity: 0.8;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.action-btn {
  min-width: 120px;
  height: 44px;
  font-weight: 600;
  border-radius: 10px;
  transition: all 0.2s ease;
}

.primary-btn {
  box-shadow: 0 2px 8px rgba(25, 118, 210, 0.3);
}

.primary-btn:hover {
  box-shadow: 0 4px 12px rgba(25, 118, 210, 0.4);
  transform: translateY(-1px);
}

.secondary-btn:hover {
  transform: translateY(-1px);
}

/* 设置网格 */
.settings-grid {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.settings-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
}

.management-row {
  grid-template-columns: 1fr 1fr;
}

/* 卡片样式 */
.setting-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;
  overflow: hidden;
  position: relative;
}

.setting-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

/* 卡片优先级样式 */
.primary-card {
  border-left: 4px solid #1976d2;
}

.secondary-card {
  border-left: 4px solid #f57c00;
}

.management-card {
  border-left: 4px solid #388e3c;
}

/* 卡片头部 */
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24px 24px 20px 24px;
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.card-title-wrapper {
  display: flex;
  align-items: center;
  gap: 16px;
  flex: 1;
}

.card-icon-wrapper {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.card-icon {
  font-size: 24px;
  color: white;
}

/* 图标主题色 */
.display-icon {
  background: linear-gradient(135deg, #1976d2, #42a5f5);
}

.performance-icon {
  background: linear-gradient(135deg, #7b1fa2, #ba68c8);
}

.notification-icon {
  background: linear-gradient(135deg, #f57c00, #ffb74d);
}

.security-icon {
  background: linear-gradient(135deg, #388e3c, #66bb6a);
}

.data-icon {
  background: linear-gradient(135deg, #d32f2f, #f44336);
}

.system-icon {
  background: linear-gradient(135deg, #1976d2, #42a5f5);
}

.card-title-content {
  flex: 1;
}

.card-title {
  font-size: 1.4rem;
  font-weight: 600;
  color: #333;
  margin: 0 0 4px 0;
  line-height: 1.3;
}

.card-subtitle {
  font-size: 0.9rem;
  color: #666;
  opacity: 0.8;
  margin: 0;
  line-height: 1.4;
}

.info-btn {
  opacity: 0.6;
  transition: opacity 0.2s ease;
}

.info-btn:hover {
  opacity: 1;
}

/* 卡片内容 */
.card-content {
  padding: 24px;
}

.setting-items {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.setting-item {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 12px;
  border: 1px solid rgba(0, 0, 0, 0.04);
  transition: all 0.2s ease;
  position: relative;
}

.setting-item:hover {
  background: #f0f2f5;
  border-color: #1976d2;
  transform: translateX(4px);
}

.setting-item.featured-item {
  background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
  border: 2px solid #1976d2;
}

.setting-item.featured-item:hover {
  background: linear-gradient(135deg, #bbdefb 0%, #e1bee7 100%);
  transform: translateX(6px);
}

.setting-item.toggle-item {
  align-items: center;
}

.setting-icon-wrapper {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.setting-icon-wrapper .q-icon {
  font-size: 20px;
  color: white;
}

/* 设置项图标主题色 */
.theme-icon {
  background: linear-gradient(135deg, #e91e63, #f48fb1);
}

.language-icon {
  background: linear-gradient(135deg, #00acc1, #4dd0e1);
}

.refresh-icon {
  background: linear-gradient(135deg, #1976d2, #42a5f5);
}

.list-icon {
  background: linear-gradient(135deg, #7b1fa2, #ba68c8);
}

.warning-icon {
  background: linear-gradient(135deg, #f57c00, #ffb74d);
}

.error-icon {
  background: linear-gradient(135deg, #d32f2f, #f44336);
}

.temperature-icon {
  background: linear-gradient(135deg, #e64a19, #ff7043);
}

.email-icon {
  background: linear-gradient(135deg, #1976d2, #42a5f5);
}

.primary-icon {
  background: linear-gradient(135deg, #1976d2, #42a5f5);
}

.success-icon {
  background: linear-gradient(135deg, #388e3c, #66bb6a);
}

.setting-content {
  flex: 1;
  min-width: 0;
}

.setting-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.setting-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #333;
  margin: 0;
  line-height: 1.3;
}

.setting-description {
  font-size: 0.9rem;
  color: #666;
  opacity: 0.8;
  line-height: 1.5;
  margin: 0 0 12px 0;
}

.setting-control {
  margin-top: 12px;
}

.theme-toggle {
  margin-top: 12px;
}

/* 管理操作样式 */
.management-actions {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
}

.management-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 12px;
  border: 1px solid rgba(0, 0, 0, 0.04);
  transition: all 0.2s ease;
}

.management-item:hover {
  background: #f0f2f5;
  border-color: #1976d2;
  transform: translateX(4px);
}

.management-icon-wrapper {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.management-icon-wrapper .q-icon {
  font-size: 20px;
  color: white;
}

.export-icon {
  background: linear-gradient(135deg, #1976d2, #42a5f5);
}

.cache-icon {
  background: linear-gradient(135deg, #f57c00, #ffb74d);
}

.management-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.management-title {
  font-size: 1rem;
  font-weight: 600;
  color: #333;
  margin: 0;
  line-height: 1.3;
}

.management-description {
  font-size: 0.85rem;
  color: #666;
  opacity: 0.8;
  line-height: 1.4;
  margin: 0;
}

.management-btn {
  align-self: flex-start;
}

/* 操作按钮网格 */
.operations-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 20px;
}

.operation-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 12px;
  border: 1px solid rgba(0, 0, 0, 0.04);
  transition: all 0.2s ease;
}

.operation-item:hover {
  background: #f0f2f5;
  border-color: #1976d2;
  transform: translateX(4px);
}

.featured-operation {
  background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
  border: 2px solid #1976d2;
}

.featured-operation:hover {
  background: linear-gradient(135deg, #bbdefb 0%, #e1bee7 100%);
  transform: translateX(6px);
}

.operation-icon-wrapper {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.operation-icon-wrapper .q-icon {
  font-size: 24px;
  color: white;
}

.save-icon {
  background: linear-gradient(135deg, #1976d2, #42a5f5);
}

.reset-icon {
  background: linear-gradient(135deg, #f57c00, #ffb74d);
}

.operation-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.operation-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #333;
  margin: 0;
  line-height: 1.3;
}

.operation-description {
  font-size: 0.9rem;
  color: #666;
  opacity: 0.8;
  line-height: 1.4;
  margin: 0;
}

.operation-btn {
  align-self: flex-start;
  min-width: 100px;
  height: 40px;
  font-weight: 600;
  border-radius: 10px;
  transition: all 0.2s ease;
}

.operation-btn:hover {
  transform: translateY(-2px);
}

/* 响应式设计 */
@media (max-width: 1024px) {
  .settings-row {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .settings-grid {
    gap: 20px;
  }
}

@media (max-width: 768px) {
  .settings-page {
    padding: 16px;
    background: #f5f7fa;
  }

  .header-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .header-actions {
    width: 100%;
    justify-content: stretch;
  }

  .action-btn {
    flex: 1;
  }

  .page-title {
    font-size: 24px;
  }

  .settings-grid {
    gap: 16px;
  }

  .operations-grid {
    gap: 12px;
  }

  .management-actions {
    gap: 12px;
  }

  .setting-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
  }

  .setting-item.toggle-item {
    flex-direction: row;
    align-items: center;
  }

  .operation-item {
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 12px;
    padding: 16px;
  }

  .management-item {
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 12px;
    padding: 16px;
  }

  .setting-header {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }

  .setting-control {
    width: 100%;
  }

  .card-header {
    padding: 20px 20px 16px 20px;
  }

  .card-content {
    padding: 20px;
  }
}

@media (max-width: 480px) {
  .settings-page {
    padding: 12px;
  }

  .title-wrapper {
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 12px;
  }

  .page-title {
    font-size: 20px;
  }

  .card-title {
    font-size: 1.2rem;
  }

  .setting-title {
    font-size: 1rem;
  }

  .operation-title {
    font-size: 1.1rem;
  }

  .management-title {
    font-size: 0.95rem;
  }
}
</style>