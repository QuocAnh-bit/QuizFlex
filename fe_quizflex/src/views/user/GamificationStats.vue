<template>
  <div class="app-container py-10 space-y-10">

    <!-- Stat cards -->
    <section>
      <h2 class="section-title">Tổng quan</h2>
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
          <div class="stat-label">Level</div>
        </div>
        <div class="stat-card">
          <i class="ti ti-medal stat-icon text-emerald-600"></i>
          <div class="stat-value">{{ stats.badges?.length || 0 }}</div>
          <div class="stat-label">Huy hiệu</div>
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
      <h2 class="section-title">Huy hiệu</h2>
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div
          v-for="badge in allBadges"
          :key="badge.id"
          :class="['card text-center transition', isEarned(badge.id) ? '' : 'opacity-40']"
        >
          <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-[var(--surface-soft)]">
            <span class="text-2xl">{{ badge.icon }}</span>
          </div>
          <div class="font-bold text-sm">{{ badge.name }}</div>
          <div class="mt-1 text-xs text-[var(--muted)]">{{ badge.description }}</div>
          <div v-if="isEarned(badge.id)" class="mt-2">
            <span class="pill-green text-xs">Đã đạt</span>
          </div>
        </div>
      </div>
    </section>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

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

onMounted(async () => {
  const [statsRes, badgesRes] = await Promise.all([
    axios.get('/api/user/stats'),
    axios.get('/api/badges'),
  ])
  stats.value = statsRes.data
  allBadges.value = badgesRes.data
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