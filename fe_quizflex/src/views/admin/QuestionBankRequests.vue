<template>
  <section class="space-y-6">
    <!-- Header -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div>
          <div class="inline-flex items-center gap-2 rounded-md border border-purple-200 bg-purple-50 px-3 py-1 text-xs font-medium text-purple-700">
            <CheckCircle class="h-3.5 w-3.5" />
            <span>Bank Moderation</span>
            <span class="text-purple-400">•</span>
            <span>Duyệt câu hỏi vào Ngân hàng</span>
          </div>
          <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
            Yêu cầu duyệt vào Ngân hàng câu hỏi
          </h1>
          <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-500">
            Xem xét, thẩm định nội dung và phê duyệt các câu hỏi do người dùng đóng góp để đưa vào Ngân hàng câu hỏi chuẩn hoá dùng chung.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-center">
            <span class="text-[10px] font-medium uppercase text-slate-500">Chờ duyệt</span>
            <p class="text-xl font-bold text-amber-600">{{ stats.pending }}</p>
          </div>
          <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-center">
            <span class="text-[10px] font-medium uppercase text-slate-500">Đã duyệt</span>
            <p class="text-xl font-bold text-emerald-600">{{ stats.approved }}</p>
          </div>
          <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-center">
            <span class="text-[10px] font-medium uppercase text-slate-500">Bị từ chối</span>
            <p class="text-xl font-bold text-rose-600">{{ stats.rejected }}</p>
          </div>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 cursor-pointer"
            @click="loadRequests(false)"
          >
            <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': isLoading }" />
            <span>Làm mới</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Filters Bar -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <!-- Search -->
        <div class="relative sm:col-span-2">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
          <input
            v-model="filters.search"
            type="text"
            placeholder="Tìm theo nội dung, tác giả..."
            class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-4 py-2 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:bg-white"
            @keyup.enter="loadRequests(true)"
          />
        </div>

        <!-- Status Filter -->
        <div>
          <select
            v-model="filters.status"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 outline-none focus:border-[#7C3AED] focus:bg-white cursor-pointer"
            @change="loadRequests(true)"
          >
            <option value="pending">Chờ duyệt (Pending)</option>
            <option value="approved">Đã duyệt (Approved)</option>
            <option value="rejected">Đã từ chối (Rejected)</option>
            <option value="all">Tất cả trạng thái</option>
          </select>
        </div>

        <!-- Subject Filter -->
        <div>
          <select
            v-model="filters.subject_id"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 outline-none focus:border-[#7C3AED] focus:bg-white cursor-pointer"
            @change="loadRequests(true)"
          >
            <option value="">Tất cả môn học</option>
            <option v-for="sub in allSubjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
          </select>
        </div>

        <!-- Difficulty Filter -->
        <div>
          <select
            v-model="filters.difficulty"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 outline-none focus:border-[#7C3AED] focus:bg-white cursor-pointer"
            @change="loadRequests(true)"
          >
            <option value="">Tất cả độ khó</option>
            <option value="easy">Dễ (Nhận biết)</option>
            <option value="medium">Vừa (Thông hiểu)</option>
            <option value="hard">Khó (Vận dụng)</option>
          </select>
        </div>
      </div>

      <!-- Active Filters Reset -->
      <div v-if="hasActiveFilters" class="flex items-center justify-between border-t border-slate-100 pt-3 text-xs">
        <span class="text-slate-500">Đang áp dụng bộ lọc</span>
        <button
          type="button"
          class="font-bold text-[#7C3AED] hover:underline cursor-pointer"
          @click="resetFilters"
        >
          Đặt lại bộ lọc
        </button>
      </div>
    </div>

    <!-- Bulk Action Toolbar -->
    <div
      v-if="selectedIds.length > 0"
      class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-purple-200 bg-purple-50 p-4 shadow-sm"
    >
      <div class="flex items-center gap-2">
        <span class="grid h-6 w-6 place-items-center rounded-md bg-[#7C3AED] text-xs font-bold text-white">
          {{ selectedIds.length }}
        </span>
        <span class="text-xs font-bold text-purple-900">
          Đang chọn {{ selectedIds.length }} câu hỏi
        </span>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition disabled:opacity-50 cursor-pointer"
          :disabled="isProcessingBulk"
          @click="handleBulkApprove"
        >
          <Check class="h-3.5 w-3.5" />
          <span>Duyệt hàng loạt ({{ selectedIds.length }})</span>
        </button>

        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-3.5 py-1.5 text-xs font-bold text-rose-600 shadow-sm hover:bg-rose-50 transition disabled:opacity-50 cursor-pointer"
          :disabled="isProcessingBulk"
          @click="openBulkRejectModal"
        >
          <X class="h-3.5 w-3.5" />
          <span>Từ chối hàng loạt</span>
        </button>
      </div>
    </div>

    <!-- Error state -->
    <div v-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs font-bold text-rose-700">
      {{ errorMessage }}
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="rounded-2xl border border-slate-200 bg-white p-12 text-center text-sm font-medium text-slate-400">
      <RefreshCw class="mx-auto mb-3 h-6 w-6 animate-spin text-[#7C3AED]" />
      <span>Đang tải danh sách yêu cầu duyệt...</span>
    </div>

    <!-- Empty State -->
    <div v-else-if="requests.length === 0" class="rounded-2xl border border-slate-200 bg-white p-12 text-center">
      <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-slate-400">
        <Inbox class="h-6 w-6" />
      </div>
      <h3 class="text-base font-bold text-slate-900">Không có yêu cầu duyệt nào</h3>
      <p class="mt-1 text-xs text-slate-500">
        {{ filters.status === 'pending' ? 'Hiện tại không có câu hỏi nào đang chờ duyệt vào Ngân hàng.' : 'Không tìm thấy câu hỏi phù hợp với bộ lọc hiện tại.' }}
      </p>
    </div>

    <!-- Questions List -->
    <div v-else class="space-y-4">
      <article
        v-for="item in requests"
        :key="item.id"
        class="rounded-2xl border bg-white p-5 shadow-sm transition hover:border-slate-300"
        :class="selectedIds.includes(item.id) ? 'border-[#7C3AED] bg-purple-50/20' : 'border-slate-200'"
      >
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div class="flex items-start gap-3 flex-1 min-w-0">
            <!-- Select Checkbox -->
            <input
              type="checkbox"
              :checked="selectedIds.includes(item.id)"
              class="h-4 w-4 rounded accent-[#7C3AED] mt-1 shrink-0 cursor-pointer"
              @change="toggleSelect(item.id)"
            />

            <div class="flex-1 min-w-0 space-y-2.5">
              <!-- Meta Badges -->
              <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-bold text-slate-700">
                  #{{ item.id }}
                </span>

                <!-- Status Badge -->
                <span
                  class="rounded-md px-2 py-0.5 text-[11px] font-bold"
                  :class="getStatusBadgeClass(item.bank_submission_status)"
                >
                  {{ getStatusLabel(item.bank_submission_status) }}
                </span>

                <!-- Difficulty Badge -->
                <span
                  class="rounded-md px-2 py-0.5 text-[11px] font-bold"
                  :class="{
                    'bg-emerald-50 text-emerald-700': item.difficulty === 'easy',
                    'bg-amber-50 text-amber-700': item.difficulty === 'medium',
                    'bg-rose-50 text-rose-700': item.difficulty === 'hard'
                  }"
                >
                  {{ getDifficultyLabel(item.difficulty) }}
                </span>

                <!-- Subject / Grade -->
                <span v-if="item.subject_name" class="rounded-md bg-purple-50 px-2 py-0.5 text-[11px] font-bold text-purple-700">
                  {{ item.subject_name }}
                </span>
                <span v-if="item.grade_name" class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">
                  {{ item.grade_name }}
                </span>
                <span v-if="item.topic_name" class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                  Chủ đề: {{ item.topic_name }}
                </span>

                <!-- Author info -->
                <span class="ml-auto text-xs text-slate-500 font-medium">
                  Tác giả: <strong class="text-slate-800">{{ item.author_name || 'Vô danh' }}</strong>
                  <span v-if="item.author_email" class="text-slate-400 ml-1">({{ item.author_email }})</span>
                </span>
              </div>

              <!-- Rejection Note if Rejected -->
              <div
                v-if="item.bank_submission_status === 'rejected' && item.bank_submission_note"
                class="rounded-xl border border-rose-200 bg-rose-50 p-2.5 text-xs text-rose-800 font-medium"
              >
                <strong>Lý do từ chối:</strong> {{ item.bank_submission_note }}
              </div>

              <!-- Question Content -->
              <h3 class="text-sm font-bold text-slate-900 leading-snug">
                {{ item.content || item.text }}
              </h3>

              <!-- Answers Grid with full Answer Key visibility (Admin always sees correct answer) -->
              <div v-if="item.answers && item.answers.length > 0" class="grid gap-2 sm:grid-cols-2 pt-1">
                <div
                  v-for="ans in item.answers"
                  :key="ans.id"
                  class="flex items-center gap-2.5 rounded-xl border px-3 py-2 text-xs font-semibold"
                  :class="ans.is_correct
                    ? 'border-emerald-300 bg-emerald-50 text-emerald-800 shadow-sm'
                    : 'border-slate-200 bg-slate-50 text-slate-600'"
                >
                  <span
                    class="grid h-5 w-5 shrink-0 place-items-center rounded-md text-[10px] font-bold"
                    :class="ans.is_correct ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700'"
                  >
                    {{ ans.key }}
                  </span>

                  <span class="truncate flex-1">{{ ans.text || ans.content }}</span>

                  <span v-if="ans.is_correct" class="ml-auto inline-flex shrink-0 items-center gap-1 text-[11px] font-bold text-emerald-600">
                    <Check class="h-3 w-3" :stroke-width="3" />
                    <span>Đáp án đúng</span>
                  </span>
                </div>
              </div>

              <!-- Submission Timestamp -->
              <div class="text-[11px] text-slate-400 font-medium pt-1">
                Gửi duyệt lúc: {{ formatDate(item.bank_submission_at || item.created_at) }}
              </div>
            </div>
          </div>

          <!-- Single Action Buttons -->
          <div class="flex items-center gap-2 shrink-0 self-end lg:self-start pt-2 lg:pt-0">
            <button
              v-if="item.bank_submission_status !== 'approved'"
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition cursor-pointer"
              title="Phê duyệt câu hỏi này vào Ngân hàng"
              @click="approveSingle(item.id)"
            >
              <Check class="h-3.5 w-3.5" />
              <span>Duyệt</span>
            </button>

            <button
              v-if="item.bank_submission_status !== 'rejected'"
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 transition cursor-pointer"
              title="Từ chối câu hỏi này"
              @click="openRejectModal(item.id)"
            >
              <X class="h-3.5 w-3.5" />
              <span>Từ chối</span>
            </button>
          </div>
        </div>
      </article>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 shadow-sm text-xs font-medium text-slate-600">
        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 hover:bg-slate-50 disabled:opacity-40 cursor-pointer"
          :disabled="pagination.current_page <= 1"
          @click="changePage(pagination.current_page - 1)"
        >
          <ChevronLeft class="h-4 w-4" />
          <span>Trang trước</span>
        </button>

        <span>Trang {{ pagination.current_page }} / {{ pagination.last_page }} (Tổng: {{ pagination.total }} câu hỏi)</span>

        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 hover:bg-slate-50 disabled:opacity-40 cursor-pointer"
          :disabled="pagination.current_page >= pagination.last_page"
          @click="changePage(pagination.current_page + 1)"
        >
          <span>Trang sau</span>
          <ChevronRight class="h-4 w-4" />
        </button>
      </div>
    </div>

    <!-- REJECT MODAL -->
    <div
      v-if="isRejectModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
      @click.self="isRejectModalOpen = false"
    >
      <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2 text-rose-600 font-bold text-sm">
            <XCircle class="h-4 w-4" />
            <span>{{ isBulkReject ? `Từ chối ${selectedIds.length} câu hỏi` : `Từ chối câu hỏi #${rejectTargetId}` }}</span>
          </div>
          <button type="button" class="text-slate-400 hover:text-slate-700 cursor-pointer" @click="isRejectModalOpen = false">
            <X class="h-4 w-4" />
          </button>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">
            Lý do từ chối kiểm duyệt *
          </label>
          <textarea
            v-model="rejectionNote"
            rows="4"
            required
            placeholder="Nhập lý do câu hỏi chưa đạt chuẩn (vd: sai đáp án, nội dung trùng lặp, thiếu thông tin môn học...)"
            class="w-full rounded-xl border border-slate-200 p-3 text-xs font-medium text-slate-900 outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button
            type="button"
            class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 cursor-pointer"
            @click="isRejectModalOpen = false"
          >
            Hủy
          </button>

          <button
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-700 transition disabled:opacity-50 cursor-pointer"
            :disabled="!rejectionNote.trim() || isSubmittingReject"
            @click="confirmReject"
          >
            <span>{{ isSubmittingReject ? 'Đang xử lý...' : 'Xác nhận từ chối' }}</span>
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject } from 'vue'
import {
  CheckCircle,
  RefreshCw,
  Search,
  Check,
  X,
  XCircle,
  Inbox,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'
import { adminBankRequestsApi, taxonomyApi } from '@/services/api'

const showToast = inject('showToast')

const requests = ref([])
const selectedIds = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const allSubjects = ref([])

const stats = reactive({
  pending: 0,
  approved: 0,
  rejected: 0,
  total: 0,
})

const filters = reactive({
  search: '',
  status: 'pending',
  subject_id: '',
  difficulty: '',
})

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
})

// Modal reject state
const isRejectModalOpen = ref(false)
const isBulkReject = ref(false)
const rejectTargetId = ref(null)
const rejectionNote = ref('')
const isSubmittingReject = ref(false)
const isProcessingBulk = ref(false)

const hasActiveFilters = computed(() => {
  return Boolean(filters.search || filters.status !== 'pending' || filters.subject_id || filters.difficulty)
})

const loadRequests = async (resetPage = false) => {
  if (resetPage) {
    pagination.current_page = 1
  }
  isLoading.value = true
  errorMessage.value = ''
  try {
    const params = {
      page: pagination.current_page,
      per_page: pagination.per_page,
      status: filters.status,
      search: filters.search.trim() || undefined,
      subject_id: filters.subject_id || undefined,
      difficulty: filters.difficulty || undefined,
    }

    const res = await adminBankRequestsApi.fetchRequests(params)
    requests.value = res.items || []
    pagination.total = res.total || 0
    pagination.current_page = res.currentPage || 1
    pagination.last_page = res.lastPage || 1
    pagination.per_page = res.perPage || 15

    if (res.stats) {
      stats.pending = res.stats.pending || 0
      stats.approved = res.stats.approved || 0
      stats.rejected = res.stats.rejected || 0
      stats.total = res.stats.total || 0
    }
  } catch (e) {
    errorMessage.value = `Không thể tải danh sách: ${e.message}`
  } finally {
    isLoading.value = false
  }
}

const fetchTaxonomy = async () => {
  try {
    const data = await taxonomyApi.tree()
    const payload = data?.education_levels ? data : (data?.data ?? data)
    if (payload) {
      allSubjects.value = payload.subjects || []
    }
  } catch (e) {
    console.error('Không tải được taxonomy:', e)
  }
}

const resetFilters = () => {
  filters.search = ''
  filters.status = 'pending'
  filters.subject_id = ''
  filters.difficulty = ''
  loadRequests(true)
}

const changePage = (page) => {
  pagination.current_page = page
  loadRequests()
}

const toggleSelect = (id) => {
  const idx = selectedIds.value.indexOf(id)
  if (idx > -1) {
    selectedIds.value.splice(idx, 1)
  } else {
    selectedIds.value.push(id)
  }
}

const approveSingle = async (id) => {
  try {
    const res = await adminBankRequestsApi.approve(id)
    if (showToast) {
      showToast(res?.message || `Đã phê duyệt câu hỏi #${id} vào Ngân hàng!`, 'success')
    }
    await loadRequests()
  } catch (e) {
    if (showToast) {
      showToast(`Không thể duyệt: ${e.message}`, 'error')
    }
  }
}

const openRejectModal = (id) => {
  isBulkReject.value = false
  rejectTargetId.value = id
  rejectionNote.value = ''
  isRejectModalOpen.value = true
}

const openBulkRejectModal = () => {
  if (selectedIds.value.length === 0) return
  isBulkReject.value = true
  rejectTargetId.value = null
  rejectionNote.value = ''
  isRejectModalOpen.value = true
}

const confirmReject = async () => {
  if (!rejectionNote.value.trim()) return
  isSubmittingReject.value = true
  try {
    if (isBulkReject.value) {
      const res = await adminBankRequestsApi.bulkReject(selectedIds.value, rejectionNote.value.trim())
      if (showToast) {
        showToast(res?.message || 'Đã từ chối các câu hỏi đã chọn!', 'success')
      }
      selectedIds.value = []
    } else {
      const res = await adminBankRequestsApi.reject(rejectTargetId.value, { note: rejectionNote.value.trim() })
      if (showToast) {
        showToast(res?.message || `Đã từ chối câu hỏi #${rejectTargetId.value}!`, 'success')
      }
    }
    isRejectModalOpen.value = false
    await loadRequests()
  } catch (e) {
    if (showToast) {
      showToast(`Không thể từ chối: ${e.message}`, 'error')
    }
  } finally {
    isSubmittingReject.value = false
  }
}

const handleBulkApprove = async () => {
  if (selectedIds.value.length === 0) return
  isProcessingBulk.value = true
  try {
    const res = await adminBankRequestsApi.bulkApprove(selectedIds.value)
    if (showToast) {
      showToast(res?.message || `Đã phê duyệt ${selectedIds.value.length} câu hỏi vào Ngân hàng!`, 'success')
    }
    selectedIds.value = []
    await loadRequests()
  } catch (e) {
    if (showToast) {
      showToast(`Không thể phê duyệt hàng loạt: ${e.message}`, 'error')
    }
  } finally {
    isProcessingBulk.value = false
  }
}

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'approved':
      return 'bg-emerald-50 text-emerald-700 border border-emerald-200'
    case 'rejected':
      return 'bg-rose-50 text-rose-700 border border-rose-200'
    case 'pending':
    default:
      return 'bg-amber-50 text-amber-700 border border-amber-200'
  }
}

const getStatusLabel = (status) => {
  switch (status) {
    case 'approved':
      return 'Đã vào Ngân hàng'
    case 'rejected':
      return 'Bị từ chối'
    case 'pending':
    default:
      return 'Đang chờ duyệt'
  }
}

const getDifficultyLabel = (diff) => {
  switch (diff) {
    case 'easy':
      return 'Dễ'
    case 'hard':
      return 'Khó'
    case 'medium':
    default:
      return 'Vừa'
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  return d.toLocaleString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

onMounted(async () => {
  await fetchTaxonomy()
  await loadRequests()
})
</script>
