<template>
  <section class="grid gap-6 py-4">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">
          Kết quả học tập
        </p>

        <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">
          Lịch sử bài làm
        </h1>

        <p class="mt-1 text-sm text-slate-600">
          Xem lại điểm số, thời gian làm và chi tiết các lượt làm quiz của bạn.
        </p>
      </div>

      <router-link
        class="btn-primary text-xs shrink-0 self-start sm:self-auto inline-flex items-center gap-1.5"
        to="/quizzes"
      >
        Làm quiz mới
        <ArrowRight :size="14" :stroke-width="2.5" />
      </router-link>
    </div>

    <!-- Summary Stats -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <article
        v-for="stat in stats"
        :key="stat.label"
        class="card p-5"
      >
        <span class="text-xs font-semibold text-slate-500">
          {{ stat.label }}
        </span>

        <b class="mt-2 block text-2xl font-black text-slate-900">
          {{ stat.value }}
        </b>
      </article>
    </div>

    <div
      v-if="isLoading"
      class="card p-10 text-center text-sm font-semibold text-slate-500"
    >
      Đang tải lịch sử làm bài...
    </div>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700"
    >
      {{ errorMessage }}
    </div>

    <!-- Attempts List -->
    <div class="grid gap-4">
      <router-link
        v-for="item in attempts"
        :key="item.id"
        :to="`/results/${item.id}`"
        class="card p-5 card-hover block space-y-3"
      >
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-base font-bold text-slate-900 hover:text-[#7C3AED] transition">
              {{ item.quiz_title || item.quiz?.title }}
            </h2>

            <p class="mt-1 text-xs text-slate-500">
              {{ formatDate(item.finished_at || item.started_at) }}
              ·
              {{ formatSeconds(item.time_spent_seconds) }}
              · Trạng thái:
              <b class="text-slate-700">{{ item.status }}</b>
            </p>
          </div>

          <VisibilityBadge :value="item.quiz?.visibility || 'public'" />
        </div>

        <div class="space-y-1.5">
          <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100 border border-slate-200">
            <div
              class="h-full rounded-full bg-[#7C3AED]"
              :style="{ width: `${item.score_percent}%` }"
            ></div>
          </div>

          <div class="flex items-center justify-between text-xs font-bold">
            <span class="text-slate-900 font-extrabold flex items-center gap-1">
              <span>Điểm:</span>
              <span class="text-[#7C3AED] text-sm">{{ formatDisplayScore(item) }}</span>
              <span class="text-slate-400 font-normal">/ 10</span>
            </span>

            <span class="text-slate-600 font-semibold">
              <span v-if="item.correct_count !== undefined && item.total_questions">{{ item.correct_count }}/{{ item.total_questions }} câu · </span>
              <span class="text-[#7C3AED] font-bold">{{ Math.round(item.accuracy_percentage ?? item.score_percent ?? 0) }}%</span>
            </span>
          </div>
        </div>
      </router-link>
    </div>

    <!-- Empty State -->
    <div
      v-if="!isLoading && attempts.length === 0"
      class="card p-12 text-center text-slate-500"
    >
      <div class="mb-2 flex justify-center">
        <ClipboardList
          :size="32"
          :stroke-width="1.8"
          class="text-slate-400"
        />
      </div>

      <h3 class="text-lg font-bold text-slate-800">
        Chưa có lượt làm bài nào
      </h3>

      <p class="mt-1 text-xs">
        Hãy bắt đầu luyện tập một bộ quiz để lưu lại kết quả tại đây.
      </p>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import {
  ArrowRight,
  ClipboardList,
} from 'lucide-vue-next'

import VisibilityBadge from '@/components/common/VisibilityBadge.vue'
import { attemptsApi, formatSeconds } from '@/services/api'

const attempts = ref([])
const isLoading = ref(false)
const errorMessage = ref('')

const stats = computed(() => {
  const completed = attempts.value.filter((item) => item.status === 'completed')

  const average = completed.length
    ? Math.round(
        completed.reduce(
          (sum, item) => sum + Number(item.score_percent || 0),
          0
        ) / completed.length
      )
    : 0

  const best = completed.length
    ? Math.max(
        ...completed.map((item) =>
          Math.round(Number(item.score_percent || 0))
        )
      )
    : 0

  return [
    { label: 'Bài đã làm', value: attempts.value.length },
    { label: 'Điểm trung bình', value: `${average}%` },
    { label: 'Điểm cao nhất', value: `${best}%` },
    { label: 'Đã hoàn thành', value: completed.length },
  ]
})

const formatDate = (value) => {
  if (!value) return 'Chưa có thời gian'
  return new Date(value).toLocaleString('vi-VN')
}

const formatDisplayScore = (item) => {
  let raw = 0
  if (item.scaled_score_10 !== undefined && item.scaled_score_10 !== null) {
    raw = Number(item.scaled_score_10)
  } else {
    const tot = Number(item.total_points) || 0
    const sc = Number(item.score) || 0
    raw = tot > 0 ? (sc / tot) * 10 : 0
  }
  return Number.isInteger(raw) ? raw.toString() : parseFloat(raw.toFixed(2)).toString()
}

const loadAttempts = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    attempts.value = await attemptsApi.list({ status: 'completed' })
  } catch (error) {
    errorMessage.value = `Không tải được lịch sử: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

onMounted(loadAttempts)
</script>
