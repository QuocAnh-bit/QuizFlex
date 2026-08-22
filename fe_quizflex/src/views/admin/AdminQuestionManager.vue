<template>
  <section class="space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-slate-500">
      <router-link to="/admin" class="hover:text-[#7C3AED] transition-colors">
        Dashboard
      </router-link>
      <ChevronRight class="h-4 w-4" />
      <span class="font-medium text-slate-900">Ngân hàng câu hỏi</span>
    </div>

    <!-- Header -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex items-start gap-4">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#7C3AED] text-white">
            <Shield class="h-6 w-6" />
          </div>
          <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
              Quản lý Ngân hàng Câu hỏi
            </h1>
            <p class="mt-1 max-w-2xl text-sm text-slate-500">
              Kiểm duyệt nội dung, điều chỉnh quyền hiển thị (Public/Private), chỉnh sửa đáp án và xử lý báo cáo trên toàn bộ hệ thống.
            </p>
          </div>
        </div>

        <div class="flex shrink-0 items-center gap-3">
          <router-link
            to="/admin/questions-trash"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
          >
            <Trash2 class="h-4 w-4 text-rose-500" />
            Thùng rác
          </router-link>

          <router-link
            to="/admin/question-bank-requests"
            class="inline-flex items-center gap-2 rounded-lg bg-[#7C3AED] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#6D28D9]"
          >
            <CheckSquare class="h-4 w-4" />
            Duyệt vào Ngân hàng
          </router-link>
        </div>
      </div>

      <!-- KPI Stats -->
      <div class="mt-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#F5F3FF] text-[#7C3AED]">
              <FileText class="h-5 w-5" />
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Tổng câu hỏi</p>
              <p class="text-2xl font-bold text-slate-900">{{ stats.total || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
              <Globe class="h-5 w-5" />
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Công khai</p>
              <p class="text-2xl font-bold text-emerald-600">{{ stats.public || 0 }}</p>
            </div>
          </div>
          <p class="mt-2 text-xs text-emerald-600/80">{{ publicPercent }}% tổng số</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
              <Lock class="h-5 w-5" />
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Riêng tư / Ẩn</p>
              <p class="text-2xl font-bold text-amber-600">{{ stats.private || 0 }}</p>
            </div>
          </div>
          <p class="mt-2 text-xs text-amber-600/80">{{ privatePercent }}% tổng số</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
              <AlertTriangle class="h-5 w-5" />
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Ticket báo cáo</p>
              <p class="text-2xl font-bold text-rose-600">{{ stats.reported || 0 }}</p>
            </div>
          </div>
          <p class="mt-2 text-xs text-rose-600/80">Cần xử lý</p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div class="relative">
          <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            v-model="filters.search"
            type="text"
            placeholder="Tìm theo nội dung, tác giả, ID..."
            class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
            @keyup.enter="fetchQuestions(1)"
          />
        </div>

        <select
          v-model="filters.subject_id"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
          @change="fetchQuestions(1)"
        >
          <option value="">Tất cả môn học</option>
          <option v-for="subj in subjects" :key="subj.id" :value="subj.id">
            {{ subj.name }}
          </option>
        </select>

        <select
          v-model="filters.grade_id"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
          @change="fetchQuestions(1)"
        >
          <option value="">Tất cả khối lớp</option>
          <option v-for="gr in grades" :key="gr.id" :value="gr.id">
            {{ gr.name }}
          </option>
        </select>

        <button
          type="button"
          class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
          @click="showAdvancedFilters = !showAdvancedFilters"
        >
          <Filter class="h-4 w-4" />
          Bộ lọc nâng cao
          <ChevronDown
            class="h-4 w-4 transition-transform"
            :class="{ 'rotate-180': showAdvancedFilters }"
          />
        </button>
      </div>

      <!-- Advanced filters -->
      <div
        v-if="showAdvancedFilters"
        class="mt-4 grid gap-3 border-t border-slate-100 pt-4 md:grid-cols-2 xl:grid-cols-5"
      >
        <select
          v-model="filters.difficulty"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
          @change="fetchQuestions(1)"
        >
          <option value="">Tất cả độ khó</option>
          <option value="easy">Dễ</option>
          <option value="medium">Trung bình</option>
          <option value="hard">Khó</option>
        </select>

        <select
          v-model="filters.visibility"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
          @change="fetchQuestions(1)"
        >
          <option value="all">Tất cả trạng thái</option>
          <option value="public">Công khai</option>
          <option value="private">Riêng tư</option>
        </select>

        <select
          v-model="filters.type"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
          @change="fetchQuestions(1)"
        >
          <option value="">Tất cả dạng câu</option>
          <option value="single_choice">Một đáp án đúng</option>
          <option value="multi_choice">Nhiều đáp án đúng</option>
          <option value="true_false">Đúng / Sai</option>
        </select>

        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-rose-600 transition hover:bg-rose-50"
          @click="resetFilters"
        >
          Đặt lại bộ lọc
        </button>

        <button
          type="button"
          class="rounded-lg bg-[#7C3AED] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#6D28D9]"
          @click="fetchQuestions(1)"
        >
          Tìm kiếm
        </button>
      </div>
    </div>

    <!-- Bulk actions -->
    <div
      v-if="selectedIds.length > 0"
      class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-[#7C3AED]/20 bg-[#F5F3FF] p-4"
    >
      <div class="flex items-center gap-3">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#7C3AED] text-sm font-bold text-white">
          {{ selectedIds.length }}
        </span>
        <span class="text-sm font-semibold text-slate-900">
          Đã chọn {{ selectedIds.length }} câu hỏi
        </span>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100"
          @click="handleBulkVisibility(true)"
        >
          <Check class="h-4 w-4" />
          Duyệt công khai
        </button>

        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-2 text-sm font-medium text-amber-700 transition hover:bg-amber-100"
          @click="handleBulkVisibility(false)"
        >
          <Lock class="h-4 w-4" />
          Chuyển riêng tư
        </button>

        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3.5 py-2 text-sm font-medium text-rose-700 transition hover:bg-rose-100"
          @click="handleBulkDelete"
        >
          <Trash2 class="h-4 w-4" />
          Xóa vào thùng rác
        </button>

        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
          @click="selectedIds = []"
        >
          Hủy chọn
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
              <th class="w-12 p-4 text-center">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 text-[#7C3AED] focus:ring-[#7C3AED]"
                  :checked="isAllSelected"
                  @change="toggleSelectAll"
                />
              </th>
              <th class="w-16 p-4 text-center">ID</th>
              <th class="min-w-[260px] p-4">Nội dung câu hỏi</th>
              <th class="p-4 text-center">Môn học</th>
              <th class="p-4 text-center">Khối lớp</th>
              <th class="p-4 text-center">Độ khó</th>
              <th class="p-4 text-center">Trạng thái</th>
              <th class="p-4 text-center">Người tạo</th>
              <th class="p-4 text-center">Thời gian</th>
              <th class="min-w-[120px] p-4 text-center">Thao tác</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="q in questions"
              :key="q.id"
              class="cursor-pointer transition hover:bg-slate-50"
              :class="{ 'bg-[#F5F3FF]/60': selectedIds.includes(q.id) }"
              @click="goToDetail(q.id)"
            >
              <td class="p-4 text-center" @click.stop>
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 text-[#7C3AED] focus:ring-[#7C3AED]"
                  :value="q.id"
                  v-model="selectedIds"
                />
              </td>

              <td class="p-4 text-center font-semibold text-[#7C3AED]">
                #{{ q.id }}
              </td>

              <td class="max-w-md p-4">
                <div class="line-clamp-2 font-medium text-slate-900" :title="q.content">
                  {{ q.content }}
                </div>
                <div
                  v-if="q.quiz_title"
                  class="mt-1 truncate text-xs text-slate-500"
                  :title="q.quiz_title"
                >
                  {{ q.quiz_title }}
                </div>
              </td>

              <td class="p-4 text-center">
                <span
                  v-if="q.subject_name"
                  class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700"
                >
                  {{ q.subject_name }}
                </span>
                <span v-else class="text-xs text-slate-400">—</span>
              </td>

              <td class="p-4 text-center">
                <span
                  v-if="q.grade_name"
                  class="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700"
                >
                  {{ q.grade_name }}
                </span>
                <span v-else class="text-xs text-slate-400">—</span>
              </td>

              <td class="p-4 text-center">
                <span
                  class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium"
                  :class="{
                    'bg-emerald-50 text-emerald-700': q.difficulty === 'easy',
                    'bg-amber-50 text-amber-700': q.difficulty === 'medium',
                    'bg-rose-50 text-rose-700': q.difficulty === 'hard'
                  }"
                >
                  {{ q.difficulty === 'easy' ? 'Dễ' : q.difficulty === 'hard' ? 'Khó' : 'Trung bình' }}
                </span>
              </td>

              <td class="p-4 text-center">
                <span
                  class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium"
                  :class="q.is_public
                    ? 'bg-emerald-50 text-emerald-700'
                    : 'bg-amber-50 text-amber-700'"
                >
                  <component :is="q.is_public ? Globe : Lock" class="h-3.5 w-3.5" />
                  {{ q.is_public ? 'Công khai' : 'Riêng tư' }}
                </span>
              </td>

              <td class="p-4 text-center text-xs font-medium text-slate-500">
                <div class="flex items-center justify-center gap-1.5">
                  <User class="h-3.5 w-3.5" />
                  <span class="max-w-[110px] truncate" :title="q.author_name">
                    {{ q.author_name || 'Vô danh' }}
                  </span>
                </div>
              </td>

              <td class="p-4 text-center text-xs text-slate-500">
                {{ formatDate(q.created_at) }}
              </td>

              <td class="space-x-1 p-4 text-center whitespace-nowrap" @click.stop>
                <router-link
                  :to="`/admin/questions/${q.id}`"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-[#7C3AED] hover:text-white"
                  title="Xem chi tiết"
                >
                  <Eye class="h-4 w-4" />
                </router-link>

                <router-link
                  :to="`/admin/questions/${q.id}/edit`"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-amber-600 transition hover:bg-amber-500 hover:text-white"
                  title="Chỉnh sửa"
                >
                  <Pencil class="h-4 w-4" />
                </router-link>

                <button
                  type="button"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-rose-600 transition hover:bg-rose-500 hover:text-white"
                  title="Xóa vào thùng rác"
                  @click="deleteSingleQuestion(q.id)"
                >
                  <Trash2 class="h-4 w-4" />
                </button>
              </td>
            </tr>

            <!-- Empty state -->
            <tr v-if="!isLoading && questions.length === 0">
              <td colspan="10" class="py-16 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                  <Search class="h-6 w-6" />
                </div>
                <p class="text-base font-medium text-slate-900">Không tìm thấy câu hỏi nào</p>
                <p class="mt-1 text-sm text-slate-500">
                  Thử thay đổi từ khóa hoặc điều chỉnh bộ lọc.
                </p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Loading -->
      <div v-if="isLoading" class="py-12 text-center">
        <div class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-4 border-[#7C3AED] border-t-transparent"></div>
        <p class="text-sm font-medium text-slate-500">Đang tải dữ liệu...</p>
      </div>
    </div>

    <!-- Pagination -->
    <div
      v-if="lastPage > 1"
      class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
    >
      <div class="flex items-center gap-2 text-sm text-slate-500">
        <span>Hiển thị</span>
        <select
          v-model="perPage"
          class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm font-medium focus:border-[#7C3AED] focus:outline-none"
          @change="fetchQuestions(1)"
        >
          <option :value="15">15</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
        </select>
        <span>câu hỏi / trang</span>
      </div>

      <div class="flex items-center gap-1">
        <button
          class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50 disabled:opacity-40"
          :disabled="currentPage <= 1"
          @click="fetchQuestions(1)"
        >
          <ChevronsLeft class="h-4 w-4" />
        </button>
        <button
          class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50 disabled:opacity-40"
          :disabled="currentPage <= 1"
          @click="fetchQuestions(currentPage - 1)"
        >
          <ChevronLeft class="h-4 w-4" />
        </button>

        <button
          v-for="p in visiblePages"
          :key="p"
          class="flex h-8 w-8 items-center justify-center rounded-lg text-sm font-medium transition"
          :class="p === currentPage
            ? 'bg-[#7C3AED] text-white'
            : 'border border-slate-200 text-slate-700 hover:bg-slate-50'"
          @click="fetchQuestions(p)"
        >
          {{ p }}
        </button>

        <button
          class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50 disabled:opacity-40"
          :disabled="currentPage >= lastPage"
          @click="fetchQuestions(currentPage + 1)"
        >
          <ChevronRight class="h-4 w-4" />
        </button>
        <button
          class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50 disabled:opacity-40"
          :disabled="currentPage >= lastPage"
          @click="fetchQuestions(lastPage)"
        >
          <ChevronsRight class="h-4 w-4" />
        </button>
      </div>

      <div class="text-sm text-slate-500">
        Trang <span class="font-semibold text-slate-900">{{ currentPage }}</span> / {{ lastPage }}
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject } from 'vue'
import { useRouter } from 'vue-router'
import {
  Shield,
  Trash2,
  CheckSquare,
  FileText,
  Globe,
  Lock,
  AlertTriangle,
  Search,
  Filter,
  ChevronDown,
  ChevronRight,
  ChevronLeft,
  ChevronsLeft,
  ChevronsRight,
  Check,
  Eye,
  Pencil,
  User
} from 'lucide-vue-next'
import { adminQuestionsApi, taxonomyApi } from '@/services/api'

const router = useRouter()
const showToast = inject('showToast')
const showConfirm = inject('showConfirm')

const questions = ref([])
const stats = reactive({ total: 0, public: 0, private: 0, reported: 0 })
const isLoading = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const perPage = ref(15)
const selectedIds = ref([])
const subjects = ref([])
const grades = ref([])
const showAdvancedFilters = ref(false)

const filters = reactive({
  search: '',
  subject_id: '',
  grade_id: '',
  difficulty: '',
  visibility: 'all',
  type: ''
})

const publicPercent = computed(() => {
  if (!stats.total) return 0
  return ((stats.public / stats.total) * 100).toFixed(1)
})

const privatePercent = computed(() => {
  if (!stats.total) return 0
  return ((stats.private / stats.total) * 100).toFixed(1)
})

const isAllSelected = computed(() => {
  return questions.value.length > 0 && questions.value.every(q => selectedIds.value.includes(q.id))
})

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedIds.value = []
  } else {
    selectedIds.value = questions.value.map(q => q.id)
  }
}

const visiblePages = computed(() => {
  const pages = []
  const maxPages = 5
  let start = Math.max(1, currentPage.value - 2)
  let end = Math.min(lastPage.value, start + maxPages - 1)
  if (end - start + 1 < maxPages) {
    start = Math.max(1, end - maxPages + 1)
  }
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const goToDetail = (id) => router.push(`/admin/questions/${id}`)

const fetchTaxonomies = async () => {
  try {
    const tree = await taxonomyApi.tree()
    if (tree) {
      if (Array.isArray(tree.subjects)) subjects.value = tree.subjects
      if (Array.isArray(tree.education_levels)) {
        const grList = []
        tree.education_levels.forEach(el => {
          if (Array.isArray(el.grades)) {
            el.grades.forEach(g => {
              if (!grList.some(existing => existing.id === g.id)) grList.push(g)
            })
          }
        })
        grades.value = grList
      }
    }
  } catch (e) {
    console.error('Lỗi tải danh mục:', e)
  }
}

const fetchQuestions = async (page = 1) => {
  isLoading.value = true
  currentPage.value = page
  try {
    const res = await adminQuestionsApi.list({
      page,
      search: filters.search || undefined,
      subject_id: filters.subject_id || undefined,
      grade_id: filters.grade_id || undefined,
      difficulty: filters.difficulty || undefined,
      type: filters.type || undefined,
      visibility: filters.visibility !== 'all' ? filters.visibility : undefined,
      per_page: perPage.value
    })
    questions.value = res.items
    lastPage.value = res.lastPage
    if (res.stats) {
      stats.total = res.stats.total
      stats.public = res.stats.public
      stats.private = res.stats.private
      stats.reported = res.stats.reported
    }
  } catch (err) {
    if (showToast) showToast(`Không tải được danh sách: ${err.message}`, 'error')
  } finally {
    isLoading.value = false
  }
}

const resetFilters = () => {
  filters.search = ''
  filters.subject_id = ''
  filters.grade_id = ''
  filters.difficulty = ''
  filters.visibility = 'all'
  filters.type = ''
  fetchQuestions(1)
}

const handleBulkVisibility = async (isPublic) => {
  if (!selectedIds.value.length) return
  const actionText = isPublic ? 'Duyệt công khai' : 'Chuyển riêng tư'
  const message = `Bạn có chắc muốn ${actionText} cho ${selectedIds.value.length} câu hỏi đã chọn?`

  const execute = async () => {
    try {
      await adminQuestionsApi.bulkToggleVisibility(selectedIds.value, isPublic)
      if (showToast) showToast(`Đã ${actionText} thành công.`, 'success')
      selectedIds.value = []
      fetchQuestions(currentPage.value)
    } catch (err) {
      if (showToast) showToast(`Lỗi: ${err.message}`, 'error')
    }
  }

  if (showConfirm) showConfirm(`${actionText} hàng loạt`, message, execute)
  else if (confirm(message)) execute()
}

const deleteSingleQuestion = async (id) => {
  const message = 'Bạn có chắc muốn chuyển câu hỏi này vào thùng rác?'
  const execute = async () => {
    try {
      await adminQuestionsApi.remove(id)
      if (showToast) showToast('Đã chuyển vào thùng rác.', 'success')
      fetchQuestions(currentPage.value)
    } catch (err) {
      if (showToast) showToast(`Xóa thất bại: ${err.message}`, 'error')
    }
  }
  if (showConfirm) showConfirm('Xóa câu hỏi', message, execute)
  else if (confirm(message)) execute()
}

const handleBulkDelete = async () => {
  if (!selectedIds.value.length) return
  const message = `Bạn có chắc muốn chuyển ${selectedIds.value.length} câu hỏi vào thùng rác?`
  const execute = async () => {
    try {
      await adminQuestionsApi.bulkDelete(selectedIds.value)
      if (showToast) showToast('Xóa hàng loạt thành công.', 'success')
      selectedIds.value = []
      fetchQuestions(currentPage.value)
    } catch (err) {
      if (showToast) showToast(`Lỗi: ${err.message}`, 'error')
    }
  }
  if (showConfirm) showConfirm('Xóa hàng loạt', message, execute)
  else if (confirm(message)) execute()
}

onMounted(() => {
  fetchTaxonomies()
  fetchQuestions()
})
</script>