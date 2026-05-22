<template>
  <section class="grid gap-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ $t('admin_views.Payments.badge') }}</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ $t('admin_views.Payments.title') }}</h1>
        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">
          {{ $t('admin_views.Payments.description') }}
        </p>
      </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
      <article
        v-for="plan in paymentPlans"
        :key="plan.id"
        class="rounded-[2rem] border p-6 shadow-[var(--shadow-card)]"
        :class="plan.popular ? 'border-[var(--border-strong)] bg-[var(--chip-active)]' : 'border-[var(--border)] bg-[var(--surface)]'"
      >
        <p class="font-black text-[var(--primary)]">{{ plan.name }}</p>
        <h2 class="mt-4 text-4xl font-black text-[var(--text)]">{{ plan.price }}</h2>
        <p class="text-sm text-[var(--muted)]">{{ plan.period }}</p>
        <div class="mt-6 grid gap-3">
          <span
            v-for="feature in plan.features"
            :key="feature"
            class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-sm font-bold text-[var(--muted)]"
          >
            {{ feature }}
          </span>
        </div>
      </article>
    </div>

    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
      <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ $t('admin_views.Payments.history_badge') }}</p>
          <h2 class="mt-2 text-2xl font-black text-[var(--text)]">{{ $t('admin_views.Payments.history_title') }}</h2>
          <p class="mt-1 text-sm font-semibold text-[var(--muted)]">{{ $t('admin_views.Payments.history_description') }}</p>
        </div>
        <button class="btn-ghost" type="button" :disabled="isLoading" @click="loadPayments">
          {{ isLoading ? $t('admin_views.Payments.loading_button') : $t('admin_views.Payments.reload_button') }}
        </button>
      </div>

      <p v-if="errorMessage" class="mt-5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">
        {{ errorMessage }}
      </p>

      <div class="mt-6 overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm font-semibold">
          <thead>
            <tr class="border-b border-[var(--border)] text-[var(--muted)]">
              <th class="pb-3 pr-4 font-black">{{ $t('admin_views.Payments.table.user') }}</th>
              <th class="pb-3 px-4 font-black">{{ $t('admin_views.Payments.table.order_code') }}</th>
              <th class="pb-3 px-4 font-black">{{ $t('admin_views.Payments.table.plan') }}</th>
              <th class="pb-3 px-4 font-black">{{ $t('admin_views.Payments.table.provider') }}</th>
              <th class="pb-3 px-4 font-black">{{ $t('admin_views.Payments.table.amount') }}</th>
              <th class="pb-3 px-4 font-black">{{ $t('admin_views.Payments.table.status') }}</th>
              <th class="pb-3 pl-4 font-black">{{ $t('admin_views.Payments.table.created_at') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="isLoading">
              <td colspan="7" class="py-10 text-center text-[var(--muted)] font-bold">{{ $t('admin_views.Payments.loading_history') }}</td>
            </tr>
            <tr
              v-for="item in paymentHistory"
              v-else
              :key="item.id"
              class="border-b border-[var(--border)]/50 hover:bg-[var(--surface-soft)]/20 transition"
            >
              <td class="py-4 pr-4">
                <b class="block text-[var(--text)]">{{ item.user_name || $t('admin_views.Payments.unknown_user') }}</b>
                <span class="block text-xs text-[var(--muted)]">{{ item.user_email || $t('admin_views.Payments.no_email') }}</span>
              </td>
              <td class="py-4 px-4 font-mono text-xs text-[var(--text)]">{{ item.order_code }}</td>
              <td class="py-4 px-4 text-[var(--text)]">{{ item.plan_name || getPlanNameByAmount(item.amount) }}</td>
              <td class="py-4 px-4 text-xs font-bold uppercase text-[var(--muted)]">{{ item.provider }}</td>
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
            <tr v-if="!isLoading && paymentHistory.length === 0">
              <td colspan="7" class="py-10 text-center text-[var(--muted)] font-bold">
                {{ $t('admin_views.Payments.empty') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </article>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { paymentsApi } from '@/services/api'

const { t } = useI18n()

const paymentPlans = computed(() => [
  { id: 'vip_1m', name: t('admin_views.Payments.plans.vip_1m.name'), price: '50.000đ', period: t('admin_views.Payments.plans.vip_1m.period'), popular: false, features: [t('admin_views.Payments.features.ai_100'), t('admin_views.Payments.features.ocr'), t('admin_views.Payments.features.private_quiz')] },
  { id: 'vip_3m', name: t('admin_views.Payments.plans.vip_3m.name'), price: '120.000đ', period: t('admin_views.Payments.plans.vip_3m.period'), popular: true, features: [t('admin_views.Payments.features.ai_350'), t('admin_views.Payments.features.ocr'), t('admin_views.Payments.features.realtime_large')] },
  { id: 'vip_1y', name: t('admin_views.Payments.plans.vip_1y.name'), price: '400.000đ', period: t('admin_views.Payments.plans.vip_1y.period'), popular: false, features: [t('admin_views.Payments.features.ai_1500'), t('admin_views.Payments.features.ocr'), t('admin_views.Payments.features.export_pdf_excel')] },
])

const paymentHistory = ref([])
const isLoading = ref(false)
const errorMessage = ref('')

onMounted(() => {
  loadPayments()
})

const loadPayments = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const res = await paymentsApi.history()
    paymentHistory.value = Array.isArray(res?.data) ? res.data : []
  } catch (error) {
    errorMessage.value = error.message || t('admin_views.Payments.errors.load_failed')
    paymentHistory.value = []
  } finally {
    isLoading.value = false
  }
}

const getPlanNameByAmount = (amount) => {
  const parsed = Number(amount)
  if (parsed === 50000) return t('admin_views.Payments.plans.vip_1m.name')
  if (parsed === 120000) return t('admin_views.Payments.plans.vip_3m.name')
  if (parsed === 400000) return t('admin_views.Payments.plans.vip_1y.name')
  return t('admin_views.Payments.custom_plan')
}

const formatPrice = (value) => new Intl.NumberFormat('vi-VN', {
  style: 'currency',
  currency: 'VND',
}).format(Number(value) || 0)

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('vi-VN', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getStatusText = (status) => ({
  pending: t('admin_views.Payments.status.pending'),
  success: t('admin_views.Payments.status.success'),
  failed: t('admin_views.Payments.status.failed'),
  refunded: t('admin_views.Payments.status.refunded'),
}[status] || status)

const getStatusBadgeClass = (status) => ({
  pending: 'bg-amber-500/10 text-amber-500 border border-amber-500/20',
  success: 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
  failed: 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
  refunded: 'bg-gray-500/10 text-gray-400 border border-gray-500/20',
}[status] || 'bg-gray-500/10 text-gray-400')
</script>
