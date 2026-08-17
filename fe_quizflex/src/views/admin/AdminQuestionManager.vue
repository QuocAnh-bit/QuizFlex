<template>
  <section class="grid gap-6">
    <!-- Top Breadcrumb & Header Banner -->
    <div class="space-y-4">
      <div class="flex items-center gap-2 text-xs font-bold text-[var(--muted)]">
        <router-link to="/admin" class="hover:text-[var(--primary)] transition">Dashboard</router-link>
        <span>&rsaquo;</span>
        <span class="text-[var(--text)]">Question Bank</span>
      </div>

      <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
        <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-center">
          <div class="flex items-start gap-4">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-[var(--primary)] to-[var(--primary-2)] text-2xl text-white shadow-lg shadow-[var(--primary)]/30">
              🛡️
            </div>
            <div>
              <h1 class="text-3xl font-black tracking-[-0.05em] text-[var(--text)]">
                Quản lý Ngân hàng Câu hỏi
              </h1>
              <p class="mt-1 max-w-2xl text-xs sm:text-sm leading-relaxed text-[var(--muted)]">
                Kiểm duyệt nội dung, điều chỉnh quyền hiển thị (Public/Private hàng loạt), chỉnh sửa đáp án và xử lý vi phạm trên toàn bộ hệ thống QuizFlex.
              </p>
            </div>
          </div>

          <div class="flex shrink-0 items-center gap-3">
            <router-link
              to="/admin/questions-trash"
              class="rounded-full border border-rose-500/30 bg-rose-500/10 px-4 py-2.5 text-xs font-black text-rose-400 transition hover:bg-rose-500/20"
            >
              🗑️ Thùng rác câu hỏi
            </router-link>

            <router-link
              to="/admin/question-bank/create-question"
              class="btn-primary flex items-center gap-2 px-6 py-3 text-sm shadow-xl transition hover:scale-105"
            >
              <span class="text-lg">┼</span>
              <span>Tạo câu hỏi mới</span>
            </router-link>
          </div>
        </div>

        <!-- 4 KPI Stat Cards Row -->
        <div class="mt-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
          <!-- Card 1: Tổng câu hỏi -->
          <div class="group relative overflow-hidden rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 transition hover:border-[var(--border-strong)]">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-purple-500/20 text-purple-300 text-lg">
                  📰
                </div>
                <div>
                  <span class="text-xs font-bold text-[var(--muted)]">Tổng câu hỏi</span>
                  <p class="text-2xl font-black text-[var(--text)]">{{ stats.total || 0 }}</p>
                </div>
              </div>
              <span class="text-xs text-[var(--muted)] group-hover:translate-x-1 transition">&rsaquo;</span>
            </div>
            <p class="mt-2 text-[10px] font-bold text-[var(--muted)]">100% tổng hệ thống</p>
          </div>

          <!-- Card 2: Công khai -->
          <div class="group relative overflow-hidden rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 transition hover:border-emerald-500/40">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-500/20 text-emerald-400 text-lg">
                  🌐
                </div>
                <div>
                  <span class="text-xs font-bold text-[var(--muted)]">Công khai</span>
                  <p class="text-2xl font-black text-emerald-400">{{ stats.public || 0 }}</p>
                </div>
              </div>
              <span class="text-xs text-[var(--muted)] group-hover:translate-x-1 transition">&rsaquo;</span>
            </div>
            <p class="mt-2 text-[10px] font-bold text-emerald-400/80">{{ publicPercent }}% tổng số</p>
          </div>

          <!-- Card 3: Riêng tư / Ẩn -->
          <div class="group relative overflow-hidden rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 transition hover:border-amber-500/40">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-amber-500/20 text-amber-400 text-lg">
                  🔒
                </div>
                <div>
                  <span class="text-xs font-bold text-[var(--muted)]">Riêng tư / Ẩn</span>
                  <p class="text-2xl font-black text-amber-400">{{ stats.private || 0 }}</p>
                </div>
              </div>
              <span class="text-xs text-[var(--muted)] group-hover:translate-x-1 transition">&rsaquo;</span>
            </div>
            <p class="mt-2 text-[10px] font-bold text-amber-400/80">{{ privatePercent }}% tổng số</p>
          </div>

          <!-- Card 4: Ticket báo cáo -->
          <div class="group relative overflow-hidden rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 transition hover:border-rose-500/40">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-rose-500/20 text-rose-400 text-lg">
                  ⚠️
                </div>
                <div>
                  <span class="text-xs font-bold text-[var(--muted)]">Ticket Báo cáo</span>
                  <p class="text-2xl font-black text-rose-400">{{ stats.reported || 0 }}</p>
                </div>
              </div>
              <span class="text-xs text-[var(--muted)] group-hover:translate-x-1 transition">&rsaquo;</span>
            </div>
            <p class="mt-2 text-[10px] font-bold text-rose-400/80">Cần xử lý</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Search + 2-Tier Filter Section -->
    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <!-- Tier 1 Filter Row -->
      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <!-- Search Input -->
        <div class="flex items-center gap-3 rounded-2xl border border-[var(--border)] bg-[var(--input-bg)] px-4 py-2.5 transition focus-within:border-[var(--border-strong)]">
          <span class="text-base text-[var(--muted)]">🔍</span>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Tìm theo nội dung câu hỏi, tác giả, ID..."
            class="w-full bg-transparent text-xs font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]"
            @keyup.enter="fetchQuestions(1)"
          />
        </div>

        <!-- Môn học Select -->
        <select v-model="filters.subject_id" class="field text-xs font-semibold" @change="fetchQuestions(1)">
          <option value="">📖 Tất cả môn học</option>
          <option v-for="subj in subjects" :key="subj.id" :value="subj.id">{{ subj.name }}</option>
        </select>

        <!-- Khối lớp Select -->
        <select v-model="filters.grade_id" class="field text-xs font-semibold" @change="fetchQuestions(1)">
          <option value="">🎓 Tất cả khối lớp</option>
          <option v-for="gr in grades" :key="gr.id" :value="gr.id">{{ gr.name }}</option>
        </select>

        <!-- Toggle Advanced Filter Button -->
        <button
          type="button"
          class="btn-ghost flex items-center justify-center gap-2 text-xs font-bold"
          @click="showAdvancedFilters = !showAdvancedFilters"
        >
          <span>🌪️ Bộ lọc nâng cao</span>
          <span>{{ showAdvancedFilters ? '▲' : '▼' }}</span>
        </button>
      </div>

      <!-- Tier 2 Filter Row (Collapsible) -->
      <transition enter-active-class="transition duration-300 ease-out" enter-from-class="-translate-y-2 opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="translate-y-0 opacity-100" leave-to-class="-translate-y-2 opacity-0">
        <div v-if="showAdvancedFilters" class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-5 pt-3 border-t border-[var(--border)]">
          <!-- Độ khó -->
          <select v-model="filters.difficulty" class="field text-xs font-semibold" @change="fetchQuestions(1)">
            <option value="">📊 Tất cả độ khó</option>
            <option value="easy">Dễ (Nhận biết)</option>
            <option value="medium">Vừa (Thông hiểu)</option>
            <option value="hard">Khó (Vận dụng)</option>
          </select>

          <!-- Trạng thái -->
          <select v-model="filters.visibility" class="field text-xs font-semibold" @change="fetchQuestions(1)">
            <option value="all">🚩 Tất cả trạng thái</option>
            <option value="public">🌐 Public (Công khai)</option>
            <option value="private">🔒 Private (Gỡ/Ẩn)</option>
          </select>

          <!-- Dạng câu -->
          <select v-model="filters.type" class="field text-xs font-semibold" @change="fetchQuestions(1)">
            <option value="">📝 Tất cả dạng câu</option>
            <option value="single_choice">Một đáp án đúng</option>
            <option value="multi_choice">Nhiều đáp án đúng</option>
            <option value="true_false">Đúng / Sai</option>
          </select>

          <!-- Reset Filter Button -->
          <button type="button" class="btn-ghost text-xs font-bold text-rose-400 hover:text-rose-300" @click="resetFilters">
            🔄 Đặt lại bộ lọc
          </button>

          <!-- Search Submit Button -->
          <button type="button" class="btn-primary justify-center text-xs font-bold" @click="fetchQuestions(1)">
            🔍 Tìm kiếm & Lọc
          </button>
        </div>
      </transition>
    </article>

    <!-- Bulk Operations Toolbar (Hiển thị khi tick chọn câu hỏi) -->
    <transition enter-active-class="transition duration-300 ease-out" enter-from-class="-translate-y-2 opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="translate-y-0 opacity-100" leave-to-class="-translate-y-2 opacity-0">
      <div v-if="selectedIds.length > 0" class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[var(--border-strong)] bg-[var(--chip-active)] p-4 shadow-lg backdrop-blur-xl">
        <div class="flex items-center gap-3">
          <span class="grid h-8 w-8 place-items-center rounded-xl bg-[var(--primary)] text-white text-xs font-black">
            {{ selectedIds.length }}
          </span>
          <span class="text-sm font-black text-[var(--text)]">Đã chọn {{ selectedIds.length }} câu hỏi</span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-4 py-2 text-xs font-black text-emerald-400 transition hover:bg-emerald-500/20"
            @click="handleBulkVisibility(true)"
          >
            ✓ Duyệt công khai
          </button>

          <button
            type="button"
            class="rounded-full border border-amber-500/30 bg-amber-500/10 px-4 py-2 text-xs font-black text-amber-300 transition hover:bg-amber-500/20"
            @click="handleBulkVisibility(false)"
          >
            🔒 Chuyển riêng tư
          </button>

          <button
            type="button"
            class="rounded-full border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-xs font-black text-rose-400 transition hover:bg-rose-500/20"
            @click="handleBulkDelete"
          >
            🗑️ Xóa vào thùng rác
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
            <th class="p-4 min-w-[240px]">NỘI DUNG CÂU HỎI</th>
            <th class="p-4 text-center">MÔN HỌC</th>
            <th class="p-4 text-center">KHỐI LỚP</th>
            <th class="p-4 text-center">ĐỘ KHÓ</th>
            <th class="p-4 text-center">TRẠNG THÁI</th>
            <th class="p-4 text-center">NGƯỜI TẠO</th>
            <th class="p-4 text-center">THỜI GIAN</th>
            <th class="p-4 text-center min-w-[120px]">THAO TÁC</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[var(--border)]">
          <tr
            v-for="q in questions"
            :key="q.id"
            class="transition hover:bg-[var(--surface-soft)]/60 cursor-pointer"
            :class="{ 'bg-[var(--chip-active)]/30': selectedIds.includes(q.id) }"
            @click="goToDetail(q.id)"
          >
            <!-- Checkbox -->
            <td class="p-4 text-center" @click.stop>
              <input
                type="checkbox"
                class="h-4 w-4 rounded border-[var(--border)] accent-[var(--primary)] cursor-pointer"
                :value="q.id"
                v-model="selectedIds"
              />
            </td>

            <!-- ID -->
            <td class="p-4 text-center font-black text-[var(--primary)]">
              #{{ q.id }}
            </td>

            <!-- NỘI DUNG CÂU HỎI -->
            <td class="p-4 max-w-md">
              <div class="font-bold text-[var(--text)] line-clamp-2 leading-relaxed" :title="q.content">
                {{ q.content }}
              </div>
              <div v-if="q.quiz_title" class="mt-1 text-[11px] text-[var(--muted)] truncate" :title="q.quiz_title">
                📖 {{ q.quiz_title }}
              </div>
            </td>

            <!-- MÔN HỌC -->
            <td class="p-4 text-center">
              <span v-if="q.subject_name" class="inline-flex items-center gap-1 rounded-full bg-[var(--surface-soft)] border border-[var(--border)] px-2.5 py-1 text-xs font-bold text-[var(--text)]">
                <span>📖</span>
                <span>{{ q.subject_name }}</span>
              </span>
              <span v-else class="text-xs text-[var(--muted)]">--</span>
            </td>

            <!-- KHỐI LỚP -->
            <td class="p-4 text-center">
              <span v-if="q.grade_name" class="inline-flex items-center gap-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 px-2.5 py-1 text-xs font-bold text-indigo-300">
                <span>🎓</span>
                <span>{{ q.grade_name }}</span>
              </span>
              <span v-else class="text-xs text-[var(--muted)]">--</span>
            </td>

            <!-- ĐỘ KHÓ -->
            <td class="p-4 text-center">
              <span
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold border"
                :class="q.difficulty === 'easy' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : q.difficulty === 'hard' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20'"
              >
                <span>📊</span>
                <span>{{ q.difficulty === 'easy' ? 'Dễ' : q.difficulty === 'hard' ? 'Khó' : 'Trung bình' }}</span>
              </span>
            </td>

            <!-- TRẠNG THÁI -->
            <td class="p-4 text-center">
              <span
                class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-black"
                :class="q.is_public ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400' : 'border-amber-500/30 bg-amber-500/10 text-amber-400'"
              >
                <span>{{ q.is_public ? '🌐' : '🔒' }}</span>
                <span>{{ q.is_public ? 'Công khai' : 'Riêng tư' }}</span>
              </span>
            </td>

            <!-- NGƯỜI TẠO -->
            <td class="p-4 text-center text-xs font-bold text-[var(--muted)]">
              <div class="flex items-center justify-center gap-1.5">
                <span>👤</span>
                <span class="truncate max-w-[110px]" :title="q.author_name">{{ q.author_name || 'Vô danh' }}</span>
              </div>
            </td>

            <!-- THỜI GIAN -->
            <td class="p-4 text-center text-xs font-semibold text-[var(--muted)]">
              {{ formatDate(q.created_at) }}
            </td>

            <!-- THAO TÁC -->
            <td class="p-4 text-center whitespace-nowrap space-x-1.5" @click.stop>
              <!-- View Detail Page Navigation -->
              <router-link
                :to="`/admin/questions/${q.id}`"
                class="inline-grid h-8 w-8 place-items-center rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] text-xs text-[var(--text)] transition hover:bg-[var(--primary)] hover:text-white"
                title="Xem chi tiết trang riêng"
              >
                👁️
              </router-link>

              <!-- Edit Page Navigation -->
              <router-link
                :to="`/admin/questions/${q.id}/edit`"
                class="inline-grid h-8 w-8 place-items-center rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] text-xs text-amber-400 transition hover:bg-amber-500 hover:text-slate-950"
                title="Chỉnh sửa trang riêng"
              >
                ✏️
              </router-link>

              <!-- Delete -->
              <button
                type="button"
                class="inline-grid h-8 w-8 place-items-center rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] text-xs text-rose-400 transition hover:bg-rose-500 hover:text-white"
                title="Xóa vào thùng rác"
                @click="deleteSingleQuestion(q.id)"
              >
                🗑️
              </button>
            </td>
          </tr>

          <!-- Empty State -->
          <tr v-if="!isLoading && questions.length === 0">
            <td colspan="10" class="text-center py-12 text-[var(--muted)] font-bold">
              <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-[var(--surface-soft)] text-2xl">🔎</div>
              <p class="text-base text-[var(--text)]">Không tìm thấy câu hỏi nào</p>
              <p class="text-xs font-normal mt-1">Thử thay đổi từ khóa hoặc điều chỉnh bộ lọc.</p>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Table Loading State -->
      <div v-if="isLoading" class="text-center py-10 text-[var(--muted)] font-bold">
        <div class="mx-auto mb-2 h-8 w-8 animate-spin rounded-full border-4 border-[var(--primary)] border-t-transparent"></div>
        Đang tải dữ liệu ngân hàng câu hỏi...
      </div>
    </div>

    <!-- Pagination Controls -->
    <div v-if="lastPage > 1" class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 shadow-[var(--shadow-card)] text-xs font-bold">
      <div class="flex items-center gap-2 text-[var(--muted)]">
        <span>Hiển thị</span>
        <select v-model="perPage" class="field !py-1 !px-3 text-xs font-bold" @change="fetchQuestions(1)">
          <option :value="15">15</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
        </select>
        <span>câu hỏi trên trang</span>
      </div>

      <div class="flex items-center gap-1">
        <button class="btn-ghost !p-2 text-xs" :disabled="currentPage <= 1" @click="fetchQuestions(1)" title="Trang đầu">&laquo;</button>
        <button class="btn-ghost !p-2 text-xs" :disabled="currentPage <= 1" @click="fetchQuestions(currentPage - 1)" title="Trang trước">&lt;</button>

        <button
          v-for="p in visiblePages"
          :key="p"
          class="h-8 w-8 rounded-xl text-xs font-black transition duration-150"
          :class="p === currentPage ? 'bg-[var(--primary)] text-white shadow-md' : 'bg-[var(--surface-soft)] text-[var(--text)] hover:bg-[var(--primary)]/20'"
          @click="fetchQuestions(p)"
        >
          {{ p }}
        </button>

        <button class="btn-ghost !p-2 text-xs" :disabled="currentPage >= lastPage" @click="fetchQuestions(currentPage + 1)" title="Trang sau">&gt;</button>
        <button class="btn-ghost !p-2 text-xs" :disabled="currentPage >= lastPage" @click="fetchQuestions(lastPage)" title="Trang cuối">&raquo;</button>
      </div>

      <div class="text-[var(--muted)]">
        Trang <strong class="text-[var(--text)]">{{ currentPage }}</strong> / {{ lastPage }}
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject } from 'vue'
import { useRouter } from 'vue-router'
import { adminQuestionsApi, taxonomyApi } from '@/services/api'

const router = useRouter()
const showToast = inject('showToast')
const showConfirm = inject('showConfirm')

const questions = ref([])
const stats = reactive({ total: 0, public: 0, private: 0, reported: 0 })
const isLoading = ref(false)
const errorMessage = ref('')
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
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

const goToDetail = (id) => {
  router.push(`/admin/questions/${id}`)
}

const fetchTaxonomies = async () => {
  try {
    const tree = await taxonomyApi.tree()
    if (tree) {
      if (Array.isArray(tree.subjects) && tree.subjects.length > 0) {
        subjects.value = tree.subjects
      }
      if (Array.isArray(tree.education_levels)) {
        const grList = []
        tree.education_levels.forEach(el => {
          if (Array.isArray(el.grades)) {
            el.grades.forEach(g => {
              if (!grList.some(existing => existing.id === g.id)) {
                grList.push(g)
              }
            })
          }
        })
        grades.value = grList
      }
    }
  } catch (e) {
    console.error('Lỗi khi tải danh mục môn/lớp:', e)
  }
}

const fetchQuestions = async (page = 1) => {
  isLoading.value = true
  errorMessage.value = ''
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
    total.value = res.total
    lastPage.value = res.lastPage
    
    if (res.stats) {
      stats.total = res.stats.total
      stats.public = res.stats.public
      stats.private = res.stats.private
      stats.reported = res.stats.reported
    }
  } catch (err) {
    errorMessage.value = `Không tải được danh sách câu hỏi: ${err.message}`
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
  if (selectedIds.value.length === 0) return
  const actionText = isPublic ? 'Duyệt công khai' : 'Chuyển riêng tư'
  const message = `Bạn có chắc muốn ${actionText} cho ${selectedIds.value.length} câu hỏi đã chọn?`

  const executeAction = async () => {
    try {
      await adminQuestionsApi.bulkToggleVisibility(selectedIds.value, isPublic)
      if (showToast) showToast(`Đã ${actionText} hàng loạt thành công.`, 'success')
      selectedIds.value = []
      fetchQuestions(currentPage.value)
    } catch (err) {
      if (showToast) showToast(`Lỗi: ${err.message}`, 'error')
    }
  }

  if (showConfirm) {
    showConfirm(`${actionText} hàng loạt`, message, executeAction)
  } else if (confirm(message)) {
    executeAction()
  }
}

const deleteSingleQuestion = async (id) => {
  const message = 'Bạn có chắc muốn chuyển câu hỏi này vào thùng rác?'
  const executeAction = async () => {
    try {
      await adminQuestionsApi.remove(id)
      if (showToast) showToast('Đã chuyển câu hỏi vào thùng rác thành công.', 'success')
      fetchQuestions(currentPage.value)
    } catch (err) {
      if (showToast) showToast(`Xóa thất bại: ${err.message}`, 'error')
    }
  }

  if (showConfirm) {
    showConfirm('Xóa câu hỏi', message, executeAction)
  } else if (confirm(message)) {
    executeAction()
  }
}

const handleBulkDelete = async () => {
  if (selectedIds.value.length === 0) return
  const message = `Bạn có chắc muốn chuyển ${selectedIds.value.length} câu hỏi đã chọn vào thùng rác?`

  const executeAction = async () => {
    try {
      await adminQuestionsApi.bulkDelete(selectedIds.value)
      if (showToast) showToast('Xóa hàng loạt thành công.', 'success')
      selectedIds.value = []
      fetchQuestions(currentPage.value)
    } catch (err) {
      if (showToast) showToast(`Lỗi: ${err.message}`, 'error')
    }
  }

  if (showConfirm) {
    showConfirm('Xóa hàng loạt', message, executeAction)
  } else if (confirm(message)) {
    executeAction()
  }
}

onMounted(() => {
  fetchTaxonomies()
  fetchQuestions()
})
</script>
