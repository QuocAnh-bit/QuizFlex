<template>
  <section class="py-4">
    <!-- 1. LOADING STATE -->
    <AppLoadingState
      v-if="isLoading"
      title="Đang tải kết quả bài làm..."
      message="Vui lòng chờ trong giây lát để hệ thống tổng hợp điểm số và nhận xét."
    />

    <!-- 2. ERROR STATE -->
    <AppErrorState
      v-else-if="errorMessage"
      title="Không thể tải kết quả bài làm"
      :message="errorMessage"
      @retry="loadAttempt"
    >
      <template #actions>
        <router-link
          class="btn-ghost text-xs"
          to="/results"
        >
          Quay lại lịch sử làm bài
        </router-link>
      </template>
    </AppErrorState>

    <!-- 3. LOADED STATE -->
    <div
      v-else-if="attempt"
      class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]"
    >
      <!-- MAIN -->
      <article class="card p-6 sm:p-8 space-y-6">

        <!-- Header -->
        <div class="border-b border-slate-100 pb-4">
          <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">
            Chi tiết kết quả
          </p>

          <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">
            {{ attempt.quiz_title || attempt.quiz?.title }}
          </h1>

          <p class="mt-1 text-xs text-slate-500">
            Mã lượt làm: #{{ attempt.id }}
          </p>
        </div>

        <!-- EDTECH HERO SCORE & STAT SUMMARY CARD -->
        <div class="rounded-2xl border border-purple-100 bg-gradient-to-br from-purple-50/80 via-slate-50/50 to-white p-5 sm:p-6 shadow-xs space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-purple-100/70 pb-4">
            <div class="flex items-center gap-4">
              
              <div>
                <div class="flex items-center gap-2">
                  <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Điểm tổng kết</span>
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border"
                    :class="gradeEvaluation.bg"
                  >
                    {{ gradeEvaluation.label }}
                  </span>
                </div>
                <div class="mt-0.5 flex items-baseline gap-1.5">
                  <span class="text-2xl sm:text-3xl font-black text-slate-900">{{ scaledScore10 }}</span>
                  <span class="text-sm font-bold text-slate-500">/ 10 điểm</span>
                </div>
              </div>
            </div>

            <!-- Tỷ lệ thanh tiến trình chính xác -->
            <div class="w-full sm:w-56 space-y-1.5">
              <div class="flex justify-between text-xs font-semibold">
                <span class="text-slate-600">Độ chính xác</span>
                <span class="text-[#7C3AED] font-bold">{{ accuracyPercent }}%</span>
              </div>
              <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-200/80">
                <div
                  class="h-full rounded-full transition-all duration-500 bg-gradient-to-r from-[#7C3AED] to-emerald-500"
                  :style="{ width: `${Math.min(100, Math.max(0, accuracyPercent))}%` }"
                ></div>
              </div>
            </div>
          </div>

          <!-- Chi tiết 4 chỉ số phụ -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
            <div class="rounded-xl border border-slate-200/80 bg-white/90 p-3 text-center shadow-2xs">
              <span class="text-[11px] font-semibold text-slate-500 block">Số câu đúng</span>
              <b class="mt-0.5 block text-lg font-black text-emerald-700">
                {{ correctCount }}/{{ totalQuestionsCount }} câu
              </b>
            </div>

            <div class="rounded-xl border border-slate-200/80 bg-white/90 p-3 text-center shadow-2xs">
              <span class="text-[11px] font-semibold text-slate-500 block">Tỷ lệ đúng</span>
              <b class="mt-0.5 block text-lg font-black text-[#7C3AED]">
                {{ accuracyPercent }}%
              </b>
            </div>

            <div class="rounded-xl border border-slate-200/80 bg-white/90 p-3 text-center shadow-2xs">
              <span class="text-[11px] font-semibold text-slate-500 block">Thời gian làm</span>
              <b class="mt-0.5 block text-lg font-black text-slate-900">
                {{ formatSeconds(attempt.time_spent_seconds) }}
              </b>
            </div>

            <div class="rounded-xl border border-slate-200/80 bg-white/90 p-3 text-center shadow-2xs">
              <span class="text-[11px] font-semibold text-slate-500 block">Trạng thái</span>
              <b class="mt-0.5 block text-base font-bold text-emerald-700 capitalize">
                {{ formatStatus(attempt.status) }}
              </b>
            </div>
          </div>
        </div>

        <!-- TEACHER EVALUATION -->
        <div
          v-if="attempt.evaluation_comment"
          class="rounded-xl border border-purple-200 bg-purple-50 p-4 space-y-1"
        >
          <p
            class="text-xs font-bold text-[#7C3AED] uppercase tracking-wider"
          >
            Phản hồi từ giảng viên
          </p>

          <p class="text-sm font-medium leading-relaxed text-slate-800 italic">
            "{{ attempt.evaluation_comment }}"
          </p>

          <p
            v-if="attempt.evaluation_comment_updated_at"
            class="text-[10px] text-slate-500"
          >
            Cập nhật lúc:
            {{ formatDateTime(attempt.evaluation_comment_updated_at) }}
          </p>
        </div>

        <!-- QUESTIONS SECTION -->
        <div class="space-y-3 pt-2">
          <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
            <h3 class="text-base font-bold text-slate-900">
              Chi tiết câu hỏi & đáp án
            </h3>
            <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-full">
              Tổng số: <b class="text-[#7C3AED]">{{ attempt.answers_snapshot?.length || 0 }} câu</b>
            </span>
          </div>

          <!-- Independent Scroll Container (Shows ~1-2 questions on desktop) -->
          <div class="max-h-[600px] overflow-y-auto pr-1.5 space-y-3 scrollbar-soft">
            <article
              v-for="(item, index) in attempt.answers_snapshot"
              :key="item.question_id || index"
              class="rounded-xl border p-4 transition space-y-3 bg-white shadow-2xs"
              :class="
                item.is_correct
                  ? 'border-emerald-200 bg-emerald-50/15'
                  : 'border-rose-200 bg-rose-50/15'
              "
            >
              <!-- Question Card Header -->
              <div
                class="flex flex-wrap items-center justify-between gap-2 border-b pb-2"
                :class="item.is_correct ? 'border-emerald-100' : 'border-rose-100'"
              >
                <div class="flex items-center gap-2">
                  <span
                    class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-bold"
                    :class="
                      item.is_correct
                        ? 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                        : 'bg-rose-100 text-rose-800 border border-rose-200'
                    "
                  >
                    <Check v-if="item.is_correct" :size="12" :stroke-width="2.5" />
                    <X v-else :size="12" :stroke-width="2.5" />
                    <span>Câu {{ index + 1 }} · {{ item.is_correct ? 'Đúng' : 'Sai' }}</span>
                  </span>

                  <span
                    v-if="item.type === 'multi_choice'"
                    class="rounded bg-purple-100 px-1.5 py-0.5 text-[10px] font-bold text-purple-700 uppercase"
                  >
                    Nhiều đáp án
                  </span>
                </div>

                <div class="flex items-center gap-2">
                  <span class="text-xs font-bold text-slate-600">
                    {{ item.earned_points }}/{{ item.points }} điểm
                  </span>

                  <button
                    v-if="item.question_id || item.id"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-md border border-rose-200 bg-rose-50 px-2 py-0.5 text-[11px] font-bold text-rose-600 transition hover:bg-rose-100 cursor-pointer"
                    title="Báo cáo vi phạm hoặc sai sót ở câu hỏi này"
                    @click="openReportModal(item)"
                  >
                    <span>🚩 Báo cáo</span>
                  </button>
                </div>
              </div>

              <!-- Question Content -->
              <div class="text-sm font-bold text-slate-900 leading-snug">
                <MathText :text="item.question || item.question_content" />
              </div>

              <!-- All Answer Options List: 2-column grid on desktop, 1-column on mobile -->
              <div
                v-if="item.answers && item.answers.length > 0"
                class="grid grid-cols-1 sm:grid-cols-2 gap-2"
              >
                <div
                  v-for="(ans, aIdx) in item.answers"
                  :key="ans.id || aIdx"
                  class="flex items-center gap-2.5 rounded-lg border p-2.5 text-xs transition duration-150"
                  :class="getAnswerOptionCardClass(item, ans)"
                >
                  <!-- Key badge -->
                  <span
                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded text-xs font-black transition"
                    :class="getAnswerOptionKeyClass(item, ans)"
                  >
                    {{ ans.key || ans.answer_key || String.fromCharCode(65 + aIdx) }}
                  </span>

                  <!-- Content text -->
                  <span class="flex-1 font-medium leading-snug break-words text-xs">
                    <MathText :text="ans.content || ans.text" />
                  </span>

                  <!-- Status Badge on the right (Compact & Inline) -->
                  <div class="shrink-0">
                    <!-- User selected AND correct -->
                    <span
                      v-if="isAnswerSelected(item, ans) && isAnswerCorrect(item, ans)"
                      class="inline-flex items-center gap-0.5 rounded bg-emerald-600 text-white px-1.5 py-0.5 text-[10px] font-bold shadow-xs whitespace-nowrap"
                    >
                      <Check :size="11" :stroke-width="3" />
                      <span>Bạn chọn • Đúng</span>
                    </span>

                    <!-- User selected AND WRONG -->
                    <span
                      v-else-if="isAnswerSelected(item, ans) && !isAnswerCorrect(item, ans)"
                      class="inline-flex items-center gap-0.5 rounded bg-rose-600 text-white px-1.5 py-0.5 text-[10px] font-bold shadow-xs whitespace-nowrap"
                    >
                      <X :size="11" :stroke-width="3" />
                      <span>Bạn chọn</span>
                    </span>

                    <!-- Not selected BUT is the correct answer -->
                    <span
                      v-else-if="!isAnswerSelected(item, ans) && isAnswerCorrect(item, ans)"
                      class="inline-flex items-center gap-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-300 px-1.5 py-0.5 text-[10px] font-bold whitespace-nowrap"
                    >
                      <Check :size="11" :stroke-width="2.5" />
                      <span>Đáp án đúng</span>
                    </span>
                  </div>
                </div>
              </div>

              <!-- Question Report Action -->
              <div
                class="flex items-center justify-end pt-1.5 border-t"
                :class="item.is_correct ? 'border-emerald-100' : 'border-rose-100'"
              >
                <span
                  v-if="reportedQuestionIds.has(item.question_id)"
                  class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-100/80 border border-emerald-200 px-2 py-0.5 rounded-md"
                >
                  <Check :size="12" :stroke-width="2.5" />
                  <span>Đã báo cáo</span>
                </span>

              </div>
            </article>
          </div>
        </div>
      </article>

      <!-- SIDEBAR -->
      <aside class="grid content-start gap-5">
        <article class="card p-5 space-y-3">

          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">
            Thao tác
          </h3>

          <div class="grid gap-2">

            <router-link
              v-if="attempt"
              class="btn-primary text-xs w-full text-center"
              :to="`/quizzes/${attempt.quiz_id}/play`"
            >
              Làm lại bài này
            </router-link>

            <router-link
              class="btn-secondary text-xs w-full text-center"
              to="/results"
            >
              Xem lịch sử làm bài
            </router-link>

            <router-link
              class="btn-ghost text-xs w-full text-center"
              to="/quizzes"
            >
              Khám phá quiz khác
            </router-link>

          </div>
        </article>
      </aside>
    </div>

    <!-- QUESTION REPORT MODAL -->
    <QuestionReportModal
      v-if="selectedReportQuestion && (selectedReportQuestion.question_id || selectedReportQuestion.id)"
      :is-open="isReportModalOpen"
      :question-id="selectedReportQuestion.question_id || selectedReportQuestion.id"
      :question-snippet="selectedReportQuestion.question || selectedReportQuestion.question_content"
      @close="closeReportModal"
      @reported="handleQuestionReported"
    />
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { Check, X, Flag } from 'lucide-vue-next'

import AppLoadingState from '@/components/common/AppLoadingState.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import QuestionReportModal from '@/components/question/QuestionReportModal.vue'
import MathText from '@/components/MathText.vue'

import {
  attemptsApi,
  formatSeconds,
} from '@/services/api'

const route = useRoute()

const attempt = ref(null)
const isLoading = ref(false)
const errorMessage = ref('')

const isReportModalOpen = ref(false)
const selectedReportQuestion = ref(null)
const reportedQuestionIds = ref(new Set())

// --- CHUẨN HÓA ĐIỂM SỐ VÀ ĐỘ CHÍNH XÁC (THANG 10 DÀNH CHO HỌC SINH) ---
const scaledScore10 = computed(() => {
  if (!attempt.value) return '0'
  let raw = 0
  if (attempt.value.scaled_score_10 !== undefined && attempt.value.scaled_score_10 !== null) {
    raw = Number(attempt.value.scaled_score_10)
  } else {
    const tot = Number(attempt.value.total_points) || 0
    const sc = Number(attempt.value.score) || 0
    raw = tot > 0 ? (sc / tot) * 10 : 0
  }
  // Hiển thị gọn gàng, tự nhiên: 10, 8.5, 5.8, 7.25
  return Number.isInteger(raw) ? raw.toString() : parseFloat(raw.toFixed(2)).toString()
})

const correctCount = computed(() => {
  if (!attempt.value) return 0
  if (attempt.value.correct_count !== undefined && attempt.value.correct_count !== null) {
    return attempt.value.correct_count
  }
  const snap = attempt.value.answers_snapshot || []
  return snap.filter(i => Boolean(i.is_correct)).length
})

const totalQuestionsCount = computed(() => {
  if (!attempt.value) return 0
  if (attempt.value.total_questions) {
    return attempt.value.total_questions
  }
  const snap = attempt.value.answers_snapshot || []
  return snap.length || 0
})

const accuracyPercent = computed(() => {
  if (!attempt.value) return 0
  if (attempt.value.accuracy_percentage !== undefined && attempt.value.accuracy_percentage !== null) {
    return Math.round(attempt.value.accuracy_percentage)
  }
  const total = totalQuestionsCount.value
  return total > 0 ? Math.round((correctCount.value / total) * 100) : Math.round(attempt.value.score_percent || 0)
})

const gradeEvaluation = computed(() => {
  const score = parseFloat(scaledScore10.value) || 0
  if (score >= 9.0) {
    return { label: 'Xuất sắc', bg: 'bg-emerald-50 text-emerald-800 border-emerald-300' }
  }
  if (score >= 8.0) {
    return { label: 'Giỏi', bg: 'bg-emerald-50 text-emerald-700 border-emerald-200' }
  }
  if (score >= 6.5) {
    return { label: 'Khá', bg: 'bg-sky-50 text-sky-700 border-sky-200' }
  }
  if (score >= 5.0) {
    return { label: 'Đạt', bg: 'bg-amber-50 text-amber-800 border-amber-300' }
  }
  return { label: 'Chưa đạt / Cần cố gắng', bg: 'bg-rose-50 text-rose-700 border-rose-200' }
})

const isAnswerSelected = (item, ans) => {
  const ansKey = ans.key || ans.answer_key
  const selectedKeys = item.selected_answer_keys || []
  const selectedIds = item.selected_answer_ids || []
  if (ansKey && selectedKeys.includes(ansKey)) return true
  if (ans.id && selectedIds.includes(ans.id)) return true
  return false
}

const isAnswerCorrect = (item, ans) => {
  if (ans.is_correct !== undefined && ans.is_correct !== null) {
    return Boolean(ans.is_correct)
  }
  const ansKey = ans.key || ans.answer_key
  const correctKeys = item.correct_answer_keys || []
  const correctIds = item.correct_answer_ids || []
  if (ansKey && correctKeys.includes(ansKey)) return true
  if (ans.id && correctIds.includes(ans.id)) return true
  return false
}

const getAnswerOptionCardClass = (item, ans) => {
  const selected = isAnswerSelected(item, ans)
  const correct = isAnswerCorrect(item, ans)

  if (selected && correct) {
    return 'border-emerald-500 bg-emerald-50/80 text-emerald-950 shadow-xs'
  }
  if (selected && !correct) {
    return 'border-rose-400 bg-rose-50/80 text-rose-950 shadow-xs'
  }
  if (!selected && correct) {
    return 'border-emerald-400 bg-emerald-50/40 text-emerald-900'
  }
  return 'border-slate-200 bg-white text-slate-700'
}

const getAnswerOptionKeyClass = (item, ans) => {
  const selected = isAnswerSelected(item, ans)
  const correct = isAnswerCorrect(item, ans)

  if (selected && correct) {
    return 'bg-emerald-600 text-white font-black'
  }
  if (selected && !correct) {
    return 'bg-rose-600 text-white font-black'
  }
  if (!selected && correct) {
    return 'bg-emerald-100 text-emerald-800 border border-emerald-300 font-black'
  }
  return 'bg-slate-100 text-slate-700 border border-slate-200 font-bold'
}

const formatStatus = (status) => {
  if (!status) return ''
  const s = String(status).toLowerCase()
  const map = {
    completed: 'Hoàn thành',
    in_progress: 'Đang làm',
    submitted: 'Đã nộp bài',
    finished: 'Đã kết thúc',
    abandoned: 'Bỏ dở',
    timed_out: 'Hết giờ',
  }
  return map[s] || status
}

const openReportModal = (questionItem) => {
  if (!questionItem) return
  if (typeof questionItem === 'number' || typeof questionItem === 'string') {
    const qId = Number(questionItem)
    const found = attempt.value?.answers_snapshot?.find(
      (it) => it.question_id === qId || it.id === qId
    )
    selectedReportQuestion.value = found || {
      question_id: qId,
      question: '',
    }
  } else {
    selectedReportQuestion.value = {
      ...questionItem,
      question_id: questionItem.question_id || questionItem.id,
    }
  }
  isReportModalOpen.value = true
}

const closeReportModal = () => {
  isReportModalOpen.value = false
  selectedReportQuestion.value = null
}

const handleQuestionReported = () => {
  const qId = selectedReportQuestion.value?.question_id || selectedReportQuestion.value?.id
  if (qId) {
    reportedQuestionIds.value.add(qId)
  }
}

const formatDateTime = (value) => {
  if (!value) return ''

  return new Date(value).toLocaleString('vi-VN')
}

const loadAttempt = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    attempt.value = await attemptsApi.get(route.params.id)
  } catch (error) {
    errorMessage.value = `Không tải được kết quả: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

onMounted(loadAttempt)
</script>
