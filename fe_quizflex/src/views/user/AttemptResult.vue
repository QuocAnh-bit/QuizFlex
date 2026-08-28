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

        <!-- STAT SUMMARY -->
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">

          <div
            class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-center"
          >
            <span class="text-xs font-semibold text-slate-500">
              Điểm số
            </span>

            <b class="mt-1 block text-2xl font-black text-slate-900">
              {{ attempt.score }}/{{ attempt.total_points }}
            </b>
          </div>

          <div
            class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-center"
          >
            <span class="text-xs font-semibold text-slate-500">
              Tỷ lệ chính xác
            </span>

            <b class="mt-1 block text-2xl font-black text-[#7C3AED]">
              {{ Math.round(attempt.score_percent) }}%
            </b>
          </div>

          <div
            class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-center"
          >
            <span class="text-xs font-semibold text-slate-500">
              Thời gian làm
            </span>

            <b class="mt-1 block text-2xl font-black text-slate-900">
              {{ formatSeconds(attempt.time_spent_seconds) }}
            </b>
          </div>

          <div
            class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-center"
          >
            <span class="text-xs font-semibold text-slate-500">
              Trạng thái
            </span>

            <b class="mt-1 block text-lg font-bold text-emerald-700 capitalize">
              {{ formatStatus(attempt.status) }}
            </b>
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

                <button
                  v-else
                  type="button"
                  class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-400 hover:text-rose-600 hover:bg-rose-50 px-2 py-0.5 rounded-md transition cursor-pointer"
                  title="Báo cáo câu hỏi có sai sót"
                  @click="openReportModal(item)"
                >
                  <Flag :size="12" />
                  <span>Báo cáo câu hỏi</span>
                </button>
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
import { onMounted, ref } from 'vue'
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
