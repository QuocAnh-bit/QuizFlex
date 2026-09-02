<template>
  <section class="space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-slate-500">
      <router-link to="/admin" class="hover:text-[#7C3AED] transition-colors">
        Dashboard
      </router-link>
      <ChevronRight class="h-4 w-4" />
      <router-link to="/admin/question-bank" class="hover:text-[#7C3AED] transition-colors">
        Ngân hàng câu hỏi
      </router-link>
      <ChevronRight class="h-4 w-4" />
      <span class="font-medium text-rose-600">Thùng rác câu hỏi</span>
    </div>

    <!-- Header -->
    <div class="rounded-2xl border border-rose-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex items-start gap-4">
          <button
            type="button"
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-[#7C3AED] hover:text-white"
            title="Quay lại"
            @click="$router.push('/admin/question-bank')"
          >
            <ArrowLeft class="h-5 w-5" />
          </button>

          <div>
            <div class="flex flex-wrap items-center gap-3">
              <h1 class="text-3xl font-black tracking-[-0.04em] text-[var(--text)]">
                Thùng rác câu hỏi
              </h1>
              <span class="inline-flex items-center gap-1.5 rounded-md bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">
                <Trash2 class="h-3.5 w-3.5" />
                {{ trashCount }} câu hỏi đã xóa
              </span>
            </div>
            <p class="mt-1 text-sm text-slate-500">
              Quản lý các câu hỏi đã bị xóa mềm. Bạn có thể khôi phục hoặc xóa vĩnh viễn khỏi hệ thống.
            </p>
          </div>
        </div>

        <router-link
          to="/admin/question-bank"
          class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
        >
          <ArrowLeft class="h-4 w-4" />
          Quay lại Ngân hàng câu hỏi
        </router-link>
      </div>
    </div>

    <!-- Search -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex max-w-xl items-center gap-3">
        <div class="relative flex-1">
          <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            v-model="search"
            type="text"
            placeholder="Tìm câu hỏi trong thùng rác theo nội dung..."
            class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
            @keyup.enter="fetchTrash(1)"
          />
        </div>
        <button
          type="button"
          class="rounded-lg bg-[#7C3AED] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#6D28D9]"
          @click="fetchTrash(1)"
        >
          Tìm kiếm
        </button>
      </div>
    </div>

    <!-- Bulk actions -->
    <div
      v-if="selectedIds.length > 0"
      class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-rose-200 bg-rose-50 p-4"
    >
      <div class="flex items-center gap-3">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-600 text-sm font-bold text-white">
          {{ selectedIds.length }}
        </span>
        <span class="text-sm font-semibold text-slate-900">
          Đã chọn {{ selectedIds.length }} câu hỏi trong thùng rác
        </span>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100"
          @click="handleBulkRestore"
        >
          <RotateCcw class="h-4 w-4" />
          Khôi phục hàng loạt
        </button>

        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg border border-rose-300 bg-rose-100 px-3.5 py-2 text-sm font-medium text-rose-700 transition hover:bg-rose-200"
          @click="handleBulkForceDelete"
        >
          <Trash2 class="h-4 w-4" />
          Xóa vĩnh viễn hàng loạt
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
              <th class="p-4 text-center">Người tạo</th>
              <th class="p-4 text-center">Ngày xóa</th>
              <th class="min-w-[160px] p-4 text-center">Thao tác</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="q in questions"
              :key="q.id"
              class="transition hover:bg-slate-50"
              :class="{ 'bg-rose-50/60': selectedIds.includes(q.id) }"
            >
              <td class="p-4 text-center">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 text-[#7C3AED] focus:ring-[#7C3AED]"
                  :value="q.id"
                  v-model="selectedIds"
                />
              </td>

              <td class="p-4 text-center font-semibold text-rose-600">
                #{{ q.id }}
              </td>

              <td class="max-w-md p-4">
                <div class="line-clamp-2 font-medium text-slate-900" :title="q.content">
                  {{ q.content }}
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

              <td class="p-4 text-center text-xs font-medium text-slate-500">
                <div class="flex items-center justify-center gap-1.5">
                  <User class="h-3.5 w-3.5" />
                  <span class="max-w-[110px] truncate">{{ q.author_name || 'Vô danh' }}</span>
                </div>
              </td>

              <td class="p-4 text-center text-xs font-medium text-rose-700 whitespace-nowrap">
                  <span>{{ formatDate(q.deleted_at || q.updated_at) }}</span>
              </td>

              <td class="space-x-1.5 p-4 text-center whitespace-nowrap">
                <router-link
                  :to="`/admin/questions/${q.id}`"
                  class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                  title="Xem chi tiết câu hỏi"
                >
                  <Eye class="h-3.5 w-3.5" />
                  <span>Xem</span>
                </router-link>

                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100"
                  @click="restoreSingle(q.id)"
                >
                  <RotateCcw class="h-3.5 w-3.5" />
                  Khôi phục
                </button>

                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-100"
                  @click="forceDeleteSingle(q.id)"
                >
                  <Trash2 class="h-3.5 w-3.5" />
                  Xóa vĩnh viễn
                </button>
              </td>
            </tr>

            <!-- Empty state -->
            <tr v-if="!isLoading && questions.length === 0">
              <td colspan="8" class="py-16 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                  <Trash2 class="h-6 w-6" />
                </div>
                <p class="text-base font-medium text-slate-900">Thùng rác câu hỏi trống</p>
                <p class="mt-1 text-sm text-slate-500">
                  Không có câu hỏi nào bị xóa mềm trong thùng rác.
                </p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Loading -->
      <div v-if="isLoading" class="py-12 text-center">
        <div class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-4 border-rose-500 border-t-transparent"></div>
        <p class="text-sm font-medium text-slate-500">Đang tải danh sách Thùng rác...</p>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="trashCount > 0" class="pt-2">
      <AppPagination
        :current-page="currentPage"
        :last-page="lastPage"
        :total="trashCount"
        :per-page="10"
        item-label="câu hỏi đã xóa"
        @change="fetchTrash"
      />
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import {
  ChevronRight,
  ArrowLeft,
  Trash2,
  Search,
  RotateCcw,
  User,
  Eye,
  Clock
} from 'lucide-vue-next'
import { adminQuestionsApi } from '@/services/api'
import { useAppLoading } from '@/composables/useAppLoading'
import AppPagination from '@/components/common/AppPagination.vue'

const { beginTask, endTask } = useAppLoading()

const showToast = inject('showToast')
const showConfirm = inject('showConfirm')

const questions = ref([])
const trashCount = ref(0)
const isLoading = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const search = ref('')
const selectedIds = ref([])

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

const fetchTrash = async (page = 1) => {
  isLoading.value = true
  currentPage.value = page
  try {
    const res = await adminQuestionsApi.trash({
      page,
      search: search.value || undefined,
      per_page: 10
    })
    questions.value = res.items
    lastPage.value = res.lastPage
    trashCount.value = res.trashCount
  } catch (err) {
    if (showToast) showToast(`Không tải được thùng rác: ${err.message}`, 'error')
  } finally {
    isLoading.value = false
  }
}

const restoreSingle = async (id) => {
  try {
    await adminQuestionsApi.restore(id)
    if (showToast) showToast('Khôi phục câu hỏi thành công!', 'success')
    fetchTrash(currentPage.value)
  } catch (err) {
    if (showToast) showToast(`Khôi phục thất bại: ${err.message}`, 'error')
  }
}

const forceDeleteSingle = async (id) => {
  const msg = 'Xóa vĩnh viễn câu hỏi này? Dữ liệu không thể khôi phục.'
  const executeAction = async () => {
    try {
      await adminQuestionsApi.forceDelete(id)
      if (showToast) showToast('Đã xóa vĩnh viễn câu hỏi.', 'success')
      fetchTrash(currentPage.value)
    } catch (err) {
      if (showToast) showToast(`Xóa thất bại: ${err.message}`, 'error')
    }
  }

  if (showConfirm) {
    showConfirm('Xóa vĩnh viễn', msg, executeAction)
  } else if (confirm(msg)) {
    executeAction()
  }
}

const handleBulkRestore = async () => {
  if (selectedIds.value.length === 0) return
  try {
    await adminQuestionsApi.bulkRestore(selectedIds.value)
    if (showToast) showToast(`Đã khôi phục ${selectedIds.value.length} câu hỏi.`, 'success')
    selectedIds.value = []
    fetchTrash(currentPage.value)
  } catch (err) {
    if (showToast) showToast(`Lỗi: ${err.message}`, 'error')
  }
}

const handleBulkForceDelete = async () => {
  if (selectedIds.value.length === 0) return
  const msg = `Xóa vĩnh viễn ${selectedIds.value.length} câu hỏi đã chọn? Dữ liệu không thể khôi phục.`
  const executeAction = async () => {
    try {
      await adminQuestionsApi.bulkForceDelete(selectedIds.value)
      if (showToast) showToast('Đã xóa vĩnh viễn các câu hỏi đã chọn.', 'success')
      selectedIds.value = []
      fetchTrash(currentPage.value)
    } catch (err) {
      if (showToast) showToast(`Lỗi: ${err.message}`, 'error')
    }
  }

  if (showConfirm) {
    showConfirm('Xóa vĩnh viễn hàng loạt', msg, executeAction)
  } else if (confirm(msg)) {
    executeAction()
  }
}

onMounted(async () => {
  beginTask()
  try {
    await fetchTrash()
  } finally {
    endTask()
  }
})
</script>
