<template>
  <div class="py-4 space-y-8 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Hồ sơ học tập</p>
        <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Thành tích & Điểm thưởng</h1>
        <p class="mt-1 text-sm text-slate-600">Theo dõi chuỗi ngày học liên tục (Streak), cấp độ và bộ sưu tập huy hiệu.</p>
      </div>
      <div class="flex items-center gap-2">
        <span class="rounded-full bg-purple-50 border border-purple-200 px-3.5 py-1 text-xs font-bold text-[#7C3AED]">
          🔥 Chuỗi: {{ stats.current_streak }} ngày
        </span>
      </div>
    </div>

    <!-- 4 Overview Stats -->
    <section class="space-y-3">
      <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Tổng quan chỉ số</h2>
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="card p-5 text-center space-y-1">
          <span class="text-2xl block mb-1">🔥</span>
          <div class="text-2xl font-black text-amber-600">{{ stats.current_streak }}</div>
          <div class="text-xs font-semibold text-slate-500">Chuỗi ngày (Streak)</div>
        </div>
        <div class="card p-5 text-center space-y-1">
          <span class="text-2xl block mb-1">⚡</span>
          <div class="text-2xl font-black text-[#7C3AED]">{{ stats.xp }}</div>
          <div class="text-xs font-semibold text-slate-500">Tổng điểm XP</div>
        </div>
        <div class="card p-5 text-center space-y-1">
          <span class="text-2xl block mb-1">👑</span>
          <div class="text-2xl font-black text-slate-900">Lv.{{ stats.level }}</div>
          <div class="text-xs font-semibold text-slate-500">Cấp độ học viên</div>
        </div>
        <div class="card p-5 text-center space-y-1">
          <span class="text-2xl block mb-1">🏅</span>
          <div class="text-2xl font-black text-emerald-600">{{ stats.badges?.length || 0 }}</div>
          <div class="text-xs font-semibold text-slate-500">Huy hiệu đạt được</div>
        </div>
      </div>
    </section>

    <!-- Weekly Streak -->
    <section class="space-y-3">
      <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Tiến độ tuần này</h2>
      <div class="card p-6 space-y-5">
        <div class="flex items-center justify-between">
          <span class="text-sm font-bold text-slate-900">Lịch học 7 ngày qua</span>
          <span class="rounded-full bg-amber-50 border border-amber-200 px-3 py-0.5 text-xs font-bold text-amber-800">
            🔥 {{ stats.current_streak }} ngày liên tiếp
          </span>
        </div>

        <div class="grid grid-cols-7 gap-2">
          <div
            v-for="(day, i) in weekDays"
            :key="i"
            class="flex flex-col items-center gap-2 rounded-xl p-3 border transition"
            :class="day.done ? 'border-amber-200 bg-amber-50/70' : 'border-slate-100 bg-slate-50'"
          >
            <div
              class="flex h-9 w-9 items-center justify-center rounded-full text-base font-bold"
              :class="day.done ? 'bg-amber-500 text-white shadow-sm' : 'bg-slate-200 text-slate-400'"
            >
              {{ day.done ? '🔥' : '·' }}
            </div>
            <span
              class="text-xs font-bold"
              :class="day.isToday ? 'text-[#7C3AED]' : 'text-slate-600'"
            >
              {{ day.label }}
            </span>
          </div>
        </div>

        <div class="flex justify-between border-t border-slate-100 pt-4 text-xs font-semibold">
          <span class="text-slate-500">Kỷ lục chuỗi dài nhất:</span>
          <span class="font-bold text-slate-900">{{ stats.longest_streak }} ngày liên tục</span>
        </div>
      </div>
    </section>

    <!-- Badges Collection -->
    <section class="space-y-3">
      <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Bộ sưu tập huy hiệu</h2>
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div
          v-for="badge in allBadges"
          :key="badge.id"
          class="card p-5 text-center transition flex flex-col justify-between"
          :class="isEarned(badge.id) ? 'border-purple-200 bg-white' : 'opacity-50 bg-slate-50 border-slate-200'"
        >
          <div>
            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50 text-2xl border border-slate-100">
              <span>{{ badge.icon }}</span>
            </div>
            <div class="font-bold text-sm text-slate-900">{{ badge.name }}</div>
            <div class="mt-1 text-xs text-slate-500 leading-relaxed">{{ badge.description }}</div>
          </div>
          <div class="mt-4 pt-3 border-t border-slate-100">
            <span v-if="isEarned(badge.id)" class="rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700">
              ✓ Đã đạt được
            </span>
            <span v-else class="text-[11px] font-semibold text-slate-400">
              🔒 Chưa mở khóa
            </span>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

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
  try {
    const [statsRes, badgesRes] = await Promise.all([
      api.get('/user/stats'),
      api.get('/badges'),
    ])
    stats.value = statsRes.data
    allBadges.value = badgesRes.data
  } catch (error) {
    console.error('Lỗi khi tải thông tin thành tích:', error)
  }
})
</script>