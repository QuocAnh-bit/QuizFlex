<template>
  <section class="mx-auto w-[min(1240px,calc(100%-32px))] py-8 space-y-6">
    

    <!-- Header Banner -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-7 shadow-xs">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-4">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-purple-50 text-[#7C3AED] border border-purple-200 shadow-2xs">
            <Flag :size="24" />
          </div>
          <div>
            <div class="flex items-center gap-2.5">
              <h1 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                Báo Cáo Của Tôi
              </h1>
              <span class="rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-bold text-purple-800 border border-purple-200">
                Lịch sử phản ánh
              </span>
            </div>
            <p class="mt-1 text-sm text-slate-500 max-w-2xl">
              Theo dõi tiến độ tiếp nhận, quy trình đính chính và kết quả xử lý của các câu hỏi bạn đã đóng góp phản ánh vi phạm.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2.5">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:border-slate-300 transition cursor-pointer active:scale-95"
            @click="fetchMyReports"
          >
            <RefreshCw class="h-4 w-4 text-slate-500" :class="{ 'animate-spin': isLoading }" />
            <span>Làm mới</span>
          </button>
        </div>
      </div>

      <!-- KPI Summary Cards (Simplified: Tổng, Đang xử lý, Đã có kết quả) -->
      <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <!-- 1. Tất cả -->
        <div
          class="rounded-2xl border p-4 transition cursor-pointer shadow-2xs"
          :class="selectedStatus === 'all' ? 'border-[#7C3AED] bg-purple-50/60 ring-2 ring-purple-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          @click="selectedStatus = 'all'"
        >
          <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-100 text-purple-700">
              <FileText :size="18" />
            </div>
            <div>
              <p class="text-[11px] font-semibold text-slate-500">Tổng báo cáo</p>
              <p class="text-xl font-black text-purple-700">{{ stats.total }}</p>
            </div>
          </div>
        </div>

        <!-- 2. Đang xử lý -->
        <div
          class="rounded-2xl border p-4 transition cursor-pointer shadow-2xs"
          :class="selectedStatus === 'in_progress' ? 'border-amber-500 bg-amber-50/60 ring-2 ring-amber-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          @click="selectedStatus = 'in_progress'"
        >
          <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
              <Clock :size="18" />
            </div>
            <div>
              <p class="text-[11px] font-semibold text-slate-500">Đang xử lý</p>
              <p class="text-xl font-black text-amber-700">{{ stats.in_progress }}</p>
            </div>
          </div>
        </div>

        <!-- 3. Đã có kết quả -->
        <div
          class="rounded-2xl border p-4 transition cursor-pointer shadow-2xs"
          :class="selectedStatus === 'completed' ? 'border-emerald-500 bg-emerald-50/60 ring-2 ring-emerald-500/20' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
          @click="selectedStatus = 'completed'"
        >
          <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
              <CheckCircle2 :size="18" />
            </div>
            <div>
              <p class="text-[11px] font-semibold text-slate-500">Đã có kết quả</p>
              <p class="text-xl font-black text-emerald-700">{{ stats.completed ?? ((stats.resolved || 0) + (stats.dismissed || 0)) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter Bar & Search -->
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs">
      <!-- Tabs (Simplified: Tất cả, Đang xử lý, Đã có kết quả) -->
      <div class="flex items-center gap-2 overflow-x-auto text-xs font-bold">
        <button
          v-for="tab in filterTabs"
          :key="tab.key"
          type="button"
          class="rounded-xl px-3.5 py-2 transition cursor-pointer flex items-center gap-2 whitespace-nowrap"
          :class="selectedStatus === tab.key
            ? 'bg-purple-50 text-[#7C3AED] border border-purple-200 shadow-2xs font-bold'
            : 'text-slate-600 hover:bg-slate-100'"
          @click="selectedStatus = tab.key"
        >
          <component :is="tab.icon" :size="14" class="shrink-0" />
          <span>{{ tab.label }}</span>
          <span
            v-if="tab.count > 0"
            class="rounded-full px-2 py-0.5 text-[10px]"
            :class="selectedStatus === tab.key ? 'bg-[#7C3AED] text-white' : 'bg-slate-200 text-slate-700'"
          >
            {{ tab.count }}
          </span>
        </button>
      </div>

      <!-- Search Input -->
      <div class="relative min-w-[240px] flex-1 sm:flex-initial">
        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Tìm theo mã câu hỏi, lý do..."
          class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-3.5 py-2 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:bg-white transition"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="p-16 text-center text-xs text-slate-400 rounded-3xl bg-white border border-slate-200">
      <RefreshCw class="mx-auto mb-3 h-8 w-8 animate-spin text-[#7C3AED]" />
      <span class="text-sm font-semibold text-slate-600">Đang tải lịch sử báo cáo...</span>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="p-12 text-center rounded-3xl bg-white border border-rose-200 space-y-3">
      <AlertCircle class="mx-auto h-10 w-10 text-rose-500" />
      <p class="text-sm font-bold text-slate-800">{{ errorMessage }}</p>
      <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-xl bg-[#7C3AED] px-4 py-2 text-xs font-bold text-white hover:bg-[#6D28D9] transition cursor-pointer"
        @click="fetchMyReports"
      >
        Thử lại
      </button>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredReports.length === 0" class="p-16 text-center rounded-3xl bg-white border border-slate-200 space-y-3 shadow-2xs">
      <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
        <CheckCircle class="h-8 w-8 text-emerald-500" />
      </div>
      <h3 class="text-base font-bold text-slate-900">
        {{ selectedStatus === 'all' ? 'Bạn chưa gửi báo cáo vi phạm nào' : 'Không có báo cáo nào trong mục này' }}
      </h3>
      <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
        Khi bạn phát hiện câu hỏi có sai sót trong quá trình luyện tập, hãy nhấp vào biểu tượng lá cờ để phản ánh cho tác giả và Ban Quản Trị.
      </p>
      <router-link
        to="/quizzes"
        class="inline-flex items-center gap-1.5 rounded-xl bg-[#7C3AED] px-5 py-2.5 text-xs font-bold text-white hover:bg-[#6D28D9] transition shadow-sm cursor-pointer"
      >
        <span>Khám phá đề thi ngay</span>
      </router-link>
    </div>

    <!-- Reports Cards List -->
    <div v-else class="space-y-4">
      <article
        v-for="rep in paginatedReports"
        :key="rep.id"
        class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-6 shadow-2xs hover:shadow-md transition space-y-4"
      >
        <!-- Card Header -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-3">
          <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-xs font-bold text-slate-900">
              Câu hỏi #{{ rep.question_id }}
            </span>
            <span v-if="rep.question?.subject?.name" class="rounded-md bg-purple-50 px-2 py-0.5 text-xs font-bold text-purple-700">
              {{ rep.question.subject.name }}
            </span>
            <span v-if="rep.question?.grade?.name" class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
              {{ rep.question.grade.name }}
            </span>
          </div>

          <div class="flex items-center gap-2 text-xs text-slate-400">
            <Clock class="h-3.5 w-3.5" />
            <span>Gửi lúc {{ formatDate(rep.created_at) }}</span>
          </div>
        </div>

        <!-- Question Snippet -->
        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-3.5 text-xs space-y-1">
          <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Nội dung câu hỏi:</span>
          <p class="font-bold text-slate-900 leading-relaxed italic">
            “<MathText :content="rep.question?.content || 'Nội dung câu hỏi không khả dụng'" compact />”
          </p>
        </div>

        <!-- Reason & Description -->
        <div class="space-y-2 text-xs">
          <div class="flex items-center gap-2">
            <span class="text-slate-500 font-medium">Lý do bạn đã báo cáo:</span>
            <span class="rounded-md bg-rose-50 px-2.5 py-0.5 text-xs font-bold text-rose-700 border border-rose-200">
              {{ rep.reason }}
            </span>
          </div>

          <p v-if="rep.description" class="text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-100 text-[11px] leading-relaxed">
            <strong>Ghi chú thêm:</strong> "{{ rep.description }}"
          </p>
        </div>

        <!-- Resolution Outcome & Status Box -->
        <div
          class="rounded-2xl border p-4 text-xs space-y-2"
          :class="getResolutionBoxStyle(rep)"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 font-black">
              <CheckCircle2 v-if="isCompletedReport(rep)" class="h-4 w-4 text-emerald-600" />
              <Clock v-else class="h-4 w-4 text-amber-600" />
              <span>{{ rep.status_label || getStatusLabel(rep) }}</span>
            </div>

            <span v-if="rep.resolved_at || rep.auto_privatized_at || rep.updated_at" class="text-[11px] text-slate-500">
              Cập nhật: {{ formatDate(rep.resolved_at || rep.auto_privatized_at || rep.updated_at) }}
            </span>
          </div>

          <!-- Human-friendly Meaningful Resolution Message -->
          <p class="text-[11px] text-slate-700 leading-relaxed font-medium">
            {{ rep.resolution_message || 'Báo cáo của bạn đã được ghi nhận.' }}
          </p>
        </div>
      </article>

      <!-- Pagination -->
      <div v-if="filteredReports.length > 0" class="pt-3">
        <AppPagination
          :current-page="currentPage"
          :last-page="totalReportPages"
          :total="filteredReports.length"
          :per-page="pageSize"
          item-label="báo cáo"
          @change="(p) => currentPage = p"
        />
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import {
  AlertCircle,
  CheckCircle,
  CheckCircle2,
  ChevronRight,
  Clock,
  FileText,
  Flag,
  Layers,
  RefreshCw,
  Search,
} from 'lucide-vue-next'
import { reportApi } from '@/services/api'
import { useAppLoading } from '@/composables/useAppLoading'
import AppPagination from '@/components/common/AppPagination.vue'
import MathText from '@/components/MathText.vue'

const { beginTask, endTask } = useAppLoading()

const reports = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const selectedStatus = ref('all')
const searchQuery = ref('')
const currentPage = ref(1)
const pageSize = 10

const stats = reactive({
  total: 0,
  in_progress: 0,
  completed: 0,
  resolved: 0,
  dismissed: 0,
})

const filterTabs = computed(() => [
  { key: 'all', label: 'Tất cả', icon: Layers, count: stats.total },
  { key: 'in_progress', label: 'Đang xử lý', icon: Clock, count: stats.in_progress },
  { key: 'completed', label: 'Đã có kết quả', icon: CheckCircle2, count: stats.completed ?? ((stats.resolved || 0) + (stats.dismissed || 0)) },
])

const isCompletedReport = (rep) => {
  return rep.status === 'resolved' || rep.status === 'dismissed' || !!rep.auto_privatized_at
}

const filteredReports = computed(() => {
  return reports.value.filter((rep) => {
    // 1. Status Tab
    if (selectedStatus.value === 'in_progress') {
      if (isCompletedReport(rep)) return false
    } else if (selectedStatus.value === 'completed') {
      if (!isCompletedReport(rep)) return false
    }

    // 2. Search
    if (!searchQuery.value.trim()) return true
    const q = searchQuery.value.toLowerCase().trim()
    const content = (rep.question?.content || '').toLowerCase()
    const reason = (rep.reason || '').toLowerCase()
    const qId = String(rep.question_id || '')

    return content.includes(q) || reason.includes(q) || qId.includes(q)
  })
})

const totalReportPages = computed(() => Math.max(1, Math.ceil(filteredReports.value.length / pageSize)))
const paginatedReports = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return filteredReports.value.slice(start, start + pageSize)
})

const fetchMyReports = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const res = await reportApi.myReports()
    reports.value = res.items || []
    if (res.stats) {
      Object.assign(stats, res.stats)
    }
  } catch (err) {
    console.error('Lỗi khi tải lịch sử báo cáo:', err)
    errorMessage.value = err.response?.data?.message || err.message || 'Không thể tải lịch sử báo cáo của bạn.'
  } finally {
    isLoading.value = false
  }
}

const getStatusLabel = (rep) => {
  if (isCompletedReport(rep)) return 'Đã có kết quả'
  return 'Đang xử lý'
}

const getResolutionBoxStyle = (rep) => {
  if (isCompletedReport(rep)) {
    return 'border-emerald-200 bg-emerald-50/50 text-emerald-950'
  }
  return 'border-amber-200 bg-amber-50/50 text-amber-950'
}

const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

onMounted(async () => {
  beginTask()
  try {
    await fetchMyReports()
  } finally {
    endTask()
  }
})
</script>
