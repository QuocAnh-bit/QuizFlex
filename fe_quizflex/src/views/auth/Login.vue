<template>
  <div class="relative grid min-h-[680px] overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface-strong)] shadow-[var(--shadow-soft)] lg:grid-cols-[0.92fr_1.08fr]">
    <aside class="relative hidden min-h-[680px] overflow-hidden p-8 text-white lg:flex lg:flex-col lg:justify-between">
      <div class="absolute inset-0 bg-gradient-to-br from-[var(--primary)]/90 via-[var(--primary-2)]/70 to-[var(--accent)]/55"></div>
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.28),transparent_28%),linear-gradient(to_bottom,rgba(0,0,0,0.05),rgba(0,0,0,0.58))]"></div>
      <div class="relative z-10"><BrandLogo to="/" size="lg" /></div>
      <div class="relative z-10">
        <div class="mb-6 inline-flex rounded-full border border-white/25 bg-white/15 px-4 py-2 text-sm font-black backdrop-blur-xl">QuizFlex Workspace</div>
        <h2 class="max-w-md text-5xl font-black leading-[0.95] tracking-[-0.075em]">Chào mừng trở lại.</h2>
        <p class="mt-5 max-w-md text-base font-medium leading-8 text-white/75">Đăng nhập để tiếp tục quản lý quiz, room realtime và lượt tạo câu hỏi bằng AI.</p>
      </div>
      <div class="relative z-10 grid grid-cols-2 gap-3">
        <div class="rounded-[1.25rem] border border-white/15 bg-white/10 p-4 backdrop-blur-xl">
          <p class="text-xs font-bold text-white/60">Quiz</p>
          <p class="mt-1 text-2xl font-black">248</p>
        </div>
        <div class="rounded-[1.25rem] border border-white/15 bg-white/10 p-4 backdrop-blur-xl">
          <p class="text-xs font-bold text-white/60">AI usage</p>
          <p class="mt-1 text-2xl font-black">8.2k</p>
        </div>
      </div>
    </aside>
    
    <section class="relative grid content-center px-5 py-8 sm:px-8 lg:px-14">
      <div class="mx-auto w-full max-w-[470px]">
        <div class="mb-8 lg:hidden"><BrandLogo to="/" size="lg" /></div>
        
        <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl sm:p-8">
          <div class="pointer-events-none absolute -right-20 -top-20 h-52 w-52 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
          
          <div class="relative z-10">
            <!-- Alert Đăng xuất thành công -->
            <div v-if="route.query.logout === 'success'" class="mb-5 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-3.5 text-xs font-bold text-emerald-400 flex items-center gap-2">
              <span>✅</span> Bạn đã đăng xuất thành công khỏi hệ thống!
            </div>

            <!-- TRẠNG THÁI XÁC THỰC GOOGLE SSO ĐANG CHẠY -->
            <div v-if="route.query.token && !errors.password" class="text-center py-8">
              <div class="mb-5 inline-flex rounded-full border border-emerald-500/20 bg-emerald-500/10 px-4 py-2 text-sm font-black text-emerald-400">Google SSO Authentication</div>
              <h1 class="text-3xl font-black leading-none tracking-[-0.065em] text-[var(--text)]">Đang đăng nhập...</h1>
              <p class="mt-3 text-sm font-medium leading-7 text-[var(--muted)]">Vui lòng chờ giây lát, chúng tôi đang thiết lập phiên làm việc.</p>
              
              <!-- Premium custom Glassmorphism loading spinner -->
              <div class="mt-8 flex justify-center">
                <div class="relative flex h-14 w-14 items-center justify-center">
                  <div class="absolute h-full w-full rounded-full border-4 border-[var(--border-strong)]"></div>
                  <div class="absolute h-full w-full rounded-full border-4 border-t-[var(--primary)] border-r-[var(--primary)] animate-spin"></div>
                </div>
              </div>
              <div v-if="successMessage" class="mt-6 rounded-2xl border border-emerald-500/25 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-400">{{ successMessage }}</div>
            </div>

            <!-- HIỂN THỊ ĐĂNG NHẬP NHANH (Sleek Account Switcher) -->
            <div v-else-if="showQuickLogin" class="text-center">
              <div class="mb-5 inline-flex rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-4 py-2 text-sm font-black text-[var(--primary)]">Chào mừng trở lại</div>
              <h1 class="text-4xl font-black leading-none tracking-[-0.065em] text-[var(--text)]">Đăng nhập nhanh</h1>
              <p class="mt-3 text-sm font-medium leading-7 text-[var(--muted)]">Chào bạn quay trở lại, hãy tiếp tục trải nghiệm!</p>

              <!-- Profile Box Glassmorphism -->
              <div class="my-6 flex flex-col items-center rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-center shadow-lg relative overflow-hidden backdrop-blur-xl">
                <div class="pointer-events-none absolute -left-10 -top-10 h-28 w-28 rounded-full bg-[var(--primary)]/10 blur-2xl"></div>
                <!-- Avatar -->
                <div class="relative flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-[var(--primary)] to-[var(--accent)] p-0.5 shadow-md">
                  <img v-if="lastUser.avatar" :src="lastUser.avatar" class="h-full w-full rounded-full object-cover border-2 border-[var(--surface)]" />
                  <div v-else class="h-full w-full rounded-full bg-[var(--surface)] text-2xl font-black text-[var(--primary)] flex items-center justify-center">
                    {{ lastUser.name ? lastUser.name.charAt(0) : 'U' }}
                  </div>
                </div>
                <!-- Name & Email -->
                <b class="mt-3 text-lg text-[var(--text)] font-black">{{ lastUser.name }}</b>
                <span class="text-xs text-[var(--muted)] font-bold mt-1">{{ lastUser.email }}</span>
                <span class="mt-2 rounded-full bg-[var(--primary)]/15 px-3 py-1 text-[10px] font-black text-[var(--primary)] uppercase">{{ lastUser.role_label }}</span>
              </div>

              <!-- Form nhập mật khẩu (Nếu đăng nhập bằng password lần trước) -->
              <form v-if="lastLoginMethod === 'password'" class="grid gap-4" @submit.prevent="handleQuickLoginPassword">
                <label class="grid gap-2 text-sm font-black text-[var(--text)] text-left">
                  Mật khẩu tài khoản
                  <div class="flex items-center gap-3 rounded-2xl border bg-[var(--input-bg)] px-4 py-3 transition focus-within:border-[var(--border-strong)]" :class="errors.password ? 'border-rose-500/50' : 'border-[var(--border)]'">
                    <span class="text-sm font-black text-[var(--primary)]">#</span>
                    <input v-model="quickPassword" class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]" :type="isPasswordVisible ? 'text' : 'password'" placeholder="Nhập mật khẩu" autocomplete="current-password" />
                    <button type="button" class="text-xs font-black text-[var(--primary)]" @click="isPasswordVisible = !isPasswordVisible">{{ isPasswordVisible ? 'Ẩn' : 'Hiện' }}</button>
                  </div>
                  <span v-if="errors.password" class="text-xs font-bold text-rose-400 mt-1">{{ errors.password }}</span>
                </label>
                <button type="submit" class="btn-primary w-full py-3.5 text-sm font-black shadow-md">Đăng nhập</button>
                
                <!-- Tùy chọn đăng nhập bằng Google nhanh (Luôn có sẵn dưới dạng phụ) -->
                <div class="relative flex items-center justify-center my-1.5">
                  <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-[var(--border)]"></div>
                  </div>
                  <div class="relative px-2.5 text-[10px] uppercase bg-[var(--surface)] text-[var(--muted)] font-black">
                    Hoặc đăng nhập nhanh với
                  </div>
                </div>

                <button 
                  type="button" 
                  class="flex items-center justify-center gap-2.5 w-full rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-3 text-xs font-black text-[var(--text)] transition hover:bg-[var(--chip-active)] focus:outline-none"
                  @click="loginWithGoogle"
                >
                  <svg class="h-5 w-5" viewBox="0 0 24 24">
                    <path fill="#EA4335" d="M12 5.04c1.66 0 3.2.57 4.38 1.69l3.27-3.27C17.67 1.47 15 0 12 0 7.35 0 3.4 2.67 1.48 6.56l3.86 3c.9-2.69 3.4-4.52 6.66-4.52z"/>
                    <path fill="#4285F4" d="M23.49 12.27c0-.81-.07-1.59-.2-2.34H12v4.44h6.46c-.28 1.46-1.1 2.69-2.34 3.52l3.62 2.81c2.12-1.95 3.75-4.83 3.75-8.43z"/>
                    <path fill="#FBBC05" d="M5.34 9.56c-.23-.69-.36-1.43-.36-2.2s.13-1.51.36-2.2l-3.86-3C.53 3.96 0 5.48 0 7.36c0 1.88.53 3.4 1.48 5.16l3.86-3z"/>
                    <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.62-2.81c-1.01.68-2.3 1.09-4.31 1.09-3.26 0-5.76-1.83-6.66-4.52l-3.86 3C3.4 21.33 7.35 24 12 24z"/>
                  </svg>
                  Đăng nhập bằng Google
                </button>
              </form>

              <!-- Nút đăng nhập Google nhanh (Nếu đăng nhập bằng google lần trước) -->
              <div v-else class="grid gap-4">
                <button type="button" class="group relative inline-flex items-center justify-center gap-3 w-full rounded-2xl bg-gradient-to-r from-[var(--primary)] via-[var(--primary-2)] to-[var(--accent)] py-4 text-sm font-black text-white shadow-lg transition duration-300 hover:-translate-y-0.5 hover:shadow-xl active:scale-95 overflow-hidden" @click="loginWithGoogle">
                  <span class="absolute inset-0 translate-x-[-120%] bg-gradient-to-r from-transparent via-white/30 to-transparent transition duration-700 group-hover:translate-x-[120%]"></span>
                  <svg class="h-5 w-5 fill-white" viewBox="0 0 24 24">
                    <path d="M12.24 10.285V14.4h6.887c-.648 2.41-2.519 4.114-5.136 4.114-3.526 0-6.388-2.87-6.388-6.406 0-3.536 2.862-6.406 6.388-6.406 1.69 0 3.142.637 4.25 1.7l3.15-3.15C19.24 2.23 15.93 1 12.24 1 5.76 1 .5 6.26.5 12.75c0 6.49 5.26 11.75 11.74 11.75 6.77 0 11.76-4.76 11.76-11.75 0-.79-.08-1.39-.26-2.465H12.24z"/>
                  </svg>
                  Tiếp tục bằng Google SSO
                </button>
                
                <!-- Hoặc đăng nhập nhanh bằng Mật khẩu (Luôn hiển thị làm tùy chọn phụ) -->
                <div class="mt-2 text-xs font-black">
                  <button type="button" class="text-[var(--primary)] hover:underline" @click="lastLoginMethod = 'password'">Đăng nhập bằng mật khẩu thay thế</button>
                </div>
              </div>

              <!-- Lựa chọn đổi tài khoản -->
              <div class="mt-6 flex items-center justify-center gap-3 text-xs font-black">
                <button type="button" class="text-[var(--primary)] hover:underline" @click="switchToStandardLogin">Sử dụng tài khoản khác</button>
                <span class="text-[var(--border-strong)]">•</span>
                <button type="button" class="text-[var(--muted)] hover:underline" @click="goToRegister">Đăng ký ngay</button>
              </div>
              <div v-if="successMessage" class="mt-4 rounded-2xl border border-emerald-500/25 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-400">{{ successMessage }}</div>
            </div>

            <!-- GIAO DIỆN ĐĂNG NHẬP TIÊU CHUẨN (Mặc định) -->
            <div v-else>
              <div class="mb-6 inline-flex rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-4 py-2 text-sm font-black text-[var(--primary)]">Đăng nhập tài khoản</div>
              <h1 class="text-4xl font-black leading-none tracking-[-0.065em] text-[var(--text)]">Tiếp tục với QuizFlex</h1>
              <p class="mt-3 text-sm font-medium leading-7 text-[var(--muted)]">Đăng nhập bằng tài khoản đã có trong bảng users.</p>
              
              <form class="mt-8 grid gap-5" @submit.prevent="handleLogin">
                <label class="grid gap-2 text-sm font-black text-[var(--text)]">Email<div class="flex items-center gap-3 rounded-2xl border bg-[var(--input-bg)] px-4 py-3 transition focus-within:border-[var(--border-strong)]" :class="errors.email ? 'border-rose-500/50' : 'border-[var(--border)]'"><span class="text-sm font-black text-[var(--primary)]">✦</span><input v-model="form.email" class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]" type="email" placeholder="you@example.com" autocomplete="email" /></div><span v-if="errors.email" class="text-xs font-bold text-rose-400">{{ errors.email }}</span></label>
                <label class="grid gap-2 text-sm font-black text-[var(--text)]">Mật khẩu<div class="flex items-center gap-3 rounded-2xl border bg-[var(--input-bg)] px-4 py-3 transition focus-within:border-[var(--border-strong)]" :class="errors.password ? 'border-rose-500/50' : 'border-[var(--border)]'"><span class="text-sm font-black text-[var(--primary)]">#</span><input v-model="form.password" class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]" :type="isPasswordVisible ? 'text' : 'password'" placeholder="••••••••" autocomplete="current-password" /><button type="button" class="rounded-full px-3 py-1 text-xs font-black text-[var(--primary)] transition hover:bg-[var(--chip-active)]" @click="isPasswordVisible = !isPasswordVisible">{{ isPasswordVisible ? 'Ẩn' : 'Hiện' }}</button></div><span v-if="errors.password" class="text-xs font-bold text-rose-400">{{ errors.password }}</span></label>
                <div class="flex flex-wrap items-center justify-between gap-3 text-sm"><label class="flex cursor-pointer items-center gap-3 font-bold text-[var(--muted)]"><input v-model="form.remember" type="checkbox" class="h-4 w-4 accent-[var(--primary)]" />Ghi nhớ</label><router-link to="/forgot-password" class="font-black text-[var(--primary)]">Quên mật khẩu?</router-link></div>
                <button type="submit" class="btn-primary w-full">Đăng nhập</button>

                <div class="relative flex items-center justify-center my-2">
                  <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-[var(--border)]"></div>
                  </div>
                  <div class="relative px-3 text-xs uppercase bg-[var(--surface)] text-[var(--muted)]">
                    Hoặc tiếp tục với
                  </div>
                </div>

                <button 
                  type="button" 
                  class="flex items-center justify-center gap-3 w-full rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-3.5 text-sm font-bold text-[var(--text)] transition hover:bg-[var(--chip-active)] focus:outline-none"
                  @click="loginWithGoogle"
                >
                  <svg class="h-5 w-5" viewBox="0 0 24 24">
                    <path fill="#EA4335" d="M12 5.04c1.66 0 3.2.57 4.38 1.69l3.27-3.27C17.67 1.47 15 0 12 0 7.35 0 3.4 2.67 1.48 6.56l3.86 3c.9-2.69 3.4-4.52 6.66-4.52z"/>
                    <path fill="#4285F4" d="M23.49 12.27c0-.81-.07-1.59-.2-2.34H12v4.44h6.46c-.28 1.46-1.1 2.69-2.34 3.52l3.62 2.81c2.12-1.95 3.75-4.83 3.75-8.43z"/>
                    <path fill="#FBBC05" d="M5.34 9.56c-.23-.69-.36-1.43-.36-2.2s.13-1.51.36-2.2l-3.86-3C.53 3.96 0 5.48 0 7.36c0 1.88.53 3.4 1.48 5.16l3.86-3z"/>
                    <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.62-2.81c-1.01.68-2.3 1.09-4.31 1.09-3.26 0-5.76-1.83-6.66-4.52l-3.86 3C3.4 21.33 7.35 24 12 24z"/>
                  </svg>
                  Đăng nhập bằng Google
                </button>

                <div v-if="successMessage" class="rounded-2xl border border-emerald-500/25 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-400">{{ successMessage }}</div>
              </form>
              <p class="mt-7 text-center text-sm font-semibold text-[var(--muted)]">Chưa có tài khoản? <button type="button" class="font-black text-[var(--primary)]" @click="goToRegister">Đăng ký ngay</button></p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { authApi, tokenStorage, getDefaultRouteForRole } from '@/services/api'
import BrandLogo from '@/components/common/BrandLogo.vue'

const route = useRoute()
const router = useRouter()
const isPasswordVisible = ref(false)
const successMessage = ref('')
const form = reactive({ email: '', password: '', remember: true })
const errors = reactive({ email: '', password: '' })

// Trạng thái cho Thẻ đăng nhập nhanh (Sleek Account Switcher)
const showQuickLogin = ref(false)
const lastUser = ref({ name: '', email: '', avatar: '', role_label: 'user' })
const lastLoginMethod = ref('password')
const quickPassword = ref('')

const switchToStandardLogin = () => {
  showQuickLogin.value = false
  localStorage.removeItem('quizflex_last_user')
}

const handleQuickLoginPassword = async () => {
  errors.password = ''
  successMessage.value = ''
  if (!quickPassword.value) {
    errors.password = 'Vui lòng nhập mật khẩu.'
    return
  }

  try {
    const user = await authApi.login({ email: lastUser.value.email, password: quickPassword.value })
    successMessage.value = 'Đăng nhập thành công.'
    localStorage.setItem('quizflex_last_login_method', 'password')

    let targetPath = getDefaultRouteForRole(user.role)
    setTimeout(() => {
      router.push(targetPath)
    }, 1000)
  } catch (error) {
    errors.password = error.message
  }
}

onMounted(async () => {
  const state = history.state

  // Kiểm tra thông tin tài khoản đăng nhập nhanh trong localStorage
  const savedUser = localStorage.getItem('quizflex_last_user')
  // Bỏ qua đăng nhập nhanh nếu URL có token hoặc tham số rõ ràng
  const hasAuthParams = route.query.token || route.query.error_message || route.query.email || (state && state.email)
  
  if (savedUser && !hasAuthParams) {
    try {
      lastUser.value = JSON.parse(savedUser)
      lastLoginMethod.value = localStorage.getItem('quizflex_last_login_method') || 'password'
      showQuickLogin.value = true
    } catch {
      showQuickLogin.value = false
    }
  }

  // 1. Nhận lỗi từ Google SSO gửi về (ví dụ tài khoản email chưa tồn tại)
  if (route.query.error_message) {
    errors.password = route.query.error_message
    form.email = ''
    form.password = ''
    showQuickLogin.value = false
    // Xóa query parameter trên URL để sạch giao diện
    router.replace({ query: {} })
    return
  }

  // 2. Nhận Token đăng nhập từ Google SSO gửi về
  if (route.query.token) {
    successMessage.value = 'Xác thực Google thành công! Đang tải thông tin cá nhân...'
    try {
      const token = route.query.token
      tokenStorage.set(token) // Lưu token mới vào localStorage

      // Đồng bộ lấy thông tin user ngay lập tức
      const user = await authApi.me()
      successMessage.value = `Xin chào ${user.name}! Đăng nhập Google thành công.`
      localStorage.setItem('quizflex_last_login_method', 'google')

      setTimeout(() => {
        router.push('/')
      }, 1200)
    } catch (err) {
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
    // Chỉ điền email nếu định dạng hợp lệ (có chứa ký tự @), tránh điền nhầm username như 'quizzz11'
    if (state.email.includes('@')) {
      form.email = state.email
    }
  }
  if (state && state.password) {
    form.password = state.password
    successMessage.value = 'Đăng ký thành công! Đang tự động đăng nhập...'
    setTimeout(() => {
      handleLogin()
    }, 800)
  }
})

const validate = () => { errors.email = !form.email ? 'Email không được để trống.' : !/^\S+@\S+\.\S+$/.test(form.email) ? 'Email chưa đúng định dạng.' : ''; errors.password = !form.password ? 'Mật khẩu không được để trống.' : form.password.length < 6 ? 'Mật khẩu tối thiểu 6 ký tự.' : ''; return !errors.email && !errors.password }

const safeRedirect = (value) => {
  if (!value || typeof value !== 'string') return ''
  if (!value.startsWith('/') || value.startsWith('//')) return ''
  return value
}

const handleLogin = async () => {
  successMessage.value = ''
  if (!validate()) return
  try {
    const user = await authApi.login({ email: form.email, password: form.password })
    successMessage.value = 'Đăng nhập thành công.'
    localStorage.setItem('quizflex_last_login_method', 'password')

    const query = {}
    let targetPath = safeRedirect(route.query.redirect) || getDefaultRouteForRole(user.role)
    if (route.query.plan) {
      targetPath = '/upgrade'
      query.plan = route.query.plan
    }

    setTimeout(() => {
      router.push({ path: targetPath, query })
    }, 1000)
  } catch (error) {
    successMessage.value = ''
    errors.password = error.message
  }
}

const loginWithGoogle = () => {
  // URL redirect của Backend cho Google SSO
  const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || '/api'
  let backendUrl = ''
  
  if (apiBaseUrl.startsWith('http')) {
    backendUrl = apiBaseUrl.replace(/\/api$/, '')
  } else {
    // Relative path fallback (e.g. /api)
    backendUrl = `${window.location.protocol}//${window.location.hostname}:8000`
  }
  
  // Đồng bộ hóa giữa localhost và 127.0.0.1 dựa trên tên miền hiện tại của trình duyệt
  if (window.location.hostname === 'localhost' && backendUrl.includes('127.0.0.1')) {
    backendUrl = backendUrl.replace('127.0.0.1', 'localhost')
  } else if (window.location.hostname === '127.0.0.1' && backendUrl.includes('localhost')) {
    backendUrl = backendUrl.replace('localhost', '127.0.0.1')
  }
  
  window.location.href = `${backendUrl}/auth/google/redirect`
}

const goToRegister = () => {
  router.push({ path: '/register', state: { email: form.email || lastUser.value?.email || '' } })
}
</script>
