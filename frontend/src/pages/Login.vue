<template>
  <q-page class="login-page">
    <!-- 增强的动态背景装饰 -->
    <q-scroll-area class="background-decoration">
      <!-- 渐变背景网格 -->
      <q-separator class="grid-pattern" />
      
      <!-- 3D浮动粒子效果 -->
      <q-parallax class="floating-particles">
        <q-chip v-for="i in 15" :key="i" :class="`particle particle-${i}`" color="transparent" size="sm" />
      </q-parallax>
      
      <!-- 光效扫描线 -->
      <q-linear-progress class="light-beams" indeterminate color="transparent">
        <q-linear-progress class="beam beam-1" indeterminate color="white" size="2px" />
        <q-linear-progress class="beam beam-2" indeterminate color="white" size="2px" />
      </q-linear-progress>
    </q-scroll-area>

    <!-- 增强的登录卡片 -->
    <q-page-container class="login-container">
      <q-card class="login-card enhanced-glass-card" flat>
        <!-- 头部优化 -->
        <q-card-section class="text-center q-pb-none login-header">
          <q-item class="login-logo-container">
            <q-item-section class="logo-wrapper">
              <q-separator class="logo-glow" />
              <q-icon name="memory" class="logo-icon" />
              <q-spinner-radio class="logo-pulse" size="sm" color="white" />
              <q-circular-progress class="logo-ring" indeterminate size="120px" color="white" track-color="transparent" />
            </q-item-section>
          </q-item>
          <q-item-label class="login-title text-h4">
            <q-item-label class="title-text">🇨🇳 Armbian 监控</q-item-label>
            <q-separator class="title-underline" />
          </q-item-label>
          <q-item-label caption class="login-subtitle">
            <q-icon name="military_tech" class="subtitle-icon" />
            庆祝2025年9月3日大阅兵 · 现代化设备管理平台
          </q-item-label>
        </q-card-section>

        <!-- 增强的登录表单 -->
        <q-card-section class="login-form-section">
          <q-form @submit="handleLogin" class="login-form" ref="loginFormRef">
            <!-- 用户名输入 - 增加焦点效果 -->
            <q-item class="input-group">
              <q-item-section class="input-wrapper">
                <q-input
                  v-model="loginForm.username"
                  type="text"
                  label="用户名"
                  outlined
                  class="enhanced-input"
                  :rules="usernameRules"
                  :disable="loading"
                  :error="usernameError"
                  :error-message="usernameErrorMessage"
                  @blur="validateUsername"
                  @input="clearUsernameError"
                  @focus="onInputFocus('username')"
                  ref="usernameInputRef"
                >
                  <template v-slot:prepend>
                    <q-item-section side class="input-icon-wrapper">
                      <q-icon name="person" class="input-icon" />
                      <q-spinner-dots class="icon-ripple" v-if="focusedInput === 'username'" size="sm" />
                    </q-item-section>
                  </template>
                </q-input>
                <q-separator class="input-border-animation" :class="{ active: focusedInput === 'username' }" />
              </q-item-section>
            </q-item>

            <!-- 密码输入 - 增加焦点效果 -->
            <q-item class="input-group">
              <q-item-section class="input-wrapper">
                <q-input
                  v-model="loginForm.password"
                  :type="showPassword ? 'text' : 'password'"
                  label="密码"
                  outlined
                  class="enhanced-input"
                  :rules="passwordRules"
                  :disable="loading"
                  :error="passwordError"
                  :error-message="passwordErrorMessage"
                  @blur="validatePassword"
                  @input="clearPasswordError"
                  @focus="onInputFocus('password')"
                  @keyup.enter="handleLogin"
                  ref="passwordInputRef"
                >
                  <template v-slot:prepend>
                    <q-item-section side class="input-icon-wrapper">
                      <q-icon name="lock" class="input-icon" />
                      <q-spinner-dots class="icon-ripple" v-if="focusedInput === 'password'" size="sm" />
                    </q-item-section>
                  </template>
                  <template v-slot:append>
                    <q-btn
                      flat
                      round
                      dense
                      :icon="showPassword ? 'visibility_off' : 'visibility'"
                      class="password-toggle-btn"
                      @click="showPassword = !showPassword"
                      :disable="loading"
                    >
                      <q-tooltip>{{ showPassword ? '隐藏密码' : '显示密码' }}</q-tooltip>
                    </q-btn>
                  </template>
                </q-input>
                <q-separator class="input-border-animation" :class="{ active: focusedInput === 'password' }" />
              </q-item-section>
            </q-item>

            <!-- 增强的表单选项 -->
            <q-item class="form-options">
              <q-checkbox
                v-model="loginForm.rememberMe"
                label="记住我"
                class="enhanced-checkbox"
                :disable="loading"
              >
                <template v-slot:default>
                  <q-item-label class="checkbox-label">记住我</q-item-label>
                </template>
              </q-checkbox>
              
              <q-btn
                flat
                no-caps
                color="primary"
                label="忘记密码？"
                size="sm"
                class="forgot-password-btn"
                @click="handleForgotPassword"
                :disable="loading"
              >
                <q-tooltip>联系管理员重置密码</q-tooltip>
              </q-btn>
            </q-item>

            <!-- 增强的登录按钮 -->
            <q-btn
              type="submit"
              color="primary"
              class="enhanced-login-btn"
              size="lg"
              :loading="loading"
              :disable="!isFormValid"
              @mouseenter="onButtonHover"
              @mouseleave="onButtonLeave"
            >
              <template v-slot:default>
                <q-item class="btn-content">
                  <q-icon name="login" class="btn-icon" v-if="!loading" />
                  <q-item-label class="btn-text">{{ loading ? '登录中...' : '立即登录' }}</q-item-label>
                  <q-separator class="btn-shine" v-if="buttonHovered" />
                </q-item>
              </template>
              
              <template v-slot:loading>
                <q-item class="loading-content">
                  <q-spinner-dots class="loading-spinner" />
                  <q-item-label>验证中...</q-item-label>
                </q-item>
              </template>
            </q-btn>


          </q-form>
        </q-card-section>

        <!-- 增强的页脚 -->
        <q-card-section class="text-center q-pt-none login-footer">
          <q-item class="footer-content">
            <q-item-section class="copyright-text">
              <q-icon name="flag" class="q-mr-xs" />
              2025 庆祝大阅兵 · Armbian 设备监控平台
            </q-item-section>
            <q-item-section class="version-info">
              <q-chip
                outline
                size="sm"
                color="white"
                text-color="white"
                icon="celebration"
                label="v2.0.0 爱国版"
              />
            </q-item-section>
            <q-item-section class="footer-links">
              <q-btn flat size="sm" color="white" @click="showAbout = true">
                关于
              </q-btn>
              <q-separator vertical color="white" class="q-mx-sm" />
              <q-btn flat size="sm" color="white" @click="showHelp = true">
                帮助
              </q-btn>
            </q-item-section>
          </q-item>
        </q-card-section>
      </q-card>

      <!-- 登录状态指示器 -->
      <q-item class="login-status-indicator" v-if="loading">
        <q-linear-progress
          indeterminate
          color="white"
          track-color="transparent"
          class="status-progress"
        />
        <q-item-label class="status-text">正在验证您的身份...</q-item-label>
      </q-item>
    </q-page-container>

    <!-- 增强的错误提示对话框 -->
    <q-dialog v-model="showErrorDialog" persistent>
      <q-card class="enhanced-error-dialog" style="min-width: 350px">
        <q-card-section class="error-header">
          <q-item class="error-icon-wrapper">
            <q-icon name="error_outline" class="error-icon" />
            <q-spinner-radio class="error-pulse" size="sm" color="negative" />
          </q-item>
          <q-item-label class="error-title text-h6">登录失败</q-item-label>
        </q-card-section>

        <q-card-section class="error-content">
          <q-item-label class="error-message">{{ errorMessage }}</q-item-label>
          <q-expansion-item class="error-suggestions" v-if="showErrorSuggestions" label="建议">
            <q-list class="suggestion-list">
              <q-item><q-item-label>检查用户名和密码是否正确</q-item-label></q-item>
              <q-item><q-item-label>确认网络连接正常</q-item-label></q-item>
              <q-item><q-item-label>联系管理员获取帮助</q-item-label></q-item>
            </q-list>
          </q-expansion-item>
        </q-card-section>

        <q-card-actions align="right" class="error-actions">
          <q-btn
            flat
            label="取消"
            color="grey-7"
            @click="closeErrorDialog"
            class="q-mr-sm"
          />
          <q-btn
            unelevated
            label="重试"
            color="primary"
            @click="retryLogin"
            class="retry-btn"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- 关于对话框 -->
    <q-dialog v-model="showAbout">
      <q-card class="about-dialog" style="max-width: 400px">
        <q-card-section>
          <q-item-label class="text-h6">关于平台</q-item-label>
        </q-card-section>
        <q-card-section class="about-content">
          <q-item-label>Armbian 设备监控平台是一个现代化的设备管理解决方案，为您提供实时的设备状态监控和管理功能。</q-item-label>
          <q-list class="about-features">
            <q-item class="feature-item">
              <q-icon name="monitor" color="primary" />
              <q-item-label>实时监控</q-item-label>
            </q-item>
            <q-item class="feature-item">
              <q-icon name="dashboard" color="primary" />
              <q-item-label>智能面板</q-item-label>
            </q-item>
            <q-item class="feature-item">
              <q-icon name="security" color="primary" />
              <q-item-label>安全可靠</q-item-label>
            </q-item>
          </q-list>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="关闭" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- 帮助对话框 -->
    <q-dialog v-model="showHelp">
      <q-card class="help-dialog" style="max-width: 450px">
        <q-card-section>
          <q-item-label class="text-h6">登录帮助</q-item-label>
        </q-card-section>
        <q-card-section class="help-content">
          <q-expansion-item class="help-section" label="常见问题" icon="help_outline" default-opened>
            <q-list>
              <q-item>
                <q-item-section>
                  <q-item-label>忘记密码怎么办？</q-item-label>
                  <q-item-label caption>请联系系统管理员重置密码</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label>账户被锁定了？</q-item-label>
                  <q-item-label caption>连续输错密码可能导致账户锁定</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-expansion-item>
          <q-item class="help-contact">
            <q-icon name="contact_support" color="primary" />
            <q-item-label>需要帮助？联系管理员</q-item-label>
          </q-item>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="关闭" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>



<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuthStore } from '@/stores/auth';

// 定义组件名称
defineOptions({
  name: 'LoginPage',
});

// 路由和状态管理
const router = useRouter();
const $q = useQuasar();
const authStore = useAuthStore();

// 响应式数据
const loading = ref(false);
const showPassword = ref(false);
const showErrorDialog = ref(false);
const showAbout = ref(false);
const showHelp = ref(false);
const errorMessage = ref('');
const showErrorSuggestions = ref(false);
const loginFormRef = ref();
const usernameInputRef = ref();
const passwordInputRef = ref();

// 新增交互状态
const focusedInput = ref('');
const buttonHovered = ref(false);

// 表单验证错误状态
const usernameError = ref(false);
const passwordError = ref(false);
const usernameErrorMessage = ref('');
const passwordErrorMessage = ref('');

// 登录表单数据
const loginForm = ref({
  username: '',
  password: '',
  rememberMe: false,
});

// 表单验证规则
const usernameRules = [
  (val: string) => !!val || '请输入用户名',
  (val: string) => val.length >= 3 || '用户名至少需要3个字符',
  (val: string) => /^[a-zA-Z0-9_]+$/.test(val) || '用户名只能包含字母、数字和下划线'
];

const passwordRules = [
  (val: string) => !!val || '请输入密码',
  (val: string) => val.length >= 6 || '密码至少需要6位字符'
];

// 计算属性
const isFormValid = computed(() => {
  return (
    loginForm.value.username &&
    loginForm.value.password &&
    loginForm.value.username.length >= 3 &&
    loginForm.value.password.length >= 6 &&
    !usernameError.value &&
    !passwordError.value
  );
});

// 新增交互函数
const onInputFocus = (inputType: string) => {
  focusedInput.value = inputType;
};

const onButtonHover = () => {
  buttonHovered.value = true;
};

const onButtonLeave = () => {
  buttonHovered.value = false;
};

// 表单验证函数
const validateUsername = () => {
  focusedInput.value = '';
  const username = loginForm.value.username;
  
  if (!username) {
    usernameError.value = true;
    usernameErrorMessage.value = '请输入用户名';
    return false;
  }
  
  if (username.length < 3) {
    usernameError.value = true;
    usernameErrorMessage.value = '用户名至少需要3个字符';
    return false;
  }
  
  if (!/^[a-zA-Z0-9_]+$/.test(username)) {
    usernameError.value = true;
    usernameErrorMessage.value = '用户名只能包含字母、数字和下划线';
    return false;
  }
  
  usernameError.value = false;
  usernameErrorMessage.value = '';
  return true;
};

const validatePassword = () => {
  focusedInput.value = '';
  const password = loginForm.value.password;
  
  if (!password) {
    passwordError.value = true;
    passwordErrorMessage.value = '请输入密码';
    return false;
  }
  
  if (password.length < 6) {
    passwordError.value = true;
    passwordErrorMessage.value = '密码至少需要6位字符';
    return false;
  }
  
  passwordError.value = false;
  passwordErrorMessage.value = '';
  return true;
};

const clearUsernameError = () => {
  usernameError.value = false;
  usernameErrorMessage.value = '';
};

const clearPasswordError = () => {
  passwordError.value = false;
  passwordErrorMessage.value = '';
};

// 错误对话框处理
const closeErrorDialog = () => {
  showErrorDialog.value = false;
  showErrorSuggestions.value = false;
  clearUsernameError();
  clearPasswordError();
};

const retryLogin = () => {
  closeErrorDialog();
  void nextTick(() => {
    if (usernameInputRef.value) {
      usernameInputRef.value.focus();
    }
  });
};

// 处理登录
const handleLogin = async () => {
  // 清除之前的错误
  clearUsernameError();
  clearPasswordError();
  
  // 验证表单
  const isUsernameValid = validateUsername();
  const isPasswordValid = validatePassword();
  
  if (!isUsernameValid || !isPasswordValid) {
    // 聚焦到第一个错误字段
    if (!isUsernameValid && usernameInputRef.value) {
      usernameInputRef.value.focus();
    } else if (!isPasswordValid && passwordInputRef.value) {
      passwordInputRef.value.focus();
    }
    return;
  }

  try {
    loading.value = true;

    // 模拟登录延迟以展示加载效果
    await new Promise(resolve => setTimeout(resolve, 1500));

    await authStore.login({
      username: loginForm.value.username,
      password: loginForm.value.password,
      rememberMe: loginForm.value.rememberMe,
    });

    // 登录成功提示
    $q.notify({
      type: 'positive',
      message: '🎉 登录成功！欢迎回来',
      caption: '即将跳转到监控面板...',
      position: 'top',
      timeout: 2000,
      actions: [
        {
          icon: 'close',
          color: 'white',
          handler: () => {},
        },
      ],
    });

    // 保存用户名（如果勾选了记住我）
    if (loginForm.value.rememberMe) {
      localStorage.setItem('remembered_username', loginForm.value.username);
    } else {
      localStorage.removeItem('remembered_username');
    }

    // 跳转到指定页面或首页
    const redirectPath = '/dashboard';
    setTimeout(() => {
      void router.push(redirectPath);
    }, 1000);
  } catch (error) {
    // 登录失败处理
    let errorMsg = '登录失败，请检查用户名和密码';
    showErrorSuggestions.value = true;
    
    if (error instanceof Error) {
      const message = error.message.toLowerCase();
      
      if (message.includes('username') || message.includes('用户名')) {
        errorMsg = '用户名不存在，请检查输入';
        usernameError.value = true;
        usernameErrorMessage.value = '用户名不存在';
        showErrorSuggestions.value = false;
      } else if (message.includes('password') || message.includes('密码')) {
        errorMsg = '密码错误，请重新输入';
        passwordError.value = true;
        passwordErrorMessage.value = '密码错误';
        showErrorSuggestions.value = false;
      } else if (message.includes('locked') || message.includes('锁定')) {
        errorMsg = '账户已被锁定，请联系管理员';
      } else if (message.includes('network') || message.includes('网络')) {
        errorMsg = '网络连接失败，请检查网络状态';
      } else {
        errorMsg = error.message;
      }
    }
    
    errorMessage.value = errorMsg;
    showErrorDialog.value = true;

    // 震动反馈（支持的设备）
    if ('vibrate' in navigator) {
      navigator.vibrate(200);
    }

    // 重置表单焦点到有错误的字段
    setTimeout(() => {
      if (usernameError.value && usernameInputRef.value) {
        usernameInputRef.value.focus();
      } else if (passwordError.value && passwordInputRef.value) {
        passwordInputRef.value.focus();
      }
    }, 100);
  } finally {
    loading.value = false;
    buttonHovered.value = false;
  }
};

// 处理忘记密码
const handleForgotPassword = () => {
  $q.notify({
    type: 'info',
    message: '🔐 忘记密码',
    caption: '请联系系统管理员重置密码',
    position: 'top',
    timeout: 4000,
    actions: [
      {
        icon: 'email',
        color: 'white',
        handler: () => {
          $q.notify({
            type: 'info',
            message: '管理员邮箱: admin@example.com',
            position: 'top',
            timeout: 3000,
          });
        },
      },
      {
        icon: 'close',
        color: 'white',
        handler: () => {},
      },
    ],
  });
};



// 组件挂载时检查是否已登录
onMounted(() => {
  // 如果已经登录，直接跳转到首页
  if (authStore.isAuthenticated) {
    router.push('/dashboard').catch(() => {});
    return;
  }

  // 从本地存储恢复记住的用户名
  const rememberedUsername = localStorage.getItem('remembered_username');
  if (rememberedUsername) {
    loginForm.value.username = rememberedUsername;
    loginForm.value.rememberMe = true;
  }

  // 页面加载完成后自动聚焦到用户名输入框
  void nextTick(() => {
    if (usernameInputRef.value && !loginForm.value.username) {
      usernameInputRef.value.focus();
    }
  });
});
</script>

<style scoped lang="scss">
// 中国红主题变量
$china-red: #DC143C;
$china-red-light: #FF6B6B;
$china-red-dark: #B22222;
$china-gold: #FFD700;
$glass-bg: rgba(255, 255, 255, 0.25);
$glass-border: rgba(220, 20, 60, 0.3);

.login-page {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;

  // 中国红渐变背景
  background: linear-gradient(
    135deg,
    $china-red 0%,
    $china-red-light 50%,
    $china-red-dark 100%
  );
  
  // 添加五星装饰背景
  &::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: 
      radial-gradient(circle at 15% 15%, $china-gold 2px, transparent 2px),
      radial-gradient(circle at 85% 15%, $china-gold 2px, transparent 2px),
      radial-gradient(circle at 15% 85%, $china-gold 2px, transparent 2px),
      radial-gradient(circle at 85% 85%, $china-gold 2px, transparent 2px),
      radial-gradient(circle at 50% 50%, $china-gold 3px, transparent 3px);
    background-size: 200px 200px;
    opacity: 0.1;
    animation: starTwinkle 8s ease-in-out infinite;
    z-index: 0;
  }
  
  &::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background:
      radial-gradient(circle at 20% 30%, rgba(255, 215, 0, 0.15) 0%, transparent 50%),
      radial-gradient(circle at 80% 70%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
      radial-gradient(circle at 60% 20%, rgba(255, 215, 0, 0.08) 0%, transparent 40%);
    animation: backgroundShift 20s ease-in-out infinite;
    z-index: 1;
  }
}

@keyframes backgroundShift {
  0%, 100% { transform: translate(0, 0) scale(1); }
  25% { transform: translate(-10px, 10px) scale(1.02); }
  50% { transform: translate(10px, -5px) scale(0.98); }
  75% { transform: translate(-5px, -10px) scale(1.01); }
}

@keyframes starTwinkle {
  0%, 100% { opacity: 0.1; transform: scale(1); }
  25% { opacity: 0.3; transform: scale(1.2); }
  50% { opacity: 0.2; transform: scale(0.8); }
  75% { opacity: 0.4; transform: scale(1.1); }
}

// 增强的背景装饰
.background-decoration {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 1;
  opacity: 0.6;
}

// 网格图案
.grid-pattern {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image:
    linear-gradient(rgba(255, 255, 255, 0.1) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
  background-size: 50px 50px;
  animation: gridMove 30s linear infinite;
}

@keyframes gridMove {
  0% { transform: translate(0, 0); }
  100% { transform: translate(50px, 50px); }
}

// 3D浮动粒子
.floating-particles {
  position: relative;
  width: 100%;
  height: 100%;
}

.particle {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 215, 0, 0.15);
  backdrop-filter: blur(5px);
  box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
  animation: particleFloat 25s infinite linear;
  
  &::before {
    content: '★';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: $china-gold;
    font-size: 12px;
    opacity: 0.6;
  }
}

// 为每个粒子生成不同的样式
.particle-1 { width: 45px; height: 35px; top: 20%; left: 10%; animation-delay: 2s; animation-duration: 18s; }
.particle-2 { width: 60px; height: 50px; top: 60%; left: 80%; animation-delay: 5s; animation-duration: 22s; }
.particle-3 { width: 30px; height: 40px; top: 80%; left: 30%; animation-delay: 8s; animation-duration: 25s; }
.particle-4 { width: 55px; height: 45px; top: 15%; left: 70%; animation-delay: 12s; animation-duration: 20s; }
.particle-5 { width: 40px; height: 60px; top: 90%; left: 50%; animation-delay: 15s; animation-duration: 30s; }
.particle-6 { width: 65px; height: 25px; top: 40%; left: 90%; animation-delay: 3s; animation-duration: 28s; }
.particle-7 { width: 35px; height: 55px; top: 70%; left: 20%; animation-delay: 10s; animation-duration: 24s; }
.particle-8 { width: 50px; height: 30px; top: 25%; left: 60%; animation-delay: 7s; animation-duration: 19s; }
.particle-9 { width: 45px; height: 65px; top: 85%; left: 75%; animation-delay: 14s; animation-duration: 26s; }
.particle-10 { width: 70px; height: 40px; top: 5%; left: 40%; animation-delay: 1s; animation-duration: 21s; }
.particle-11 { width: 25px; height: 50px; top: 55%; left: 85%; animation-delay: 18s; animation-duration: 27s; }
.particle-12 { width: 60px; height: 35px; top: 35%; left: 15%; animation-delay: 6s; animation-duration: 23s; }
.particle-13 { width: 40px; height: 45px; top: 75%; left: 65%; animation-delay: 11s; animation-duration: 17s; }
.particle-14 { width: 55px; height: 70px; top: 10%; left: 95%; animation-delay: 20s; animation-duration: 29s; }
.particle-15 { width: 30px; height: 25px; top: 95%; left: 5%; animation-delay: 4s; animation-duration: 16s; }

@keyframes particleFloat {
  0% {
    transform: translateY(0px) translateX(0px) rotate(0deg) scale(1);
    opacity: 0.8;
  }
  25% {
    transform: translateY(-100px) translateX(50px) rotate(90deg) scale(1.1);
    opacity: 0.6;
  }
  50% {
    transform: translateY(-50px) translateX(-30px) rotate(180deg) scale(0.9);
    opacity: 0.8;
  }
  75% {
    transform: translateY(-120px) translateX(-80px) rotate(270deg) scale(1.2);
    opacity: 0.4;
  }
  100% {
    transform: translateY(0px) translateX(0px) rotate(360deg) scale(1);
    opacity: 0.8;
  }
}

// 光效扫描线
.light-beams {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

.beam {
  position: absolute;
  width: 2px;
  height: 100%;
  background: linear-gradient(
    to bottom,
    transparent 0%,
    rgba(255, 255, 255, 0.6) 50%,
    transparent 100%
  );
  animation: beamMove 8s ease-in-out infinite;
}

.beam-1 {
  left: 20%;
  animation-delay: 0s;
}

.beam-2 {
  right: 30%;
  animation-delay: 4s;
}

@keyframes beamMove {
  0%, 100% {
    opacity: 0;
    transform: translateY(-100%);
  }
  50% {
    opacity: 1;
    transform: translateY(0);
  }
}

// 登录容器 - 完美居中
.login-container {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 450px;
  padding: 24px;
  margin: 0 auto; // 水平居中
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: calc(100vh - 48px); // 垂直居中，减去padding
}

// 中国红主题玻璃拟态卡片
.enhanced-glass-card {
  background: $glass-bg !important;
  backdrop-filter: blur(25px) saturate(180%);
  border: 2px solid rgba(220, 20, 60, 0.4) !important;
  border-radius: 28px !important;
  box-shadow:
    0 16px 40px rgba(220, 20, 60, 0.3),
    0 8px 20px rgba(0, 0, 0, 0.1),
    inset 0 1px 0 rgba(255, 255, 255, 0.5),
    inset 0 -1px 0 rgba(255, 215, 0, 0.3),
    0 0 30px rgba(255, 215, 0, 0.2);
  
  position: relative;
  overflow: hidden;

  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);

  &::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, rgba(255, 215, 0, 0.8), transparent);
    animation: shimmer 3s ease-in-out infinite;
  }

  &::after {
    content: '🇨🇳';
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 1.2rem;
    opacity: 0.6;
    animation: flagWave 4s ease-in-out infinite;
  }

  &:hover {
    transform: translateY(-4px) scale(1.01);
    box-shadow:
      0 20px 50px rgba(220, 20, 60, 0.4),
      0 10px 25px rgba(0, 0, 0, 0.15),
      inset 0 1px 0 rgba(255, 255, 255, 0.6),
      inset 0 -1px 0 rgba(255, 215, 0, 0.4),
      0 0 40px rgba(255, 215, 0, 0.3);
  }
}

@keyframes flagWave {
  0%, 100% { transform: scale(1) rotate(0deg); }
  25% { transform: scale(1.1) rotate(5deg); }
  75% { transform: scale(1.1) rotate(-5deg); }
}

@keyframes shimmer {
  0%, 100% { opacity: 0; }
  50% { opacity: 1; }
}

// 头部样式增强
.login-header {
  padding: 1.5rem 2rem 1rem;
  position: relative;
}

.login-logo-container {
  margin-bottom: 1rem;
  display: flex;
  justify-content: center;
}

.logo-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 70px;
  height: 70px;
}

.logo-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 120px;
  height: 120px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.2), transparent 70%);
  border-radius: 50%;
  transform: translate(-50%, -50%);
  animation: logoGlow 4s ease-in-out infinite;
}

.logo-icon {
  font-size: 36px !important;
  color: white;
  text-shadow: 
    0 0 20px rgba(255, 255, 255, 0.8),
    0 0 40px rgba(255, 255, 255, 0.4);
  z-index: 3;
  position: relative;
  animation: logoFloat 6s ease-in-out infinite;
}

.logo-pulse {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-radius: 50%;
  animation: logoPulse 2s ease-in-out infinite;
}

.logo-ring {
  position: absolute;
  top: -10px;
  left: -10px;
  width: 120px;
  height: 120px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  animation: logoRotate 20s linear infinite;
}

@keyframes logoGlow {
  0%, 100% { opacity: 0.3; transform: translate(-50%, -50%) scale(1); }
  50% { opacity: 0.6; transform: translate(-50%, -50%) scale(1.1); }
}

@keyframes logoFloat {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-5px); }
}

@keyframes logoPulse {
  0% { transform: scale(0.8); opacity: 1; }
  100% { transform: scale(1.6); opacity: 0; }
}

@keyframes logoRotate {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.login-title {
  color: white;
  font-weight: 800;
  font-size: 1.6rem;
  margin: 0 0 0.5rem 0;
  text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  letter-spacing: 1px;
  position: relative;
}

.title-text {
  position: relative;
  z-index: 2;
}

.title-underline {
  position: absolute;
  bottom: -8px;
  left: 50%;
  transform: translateX(-50%);
  width: 60px;
  height: 3px;
  background: linear-gradient(90deg, transparent, white, transparent);
  border-radius: 2px;
  animation: underlineGlow 2s ease-in-out infinite;
}

@keyframes underlineGlow {
  0%, 100% { opacity: 0.6; width: 60px; }
  50% { opacity: 1; width: 80px; }
}

.login-subtitle {
  color: rgba(255, 255, 255, 0.95);
  font-size: 0.9rem;
  margin: 0;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.subtitle-icon {
  font-size: 1.2rem;
  animation: iconPulse 3s ease-in-out infinite;
}

@keyframes iconPulse {
  0%, 100% { opacity: 0.8; transform: scale(1); }
  50% { opacity: 1; transform: scale(1.1); }
}

// 表单样式增强 - 居中布局
.login-form-section {
  padding: 0 2rem 1rem;
  display: flex;
  justify-content: center; // 表单水平居中
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
  width: 100%;
  max-width: 350px; // 限制最大宽度，保持紧凑
}

.input-group {
  position: relative;
}

.input-wrapper {
  position: relative;
}

.enhanced-input {
  :deep(.q-field__control) {
    background: rgba(255, 255, 255, 0.92) !important;
    border-radius: 16px !important;
    backdrop-filter: blur(15px);
    border: 2px solid rgba(255, 255, 255, 0.3) !important;
    box-shadow: 
      0 4px 15px rgba(0, 0, 0, 0.1),
      inset 0 1px 0 rgba(255, 255, 255, 0.8) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    height: 50px;

    &:hover {
      background: rgba(255, 255, 255, 0.96) !important;
      border-color: rgba(255, 255, 255, 0.5) !important;
      box-shadow: 
        0 6px 20px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.9) !important;
    }
  }

  :deep(.q-field--focused) {
    .q-field__control {
      background: rgba(255, 255, 255, 0.98) !important;
      border-color: $china-red !important;
      box-shadow: 
        0 8px 25px rgba(220, 20, 60, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.9) !important,
        0 0 15px rgba(255, 215, 0, 0.4);
      transform: scale(1.02);
    }
  }

  :deep(.q-field__outlined) {
    .q-field__control:before {
      border: none !important;
    }
  }

  :deep(.q-field__label) {
    color: #555 !important;
    font-weight: 600;
    font-size: 1rem;
  }

  :deep(.q-field__native) {
    color: #333 !important;
    font-weight: 600;
    font-size: 1.1rem;
  }
}

.input-icon-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.input-icon {
  color: #666 !important;
  font-size: 1.4rem !important;
  transition: color 0.3s ease;
  z-index: 2;
}

.icon-ripple {
  position: absolute;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: rgba(25, 118, 210, 0.2);
  animation: iconRipple 1.5s ease-out infinite;
}

@keyframes iconRipple {
  0% {
    transform: scale(0.5);
    opacity: 1;
  }
  100% {
    transform: scale(2);
    opacity: 0;
  }
}

.input-border-animation {
  position: absolute;
  bottom: 0;
  left: 50%;
  width: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, $china-red, transparent);
  border-radius: 2px;
  transition: all 0.3s ease;
  transform: translateX(-50%);

  &.active {
    width: 100%;
  }
}

.password-toggle-btn {
  color: #999 !important;
  transition: all 0.2s ease;

  &:hover {
    color: $china-red !important;
    transform: scale(1.1);
  }
}

// 表单选项增强
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: -0.5rem 0 0.5rem 0;
}

.enhanced-checkbox {
  :deep(.q-checkbox__label) {
    color: rgba(255, 255, 255, 0.95) !important;
    font-weight: 600;
    font-size: 0.95rem;
  }

  :deep(.q-checkbox__inner) {
    color: rgba(255, 255, 255, 0.9);
    transition: all 0.2s ease;
    
    &:hover {
      transform: scale(1.1);
    }
  }
}

.forgot-password-btn {
  color: rgba(255, 255, 255, 0.9) !important;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
  padding: 8px 12px;
  border-radius: 8px;

  &:hover {
    color: white !important;
    background: rgba(255, 255, 255, 0.1);
    text-decoration: underline;
    transform: translateY(-1px);
  }
}

// 中国红主题登录按钮
.enhanced-login-btn {
  height: 52px;
  border-radius: 26px !important;
  font-size: 1.1rem;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: none;
  position: relative;
  overflow: hidden;

  background: linear-gradient(135deg, $china-red 0%, $china-red-light 100%) !important;
  box-shadow:
    0 8px 25px rgba(220, 20, 60, 0.4),
    0 4px 12px rgba(0, 0, 0, 0.1),
    inset 0 1px 0 rgba(255, 255, 255, 0.2),
    0 0 20px rgba(255, 215, 0, 0.3);

  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);

  &:hover:not(:disabled) {
    background: linear-gradient(135deg, $china-red-dark 0%, $china-red 100%) !important;
    transform: translateY(-3px) scale(1.02);
    box-shadow:
      0 12px 35px rgba(220, 20, 60, 0.5),
      0 6px 20px rgba(0, 0, 0, 0.15),
      inset 0 1px 0 rgba(255, 255, 255, 0.3),
      0 0 30px rgba(255, 215, 0, 0.5);
  }

  &:active:not(:disabled) {
    transform: translateY(-1px) scale(1.01);
  }

  &:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none !important;
  }
}

.btn-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  position: relative;
  z-index: 2;
}

.btn-icon {
  font-size: 1.3rem;
  transition: transform 0.3s ease;
}

.btn-text {
  font-weight: 700;
}

.btn-shine {
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  animation: buttonShine 1.5s ease-in-out;
}

@keyframes buttonShine {
  0% { left: -100%; }
  100% { left: 100%; }
}

.loading-content {
  display: flex;
  align-items: center;
  gap: 12px;
}

.loading-spinner {
  color: white;
}

// 社交登录分割线
.social-login-divider {
  display: flex;
  align-items: center;
  margin: 1rem 0 0.8rem;
}

.divider-line {
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
}

.divider-text {
  padding: 0 1.5rem;
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.9rem;
  font-weight: 500;
}

// 快捷操作
.quick-actions {
  display: flex;
  justify-content: center;
}

.quick-action-btn {
  border: 2px solid rgba(255, 255, 255, 0.3) !important;
  color: rgba(255, 255, 255, 0.9) !important;
  background: rgba(255, 255, 255, 0.1) !important;
  backdrop-filter: blur(10px);
  border-radius: 16px !important;
  height: 48px;
  padding: 0 24px;
  font-weight: 600;
  transition: all 0.3s ease;

  &:hover {
    border-color: rgba(255, 255, 255, 0.5) !important;
    background: rgba(255, 255, 255, 0.15) !important;
    color: white !important;
    transform: translateY(-2px);
  }
}

// 登录状态指示器
.login-status-indicator {
  position: absolute;
  bottom: -60px;
  left: 0;
  right: 0;
  text-align: center;
  color: white;
}

.status-progress {
  margin-bottom: 8px;
  border-radius: 2px;
  overflow: hidden;
}

.status-text {
  font-size: 0.9rem;
  font-weight: 500;
  opacity: 0.9;
}

// 增强的页脚
.login-footer {
  padding: 1rem 2rem 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.6rem;
}

.copyright-text {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.9rem;
  font-weight: 500;
  display: flex;
  align-items: center;
}

.version-info {
  :deep(.q-chip) {
    background: rgba(255, 255, 255, 0.1) !important;
    backdrop-filter: blur(10px);
  }
}

.footer-links {
  display: flex;
  align-items: center;
  opacity: 0.8;
  
  .q-btn {
    font-size: 0.85rem;
    
    &:hover {
      background: rgba(255, 255, 255, 0.1);
    }
  }
}

// 增强的错误对话框
.enhanced-error-dialog {
  border-radius: 20px !important;
  backdrop-filter: blur(25px);
  background: rgba(255, 255, 255, 0.98) !important;
  box-shadow: 
    0 16px 40px rgba(0, 0, 0, 0.15),
    0 8px 20px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.error-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 24px 24px 16px;
  background: rgba(244, 67, 54, 0.05);
}

.error-icon-wrapper {
  position: relative;
}

.error-icon {
  font-size: 2rem;
  color: #f44336;
}

.error-pulse {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 40px;
  height: 40px;
  border: 2px solid #f44336;
  border-radius: 50%;
  transform: translate(-50%, -50%);
  animation: errorPulse 2s ease-in-out infinite;
  opacity: 0.3;
}

@keyframes errorPulse {
  0% { transform: translate(-50%, -50%) scale(0.8); opacity: 1; }
  100% { transform: translate(-50%, -50%) scale(1.4); opacity: 0; }
}

.error-title {
  font-size: 1.3rem;
  font-weight: 700;
  color: #333;
}

.error-content {
  padding: 16px 24px;
}

.error-message {
  font-size: 1rem;
  color: #666;
  margin-bottom: 16px;
  line-height: 1.5;
}

.error-suggestions {
  background: rgba(33, 150, 243, 0.05);
  border: 1px solid rgba(33, 150, 243, 0.1);
  border-radius: 12px;
  padding: 16px;
}

.suggestion-title {
  font-weight: 600;
  color: #2196f3;
  margin-bottom: 8px;
  font-size: 0.9rem;
}

.suggestion-list {
  margin: 0;
  padding-left: 20px;
  color: #666;
  font-size: 0.85rem;
  line-height: 1.4;
  
  li {
    margin-bottom: 4px;
  }
}

.error-actions {
  padding: 16px 24px 24px;
}

.retry-btn {
  background: #2196f3 !important;
  font-weight: 600;
}

// 关于和帮助对话框
.about-dialog, .help-dialog {
  border-radius: 16px !important;
  background: rgba(255, 255, 255, 0.98) !important;
  backdrop-filter: blur(20px);
}

.about-content, .help-content {
  line-height: 1.6;
}

.about-features {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 20px;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px;
  border-radius: 8px;
  background: rgba(25, 118, 210, 0.05);
  
  span {
    font-weight: 500;
  }
}

.help-section {
  margin-bottom: 20px;
}

.help-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  margin-bottom: 16px;
  color: #2196f3;
}

.help-contact {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px;
  background: rgba(76, 175, 80, 0.1);
  border-radius: 12px;
  color: #4caf50;
  font-weight: 500;
}

// 响应式设计 - 768px以下平板优化
@media (max-width: 768px) {
  .login-page {
    background: #fafafa !important; // 简化背景为浅灰色
    
    &::before {
      opacity: 0.2; // 减少装饰性背景
    }
  }

  .background-decoration {
    opacity: 0.3; // 大幅降低装饰元素透明度
  }

  .enhanced-glass-card {
    background: rgba(255, 255, 255, 0.95) !important; // 更纯净的白色背景
    margin: 0 8px;
    border-radius: 24px !important;
    backdrop-filter: blur(10px); // 减少模糊效果
    box-shadow:
      0 8px 25px rgba(0, 0, 0, 0.1),
      0 4px 12px rgba(0, 0, 0, 0.05);
  }

  .login-container {
    padding: 16px;
    max-width: 100%;
  }

  .login-header {
    padding: 1.2rem 1.5rem 0.8rem;
  }

  .login-form-section {
    padding: 0 1.5rem 0.8rem;
  }

  .login-title {
    font-size: 1.7rem;
  }

  .login-subtitle {
    font-size: 1rem;
  }

  .floating-particles .particle {
    opacity: 0.2; // 进一步减少粒子透明度
  }

  .light-beams {
    opacity: 0.3; // 减少光束效果
  }

  // 简化悬停效果
  .enhanced-glass-card:hover {
    transform: translateY(-2px) scale(1.005); // 减少悬停动画幅度
    box-shadow:
      0 12px 30px rgba(0, 0, 0, 0.12),
      0 6px 15px rgba(0, 0, 0, 0.08);
  }
}

// 480px以下极简设计
@media (max-width: 480px) {
  .login-page {
    background: #ffffff !important; // 完全简化为白色背景
    padding: 12px;

    &::before {
      display: none; // 移除所有背景装饰
    }
  }

  .background-decoration {
    display: none !important; // 完全隐藏装饰元素
  }

  .enhanced-glass-card {
    background: #ffffff !important; // 纯白色卡片背景
    backdrop-filter: none; // 移除模糊效果
    border-radius: 16px !important; // 更简洁的圆角
    border: 1px solid rgba(0, 0, 0, 0.1); // 添加简单边框
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important; // 极简阴影
    
    &:hover {
      transform: none !important; // 移除悬停效果
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
    }
  }

  .login-header {
    padding: 1rem 1rem 0.8rem;
  }

  .logo-wrapper {
    width: 50px; // 更小的logo
    height: 50px;
  }

  .logo-icon {
    font-size: 24px !important;
    color: #1976d2 !important; // 使用主色调而非白色
    text-shadow: none; // 移除文字阴影
  }

  .logo-glow,
  .logo-pulse,
  .logo-ring {
    display: none; // 移除所有装饰性效果
  }

  .login-title {
    font-size: 1.2rem;
    color: #333333 !important; // 深色文字
    text-shadow: none;
  }

  .login-subtitle {
    font-size: 0.75rem;
    color: #666666 !important; // 深色副标题
  }

  .form-options {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }

  .enhanced-checkbox {
    :deep(.q-checkbox__label) {
      color: #333333 !important; // 深色文字
    }
  }

  .forgot-password-btn {
    align-self: flex-end;
    color: #1976d2 !important; // 主色调链接
    background: transparent !important;
    
    &:hover {
      background: rgba(25, 118, 210, 0.05) !important;
    }
  }

  .enhanced-login-btn {
    height: 52px;
    font-size: 1rem;
    box-shadow: 0 2px 8px rgba(25, 118, 210, 0.3) !important; // 简化按钮阴影
  }

  .login-footer {
    .copyright-text,
    .footer-links .q-btn {
      color: #666666 !important; // 深色页脚文字
    }
    
    .version-info :deep(.q-chip) {
      background: rgba(25, 118, 210, 0.1) !important;
      color: #1976d2 !important;
    }
  }

  // 完全移除动画装饰
  .floating-particles,
  .light-beams,
  .grid-pattern {
    display: none !important;
  }
}

// 深色模式支持
@media (prefers-color-scheme: dark) {
  .enhanced-input {
    :deep(.q-field__control) {
      background: rgba(40, 40, 40, 0.9) !important;
      border-color: rgba(255, 255, 255, 0.2) !important;
    }

    :deep(.q-field__label) {
      color: #ccc !important;
    }

    :deep(.q-field__native) {
      color: white !important;
    }
  }

  .input-icon {
    color: #ccc !important;
  }

  .enhanced-error-dialog {
    background: rgba(40, 40, 40, 0.95) !important;
    color: white;
  }

  .about-dialog,
  .help-dialog {
    background: rgba(40, 40, 40, 0.95) !important;
    color: white;
  }
}

// 高对比度模式
@media (prefers-contrast: high) {
  .enhanced-glass-card {
    background: rgba(255, 255, 255, 0.95) !important;
    border: 3px solid rgba(0, 0, 0, 0.3);
  }

  .login-title,
  .login-subtitle {
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
  }

  .enhanced-input {
    :deep(.q-field__control) {
      border-width: 3px !important;
    }
  }
}

// 触摸设备优化
@media (hover: none) and (pointer: coarse) {
  // 移除所有悬停效果，只保留点击反馈
  .enhanced-glass-card:hover {
    transform: none !important;
    box-shadow: inherit !important;
  }

  .enhanced-input:deep(.q-field__control):hover {
    background: inherit !important;
    border-color: inherit !important;
    box-shadow: inherit !important;
  }

  .password-toggle-btn:hover {
    color: inherit !important;
    transform: none !important;
  }

  .forgot-password-btn:hover {
    background: transparent !important;
    text-decoration: none !important;
    transform: none !important;
  }

  .enhanced-login-btn:hover:not(:disabled) {
    background: inherit !important;
    transform: none !important;
    box-shadow: inherit !important;
  }

  // 增强点击反馈
  .enhanced-login-btn:active:not(:disabled) {
    transform: scale(0.98) !important;
    opacity: 0.9;
  }

  .forgot-password-btn:active {
    opacity: 0.7;
  }

  .password-toggle-btn:active {
    opacity: 0.7;
  }
}

// 动画减少模式
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }

  .floating-particles,
  .light-beams,
  .grid-pattern {
    animation: none !important;
  }
}

// 打印样式
@media print {
  .login-page {
    background: white !important;
  }

  .background-decoration,
  .floating-particles,
  .light-beams {
    display: none !important;
  }

  .enhanced-glass-card {
    background: white !important;
    box-shadow: none !important;
    border: 2px solid #ccc;
  }
}
</style>
