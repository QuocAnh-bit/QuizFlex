<template>
  <div class="mx-auto max-w-md py-6">
    <div class="card p-6 sm:p-8 space-y-6 shadow-sm">
      <!-- Logo Header -->
      <div class="text-center space-y-2">
        <div class="flex justify-center">
          <BrandLogo to="/" size="md" />
        </div>
        <h1 class="text-2xl font-black text-slate-900 pt-1">Khôi phục mật khẩu</h1>
        <p class="text-xs text-slate-500">Đặt lại mật khẩu tài khoản QuizFlex của bạn qua mã OTP</p>
      </div>

      <!-- Stepper indicator -->
      <div class="flex items-center justify-center gap-2 text-xs font-bold">
        <div
          class="flex items-center gap-1.5 rounded-full px-3 py-1 transition"
          :class="step >= 1 ? 'bg-[#7C3AED] text-white' : 'bg-slate-100 text-slate-400'"
        >
          <span>1</span>
          <span>Email</span>
        </div>
        <span class="text-slate-300">→</span>
        <div
          class="flex items-center gap-1.5 rounded-full px-3 py-1 transition"
          :class="step >= 2 ? 'bg-[#7C3AED] text-white' : 'bg-slate-100 text-slate-400'"
        >
          <span>2</span>
          <span>OTP & Mật khẩu</span>
        </div>
      </div>

      <!-- Message Alert -->
      <div
        v-if="message.text"
        class="rounded-xl border p-3 text-xs font-bold"
        :class="message.type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'"
      >
        <div class="flex items-start gap-2">
          <span>{{ message.type === 'success' ? '✅' : '⚠️' }}</span>
          <div class="flex-1 leading-relaxed">{{ message.text }}</div>
        </div>
      </div>

      <!-- Step 1: Input Email -->
      <div v-if="step === 1" class="space-y-4">
        <form class="space-y-4" @submit.prevent="handleSendOtp">
          <label class="grid gap-1.5 text-xs font-bold text-slate-700">
            Email tài khoản
            <input
              v-model.trim="form.email"
              type="email"
              class="field text-xs"
              placeholder="nhap-email@example.com"
              :disabled="loading"
              required
            />
            <span v-if="errors.email" class="text-xs font-bold text-red-600">{{ errors.email }}</span>
          </label>

          <button
            type="submit"
            class="btn-primary w-full py-2.5 text-xs"
            :disabled="loading"
          >
            {{ loading ? 'Đang gửi mã OTP...' : 'Gửi mã xác thực OTP →' }}
          </button>

          <router-link class="btn-secondary w-full text-center text-xs py-2 block" to="/login">
            ← Quay lại đăng nhập
          </router-link>
        </form>
      </div>

      <!-- Step 2: OTP & New Password -->
      <div v-else-if="step === 2" class="space-y-4">
        <div class="flex items-center justify-between text-xs">
          <span class="text-slate-500">Mã gửi tới: <b class="text-slate-900">{{ form.email }}</b></span>
          <button type="button" class="font-bold text-[#7C3AED] hover:underline" @click="step = 1">
            Sửa email
          </button>
        </div>

        <form class="space-y-4" @submit.prevent="handleResetPassword">
          <label class="grid gap-1.5 text-xs font-bold text-slate-700">
            Mã OTP (6 chữ số)
            <input
              v-model.trim="form.otp"
              type="text"
              name="one-time-code"
              autocomplete="one-time-code"
              inputmode="numeric"
              maxlength="6"
              required
              class="field text-center font-mono text-lg font-bold tracking-[0.25em]"
              placeholder="123456"
              :disabled="loading"
            />
            <span v-if="errors.otp" class="text-xs font-bold text-red-600">{{ errors.otp }}</span>
          </label>

          <label class="grid gap-1.5 text-xs font-bold text-slate-700">
            Mật khẩu mới
            <input
              v-model="form.password"
              type="password"
              name="new-password"
              autocomplete="new-password"
              required
              minlength="8"
              class="field text-xs"
              placeholder="Tối thiểu 8 ký tự"
              :disabled="loading"
            />
            <span v-if="errors.password" class="text-xs font-bold text-red-600">{{ errors.password }}</span>
          </label>

          <label class="grid gap-1.5 text-xs font-bold text-slate-700">
            Xác nhận mật khẩu mới
            <input
              v-model="form.password_confirmation"
              type="password"
              name="confirm-new-password"
              autocomplete="new-password"
              required
              minlength="8"
              class="field text-xs"
              placeholder="Nhập lại mật khẩu mới"
              :disabled="loading"
            />
            <span v-if="errors.password_confirmation" class="text-xs font-bold text-red-600">{{ errors.password_confirmation }}</span>
          </label>

          <button
            type="submit"
            class="btn-primary w-full py-2.5 text-xs"
            :disabled="loading"
          >
            {{ loading ? 'Đang cập nhật mật khẩu...' : 'Đặt lại mật khẩu' }}
          </button>

          <div class="flex items-center justify-between text-xs pt-1">
            <span class="text-slate-500">Chưa nhận được mã?</span>
            <button
              type="button"
              class="font-bold text-[#7C3AED] disabled:opacity-50 disabled:cursor-not-allowed hover:underline"
              :disabled="resendTimer > 0 || loading"
              @click="handleResendOtp"
            >
              {{ resendTimer > 0 ? `Gửi lại sau (${resendTimer}s)` : 'Gửi lại mã OTP' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Step 3: Success -->
      <div v-else-if="step === 3" class="text-center py-4 space-y-4">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-2xl text-emerald-600">
          ✓
        </div>
        <h2 class="text-lg font-bold text-slate-900">Đổi mật khẩu thành công!</h2>
        <p class="text-xs text-slate-500">
          Mật khẩu mới của bạn đã được cập nhật. Bạn có thể sử dụng mật khẩu này để đăng nhập.
        </p>

        <button type="button" class="btn-primary w-full py-2.5 text-xs" @click="goToLogin">
          Đăng nhập ngay →
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import BrandLogo from '@/components/common/BrandLogo.vue'
import { authApi } from '@/services/api'

const router = useRouter()

const step = ref(1)
const loading = ref(false)
const resendTimer = ref(0)
let timerInterval = null

const form = reactive({
  email: '',
  otp: '',
  password: '',
  password_confirmation: '',
})

const errors = reactive({ email: '', otp: '', password: '', password_confirmation: '' })

const validateEmail = (value) => {
  const trimmed = value.trim()
  if (!trimmed) return 'Email không được để trống.'
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed)) return 'Email chưa đúng định dạng.'
  return ''
}

const validateOtp = (value) => {
  if (!/^\d{6}$/.test(value.trim())) return 'Mã OTP phải gồm đúng 6 chữ số.'
  return ''
}

const validatePassword = (value) => {
  if (value.length < 8) return 'Mật khẩu tối thiểu 8 ký tự.'
  return ''
}

const validateStep1 = () => {
  errors.email = validateEmail(form.email)
  return !errors.email
}

const validateStep2 = () => {
  errors.otp = validateOtp(form.otp)
  errors.password = validatePassword(form.password)
  errors.password_confirmation =
    form.password_confirmation !== form.password ? 'Mật khẩu xác nhận không khớp.' : ''
  return !errors.otp && !errors.password && !errors.password_confirmation
}

const message = reactive({
  text: '',
  type: 'success',
})

const setMessage = (text, type = 'error') => {
  message.text = text
  message.type = type
}

const startResendTimer = () => {
  resendTimer.value = 60
  if (timerInterval) clearInterval(timerInterval)
  timerInterval = setInterval(() => {
    if (resendTimer.value > 0) {
      resendTimer.value--
    } else {
      clearInterval(timerInterval)
    }
  }, 1000)
}

const handleSendOtp = async () => {
  if (!validateStep1()) return

  loading.value = true
  setMessage('', 'success')

  try {
    const res = await authApi.forgotPasswordSendOtp({ email: form.email.trim() })
    setMessage(res.message || 'Mã OTP đã được gửi thành công!', 'success')
    form.otp = ''
    form.password = ''
    form.password_confirmation = ''
    step.value = 2
    startResendTimer()
  } catch (err) {
    setMessage(err.message || 'Không thể gửi mã OTP. Vui lòng kiểm tra lại email.', 'error')
  } finally {
    loading.value = false
  }
}

const handleResendOtp = async () => {
  if (resendTimer.value > 0 || loading.value) return

  loading.value = true
  setMessage('', 'success')

  try {
    const res = await authApi.forgotPasswordSendOtp({ email: form.email })
    setMessage(res.message || 'Mã OTP mới đã được gửi tới email!', 'success')
    startResendTimer()
  } catch (err) {
    setMessage(err.message || 'Gửi lại mã OTP thất bại.', 'error')
  } finally {
    loading.value = false
  }
}

const handleResetPassword = async () => {
  if (!validateStep2()) return

  loading.value = true
  setMessage('', 'success')

  try {
    const res = await authApi.forgotPasswordReset({
      email: form.email,
      otp: form.otp,
      password: form.password,
      password_confirmation: form.password_confirmation,
    })
    setMessage(res.message || 'Đặt lại mật khẩu thành công!', 'success')
    step.value = 3
  } catch (err) {
    setMessage(err.message || 'Xác nhận OTP hoặc đổi mật khẩu thất bại.', 'error')
  } finally {
    loading.value = false
  }
}

const goToLogin = () => {
  router.push({
    path: '/login',
    query: { email: form.email, reset: 'success' },
    state: { email: form.email, password: form.password },
  })
}

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
})
</script>
