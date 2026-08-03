<template>
  <section class="space-y-6">
    <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Kháng cáo tài khoản</p>
          <h1 class="mt-2 text-3xl font-black tracking-[-0.05em] text-[var(--text)]">Danh sách kháng cáo</h1>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <select v-model="statusFilter" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-2 text-sm font-bold text-[var(--text)] outline-none">
            <option value="all">Tất cả</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </select>
          <button class="btn-ghost" type="button" @click="loadRequests">Tải lại</button>
        </div>
      </div>
    </div>

    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">
      {{ errorMessage }}
    </div>

    <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
      <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-black text-[var(--text)]">Danh sách kháng cáo</h2>
          <span class="rounded-full border border-[var(--border)] bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--primary)]">
            {{ filteredRequests.length }} mục
          </span>
        </div>

        <div v-if="isLoading" class="mt-6 text-sm font-semibold text-[var(--muted)]">Đang tải...</div>
        <div v-else-if="filteredRequests.length === 0" class="mt-6 text-sm font-semibold text-[var(--muted)]">Chưa có kháng cáo nào.</div>
        <div v-else class="mt-6 space-y-3">
          <button
            v-for="item in filteredRequests"
            :key="item.id"
            type="button"
            class="flex w-full items-start justify-between rounded-[1.4rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4 text-left transition hover:border-[var(--border-strong)]"
            @click="selectRequest(item)"
          >
            <div>
              <p class="font-black text-[var(--text)]">{{ item.user?.name || 'Không rõ' }}</p>
              <p class="mt-1 text-sm font-semibold text-[var(--muted)]">{{ item.user?.email || '' }}</p>
              <p class="mt-2 text-xs font-bold uppercase tracking-[0.2em] text-[var(--primary)]">{{ formatDate(item.created_at) }}</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-black" :class="statusBadgeClass(item.status)">{{ item.status }}</span>
          </button>
        </div>
      </div>

      <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <div v-if="!selectedRequest" class="grid h-full min-h-[280px] place-items-center text-sm font-semibold text-[var(--muted)]">
          Chọn một kháng cáo để xem chi tiết.
        </div>
        <div v-else class="space-y-6">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Chi tiết kháng cáo</p>
            <h2 class="mt-2 text-2xl font-black text-[var(--text)]">{{ selectedRequest.user?.name || 'Không rõ' }}</h2>
          </div>

          <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <h3 class="text-lg font-black text-[var(--text)]">Thông tin User</h3>
            <div class="mt-3 space-y-2 text-sm text-[var(--muted)]">
              <p><span class="font-black text-[var(--text)]">Tên:</span> {{ selectedRequest.user?.name || 'Không rõ' }}</p>
              <p><span class="font-black text-[var(--text)]">Email:</span> {{ selectedRequest.user?.email || 'Không rõ' }}</p>
              <p><span class="font-black text-[var(--text)]">Role:</span> {{ selectedRequest.user?.role || 'Không rõ' }}</p>
            </div>
          </div>

          <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <h3 class="text-lg font-black text-[var(--text)]">Thông tin khóa</h3>
            <div class="mt-3 space-y-2 text-sm text-[var(--muted)]">
              <p><span class="font-black text-[var(--text)]">Lý do khóa:</span> {{ selectedRequest.locked_reason || 'Không có thông tin' }}</p>
              <p><span class="font-black text-[var(--text)]">Ngày khóa:</span> {{ selectedRequest.locked_at || 'Không có thông tin' }}</p>
            </div>
          </div>

          <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <h3 class="text-lg font-black text-[var(--text)]">Nội dung kháng cáo</h3>
            <p class="mt-3 text-sm leading-7 text-[var(--muted)]">{{ selectedRequest.message }}</p>
          </div>

          <div v-if="selectedRequest.status === 'pending'" class="space-y-4">
            <div>
              <label class="text-sm font-black text-[var(--text)]" for="admin-note">Admin Note</label>
              <textarea id="admin-note" v-model="adminNote" rows="4" class="mt-2 w-full rounded-[1.25rem] border border-[var(--border)] bg-[var(--surface)] px-4 py-3 text-sm font-medium text-[var(--text)] outline-none transition focus:border-[var(--primary)]" placeholder="Nhập ghi chú..."></textarea>
            </div>
            <div class="flex flex-wrap gap-3">
              <button class="btn-primary" type="button" :disabled="isActionLoading" @click="approveRequest">Duyệt</button>
              <button class="btn-ghost" type="button" :disabled="isActionLoading" @click="rejectRequest">Từ chối</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { unlockRequestsApi } from '@/services/api'

const requests = ref([])
const selectedRequest = ref(null)
const statusFilter = ref('all')
const adminNote = ref('')
const errorMessage = ref('')
const isLoading = ref(false)
const isActionLoading = ref(false)

const filteredRequests = computed(() => {
  if (statusFilter.value === 'all') return requests.value
  return requests.value.filter((item) => item.status === statusFilter.value)
})

const loadRequests = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const payload = await unlockRequestsApi.adminList({ status: statusFilter.value })
    requests.value = payload?.data || payload || []
    if (requests.value.length) {
      selectedRequest.value = requests.value[0]
    } else {
      selectedRequest.value = null
    }
  } catch (error) {
    errorMessage.value = error.message || 'Không tải được danh sách kháng cáo.'
  } finally {
    isLoading.value = false
  }
}

const selectRequest = async (item) => {
  selectedRequest.value = item
  adminNote.value = ''
  try {
    const payload = await unlockRequestsApi.adminGet(item.id)
    const data = payload?.data || payload || null
    if (data) {
      selectedRequest.value = { ...item, ...data }
    }
  } catch {
    selectedRequest.value = item
  }
}

const approveRequest = async () => {
  if (!selectedRequest.value) return
  isActionLoading.value = true
  try {
    await unlockRequestsApi.approve(selectedRequest.value.id, { admin_note: adminNote.value })
    await loadRequests()
  } catch (error) {
    errorMessage.value = error.message || 'Không thể duyệt kháng cáo.'
  } finally {
    isActionLoading.value = false
  }
}

const rejectRequest = async () => {
  if (!selectedRequest.value) return
  isActionLoading.value = true
  try {
    await unlockRequestsApi.reject(selectedRequest.value.id, { admin_note: adminNote.value })
    await loadRequests()
  } catch (error) {
    errorMessage.value = error.message || 'Không thể từ chối kháng cáo.'
  } finally {
    isActionLoading.value = false
  }
}

const formatDate = (value) => {
  if (!value) return 'Không rõ'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString('vi-VN')
}

const statusBadgeClass = (status) => ({
  pending: 'border-amber-500/25 bg-amber-500/10 text-amber-300',
  approved: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-300',
  rejected: 'border-rose-500/25 bg-rose-500/10 text-rose-300',
}[status] || 'border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)]')

onMounted(loadRequests)
</script>
