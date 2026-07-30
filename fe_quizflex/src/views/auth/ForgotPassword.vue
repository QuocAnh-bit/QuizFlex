<template>
  <div class="mx-auto max-w-xl rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 sm:p-8 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
    <div class="flex items-center justify-between">
      <BrandLogo to="/" size="lg" />
      <span class="rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-3.5 py-1 text-xs font-black text-[var(--primary)]">
        Khôi phục mật khẩu
      </span>
    </div>

    <!-- Stepper indicator -->
    <div class="mt-6 flex items-center justify-center gap-2">
      <div
        class="flex h-8 items-center gap-2 rounded-full px-4 text-xs font-black transition"
        :class="step >= 1 ? 'bg-[var(--primary)] text-white' : 'bg-[var(--surface-soft)] text-[var(--muted)]'"
      >
        <span>1</span>
        <span>Nhập Email</span>
      </div>
      <div class="h-0.5 w-6 bg-[var(--border)]"></div>
      <div
        class="flex h-8 items-center gap-2 rounded-full px-4 text-xs font-black transition"
        :class="step >= 2 ? 'bg-[var(--primary)] text-white' : 'bg-[var(--surface-soft)] text-[var(--muted)]'"
      >
        <span>2</span>
        <span>Mã OTP & Mật khẩu</span>
      </div>
    </div>

    <!-- Message Alert -->
    <div
      v-if="message.text"
      class="mt-6 rounded-2xl border p-4 text-sm font-bold shadow-sm transition"
      :class="message.type === 'success' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-rose-500/30 bg-rose-500/10 text-rose-300'"
    >
      <div class="flex items-start gap-3">
        <span class="text-base">{{ message.type === 'success' ? '✅' : '⚠️' }}</span>
        <div class="flex-1 leading-6">{{ message.text }}</div>
      </div>
    </div>

    <!-- BƯỚC 1: NHẬP EMAIL -->
    <div v-if="step === 1" class="mt-6">
      <h1 class="text-3xl font-black tracking-[-0.05em] text-[var(--text)] sm:text-4xl">Quên mật khẩu?</h1>
      <p class="mt-2 text-sm leading-6 text-[var(--muted)]">
        Nhập địa chỉ email đăng ký tài khoản QuizFlex của bạn. Chúng tôi sẽ gửi mã xác thực OTP 6 số để bạn đặt lại mật khẩu.
      </p>

      <form class="mt-6 grid gap-4" @submit.prevent="handleSendOtp">
        <label class="grid gap-2 text-xs font-black uppercase text-[var(--muted)]">
          Email tài khoản
          <input
            v-model.trim="form.email"
            type="email"
            required
            class="field"
            placeholder="nhap-email@example.com"
            :disabled="loading"
          />
        </label>

        <button
          type="submit"
          class="btn-primary w-full !py-3 text-sm font-black shadow-lg"
          :disabled="loading"
        >
          <span v-if="loading">Đang gửi mã OTP...</span>
          <span v-else>🚀 Gửi mã xác thực OTP</span>
        </button>

        <router-link class="btn-ghost w-full text-center text-sm font-bold" to="/login">
          ← Quay lại đăng nhập
        </router-link>
      </form>
    </div>

    <!-- BƯỚC 2: NHẬP OTP & ĐỔI MẬT KHẨU -->
    <div v-else-if="step === 2" class="mt-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-black tracking-[-0.05em] text-[var(--text)] sm:text-3xl">Nhập OTP & Mật khẩu mới</h1>
          <p class="mt-1 text-xs text-[var(--muted)]">
            Mã OTP đã gửi tới <strong class="text-[var(--text)]">{{ form.email }}</strong>
          </p>
        </div>
        <button type="button" class="text-xs font-bold text-[var(--primary)] hover:underline" @click="step = 1">
          Sửa email
        </button>
      </div>

      <form class="mt-6 grid gap-4" @submit.prevent="handleResetPassword">
        <label class="grid gap-2 text-xs font-black uppercase text-[var(--muted)]">
          Mã OTP (6 chữ số)
          <input
            v-model.trim="form.otp"
            type="text"
            name="one-time-code"
            autocomplete="one-time-code"
            inputmode="numeric"
            maxlength="6"
            required
            class="field text-center text-lg tracking-[0.3em] font-black"
            placeholder="123456"
            :disabled="loading"
          />
        </label>

        <label class="grid gap-2 text-xs font-black uppercase text-[var(--muted)]">
          Mật khẩu mới
          <input
            v-model="form.password"
            type="password"
            name="new-password"
            autocomplete="new-password"
            required
            minlength="6"
            class="field"
            placeholder="Tối thiểu 6 ký tự"
            :disabled="loading"
          />
        </label>

        <label class="grid gap-2 text-xs font-black uppercase text-[var(--muted)]">
          Xác nhận mật khẩu mới
          <input
            v-model="form.password_confirmation"
            type="password"
            name="confirm-new-password"
            autocomplete="new-password"
            required
            minlength="6"
            class="field"
            placeholder="Nhập lại mật khẩu mới"
            :disabled="loading"
          />
        </label>

        <button
          type="submit"
          class="btn-primary w-full !py-3 text-sm font-black shadow-lg"
          :disabled="loading"
        >
          <span v-if="loading">Đang cập nhật mật khẩu...</span>
          <span v-else>💾 Đặt lại mật khẩu</span>
        </button>

        <div class="flex items-center justify-between text-xs">
          <span class="text-[var(--muted)]">Không nhận được mã?</span>
          <button
            type="button"
            class="font-black text-[var(--primary)] disabled:opacity-50 disabled:cursor-not-allowed hover:underline"
            :disabled="resendTimer > 0 || loading"
            @click="handleResendOtp"
          >
            {{ resendTimer > 0 ? `Gửi lại sau (${resendTimer}s)` : 'Gửi lại mã OTP' }}
          </button>
        </div>
      </form>
    </div>

    <!-- BƯỚC 3: THÀNH CÔNG -->
    <div v-else-if="step === 3" class="mt-6 text-center">
      <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/20 text-3xl font-black text-emerald-400">
        ✓
      </div>
      <h2 class="mt-4 text-3xl font-black text-[var(--text)]">Đổi mật khẩu thành công!</h2>
      <p class="mt-2 text-sm leading-6 text-[var(--muted)]">
        Mật khẩu mới của bạn đã được cập nhật. Bạn có thể sử dụng mật khẩu mới này để đăng nhập ngay vào hệ thống QuizFlex.
      </p>

      <div class="mt-6">
        <button type="button" class="btn-primary w-full !py-3 text-sm font-black shadow-lg" @click="goToLogin">
          🔑 Đăng nhập ngay
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
  if (!form.email) return

  loading.value = true
  setMessage('', 'success')

  try {
    const res = await authApi.forgotPasswordSendOtp({ email: form.email })
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
  if (form.password !== form.password_confirmation) {
    setMessage('Mật khẩu xác nhận không khớp.', 'error')
    return
  }

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
