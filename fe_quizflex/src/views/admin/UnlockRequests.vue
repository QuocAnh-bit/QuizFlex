<template>
  <section class="max-w-6xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Kiểm duyệt tài khoản</p>
        <h1 class="text-3xl font-black tracking-[-0.04em] text-[var(--text)]">Danh sách đơn kháng cáo</h1>
        <p class="mt-1 text-sm text-slate-600">Xem xét và xử lý các yêu cầu mở khóa tài khoản từ người dùng.</p>
      </div>
      <div class="flex items-center gap-2.5">
        <select v-model="statusFilter" class="field text-xs" @change="loadRequests">
          <option value="all">Tất cả trạng thái</option>
          <option value="pending">Chờ duyệt (Pending)</option>
          <option value="approved">Đã duyệt (Approved)</option>
          <option value="rejected">Đã từ chối (Rejected)</option>
        </select>
        <button class="btn-secondary text-xs px-3.5 py-1.5" type="button" @click="loadRequests">
          🔄 Tải lại
        </button>
      </div>
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700">
      {{ errorMessage }}
    </div>

    <!-- Master-Detail Grid -->
    <div class="grid gap-6 lg:grid-cols-[1fr_1.2fr]">
      <!-- Left: Requests List -->
      <div class="card p-5 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h2 class="text-sm font-bold text-slate-900">Danh sách đơn kháng cáo</h2>
          <span class="rounded bg-purple-50 text-[#7C3AED] px-2 py-0.5 text-xs font-bold">
            {{ filteredRequests.length }} đơn
          </span>
        </div>

        <div v-if="isLoading" class="py-10 text-center text-xs text-slate-400">Đang tải danh sách...</div>
        <div v-else-if="filteredRequests.length === 0" class="py-10 text-center text-xs text-slate-400">Chưa có kháng cáo nào ở trạng thái này.</div>
        <div v-else class="space-y-2 max-h-[500px] overflow-y-auto pr-1">
          <button
            v-for="item in filteredRequests"
            :key="item.id"
            type="button"
            class="flex w-full items-start justify-between rounded-xl border p-3.5 text-left transition"
            :class="selectedRequest?.id === item.id ? 'border-[#7C3AED] bg-purple-50/50 shadow-sm' : 'border-slate-100 bg-slate-50 hover:border-slate-200'"
            @click="selectRequest(item)"
          >
            <div>
              <p class="font-bold text-xs text-slate-900">{{ item.user?.name || 'Không rõ' }}</p>
              <p class="text-[11px] text-slate-400">{{ item.user?.email || '' }}</p>
              <span class="text-[10px] text-slate-400 block pt-1 font-medium">{{ formatDate(item.created_at) }}</span>
            </div>
            <span class="rounded px-2 py-0.5 text-[10px] font-bold uppercase" :class="statusBadgeClass(item.status)">
              {{ item.status }}
            </span>
          </button>
        </div>
      </div>

      <!-- Right: Request Detail -->
      <div class="card p-6 space-y-5">
        <div v-if="!selectedRequest" class="grid h-full min-h-[300px] place-items-center text-xs text-slate-400">
          Chọn một kháng cáo từ danh sách bên trái để xem chi tiết.
        </div>
        <div v-else class="space-y-4">
          <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <div>
              <span class="text-[10px] font-bold uppercase text-[#7C3AED]">Chi tiết kháng cáo</span>
              <h3 class="text-base font-bold text-slate-900">{{ selectedRequest.user?.name || 'Không rõ' }}</h3>
            </div>
            <span class="rounded px-2.5 py-0.5 text-xs font-bold uppercase" :class="statusBadgeClass(selectedRequest.status)">
              {{ selectedRequest.status }}
            </span>
          </div>

          <div class="grid grid-cols-2 gap-3 text-xs">
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
              <span class="text-[10px] font-bold uppercase text-slate-400 block">Thông tin User</span>
              <b class="text-slate-900 block mt-0.5">{{ selectedRequest.user?.name }}</b>
              <span class="text-slate-400 text-[11px] block">{{ selectedRequest.user?.email }}</span>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
              <span class="text-[10px] font-bold uppercase text-slate-400 block">Lý do bị khóa</span>
              <b class="text-slate-900 block mt-0.5">{{ selectedRequest.locked_reason || 'Không có thông tin' }}</b>
              <span class="text-slate-400 text-[11px] block">Khóa lúc: {{ formatDate(selectedRequest.locked_at) }}</span>
            </div>
          </div>

          <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 space-y-1.5 text-xs">
            <span class="text-[10px] font-bold uppercase text-slate-400">Nội dung người dùng gửi:</span>
            <p class="text-slate-800 leading-relaxed text-xs">{{ selectedRequest.message }}</p>
          </div>

          <div v-if="selectedRequest.status === 'pending'" class="space-y-3 pt-2 border-t border-slate-100 text-xs">
            <div class="space-y-1">
              <label class="font-bold text-slate-700 block">Ghi chú phản hồi của Admin</label>
              <textarea 
                v-model="adminNote" 
                rows="3" 
                maxlength="1000" 
                class="field text-xs resize-none" 
                placeholder="Nhập ghi chú phản hồi (bắt buộc khi từ chối)..."
              ></textarea>
              <span v-if="adminNoteError" class="text-xs font-bold text-red-600 block">{{ adminNoteError }}</span>
            </div>
            <div class="flex items-center justify-end gap-2 pt-1">
              <button class="btn-secondary text-xs px-3.5 py-1.5 text-red-700 font-bold" type="button" :disabled="isActionLoading" @click="rejectRequest">
                Từ chối kháng cáo
              </button>
              <button class="btn-primary text-xs px-4 py-1.5" type="button" :disabled="isActionLoading" @click="approveRequest">
                Duyệt & Mở khóa
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { useAppLoading } from '@/composables/useAppLoading'
const { beginTask, endTask } = useAppLoading()

import { computed, onMounted, ref } from 'vue'
import { unlockRequestsApi } from '@/services/api'

const requests = ref([])
const selectedRequest = ref(null)
const statusFilter = ref('all')
const adminNote = ref('')
const errorMessage = ref('')
const isLoading = ref(false)
const isActionLoading = ref(false)
const adminNoteError = ref('')

const filteredRequests = computed(() => {
  if (statusFilter.value === 'all') return requests.value
  return requests.value.filter((item) => item.status === statusFilter.value)
})

const loadRequests = async () => {
    beginTask()
    try {
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
    } finally {
      endTask()
    }
  }

const selectRequest = async (item) => {
  selectedRequest.value = item
  adminNote.value = ''
  adminNoteError.value = ''
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

  const trimmedNote = adminNote.value.trim()
  if (trimmedNote.length > 1000) {
    adminNoteError.value = 'Ghi chú tối đa 1000 ký tự.'
    return
  }
  adminNoteError.value = ''

  isActionLoading.value = true
  try {
    await unlockRequestsApi.approve(selectedRequest.value.id, { admin_note: trimmedNote })
    adminNote.value = ''
    await loadRequests()
  } catch (error) {
    errorMessage.value = error.message || 'Không thể duyệt kháng cáo.'
  } finally {
    isActionLoading.value = false
  }
}

const rejectRequest = async () => {
  if (!selectedRequest.value) return

  const trimmedNote = adminNote.value.trim()
  if (!trimmedNote) {
    adminNoteError.value = 'Vui lòng nhập lý do từ chối để người dùng biết vì sao kháng cáo bị từ chối.'
    return
  }
  if (trimmedNote.length > 1000) {
    adminNoteError.value = 'Ghi chú tối đa 1000 ký tự.'
    return
  }
  adminNoteError.value = ''

  isActionLoading.value = true
  try {
    await unlockRequestsApi.reject(selectedRequest.value.id, { admin_note: trimmedNote })
    adminNote.value = ''
    await loadRequests()
  } catch (error) {
    errorMessage.value = error.message || 'Không thể từ chối kháng cáo.'
  } finally {
    isActionLoading.value = false
  }
}

const formatDate = (value) => {
  if (!value) return '-'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString('vi-VN')
}

const statusBadgeClass = (status) => ({
  pending: 'bg-amber-100 text-amber-800',
  approved: 'bg-emerald-100 text-emerald-800',
  rejected: 'bg-red-100 text-red-800',
}[status] || 'bg-slate-100 text-slate-500')

onMounted(loadRequests)
</script>
