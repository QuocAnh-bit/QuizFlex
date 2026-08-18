<template>
  <div class="mx-auto max-w-md py-6">
    <div class="card p-6 sm:p-8 space-y-6 shadow-sm" :class="{ 'animate-shake': isShaking }">
      <!-- Logo Header -->
      <div class="text-center space-y-2">
        <div class="flex justify-center">
          <BrandLogo to="/" size="md" />
        </div>
        <h1 class="text-2xl font-black text-slate-900 pt-1">Đăng nhập vào QuizFlex</h1>
        <p class="text-xs text-slate-500">Nền tảng học tập & luyện thi trắc nghiệm thông minh</p>
      </div>

      <!-- Logout Alert -->
      <div v-if="route.query.logout === 'success'" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-bold text-emerald-700 flex items-center gap-2">
        <span>✅</span> Bạn đã đăng xuất thành công khỏi hệ thống.
      </div>

      <!-- Google SSO Authenticating -->
      <div v-if="route.query.token && !errors.password" class="text-center py-6 space-y-3">
        <div class="h-10 w-10 animate-spin rounded-full border-2 border-slate-200 border-t-[#7C3AED] mx-auto"></div>
        <h3 class="text-base font-bold text-slate-900">Đang thiết lập phiên đăng nhập...</h3>
        <p class="text-xs text-slate-500">Vui lòng chờ trong giây lát</p>
        <div v-if="successMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-bold text-emerald-700">{{ successMessage }}</div>
      </div>

      <!-- Quick Login Account Switcher -->
      <div v-else-if="showQuickLogin" class="text-center space-y-4">
        <div class="flex flex-col items-center rounded-2xl border border-slate-100 bg-slate-50 p-4">
          <div class="h-16 w-16 rounded-full bg-purple-100 text-[#7C3AED] flex items-center justify-center text-xl font-bold border-2 border-white shadow-sm overflow-hidden">
            <img v-if="lastUser.avatar" :src="lastUser.avatar" class="h-full w-full object-cover" />
            <span v-else>{{ lastUser.name ? lastUser.name.charAt(0) : 'U' }}</span>
          </div>
          <b class="mt-2 text-sm text-slate-900">{{ lastUser.name }}</b>
          <span class="text-xs text-slate-500">{{ lastUser.email }}</span>
        </div>

        <form v-if="lastLoginMethod === 'password'" class="space-y-4" @submit.prevent="handleQuickLoginPassword" novalidate>
          <label class="grid gap-1.5 text-xs font-bold text-slate-700 text-left">
            Mật khẩu tài khoản
            <div class="relative">
              <input 
                v-model="quickPassword" 
                class="field text-xs pr-14" 
                :type="isPasswordVisible ? 'text' : 'password'" 
                placeholder="Nhập mật khẩu của bạn" 
                autocomplete="current-password" 
                required
              />
              <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-[#7C3AED]" @click="isPasswordVisible = !isPasswordVisible">
                {{ isPasswordVisible ? 'Ẩn' : 'Hiện' }}
              </button>
            </div>
            <span v-if="errors.password" class="text-xs font-bold text-red-600">{{ errors.password }}</span>
          </label>

          <button type="submit" :disabled="isSubmitting" class="btn-primary w-full py-2.5 text-xs">
            {{ isSubmitting ? 'Đang đăng nhập...' : 'Đăng nhập tiếp tục' }}
          </button>
        </form>

        <div v-else class="space-y-3">
          <button type="button" class="btn-primary w-full py-2.5 text-xs" @click="loginWithGoogle">
            Tiếp tục với Google
          </button>
          <button type="button" class="text-xs font-bold text-[#7C3AED] hover:underline" @click="lastLoginMethod = 'password'">
            Đăng nhập bằng mật khẩu
          </button>
        </div>

        <div class="flex items-center justify-center gap-3 pt-2 text-xs text-slate-500 border-t border-slate-100">
          <button type="button" class="font-bold text-[#7C3AED] hover:underline" @click="switchToStandardLogin">Dùng tài khoản khác</button>
          <span>•</span>
          <router-link to="/forgot-password" class="hover:underline">Quên mật khẩu?</router-link>
        </div>
      </div>

      <!-- Standard Login Form -->
      <div v-else class="space-y-4">
        <form class="space-y-4" @submit.prevent="handleLogin" novalidate>
          <!-- Email input -->
          <div class="space-y-1 text-xs">
            <label class="font-bold text-slate-700 block">Email</label>
            <input 
              v-model="form.email" 
              class="field text-xs" 
              type="email" 
              placeholder="you@example.com" 
              autocomplete="email" 
              @blur="handleBlur('email')"
              @input="handleInput('email')"
              required
            />
            <div v-if="emailTypoHint" class="text-[11px] font-bold text-amber-600 cursor-pointer pt-0.5" @click="applyEmailHint">
              💡 Gợi ý: Có phải bạn muốn gõ <u>{{ emailTypoHint }}</u>?
            </div>
            <span v-if="errors.email" class="text-xs font-bold text-red-600 block">{{ errors.email }}</span>
          </div>

          <!-- Password input -->
          <div class="space-y-1 text-xs">
            <div class="flex items-center justify-between">
              <label class="font-bold text-slate-700">Mật khẩu</label>
              <router-link to="/forgot-password" class="text-xs font-bold text-[#7C3AED] hover:underline">Quên mật khẩu?</router-link>
            </div>
            <div class="relative">
              <input 
                v-model="form.password" 
                class="field text-xs pr-14" 
                :type="isPasswordVisible ? 'text' : 'password'" 
                placeholder="••••••••" 
                autocomplete="current-password"
                @blur="handleBlur('password')"
                @input="handleInput('password')"
                required
              />
              <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-[#7C3AED]" @click="isPasswordVisible = !isPasswordVisible">
                {{ isPasswordVisible ? 'Ẩn' : 'Hiện' }}
              </button>
            </div>
            <span v-if="errors.password" class="text-xs font-bold text-red-600 block">{{ errors.password }}</span>
          </div>

          <div class="flex items-center text-xs">
            <label class="flex items-center gap-2 cursor-pointer text-slate-600 font-medium">
              <input v-model="form.remember" type="checkbox" class="rounded text-[#7C3AED]" />
              <span>Ghi nhớ đăng nhập</span>
            </label>
          </div>

          <button type="submit" :disabled="isSubmitting" class="btn-primary w-full py-2.5 text-xs">
            {{ isSubmitting ? 'Đang đăng nhập...' : 'Đăng nhập ngay' }}
          </button>

          <!-- Divider -->
          <div class="relative flex items-center justify-center my-3">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-slate-200"></div>
            </div>
            <span class="relative bg-white px-2.5 text-[11px] font-bold text-slate-400 uppercase">Hoặc</span>
          </div>

          <!-- Google SSO -->
          <button 
            type="button" 
            class="flex items-center justify-center gap-2.5 w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition active:scale-[0.99]"
            @click="loginWithGoogle"
          >
            <svg class="h-4 w-4" viewBox="0 0 24 24">
              <path fill="#EA4335" d="M12 5.04c1.66 0 3.2.57 4.38 1.69l3.27-3.27C17.67 1.47 15 0 12 0 7.35 0 3.4 2.67 1.48 6.56l3.86 3c.9-2.69 3.4-4.52 6.66-4.52z"/>
              <path fill="#4285F4" d="M23.49 12.27c0-.81-.07-1.59-.2-2.34H12v4.44h6.46c-.28 1.46-1.1 2.69-2.34 3.52l3.62 2.81c2.12-1.95 3.75-4.83 3.75-8.43z"/>
              <path fill="#FBBC05" d="M5.34 9.56c-.23-.69-.36-1.43-.36-2.2s.13-1.51.36-2.2l-3.86-3C.53 3.96 0 5.48 0 7.36c0 1.88.53 3.4 1.48 5.16l3.86-3z"/>
              <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.62-2.81c-1.01.68-2.3 1.09-4.31 1.09-3.26 0-5.76-1.83-6.66-4.52l-3.86 3C3.4 21.33 7.35 24 12 24z"/>
            </svg>
            <span>Đăng nhập bằng Google</span>
          </button>

          <div v-if="successMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-bold text-emerald-700">{{ successMessage }}</div>
        </form>

        <p class="text-center text-xs text-slate-500 pt-2 border-t border-slate-100">
          Chưa có tài khoản? 
          <button type="button" class="font-bold text-[#7C3AED] hover:underline" @click="goToRegister">Đăng ký ngay</button>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { authApi, tokenStorage, getDefaultRouteForRole } from '@/services/api'
import BrandLogo from '@/components/common/BrandLogo.vue'

const route = useRoute()
const router = useRouter()
const isPasswordVisible = ref(false)
const successMessage = ref('')
const isSubmitting = ref(false)
const isShaking = ref(false)

const form = reactive({ email: '', password: '', remember: true })
const errors = reactive({ email: '', password: '' })
const touched = reactive({ email: false, password: false })

const showQuickLogin = ref(false)
const lastUser = ref({ name: '', email: '', avatar: '', role_label: 'user' })
const lastLoginMethod = ref('password')
const quickPassword = ref('')

const switchToStandardLogin = () => {
  showQuickLogin.value = false
  localStorage.removeItem('quizflex_last_user')
}

const emailTypoHint = computed(() => {
  const email = form.email.trim()
  if (!email || !email.includes('@')) return ''
  const domain = email.split('@')[1] || ''
  if (domain === 'gmail.con' || domain === 'gmai.com' || domain === 'gamil.com') {
    return email.split('@')[0] + '@gmail.com'
  }
  return ''
})

const applyEmailHint = () => {
  if (emailTypoHint.value) {
    form.email = emailTypoHint.value
    validateField('email')
  }
}

const validateField = (field) => {
  if (field === 'email') {
    if (!form.email) {
      errors.email = 'Email không được để trống.'
    } else if (!/^\S+@\S+\.\S+$/.test(form.email)) {
      errors.email = 'Email chưa đúng định dạng (ví dụ: name@domain.com).'
    } else {
      errors.email = ''
    }
  } else if (field === 'password') {
    if (!form.password) {
      errors.password = 'Mật khẩu không được để trống.'
    } else if (form.password.length < 6) {
      errors.password = 'Mật khẩu tối thiểu 6 ký tự.'
    } else {
      errors.password = ''
    }
  }
}

const handleBlur = (field) => {
  touched[field] = true
  validateField(field)
}

const handleInput = (field) => {
  if (touched[field]) {
    validateField(field)
  }
}

const validateAll = () => {
  touched.email = true
  touched.password = true
  validateField('email')
  validateField('password')

  const isValid = !errors.email && !errors.password
  if (!isValid) {
    triggerShakeAnimation()
  }
  return isValid
}

const triggerShakeAnimation = () => {
  isShaking.value = true
  setTimeout(() => {
    isShaking.value = false
  }, 500)
}

const handleQuickLoginPassword = async () => {
  errors.password = ''
  successMessage.value = ''
  if (!quickPassword.value) {
    errors.password = 'Vui lòng nhập mật khẩu.'
    triggerShakeAnimation()
    return
  }

  isSubmitting.value = true
  try {
    const user = await authApi.login({ email: lastUser.value.email, password: quickPassword.value })
    successMessage.value = 'Đăng nhập thành công.'
    localStorage.setItem('quizflex_last_login_method', 'password')

    if (user.is_locked) {
      successMessage.value = 'Tài khoản của bạn đã bị khóa. Đang chuyển tới trang kháng cáo...'
      setTimeout(() => {
        router.push('/account-locked')
      }, 400)
      return
    }

    let targetPath = getDefaultRouteForRole(user.role)
    setTimeout(() => {
      router.push(targetPath)
    }, 400)
  } catch (error) {
    errors.password = error.message
    triggerShakeAnimation()
  } finally {
    isSubmitting.value = false
  }
}

onMounted(async () => {
  const state = history.state
  const savedUser = localStorage.getItem('quizflex_last_user')
  const hasAuthParams = route.query.token || route.query.error_message || route.query.email || route.query.reset || (state && state.email)
  
  if (savedUser && !hasAuthParams) {
    try {
      lastUser.value = JSON.parse(savedUser)
      lastLoginMethod.value = localStorage.getItem('quizflex_last_login_method') || 'password'
      showQuickLogin.value = true
    } catch {
      showQuickLogin.value = false
    }
  }

  if (route.query.error_message) {
    errors.password = route.query.error_message
    form.email = ''
    form.password = ''
    showQuickLogin.value = false
    router.replace({ query: {} })
    return
  }

  if (route.query.token) {
    successMessage.value = 'Xác thực Google thành công! Đang tải thông tin cá nhân...'
    try {
      const token = route.query.token
      tokenStorage.set(token)

      const user = await authApi.me()
      successMessage.value = `Xin chào ${user.name}! Đăng nhập Google thành công.`
      localStorage.setItem('quizflex_last_login_method', 'google')

      if (user.is_locked) {
        successMessage.value = 'Tài khoản của bạn đã bị khóa. Đang chuyển tới trang kháng cáo...'
        setTimeout(() => {
          router.push('/account-locked')
        }, 400)
        return
      }

      setTimeout(() => {
        router.push('/')
      }, 400)
    } catch {
      successMessage.value = ''
      errors.password = 'Không thể lấy thông tin đăng nhập Google.'
      tokenStorage.clear()
    }
    return
  }

  if (route.query.email && route.query.email.includes('@')) {
    form.email = route.query.email
  }
  if (state && state.email) {
    if (state.email.includes('@')) {
      form.email = state.email
    }
  }
  if (state && state.password) {
    form.password = state.password
    if (route.query.reset === 'success') {
      successMessage.value = 'Đặt lại mật khẩu thành công! Mật khẩu mới đã được điền sẵn.'
    } else {
      successMessage.value = 'Kích hoạt tài khoản thành công! Vui lòng nhấn Đăng nhập để tiếp tục.'
    }
  } else if (route.query.reset === 'success') {
    successMessage.value = 'Đặt lại mật khẩu thành công! Vui lòng nhập mật khẩu mới để đăng nhập.'
  }
})

const safeRedirect = (value) => {
  if (!value || typeof value !== 'string') return ''
  if (!value.startsWith('/') || value.startsWith('//')) return ''
  return value
}

const handleLogin = async () => {
  successMessage.value = ''
  if (!validateAll()) return
  isSubmitting.value = true
  try {
    const user = await authApi.login({ email: form.email.trim(), password: form.password })
    successMessage.value = 'Đăng nhập thành công.'
    localStorage.setItem('quizflex_last_login_method', 'password')

    if (user.is_locked) {
      successMessage.value = 'Tài khoản của bạn đã bị khóa. Đang chuyển tới trang kháng cáo...'
      setTimeout(() => {
        router.push('/account-locked')
      }, 400)
      return
    }

    const query = {}
    let targetPath = safeRedirect(route.query.redirect) || getDefaultRouteForRole(user.role)
    if (route.query.plan) {
      targetPath = '/upgrade'
      query.plan = route.query.plan
    }

    setTimeout(() => {
      router.push({ path: targetPath, query })
    }, 400)
  } catch (error) {
    successMessage.value = ''
    errors.password = error.message
    triggerShakeAnimation()
  } finally {
    isSubmitting.value = false
  }
}

const loginWithGoogle = () => {
  const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || '/api'
  let backendUrl = ''
  
  if (apiBaseUrl.startsWith('http')) {
    backendUrl = apiBaseUrl.replace(/\/api$/, '')
  } else {
    backendUrl = `${window.location.protocol}//${window.location.hostname}:8000`
  }
  
  if (window.location.hostname === 'localhost' && backendUrl.includes('127.0.0.1')) {
    backendUrl = backendUrl.replace('127.0.0.1', 'localhost')
  } else if (window.location.hostname === '127.0.0.1' && backendUrl.includes('localhost')) {
    backendUrl = backendUrl.replace('localhost', '127.0.0.1')
  }
  
  window.location.href = `${backendUrl}/auth/google/redirect`
}

const goToRegister = () => {
  const query = route.query.redirect ? { redirect: route.query.redirect } : {}
  router.push({ path: '/register', query, state: { email: form.email || lastUser.value?.email || '' } })
}
</script>

<style scoped>
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20%, 60% { transform: translateX(-6px); }
  40%, 80% { transform: translateX(6px); }
}

.animate-shake {
  animation: shake 0.4s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
}
</style>
