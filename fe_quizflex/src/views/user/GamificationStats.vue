<template>
  <div class="app-container py-10 space-y-10">

    <!-- Loading State -->
    <div v-if="isLoading" class="flex flex-col items-center justify-center py-20 space-y-4">
      <div class="h-10 w-10 animate-spin rounded-full border-4 border-purple-500 border-t-transparent"></div>
      <p class="text-sm font-semibold text-[var(--muted)]">Đang tải dữ liệu thành tích & huy hiệu...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="card border-red-500/20 bg-red-500/5 p-6 text-center">
      <i class="ti ti-alert-circle text-4xl text-red-500 mb-2"></i>
      <h3 class="font-bold text-red-500">Không thể tải thông tin thành tích</h3>
      <p class="text-xs text-[var(--muted)] mt-1 mb-4">{{ errorMessage }}</p>
      <button @click="fetchData" class="btn-primary inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl">
        <i class="ti ti-refresh"></i> Thử lại
      </button>
    </div>

    <!-- Main Content -->
    <template v-else>
      <!-- Stat cards -->
      <section>
        <h2 class="section-title">Tổng quan thành tích</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div class="stat-card">
            <i class="ti ti-flame stat-icon text-orange-400"></i>
            <div class="stat-value">{{ stats.current_streak }}</div>
            <div class="stat-label">Ngày streak</div>
          </div>
          <div class="stat-card">
            <i class="ti ti-bolt stat-icon text-[var(--primary)]"></i>
            <div class="stat-value">{{ stats.xp }}</div>
            <div class="stat-label">Tổng XP</div>
          </div>
          <div class="stat-card">
            <i class="ti ti-trophy stat-icon text-yellow-600"></i>
            <div class="stat-value">{{ stats.level }}</div>
            <div class="stat-label">Cấp độ (Level)</div>
          </div>
          <div class="stat-card">
            <i class="ti ti-medal stat-icon text-emerald-600"></i>
            <div class="stat-value">{{ stats.badges?.length || 0 }}</div>
            <div class="stat-label">Huy hiệu đã đạt</div>
          </div>
        </div>
      </section>

      <!-- Streak tuần -->
      <section>
        <h2 class="section-title">Streak hàng ngày</h2>
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <span class="font-bold">Tuần này</span>
            <span class="pill-orange">
              <i class="ti ti-flame"></i> {{ stats.current_streak }} ngày liên tiếp
            </span>
          </div>
          <div class="flex gap-2">
            <div
              v-for="(day, i) in weekDays"
              :key="i"
              class="flex flex-1 flex-col items-center gap-2"
            >
              <div
                :class="[
                  'flex h-10 w-10 items-center justify-center rounded-full border',
                  day.done
                    ? 'border-transparent bg-orange-100 text-orange-400'
                    : 'border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)]',
                ]"
              >
                <i :class="day.done ? 'ti ti-flame text-lg' : 'ti ti-circle-dashed text-base'"></i>
              </div>
              <span
                :class="[
                  'text-xs',
                  day.isToday ? 'font-bold text-[var(--primary)]' : 'text-[var(--muted)]',
                ]"
              >{{ day.label }}</span>
            </div>
          </div>
          <div class="mt-4 flex justify-between border-t border-[var(--border)] pt-4">
            <span class="text-sm text-[var(--muted)]">Streak dài nhất</span>
            <span class="font-bold">{{ stats.longest_streak }} ngày</span>
          </div>
        </div>
      </section>

      <!-- Huy hiệu -->
      <section>
        <h2 class="section-title">Danh sách Huy hiệu ({{ stats.badges?.length || 0 }}/{{ allBadges.length }})</h2>
        <div v-if="allBadges.length === 0" class="text-center py-6 text-sm text-[var(--muted)]">
          Chưa có huy hiệu nào trong hệ thống.
        </div>
        <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div
            v-for="badge in allBadges"
            :key="badge.id"
            :class="['card text-center transition hover:shadow-lg', isEarned(badge.id) ? 'border-purple-500/30' : 'opacity-40 grayscale']"
          >
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-[var(--surface-soft)]">
              <span class="text-2xl">{{ badge.icon || '🏅' }}</span>
            </div>
            <div class="font-bold text-sm">{{ badge.name }}</div>
            <div class="mt-1 text-xs text-[var(--muted)]">{{ badge.description }}</div>
            <div v-if="isEarned(badge.id)" class="mt-2">
              <span class="pill-green text-xs">Đã đạt</span>
            </div>
            <div v-else class="mt-2">
              <span class="text-[10px] text-[var(--muted)] uppercase font-semibold">Chưa khóa</span>
            </div>
          </div>
        </div>
      </section>
    </template>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { gamificationApi } from '@/services/api'

const isLoading = ref(true)
const errorMessage = ref('')
const stats = ref({ xp: 0, level: 1, current_streak: 0, longest_streak: 0, badges: [] })
const allBadges = ref([])

const earnedIds = computed(() => stats.value.badges?.map(b => b.badge_id) || [])
const isEarned = (id) => earnedIds.value.includes(id)

const weekDays = computed(() => {
  const labels = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN']
  const today = new Date().getDay()
  const todayIndex = today === 0 ? 6 : today - 1
  return labels.map((label, i) => ({
    label,
    done: i < todayIndex || (i === todayIndex && stats.value.current_streak > 0),
    isToday: i === todayIndex,
  }))
})

const fetchData = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const [statsData, badgesData] = await Promise.all([
      gamificationApi.getUserStats(),
      gamificationApi.getBadges(),
    ])
    stats.value = statsData || { xp: 0, level: 1, current_streak: 0, longest_streak: 0, badges: [] }
    allBadges.value = badgesData || []
  } catch (error) {
    console.error('Lỗi khi tải thông tin thành tích:', error)
    errorMessage.value = error.message || 'Có lỗi xảy ra khi kết nối máy chủ.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.section-title { @apply mb-3 text-xs font-bold uppercase tracking-widest text-[var(--muted)]; }
.card { @apply rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4; }
.stat-card { @apply card flex flex-col items-center gap-1 py-5 text-center; }
.stat-icon { @apply text-3xl; }
.stat-value { @apply text-2xl font-black; }
.stat-label { @apply text-xs text-[var(--muted)]; }
.pill-orange { @apply inline-flex items-center gap-1 rounded-full bg-orange-100 px-3 py-1 text-xs font-bold text-orange-700; }
.pill-green { @apply inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 font-bold text-emerald-700; }
</style>