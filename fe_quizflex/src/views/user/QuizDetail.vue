<template>
  <section class="py-4">
    <Transition name="fade" mode="out-in">

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
            class="flex flex-wrap gap-2.5 pt-4 border-t border-slate-100"
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

            <router-link
              class="btn-ghost text-xs px-4 py-2.5"
              to="/quizzes"
            >
              Quay lại danh sách
            </router-link>

            <button
              class="btn-ghost text-xs px-4 py-2.5 text-red-600 hover:text-red-700 hover:bg-red-50"
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

    </Transition>

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
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import {
  UserRound,
  ListChecks,
  Clock3,
  UsersRound,
} from '@lucide/vue'

import AppLoadingState from '@/components/common/AppLoadingState.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import VisibilityBadge from '@/components/common/VisibilityBadge.vue'
import ReportModal from '@/components/common/ReportModal.vue'

import {
  normalizeQuestion,
  normalizeQuizCard,
  quizzesApi,
} from '@/services/api'

const route = useRoute()

const quiz = ref(null)
const isLoading = ref(false)
const errorMessage = ref('')
const isReportModalOpen = ref(false)

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

onMounted(loadQuiz)
</script>
