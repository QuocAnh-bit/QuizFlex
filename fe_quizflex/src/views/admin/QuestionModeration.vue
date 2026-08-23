<template>
  <section class="grid gap-6">
    <div class="glass-card flex flex-col justify-between rounded-2xl p-4 transition-all duration-200 hover:-translate-y-0.5">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-purple-500/10 text-purple-500">
            <Shield class="h-6 w-6" />
          </div>
          <div>
<h1 class="text-3xl font-black tracking-[-0.04em] text-[var(--text)]">Kiểm duyệt Ngân hàng Câu hỏi

</h1>
<p class="mt-1 text-sm font-medium text-[var(--muted)]">
           Kiểm duyệt tất cả câu hỏi trên hệ thống
          </p></div>
        </div>
      </div>
    <!-- 1. SUMMARY CARDS (4 CARDS COMPACT) -->
     
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
      <!-- Card 1: Câu hỏi chờ duyệt -->
      <div class="glass-card flex flex-col justify-between rounded-2xl p-4 transition-all duration-200 hover:-translate-y-0.5">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-purple-500/10 text-purple-500">
            <Clock class="h-5 w-5" />
          </div>
          <div>
            <p class="text-xs font-semibold text-[var(--muted)]">Câu hỏi chờ duyệt</p>
            <p class="mt-0.5 text-2xl font-black text-[var(--text)]">{{ pendingOnlyCount }}</p>
          </div>
        </div>
        <p class="mt-3 text-[11px] font-medium text-[var(--muted)]">Đóng góp công khai mới</p>
      </div>

      <!-- Card 2: Tác giả đóng góp -->
      <div class="glass-card flex flex-col justify-between rounded-2xl p-4 transition-all duration-200 hover:-translate-y-0.5">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-500">
            <Users class="h-5 w-5" />
          </div>
          <div>
            <p class="text-xs font-semibold text-[var(--muted)]">Tác giả đóng góp</p>
            <p class="mt-0.5 text-2xl font-black text-[var(--text)]">{{ uniqueAuthorsCount }}</p>
          </div>
        </div>
        <p class="mt-3 text-[11px] font-medium text-[var(--muted)]">Thành viên nộp bài</p>
      </div>

      <!-- Card 3: Môn học có câu chờ -->
      <div class="glass-card flex flex-col justify-between rounded-2xl p-4 transition-all duration-200 hover:-translate-y-0.5">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-500">
            <BookOpen class="h-5 w-5" />
          </div>
          <div>
            <p class="text-xs font-semibold text-[var(--muted)]">Môn học có câu chờ</p>
            <p class="mt-0.5 text-2xl font-black text-[var(--text)]">{{ uniqueSubjectsCount }}</p>
          </div>
        </div>
        <p class="mt-3 text-[11px] font-medium text-[var(--muted)]">Các bộ môn trong danh sách</p>
      </div>

      <!-- Card 4: Đã đính chính / nộp lại -->
      <div class="glass-card flex flex-col justify-between rounded-2xl p-4 transition-all duration-200 hover:-translate-y-0.5">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-500">
            <RefreshCw class="h-5 w-5" />
          </div>
          <div>
            <p class="text-xs font-semibold text-[var(--muted)]">Đã đính chính</p>
            <p class="mt-0.5 text-2xl font-black text-[var(--text)]">{{ resubmittedCount }}</p>
          </div>
        </div>
        <p class="mt-3 text-[11px] font-medium text-[var(--muted)]">Nộp lại sau khi sửa</p>
      </div>
    </div>

    <!-- 2. WORKSPACE CONTAINER DUY NHẤT -->
    <div class="glass-card rounded-[2rem] p-5 space-y-4">
      <!-- TOP BAR: TABS (BÊN TRÁI) + FILTERS & SEARCH (BÊN PHẢI) -->
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between border-b border-[var(--border)] pb-4">
        <!-- Left Side: 2 Tabs phân tách tuyệt đối -->
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-extrabold transition-all cursor-pointer"
            :class="viewMode === 'pending'
              ? 'bg-[var(--chip-active)] text-[var(--primary)] border border-[var(--border-strong)]'
              : 'text-[var(--muted)] hover:text-[var(--text)] border border-transparent'"
            @click="viewMode = 'pending'"
          >
            <Clock :size="15" />
            <span>Tất cả câu chờ</span>
            <span class="rounded-full bg-purple-500/10 px-2 py-0.5 text-xs font-bold text-purple-500 border border-purple-500/20">
              {{ pendingOnlyCount }}
            </span>
          </button>

          <button
            type="button"
            class="flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-extrabold transition-all cursor-pointer"
            :class="viewMode === 'resubmitted'
              ? 'bg-[var(--chip-active)] text-[var(--primary)] border border-[var(--border-strong)]'
              : 'text-[var(--muted)] hover:text-[var(--text)] border border-transparent'"
            @click="viewMode = 'resubmitted'"
          >
            <CheckCircle2 :size="15" />
            <span>Đã đính chính nộp lại</span>
            <span class="rounded-full bg-amber-500/10 px-2 py-0.5 text-xs font-bold text-amber-500 border border-amber-500/20">
              {{ resubmittedCount }}
            </span>
          </button>
        </div>

        <!-- Right Side: Select Môn học + Lớp + Độ khó + Search Input + Button Làm mới -->
        <div class="flex flex-wrap items-center gap-2.5">
          <!-- Select Môn học -->
          <div v-if="availableSubjects.length > 0" class="relative min-w-[130px]">
            <select
              v-model="selectedSubjectFilter"
              class="field w-full appearance-none pr-8 text-xs font-semibold"
            >
              <option value="">Tất cả môn học</option>
              <option v-for="subj in availableSubjects" :key="subj" :value="subj">{{ subj }}</option>
            </select>
            <div class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--muted)]">
              <ChevronDown :size="14" />
            </div>
          </div>

          <!-- Select Khối lớp -->
          <div v-if="availableGrades.length > 0" class="relative min-w-[120px]">
            <select
              v-model="selectedGradeFilter"
              class="field w-full appearance-none pr-8 text-xs font-semibold"
            >
              <option value="">Tất cả khối lớp</option>
              <option v-for="grade in availableGrades" :key="grade" :value="grade">{{ grade }}</option>
            </select>
            <div class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--muted)]">
              <ChevronDown :size="14" />
            </div>
          </div>

          <!-- Select Độ khó -->
          <div v-if="availableDifficulties.length > 0" class="relative min-w-[110px]">
            <select
              v-model="selectedDifficultyFilter"
              class="field w-full appearance-none pr-8 text-xs font-semibold"
            >
              <option value="">Tất cả độ khó</option>
              <option v-for="diff in availableDifficulties" :key="diff" :value="diff">
                {{ difficultyLabels[diff] || diff }}
              </option>
            </select>
            <div class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--muted)]">
              <ChevronDown :size="14" />
            </div>
          </div>

          <!-- Search Input -->
          <div class="relative w-full sm:w-52">
            <input
              v-model.trim="searchQuery"
              class="field w-full pl-8 pr-3 text-xs font-medium"
              type="search"
              placeholder="Tìm câu hỏi, ID, tác giả..."
            />
            <div class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-[var(--muted)]">
              <Search :size="14" />
            </div>
          </div>

          <!-- Refresh Button -->
          <button
            type="button"
            class="btn-ghost flex items-center gap-1.5 px-3 py-2 text-xs font-bold shrink-0 cursor-pointer"
            :disabled="isLoadingPending"
            @click="fetchPendingQuestions"
          >
            <RefreshCw :size="14" :class="{ 'animate-spin': isLoadingPending }" />
            <span>Làm mới</span>
          </button>
        </div>
      </div>

      <!-- BULK SELECTION CONTROL BAR -->
      <div v-if="filteredPendingQuestions.length > 0" class="flex items-center justify-between px-2 py-1 text-xs font-semibold border-b border-[var(--border)]/50 pb-3 text-[var(--muted)]">
        <label class="flex items-center gap-2 cursor-pointer select-none hover:text-[var(--text)] transition">
          <input
            type="checkbox"
            class="h-4 w-4 rounded border-[var(--border)] text-purple-600 focus:ring-purple-500 cursor-pointer"
            :checked="isAllSelected"
            @change="toggleSelectAll"
          />
          <span>Chọn tất cả trong danh sách ({{ filteredPendingQuestions.length }})</span>
        </label>

        <span v-if="selectedQuestionIds.length > 0" class="text-purple-500 font-extrabold flex items-center gap-1">
          ✓ Đã chọn {{ selectedQuestionIds.length }} câu hỏi
        </span>
      </div>

      <!-- QUESTION LIST CONTAINER -->
      <div v-if="isLoadingPending" class="py-12 text-center border border-[var(--border)] bg-[var(--surface-soft)] rounded-2xl space-y-2">
        <div class="mx-auto h-6 w-6 animate-spin rounded-full border-2 border-[var(--primary)] border-t-transparent"></div>
        <p class="text-xs font-medium text-[var(--muted)]">Đang tải câu hỏi chờ duyệt...</p>
      </div>

      <div v-else-if="filteredPendingQuestions.length === 0" class="py-12 text-center border border-dashed border-[var(--border)] bg-[var(--surface-soft)] rounded-2xl space-y-2">
        <div class="mx-auto h-10 w-10 grid place-items-center rounded-xl bg-emerald-500/10 text-emerald-500 text-base font-bold">
          ✓
        </div>
        <h4 class="text-sm font-bold text-[var(--text)]">Không có câu hỏi nào trong danh sách</h4>
        <p class="text-xs text-[var(--muted)]">Tất cả câu hỏi đã được xem xét và xử lý!</p>
      </div>

      <div v-else class="grid gap-3.5">
        <article
          v-for="q in filteredPendingQuestions"
          :key="q.id"
          class="rounded-2xl border transition space-y-3 p-4"
          :class="[
            selectedQuestionIds.includes(q.id)
              ? 'border-purple-500/50 bg-purple-500/5 dark:bg-purple-900/10'
              : 'border-[var(--border)] bg-[var(--surface-soft)] hover:bg-[var(--surface)]'
          ]"
        >
          <!-- Metadata Bar Header -->
          <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-2.5 flex-wrap font-bold">
              <!-- Checkbox cho từng câu -->
              <input
                type="checkbox"
                class="h-4 w-4 rounded border-[var(--border)] text-purple-600 focus:ring-purple-500 cursor-pointer"
                :checked="selectedQuestionIds.includes(q.id)"
                @change="toggleSelectQuestion(q.id)"
              />

              <span class="rounded-lg bg-[var(--surface)] border border-[var(--border)] px-2.5 py-0.5 text-[var(--text)]">#{{ q.id }}</span>
              
              <span
                v-if="q.has_author_updated || q.is_resubmitted"
                class="inline-flex items-center gap-1 rounded-full bg-amber-500/10 border border-amber-500/20 px-2.5 py-0.5 text-xs font-extrabold text-amber-500"
              >
                <CheckCircle2 :size="12" />
                Đã đính chính nộp lại
              </span>
              <span
                v-else
                class="inline-flex items-center gap-1 rounded-full bg-purple-500/10 border border-purple-500/20 px-2.5 py-0.5 text-xs font-extrabold text-purple-500"
              >
                <Clock :size="12" />
                Chờ duyệt
              </span>

              <span v-if="q.subject_name" class="rounded-full bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-0.5 text-xs font-extrabold text-emerald-500">
                {{ q.subject_name }}
              </span>

              <span v-if="q.grade_name" class="rounded-full bg-blue-500/10 border border-blue-500/20 px-2.5 py-0.5 text-xs font-extrabold text-blue-400">
                {{ q.grade_name }}
              </span>

              <span v-if="q.difficulty" class="rounded-full bg-[var(--surface)] border border-[var(--border)] px-2.5 py-0.5 text-xs font-medium text-[var(--muted)]">
                {{ difficultyLabels[q.difficulty] || q.difficulty }}
              </span>

              <span v-if="q.education_level_name" class="rounded-full bg-[var(--surface)] border border-[var(--border)] px-2.5 py-0.5 text-xs font-medium text-[var(--muted)]">
                {{ q.education_level_name }}
              </span>
            </div>

            <div class="text-[11px] font-medium text-[var(--muted)]">
              Tác giả: <b class="text-[var(--text)] font-semibold">{{ q.user_name || q.user?.name || 'Vô danh' }}</b> • {{ formatTime(q.created_at) }}
            </div>
          </div>

          <!-- AUDIT ALERT BOX: Hiển thị lý do từ chối/báo cáo trước đó -->
          <div
            v-if="q.report_reason || q.has_author_updated"
            class="rounded-xl bg-amber-500/10 border border-amber-500/20 p-3 text-xs text-amber-600 dark:text-amber-400 flex items-start gap-2.5"
          >
            <AlertTriangle :size="16" class="shrink-0 mt-0.5 text-amber-500" />
            <div class="space-y-0.5">
              <p class="font-extrabold text-amber-600 dark:text-amber-400">Lưu ý đối chiếu (Đã đính chính / Báo cáo):</p>
              <p v-if="q.report_reason" class="font-medium opacity-90">
                Lần trả về / báo cáo trước: <span class="font-bold underline">{{ q.report_reason }}</span>
              </p>
              <p v-else class="font-medium opacity-90">
                Tác giả đã đính chính và cập nhật lại nội dung câu hỏi sau khi có yêu cầu chỉnh sửa.
              </p>
            </div>
          </div>

          <!-- Question Content Text -->
          <h4 class="text-sm font-bold text-[var(--text)] leading-snug break-words">
            {{ q.content || q.text }}
          </h4>

          <!-- Answers 2-Column Grid -->
          <div v-if="q.answers && q.answers.length" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
            <div
              v-for="ans in q.answers"
              :key="ans.id || ans.key"
              class="flex items-center justify-between rounded-xl border p-2.5 font-medium transition"
              :class="ans.is_correct
                ? 'border-emerald-500/30 bg-emerald-500/10 text-[var(--text)] font-semibold'
                : 'border-[var(--border)] bg-[var(--surface)] text-[var(--text)]'"
            >
              <div class="flex items-center gap-2.5 truncate min-w-0 pr-2">
                <span class="font-bold text-[var(--text)] shrink-0">{{ ans.key }}.</span>
                <span class="truncate min-w-0">{{ ans.content || ans.text }}</span>
              </div>

              <span v-if="ans.is_correct" class="text-emerald-500 font-bold text-[11px] shrink-0 flex items-center gap-0.5">
                ✓ Đáp án đúng
              </span>
            </div>
          </div>

          <!-- Actions Footer Row -->
          <div class="flex items-center justify-end gap-2 pt-2 border-t border-[var(--border)]">
            <button
              type="button"
              class="btn-ghost flex h-9 items-center gap-1.5 rounded-xl border border-rose-500/20 px-3 text-xs font-bold text-rose-500 hover:bg-rose-500/10 transition cursor-pointer"
              @click="openRejectModal(q)"
            >
              <X :size="14" />
              <span>Từ chối</span>
            </button>

            <button
              type="button"
              class="btn-primary flex h-9 items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 text-xs font-bold text-white transition cursor-pointer shadow-xs"
              @click="moderateQuestion(q.id, 'approve')"
            >
              <Check :size="14" />
              <span>Duyệt công khai</span>
            </button>
          </div>
        </article>
      </div>
    </div>

    <!-- FLOATING BULK ACTION BAR (KHI CÓ CÂU ĐƯỢC CHỌN) -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="selectedQuestionIds.length > 0"
          class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 flex items-center gap-3 rounded-2xl border border-[var(--border-strong)] bg-[var(--surface)]/95 p-3 px-5 shadow-2xl backdrop-blur-md"
        >
          <div class="flex items-center gap-2 pr-3 border-r border-[var(--border)] text-xs font-bold text-[var(--text)]">
            <span class="grid h-6 w-6 place-items-center rounded-full bg-purple-500 text-xs text-white font-extrabold">
              {{ selectedQuestionIds.length }}
            </span>
            <span>câu đã chọn</span>
          </div>

          <button
            type="button"
            class="btn-primary flex h-9 items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 text-xs font-bold text-white transition cursor-pointer shadow-xs"
            @click="bulkModerate('approve')"
          >
            <Check :size="14" />
            <span>Duyệt hàng loạt ({{ selectedQuestionIds.length }})</span>
          </button>

          <button
            type="button"
            class="btn-ghost flex h-9 items-center gap-1.5 rounded-xl border border-rose-500/20 px-3.5 text-xs font-bold text-rose-500 hover:bg-rose-500/10 cursor-pointer"
            @click="openRejectModal(null)"
          >
            <X :size="14" />
            <span>Từ chối hàng loạt</span>
          </button>

          <button
            type="button"
            class="text-xs font-semibold text-[var(--muted)] hover:text-[var(--text)] pl-2 cursor-pointer"
            @click="selectedQuestionIds = []"
          >
            Bỏ chọn
          </button>
        </div>
      </Transition>
    </Teleport>

    <!-- REJECTION REASON MODAL (HỖ TRỢ TỪ CHỐI ĐƠN LẺ & HÀNG LOẠT + CHIP LÝ DO MẪU) -->
    <Teleport to="body">
      <div v-if="rejectingQuestion" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-6 shadow-xl space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-[var(--text)] flex items-center gap-2">
              <XCircle class="text-rose-500" :size="18" />
              <span v-if="isBulkReject">Từ chối hàng loạt {{ selectedQuestionIds.length }} câu hỏi</span>
              <span v-else>Từ chối duyệt câu hỏi #{{ rejectingQuestion.id }}</span>
            </h3>
            <button type="button" class="text-[var(--muted)] hover:text-[var(--text)]" @click="closeRejectModal">
              <X :size="18" />
            </button>
          </div>

          <p class="text-xs text-[var(--muted)]">
            Vui lòng nhập lý do từ chối để thông báo cho tác giả chỉnh sửa lại:
          </p>

          <!-- PRESET QUICK REASON TAGS CHIPS -->
          <div class="space-y-1.5">
            <p class="text-[11px] font-bold text-[var(--muted)] flex items-center gap-1">
              <Tag :size="12" /> Select lý do mẫu nhanh:
            </p>
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="tag in quickReasons"
                :key="tag"
                type="button"
                class="rounded-lg bg-[var(--surface-soft)] hover:bg-[var(--chip-active)] border border-[var(--border)] px-2.5 py-1 text-[11px] font-semibold text-[var(--text)] transition cursor-pointer"
                @click="selectQuickReason(tag)"
              >
                + {{ tag }}
              </button>
            </div>
          </div>

          <textarea
            v-model="rejectionReason"
            rows="3"
            placeholder="Nhập lý do từ chối (VD: Sai kiến thức chuyên môn, sai đáp án đúng...)"
            class="field w-full p-3 text-xs text-[var(--text)] placeholder-slate-400 focus:outline-none"
          ></textarea>

          <div class="flex items-center justify-end gap-2 pt-2">
            <button
              type="button"
              class="btn-ghost rounded-xl px-4 py-2 text-xs font-semibold text-[var(--muted)] cursor-pointer"
              @click="closeRejectModal"
            >
              Hủy
            </button>
            <button
              type="button"
              class="btn-primary rounded-xl bg-rose-600 hover:bg-rose-700 px-4 py-2 text-xs font-bold text-white shadow-xs cursor-pointer"
              @click="confirmReject"
            >
              Xác nhận từ chối
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import {
  RefreshCw,
  Clock,
  Shield,
  Check,
  CheckCircle2,
  X,
  XCircle,
  Search,
  Users,
  BookOpen,
  ChevronDown,
  AlertTriangle,
  Tag,
  Plus
} from 'lucide-vue-next'
import { adminQuestionsApi } from '@/services/api'

const showToast = inject('showToast')

const pendingQuestions = ref([])
const isLoadingPending = ref(false)
const searchQuery = ref('')
const selectedSubjectFilter = ref('')
const selectedGradeFilter = ref('')
const selectedDifficultyFilter = ref('')
const viewMode = ref('pending') // 'pending' (Tất cả câu chờ mới nộp) | 'resubmitted' (Đã đính chính nộp lại)

const selectedQuestionIds = ref([])
const rejectingQuestion = ref(null)
const rejectionReason = ref('')
const isBulkReject = ref(false)

const quickReasons = [
  'Sai đáp án đúng',
  'Lỗi chính tả / Định dạng',
  'Nội dung thiếu rõ ràng',
  'Trùng lặp câu hỏi',
  'Vi phạm quy chuẩn nội dung'
]

const difficultyLabels = {
  easy: 'Dễ',
  medium: 'Vừa',
  hard: 'Khó'
}

const uniqueAuthorsCount = computed(() => {
  const set = new Set(
    pendingQuestions.value
      .map(q => q.user_name || q.user?.name || q.user_id)
      .filter(Boolean)
  )
  return set.size
})

const uniqueSubjectsCount = computed(() => {
  const set = new Set(
    pendingQuestions.value
      .map(q => q.subject_name || q.subject_id)
      .filter(Boolean)
  )
  return set.size
})

const pendingOnlyCount = computed(() => {
  return pendingQuestions.value.filter(q => !q.has_author_updated && !q.is_resubmitted && q.status !== 'resubmitted').length
})

const resubmittedCount = computed(() => {
  return pendingQuestions.value.filter(q => q.has_author_updated || q.is_resubmitted || q.status === 'resubmitted').length
})

const availableSubjects = computed(() => {
  const subjects = pendingQuestions.value
    .map(q => q.subject_name)
    .filter(Boolean)
  return [...new Set(subjects)]
})

const availableGrades = computed(() => {
  const grades = pendingQuestions.value
    .map(q => q.grade_name)
    .filter(Boolean)
  return [...new Set(grades)]
})

const availableDifficulties = computed(() => {
  const diffs = pendingQuestions.value
    .map(q => q.difficulty)
    .filter(Boolean)
  return [...new Set(diffs)]
})

const filteredPendingQuestions = computed(() => {
  let list = pendingQuestions.value

  if (viewMode.value === 'resubmitted') {
    list = list.filter(q => q.has_author_updated || q.is_resubmitted || q.status === 'resubmitted')
  } else {
    list = list.filter(q => !q.has_author_updated && !q.is_resubmitted && q.status !== 'resubmitted')
  }

  if (selectedSubjectFilter.value) {
    list = list.filter(q => q.subject_name === selectedSubjectFilter.value)
  }

  if (selectedGradeFilter.value) {
    list = list.filter(q => q.grade_name === selectedGradeFilter.value)
  }

  if (selectedDifficultyFilter.value) {
    list = list.filter(q => q.difficulty === selectedDifficultyFilter.value)
  }

  if (searchQuery.value.trim()) {
    const qStr = searchQuery.value.toLowerCase().trim()
    list = list.filter(item => {
      const text = (item.content || item.text || '').toLowerCase()
      const author = (item.user_name || item.user?.name || '').toLowerCase()
      const idStr = String(item.id)
      const subject = (item.subject_name || '').toLowerCase()
      return text.includes(qStr) || author.includes(qStr) || idStr.includes(qStr) || subject.includes(qStr)
    })
  }

  return list
})

const isAllSelected = computed(() => {
  if (filteredPendingQuestions.value.length === 0) return false
  return filteredPendingQuestions.value.every(q => selectedQuestionIds.value.includes(q.id))
})

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedQuestionIds.value = []
  } else {
    selectedQuestionIds.value = filteredPendingQuestions.value.map(q => q.id)
  }
}

const toggleSelectQuestion = (id) => {
  const idx = selectedQuestionIds.value.indexOf(id)
  if (idx > -1) {
    selectedQuestionIds.value.splice(idx, 1)
  } else {
    selectedQuestionIds.value.push(id)
  }
}

const fetchPendingQuestions = async () => {
  isLoadingPending.value = true
  try {
    const res = await adminQuestionsApi.fetchPending()
    pendingQuestions.value = res.items || res.data?.data || res.data || []
    selectedQuestionIds.value = []
  } catch (error) {
    console.error('Lỗi khi lấy câu hỏi chờ duyệt:', error)
    if (showToast) showToast('Không thể tải danh sách câu hỏi chờ duyệt', 'error')
  } finally {
    isLoadingPending.value = false
  }
}

const moderateQuestion = async (id, action, reason = null) => {
  try {
    await adminQuestionsApi.moderate(id, { action, note: reason })
    const statusText = action === 'approve' ? 'đã được duyệt công khai' : 'đã bị từ chối'
    if (showToast) showToast(`Câu hỏi #${id} ${statusText}!`, 'success')
    fetchPendingQuestions()
  } catch (error) {
    console.error(`Lỗi khi ${action} câu hỏi #${id}:`, error)
    if (showToast) showToast(error.response?.data?.message || 'Thao tác thất bại', 'error')
  }
}

const bulkModerate = async (action, reason = null) => {
  if (!selectedQuestionIds.value.length) return
  try {
    await adminQuestionsApi.bulkModerate({
      question_ids: selectedQuestionIds.value,
      action,
      note: reason
    })
    const actionText = action === 'approve' ? 'Duyệt công khai' : 'Từ chối'
    if (showToast) showToast(`${actionText} ${selectedQuestionIds.value.length} câu hỏi thành công!`, 'success')
    fetchPendingQuestions()
  } catch (error) {
    console.error(`Lỗi khi ${action} hàng loạt:`, error)
    if (showToast) showToast(error.response?.data?.message || 'Thao tác hàng loạt thất bại', 'error')
  }
}

const selectQuickReason = (reason) => {
  if (!rejectionReason.value) {
    rejectionReason.value = reason
  } else if (!rejectionReason.value.includes(reason)) {
    rejectionReason.value += `; ${reason}`
  }
}

const openRejectModal = (question = null) => {
  if (question) {
    rejectingQuestion.value = question
    isBulkReject.value = false
  } else {
    rejectingQuestion.value = { id: 'bulk', count: selectedQuestionIds.value.length }
    isBulkReject.value = true
  }
  rejectionReason.value = ''
}

const closeRejectModal = () => {
  rejectingQuestion.value = null
  isBulkReject.value = false
}

const confirmReject = () => {
  const reason = rejectionReason.value || 'Nội dung chưa đạt yêu cầu'
  if (isBulkReject.value) {
    bulkModerate('reject', reason)
  } else if (rejectingQuestion.value && rejectingQuestion.value.id !== 'bulk') {
    moderateQuestion(rejectingQuestion.value.id, 'reject', reason)
  }
  closeRejectModal()
}

const formatTime = (isoString) => {
  if (!isoString) return ''
  const d = new Date(isoString)
  return d.toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  fetchPendingQuestions()
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translate(-50%, 10px);
}
</style>
