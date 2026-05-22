<template>
  <section class="max-w-[600px] mx-auto py-16 px-4">
    
    <!-- Glassmorphic Card Container -->
    <div class="relative overflow-hidden rounded-[2.5rem] border border-[var(--border)] bg-[var(--surface)] p-8 md:p-10 text-center shadow-[var(--shadow-card)] backdrop-blur-3xl">
      <div class="pointer-events-none absolute -right-20 -top-20 h-44 w-44 rounded-full bg-[var(--primary)]/10 blur-3xl"></div>
      
      <!-- Loading State -->
      <div v-if="isLoading" class="flex flex-col items-center justify-center gap-4 py-8">
        <div class="h-14 w-14 animate-spin rounded-full border-[5px] border-[var(--border)] border-t-[var(--primary)] shadow-md"></div>
        <h2 class="text-2xl font-black text-[var(--text)] mt-3">{{ $t('user_views.PaymentResult.loading_title') }}</h2>
        <p class="text-sm font-semibold text-[var(--muted)]">{{ $t('user_views.PaymentResult.loading_description') }}</p>
      </div>

      <!-- Success State -->
      <div v-else-if="isSuccess" class="grid gap-6 py-4">
        <!-- Success Animated Circle -->
        <div class="mx-auto h-20 w-20 rounded-full bg-emerald-500/10 border border-emerald-500/25 flex items-center justify-center text-4xl text-emerald-400 shadow-[0_0_30px_rgba(16,185,129,0.2)] animate-pulse">
          ✓
        </div>

        <div class="grid gap-2">
          <span class="text-xs font-black uppercase tracking-[0.2em] text-emerald-400">{{ $t('user_views.PaymentResult.success_badge') }}</span>
          <h2 class="text-3xl md:text-4xl font-black text-[var(--text)] tracking-tight">{{ $t('user_views.PaymentResult.success_title') }}</h2>
          <p class="text-sm font-semibold text-[var(--muted)] leading-relaxed">
            {{ $t('user_views.PaymentResult.success_description') }}
          </p>
        </div>

        <!-- Receipt Box -->
        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-left text-sm font-semibold grid gap-3">
          <div class="flex items-center justify-between">
            <span class="text-[var(--muted)]">{{ $t('user_views.PaymentResult.order_code_label') }}</span>
            <span class="font-mono text-xs text-[var(--text)]">{{ resultData.order_code }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[var(--muted)]">{{ $t('user_views.PaymentResult.amount_label') }}</span>
            <span class="font-black text-[var(--accent)]">{{ formatPrice(resultData.amount) }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[var(--muted)]">{{ $t('user_views.PaymentResult.account_type_label') }}</span>
            <span class="inline-flex items-center gap-1 rounded bg-amber-500/10 text-amber-400 px-2 py-0.5 text-xs font-black uppercase border border-amber-500/20">
              👑 {{ resultData.user?.role }}
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[var(--muted)]">{{ $t('user_views.PaymentResult.ai_quota_label') }}</span>
            <span class="font-black text-[var(--primary)]">{{ $t('user_views.PaymentResult.ai_quota_value', { quota: resultData.user?.ai_quota_remaining }) }}</span>
          </div>
          <div class="flex items-center justify-between" v-if="resultData.user?.vip_expires_at">
            <span class="text-[var(--muted)]">{{ $t('user_views.PaymentResult.vip_expiry_label') }}</span>
            <span class="text-[var(--text)]">{{ formatDate(resultData.user?.vip_expires_at) }}</span>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
          <router-link 
            to="/" 
            class="h-12 flex items-center justify-center font-black rounded-full border border-[var(--border-strong)] bg-[var(--surface-soft)] text-[var(--text)] transition hover:bg-[var(--chip-active)] active:scale-[0.98]"
          >
            {{ $t('user_views.PaymentResult.home_button') }}
          </router-link>
          
          <router-link 
            to="/dashboard/questions/ai" 
            class="h-12 flex items-center justify-center font-black rounded-full bg-gradient-to-r from-[var(--primary)] to-[var(--accent)] text-white shadow-[0_16px_36px_rgba(155,44,255,0.25)] transition hover:-translate-y-0.5 active:scale-[0.98]"
          >
            {{ $t('user_views.PaymentResult.try_ai_button') }}
          </router-link>
        </div>
      </div>

      <!-- Failure State -->
      <div v-else class="grid gap-6 py-4">
        <!-- Error Circle -->
        <div class="mx-auto h-20 w-20 rounded-full bg-rose-500/10 border border-rose-500/25 flex items-center justify-center text-4xl text-rose-400 shadow-[0_0_30px_rgba(244,63,94,0.15)] animate-bounce">
          ✕
        </div>

        <div class="grid gap-2">
          <span class="text-xs font-black uppercase tracking-[0.2em] text-rose-400">{{ $t('user_views.PaymentResult.failure_badge') }}</span>
          <h2 class="text-3xl font-black text-[var(--text)] tracking-tight">{{ $t('user_views.PaymentResult.failure_title') }}</h2>
          <p class="text-sm font-semibold text-[var(--muted)] leading-relaxed">
            {{ $t('user_views.PaymentResult.failure_description') }}
          </p>
        </div>

        <!-- Error Alert Message -->
        <div class="rounded-2xl border border-rose-500/25 bg-rose-500/10 p-4 text-sm font-bold text-rose-400 text-left">
          {{ $t('user_views.PaymentResult.error_detail', { message: errorMsg || $t('user_views.PaymentResult.default_error') }) }}
        </div>

        <!-- Action Buttons -->
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
          <router-link 
            to="/" 
            class="h-12 flex items-center justify-center font-black rounded-full border border-[var(--border-strong)] bg-[var(--surface-soft)] text-[var(--text)] transition hover:bg-[var(--chip-active)] active:scale-[0.98]"
          >
            {{ $t('user_views.PaymentResult.back_home_button') }}
          </router-link>
          
          <router-link 
            to="/upgrade" 
            class="h-12 flex items-center justify-center font-black rounded-full bg-rose-500 text-white shadow-[0_16px_36px_rgba(244,63,94,0.2)] transition hover:-translate-y-0.5 active:scale-[0.98]"
          >
            {{ $t('user_views.PaymentResult.retry_payment_button') }}
          </router-link>
        </div>
      </div>

    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { currentUserStorage, paymentsApi } from '@/services/api'

const route = useRoute()
const { t } = useI18n()

const isLoading = ref(true)
const isSuccess = ref(false)
const errorMsg = ref('')
const resultData = ref({})

onMounted(() => {
  verifyTransaction()
})

const verifyTransaction = async () => {
  // Capture all redirect query parameters sent from MoMo sandbox
  const queryParams = route.query
  
  if (!queryParams.orderId || !queryParams.signature) {
    isLoading.value = false
    isSuccess.value = false
    errorMsg.value = t('user_views.PaymentResult.errors.missing_verification')
    return
  }

  try {
    // 1. Gọi backend verify signature & sync trạng thái
    const res = await paymentsApi.callback(queryParams)
    
    if (res.success && res.status === 'success') {
      isSuccess.value = true
      resultData.value = res
      
      // 2. Tự động đồng bộ tài khoản mới trong localStorage của Client
      // Điều này tự động thay đổi Avatar, Quota và Role VIP ngay lập tức mà không cần reload!
      if (res.user) {
        currentUserStorage.set(res.user)
      }
    } else {
      isSuccess.value = false
      errorMsg.value = t('user_views.PaymentResult.errors.status_finished', { status: res.status || 'unknown' })
    }
  } catch (error) {
    isSuccess.value = false
    errorMsg.value = error.message || t('user_views.PaymentResult.errors.signature_failed')
  } finally {
    isLoading.value = false
  }
}

// Helpers
const formatPrice = (value) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value)
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('vi-VN', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>
