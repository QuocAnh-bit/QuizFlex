<template>
  <section class="space-y-6" @click="closeDropdown">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-slate-500">
      <router-link to="/admin" class="hover:text-[#7C3AED] transition-colors">
        Dashboard
      </router-link>
      <ChevronRight class="h-4 w-4" />
      <span class="font-medium text-slate-900">Quản lý Bộ môn</span>
    </div>

    <!-- Header -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex items-start gap-4 min-w-0">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#F5F3FF] text-[#7C3AED]">
            <BookOpen class="h-6 w-6" />
          </div>
          <div class="min-w-0">
            <h1 class="text-3xl font-black tracking-[-0.04em] text-[var(--text)] truncate">
              Quản lý Bộ môn & Danh mục
            </h1>
            <p class="mt-1 text-sm text-slate-500 leading-relaxed">
              Danh sách bộ môn hệ thống, cấu hình mã môn, phân nhóm và hỗ trợ xóa mềm an toàn.
            </p>
          </div>
        </div>

        <div class="flex shrink-0 items-center gap-3">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-medium transition"
            :class="activeTab === 'trash'
              ? 'border-[#7C3AED]/30 bg-[#F5F3FF] text-[#7C3AED]'
              : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
            @click="toggleTab(activeTab === 'trash' ? 'active' : 'trash')"
          >
            <component :is="activeTab === 'trash' ? LayoutList : Trash2" class="h-4 w-4" />
            {{ activeTab === 'trash' ? 'Danh sách chính' : 'Thùng rác môn học' }}
            <span
              v-if="stats.trashed > 0"
              class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-600"
            >
              {{ stats.trashed }}
            </span>
          </button>

          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-[#7C3AED] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#6D28D9]"
            @click="openCreateModal"
          >
            <Plus class="h-4 w-4" />
            Thêm bộ môn mới
          </button>
        </div>
      </div>

      <!-- KPI Stats -->
      <div class="mt-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#F5F3FF] text-[#7C3AED]">
              <BookOpen class="h-5 w-5" />
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Tổng bộ môn</p>
              <p class="text-2xl font-bold text-slate-900">{{ stats.total || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
              <Atom class="h-5 w-5" />
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Khoa học Tự nhiên</p>
              <p class="text-2xl font-bold text-slate-900">{{ naturalGroupCount }}</p>
            </div>
          </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
              <Languages class="h-5 w-5" />
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Xã hội & Ngoại ngữ</p>
              <p class="text-2xl font-bold text-slate-900">{{ socialGroupCount }}</p>
            </div>
          </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
              <Trash2 class="h-5 w-5" />
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Thùng rác</p>
              <p class="text-2xl font-bold text-slate-900">{{ stats.trashed || 0 }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
      <div class="relative flex-1 min-w-[240px]">
        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <input
          v-model="filters.search"
          type="text"
          class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
          placeholder="Tìm tên môn hoặc mã môn..."
          @input="debounceSearch"
        />
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        <select
          v-model="filters.category_group"
          class="rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
          @change="fetchSubjects(1)"
        >
          <option value="">Tất cả nhóm môn</option>
          <option value="natural">Khoa học Tự nhiên</option>
          <option value="social">Khoa học Xã hội</option>
          <option value="foreign_language">Ngoại ngữ</option>
          <option value="technology">Tin học & Công nghệ</option>
          <option value="other">Năng khiếu & Khác</option>
        </select>

        <button
          v-if="filters.search || filters.category_group"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-rose-600 transition hover:bg-rose-50"
          @click="resetFilters"
        >
          <X class="h-4 w-4" />
          Bỏ lọc
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="py-16 text-center">
      <div class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-4 border-[#7C3AED] border-t-transparent"></div>
      <p class="text-sm font-medium text-slate-500">Đang tải danh sách bộ môn...</p>
    </div>

    <!-- Empty state -->
    <div
      v-else-if="displayedSubjects.length === 0"
      class="rounded-xl border border-slate-200 bg-white p-12 text-center shadow-sm"
    >
      <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
        <FolderOpen class="h-6 w-6" />
      </div>
      <h3 class="text-base font-medium text-slate-900">
        {{ activeTab === 'trash' ? 'Thùng rác trống' : 'Không tìm thấy bộ môn nào' }}
      </h3>
      <p class="mt-1 text-sm text-slate-500">
        {{ activeTab === 'trash'
          ? 'Chưa có môn học nào bị xóa mềm.'
          : 'Thử thay đổi từ khóa tìm kiếm hoặc thêm bộ môn mới.' }}
      </p>
    </div>

    <!-- Table -->
    <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
              <th class="py-3 pl-6 pr-3 w-[36%] min-w-[210px]">Môn học</th>
              <th class="py-3 px-3 w-[28%] min-w-[170px]">Nhóm môn</th>
              <th class="py-3 px-2 w-[11%] text-center min-w-[90px]">Số Quiz</th>
              <th class="py-3 px-2 w-[11%] text-center min-w-[100px]">Số câu hỏi</th>
              <th class="py-3 pl-2 pr-7 w-[14%] text-right min-w-[130px]">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="subject in displayedSubjects"
              :key="subject.id"
              class="transition hover:bg-slate-50"
            >
              <!-- Môn học -->
              <td class="py-3.5 pl-6 pr-3">
                <div class="flex items-center gap-3.5 min-w-0">
                  <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F5F3FF] text-[#7C3AED]">
                    <component :is="getSubjectIcon(subject.code, subject.name)" class="h-5 w-5" />
                  </div>
                  <div class="min-w-0 flex-1">
                    <h4 class="truncate font-semibold text-slate-900">
                      {{ subject.name }}
                    </h4>
                    <span class="mt-0.5 font-mono text-xs font-medium text-[#7C3AED]">
                      #{{ subject.code }}
                    </span>
                  </div>
                </div>
              </td>

              <!-- Nhóm môn -->
              <td class="py-3.5 px-3">
                <span
                  class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium"
                  :class="getCategoryGroupClass(subject.category_group)"
                >
                  <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                  {{ getCategoryGroupLabel(subject.category_group) }}
                </span>
              </td>

              <!-- Số Quiz -->
              <td class="py-3.5 px-2 text-center">
                <span class="tabular-nums text-base font-semibold text-slate-900">
                  {{ subject.quizzes_count || 0 }}
                </span>
                <span class="ml-1 text-xs text-slate-500">Quiz</span>
              </td>

              <!-- Số câu hỏi -->
              <td class="py-3.5 px-2 text-center">
                <span class="tabular-nums text-base font-semibold text-slate-900">
                  {{ subject.questions_count || 0 }}
                </span>
                <span class="ml-1 text-xs text-slate-500">Câu hỏi</span>
              </td>

              <!-- Thao tác -->
              <td class="py-3.5 pl-2 pr-7 text-right">
                <div class="relative flex items-center justify-end gap-1.5">
                  <template v-if="activeTab === 'active'">
                    <button
                      type="button"
                      class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:border-[#7C3AED] hover:text-[#7C3AED]"
                      @click.stop="openEditModal(subject)"
                    >
                      Sửa
                    </button>

                    <button
                      type="button"
                      class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50"
                      @click.stop="toggleDropdown(subject.id)"
                    >
                      <MoreVertical class="h-4 w-4" />
                    </button>

                    <!-- Dropdown -->
                    <div
                      v-if="openMenuId === subject.id"
                      class="absolute right-0 top-10 z-50 min-w-[170px] rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg"
                      @click.stop
                    >
                      <button
                        type="button"
                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                        @click="openEditModal(subject); closeDropdown()"
                      >
                        <Pencil class="h-4 w-4" />
                        Chỉnh sửa
                      </button>

                      <router-link
                        :to="`/admin/question-bank?subject_id=${subject.id}`"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                        @click="closeDropdown"
                      >
                        <HelpCircle class="h-4 w-4" />
                        Xem câu hỏi
                      </router-link>

                      <router-link
                        :to="`/admin/quizzes?subject_id=${subject.id}`"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                        @click="closeDropdown"
                      >
                        <FileText class="h-4 w-4" />
                        Xem Quiz
                      </router-link>

                      <div class="my-1 border-t border-slate-100"></div>

                      <button
                        type="button"
                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-rose-600 hover:bg-rose-50"
                        @click="confirmSoftDelete(subject); closeDropdown()"
                      >
                        <Trash2 class="h-4 w-4" />
                        Xóa môn học
                      </button>
                    </div>
                  </template>

                  <template v-else>
                    <button
                      type="button"
                      class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100"
                      @click="handleRestore(subject)"
                    >
                      Khôi phục
                    </button>

                    <button
                      type="button"
                      class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-sm font-medium text-rose-700 transition hover:bg-rose-100"
                      @click="confirmForceDelete(subject)"
                    >
                      Xóa vĩnh viễn
                    </button>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Server-side Pagination -->
      <div class="px-5 pb-5">
        <AppPagination
          :current-page="pagination.currentPage"
          :last-page="pagination.lastPage"
          :total="pagination.total"
          :per-page="pagination.perPage"
          item-label="bộ môn"
          @change="fetchSubjects"
        />
      </div>
    </div>

    <!-- Modal Thêm / Sửa -->
    <div
      v-if="isModalOpen"
      class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4 sm:p-6"
      @click.self="closeModal"
    >
      <div class="relative w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl sm:p-7">
        <!-- Header -->
        <div class="mb-5 flex items-center justify-between border-b border-slate-100 pb-4">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#F5F3FF] text-[#7C3AED]">
              <component :is="getSubjectIcon(modalForm.code, modalForm.name)" class="h-5 w-5" />
            </div>
            <div>
              <h3 class="text-lg font-semibold text-slate-900">
                {{ isEditing ? 'Chỉnh sửa Bộ môn' : 'Thêm Bộ môn mới' }}
              </h3>
              <p class="text-xs text-slate-500">
                Thiết lập thông tin bộ môn chuẩn hóa toàn hệ thống
              </p>
            </div>
          </div>
          <button
            type="button"
            class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
            @click="closeModal"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <!-- Form -->
        <form class="space-y-4" @submit.prevent="saveSubject">
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-1.5">
              <label class="text-xs font-medium text-slate-500">
                Tên Bộ môn <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="modalForm.name"
                type="text"
                required
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
                placeholder="VD: Toán học, Tin học..."
                @input="autoGenerateCode"
              />
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-medium text-slate-500">
                Mã Bộ môn <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="modalForm.code"
                type="text"
                required
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 font-mono text-sm text-[#7C3AED] focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
                placeholder="VD: math, informatics"
              />
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-1.5">
              <label class="text-xs font-medium text-slate-500">Nhóm bộ môn</label>
              <select
                v-model="modalForm.category_group"
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
              >
                <option value="natural">Khoa học Tự nhiên</option>
                <option value="social">Khoa học Xã hội</option>
                <option value="foreign_language">Ngoại ngữ</option>
                <option value="technology">Tin học & Công nghệ</option>
                <option value="other">Năng khiếu & Khác</option>
              </select>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-medium text-slate-500">Thứ tự hiển thị</label>
              <input
                v-model.number="modalForm.order"
                type="number"
                min="0"
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
                placeholder="0"
              />
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
            <button
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
              @click="closeModal"
            >
              Hủy
            </button>
            <button
              type="submit"
              class="rounded-lg bg-[#7C3AED] px-5 py-2 text-sm font-semibold text-white transition hover:bg-[#6D28D9] disabled:opacity-60"
              :disabled="isSubmitting"
            >
              {{ isSubmitting ? 'Đang lưu...' : (isEditing ? 'Lưu thay đổi' : 'Tạo môn học') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject } from 'vue'
import {
  ChevronRight,
  BookOpen,
  Plus,
  Trash2,
  LayoutList,
  Atom,
  Languages,
  Search,
  X,
  FolderOpen,
  MoreVertical,
  Pencil,
  HelpCircle,
  FileText,
  Calculator,
  FlaskConical,
  Dna,
  Landmark,
  Globe,
  Scale,
  Monitor
} from 'lucide-vue-next'
import { adminSubjectsApi, formatApiErrorMessage } from '@/services/api'
import { useAppLoading } from '@/composables/useAppLoading'
import AppPagination from '@/components/common/AppPagination.vue'

const { beginTask, endTask } = useAppLoading()

const showToast = inject('showToast', (msg) => alert(msg))

const isLoading = ref(true)
const isSubmitting = ref(false)
const activeTab = ref('active')
const openMenuId = ref(null)

const subjects = ref([])
const trashedSubjects = ref([])
const stats = reactive({ total: 0, trashed: 0 })

const pagination = reactive({
  currentPage: 1,
  lastPage: 1,
  total: 0,
  perPage: 10
})

const filters = reactive({
  search: '',
  category_group: ''
})

let searchTimeout = null

const isModalOpen = ref(false)
const isEditing = ref(false)
const currentSubjectId = ref(null)

const modalForm = reactive({
  name: '',
  code: '',
  category_group: 'natural',
  order: 0
})

const getSubjectIcon = (code, name) => {
  const key = ((code || '') + ' ' + (name || '')).toLowerCase()

  if (key.includes('math') || key.includes('toan')) return Calculator
  if (key.includes('lit') || key.includes('van') || key.includes('viet')) return BookOpen
  if (key.includes('eng') || key.includes('anh') || key.includes('foreign') || key.includes('lang')) return Languages
  if (key.includes('phys') || key.includes('ly')) return Atom
  if (key.includes('chem') || key.includes('hoa')) return FlaskConical
  if (key.includes('bio') || key.includes('sinh')) return Dna
  if (key.includes('hist') || key.includes('su')) return Landmark
  if (key.includes('geo') || key.includes('dia')) return Globe
  if (key.includes('civic') || key.includes('gdcd') || key.includes('phap')) return Scale
  if (key.includes('info') || key.includes('tech') || key.includes('tin') || key.includes('cong')) return Monitor

  return BookOpen
}

const fetchSubjects = async (page = 1) => {
  isLoading.value = true
  pagination.currentPage = page
  try {
    const params = {
      search: filters.search.trim() || undefined,
      category_group: filters.category_group || undefined,
      page,
      per_page: pagination.perPage
    }
    if (activeTab.value === 'active') {
      const data = await adminSubjectsApi.list(params)
      subjects.value = data.subjects || data.items || []
      pagination.total = data.total || 0
      pagination.currentPage = data.currentPage || page
      pagination.lastPage = data.lastPage || 1
      stats.total = data.stats?.total || data.total || 0
      stats.trashed = data.stats?.trashed || 0
    } else {
      const data = await adminSubjectsApi.trash(params)
      trashedSubjects.value = data.subjects || data.items || []
      pagination.total = data.total || 0
      pagination.currentPage = data.currentPage || page
      pagination.lastPage = data.lastPage || 1
    }
  } catch (e) {
    console.error('Lỗi khi tải danh sách môn học:', e)
    showToast('Không thể tải danh sách môn học.', 'error')
  } finally {
    isLoading.value = false
  }
}

const toggleTab = (tab) => {
  activeTab.value = tab
  pagination.currentPage = 1
  fetchSubjects(1)
}

const toggleDropdown = (id) => {
  openMenuId.value = openMenuId.value === id ? null : id
}

const closeDropdown = () => {
  openMenuId.value = null
}

const debounceSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchSubjects(1), 300)
}

const resetFilters = () => {
  filters.search = ''
  filters.category_group = ''
  fetchSubjects(1)
}

const displayedSubjects = computed(() =>
  activeTab.value === 'active' ? subjects.value : trashedSubjects.value
)

const naturalGroupCount = computed(() =>
  subjects.value.filter(s => s.category_group === 'natural' || s.category_group === 'technology').length
)

const socialGroupCount = computed(() =>
  subjects.value.filter(s => s.category_group === 'social' || s.category_group === 'foreign_language').length
)

const getCategoryGroupLabel = (group) => {
  switch (group) {
    case 'natural': return 'Khoa học Tự nhiên'
    case 'social': return 'Khoa học Xã hội'
    case 'foreign_language': return 'Ngoại ngữ'
    case 'technology': return 'Tin học & Công nghệ'
    default: return 'Năng khiếu / Khác'
  }
}

const getCategoryGroupClass = (group) => {
  switch (group) {
    case 'natural': return 'bg-emerald-50 text-emerald-700'
    case 'social': return 'bg-amber-50 text-amber-700'
    case 'foreign_language': return 'bg-sky-50 text-sky-700'
    case 'technology': return 'bg-[#F5F3FF] text-[#7C3AED]'
    default: return 'bg-slate-100 text-slate-600'
  }
}

const autoGenerateCode = () => {
  if (!isEditing.value && modalForm.name) {
    modalForm.code = modalForm.name
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/đ/g, 'd')
      .replace(/[^a-z0-9]/g, '_')
      .replace(/_+/g, '_')
      .replace(/^_+|_+$/g, '')
  }
}

const openCreateModal = () => {
  isEditing.value = false
  currentSubjectId.value = null
  modalForm.name = ''
  modalForm.code = ''
  modalForm.category_group = 'natural'
  modalForm.order = 0
  isModalOpen.value = true
}

const openEditModal = (subject) => {
  isEditing.value = true
  currentSubjectId.value = subject.id
  modalForm.name = subject.name
  modalForm.code = subject.code
  modalForm.category_group = subject.category_group || 'natural'
  modalForm.order = subject.order || 0
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
}

const saveSubject = async () => {
  if (!modalForm.name.trim() || !modalForm.code.trim()) {
    showToast('Vui lòng nhập tên và mã bộ môn.', 'error')
    return
  }

  isSubmitting.value = true
  try {
    if (isEditing.value) {
      await adminSubjectsApi.update(currentSubjectId.value, modalForm)
      showToast(`Đã cập nhật môn '${modalForm.name}' thành công.`, 'success')
    } else {
      await adminSubjectsApi.create(modalForm)
      showToast(`Đã tạo môn '${modalForm.name}' thành công.`, 'success')
    }
    closeModal()
    fetchSubjects()
  } catch (e) {
    console.error('Lỗi khi lưu bộ môn:', e)
    showToast(formatApiErrorMessage(e, 'Có lỗi xảy ra khi lưu môn học.'), 'error')
  } finally {
    isSubmitting.value = false
  }
}

const confirmSoftDelete = async (subject) => {
  if (!confirm(`Bạn có chắc chắn muốn chuyển môn học '${subject.name}' vào Thùng rác không?`)) return
  try {
    await adminSubjectsApi.softDelete(subject.id)
    showToast(`Đã chuyển môn '${subject.name}' vào Thùng rác.`, 'success')
    fetchSubjects()
  } catch (e) {
    console.error('Lỗi khi xóa mềm môn học:', e)
    showToast('Không thể xóa mềm môn học này.', 'error')
  }
}

const handleRestore = async (subject) => {
  try {
    await adminSubjectsApi.restore(subject.id)
    showToast(`Đã khôi phục môn '${subject.name}'.`, 'success')
    fetchSubjects()
  } catch (e) {
    console.error('Lỗi khi khôi phục môn học:', e)
    showToast('Không thể khôi phục môn học.', 'error')
  }
}

const confirmForceDelete = async (subject) => {
  if (!confirm(`HÀNH ĐỘNG NGUY HIỂM: Bạn có chắc muốn XÓA VĨNH VIỄN môn '${subject.name}'? Hành động này không thể hoàn tác!`)) return
  try {
    const res = await adminSubjectsApi.forceDelete(subject.id)
    if (res.success) {
      showToast(res.message || 'Đã xóa vĩnh viễn môn học.', 'success')
      fetchSubjects()
    } else {
      showToast(res.message || 'Không thể xóa vĩnh viễn môn học.', 'error')
    }
  } catch (e) {
    console.error('Lỗi khi xóa vĩnh viễn môn học:', e)
    showToast(e.response?.data?.message || 'Không thể xóa vĩnh viễn môn học này.', 'error')
  }
}

onMounted(async () => {
  beginTask()
  try {
    await fetchSubjects()
  } finally {
    endTask()
  }
})
</script>
