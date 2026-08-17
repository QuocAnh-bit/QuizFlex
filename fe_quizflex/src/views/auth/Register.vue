<template>
  <div class="mx-auto max-w-md py-6">
    <div class="card p-6 sm:p-8 space-y-6 shadow-sm" :class="{ 'animate-shake': isShaking }">
      <!-- Logo Header -->
      <div class="text-center space-y-2">
        <div class="flex justify-center">
          <BrandLogo to="/" size="md" />
        </div>
        <h1 class="text-2xl font-black text-slate-900 pt-1">
          {{ isOtpMode ? 'Xác thực tài khoản' : 'Tạo tài khoản mới' }}
        </h1>
        <p class="text-xs text-slate-500">
          {{ isOtpMode ? 'Nhập mã 6 chữ số đã gửi tới email' : 'Đăng ký tài khoản và bắt đầu học tập ngay hôm nay' }}
        </p>
      </div>

      <!-- Transition between Register Form and OTP Form -->
      <Transition name="fade" mode="out-in">
        <!-- 1. Register Info Form -->
        <div v-if="!isOtpMode" key="register" class="space-y-4">
          <form class="space-y-4" @submit.prevent="handleRegister" novalidate>
            <!-- Full Name -->
            <div class="space-y-1 text-xs">
              <label class="font-bold text-slate-700 block">Họ và tên</label>
              <input 
                v-model="form.fullName" 
                class="field text-xs" 
                placeholder="Nguyễn Văn A" 
                autocomplete="name"
                @blur="handleBlur('fullName')"
                @input="handleInput('fullName')"
                required
              />
              <span v-if="errors.fullName" class="text-xs font-bold text-red-600 block">{{ errors.fullName }}</span>
            </div>

            <!-- Email -->
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

            <!-- Password -->
            <div class="space-y-1 text-xs">
              <div class="flex items-center justify-between">
                <label class="font-bold text-slate-700">Mật khẩu</label>
                <span v-if="form.password" class="text-[11px] font-bold" :class="strengthTextClass">
                  Độ mạnh: {{ strengthLabel }}
                </span>
              </div>
              <div class="relative">
                <input 
                  v-model="form.password" 
                  class="field text-xs pr-14" 
                  :type="isPasswordVisible ? 'text' : 'password'" 
                  placeholder="Tối thiểu 8 ký tự" 
                  autocomplete="new-password"
                  @blur="handleBlur('password')"
                  @input="handleInput('password')"
                  required
                />
                <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-[#7C3AED]" @click="isPasswordVisible = !isPasswordVisible">
                  {{ isPasswordVisible ? 'Ẩn' : 'Hiện' }}
                </button>
              </div>

              <!-- Password Strength Bar -->
              <div v-if="form.password" class="pt-1">
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                  <div class="h-full transition-all duration-300 rounded-full" :style="{ width: strengthPercentage + '%' }" :class="strengthBarClass"></div>
                </div>
              </div>

              <span v-if="errors.password" class="text-xs font-bold text-red-600 block">{{ errors.password }}</span>
            </div>

            <!-- Terms checkbox -->
            <div class="flex items-center text-xs pt-1">
              <label class="flex items-center gap-2 cursor-pointer text-slate-600 font-medium">
                <input v-model="form.acceptTerms" type="checkbox" class="rounded text-[#7C3AED]" @change="handleBlur('acceptTerms')" />
                <span>Tôi đồng ý với điều khoản sử dụng & bảo mật</span>
              </label>
            </div>
            <span v-if="errors.acceptTerms" class="text-xs font-bold text-red-600 block">{{ errors.acceptTerms }}</span>

            <button type="submit" :disabled="isSubmitting" class="btn-primary w-full py-2.5 text-xs">
              {{ isSubmitting ? 'Đang tạo tài khoản...' : 'Đăng ký tài khoản' }}
            </button>

            <div v-if="successMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-bold text-emerald-700">{{ successMessage }}</div>
          </form>

          <p class="text-center text-xs text-slate-500 pt-2 border-t border-slate-100">
            Đã có tài khoản? 
            <router-link class="font-bold text-[#7C3AED] hover:underline" :to="{ path: '/login', query: route.query.redirect ? { redirect: route.query.redirect } : {} }">
              Đăng nhập ngay
            </router-link>
          </p>
        </div>

        <!-- 2. OTP Verification Screen -->
        <div v-else key="otp" class="space-y-4">
          <p class="text-xs text-slate-600 text-center">
            Mã OTP 6 số đã được gửi tới email <b class="text-slate-900">{{ form.email }}</b>.
          </p>

          <form class="space-y-4" @submit.prevent="handleVerifyOtp">
            <div class="flex justify-between gap-2 py-2" @paste="handleOtpPaste">
              <input 
                v-for="(digit, idx) in otpDigits" 
                :key="idx" 
                v-model="otpDigits[idx]" 
                ref="otpInputs" 
                type="text" 
                maxlength="1" 
                class="h-12 w-10 rounded-xl border border-slate-200 bg-slate-50 text-center font-mono text-xl font-bold text-slate-900 outline-none focus:border-[#7C3AED] focus:ring-2 focus:ring-purple-100"
                placeholder="•"
                @input="handleOtpInput(idx, $event)"
                @keydown="handleOtpKeyDown(idx, $event)"
              />
            </div>
            <span v-if="otpError" class="text-xs font-bold text-red-600 block text-center">{{ otpError }}</span>

            <button type="submit" :disabled="isVerifyingOtp" class="btn-primary w-full py-2.5 text-xs">
              {{ isVerifyingOtp ? 'Đang kích hoạt...' : 'Kích hoạt tài khoản' }}
            </button>

            <button type="button" class="btn-secondary w-full py-2 text-xs" :disabled="otpCountdown > 0" @click="handleResendOtp">
              {{ otpCountdown > 0 ? `Gửi lại mã mới (${otpCountdown}s)` : 'Gửi lại mã OTP' }}
            </button>

            <div class="text-center pt-2">
              <button type="button" class="text-xs font-bold text-[#7C3AED] hover:underline" @click="goBackToRegister">
                ← Sửa lại thông tin đăng ký
              </button>
            </div>

            <div v-if="successMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-bold text-emerald-700">{{ successMessage }}</div>
          </form>
        </div>
      </Transition>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { authApi } from '@/services/api'
import BrandLogo from '@/components/common/BrandLogo.vue'

const router = useRouter()
const route = useRoute()

const isPasswordVisible = ref(false)
const successMessage = ref('')
const isSubmitting = ref(false)
const isShaking = ref(false)

const form = reactive({ fullName: '', email: '', password: '', acceptTerms: true })
const errors = reactive({ fullName: '', email: '', password: '', acceptTerms: '' })
const touched = reactive({ fullName: false, email: false, password: false, acceptTerms: false })

// OTP State
const isOtpMode = ref(false)
const otpDigits = ref(['', '', '', '', '', ''])
const otpInputs = ref([])
const otpError = ref('')
const otpCountdown = ref(0)
let countdownInterval = null
const isVerifyingOtp = ref(false)

const passwordStrengthScore = computed(() => {
  const pwd = form.password
  if (!pwd) return 0
  let score = 0
  if (pwd.length >= 8) score++
  if (/[a-z]/.test(pwd) && /[A-Z]/.test(pwd)) score++
  if (/[0-9]/.test(pwd)) score++
  if (/[^A-Za-z0-9]/.test(pwd)) score++
  return score
})

const strengthPercentage = computed(() => {
  return (passwordStrengthScore.value / 4) * 100
})

const strengthLabel = computed(() => {
  switch (passwordStrengthScore.value) {
    case 1: return 'Yếu'
    case 2: return 'Trung bình'
    case 3: return 'Khá'
    case 4: return 'Mạnh'
    default: return ''
  }
})

const strengthBarClass = computed(() => {
  switch (passwordStrengthScore.value) {
    case 1: return 'bg-red-500'
    case 2: return 'bg-amber-500'
    case 3: return 'bg-blue-500'
    case 4: return 'bg-emerald-500'
    default: return 'bg-slate-200'
  }
})

const strengthTextClass = computed(() => {
  switch (passwordStrengthScore.value) {
    case 1: return 'text-red-600'
    case 2: return 'text-amber-600'
    case 3: return 'text-blue-600'
    case 4: return 'text-emerald-600'
    default: return 'text-slate-500'
  }
})

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
  if (field === 'fullName') {
    if (!form.fullName.trim()) {
      errors.fullName = 'Họ và tên không được để trống.'
    } else {
      errors.fullName = ''
    }
  } else if (field === 'email') {
    if (!form.email) {
      errors.email = 'Email không được để trống.'
    } else if (!/^\S+@\S+\.\S+$/.test(form.email)) {
      errors.email = 'Email chưa đúng định dạng.'
    } else {
      errors.email = ''
    }
  } else if (field === 'password') {
    if (!form.password) {
      errors.password = 'Mật khẩu không được để trống.'
    } else if (form.password.length < 8) {
      errors.password = 'Mật khẩu tối thiểu 8 ký tự.'
    } else {
      errors.password = ''
    }
  } else if (field === 'acceptTerms') {
    if (!form.acceptTerms) {
      errors.acceptTerms = 'Bạn cần đồng ý với điều khoản để tiếp tục.'
    } else {
      errors.acceptTerms = ''
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
  touched.fullName = true
  touched.email = true
  touched.password = true
  touched.acceptTerms = true
  validateField('fullName')
  validateField('email')
  validateField('password')
  validateField('acceptTerms')

  const isValid = !errors.fullName && !errors.email && !errors.password && !errors.acceptTerms
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

const startOtpCountdown = () => {
  otpCountdown.value = 60
  if (countdownInterval) clearInterval(countdownInterval)
  countdownInterval = setInterval(() => {
    if (otpCountdown.value > 0) {
      otpCountdown.value--
    } else {
      clearInterval(countdownInterval)
    }
  }, 1000)
}

const handleRegister = async () => {
  successMessage.value = ''
  if (!validateAll()) return

  isSubmitting.value = true
  try {
    const res = await authApi.register({
      name: form.fullName.trim(),
      email: form.email.trim(),
      password: form.password,
    })

    successMessage.value = res.message || 'Mã OTP 6 số đã được gửi tới email của bạn.'
    isOtpMode.value = true
    startOtpCountdown()
    nextTick(() => {
      otpInputs.value[0]?.focus()
    })
  } catch (error) {
    if (error.message?.toLowerCase().includes('email')) {
      errors.email = error.message
    } else {
      errors.password = error.message
    }
    triggerShakeAnimation()
  } finally {
    isSubmitting.value = false
  }
}

const handleOtpInput = (index, event) => {
  const value = event.target.value
  if (value && index < 5) {
    nextTick(() => {
      otpInputs.value[index + 1]?.focus()
    })
  }
}

const handleOtpKeyDown = (index, event) => {
  if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
    nextTick(() => {
      otpInputs.value[index - 1]?.focus()
    })
  }
}

const handleOtpPaste = (event) => {
  event.preventDefault()
  const pastedData = event.clipboardData.getData('text').trim()
  if (/^\d{6}$/.test(pastedData)) {
    for (let i = 0; i < 6; i++) {
      otpDigits.value[i] = pastedData[i]
    }
    nextTick(() => {
      otpInputs.value[5]?.focus()
    })
  }
}

const handleVerifyOtp = async () => {
  otpError.value = ''
  successMessage.value = ''
  const code = otpDigits.value.join('')

  if (code.length < 6) {
    otpError.value = 'Vui lòng nhập đủ 6 chữ số OTP.'
    return
  }

  isVerifyingOtp.value = true
  try {
    const res = await authApi.verifyOtp({
      email: form.email.trim(),
      otp: code,
    })

    successMessage.value = res.message || 'Xác thực thành công! Đang chuyển hướng...'
    setTimeout(() => {
      router.push({
        path: '/login',
        query: { email: form.email.trim(), verified: 'true' },
        state: { email: form.email.trim(), password: form.password },
      })
    }, 500)
  } catch (error) {
    otpError.value = error.message || 'Mã OTP không chính xác.'
  } finally {
    isVerifyingOtp.value = false
  }
}

const handleResendOtp = async () => {
  if (otpCountdown.value > 0) return
  try {
    await authApi.resendOtp({ email: form.email.trim() })
    startOtpCountdown()
    successMessage.value = 'Đã gửi lại mã OTP mới tới email của bạn.'
  } catch (error) {
    otpError.value = error.message || 'Không thể gửi lại mã OTP.'
  }
}

const goBackToRegister = () => {
  isOtpMode.value = false
  otpDigits.value = ['', '', '', '', '', '']
  otpError.value = ''
}

onMounted(() => {
  const state = history.state
  if (state && state.email) {
    form.email = state.email
  }
})

onUnmounted(() => {
  if (countdownInterval) clearInterval(countdownInterval)
})
</script>
