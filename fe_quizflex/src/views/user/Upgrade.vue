<template>
  <section class="max-w-[1280px] mx-auto py-10 px-4 grid gap-10">
    
    <!-- Hero Banner with Neon Glow -->
    <div class="relative overflow-hidden rounded-[2.5rem] border border-[var(--border)] bg-[var(--surface)]/60 p-8 md:p-12 text-center shadow-[var(--shadow-card)] backdrop-blur-3xl">
      <div class="pointer-events-none absolute left-1/2 top-0 h-96 w-96 -translate-x-1/2 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="pointer-events-none absolute right-10 bottom-0 h-48 w-48 rounded-full bg-[var(--accent)]/10 blur-3xl"></div>
      
      <div class="relative z-10 max-w-3xl mx-auto">
        <span class="inline-flex items-center gap-1.5 rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-4 py-1.5 text-xs font-black uppercase tracking-wider text-[var(--primary)] shadow-[0_4px_12px_rgba(155,44,255,0.12)]">
          {{ $t('user_views.Upgrade.hero_badge') }}
        </span>
        <h1 class="mt-5 text-4xl md:text-6xl font-black tracking-tight text-[var(--text)] leading-none">
          {{ $t('user_views.Upgrade.hero_title_prefix') }} <span class="bg-gradient-to-r from-[var(--primary)] to-[var(--accent)] bg-clip-text text-transparent">{{ $t('user_views.Upgrade.hero_title_highlight') }}</span>
        </h1>
        <p class="mt-4 text-base leading-relaxed text-[var(--muted)]">
          {{ $t('user_views.Upgrade.hero_description') }}
        </p>
      </div>
    </div>

    <!-- Pricing Cards Grid -->
    <div class="grid gap-6 md:grid-cols-3">
      <article 
        v-for="plan in plans" 
        :key="plan.id" 
        class="relative overflow-hidden rounded-[2.2rem] border p-8 shadow-[var(--shadow-card)] backdrop-blur-2xl transition duration-300 flex flex-col justify-between"
        :class="plan.popular 
          ? 'border-[var(--primary)]/50 bg-[var(--surface-strong)]/80 scale-[1.03] shadow-[0_24px_50px_rgba(155,44,255,0.15)] ring-1 ring-[var(--primary)]/30' 
          : 'border-[var(--border)] bg-[var(--surface)]/50 hover:border-[var(--border-strong)] hover:shadow-[var(--shadow-soft)]'
        "
      >
        <!-- Popular Badge -->
        <span 
          v-if="plan.popular" 
          class="absolute top-4 right-4 bg-gradient-to-r from-[var(--primary)] to-[var(--accent)] text-white text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full shadow-md"
        >
          {{ $t('user_views.Upgrade.popular_badge') }}
        </span>

        <div>
          <!-- Plan Header -->
          <div class="flex items-center gap-3">
            <span class="text-3xl">{{ plan.icon }}</span>
            <div>
              <p class="font-black text-xl text-[var(--text)]">{{ plan.name }}</p>
              <p class="text-xs font-semibold text-[var(--muted)]">{{ plan.period }}</p>
            </div>
          </div>

          <!-- Pricing -->
          <div class="mt-6 flex items-baseline gap-1">
            <h2 class="text-4xl font-black tracking-tight text-[var(--text)]">{{ plan.priceLabel }}</h2>
            <span v-if="plan.price > 0" class="text-sm font-semibold text-[var(--muted)]">{{ $t('user_views.Upgrade.per_plan') }}</span>
          </div>

          <!-- Benefits Description -->
          <div class="mt-4 pb-4 border-b border-[var(--border)] text-xs text-[var(--muted)] font-semibold">
            {{ plan.desc }}
          </div>

          <!-- Feature List -->
          <div class="mt-6 grid gap-3">
            <div 
              v-for="feature in plan.features" 
              :key="feature" 
              class="flex items-center gap-2.5 text-sm font-bold text-[var(--text)]"
            >
              <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[var(--primary)]/10 text-[var(--primary)] text-xs">✓</span>
              <span>{{ feature }}</span>
            </div>
          </div>
        </div>

        <!-- Action Button -->
        <div class="mt-8">
          <button 
            @click="openCheckout(plan)"
            class="w-full h-12 flex items-center justify-center font-black rounded-full transition duration-300 active:scale-[0.98]"
            :class="plan.popular
              ? 'bg-gradient-to-r from-[var(--primary)] to-[var(--accent)] text-white hover:shadow-[0_16px_36px_rgba(155,44,255,0.3)]'
              : 'border border-[var(--border-strong)] bg-[var(--surface-soft)] text-[var(--text)] hover:bg-[var(--chip-active)] hover:border-[var(--primary)]/50'
            "
          >
            {{ plan.btnText }}
          </button>
        </div>
      </article>
    </div>

    <!-- Transaction History (Show only when logged in) -->
    <div 
      v-if="currentUser" 
      class="overflow-hidden rounded-[2.2rem] border border-[var(--border)] bg-[var(--surface)]/40 p-6 md:p-8 shadow-[var(--shadow-card)] backdrop-blur-2xl"
    >
      <h3 class="text-2xl font-black text-[var(--text)] flex items-center gap-2">
        <span>🕒</span> {{ $t('user_views.Upgrade.history_title') }}
      </h3>
      <p class="text-sm text-[var(--muted)] mt-1 font-semibold">{{ $t('user_views.Upgrade.history_description') }}</p>

      <div class="mt-6 overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm font-semibold">
          <thead>
            <tr class="border-b border-[var(--border)] text-[var(--muted)]">
              <th class="pb-3 pr-4 font-black">{{ $t('user_views.Upgrade.table.order_code') }}</th>
              <th class="pb-3 px-4 font-black">{{ $t('user_views.Upgrade.table.plan') }}</th>
              <th class="pb-3 px-4 font-black">{{ $t('user_views.Upgrade.table.provider') }}</th>
              <th class="pb-3 px-4 font-black">{{ $t('user_views.Upgrade.table.amount') }}</th>
              <th class="pb-3 px-4 font-black">{{ $t('user_views.Upgrade.table.status') }}</th>
              <th class="pb-3 pl-4 font-black">{{ $t('user_views.Upgrade.table.created_at') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="item in historyList" 
              :key="item.id" 
              class="border-b border-[var(--border)]/50 hover:bg-[var(--surface-soft)]/20 transition"
            >
              <td class="py-4 pr-4 font-mono text-xs text-[var(--text)]">{{ item.order_code }}</td>
              <td class="py-4 px-4 text-[var(--text)]">{{ item.plan_name || getPlanNameByAmount(item.amount) }}</td>
              <td class="py-4 px-4 text-xs font-bold uppercase text-[var(--muted)]">
                <span class="inline-flex items-center gap-1 rounded bg-fuchsia-500/10 text-fuchsia-400 px-2 py-0.5" v-if="item.provider === 'momo'">
                  {{ $t('user_views.Upgrade.provider_momo') }}
                </span>
                <span class="inline-flex items-center gap-1 rounded bg-blue-500/10 text-blue-400 px-2 py-0.5" v-else>
                  {{ $t('user_views.Upgrade.provider_vnpay') }}
                </span>
              </td>
              <td class="py-4 px-4 text-[var(--text)] font-black">{{ formatPrice(item.amount) }}</td>
              <td class="py-4 px-4 text-xs">
                <span 
                  class="inline-flex items-center gap-1 px-3 py-1 rounded-full font-black uppercase text-[10px]"
                  :class="getStatusBadgeClass(item.status)"
                >
                  {{ getStatusText(item.status) }}
                </span>
              </td>
              <td class="py-4 pl-4 text-[var(--muted)] text-xs">{{ formatDate(item.created_at) }}</td>
            </tr>
            <tr v-if="historyList.length === 0">
              <td colspan="6" class="py-10 text-center text-[var(--muted)] font-bold">
                {{ $t('user_views.Upgrade.no_transactions') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Checkout Modal Pop-up -->
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="isPaymentModalOpen" 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-md p-4"
        @click.self="closeCheckout"
      >
        <div class="relative overflow-hidden w-full max-w-[500px] rounded-[2.2rem] border border-[var(--border-strong)] bg-[var(--surface)] p-6 md:p-8 shadow-[0_24px_80px_rgba(0,0,0,0.4)] transition">
          <div class="pointer-events-none absolute -right-20 -top-20 h-44 w-44 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
          
          <!-- Close Button -->
          <button 
            @click="closeCheckout"
            class="absolute top-4 right-4 h-9 w-9 flex items-center justify-center rounded-full border border-[var(--border)] bg-[var(--surface-soft)] text-[var(--text)] transition hover:border-[var(--border-strong)] active:scale-95"
          >
            ✕
          </button>

          <!-- Modal Header -->
          <div class="relative z-10 text-center">
            <span class="text-4xl">{{ selectedPlan.icon }}</span>
            <h4 class="mt-3 text-2xl font-black text-[var(--text)]">{{ $t('user_views.Upgrade.modal_title') }}</h4>
            <p class="text-sm text-[var(--muted)] mt-1 font-semibold">{{ $t('user_views.Upgrade.modal_description') }}</p>
          </div>

          <!-- Order Summary Card -->
          <div class="mt-6 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 relative z-10 grid gap-2">
            <div class="flex items-center justify-between font-bold text-sm">
              <span class="text-[var(--muted)]">{{ $t('user_views.Upgrade.summary.plan_label') }}</span>
              <span class="text-[var(--text)]">{{ selectedPlan.name }}</span>
            </div>
            <div class="flex items-center justify-between font-bold text-sm">
              <span class="text-[var(--muted)]">{{ $t('user_views.Upgrade.summary.period_label') }}</span>
              <span class="text-[var(--text)]">{{ selectedPlan.period }}</span>
            </div>
            <div class="flex items-center justify-between font-bold text-sm">
              <span class="text-[var(--muted)]">{{ $t('user_views.Upgrade.summary.quota_label') }}</span>
              <span class="text-[var(--primary)]">{{ $t('user_views.Upgrade.summary.quota_value', { quota: selectedPlan.quota }) }}</span>
            </div>
            <div class="mt-2 pt-2 border-t border-[var(--border)] flex items-center justify-between font-black text-base">
              <span class="text-[var(--text)]">{{ $t('user_views.Upgrade.summary.total_label') }}</span>
              <span class="text-[var(--accent)] text-lg">{{ selectedPlan.priceLabel }}</span>
            </div>
          </div>

          <!-- Error Alert -->
          <div 
            v-if="errorMessage" 
            class="mt-4 rounded-xl border border-rose-500/25 bg-rose-500/10 p-3 text-xs font-bold text-rose-400"
          >
            {{ $t('user_views.Upgrade.error_prefix', { message: errorMessage }) }}
          </div>

          <!-- Payment Options -->
          <div class="mt-6 grid gap-3 relative z-10">
            <!-- MoMo Wallet -->
            <button 
              @click="handlePayment('momo')"
              :disabled="isProcessing"
              class="flex items-center justify-between rounded-[1.25rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4 transition duration-300 hover:border-fuchsia-500 hover:bg-fuchsia-500/5 active:scale-[0.98] group disabled:opacity-50"
            >
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 shrink-0 rounded-xl bg-[#a21960] flex items-center justify-center text-white text-xs font-black shadow-md">
                  MoMo
                </div>
                <div class="text-left">
                  <b class="block text-sm font-black text-[var(--text)] group-hover:text-fuchsia-400 transition">{{ $t('user_views.Upgrade.momo_title') }}</b>
                  <span class="text-[10px] font-semibold text-[var(--muted)]">{{ $t('user_views.Upgrade.momo_description') }}</span>
                </div>
              </div>
              <span class="text-xs font-black text-fuchsia-400">{{ $t('user_views.Upgrade.choose_button') }}</span>
            </button>

            <!-- VNPay (Inactive placeholder in checklist step, but designed beautifully) -->
            <button 
              disabled
              class="flex items-center justify-between rounded-[1.25rem] border border-[var(--border)] bg-[var(--surface-soft)]/40 p-4 opacity-50 cursor-not-allowed group"
            >
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 shrink-0 rounded-xl bg-blue-600 flex items-center justify-center text-white text-[10px] font-black shadow-md">
                  VNPay
                </div>
                <div class="text-left">
                  <b class="block text-sm font-black text-[var(--text)]">{{ $t('user_views.Upgrade.vnpay_title') }}</b>
                  <span class="text-[10px] font-semibold text-[var(--muted)]">{{ $t('user_views.Upgrade.vnpay_description') }}</span>
                </div>
              </div>
              <span class="text-[10px] font-black text-[var(--muted)]">{{ $t('user_views.Upgrade.coming_soon') }}</span>
            </button>
          </div>

          <!-- Loading Spinner overlay inside modal -->
          <div 
            v-if="isProcessing" 
            class="absolute inset-0 bg-[var(--surface)]/90 backdrop-blur-sm z-30 flex flex-col items-center justify-center gap-3"
          >
            <div class="h-10 w-10 animate-spin rounded-full border-4 border-[var(--border)] border-t-[var(--primary)]"></div>
            <p class="text-sm font-black text-[var(--text)]">{{ $t('user_views.Upgrade.processing_title') }}</p>
            <p class="text-xs font-bold text-[var(--muted)]">{{ $t('user_views.Upgrade.processing_description') }}</p>
          </div>

        </div>
      </div>
    </Transition>

  </section>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { currentUserStorage, paymentsApi } from '@/services/api'

const router = useRouter()
const route = useRoute()
const { t } = useI18n()
const currentUser = ref(currentUserStorage.get())
const historyList = ref([])

const isPaymentModalOpen = ref(false)
const selectedPlan = ref(null)
const isProcessing = ref(false)
const errorMessage = ref('')

const plans = computed(() => [
  {
    id: 'vip_1m',
    name: t('user_views.Upgrade.plans.vip_1m.name'),
    price: 50000,
    priceLabel: '50.000đ',
    period: t('user_views.Upgrade.plans.vip_1m.period'),
    icon: '⚡',
    desc: t('user_views.Upgrade.plans.vip_1m.desc'),
    quota: 100,
    btnText: t('user_views.Upgrade.plans.vip_1m.button'),
    popular: false,
    features: [
      t('user_views.Upgrade.features.ai_100'),
      t('user_views.Upgrade.features.ocr'),
      t('user_views.Upgrade.features.private_quiz'),
      t('user_views.Upgrade.features.realtime_group')
    ]
  },
  {
    id: 'vip_3m',
    name: t('user_views.Upgrade.plans.vip_3m.name'),
    price: 120000,
    priceLabel: '120.000đ',
    period: t('user_views.Upgrade.plans.vip_3m.period'),
    icon: '🚀',
    desc: t('user_views.Upgrade.plans.vip_3m.desc'),
    quota: 350,
    btnText: t('user_views.Upgrade.plans.vip_3m.button'),
    popular: true,
    features: [
      t('user_views.Upgrade.features.ai_350'),
      t('user_views.Upgrade.features.ocr'),
      t('user_views.Upgrade.features.private_quiz'),
      t('user_views.Upgrade.features.realtime_large'),
      t('user_views.Upgrade.features.export_pdf_excel')
    ]
  },
  {
    id: 'vip_1y',
    name: t('user_views.Upgrade.plans.vip_1y.name'),
    price: 400000,
    priceLabel: '400.000đ',
    period: t('user_views.Upgrade.plans.vip_1y.period'),
    icon: '👑',
    desc: t('user_views.Upgrade.plans.vip_1y.desc'),
    quota: 1500,
    btnText: t('user_views.Upgrade.plans.vip_1y.button'),
    popular: false,
    features: [
      t('user_views.Upgrade.features.ai_1500'),
      t('user_views.Upgrade.features.ocr'),
      t('user_views.Upgrade.features.private_quiz'),
      t('user_views.Upgrade.features.realtime_max'),
      t('user_views.Upgrade.features.export_pdf_excel'),
      t('user_views.Upgrade.features.vip_badge')
    ]
  }
])

onMounted(() => {
  if (currentUser.value) {
    loadHistory()
    
    // Tự động mở modal thanh toán nếu có plan truyền qua URL query
    if (route.query.plan) {
      const matchedPlan = plans.value.find(p => p.id === route.query.plan)
      if (matchedPlan) {
        openCheckout(matchedPlan)
      }
    }
  }
})

const loadHistory = async () => {
  try {
    const res = await paymentsApi.history()
    historyList.value = Array.isArray(res?.data) ? res.data : []
  } catch (error) {
    console.error('Failed to load transaction history', error)
  }
}

const openCheckout = (plan) => {
  if (!currentUser.value) {
    // Nếu chưa đăng nhập, chuyển hướng sang trang đăng ký kèm giữ ý định chọn gói
    router.push({ path: '/register', query: { plan: plan.id } })
    return
  }
  selectedPlan.value = plan
  isPaymentModalOpen.value = true
  errorMessage.value = ''
}

const closeCheckout = () => {
  if (isProcessing.value) return
  isPaymentModalOpen.value = false
  selectedPlan.value = null
}

const handlePayment = async (provider) => {
  if (!selectedPlan.value) return
  isProcessing.value = true
  errorMessage.value = ''

  try {
    const res = await paymentsApi.create({
      plan_id: selectedPlan.value.id,
      provider: provider
    })

    if (res.success && res.payUrl) {
      // Chuyển hướng trình duyệt sang cổng thanh toán Sandbox MoMo
      window.location.href = res.payUrl
    } else {
      throw new Error(res.message || t('user_views.Upgrade.errors.payment_url_failed'))
    }
  } catch (error) {
    errorMessage.value = error.message
    isProcessing.value = false
  }
}

// Helpers
const getPlanNameByAmount = (amount) => {
  const parsed = Number(amount)
  if (parsed === 50000) return t('user_views.Upgrade.plan_names.vip_1m')
  if (parsed === 120000) return t('user_views.Upgrade.plan_names.vip_3m')
  if (parsed === 400000) return t('user_views.Upgrade.plan_names.vip_1y')
  return t('user_views.Upgrade.plan_names.custom')
}

const formatPrice = (value) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value)
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('vi-VN', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusText = (status) => {
  const mapping = {
    pending: t('user_views.Upgrade.status.pending'),
    success: t('user_views.Upgrade.status.success'),
    failed: t('user_views.Upgrade.status.failed'),
    refunded: t('user_views.Upgrade.status.refunded')
  }
  return mapping[status] || status
}

const getStatusBadgeClass = (status) => {
  const mapping = {
    pending: 'bg-amber-500/10 text-amber-500 border border-amber-500/20',
    success: 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
    failed: 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
    refunded: 'bg-gray-500/10 text-gray-400 border border-gray-500/20'
  }
  return mapping[status] || 'bg-gray-500/10 text-gray-400'
}
</script>

<style scoped>
/* Thêm một số hiệu ứng glowing và neon */
article:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 40px rgba(124, 58, 237, 0.08);
}
</style>
