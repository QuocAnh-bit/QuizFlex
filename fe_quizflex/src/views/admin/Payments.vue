<template>
  <section class="grid gap-6">
    <!-- Header: Giao diện Đen - Tím hiện đại -->
    <div class="bg-[var(--surface)] border border-[var(--border)] rounded-[2rem] p-6 shadow-xl backdrop-blur-xl">
      <!-- Hiệu ứng Glow tím -->
      <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-violet-600/20 blur-[100px]"></div>
      
      <div class="relative z-10">
        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-violet-500">
          PAYMENT MANAGEMENT
        </p>
        <h1 class="mt-2 text-4xl font-black tracking-tighter text-white">
          Quản lý Tài chính
        </h1>
        <p class="mt-3 max-w-2xl text-sm leading-7 text-neutral-400">
          Quản lý toàn bộ giao dịch trên hệ thống, theo dõi doanh thu và trạng thái thanh toán của người dùng.
        </p>
      </div>
    </div>


    <div class="bg-[var(--surface)] border border-[var(--border)] rounded-[2rem] p-6 shadow-xl backdrop-blur-xl">
      
      <div class="flex items-center justify-between mb-8">
        <button v-if="selectedUser" @click="selectedUser = null" class="flex items-center gap-2 text-sm font-bold text-[var(--primary)] hover:translate-x-[-5px] transition-all">
          ← Quay lại danh sách
        </button>
        <h2 v-else class="text-xl font-black text-[var(--text)]">Danh sách khách hàng</h2>
        
        <button @click="loadPayments" :disabled="isLoading" class="group flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-[var(--surface-soft)] font-bold text-sm border border-[var(--border)] hover:border-[var(--primary)] transition-all">
          <span :class="{'animate-spin': isLoading}">↻</span>
          {{ isLoading ? 'Đang tải...' : 'Làm mới' }}
        </button>
      </div>

      <div class="overflow-x-auto">
        <table v-if="!selectedUser" class="w-full text-left border-collapse">
          <thead>
            <tr class="text-[var(--muted)] text-[10px] uppercase tracking-[0.2em]">
              <th class="pb-6 pl-4">Khách hàng</th>
              <th class="pb-6 px-6 text-center">Giao dịch thành công</th>
              <th class="pb-6 px-6">Tổng chi tiêu</th>
              <th class="pb-6 pr-4 text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--border)]">
            <tr v-for="user in usersList" :key="user.user_email" class="hover:bg-[var(--surface-soft)]/50 transition-colors group">
              <td class="py-5 pl-4">
                <div class="font-black text-[var(--text)] group-hover:text-[var(--primary)] transition-colors">{{ user.user_name || 'Anonymous' }}</div>
                <div class="text-[11px] text-[var(--muted)] font-mono">{{ user.user_email }}</div>
              </td>
              <td class="py-5 px-6 text-center font-black text-lg">{{ user.total_transactions }}</td>
              <td class="py-5 px-6 font-black text-xl text-[var(--primary)]">{{ formatPrice(user.total_amount) }}</td>
              <td class="py-5 pr-4 text-right">
                <button @click="selectedUser = user" class="px-5 py-2 bg-black text-white dark:bg-white dark:text-black rounded-xl font-bold text-xs hover:scale-105 transition-transform shadow-lg">
                  Chi tiết
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <table v-else class="w-full text-left border-collapse">
          <thead>
            <tr class="text-[var(--muted)] text-[10px] uppercase tracking-[0.2em]">
              <th class="pb-6">Mã Giao Dịch</th>
              <th class="pb-6 px-6">Gói</th>
              <th class="pb-6 px-6">Kênh</th>
              <th class="pb-6 px-6">Số tiền</th>
              <th class="pb-6 px-6">Trạng thái</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--border)]">
            <tr v-for="item in selectedUser.history" :key="item.id" class="hover:bg-[var(--surface-soft)]/50">
              <td class="py-4 px-4 font-mono text-xs text-[var(--text)]">{{ item.order_code }}</td>
              <td class="py-4 px-4 text-[var(--text)]">
                <div class="flex items-center gap-2">
                  <span class="font-bold">{{ item.plan_name }}</span>
                  <span 
                    v-if="item.is_upgrade" 
                    class="bg-purple-500/20 text-purple-400 text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded border border-purple-500/20"
                  >
                    Nâng cấp
                  </span>
                </div>
              </td>
              <td class="py-4 px-4 text-xs font-bold uppercase text-[var(--muted)]">
                <span class="inline-flex items-center gap-1 rounded bg-fuchsia-500/10 text-fuchsia-400 px-2 py-0.5" v-if="item.provider === 'momo'">
                  MoMo
                </span>
                <span class="inline-flex items-center gap-1 rounded bg-emerald-500/10 text-emerald-400 px-2 py-0.5" v-else-if="item.provider === 'payos'">
                  VietQR (PayOS)
                </span>
                <span class="inline-flex items-center gap-1 rounded bg-purple-500/10 text-purple-400 px-2 py-0.5" v-else-if="item.provider === 'trial'">
                  Dùng thử
                </span>
                <span class="inline-flex items-center gap-1 rounded bg-blue-500/10 text-blue-400 px-2 py-0.5" v-else>
                  VNPay
                </span>
              </td>
              <td class="py-4 px-4 text-[var(--text)]">
                <div class="flex flex-col">
                  <span class="font-black text-sm">{{ formatPrice(item.amount) }}</span>
                  <span 
                    v-if="item.is_upgrade && item.discount_amount > 0" 
                    class="text-[10px] text-[var(--muted)] font-semibold"
                  >
                    (Gốc: {{ formatPrice(item.original_amount) }} | -{{ formatPrice(item.discount_amount) }})
                  </span>
                </div>
              </td>
              <td class="py-4 px-4 text-xs">
                <span
                  class="inline-flex items-center gap-1 px-3 py-1 rounded-full font-black uppercase text-[10px]"
                  :class="getStatusBadgeClass(item.status)"
                >
                  {{ getStatusText(item.status) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { paymentsApi } from '@/services/api'

const usersList = ref([])
const selectedUser = ref(null)
const isLoading = ref(false)

const loadPayments = async () => {
  isLoading.value = true
  try {
    const res = await paymentsApi.history()
    const rawData = Array.isArray(res?.data) ? res.data : []
    
    const userMap = new Map()
    rawData.forEach(item => {
      const id = item.user_email || 'unknown'
      if (!userMap.has(id)) {
        userMap.set(id, { user_name: item.user_name, user_email: item.user_email, total_transactions: 0, total_amount: 0, history: [] })
      }
      const u = userMap.get(id)
      
      // Chỉ tính tổng cho các giao dịch thành công
      if (item.status === 'success') {
        u.total_transactions += 1
        u.total_amount += Number(item.amount)
      }
      
      u.history.push(item)
    })
    
    usersList.value = Array.from(userMap.values()).sort((a, b) => b.total_amount - a.total_amount)
  } catch (e) { console.error(e) } finally { isLoading.value = false }
}

const formatPrice = (v) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(v))
const getPlanNameByAmount = (a) => ({ 50000: 'Gói Plus', 120000: 'Gói Pro', 250000: 'Gói Ultra' }[a] || 'Khác')
const getStatusText = (s) => ({ pending: 'Chờ xử lý', success: 'Thành công', failed: 'Thất bại', refunded: 'Hoàn tiền' }[s] || s)

const getStatusBadgeClass = (s) => ({
  success: 'bg-emerald-500/10 text-emerald-500',
  pending: 'bg-amber-500/10 text-amber-500',
  failed: 'bg-rose-500/10 text-rose-500',
  refunded: 'bg-slate-500/10 text-slate-500'
}[s] || 'bg-slate-500/10 text-slate-500')

onMounted(loadPayments)
</script>