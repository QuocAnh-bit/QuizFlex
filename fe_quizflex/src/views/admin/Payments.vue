<template>
  <section class="grid gap-6">
    <!-- Header: Giao diện Đen - Tím hiện đại -->
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-xl backdrop-blur-xl">
      <!-- Hiệu ứng Glow tím -->
      <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-violet-600/20 blur-[100px]"></div>
      
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

    <!-- Stats Cards Grid -->
    <div class="grid gap-4 sm:grid-cols-3">
      <!-- Doanh thu tổng -->
      <div class="relative overflow-hidden rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-soft)]">
        <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-emerald-500/10 blur-xl"></div>
        <p class="text-[10px] font-black uppercase tracking-[0.15em] text-emerald-500">Tổng doanh thu</p>
        <p class="mt-2 text-2xl font-black text-white">{{ formatPrice(totalRevenue) }}</p>
        <span class="mt-1 block text-xs text-[var(--muted)]">Tổng số tiền thanh toán thành công</span>
      </div>

      <!-- Số giao dịch thành công -->
      <div class="relative overflow-hidden rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-soft)]">
        <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-blue-500/10 blur-xl"></div>
        <p class="text-[10px] font-black uppercase tracking-[0.15em] text-blue-400">Giao dịch thành công</p>
        <p class="mt-2 text-2xl font-black text-white">{{ totalTransactionsCount }}</p>
        <span class="mt-1 block text-xs text-[var(--muted)]">Hóa đơn đã được xác nhận thanh toán</span>
      </div>

      <!-- Số lượng khách hàng giao dịch -->
      <div class="relative overflow-hidden rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-soft)]">
        <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-purple-500/10 blur-xl"></div>
        <p class="text-[10px] font-black uppercase tracking-[0.15em] text-purple-400">Khách hàng thanh toán</p>
        <p class="mt-2 text-2xl font-black text-white">{{ totalCustomersCount }}</p>
        <span class="mt-1 block text-xs text-[var(--muted)]">Số người dùng đã đăng ký mua gói</span>
      </div>
    </div>

    <!-- Main Section Card -->
    <div class="bg-[var(--surface)] border border-[var(--border)] rounded-[2rem] p-6 shadow-xl backdrop-blur-xl">
      
      <!-- Table Header & Filters -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
          <!-- Nút Quay lại -->
          <button 
            v-if="selectedUser" 
            @click="selectedUser = null" 
            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-[var(--surface-soft)] font-bold text-xs border border-[var(--border)] text-[var(--text)] hover:translate-x-[-3px] transition-all"
          >
            ← Quay lại danh sách
          </button>
          <h2 v-else class="text-xl font-black text-[var(--text)]">Danh sách khách hàng</h2>
        </div>
        
        <!-- Các bộ lọc / Nút chức năng -->
        <div class="flex flex-wrap items-center gap-3">
          <!-- Search box khi ở màn danh sách người dùng -->
          <div v-if="!selectedUser" class="relative min-w-[240px]">
            <input 
              v-model="searchQuery" 
              class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-2 text-xs font-semibold text-[var(--text)] outline-none focus:border-[var(--primary)] placeholder:text-[var(--muted)]"
              placeholder="🔍 Tìm khách hàng (tên, email)..." 
            />
          </div>

          <button 
            @click="loadPayments" 
            :disabled="isLoading" 
            class="group flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-[var(--surface-soft)] font-bold text-sm border border-[var(--border)] hover:border-[var(--primary)] transition-all"
          >
            <span :class="{'animate-spin': isLoading}">↻</span>
            {{ isLoading ? 'Đang tải...' : 'Làm mới' }}
          </button>
        </div>
      </div>

      <!-- Banner thông tin khách hàng khi đang xem chi tiết -->
      <div v-if="selectedUser" class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
        <div class="flex items-center gap-4">
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[var(--primary)]/10 font-bold text-lg text-[var(--primary)] border border-[var(--primary)]/20">
            {{ (selectedUser.user_name || 'A').charAt(0).toUpperCase() }}
          </div>
          <div>
            <h3 class="font-black text-white text-base">{{ selectedUser.user_name || 'Khách hàng ẩn danh' }}</h3>
            <p class="text-xs text-[var(--muted)] font-mono">{{ selectedUser.user_email }}</p>
          </div>
        </div>
        <div class="flex gap-4">
          <div class="text-right">
            <p class="text-[10px] uppercase font-black tracking-wider text-[var(--muted)]">Tổng chi tiêu</p>
            <p class="text-lg font-black text-[var(--primary)]">{{ formatPrice(selectedUser.total_amount) }}</p>
          </div>
          <div class="text-right">
            <p class="text-[10px] uppercase font-black tracking-wider text-[var(--muted)]">Giao dịch thành công</p>
            <p class="text-lg font-black text-white">{{ selectedUser.total_transactions }}</p>
          </div>
        </div>
      </div>

      <!-- Tables -->
      <div class="overflow-x-auto">
        <!-- Bảng danh sách khách hàng -->
        <table v-if="!selectedUser" class="w-full text-left border-collapse">
          <thead>
            <tr class="text-[var(--muted)] text-[10px] uppercase tracking-[0.2em] border-b border-[var(--border)]">
              <th class="pb-4 pl-4">Khách hàng</th>
              <th class="pb-4 px-6 text-center">Giao dịch thành công</th>
              <th class="pb-4 px-6">Tổng chi tiêu</th>
              <th class="pb-4 pr-4 text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--border)]">
            <tr v-for="user in filteredUsers" :key="user.user_email" class="hover:bg-[var(--surface-soft)]/50 transition-colors group">
              <td class="py-4 pl-4">
                <div class="flex items-center gap-3">
                  <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[var(--primary)]/10 font-bold text-[var(--primary)] group-hover:bg-[var(--primary)] group-hover:text-white transition-all">
                    {{ (user.user_name || 'A').charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <div class="font-black text-[var(--text)] group-hover:text-[var(--primary)] transition-colors">
                      {{ user.user_name || 'Anonymous' }}
                    </div>
                    <div class="text-[11px] text-[var(--muted)] font-mono">{{ user.user_email }}</div>
                  </div>
                </div>
              </td>
              <td class="py-4 px-6 text-center font-black text-base">{{ user.total_transactions }}</td>
              <td class="py-4 px-6 font-black text-lg text-[var(--primary)]">{{ formatPrice(user.total_amount) }}</td>
              <td class="py-4 pr-4 text-right">
                <button 
                  @click="selectedUser = user" 
                  class="px-5 py-2 bg-[var(--surface-soft)] hover:bg-[var(--primary)] hover:text-white border border-[var(--border)] hover:border-[var(--primary)] rounded-xl font-bold text-xs transition-all shadow-md hover:-translate-y-0.5 active:scale-95"
                >
                  Chi tiết
                </button>
              </td>
            </tr>
            <tr v-if="filteredUsers.length === 0">
              <td colspan="4" class="py-8 text-center text-sm font-bold text-[var(--muted)]">
                Không tìm thấy khách hàng nào phù hợp.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Bảng chi tiết lịch sử giao dịch của khách hàng đó -->
        <table v-else class="w-full text-left border-collapse">
          <thead>
            <tr class="text-[var(--muted)] text-[10px] uppercase tracking-[0.2em] border-b border-[var(--border)]">
              <th class="pb-4 pl-4">Mã Giao Dịch</th>
              <th class="pb-4 px-6">Gói</th>
              <th class="pb-4 px-6">Kênh</th>
              <th class="pb-4 px-6">Số tiền</th>
              <th class="pb-4 px-6">Thời gian giao dịch</th>
              <th class="pb-4 pr-4 text-right">Trạng thái</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--border)]">
            <tr v-for="item in selectedUser.history" :key="item.id" class="hover:bg-[var(--surface-soft)]/50 transition-colors">
              <td class="py-4 pl-4 font-mono text-xs text-[var(--text)] font-semibold">{{ item.order_code }}</td>
              <td class="py-4 px-6 text-[var(--text)]">
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
              <td class="py-4 px-6 text-xs font-bold uppercase text-[var(--muted)]">
                <span class="inline-flex items-center gap-1 rounded bg-fuchsia-500/10 text-fuchsia-400 px-2 py-0.5 border border-fuchsia-500/10" v-if="item.provider === 'momo'">
                  MoMo
                </span>
                <span class="inline-flex items-center gap-1 rounded bg-emerald-500/10 text-emerald-400 px-2 py-0.5 border border-emerald-500/10" v-else-if="item.provider === 'payos'">
                  VietQR (PayOS)
                </span>
                <span class="inline-flex items-center gap-1 rounded bg-purple-500/10 text-purple-400 px-2 py-0.5 border border-purple-500/10" v-else-if="item.provider === 'trial'">
                  Dùng thử
                </span>
                <span class="inline-flex items-center gap-1 rounded bg-blue-500/10 text-blue-400 px-2 py-0.5 border border-blue-500/10" v-else>
                  VNPay
                </span>
              </td>
              <td class="py-4 px-6 text-[var(--text)]">
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
              <!-- Cột Thời gian giao dịch -->
              <td class="py-4 px-6 text-xs text-[var(--text)] font-semibold">
                {{ formatDateTime(item.created_at) }}
              </td>
              <td class="py-4 pr-4 text-right text-xs">
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
import { onMounted, ref, computed } from 'vue'
import { paymentsApi } from '@/services/api'

const usersList = ref([])
const selectedUser = ref(null)
const isLoading = ref(false)
const searchQuery = ref('')

const totalRevenue = ref(0)
const totalTransactionsCount = ref(0)
const totalCustomersCount = ref(0)

const loadPayments = async () => {
  isLoading.value = true
  try {
    const res = await paymentsApi.history()
    const rawData = Array.isArray(res?.data) ? res.data : []
    
    let revenueSum = 0
    let transCount = 0
    
    const userMap = new Map()
    rawData.forEach(item => {
      const id = item.user_email || 'unknown'
      if (!userMap.has(id)) {
        userMap.set(id, { 
          user_name: item.user_name, 
          user_email: item.user_email, 
          total_transactions: 0, 
          total_amount: 0, 
          history: [],
          role: item.user_role || 'free'
        })
      }
      const u = userMap.get(id)
      
      // Chỉ tính tổng cho các giao dịch thành công
      if (item.status === 'success') {
        u.total_transactions += 1
        u.total_amount += Number(item.amount)
        revenueSum += Number(item.amount)
        transCount += 1
      }
      
      u.history.push(item)
    })
    
    usersList.value = Array.from(userMap.values()).sort((a, b) => b.total_amount - a.total_amount)
    totalRevenue.value = revenueSum
    totalTransactionsCount.value = transCount
    totalCustomersCount.value = userMap.size

    // Cập nhật lại selectedUser nếu đang xem chi tiết một khách hàng để đồng bộ data mới nhất
    if (selectedUser.value) {
      const updatedUser = usersList.value.find(u => u.user_email === selectedUser.value.user_email)
      if (updatedUser) {
        selectedUser.value = updatedUser
      }
    }
  } catch (e) { 
    console.error(e) 
  } finally { 
    isLoading.value = false 
  }
}

// Bộ lọc tìm kiếm khách hàng
const filteredUsers = computed(() => {
  if (!searchQuery.value.trim()) return usersList.value
  const q = searchQuery.value.toLowerCase().trim()
  return usersList.value.filter(u => 
    (u.user_name && u.user_name.toLowerCase().includes(q)) || 
    (u.user_email && u.user_email.toLowerCase().includes(q))
  )
})

const formatPrice = (v) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(v))
const getStatusText = (s) => ({ pending: 'Chờ xử lý', success: 'Thành công', failed: 'Thất bại', refunded: 'Hoàn tiền' }[s] || s)

const getStatusBadgeClass = (s) => ({
  success: 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-[0_0_12px_rgba(16,185,129,0.1)]',
  pending: 'bg-amber-500/10 text-amber-400 border border-amber-500/20 shadow-[0_0_12px_rgba(245,158,11,0.1)]',
  failed: 'bg-rose-500/10 text-rose-400 border border-rose-500/20 shadow-[0_0_12px_rgba(244,63,94,0.1)]',
  refunded: 'bg-slate-500/10 text-slate-400 border border-slate-500/20'
}[s] || 'bg-slate-500/10 text-slate-400')

// Định dạng ngày giờ hiển thị
const formatDateTime = (value) => {
  if (!value) return '-'
  const date = new Date(value)
  return date.toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(loadPayments)
</script>