<template>
  <section class="grid gap-6 page-fade-in">
    <Transition name="fade">
      <div v-if="isLoading" class="loading-bar"></div>
    </Transition>

    <!-- Header -->
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-xl backdrop-blur-xl">
      <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-violet-600/20 blur-[100px]"></div>
      <div class="relative z-10">
        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-violet-500">
          PAYMENT MANAGEMENT
        </p>
        <h1 class="mt-2 text-4xl font-black tracking-tighter text-white">
          Quản lý Tài chính
        </h1>
        <p class="mt-3 max-w-2xl text-sm leading-7 text-neutral-400">
          Quản lý toàn bộ giao dịch trên hệ thống, theo dõi doanh thu, xu hướng tăng trưởng và phân tích chi tiết từng khách hàng.
        </p>
      </div>
    </div>

    <!-- Stats Cards Grid — hàng 1 -->
    <div class="grid gap-4 sm:grid-cols-3">
      <div class="stat-card relative overflow-hidden rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-soft)] transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:border-emerald-500/30">
        <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-emerald-500/10 blur-xl"></div>
        <p class="text-[10px] font-black uppercase tracking-[0.15em] text-emerald-500">Tổng doanh thu</p>
        <p class="mt-2 text-2xl font-black text-white tabular-nums transition-opacity duration-300" :class="{ 'opacity-40': isLoading && !usersList.length }">{{ formatPrice(totalRevenue) }}</p>
        <span class="mt-1 block text-xs text-[var(--muted)]">Tổng số tiền thanh toán thành công</span>
      </div>

      <div class="stat-card relative overflow-hidden rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-soft)] transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:border-blue-500/30">
        <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-blue-500/10 blur-xl"></div>
        <p class="text-[10px] font-black uppercase tracking-[0.15em] text-blue-400">Giao dịch thành công</p>
        <p class="mt-2 text-2xl font-black text-white tabular-nums transition-opacity duration-300" :class="{ 'opacity-40': isLoading && !usersList.length }">{{ totalTransactionsCount }}</p>
        <span class="mt-1 block text-xs text-[var(--muted)]">Hóa đơn đã được xác nhận thanh toán</span>
      </div>

      <div class="stat-card relative overflow-hidden rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-soft)] transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:border-purple-500/30">
        <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-purple-500/10 blur-xl"></div>
        <p class="text-[10px] font-black uppercase tracking-[0.15em] text-purple-400">Khách hàng thanh toán</p>
        <p class="mt-2 text-2xl font-black text-white tabular-nums transition-opacity duration-300" :class="{ 'opacity-40': isLoading && !usersList.length }">{{ totalCustomersCount }}</p>
        <span class="mt-1 block text-xs text-[var(--muted)]">Số người dùng đã đăng ký mua gói</span>
      </div>
    </div>

    <!-- Stats Cards Grid — hàng 2: chỉ số phân tích sâu -->
    <div class="grid gap-4 sm:grid-cols-3">
      <div class="stat-card relative overflow-hidden rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-soft)] transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:border-amber-500/30">
        <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-amber-500/10 blur-xl"></div>
        <p class="text-[10px] font-black uppercase tracking-[0.15em] text-amber-400">Doanh thu tháng này</p>
        <p class="mt-2 text-2xl font-black text-white tabular-nums transition-opacity duration-300" :class="{ 'opacity-40': isLoading && !usersList.length }">{{ formatPrice(monthRevenue) }}</p>
        <span
          class="mt-1 inline-flex items-center gap-1 text-xs font-bold transition-colors"
          :class="monthGrowth >= 0 ? 'text-emerald-400' : 'text-rose-400'"
        >
          {{ monthGrowth >= 0 ? '▲' : '▼' }} {{ Math.abs(monthGrowth).toFixed(1) }}% so với tháng trước
        </span>
      </div>

      <div class="stat-card relative overflow-hidden rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-soft)] transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:border-cyan-500/30">
        <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-cyan-500/10 blur-xl"></div>
        <p class="text-[10px] font-black uppercase tracking-[0.15em] text-cyan-400">Giá trị đơn trung bình</p>
        <p class="mt-2 text-2xl font-black text-white tabular-nums transition-opacity duration-300" :class="{ 'opacity-40': isLoading && !usersList.length }">{{ formatPrice(averageOrderValue) }}</p>
        <span class="mt-1 block text-xs text-[var(--muted)]">Trung bình mỗi giao dịch thành công</span>
      </div>

      <div class="stat-card relative overflow-hidden rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-soft)] transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:border-rose-500/30">
        <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-rose-500/10 blur-xl"></div>
        <p class="text-[10px] font-black uppercase tracking-[0.15em] text-rose-400">Tỷ lệ thất bại / hoàn tiền</p>
        <p class="mt-2 text-2xl font-black text-white tabular-nums transition-opacity duration-300" :class="{ 'opacity-40': isLoading && !usersList.length }">{{ failureRate.toFixed(1) }}%</p>
        <span class="mt-1 block text-xs text-[var(--muted)]">{{ failedCount }} thất bại · {{ refundedCount }} hoàn tiền</span>
      </div>
    </div>

    <!-- Biểu đồ xu hướng doanh thu 14 ngày gần nhất -->
    <div class="bg-[var(--surface)] border border-[var(--border)] rounded-[2rem] p-6 shadow-xl backdrop-blur-xl">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-lg font-black text-[var(--text)]">Xu hướng doanh thu</h2>
          <p class="text-xs text-[var(--muted)] mt-1">14 ngày gần nhất, chỉ tính giao dịch thành công</p>
        </div>
        <div class="text-right">
          <p class="text-[10px] uppercase font-black tracking-wider text-[var(--muted)]">Cao nhất trong kỳ</p>
          <p class="text-sm font-black text-violet-400">{{ formatPrice(maxDailyRevenue) }}</p>
        </div>
      </div>

      <div v-if="dailyRevenue.length" class="flex items-end gap-2 h-40">
        <div
          v-for="(day, idx) in dailyRevenue"
          :key="day.date"
          class="group relative flex-1 flex flex-col items-center justify-end h-full"
        >
          <div class="absolute -top-7 opacity-0 group-hover:opacity-100 translate-y-1 group-hover:translate-y-0 transition-all duration-200 flex flex-col items-center px-2 py-1 rounded-lg bg-[var(--surface-soft)] border border-[var(--border)] text-[10px] font-bold text-[var(--text)] whitespace-nowrap z-10 pointer-events-none">
            {{ formatPrice(day.amount) }}
          </div>
          <div
            class="w-full rounded-t-md bg-gradient-to-t from-violet-600/40 to-violet-500 transition-[height,filter] duration-700 ease-out group-hover:from-violet-500 group-hover:to-fuchsia-400 group-hover:brightness-110"
            :style="{ height: chartMounted ? barHeight(day.amount) : '0%', transitionDelay: `${idx * 30}ms` }"
          ></div>
          <span class="mt-2 text-[9px] font-bold text-[var(--muted)]">{{ day.label }}</span>
        </div>
      </div>
      <div v-else class="py-10 text-center text-sm font-bold text-[var(--muted)]">
        Chưa có dữ liệu giao dịch để hiển thị biểu đồ.
      </div>
    </div>

    <!-- Main Section Card -->
    <div class="bg-[var(--surface)] border border-[var(--border)] rounded-[2rem] p-6 shadow-xl backdrop-blur-xl">

      <!-- Table Header & Filters -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
          <button
            v-if="selectedUser"
            @click="selectedUser = null"
            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-[var(--surface-soft)] font-bold text-xs border border-[var(--border)] text-[var(--text)] hover:translate-x-[-3px] transition-all"
          >
            ← Quay lại danh sách
          </button>
          <h2 v-else class="text-xl font-black text-[var(--text)]">Danh sách khách hàng</h2>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <div v-if="!selectedUser" class="relative min-w-[220px]">
            <input
              v-model="searchQuery"
              class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-2 text-xs font-semibold text-[var(--text)] outline-none focus:border-[var(--primary)] placeholder:text-[var(--muted)]"
              placeholder="🔍 Tìm khách hàng (tên, email)..."
            />
          </div>

          <select
            v-if="!selectedUser"
            v-model="sortBy"
            class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-2 text-xs font-bold text-[var(--text)] outline-none focus:border-[var(--primary)]"
          >
            <option value="amount_desc">Chi tiêu cao nhất</option>
            <option value="amount_asc">Chi tiêu thấp nhất</option>
            <option value="trans_desc">Giao dịch nhiều nhất</option>
            <option value="name_asc">Tên A → Z</option>
            <option value="recent">Giao dịch gần nhất</option>
          </select>

          <button
            v-if="!selectedUser"
            @click="exportCsv"
            class="flex items-center gap-2 px-4 py-2 rounded-2xl bg-[var(--surface-soft)] font-bold text-xs border border-[var(--border)] hover:border-emerald-500 hover:text-emerald-400 transition-all"
          >
            ⤓ Xuất CSV
          </button>

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

      <!-- Filter tabs theo trạng thái (chỉ hiển thị ở màn chi tiết khách hàng) -->
      <div v-if="selectedUser" class="flex flex-wrap items-center gap-2 mb-6">
        <button
          v-for="tab in statusTabs"
          :key="tab.value"
          @click="historyStatusFilter = tab.value"
          class="px-4 py-1.5 rounded-full text-[11px] font-black uppercase tracking-wide border transition-all"
          :class="historyStatusFilter === tab.value
            ? 'bg-[var(--primary)] text-white border-[var(--primary)]'
            : 'bg-[var(--surface-soft)] text-[var(--muted)] border-[var(--border)] hover:text-[var(--text)]'"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Banner phân tích chi tiết khách hàng -->
      <Transition name="fade-slide">
      <div v-if="selectedUser" class="mb-6 grid gap-4 lg:grid-cols-[1.3fr_1fr] items-stretch">
        <div class="flex items-center gap-4 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
          <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-[var(--primary)]/10 font-bold text-xl text-[var(--primary)] border border-[var(--primary)]/20">
            {{ (selectedUser.user_name || 'A').charAt(0).toUpperCase() }}
          </div>
          <div class="min-w-0">
            <h3 class="font-black text-white text-base truncate">{{ selectedUser.user_name || 'Khách hàng ẩn danh' }}</h3>
            <p class="text-xs text-[var(--muted)] font-mono truncate">{{ selectedUser.user_email }}</p>
            <span class="mt-1 inline-block px-2 py-0.5 rounded bg-violet-500/10 text-violet-400 text-[9px] font-black uppercase border border-violet-500/20">
              {{ selectedUser.role || 'free' }}
            </span>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3">
            <p class="text-[9px] uppercase font-black tracking-wider text-[var(--muted)]">Tổng chi tiêu</p>
            <p class="text-base font-black text-[var(--primary)]">{{ formatPrice(selectedUser.total_amount) }}</p>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3">
            <p class="text-[9px] uppercase font-black tracking-wider text-[var(--muted)]">Giao dịch thành công</p>
            <p class="text-base font-black text-white">{{ selectedUser.total_transactions }}</p>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3">
            <p class="text-[9px] uppercase font-black tracking-wider text-[var(--muted)]">Giá trị TB/đơn</p>
            <p class="text-base font-black text-white">{{ formatPrice(selectedUserAvgOrder) }}</p>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3">
            <p class="text-[9px] uppercase font-black tracking-wider text-[var(--muted)]">Kênh ưa thích</p>
            <p class="text-base font-black text-white">{{ selectedUserFavoriteChannel }}</p>
          </div>
          <div class="col-span-2 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 flex items-center justify-between">
            <div>
              <p class="text-[9px] uppercase font-black tracking-wider text-[var(--muted)]">Mua lần đầu</p>
              <p class="text-xs font-bold text-white">{{ formatDateTime(selectedUserFirstPurchase) }}</p>
            </div>
            <div class="text-right">
              <p class="text-[9px] uppercase font-black tracking-wider text-[var(--muted)]">Gần nhất</p>
              <p class="text-xs font-bold text-white">{{ formatDateTime(selectedUserLastPurchase) }}</p>
            </div>
          </div>
        </div>
      </div>
      </Transition>

      <!-- Tables -->
      <div class="overflow-x-auto">
      <Transition name="fade-slide" mode="out-in">
        <!-- Bảng danh sách khách hàng -->
        <table v-if="!selectedUser" key="list-table" class="w-full text-left border-collapse">
          <thead>
            <tr class="text-[var(--muted)] text-[10px] uppercase tracking-[0.2em] border-b border-[var(--border)]">
              <th class="pb-4 pl-4">Khách hàng</th>
              <th class="pb-4 px-6 text-center">Giao dịch thành công</th>
              <th class="pb-4 px-6">Tổng chi tiêu</th>
              <th class="pb-4 px-6">Giao dịch gần nhất</th>
              <th class="pb-4 pr-4 text-right">Thao tác</th>
            </tr>
          </thead>
          <TransitionGroup tag="tbody" name="row-fade" class="divide-y divide-[var(--border)]">
            <tr v-for="user in paginatedUsers" :key="user.user_email" class="hover:bg-[var(--surface-soft)]/50 transition-colors group">
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
              <td class="py-4 px-6 text-xs font-semibold text-[var(--muted)]">{{ formatDateTime(lastPurchaseOf(user)) }}</td>
              <td class="py-4 pr-4 text-right">
                <button
                  @click="selectedUser = user; historyStatusFilter = 'all'"
                  class="px-5 py-2 bg-[var(--surface-soft)] hover:bg-[var(--primary)] hover:text-white border border-[var(--border)] hover:border-[var(--primary)] rounded-xl font-bold text-xs transition-all shadow-md hover:-translate-y-0.5 active:scale-95"
                >
                  Chi tiết
                </button>
              </td>
            </tr>
            <tr v-if="filteredUsers.length === 0" key="empty-state">
              <td colspan="5" class="py-8 text-center text-sm font-bold text-[var(--muted)]">
                Không tìm thấy khách hàng nào phù hợp.
              </td>
            </tr>
          </TransitionGroup>
        </table>

        <!-- Bảng chi tiết lịch sử giao dịch của khách hàng đó -->
        <table v-else key="detail-table" class="w-full text-left border-collapse">
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
            <tr v-for="item in filteredHistory" :key="item.id" class="hover:bg-[var(--surface-soft)]/50 transition-colors">
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
            <tr v-if="filteredHistory.length === 0">
              <td colspan="6" class="py-8 text-center text-sm font-bold text-[var(--muted)]">
                Không có giao dịch nào ở trạng thái này.
              </td>
            </tr>
          </tbody>
        </table>
      </Transition>
      </div>

      <!-- Pagination (chỉ áp dụng cho danh sách khách hàng) -->
      <div v-if="!selectedUser && filteredUsers.length > pageSize" class="flex items-center justify-between mt-6 pt-4 border-t border-[var(--border)]">
        <p class="text-xs font-semibold text-[var(--muted)]">
          Hiển thị {{ (currentPage - 1) * pageSize + 1 }}–{{ Math.min(currentPage * pageSize, filteredUsers.length) }}
          trong tổng số {{ filteredUsers.length }} khách hàng
        </p>
        <div class="flex items-center gap-2">
          <button
            @click="currentPage > 1 && currentPage--"
            :disabled="currentPage === 1"
            class="px-3 py-1.5 rounded-lg bg-[var(--surface-soft)] border border-[var(--border)] text-xs font-bold text-[var(--text)] disabled:opacity-30 disabled:cursor-not-allowed hover:border-[var(--primary)] transition-all"
          >
            ← Trước
          </button>
          <span class="text-xs font-bold text-[var(--muted)] px-2">{{ currentPage }} / {{ totalPages }}</span>
          <button
            @click="currentPage < totalPages && currentPage++"
            :disabled="currentPage === totalPages"
            class="px-3 py-1.5 rounded-lg bg-[var(--surface-soft)] border border-[var(--border)] text-xs font-bold text-[var(--text)] disabled:opacity-30 disabled:cursor-not-allowed hover:border-[var(--primary)] transition-all"
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
const chartMounted = ref(false)

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

    // sắp xếp lịch sử mỗi khách hàng theo thời gian mới nhất trước
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

    chartMounted.value = false
    requestAnimationFrame(() => setTimeout(() => { chartMounted.value = true }, 50))
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

// ---------- Phân tích & thống kê nâng cao ----------

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

// Doanh thu theo ngày, 14 ngày gần nhất
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
  const pct = Math.max(4, (amount / maxDailyRevenue.value) * 100)
  return `${pct}%`
}

// ---------- Danh sách khách hàng: lọc, sắp xếp, phân trang ----------

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

// ---------- Phân tích chi tiết khách hàng đang chọn ----------

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

const selectedUserFirstPurchase = computed(() => {
  if (!selectedUser.value || !selectedUser.value.history.length) return null
  return selectedUser.value.history[selectedUser.value.history.length - 1].created_at
})

const selectedUserLastPurchase = computed(() => {
  if (!selectedUser.value || !selectedUser.value.history.length) return null
  return selectedUser.value.history[0].created_at
})

// ---------- Tiện ích ----------

const formatPrice = (v) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(v) || 0)
const getStatusText = (s) => ({ pending: 'Chờ xử lý', success: 'Thành công', failed: 'Thất bại', refunded: 'Hoàn tiền' }[s] || s)

const getStatusBadgeClass = (s) => ({
  success: 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-[0_0_12px_rgba(16,185,129,0.1)]',
  pending: 'bg-amber-500/10 text-amber-400 border border-amber-500/20 shadow-[0_0_12px_rgba(245,158,11,0.1)]',
  failed: 'bg-rose-500/10 text-rose-400 border border-rose-500/20 shadow-[0_0_12px_rgba(244,63,94,0.1)]',
  refunded: 'bg-slate-500/10 text-slate-400 border border-slate-500/20'
}[s] || 'bg-slate-500/10 text-slate-400')

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

<style scoped>
/* Trang mờ dần + trượt nhẹ khi vào lần đầu */
.page-fade-in {
  animation: pageFadeIn 0.5s ease both;
}
@keyframes pageFadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Các thẻ thống kê xuất hiện lần lượt (stagger) */
.stat-card {
  animation: cardFadeUp 0.5s ease both;
}
.stat-card:nth-child(1) { animation-delay: 0.05s; }
.stat-card:nth-child(2) { animation-delay: 0.12s; }
.stat-card:nth-child(3) { animation-delay: 0.19s; }
@keyframes cardFadeUp {
  from { opacity: 0; transform: translateY(14px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Thanh loading mảnh khi đang làm mới dữ liệu */
.loading-bar {
  grid-column: 1 / -1;
  height: 3px;
  border-radius: 999px;
  background: linear-gradient(90deg, transparent, var(--primary, #8b5cf6), transparent);
  background-size: 200% 100%;
  animation: loadingSweep 1.1s linear infinite;
}
@keyframes loadingSweep {
  from { background-position: 200% 0; }
  to { background-position: -200% 0; }
}

/* Chuyển cảnh mờ + trượt giữa danh sách và chi tiết khách hàng */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(10px);
}
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
.fade-slide-leave-active {
  position: absolute;
}

/* Fade đơn giản cho thanh loading */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Các dòng trong bảng khách hàng mờ/trượt khi lọc, sắp xếp hoặc phân trang */
.row-fade-enter-active,
.row-fade-leave-active,
.row-fade-move {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.row-fade-enter-from {
  opacity: 0;
  transform: translateX(-6px);
}
.row-fade-leave-to {
  opacity: 0;
  transform: translateX(-6px);
}

/* Icon làm mới quay mượt hơn animate-spin mặc định */
.animate-spin {
  animation: spinSmooth 0.8s linear infinite;
}
@keyframes spinSmooth {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@media (prefers-reduced-motion: reduce) {
  .page-fade-in,
  .stat-card,
  .loading-bar,
  .animate-spin {
    animation: none !important;
  }
  .fade-slide-enter-active,
  .fade-slide-leave-active,
  .fade-enter-active,
  .fade-leave-active,
  .row-fade-enter-active,
  .row-fade-leave-active,
  .row-fade-move {
    transition: none !important;
  }
}
</style>