<template>
  <section class="max-w-6xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Quản trị tài chính</p>
        <h1 class="text-3xl font-black tracking-[-0.04em] text-[var(--text)]">Quản lý giao dịch & VIP</h1>
        <p class="mt-1 text-sm text-slate-600">Theo dõi doanh thu, lịch sử nạp VIP, đối soát cổng thanh toán và phân tích khách hàng.</p>
      </div>
      <div class="flex items-center gap-2.5">
        <button class="btn-secondary text-xs px-3.5 py-1.5" type="button" @click="exportCsv">
          ⤓ Xuất CSV
        </button>
        <button class="btn-primary text-xs px-3.5 py-1.5" type="button" :disabled="isLoading" @click="loadPayments">
          {{ isLoading ? 'Đang tải...' : '🔄 Làm mới' }}
        </button>
      </div>
    </div>

    <!-- Stats Row 1: Key Metrics -->
    <div class="grid gap-4 sm:grid-cols-3">
      <div class="card p-5 space-y-1">
        <span class="text-[10px] font-bold uppercase text-slate-400 block">Tổng doanh thu</span>
        <strong class="text-2xl font-black text-emerald-600 block">{{ formatPrice(totalRevenue) }}</strong>
        <span class="text-[11px] text-slate-500 font-medium block">Từ các giao dịch thành công</span>
      </div>

      <div class="card p-5 space-y-1">
        <span class="text-[10px] font-bold uppercase text-slate-400 block">Giao dịch thành công</span>
        <strong class="text-2xl font-black text-slate-900 block">{{ totalTransactionsCount }}</strong>
        <span class="text-[11px] text-slate-500 font-medium block">Hóa đơn đã xác nhận</span>
      </div>

      <div class="card p-5 space-y-1">
        <span class="text-[10px] font-bold uppercase text-slate-400 block">Khách hàng thanh toán</span>
        <strong class="text-2xl font-black text-[#7C3AED] block">{{ totalCustomersCount }}</strong>
        <span class="text-[11px] text-slate-500 font-medium block">Số tài khoản đã mua VIP</span>
      </div>
    </div>

    <!-- Stats Row 2: Secondary Analysis -->
    <div class="grid gap-4 sm:grid-cols-3">
      <div class="card p-5 space-y-1">
        <span class="text-[10px] font-bold uppercase text-slate-400 block">Doanh thu tháng này</span>
        <strong class="text-2xl font-black text-slate-900 block">{{ formatPrice(monthRevenue) }}</strong>
        <span class="text-[11px] font-bold" :class="monthGrowth >= 0 ? 'text-emerald-600' : 'text-red-600'">
          {{ monthGrowth >= 0 ? '▲' : '▼' }} {{ Math.abs(monthGrowth).toFixed(1) }}% so với tháng trước
        </span>
      </div>

      <div class="card p-5 space-y-1">
        <span class="text-[10px] font-bold uppercase text-slate-400 block">Giá trị đơn TB (AOV)</span>
        <strong class="text-2xl font-black text-slate-900 block">{{ formatPrice(averageOrderValue) }}</strong>
        <span class="text-[11px] text-slate-500 font-medium block">Trung bình / đơn thành công</span>
      </div>

      <div class="card p-5 space-y-1">
        <span class="text-[10px] font-bold uppercase text-slate-400 block">Tỷ lệ hủy / thất bại</span>
        <strong class="text-2xl font-black text-red-600 block">{{ failureRate.toFixed(1) }}%</strong>
        <span class="text-[11px] text-slate-500 font-medium block">{{ failedCount }} thất bại · {{ refundedCount }} hoàn tiền</span>
      </div>
    </div>

    <!-- Daily Revenue Trend (14 Days) -->
    <div class="card p-6 space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
          <h2 class="text-sm font-bold text-slate-900">Xu hướng doanh thu 14 ngày gần nhất</h2>
          <p class="text-xs text-slate-500">Chỉ tính giao dịch thành công</p>
        </div>
        <div class="text-right">
          <span class="text-[10px] font-bold uppercase text-slate-400 block">Cao nhất trong kỳ</span>
          <b class="text-xs font-bold text-[#7C3AED]">{{ formatPrice(maxDailyRevenue) }}</b>
        </div>
      </div>

      <div v-if="dailyRevenue.length" class="flex items-end gap-2 h-36 pt-4">
        <div
          v-for="day in dailyRevenue"
          :key="day.date"
          class="group relative flex-1 flex flex-col items-center justify-end h-full"
        >
          <div class="w-full rounded-t bg-purple-100 group-hover:bg-[#7C3AED] transition-colors" :style="{ height: barHeight(day.amount) }"></div>
          <span class="mt-1.5 text-[9px] font-semibold text-slate-400">{{ day.label }}</span>
        </div>
      </div>
      <div v-else class="py-8 text-center text-xs text-slate-400">
        Chưa có dữ liệu doanh thu gần đây.
      </div>
    </div>

    <!-- Main Customers / Transaction History -->
    <div class="card p-6 space-y-5">
      <!-- Filter Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
        <div class="flex items-center gap-3">
          <button
            v-if="selectedUser"
            @click="selectedUser = null"
            class="btn-secondary text-xs px-3 py-1.5"
          >
            ← Quay lại danh sách
          </button>
          <h2 v-else class="text-sm font-bold text-slate-900">Danh sách khách hàng</h2>
        </div>

        <div v-if="!selectedUser" class="flex flex-wrap items-center gap-2.5">
          <input
            v-model="searchQuery"
            class="field text-xs min-w-[200px]"
            placeholder="Tìm tên hoặc email..."
          />
          <select v-model="sortBy" class="field text-xs">
            <option value="amount_desc">Chi tiêu cao nhất</option>
            <option value="amount_asc">Chi tiêu thấp nhất</option>
            <option value="trans_desc">Giao dịch nhiều nhất</option>
            <option value="name_asc">Tên A → Z</option>
            <option value="recent">Gần nhất</option>
          </select>
        </div>
      </div>

      <!-- Customer Detail Header Card -->
      <div v-if="selectedUser" class="rounded-xl border border-purple-100 bg-purple-50/40 p-4 space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-purple-200 text-[#7C3AED] font-bold flex items-center justify-center text-sm">
              {{ (selectedUser.user_name || 'A').charAt(0).toUpperCase() }}
            </div>
            <div>
              <h3 class="font-bold text-slate-900 text-sm">{{ selectedUser.user_name || 'Anonymous' }}</h3>
              <p class="text-xs text-slate-500 font-mono">{{ selectedUser.user_email }}</p>
            </div>
          </div>
          <span class="rounded bg-purple-100 text-[#7C3AED] font-bold text-[10px] uppercase px-2 py-0.5">
            {{ selectedUser.role || 'free' }}
          </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
          <div class="rounded-lg bg-white p-2.5 border border-purple-100">
            <span class="text-[10px] text-slate-400 font-bold uppercase block">Tổng chi tiêu</span>
            <b class="text-emerald-700 font-black text-sm block mt-0.5">{{ formatPrice(selectedUser.total_amount) }}</b>
          </div>
          <div class="rounded-lg bg-white p-2.5 border border-purple-100">
            <span class="text-[10px] text-slate-400 font-bold uppercase block">Giao dịch</span>
            <b class="text-slate-900 font-bold text-sm block mt-0.5">{{ selectedUser.total_transactions }} đơn</b>
          </div>
          <div class="rounded-lg bg-white p-2.5 border border-purple-100">
            <span class="text-[10px] text-slate-400 font-bold uppercase block">TB / Đơn</span>
            <b class="text-slate-900 font-bold text-sm block mt-0.5">{{ formatPrice(selectedUserAvgOrder) }}</b>
          </div>
          <div class="rounded-lg bg-white p-2.5 border border-purple-100">
            <span class="text-[10px] text-slate-400 font-bold uppercase block">Kênh hay dùng</span>
            <b class="text-slate-900 font-bold text-sm block mt-0.5">{{ selectedUserFavoriteChannel }}</b>
          </div>
        </div>

        <!-- Status Filter for user history -->
        <div class="flex items-center gap-1.5 pt-1">
          <button
            v-for="tab in statusTabs"
            :key="tab.value"
            @click="historyStatusFilter = tab.value"
            class="px-2.5 py-1 rounded text-[10px] font-bold transition"
            :class="historyStatusFilter === tab.value ? 'bg-[#7C3AED] text-white' : 'bg-white text-slate-600 border border-slate-200'"
          >
            {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- Tables -->
      <div class="overflow-x-auto">
        <!-- Customers Table -->
        <table v-if="!selectedUser" class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-100 text-slate-400 uppercase text-[10px] font-bold">
              <th class="py-3 px-3">Khách hàng</th>
              <th class="py-3 px-3 text-center">Giao dịch thành công</th>
              <th class="py-3 px-3">Tổng chi tiêu</th>
              <th class="py-3 px-3">Gần nhất</th>
              <th class="py-3 px-3 text-right">Chi tiết</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="user in paginatedUsers" :key="user.user_email" class="hover:bg-slate-50">
              <td class="py-3 px-3">
                <div class="flex items-center gap-2.5">
                  <div class="h-7 w-7 rounded-full bg-slate-100 text-slate-600 font-bold flex items-center justify-center text-xs shrink-0">
                    {{ (user.user_name || 'A').charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <span class="font-bold text-slate-900 block">{{ user.user_name || 'Anonymous' }}</span>
                    <span class="text-[10px] text-slate-400 block font-mono">{{ user.user_email }}</span>
                  </div>
                </div>
              </td>
              <td class="py-3 px-3 text-center font-bold text-slate-800">{{ user.total_transactions }}</td>
              <td class="py-3 px-3 font-bold text-emerald-700">{{ formatPrice(user.total_amount) }}</td>
              <td class="py-3 px-3 text-slate-400 text-[11px]">{{ formatDateTime(lastPurchaseOf(user)) }}</td>
              <td class="py-3 px-3 text-right">
                <button
                  @click="selectedUser = user; historyStatusFilter = 'all'"
                  class="btn-secondary text-[11px] px-2.5 py-1"
                >
                  Lịch sử →
                </button>
              </td>
            </tr>
            <tr v-if="filteredUsers.length === 0">
              <td colspan="5" class="py-8 text-center text-slate-400">Không tìm thấy khách hàng nào.</td>
            </tr>
          </tbody>
        </table>

        <!-- Specific Customer Transactions Table -->
        <table v-else class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-100 text-slate-400 uppercase text-[10px] font-bold">
              <th class="py-3 px-3">Mã đơn</th>
              <th class="py-3 px-3">Gói dịch vụ</th>
              <th class="py-3 px-3">Kênh</th>
              <th class="py-3 px-3">Số tiền</th>
              <th class="py-3 px-3">Thời gian</th>
              <th class="py-3 px-3 text-right">Trạng thái</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="item in filteredHistory" :key="item.id" class="hover:bg-slate-50">
              <td class="py-3 px-3 font-mono text-[11px] text-slate-700">{{ item.order_code }}</td>
              <td class="py-3 px-3 font-bold text-slate-900">{{ item.plan_name }}</td>
              <td class="py-3 px-3 uppercase text-[10px] font-bold text-slate-500">{{ item.provider }}</td>
              <td class="py-3 px-3 font-bold text-slate-900">{{ formatPrice(item.amount) }}</td>
              <td class="py-3 px-3 text-slate-400 text-[11px]">{{ formatDateTime(item.created_at) }}</td>
              <td class="py-3 px-3 text-right">
                <span class="rounded px-2 py-0.5 text-[10px] font-bold" :class="getStatusBadgeClass(item.status)">
                  {{ getStatusText(item.status) }}
                </span>
              </td>
            </tr>
            <tr v-if="filteredHistory.length === 0">
              <td colspan="6" class="py-8 text-center text-slate-400">Không có giao dịch nào ở trạng thái này.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="!selectedUser && filteredUsers.length > pageSize" class="flex items-center justify-between pt-4 border-t border-slate-100 text-xs">
        <span class="text-slate-500 text-[11px]">
          Hiển thị {{ (currentPage - 1) * pageSize + 1 }}–{{ Math.min(currentPage * pageSize, filteredUsers.length) }} trong số {{ filteredUsers.length }}
        </span>
        <div class="flex items-center gap-2">
          <button
            @click="currentPage > 1 && currentPage--"
            :disabled="currentPage === 1"
            class="btn-secondary text-[11px] px-2.5 py-1 disabled:opacity-40"
          >
            ← Trước
          </button>
          <span class="text-slate-700 font-bold">{{ currentPage }} / {{ totalPages }}</span>
          <button
            @click="currentPage < totalPages && currentPage++"
            :disabled="currentPage === totalPages"
            class="btn-secondary text-[11px] px-2.5 py-1 disabled:opacity-40"
          >
            Sau →
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref, computed, watch } from 'vue'
import { paymentsApi } from '@/services/api'

const usersList = ref([])
const rawTransactions = ref([])
const selectedUser = ref(null)
const isLoading = ref(false)
const searchQuery = ref('')
const sortBy = ref('amount_desc')
const historyStatusFilter = ref('all')
const currentPage = ref(1)
const pageSize = 10

const totalRevenue = ref(0)
const totalTransactionsCount = ref(0)
const totalCustomersCount = ref(0)

const statusTabs = [
  { value: 'all', label: 'Tất cả' },
  { value: 'success', label: 'Thành công' },
  { value: 'pending', label: 'Chờ xử lý' },
  { value: 'failed', label: 'Thất bại' },
  { value: 'refunded', label: 'Hoàn tiền' },
]

const loadPayments = async () => {
  isLoading.value = true
  try {
    const res = await paymentsApi.history()
    const rawData = Array.isArray(res?.data) ? res.data : []
    rawTransactions.value = rawData

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

      if (item.status === 'success') {
        u.total_transactions += 1
        u.total_amount += Number(item.amount)
        revenueSum += Number(item.amount)
        transCount += 1
      }

      u.history.push(item)
    })

    userMap.forEach(u => {
      u.history.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    })

    usersList.value = Array.from(userMap.values()).sort((a, b) => b.total_amount - a.total_amount)
    totalRevenue.value = revenueSum
    totalTransactionsCount.value = transCount
    totalCustomersCount.value = userMap.size

    if (selectedUser.value) {
      const updatedUser = usersList.value.find(u => u.user_email === selectedUser.value.user_email)
      if (updatedUser) selectedUser.value = updatedUser
    }
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

const successTransactions = computed(() =>
  rawTransactions.value.filter(t => t.status === 'success')
)

const monthRevenue = computed(() => {
  const now = new Date()
  return successTransactions.value
    .filter(t => {
      const d = new Date(t.created_at)
      return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear()
    })
    .reduce((sum, t) => sum + Number(t.amount), 0)
})

const lastMonthRevenue = computed(() => {
  const now = new Date()
  const lastMonth = now.getMonth() === 0 ? 11 : now.getMonth() - 1
  const year = now.getMonth() === 0 ? now.getFullYear() - 1 : now.getFullYear()
  return successTransactions.value
    .filter(t => {
      const d = new Date(t.created_at)
      return d.getMonth() === lastMonth && d.getFullYear() === year
    })
    .reduce((sum, t) => sum + Number(t.amount), 0)
})

const monthGrowth = computed(() => {
  if (!lastMonthRevenue.value) return monthRevenue.value > 0 ? 100 : 0
  return ((monthRevenue.value - lastMonthRevenue.value) / lastMonthRevenue.value) * 100
})

const averageOrderValue = computed(() => {
  if (!successTransactions.value.length) return 0
  return totalRevenue.value / successTransactions.value.length
})

const failedCount = computed(() => rawTransactions.value.filter(t => t.status === 'failed').length)
const refundedCount = computed(() => rawTransactions.value.filter(t => t.status === 'refunded').length)
const failureRate = computed(() => {
  if (!rawTransactions.value.length) return 0
  return ((failedCount.value + refundedCount.value) / rawTransactions.value.length) * 100
})

const dailyRevenue = computed(() => {
  const days = []
  const today = new Date()
  for (let i = 13; i >= 0; i--) {
    const d = new Date(today)
    d.setDate(d.getDate() - i)
    const key = d.toISOString().slice(0, 10)
    const amount = successTransactions.value
      .filter(t => new Date(t.created_at).toISOString().slice(0, 10) === key)
      .reduce((sum, t) => sum + Number(t.amount), 0)
    days.push({
      date: key,
      amount,
      label: `${d.getDate()}/${d.getMonth() + 1}`
    })
  }
  return days
})

const maxDailyRevenue = computed(() => Math.max(1, ...dailyRevenue.value.map(d => d.amount)))
const barHeight = (amount) => {
  const pct = Math.max(6, (amount / maxDailyRevenue.value) * 100)
  return `${pct}%`
}

const filteredUsers = computed(() => {
  let list = usersList.value
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim()
    list = list.filter(u =>
      (u.user_name && u.user_name.toLowerCase().includes(q)) ||
      (u.user_email && u.user_email.toLowerCase().includes(q))
    )
  }

  const sorted = [...list]
  switch (sortBy.value) {
    case 'amount_asc':
      sorted.sort((a, b) => a.total_amount - b.total_amount)
      break
    case 'trans_desc':
      sorted.sort((a, b) => b.total_transactions - a.total_transactions)
      break
    case 'name_asc':
      sorted.sort((a, b) => (a.user_name || '').localeCompare(b.user_name || ''))
      break
    case 'recent':
      sorted.sort((a, b) => new Date(lastPurchaseOf(b) || 0) - new Date(lastPurchaseOf(a) || 0))
      break
    default:
      sorted.sort((a, b) => b.total_amount - a.total_amount)
  }
  return sorted
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredUsers.value.length / pageSize)))
const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return filteredUsers.value.slice(start, start + pageSize)
})

watch([searchQuery, sortBy], () => { currentPage.value = 1 })

const lastPurchaseOf = (user) => user.history.length ? user.history[0].created_at : null
const firstPurchaseOf = (user) => user.history.length ? user.history[user.history.length - 1].created_at : null

const filteredHistory = computed(() => {
  if (!selectedUser.value) return []
  if (historyStatusFilter.value === 'all') return selectedUser.value.history
  return selectedUser.value.history.filter(h => h.status === historyStatusFilter.value)
})

const selectedUserAvgOrder = computed(() => {
  if (!selectedUser.value || !selectedUser.value.total_transactions) return 0
  return selectedUser.value.total_amount / selectedUser.value.total_transactions
})

const selectedUserFavoriteChannel = computed(() => {
  if (!selectedUser.value) return '-'
  const counts = {}
  selectedUser.value.history.forEach(h => {
    counts[h.provider] = (counts[h.provider] || 0) + 1
  })
  const top = Object.entries(counts).sort((a, b) => b[1] - a[1])[0]
  if (!top) return '-'
  const names = { momo: 'MoMo', payos: 'VietQR (PayOS)', trial: 'Dùng thử' }
  return names[top[0]] || 'VNPay'
})

const formatPrice = (v) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(v) || 0)
const getStatusText = (s) => ({ pending: 'Chờ xử lý', success: 'Thành công', failed: 'Thất bại', refunded: 'Hoàn tiền' }[s] || s)

const getStatusBadgeClass = (s) => ({
  success: 'bg-emerald-100 text-emerald-800',
  pending: 'bg-amber-100 text-amber-800',
  failed: 'bg-red-100 text-red-800',
  refunded: 'bg-slate-100 text-slate-600'
}[s] || 'bg-slate-100 text-slate-600')

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

const exportCsv = () => {
  const escapeCsvCell = (value) => {
    const str = String(value ?? '')
    return `"${str.replace(/"/g, '""')}"`
  }

  const rows = [
    ['Tên khách hàng', 'Email', 'Giao dịch thành công', 'Tổng chi tiêu (VND)', 'Giao dịch lần đầu', 'Giao dịch gần nhất'],
    ...filteredUsers.value.map(u => [
      u.user_name || 'Anonymous',
      u.user_email,
      u.total_transactions,
      u.total_amount,
      formatDateTime(firstPurchaseOf(u)),
      formatDateTime(lastPurchaseOf(u))
    ])
  ]
  const csvContent = rows.map(r => r.map(escapeCsvCell).join(',')).join('\n')
  const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `khach-hang-thanh-toan-${new Date().toISOString().slice(0, 10)}.csv`
  link.click()
  URL.revokeObjectURL(url)
}

onMounted(loadPayments)
</script>
