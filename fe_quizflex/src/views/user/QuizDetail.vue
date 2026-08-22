<template>
  <section class="py-4">
    <!-- 1. LOADING STATE -->
    <AppLoadingState
      v-if="isLoading"
      title="Đang tải chi tiết quiz..."
      message="Vui lòng chờ trong giây lát để lấy thông tin quiz và danh sách câu hỏi."
      icon="user"
    />

    <!-- 2. ERROR STATE -->
    <AppErrorState
      v-else-if="errorMessage"
      title="Không thể tải chi tiết quiz"
      :message="errorMessage"
      @retry="loadQuiz"
    >
      <template #actions>
        <router-link
          class="btn-ghost text-xs"
          to="/quizzes"
        >
          Quay lại danh sách
        </router-link>
      </template>
    </AppErrorState>

      <!-- 3. LOADED STATE -->
      <div
        v-else-if="quiz"
        class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]"
      >

        <!-- MAIN -->
        <article class="card p-6 sm:p-8 space-y-6">

          <!-- COVER -->
          <div class="overflow-hidden rounded-xl border border-slate-200 shadow-sm">
            <div
              class="relative min-h-[200px] sm:min-h-[240px] bg-slate-100 flex items-end p-5"
              :style="{ background: quiz.cover }"
            >

              <!-- Badge -->
              <div
                class="absolute left-4 top-4 rounded-md bg-slate-900/80 px-2.5 py-0.5 text-[10px] font-bold uppercase text-white"
              >
                {{ quiz.badge || 'Quiz' }}
              </div>

              <!-- USER ICON -->
              <div
                class="absolute bottom-4 right-4 grid h-12 w-12 place-items-center rounded-xl bg-white shadow-sm"
              >
                <UserRound
                  class="h-6 w-6 text-[#7C3AED]"
                  :stroke-width="2"
                />
              </div>

            </div>
          </div>

          <!-- QUIZ INFO -->
          <div class="space-y-3">

            <div class="flex flex-wrap items-center gap-2">
              <VisibilityBadge :value="quiz.visibility" />

              <span
                class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600"
              >
                {{ quiz.difficulty }}
              </span>

              <span
                class="rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-semibold text-[#7C3AED]"
              >
                {{ quiz.category }}
              </span>

              <!-- Badge trạng thái kiểm duyệt -->
              <StatusBadge
                v-if="quiz.review_status === 'pending_review' || quiz.review_status === 'rejected'"
                :value="quiz.review_status"
              />
            </div>

            <!-- Review Status Alert Banners -->
            <div
              v-if="quiz.review_status === 'pending_review'"
              class="rounded-2xl border border-amber-500/40 bg-amber-500/10 p-4 text-xs font-bold text-amber-900 shadow-sm flex items-start gap-3"
            >
              <Clock3 class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" />
              <div class="grid gap-0.5">
                <span class="text-amber-800 font-bold">Đang chờ kiểm duyệt</span>
                <span class="text-[11px] font-normal text-amber-700">Yêu cầu công khai của bạn đang được Ban Quản Trị xem xét. Bạn sẽ nhận được thông báo khi có kết quả.</span>
              </div>
            </div>

            <div
              v-else-if="quiz.review_status === 'rejected'"
              class="rounded-2xl border border-rose-500/40 bg-rose-500/10 p-4 text-xs font-bold text-rose-900 shadow-sm flex items-start gap-3"
            >
              <AlertCircle class="mt-0.5 h-4 w-4 shrink-0 text-rose-500" />
              <div class="grid gap-0.5">
                <span class="text-rose-800 font-bold">Yêu cầu bị từ chối</span>
                <span class="text-[11px] font-normal text-rose-700">Lý do: "{{ quiz.rejection_reason || 'Nội dung chưa đạt tiêu chuẩn công khai' }}". Bạn có thể chỉnh sửa lại đề thi và gửi lại yêu cầu.</span>
              </div>
            </div>

            <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">
              {{ quiz.title }}
            </h1>

            <p class="text-sm leading-relaxed text-slate-600">
              {{ quiz.description || 'Quiz này chưa có mô tả.' }}
            </p>

          </div>

          <!-- STATS -->
          <div class="grid gap-3 sm:grid-cols-3 pt-2">

            <!-- Questions -->
            <div
              class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-center"
            >
              <ListChecks
                class="mx-auto h-6 w-6 text-[#7C3AED]"
                :stroke-width="2"
              />

              <span
                class="mt-2 block text-xs font-semibold text-slate-500"
              >
                Số lượng câu
              </span>

              <b
                class="mt-1 block text-2xl font-black text-slate-900"
              >
                {{ quiz.questions }}
              </b>
            </div>

            <!-- Duration -->
            <div
              class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-center"
            >
              <Clock3
                class="mx-auto h-6 w-6 text-[#7C3AED]"
                :stroke-width="2"
              />

              <span
                class="mt-2 block text-xs font-semibold text-slate-500"
              >
                Thời gian làm bài
              </span>

              <b
                class="mt-1 block text-2xl font-black text-slate-900"
              >
                {{ quiz.duration }}
              </b>
            </div>

            <!-- Attempts -->
            <div
              class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-center"
            >
              <UsersRound
                class="mx-auto h-6 w-6 text-[#7C3AED]"
                :stroke-width="2"
              />

              <span
                class="mt-2 block text-xs font-semibold text-slate-500"
              >
                Lượt làm bài
              </span>

              <b
                class="mt-1 block text-2xl font-black text-slate-900"
              >
                {{ quiz.attempts }}
              </b>
            </div>

          </div>

          <!-- ACTIONS -->
          <div
            class="flex flex-wrap items-center gap-2.5 pt-4 border-t border-slate-100"
          >

            <router-link
              class="btn-primary text-xs px-5 py-2.5"
              :to="`/quizzes/${quiz.id}/play`"
            >
              Bắt đầu làm bài
            </router-link>

            <router-link
              class="btn-outline text-xs px-4 py-2.5"
              :to="`/quizzes/${quiz.id}/flashcards`"
            >
              Ôn tập Flashcard
            </router-link>

            <!-- Nút Gửi yêu cầu công khai (cho Quiz thủ công đang Private / Rejected) -->
            <button
              v-if="canRequestReview"
              type="button"
              class="btn-primary text-xs px-4 py-2.5 flex items-center gap-1.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white shadow-sm"
              :disabled="isSubmittingReview"
              @click="isReviewModalOpen = true"
            >
              <Send :size="13" />
              <span>{{ quiz.review_status === 'rejected' ? 'Gửi lại yêu cầu' : 'Yêu cầu công khai' }}</span>
            </button>

            <!-- Badge đang chờ duyệt -->
            <button
              v-else-if="quiz.review_status === 'pending_review' && isOwner"
              type="button"
              class="btn-secondary text-xs px-4 py-2.5 flex items-center gap-1.5 opacity-80 cursor-not-allowed text-amber-700 bg-amber-50 border-amber-200"
              disabled
            >
              <Clock3 :size="13" />
              <span>Đang chờ kiểm duyệt</span>
            </button>

            <!-- Nút Chỉnh sửa Quiz cho tác giả -->
            <router-link
              v-if="isOwner"
              class="btn-secondary text-xs px-4 py-2.5 flex items-center gap-1.5"
              :to="`/quizzes/${quiz.id}/edit`"
            >
              <Pencil :size="13" />
              <span>Chỉnh sửa Quiz</span>
            </router-link>

            <router-link
              class="btn-ghost text-xs px-4 py-2.5"
              to="/quizzes"
            >
              Quay lại danh sách
            </router-link>

            <button
              class="btn-ghost text-xs px-4 py-2.5 text-red-600 hover:text-red-700 hover:bg-red-50 ml-auto"
              @click="isReportModalOpen = true"
            >
              Báo lỗi quiz
            </button>

          </div>

        </article>

        <!-- ASIDE -->
        <aside class="grid content-start gap-5">

          <article class="card p-5 space-y-4">

            <div
              class="flex items-center justify-between border-b border-slate-100 pb-3"
            >
              <div class="flex items-center gap-2">

                <span
                  class="grid h-7 w-7 place-items-center rounded-lg bg-purple-50"
                >
                  <ListChecks
                    class="h-4 w-4 text-[#7C3AED]"
                    :stroke-width="2"
                  />
                </span>

                <h2
                  class="text-xs font-bold uppercase tracking-wider text-slate-400"
                >
                  Danh sách câu hỏi
                </h2>

              </div>

              <span class="text-xs font-semibold text-slate-500">
                {{ questions.length }} câu
              </span>
            </div>

            <div
              class="mt-2 grid max-h-[500px] gap-2.5 overflow-y-auto pr-1 scrollbar-soft"
            >

              <div
                v-for="(question, index) in questions"
                :key="question.id"
                class="rounded-lg border border-slate-100 bg-slate-50 p-3"
              >
                <span class="text-[11px] font-bold text-[#7C3AED]">
                  Câu {{ index + 1 }} • {{ question.points }} điểm
                </span>

                <p
                  class="mt-1 text-xs font-medium leading-snug text-slate-800"
                >
                  {{ question.question }}
                </p>
              </div>

            </div>

          </article>

        </aside>

      </div>

    <!-- MODAL GỬI YÊU CẦU DUYỆT CÔNG KHAI -->
    <div
      v-if="isReviewModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn"
    >
      <div class="card p-6 max-w-lg w-full space-y-4 shadow-2xl animate-scaleUp">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
            <Send :size="18" class="text-[#7C3AED]" />
            <span>Yêu cầu công khai bài Quiz</span>
          </h3>
          <button
            type="button"
            class="text-slate-400 hover:text-slate-600 text-sm font-bold"
            @click="isReviewModalOpen = false"
          >
            ✕
          </button>
        </div>

        <p class="text-xs leading-relaxed text-slate-600">
          Bài Quiz thủ công của bạn sẽ được gửi tới Ban Quản Trị để kiểm duyệt nội dung. Sau khi được phê duyệt, bài Quiz và các câu hỏi đạt chuẩn sẽ được công khai cho toàn bộ cộng đồng QuizFlex.
        </p>

        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-700 block">Lời nhắn gửi Admin (Không bắt buộc)</label>
          <textarea
            v-model="reviewRequestNote"
            rows="3"
            class="field text-xs resize-none w-full"
            placeholder="Ghi chú về đề thi, mục tiêu hoặc tài liệu tham khảo..."
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <button
            type="button"
            class="btn-secondary text-xs px-4 py-2"
            :disabled="isSubmittingReview"
            @click="isReviewModalOpen = false"
          >
            Hủy
          </button>
          <button
            type="button"
            class="btn-primary text-xs px-5 py-2 flex items-center gap-1.5"
            :disabled="isSubmittingReview"
            @click="handleSubmitReview"
          >
            <Send :size="13" />
            <span>{{ isSubmittingReview ? 'Đang gửi...' : 'Xác nhận gửi' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- REPORT MODAL -->
    <ReportModal
      v-if="quiz"
      :is-open="isReportModalOpen"
      :quiz-id="quiz.id"
      @close="isReportModalOpen = false"
    />

  </section>
</template>

<script setup>
import { computed, inject, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import {
  UserRound,
  ListChecks,
  Clock3,
  UsersRound,
  Send,
  Pencil,
  AlertCircle,
} from 'lucide-vue-next'

import AppLoadingState from '@/components/common/AppLoadingState.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import VisibilityBadge from '@/components/common/VisibilityBadge.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import ReportModal from '@/components/common/ReportModal.vue'

import {
  authApi,
  currentUserStorage,
  normalizeQuestion,
  normalizeQuizCard,
  quizReviewApi,
  quizzesApi,
} from '@/services/api'

const route = useRoute()
const showToast = inject('showToast')

const quiz = ref(null)
const isLoading = ref(true)
const errorMessage = ref('')
const isReportModalOpen = ref(false)
const isReviewModalOpen = ref(false)
const reviewRequestNote = ref('')
const isSubmittingReview = ref(false)

const currentUser = currentUserStorage.get()

const isOwner = computed(() => {
  if (!currentUser || !quiz.value) return false
  return currentUser.id === quiz.value.user_id || String(currentUser.role || '').toLowerCase() === 'admin'
})

const canRequestReview = computed(() => {
  if (!quiz.value || !isOwner.value) return false
  const isManual = (quiz.value.creation_mode || 'manual') === 'manual'
  const isNotPublic = !quiz.value.is_public
  const isNotPending = quiz.value.review_status !== 'pending_review'
  return isManual && isNotPublic && isNotPending
})

const questions = computed(() =>
  (quiz.value?.rawQuestions || []).map(normalizeQuestion)
)

const loadQuiz = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const data = await quizzesApi.get(route.params.id)

    quiz.value = {
      ...normalizeQuizCard(data),
      rawQuestions: data.questions || [],
    }
  } catch (error) {
    errorMessage.value = `Không tải được chi tiết quiz: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

const handleSubmitReview = async () => {
  if (!quiz.value) return
  isSubmittingReview.value = true
  try {
    const res = await quizReviewApi.requestReview(quiz.value.id, {
      request_note: reviewRequestNote.value.trim() || undefined,
    })
    if (showToast) {
      showToast(res.message || 'Đã gửi yêu cầu công khai Quiz thành công!', 'success')
    } else {
      alert('Đã gửi yêu cầu công khai Quiz thành công!')
    }
    isReviewModalOpen.value = false
    reviewRequestNote.value = ''
    await loadQuiz()
  } catch (error) {
    const msg = error.response?.data?.message || error.message || 'Không thể gửi yêu cầu duyệt'
    if (showToast) showToast(msg, 'error')
    else alert(msg)
  } finally {
    isSubmittingReview.value = false
  }
}

onMounted(loadQuiz)
</script>
