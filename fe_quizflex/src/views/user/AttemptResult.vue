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
              {{ attempt.status }}
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

        <!-- QUESTIONS -->
        <div class="space-y-3 pt-2">
          <h3 class="text-base font-bold text-slate-900">
            Chi tiết câu hỏi & đáp án
          </h3>

          <div class="grid gap-3">
            <article
              v-for="(item, index) in attempt.answers_snapshot"
              :key="item.question_id"
              class="rounded-xl border p-4 transition space-y-2"
              :class="
                item.is_correct
                  ? 'border-emerald-200 bg-emerald-50/50'
                  : 'border-red-200 bg-red-50/50'
              "
            >
              <div class="flex flex-wrap items-start justify-between gap-2">
                <span
                  class="rounded px-2 py-0.5 text-xs font-bold"
                  :class="
                    item.is_correct
                      ? 'bg-emerald-100 text-emerald-800'
                      : 'bg-red-100 text-red-800'
                  "
                >
                  Câu {{ index + 1 }}
                  ·
                  {{ item.is_correct ? 'Đúng' : 'Sai' }}
                </span>

                <span class="text-xs font-bold text-slate-600">
                  {{ item.earned_points }}/{{ item.points }} điểm
                </span>
              </div>

              <h4 class="text-sm font-bold text-slate-900 leading-snug">
                {{ item.question }}
              </h4>

              <div class="text-xs space-y-0.5 pt-1 text-slate-600">
                <p>
                  Bạn đã chọn:

                  <b
                    :class="
                      item.is_correct
                        ? 'text-emerald-700'
                        : 'text-red-700'
                    "
                  >
                    {{
                      item.selected_answer_keys?.join(', ')
                      || 'Chưa chọn'
                    }}
                  </b>
                </p>

                <p v-if="!item.is_correct">
                  Đáp án đúng:

                  <b class="text-emerald-700">
                    {{ item.correct_answer_keys?.join(', ') }}
                  </b>
                </p>
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
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import AppLoadingState from '@/components/common/AppLoadingState.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'

import {
  attemptsApi,
  formatSeconds,
} from '@/services/api'

const route = useRoute()

const attempt = ref(null)
const isLoading = ref(false)
const errorMessage = ref('')

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
