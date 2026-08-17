<template>
  <section class="grid gap-6">
    <!-- Top Breadcrumb & Header Banner -->
    <div class="space-y-4">
      <div class="flex items-center gap-2 text-xs font-bold text-[var(--muted)]">
        <router-link to="/admin" class="hover:text-[var(--primary)] transition">Dashboard</router-link>
        <span>&rsaquo;</span>
        <router-link to="/admin/question-bank" class="hover:text-[var(--primary)] transition">Question Bank</router-link>
        <span>&rsaquo;</span>
        <span class="text-rose-400 font-bold">Thùng rác câu hỏi</span>
      </div>

      <div class="relative overflow-hidden rounded-[2rem] border border-rose-500/30 bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-rose-500/15 blur-3xl"></div>

        <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-center">
          <div class="flex items-center gap-4">
            <button
              type="button"
              class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] text-lg text-[var(--text)] transition hover:bg-[var(--primary)] hover:text-white"
              title="Quay lại"
              @click="$router.push('/admin/question-bank')"
            >
              ←
            </button>
            <div>
              <div class="flex items-center gap-3">
                <h1 class="text-3xl font-black tracking-[-0.05em] text-rose-400">
                  🗑️ Thùng rác câu hỏi
                </h1>
                <span class="rounded-full bg-rose-500/20 border border-rose-500/30 px-3 py-1 text-xs font-black text-rose-300">
                  {{ trashCount }} câu hỏi đã xóa
                </span>
              </div>
              <p class="mt-1 text-xs sm:text-sm text-[var(--muted)]">
                Quản lý các câu hỏi đã bị xóa mềm khỏi hệ thống. Bạn có thể khôi phục lại hoặc xóa vĩnh viễn khỏi cơ sở dữ liệu.
              </p>
            </div>
          </div>

          <div class="flex shrink-0 items-center gap-3">
            <router-link
              to="/admin/question-bank"
              class="btn-ghost flex items-center gap-2 px-5 py-2.5 text-xs font-bold text-[var(--text)]"
            >
              <span>← Quay lại Ngân hàng câu hỏi</span>
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Search Bar Panel -->
    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <div class="flex items-center gap-3 rounded-2xl border border-[var(--border)] bg-[var(--input-bg)] px-4 py-2.5 transition focus-within:border-[var(--border-strong)] max-w-xl">
        <span class="text-base text-[var(--muted)]">🔍</span>
        <input
          v-model="search"
          type="text"
          placeholder="Tìm câu hỏi trong thùng rác theo nội dung..."
          class="w-full bg-transparent text-xs font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]"
          @keyup.enter="fetchTrash(1)"
        />
        <button type="button" class="btn-primary !py-1 !px-3 text-xs font-bold shrink-0" @click="fetchTrash(1)">
          Tìm kiếm
        </button>
      </div>
    </article>

    <!-- Bulk Actions Panel (Hiển thị khi tick chọn câu hỏi) -->
    <transition enter-active-class="transition duration-300 ease-out" enter-from-class="-translate-y-2 opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="translate-y-0 opacity-100" leave-to-class="-translate-y-2 opacity-0">
      <div v-if="selectedIds.length > 0" class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-rose-500/40 bg-rose-500/10 p-4 shadow-lg backdrop-blur-xl">
        <div class="flex items-center gap-3">
          <span class="grid h-8 w-8 place-items-center rounded-xl bg-rose-500 text-white text-xs font-black">
            {{ selectedIds.length }}
          </span>
          <span class="text-sm font-black text-white">Đã chọn {{ selectedIds.length }} câu hỏi trong thùng rác</span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            class="rounded-full border border-emerald-500/30 bg-emerald-500/20 px-4 py-2 text-xs font-black text-emerald-300 transition hover:bg-emerald-500/30"
            @click="handleBulkRestore"
          >
            ♻️ Khôi phục hàng loạt
          </button>

          <button
            type="button"
            class="rounded-full border border-rose-500/40 bg-rose-600/30 px-4 py-2 text-xs font-black text-rose-200 transition hover:bg-rose-600/50"
            @click="handleBulkForceDelete"
          >
            🔥 Xóa vĩnh viễn hàng loạt
          </button>

          <button
            type="button"
            class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-2 text-xs font-bold text-[var(--muted)] hover:text-[var(--text)]"
            @click="selectedIds = []"
          >
            Hủy chọn
          </button>
        </div>
      </div>
    </transition>

    <!-- Data Table Container -->
    <div class="overflow-x-auto rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <table class="w-full border-collapse text-left text-xs sm:text-sm text-[var(--text)]">
        <thead>
          <tr class="border-b border-[var(--border)] bg-[var(--surface-soft)] text-[11px] font-black uppercase tracking-wider text-[var(--muted)]">
            <th class="p-4 w-10 text-center">
              <input
                type="checkbox"
                class="h-4 w-4 rounded border-[var(--border)] accent-[var(--primary)] cursor-pointer"
                :checked="isAllSelected"
                @change="toggleSelectAll"
              />
            </th>
            <th class="p-4 w-16 text-center">ID</th>
            <th class="p-4 min-w-[260px]">NỘI DUNG CÂU HỎI</th>
            <th class="p-4 text-center">MÔN HỌC</th>
            <th class="p-4 text-center">KHỐI LỚP</th>
            <th class="p-4 text-center">NGƯỜI TẠO</th>
            <th class="p-4 text-center">NGÀY XÓA</th>
            <th class="p-4 text-center min-w-[140px]">THAO TÁC</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[var(--border)]">
          <tr
            v-for="q in questions"
            :key="q.id"
            class="transition hover:bg-[var(--surface-soft)]/60"
            :class="{ 'bg-rose-500/10': selectedIds.includes(q.id) }"
          >
            <td class="p-4 text-center">
              <input
                type="checkbox"
                class="h-4 w-4 rounded border-[var(--border)] accent-[var(--primary)] cursor-pointer"
                :value="q.id"
                v-model="selectedIds"
              />
            </td>

            <td class="p-4 text-center font-black text-rose-400">
              #{{ q.id }}
            </td>

            <td class="p-4 max-w-md">
              <div class="font-bold text-[var(--text)] line-clamp-2 leading-relaxed" :title="q.content">
                {{ q.content }}
              </div>
            </td>

            <td class="p-4 text-center">
              <span v-if="q.subject_name" class="inline-flex items-center gap-1 rounded-full bg-[var(--surface-soft)] border border-[var(--border)] px-2.5 py-1 text-xs font-bold text-[var(--text)]">
                📖 {{ q.subject_name }}
              </span>
              <span v-else class="text-xs text-[var(--muted)]">--</span>
            </td>

            <td class="p-4 text-center">
              <span v-if="q.grade_name" class="inline-flex items-center gap-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 px-2.5 py-1 text-xs font-bold text-indigo-300">
                🎓 {{ q.grade_name }}
              </span>
              <span v-else class="text-xs text-[var(--muted)]">--</span>
            </td>

            <td class="p-4 text-center text-xs font-bold text-[var(--muted)]">
              👤 {{ q.author_name || 'Vô danh' }}
            </td>

            <td class="p-4 text-center text-xs font-semibold text-rose-400">
              {{ formatDate(q.deleted_at || q.updated_at) }}
            </td>

            <td class="p-4 text-center whitespace-nowrap space-x-2">
              <button
                type="button"
                class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 text-xs font-black text-emerald-300 transition hover:bg-emerald-500 hover:text-slate-950"
                @click="restoreSingle(q.id)"
              >
                ♻️ Khôi phục
              </button>

              <button
                type="button"
                class="rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1.5 text-xs font-black text-rose-400 transition hover:bg-rose-600 hover:text-white"
                @click="forceDeleteSingle(q.id)"
              >
                🔥 Xóa vĩnh viễn
              </button>
            </td>
          </tr>

          <!-- Empty State -->
          <tr v-if="!isLoading && questions.length === 0">
            <td colspan="8" class="text-center py-12 text-[var(--muted)] font-bold">
              <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-[var(--surface-soft)] text-2xl">🗑️</div>
              <p class="text-base text-[var(--text)]">Thùng rác câu hỏi trống</p>
              <p class="text-xs font-normal mt-1">Không có câu hỏi nào bị xóa mềm trong thùng rác.</p>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="isLoading" class="text-center py-10 text-[var(--muted)] font-bold">
        <div class="mx-auto mb-2 h-8 w-8 animate-spin rounded-full border-4 border-rose-500 border-t-transparent"></div>
        Đang tải danh sách Thùng rác...
      </div>
    </div>

    <!-- Pagination Controls -->
    <div v-if="lastPage > 1" class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 shadow-[var(--shadow-card)] text-xs font-bold">
      <div class="text-[var(--muted)]">
        Trang <strong class="text-[var(--text)]">{{ currentPage }}</strong> / {{ lastPage }} (Tổng {{ trashCount }} câu)
      </div>

      <div class="flex items-center gap-1">
        <button class="btn-ghost !p-2 text-xs" :disabled="currentPage <= 1" @click="fetchTrash(1)">&laquo;</button>
        <button class="btn-ghost !p-2 text-xs" :disabled="currentPage <= 1" @click="fetchTrash(currentPage - 1)">&lt;</button>

        <button
          v-for="p in visiblePages"
          :key="p"
          class="h-8 w-8 rounded-xl text-xs font-black transition duration-150"
          :class="p === currentPage ? 'bg-rose-500 text-white shadow-md' : 'bg-[var(--surface-soft)] text-[var(--text)] hover:bg-rose-500/20'"
          @click="fetchTrash(p)"
        >
          {{ p }}
        </button>

        <button class="btn-ghost !p-2 text-xs" :disabled="currentPage >= lastPage" @click="fetchTrash(currentPage + 1)">&gt;</button>
        <button class="btn-ghost !p-2 text-xs" :disabled="currentPage >= lastPage" @click="fetchTrash(lastPage)">&raquo;</button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import { adminQuestionsApi } from '@/services/api'

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
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

const formatDate = (dateStr) => {
  if (!dateStr) return '--'
  const d = new Date(dateStr)
  return d.toLocaleDateString('vi-VN', {
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
      per_page: 15
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

onMounted(() => {
  fetchTrash()
})
</script>
