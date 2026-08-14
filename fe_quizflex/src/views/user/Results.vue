<template>
  <section class="grid gap-6 py-4">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Kết quả học tập</p>
        <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Lịch sử bài làm</h1>
        <p class="mt-1 text-sm text-slate-600">Xem lại điểm số, thời gian làm và chi tiết các lượt làm quiz của bạn.</p>
      </div>
      <router-link class="btn-primary text-xs shrink-0 self-start sm:self-auto" to="/quizzes">
        Làm quiz mới →
      </router-link>
    </div>

    <!-- Summary Stats -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <article v-for="stat in stats" :key="stat.label" class="card p-5">
        <span class="text-xs font-semibold text-slate-500">{{ stat.label }}</span>
        <b class="mt-2 block text-2xl font-black text-slate-900">{{ stat.value }}</b>
      </article>
    </div>

    <div v-if="isLoading" class="card p-10 text-center text-sm font-semibold text-slate-500">
      Đang tải lịch sử làm bài...
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700">
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
              {{ formatDate(item.finished_at || item.started_at) }} · {{ formatSeconds(item.time_spent_seconds) }} · Trạng thái: <b class="text-slate-700">{{ item.status }}</b>
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
            <span class="text-slate-500">Điểm: {{ item.score }}/{{ item.total_points }}</span>
            <span class="text-[#7C3AED]">{{ Math.round(item.score_percent) }}%</span>
          </div>
        </div>
      </router-link>
    </div>

    <div v-if="!isLoading && attempts.length === 0" class="card p-12 text-center text-slate-500">
      <span class="text-3xl block mb-2">📝</span>
      <h3 class="text-lg font-bold text-slate-800">Chưa có lượt làm bài nào</h3>
      <p class="mt-1 text-xs">Hãy bắt đầu luyện tập một bộ quiz để lưu lại kết quả tại đây.</p>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import VisibilityBadge from '@/components/common/VisibilityBadge.vue'
import { attemptsApi, formatSeconds } from '@/services/api'

const attempts = ref([])
const isLoading = ref(false)
const errorMessage = ref('')

const stats = computed(() => {
  const completed = attempts.value.filter((item) => item.status === 'completed')
  const average = completed.length ? Math.round(completed.reduce((sum, item) => sum + Number(item.score_percent || 0), 0) / completed.length) : 0
  const best = completed.length ? Math.max(...completed.map((item) => Math.round(Number(item.score_percent || 0)))) : 0

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
