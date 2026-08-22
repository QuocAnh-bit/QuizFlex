<template>
  <section class="space-y-6">
    <!-- Header -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div>
          <div class="inline-flex items-center gap-2 rounded-md border border-purple-200 bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700">
            <ClipboardCheck class="h-3.5 w-3.5" />
            <span>Quiz Moderation</span>
            <span class="text-purple-400">•</span>
            <span>Duyệt công khai bài Quiz</span>
          </div>
          <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
            Yêu cầu kiểm duyệt Quiz công khai
          </h1>
          <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-500">
            Xem xét các bài Quiz thủ công do giáo viên/học sinh đề xuất công khai. Khi phê duyệt, các câu hỏi đạt chuẩn sẽ được tự động snapshot vào Ngân hàng câu hỏi.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-center">
            <span class="text-[10px] font-bold uppercase text-slate-500">Chờ duyệt</span>
            <p class="text-xl font-black text-amber-600">{{ stats.pending }}</p>
          </div>
          <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-center">
            <span class="text-[10px] font-bold uppercase text-slate-500">Đã duyệt</span>
            <p class="text-xl font-black text-emerald-600">{{ stats.approved }}</p>
          </div>
          <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-center">
            <span class="text-[10px] font-bold uppercase text-slate-500">Bị từ chối</span>
            <p class="text-xl font-black text-rose-600">{{ stats.rejected }}</p>
          </div>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 cursor-pointer shadow-sm"
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
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Search -->
        <div class="relative sm:col-span-2">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
          <input
            v-model="filters.search"
            type="text"
            placeholder="Tìm theo tên Quiz, tác giả..."
            class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-4 py-2 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:bg-white"
            @keyup.enter="loadRequests(true)"
          />
        </div>

        <!-- Status Filter -->
        <div>
          <select
            v-model="filters.status"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 outline-none focus:border-[#7C3AED] focus:bg-white cursor-pointer"
            @change="loadRequests(true)"
          >
            <option value="pending">Chờ duyệt (Pending)</option>
            <option value="approved">Đã duyệt (Approved)</option>
            <option value="rejected">Bị từ chối (Rejected)</option>
            <option value="all">Tất cả trạng thái</option>
          </select>
        </div>

        <!-- Subject Filter -->
        <div>
          <select
            v-model="filters.subject_id"
            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-700 outline-none focus:border-[#7C3AED] focus:bg-white cursor-pointer"
            @change="loadRequests(true)"
          >
            <option value="">Tất cả môn học</option>
            <option v-for="sub in allSubjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Table of Requests -->
    <div class="card overflow-hidden">
      <!-- Bulk Actions Bar -->
      <div v-if="selectedIds.length > 0" class="bg-purple-50 border-b border-purple-100 p-3 px-5 flex items-center justify-between">
        <span class="text-xs font-bold text-purple-900">Đã chọn {{ selectedIds.length }} yêu cầu</span>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="btn-primary text-xs px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white"
            @click="handleBulkApprove"
          >
            ✓ Phê duyệt các mục đã chọn
          </button>
          <button
            type="button"
            class="btn-secondary text-xs px-3.5 py-1.5 text-rose-700 hover:bg-rose-50"
            @click="openBulkRejectModal"
          >
            ✕ Từ chối các mục đã chọn
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50 text-slate-400 font-bold uppercase text-[10px]">
              <th class="py-3 px-4 w-10">
                <input
                  type="checkbox"
                  class="rounded accent-[#7C3AED] cursor-pointer"
                  :checked="isAllSelected"
                  @change="toggleSelectAll"
                />
              </th>
              <th class="py-3 px-4">Bài Quiz</th>
              <th class="py-3 px-4">Người gửi</th>
              <th class="py-3 px-4 text-center">Chế độ</th>
              <th class="py-3 px-4 text-center">Số câu</th>
              <th class="py-3 px-4 text-center">Trạng thái</th>
              <th class="py-3 px-4 text-center">Thời gian gửi</th>
              <th class="py-3 px-4 text-right">Hành động</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="item in requests" :key="item.id" class="hover:bg-slate-50/80 transition-colors">
              <td class="py-3.5 px-4">
                <input
                  type="checkbox"
                  class="rounded accent-[#7C3AED] cursor-pointer"
                  :value="item.id"
                  v-model="selectedIds"
                />
              </td>
              <td class="py-3.5 px-4 max-w-xs">
                <div class="font-bold text-slate-900 truncate">{{ item.quiz?.title || 'Quiz #' + item.quiz_id }}</div>
                <div class="text-[11px] text-slate-500 truncate mt-0.5">
                  {{ item.quiz?.subject?.name || item.quiz?.subject_name || 'Chung' }} • {{ item.quiz?.grade?.name || 'Khối lớp' }}
                </div>
              </td>
              <td class="py-3.5 px-4">
                <div class="flex items-center gap-2">
                  <div class="h-7 w-7 rounded-full bg-purple-100 text-[#7C3AED] font-bold flex items-center justify-center text-xs shrink-0">
                    {{ (item.user?.name || 'U').charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <div class="font-bold text-slate-900">{{ item.user?.name || 'Người dùng' }}</div>
                    <div class="text-[10px] text-slate-400">{{ item.user?.email }}</div>
                  </div>
                </div>
              </td>
              <td class="py-3.5 px-4 text-center">
                <span
                  class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase"
                  :class="item.quiz?.creation_mode === 'auto' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-purple-50 text-purple-700 border border-purple-200'"
                >
                  {{ item.quiz?.creation_mode === 'auto' ? 'Tự động' : 'Thủ công' }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-center font-bold text-slate-800">
                {{ item.quiz?.questions_count ?? 0 }} câu
              </td>
              <td class="py-3.5 px-4 text-center">
                <StatusBadge :value="item.status" />
              </td>
              <td class="py-3.5 px-4 text-center text-slate-500 text-[11px]">
                {{ formatDate(item.created_at) }}
              </td>
              <td class="py-3.5 px-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    type="button"
                    class="btn-secondary text-xs px-2.5 py-1 flex items-center gap-1 hover:bg-purple-50 hover:text-[#7C3AED] hover:border-purple-200"
                    @click="openDetailModal(item.id)"
                  >
                    <Eye :size="13" />
                    <span>Xem xét</span>
                  </button>
                  <template v-if="item.status === 'pending'">
                    <button
                      type="button"
                      class="btn-primary text-xs px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white"
                      title="Phê duyệt ngay"
                      @click="handleSingleApprove(item)"
                    >
                      <Check :size="13" />
                    </button>
                    <button
                      type="button"
                      class="btn-secondary text-xs px-2.5 py-1 text-rose-600 hover:bg-rose-50 hover:border-rose-200"
                      title="Từ chối"
                      @click="openRejectModal(item)"
                    >
                      <X :size="13" />
                    </button>
                  </template>
                </div>
              </td>
            </tr>
            <tr v-if="!isLoading && requests.length === 0">
              <td colspan="8" class="py-12 text-center text-slate-400">
                <Inbox :size="36" class="mx-auto mb-2 opacity-40" />
                <p class="text-xs font-medium">Không có yêu cầu kiểm duyệt nào phù hợp bộ lọc.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.perPage" class="p-4 border-t border-slate-100 flex items-center justify-between text-xs">
        <span class="text-slate-500">
          Hiển thị {{ (pagination.currentPage - 1) * pagination.perPage + 1 }} - {{ Math.min(pagination.currentPage * pagination.perPage, pagination.total) }} trên {{ pagination.total }} yêu cầu
        </span>
        <div class="flex items-center gap-2">
          <button
            class="btn-secondary text-xs px-3 py-1"
            :disabled="pagination.currentPage <= 1"
            @click="changePage(pagination.currentPage - 1)"
          >
            ← Trước
          </button>
          <span class="font-bold text-slate-700">Trang {{ pagination.currentPage }} / {{ pagination.lastPage }}</span>
          <button
            class="btn-secondary text-xs px-3 py-1"
            :disabled="pagination.currentPage >= pagination.lastPage"
            @click="changePage(pagination.currentPage + 1)"
          >
            Sau →
          </button>
        </div>
      </div>
    </div>

    <!-- DETAIL & MODERATION MODAL -->
    <div
      v-if="isDetailModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn"
    >
      <div class="card max-w-4xl w-full max-h-[90vh] flex flex-col shadow-2xl animate-scaleUp overflow-hidden">
        <!-- Modal Header -->
        <div class="p-5 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/50">
          <div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-purple-600">Thẩm định Quiz</span>
            <h2 class="text-lg font-black text-slate-900">{{ currentDetail?.quiz?.title }}</h2>
          </div>
          <button
            type="button"
            class="text-slate-400 hover:text-slate-600 text-base font-bold p-1"
            @click="isDetailModalOpen = false"
          >
            ✕
          </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-6 overflow-y-auto space-y-6 flex-1">
          <!-- Quiz & Creator Overview Grid -->
          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 text-xs">
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
              <span class="block text-[10px] font-bold uppercase text-slate-400">Người tạo</span>
              <span class="font-bold text-slate-900 block mt-0.5">{{ currentDetail?.review_request?.user?.name }}</span>
              <span class="text-[11px] text-slate-500">{{ currentDetail?.review_request?.user?.email }}</span>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
              <span class="block text-[10px] font-bold uppercase text-slate-400">Thời gian gửi</span>
              <span class="font-bold text-slate-900 block mt-0.5">{{ formatDate(currentDetail?.review_request?.created_at) }}</span>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
              <span class="block text-[10px] font-bold uppercase text-slate-400">Chế độ tạo</span>
              <span class="font-bold text-purple-700 block mt-0.5 uppercase">{{ currentDetail?.quiz?.creation_mode || 'manual' }}</span>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
              <span class="block text-[10px] font-bold uppercase text-slate-400">Trạng thái</span>
              <div class="mt-1">
                <StatusBadge :value="currentDetail?.review_request?.status" />
              </div>
            </div>
          </div>

          <!-- Ghi chú của tác giả -->
          <div v-if="currentDetail?.review_request?.request_note" class="rounded-xl border border-purple-200 bg-purple-50/50 p-3.5 text-xs text-purple-900">
            <span class="font-bold block mb-1">Lời nhắn từ người gửi:</span>
            <p class="leading-relaxed">{{ currentDetail.review_request.request_note }}</p>
          </div>

          <!-- Rejection Reason if rejected -->
          <div v-if="currentDetail?.review_request?.status === 'rejected'" class="rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-xs text-rose-900">
            <span class="font-bold block mb-1">Lý do từ chối:</span>
            <p class="leading-relaxed">{{ currentDetail.review_request.rejection_reason }}</p>
            <span class="text-[10px] text-rose-700 block mt-1">Duyệt bởi: {{ currentDetail.review_request.reviewer?.name }} vào {{ formatDate(currentDetail.review_request.reviewed_at) }}</span>
          </div>

          <!-- Questions List Section -->
          <div class="space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
              <h3 class="text-sm font-bold text-slate-900">
                Danh sách câu hỏi ({{ currentDetail?.quiz?.questions?.length || 0 }})
              </h3>
              <span class="text-[11px] text-slate-500 font-medium">
                Câu hỏi cá nhân sẽ được tạo bản sao Snapshot vào Ngân hàng khi duyệt
              </span>
            </div>

            <div class="space-y-4">
              <div
                v-for="(q, index) in currentDetail?.quiz?.questions"
                :key="q.id"
                class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 space-y-3"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="flex items-center gap-2">
                    <span class="h-5 w-5 rounded-md bg-purple-100 text-[#7C3AED] font-black text-[11px] flex items-center justify-center shrink-0">
                      {{ index + 1 }}
                    </span>
                    <h4 class="text-xs font-bold text-slate-900 leading-snug">{{ q.content || q.question }}</h4>
                  </div>
                  <div class="flex items-center gap-1.5 shrink-0">
                    <!-- Source Badge -->
                    <span
                      class="rounded-md px-2 py-0.5 text-[10px] font-bold"
                      :class="q.source === 'public_bank' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                    >
                      {{ q.source === 'public_bank' ? 'Ngân hàng' : 'Kho cá nhân (Cần snapshot)' }}
                    </span>
                    <span class="rounded-md bg-white border border-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-500">
                      {{ q.points || 10 }} pts
                    </span>
                  </div>
                </div>

                <!-- Answers Grid -->
                <div class="grid gap-2 sm:grid-cols-2 text-xs">
                  <div
                    v-for="(ans, aIndex) in q.answers"
                    :key="ans.id ?? aIndex"
                    class="p-2.5 rounded-lg border text-xs flex items-center justify-between"
                    :class="ans.is_correct ? 'bg-emerald-50 border-emerald-300 text-emerald-900 font-bold' : 'bg-white border-slate-200 text-slate-700'"
                  >
                    <span>{{ String.fromCharCode(65 + aIndex) }}. {{ ans.content }}</span>
                    <span v-if="ans.is_correct" class="text-[10px] font-bold text-emerald-700 uppercase">✓ Đúng</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer Actions -->
        <div class="p-4 border-t border-slate-100 bg-slate-50/80 flex items-center justify-between shrink-0">
          <button
            type="button"
            class="btn-secondary text-xs px-4 py-2"
            @click="isDetailModalOpen = false"
          >
            Đóng
          </button>

          <div v-if="currentDetail?.review_request?.status === 'pending'" class="flex items-center gap-2">
            <button
              type="button"
              class="btn-secondary text-xs px-4 py-2 text-rose-700 hover:bg-rose-50 border-rose-200"
              :disabled="isProcessing"
              @click="openRejectModal(currentDetail.review_request)"
            >
              ✕ Từ chối phê duyệt
            </button>
            <button
              type="button"
              class="btn-primary text-xs px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white flex items-center gap-1.5"
              :disabled="isProcessing"
              @click="handleSingleApprove(currentDetail.review_request)"
            >
              <Check :size="14" />
              <span>{{ isProcessing ? 'Đang xử lý...' : 'Phê duyệt & Công khai Quiz' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- REJECT REASON MODAL -->
    <div
      v-if="isRejectModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn"
    >
      <div class="card p-6 max-w-md w-full space-y-4 shadow-2xl animate-scaleUp">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-base font-black text-rose-600 flex items-center gap-2">
            <AlertCircle :size="18" />
            <span>Từ chối phê duyệt Quiz</span>
          </h3>
          <button
            type="button"
            class="text-slate-400 hover:text-slate-600 text-sm font-bold"
            @click="isRejectModalOpen = false"
          >
            ✕
          </button>
        </div>

        <p class="text-xs leading-relaxed text-slate-600">
          Vui lòng nhập lý do từ chối cụ thể để tác giả biết và chỉnh sửa lại bài Quiz trước khi gửi lại yêu cầu.
        </p>

        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-700 block">Lý do từ chối <span class="text-rose-500">*</span></label>
          <textarea
            v-model="rejectionReason"
            rows="4"
            class="field text-xs resize-none w-full"
            placeholder="Ví dụ: Câu số 3 thiếu đáp án đúng, nội dung câu hỏi chưa rõ ràng..."
            required
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <button
            type="button"
            class="btn-secondary text-xs px-4 py-2"
            :disabled="isProcessing"
            @click="isRejectModalOpen = false"
          >
            Hủy
          </button>
          <button
            type="button"
            class="btn-primary text-xs px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white flex items-center gap-1.5"
            :disabled="isProcessing || !rejectionReason.trim()"
            @click="handleConfirmReject"
          >
            <span>{{ isProcessing ? 'Đang xử lý...' : 'Xác nhận từ chối' }}</span>
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject } from 'vue'
import {
  ClipboardCheck,
  Search,
  RefreshCw,
  Eye,
  Check,
  X,
  Inbox,
  AlertCircle,
} from 'lucide-vue-next'
import { quizReviewApi, taxonomyApi } from '@/services/api'
import StatusBadge from '@/components/common/StatusBadge.vue'

const showToast = inject('showToast')
const showConfirm = inject('showConfirm')

const isLoading = ref(false)
const isProcessing = ref(false)
const requests = ref([])
const selectedIds = ref([])
const allSubjects = ref([])

const stats = reactive({
  pending: 0,
  approved: 0,
  rejected: 0,
  total: 0,
})

const pagination = reactive({
  currentPage: 1,
  lastPage: 1,
  perPage: 15,
  total: 0,
})

const filters = reactive({
  search: '',
  status: 'pending',
  subject_id: '',
})

const isDetailModalOpen = ref(false)
const currentDetail = ref(null)

const isRejectModalOpen = ref(false)
const targetRejectRequest = ref(null)
const rejectionReason = ref('')
const isBulkReject = ref(false)

const isAllSelected = computed(() => {
  return requests.value.length > 0 && selectedIds.value.length === requests.value.length
})

const toggleSelectAll = (e) => {
  if (e.target.checked) {
    selectedIds.value = requests.value.map(r => r.id)
  } else {
    selectedIds.value = []
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

const loadRequests = async (resetPage = false) => {
  if (resetPage) pagination.currentPage = 1
  isLoading.value = true
  try {
    const params = {
      page: pagination.currentPage,
      per_page: pagination.perPage,
      status: filters.status,
      search: filters.search.trim() || undefined,
      subject_id: filters.subject_id || undefined,
    }
    const res = await quizReviewApi.fetchAdminReviewRequests(params)
    requests.value = res.items || []
    pagination.total = res.total || 0
    pagination.currentPage = res.current_page || 1
    pagination.lastPage = res.last_page || 1

    if (res.stats) {
      stats.pending = res.stats.pending || 0
      stats.approved = res.stats.approved || 0
      stats.rejected = res.stats.rejected || 0
      stats.total = res.stats.total || 0
    }
  } catch (error) {
    if (showToast) showToast(`Không tải được danh sách: ${error.message}`, 'error')
  } finally {
    isLoading.value = false
  }
}

const changePage = (page) => {
  pagination.currentPage = page
  loadRequests()
}

const openDetailModal = async (requestId) => {
  try {
    const res = await quizReviewApi.getAdminReviewRequest(requestId)
    currentDetail.value = res
    isDetailModalOpen.value = true
  } catch (error) {
    if (showToast) showToast(`Không tải được chi tiết: ${error.message}`, 'error')
  }
}

const handleSingleApprove = (request) => {
  const quizTitle = request.quiz?.title || ''
  const message = `Bạn có chắc chắn muốn phê duyệt và công khai bài Quiz "${quizTitle}"? Các câu hỏi từ kho cá nhân của người dùng sẽ được tạo bản sao vào Ngân hàng câu hỏi.`

  if (showConfirm) {
    showConfirm('Xác nhận phê duyệt Quiz', message, async () => {
      await executeSingleApprove(request)
    })
  } else {
    executeSingleApprove(request)
  }
}

const executeSingleApprove = async (request) => {
  isProcessing.value = true
  try {
    const res = await quizReviewApi.adminApprove(request.id)
    if (showToast) showToast(res.message || 'Phê duyệt bài Quiz thành công!', 'success')
    isDetailModalOpen.value = false
    await loadRequests()
  } catch (error) {
    const msg = error.response?.data?.message || error.message || 'Phê duyệt thất bại'
    if (showToast) showToast(msg, 'error')
  } finally {
    isProcessing.value = false
  }
}

const openRejectModal = (request) => {
  targetRejectRequest.value = request
  rejectionReason.value = ''
  isBulkReject.value = false
  isRejectModalOpen.value = true
}

const openBulkRejectModal = () => {
  targetRejectRequest.value = null
  rejectionReason.value = ''
  isBulkReject.value = true
  isRejectModalOpen.value = true
}

const handleConfirmReject = async () => {
  if (!rejectionReason.value.trim()) {
    if (showToast) showToast('Vui lòng nhập lý do từ chối!', 'error')
    return
  }

  isProcessing.value = true
  try {
    if (isBulkReject.value) {
      const res = await quizReviewApi.adminBulkReject(selectedIds.value, rejectionReason.value.trim())
      if (showToast) showToast(res.message || 'Đã từ chối các yêu cầu được chọn!', 'success')
      selectedIds.value = []
    } else if (targetRejectRequest.value) {
      const res = await quizReviewApi.adminReject(targetRejectRequest.value.id, rejectionReason.value.trim())
      if (showToast) showToast(res.message || 'Đã từ chối bài Quiz thành công!', 'success')
      isDetailModalOpen.value = false
    }
    isRejectModalOpen.value = false
    await loadRequests()
  } catch (error) {
    const msg = error.response?.data?.message || error.message || 'Từ chối thất bại'
    if (showToast) showToast(msg, 'error')
  } finally {
    isProcessing.value = false
  }
}

const handleBulkApprove = () => {
  const count = selectedIds.value.length
  const message = `Bạn có chắc chắn muốn phê duyệt ${count} bài Quiz đã chọn? Tất cả bài Quiz này sẽ được chuyển sang trạng thái Công khai (Public).`

  if (showConfirm) {
    showConfirm('Phê duyệt hàng loạt', message, async () => {
      await executeBulkApprove()
    })
  } else {
    executeBulkApprove()
  }
}

const executeBulkApprove = async () => {
  isProcessing.value = true
  try {
    const res = await quizReviewApi.adminBulkApprove(selectedIds.value)
    if (showToast) showToast(res.message || 'Đã phê duyệt các bài Quiz thành công!', 'success')
    selectedIds.value = []
    await loadRequests()
  } catch (error) {
    const msg = error.response?.data?.message || error.message || 'Phê duyệt hàng loạt thất bại'
    if (showToast) showToast(msg, 'error')
  } finally {
    isProcessing.value = false
  }
}

onMounted(async () => {
  try {
    allSubjects.value = await taxonomyApi.fetchSubjects()
  } catch (e) {
    console.error(e)
  }
  await loadRequests()
})
</script>
