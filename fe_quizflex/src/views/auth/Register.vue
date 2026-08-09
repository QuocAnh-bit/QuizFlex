<template>
  <div class="relative grid min-h-[700px] overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface-strong)] shadow-[var(--shadow-soft)] lg:grid-cols-[0.92fr_1.08fr]">
    <!-- Aside Sidebar (Trang trí trái) -->
    <aside class="relative hidden min-h-[700px] overflow-hidden p-8 text-white lg:flex lg:flex-col lg:justify-between">
      <div class="absolute inset-0 bg-gradient-to-br from-[var(--primary)]/90 via-[var(--primary-2)]/75 to-[var(--accent)]/55"></div>
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.28),transparent_28%),linear-gradient(to_bottom,rgba(0,0,0,0.03),rgba(0,0,0,0.58))]"></div>
      <div class="relative z-10"><BrandLogo to="/" size="lg" /></div>
      <div class="relative z-10">
        <div class="mb-6 inline-flex rounded-full border border-white/25 bg-white/15 px-4 py-2 text-sm font-black backdrop-blur-xl">QuizFlex Account</div>
        <h2 class="max-w-md text-5xl font-black leading-[0.95] tracking-[-0.075em]">Tạo quiz thông minh hơn.</h2>
        <p class="mt-5 max-w-md text-base font-medium leading-8 text-white/75">Tạo đề bằng tay, AI hoặc OCR. Chơi cá nhân hoặc realtime theo phòng.</p>
      </div>
      <div class="relative z-10 grid grid-cols-2 gap-3">
        <div class="rounded-[1.25rem] border border-white/15 bg-white/10 p-4 backdrop-blur-xl">
          <p class="text-xs font-bold text-white/60">Quiz mẫu</p>
          <p class="mt-1 text-2xl font-black">50+</p>
        </div>
        <div class="rounded-[1.25rem] border border-white/15 bg-white/10 p-4 backdrop-blur-xl">
          <p class="text-xs font-bold text-white/60">Chế độ</p>
          <p class="mt-1 text-2xl font-black">3</p>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <section class="relative grid content-center px-5 py-8 sm:px-8 lg:px-14">
      <div class="mx-auto w-full max-w-[540px]">
        <div class="mb-8 lg:hidden"><BrandLogo to="/" size="lg" /></div>
        
        <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl sm:p-8" :class="{ 'animate-shake': isShaking }">
          <div class="pointer-events-none absolute -right-20 -top-20 h-52 w-52 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
          
          <div class="relative z-10">
            <!-- Hiệu ứng chuyển động mượt mà (Fade Slide Transition) -->
            <Transition name="fade-slide" mode="out-in">
              <!-- PHẦN 1: MÀN HÌNH ĐĂNG KÝ THÔNG TIN -->
              <div v-if="!isOtpMode" key="register">
                <div class="mb-6 inline-flex rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-4 py-2 text-sm font-black text-[var(--primary)]">Tạo tài khoản mới</div>
                <h1 class="text-4xl font-black leading-none tracking-[-0.065em] text-[var(--text)]">Bắt đầu với QuizFlex</h1>
                <p class="mt-3 text-sm font-medium leading-7 text-[var(--muted)]">Đăng ký tài khoản và bắt đầu xây dựng kho quiz của bạn.</p>
                
                <form class="mt-8 grid gap-5" @submit.prevent="handleRegister" novalidate>
                  <div class="rounded-[1.4rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4">
                    <div class="flex items-start gap-3">
                      <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-gradient-to-br from-[var(--primary)] to-[var(--primary-2)] text-xs font-black text-white">U</span>
                      <div>
                        <b class="block text-sm font-black text-[var(--text)]">Tài khoản người dùng</b>
                        <span class="mt-2 block text-xs font-semibold leading-5 text-[var(--muted)]">Sau khi đăng ký, bạn có thể dùng thử hoặc nâng cấp gói dịch vụ.</span>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Trường Họ và tên với Event-driven Validation -->
                  <FieldInput 
                    label="Họ và tên" 
                    v-model="form.fullName" 
                    :error="errors.fullName" 
                    :is-touched="touched.fullName"
                    :is-valid="!errors.fullName && !!form.fullName.trim()"
                    placeholder="Nguyễn Văn A" 
                    prefix="✦" 
                    autocomplete="name"
                    @blur="handleBlur('fullName')"
                    @input="handleInput('fullName')"
                  />
                  
                  <!-- Trường Email với Event-driven Validation & Smart Hint -->
                  <div>
                    <FieldInput 
                      label="Email" 
                      v-model="form.email" 
                      :error="errors.email" 
                      :is-touched="touched.email"
                      :is-valid="!errors.email && !!form.email && /^\S+@\S+\.\S+$/.test(form.email)"
                      type="email" 
                      placeholder="you@example.com" 
                      prefix="✦" 
                      autocomplete="email"
                      @blur="handleBlur('email')"
                      @input="handleInput('email')"
                    />
                    <div v-if="emailTypoHint" class="mt-1 text-xs font-bold text-amber-500 flex items-center gap-1 cursor-pointer" @click="applyEmailHint">
                      <span>💡 Gợi ý: Có phải bạn muốn gõ <u>{{ emailTypoHint }}</u>? (Nhấn để áp dụng)</span>
                    </div>
                  </div>
                  
                  <!-- Trường Mật khẩu với Password Strength Meter -->
                  <div class="grid gap-2 text-sm font-black text-[var(--text)]">
                    <div class="flex items-center justify-between">
                      <span>Mật khẩu</span>
                      <span v-if="form.password" class="text-xs font-bold transition-colors" :class="strengthTextClass">
                        {{ strengthLabel }}
                      </span>
                    </div>
                    <div class="flex items-center gap-3 rounded-2xl border bg-[var(--input-bg)] px-4 py-3 transition focus-within:border-[var(--border-strong)]" :class="touched.password ? (errors.password ? 'border-rose-500/50' : 'border-emerald-500/50') : 'border-[var(--border)]'">
                      <span class="text-sm font-black text-[var(--primary)]">#</span>
                      <input 
                        v-model="form.password" 
                        class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]" 
                        :type="isPasswordVisible ? 'text' : 'password'" 
                        placeholder="Ít nhất 8 ký tự" 
                        autocomplete="new-password"
                        @blur="handleBlur('password')"
                        @input="handleInput('password')"
                      />
                      <button type="button" class="text-xs font-black text-[var(--primary)] shrink-0" @click="isPasswordVisible = !isPasswordVisible">
                        {{ isPasswordVisible ? 'Ẩn' : 'Hiện' }}
                      </button>
                      <span v-if="touched.password" class="shrink-0 text-xs font-black">
                        <span v-if="!errors.password && form.password.length >= 8" class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">✓</span>
                        <span v-else-if="errors.password" class="flex h-5 w-5 items-center justify-center rounded-full bg-rose-500/20 text-rose-400">!</span>
                      </span>
                    </div>
                    
                    <!-- Thanh đo độ mạnh mật khẩu (Password Strength Meter Bar) -->
                    <div v-if="form.password" class="mt-1 space-y-2">
                      <div class="h-1.5 w-full overflow-hidden rounded-full bg-[var(--border)]">
                        <div class="h-full transition-all duration-300 rounded-full" :style="{ width: strengthPercentage + '%' }" :class="strengthBarClass"></div>
                      </div>
                      
                      <!-- Checklist tiêu chuẩn mật khẩu -->
                      <div class="grid grid-cols-1 sm:grid-cols-3 gap-1.5 pt-1 text-[11px] font-bold">
                        <div class="flex items-center gap-1.5" :class="passwordCriteria.minLength ? 'text-emerald-400' : 'text-[var(--muted)]'">
                          <span>{{ passwordCriteria.minLength ? '✓' : '•' }}</span>
                          <span>Tối thiểu 8 ký tự</span>
                        </div>
                        <div class="flex items-center gap-1.5" :class="passwordCriteria.hasLetterAndNumber ? 'text-emerald-400' : 'text-[var(--muted)]'">
                          <span>{{ passwordCriteria.hasLetterAndNumber ? '✓' : '•' }}</span>
                          <span>Chữ cái & Chữ số</span>
                        </div>
                        <div class="flex items-center gap-1.5" :class="passwordCriteria.hasSpecialOrUpper ? 'text-emerald-400' : 'text-[var(--muted)]'">
                          <span>{{ passwordCriteria.hasSpecialOrUpper ? '✓' : '•' }}</span>
                          <span>Hoa / Ký tự đặc biệt</span>
                        </div>
                      </div>
                    </div>

                    <span v-if="errors.password" class="text-xs font-bold text-rose-400">{{ errors.password }}</span>
                  </div>
                  
                  <!-- Checkbox điều khoản -->
                  <label class="flex items-start gap-3 text-sm font-semibold text-[var(--muted)]">
                    <input 
                      v-model="form.acceptTerms" 
                      type="checkbox" 
                      class="mt-1 h-4 w-4 accent-[var(--primary)]"
                      @change="handleBlur('acceptTerms')" 
                    />
                    Tôi đồng ý với điều khoản sử dụng và chính sách bảo mật.
                  </label>
                  <span v-if="errors.acceptTerms" class="text-xs font-bold text-rose-400">{{ errors.acceptTerms }}</span>
                  
                  <button type="submit" :disabled="isSubmitting" class="btn-primary w-full flex items-center justify-center gap-2">
                    <span v-if="isSubmitting" class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                    {{ isSubmitting ? 'Đang tạo tài khoản...' : 'Tạo tài khoản' }}
                  </button>
                  <div v-if="successMessage" class="rounded-2xl border border-emerald-500/25 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-400">{{ successMessage }}</div>
                </form>
                
                <p class="mt-7 text-center text-sm font-semibold text-[var(--muted)]">Đã có tài khoản? <router-link class="font-black text-[var(--primary)]" :to="{ path: '/login', query: route.query.redirect ? { redirect: route.query.redirect } : {} }">Đăng nhập</router-link></p>
              </div>

              <!-- PHẦN 2: MÀN HÌNH NHẬP MÃ XÁC THỰC OTP -->
              <div v-else key="otp">
                <div class="mb-6 inline-flex rounded-full border border-[var(--border-strong)] bg-sky-500/10 px-4 py-2 text-sm font-black text-sky-400">Xác thực tài khoản</div>
                <h1 class="text-4xl font-black leading-none tracking-[-0.065em] text-[var(--text)]">Nhập mã xác thực</h1>
                <p class="mt-3 text-sm font-medium leading-7 text-[var(--muted)]">
                  Mã OTP 6 số đã gửi tới email <b class="text-[var(--text)]">{{ form.email }}</b>.<br>
                  <span class="text-xs text-[var(--muted)]">(Vui lòng kiểm tra hộp thư hoặc file <b>laravel.log</b> ở máy local của bạn)</span>
                </p>
                
                <form class="mt-8 grid gap-6" @submit.prevent="handleVerifyOtp">
                  <div class="grid gap-2">
                    <label class="text-sm font-black text-[var(--text)]">Mã xác thực OTP</label>
                    
                    <div class="flex justify-between gap-2 py-2" @paste="handleOtpPaste">
                      <input 
                        v-for="(digit, idx) in otpDigits" 
                        :key="idx" 
                        v-model="otpDigits[idx]" 
                        ref="otpInputs" 
                        type="text" 
                        maxlength="1" 
                        class="h-14 w-12 rounded-2xl border border-[var(--border)] bg-[var(--input-bg)] text-center text-2xl font-black text-[var(--primary)] outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 placeholder:text-[var(--muted)]/30"
                        placeholder="-"
                        @input="handleOtpInput(idx, $event)"
                        @keydown="handleOtpKeyDown(idx, $event)"
                      />
                    </div>
                    <span v-if="otpError" class="text-xs font-bold text-rose-400 mt-1">{{ otpError }}</span>
                  </div>

                  <button type="submit" :disabled="isVerifyingOtp" class="btn-primary w-full py-4 text-base font-bold shadow-[var(--shadow-soft)] flex items-center justify-center gap-2">
                    <span v-if="isVerifyingOtp" class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                    {{ isVerifyingOtp ? 'Đang kích hoạt...' : 'Kích hoạt tài khoản' }}
                  </button>
                  
                  <button type="button" class="btn-secondary w-full py-4 text-sm font-semibold" :disabled="otpCountdown > 0" @click="handleResendOtp">
                    {{ otpCountdown > 0 ? `Gửi lại mã mới (${otpCountdown}s)` : 'Gửi lại mã OTP' }}
                  </button>
                  
                  <button type="button" class="mt-2 text-center text-xs font-bold text-[var(--primary)] hover:underline" @click="goBackToRegister">
                    ← Quay về sửa email đăng ký
                  </button>
                  
                  <div v-if="successMessage" class="rounded-2xl border border-emerald-500/25 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-400">{{ successMessage }}</div>
                </form>
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { reactive, ref, computed, defineComponent, h, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { authApi } from '@/services/api'
import BrandLogo from '@/components/common/BrandLogo.vue'

const router = useRouter()
const route = useRoute()

// Reusable Event-driven FieldInput Component
const FieldInput = defineComponent({ 
  props: { 
    modelValue: String, 
    label: String, 
    error: String, 
    placeholder: String, 
    prefix: String, 
    type: { type: String, default: 'text' }, 
    autocomplete: String,
    isTouched: Boolean,
    isValid: Boolean
  }, 
  emits: ['update:modelValue', 'blur', 'input'], 
  setup(props, { emit }) { 
    return () => h('label', { class: 'grid gap-2 text-sm font-black text-[var(--text)]' }, [
      props.label, 
      h('div', { 
        class: [
          'flex items-center gap-3 rounded-2xl border bg-[var(--input-bg)] px-4 py-3 transition focus-within:border-[var(--border-strong)]', 
          props.isTouched ? (props.error ? 'border-rose-500/50' : 'border-emerald-500/50') : 'border-[var(--border)]'
        ] 
      }, [
        h('span', { class: 'text-sm font-black text-[var(--primary)]' }, props.prefix), 
        h('input', { 
          class: 'w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]', 
          value: props.modelValue, 
          type: props.type, 
          placeholder: props.placeholder, 
          autocomplete: props.autocomplete, 
          onInput: (event) => {
            emit('update:modelValue', event.target.value)
            emit('input', event)
          },
          onBlur: (event) => emit('blur', event)
        }),
        // Event-driven Status Indicator Icon
        props.isTouched ? h('span', { class: 'shrink-0 text-xs font-black' }, [
          props.isValid 
            ? h('span', { class: 'flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400' }, '✓')
            : (props.error ? h('span', { class: 'flex h-5 w-5 items-center justify-center rounded-full bg-rose-500/20 text-rose-400' }, '!') : null)
        ]) : null
      ]), 
      props.error ? h('span', { class: 'text-xs font-bold text-rose-400' }, props.error) : null
    ]) 
  } 
})

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

// Password Criteria & Strength Computation
const passwordCriteria = computed(() => {
  const pwd = form.password
  return {
    minLength: pwd.length >= 8,
    hasLetterAndNumber: /[a-zA-Z]/.test(pwd) && /[0-9]/.test(pwd),
    hasSpecialOrUpper: /[A-Z]/.test(pwd) || /[^A-Za-z0-9]/.test(pwd)
  }
})

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
    case 3: return 'Mạnh'
    case 4: return 'Tối thượng 🔥'
    default: return ''
  }
})

const strengthTextClass = computed(() => {
  switch (passwordStrengthScore.value) {
    case 1: return 'text-rose-400'
    case 2: return 'text-amber-400'
    case 3: return 'text-emerald-400'
    case 4: return 'text-indigo-400'
    default: return 'text-[var(--muted)]'
  }
})

const strengthBarClass = computed(() => {
  switch (passwordStrengthScore.value) {
    case 1: return 'bg-rose-500'
    case 2: return 'bg-amber-500'
    case 3: return 'bg-emerald-500'
    case 4: return 'bg-gradient-to-r from-emerald-500 via-indigo-500 to-purple-500'
    default: return 'bg-[var(--border)]'
  }
})

// Smart Email Typo Hint
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

// Event-driven Validation Logic
const validateField = (field) => {
  if (field === 'fullName') {
    errors.fullName = form.fullName.trim() ? '' : 'Vui lòng nhập họ tên.'
  } else if (field === 'email') {
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
    } else if (form.password.length < 8) {
      errors.password = 'Mật khẩu tối thiểu 8 ký tự.'
    } else {
      errors.password = ''
    }
  } else if (field === 'acceptTerms') {
    errors.acceptTerms = form.acceptTerms ? '' : 'Bạn cần đồng ý với điều khoản sử dụng.'
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

onMounted(() => {
  const state = history.state
  if (state && state.email) {
    form.email = state.email
  }
  const params = new URLSearchParams(window.location.search)
  if (params.get('email')) {
    form.email = params.get('email')
  }
})

onUnmounted(() => {
  if (countdownInterval) clearInterval(countdownInterval)
})

const startCountdown = () => {
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
    await authApi.register({
      name: form.fullName.trim(),
      email: form.email.trim(),
      password: form.password,
      role: 'FREE',
    })
    successMessage.value = 'Đăng ký thành công! Vui lòng nhập mã OTP để kích hoạt.'
    
    setTimeout(async () => {
      isOtpMode.value = true
      successMessage.value = ''
      startCountdown()
      await nextTick()
      if (otpInputs.value[0]) otpInputs.value[0].focus()
    }, 400)

  } catch (error) {
    successMessage.value = ''
    errors.email = error.message
    triggerShakeAnimation()
  } finally {
    isSubmitting.value = false
  }
}

const handleOtpInput = (index, event) => {
  const val = event.target.value.replace(/[^0-9]/g, '')
  otpDigits.value[index] = val
  if (val && index < 5) {
    otpInputs.value[index + 1].focus()
  }
}

const handleOtpKeyDown = (index, event) => {
  if (event.key === 'Backspace') {
    if (!otpDigits.value[index] && index > 0) {
      otpDigits.value[index - 1] = ''
      otpInputs.value[index - 1].focus()
    } else {
      otpDigits.value[index] = ''
    }
  }
}

const handleOtpPaste = (event) => {
  event.preventDefault()
  const pasteText = event.clipboardData.getData('text').trim().replace(/[^0-9]/g, '')
  if (pasteText.length === 6) {
    for (let i = 0; i < 6; i++) {
      otpDigits.value[i] = pasteText[i]
    }
    otpInputs.value[5].focus()
  }
}

const handleVerifyOtp = async () => {
  otpError.value = ''
  successMessage.value = ''
  const fullOtp = otpDigits.value.join('').trim()
  if (fullOtp.length !== 6) {
    otpError.value = 'Mã OTP yêu cầu đầy đủ 6 chữ số.'
    return
  }

  isVerifyingOtp.value = true
  try {
    await authApi.verifyOtp({
      email: form.email,
      otp: fullOtp,
    })

    successMessage.value = 'Kích hoạt tài khoản thành công! Đang chuyển hướng sang trang đăng nhập...'
    
    setTimeout(() => {
      router.push({
        path: '/login',
        query: route.query.redirect ? { redirect: route.query.redirect } : {},
        state: { email: form.email, password: form.password }
      })
    }, 400)

  } catch (error) {
    otpError.value = error.message
  } finally {
    isVerifyingOtp.value = false
  }
}

const handleResendOtp = async () => {
  if (otpCountdown.value > 0) return
  otpError.value = ''
  successMessage.value = ''
  otpDigits.value = ['', '', '', '', '', '']

  try {
    await authApi.resendOtp({
      email: form.email
    })
    successMessage.value = 'Một mã OTP mới đã được gửi tới email của bạn.'
    startCountdown()
    await nextTick()
    if (otpInputs.value[0]) otpInputs.value[0].focus()
  } catch (error) {
    otpError.value = error.message
  }
}

const goBackToRegister = () => {
  isOtpMode.value = false
  otpDigits.value = ['', '', '', '', '', '']
  otpError.value = ''
  successMessage.value = ''
  if (countdownInterval) clearInterval(countdownInterval)
  otpCountdown.value = 0
}
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(16px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-16px);
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20%, 60% { transform: translateX(-6px); }
  40%, 80% { transform: translateX(6px); }
}

.animate-shake {
  animation: shake 0.4s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
}
</style>
