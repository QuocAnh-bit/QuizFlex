<template>
  <section class="grid gap-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)]">
      <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Payment Management</p>
      <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">
        {{ selectedUser ? `Chi tiết: ${selectedUser.user_name}` : 'Quản lý giao dịch người dùng' }}
      </h1>
    </div>

    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
      
      <button v-if="selectedUser" @click="selectedUser = null" class="mb-4 flex items-center gap-2 text-sm font-bold text-[var(--primary)]">
        &larr; Quay lại danh sách
      </button>

      <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-black text-[var(--text)]">
          {{ selectedUser ? 'Danh sách giao dịch' : 'Người dùng đã thanh toán' }}
        </h2>
        <button class="btn-ghost" @click="loadPayments" :disabled="isLoading">
          {{ isLoading ? 'Đang tải...' : 'Tải lại dữ liệu' }}
        </button>
      </div>

      <div class="overflow-x-auto">
        <table v-if="!selectedUser" class="w-full text-left text-sm">
          <thead>
            <tr class="border-b border-[var(--border)] text-[var(--muted)]">
              <th class="pb-3 font-black">Người dùng</th>
              <th class="pb-3 px-4 font-black text-center">Số lần</th>
              <th class="pb-3 px-4 font-black">Tổng chi tiêu</th>
              <th class="pb-3 text-right font-black">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in usersList" :key="user.user_email" class="border-b border-[var(--border)]/50 hover:bg-[var(--surface-soft)]/20">
              <td class="py-4">
                <b class="block text-[var(--text)]">{{ user.user_name || 'Không xác định' }}</b>
                <span class="text-xs text-[var(--muted)]">{{ user.user_email || 'Không có email' }}</span>
              </td>
              <td class="py-4 px-4 text-center font-bold">{{ user.total_transactions }}</td>
              <td class="py-4 px-4 font-black text-[var(--primary)]">{{ formatPrice(user.total_amount) }}</td>
              <td class="py-4 text-right">
                <button @click="selectedUser = user" class="px-4 py-2 bg-[var(--surface-soft)] rounded-xl font-bold text-xs hover:bg-[var(--border)] transition">
                  Xem chi tiết
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <table v-else class="w-full text-left text-sm">
          <thead>
            <tr class="border-b border-[var(--border)] text-[var(--muted)]">
              <th class="pb-3 font-black">Mã</th>
              <th class="pb-3 px-4 font-black">Gói</th>
              <th class="pb-3 px-4 font-black">Số tiền</th>
              <th class="pb-3 px-4 font-black">Trạng thái</th>
              <th class="pb-3 pl-4 font-black">Ngày</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in selectedUser.history" :key="item.id" class="border-b border-[var(--border)]/50">
              <td class="py-4 font-mono text-xs">{{ item.order_code }}</td>
              <td class="py-4 px-4">{{ item.plan_name || getPlanNameByAmount(item.amount) }}</td>
              <td class="py-4 px-4 font-bold">{{ formatPrice(item.amount) }}</td>
              <td class="py-4 px-4">
                <span class="inline-flex px-2 py-1 rounded-full text-[10px] font-black uppercase" :class="getStatusBadgeClass(item.status)">
                  {{ getStatusText(item.status) }}
                </span>
              </td>
              <td class="py-4 pl-4 text-xs text-[var(--muted)]">{{ formatDate(item.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </article>
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
    
    // Thuật toán gom nhóm người dùng
    const userMap = new Map()
    rawData.forEach(item => {
      const id = item.user_email || item.user_name || 'unknown'
      if (!userMap.has(id)) {
        userMap.set(id, { user_name: item.user_name, user_email: item.user_email, total_transactions: 0, total_amount: 0, history: [] })
      }
      const u = userMap.get(id)
      u.total_transactions += 1
      u.total_amount += Number(item.amount)
      u.history.push(item)
    })
    usersList.value = Array.from(userMap.values())
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

// Các hàm bổ trợ
const formatPrice = (v) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(v))
const formatDate = (d) => new Date(d).toLocaleDateString('vi-VN')
const getPlanNameByAmount = (a) => ({ 50000: 'Gói Plus', 120000: 'Gói Pro', 250000: 'Gói Ultra' }[a] || 'Gói khác')
const getStatusText = (s) => ({ pending: 'Đang xử lý', success: 'Thành công', failed: 'Thất bại' }[s] || s)
const getStatusBadgeClass = (s) => s === 'success' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-gray-500/10 text-gray-400'

onMounted(loadPayments)
</script>