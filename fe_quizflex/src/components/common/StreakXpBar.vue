<template>
  <div class="flex items-center gap-2">
    <!-- Streak -->
    <div class="flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800 shadow-sm" title="Chuỗi học tập liên tục">
      <span>🔥</span>
      <span>{{ stats.current_streak }} ngày</span>
    </div>

    <!-- XP & Level -->
    <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold shadow-sm">
      <span class="font-extrabold text-[#7C3AED]">Lv.{{ stats.level }}</span>
      <div class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-100 border border-slate-200">
        <div
          class="h-full rounded-full bg-[#7C3AED] transition-all duration-500"
          :style="{ width: xpPercent + '%' }"
        ></div>
      </div>
      <span class="text-slate-500 text-[11px]">{{ xpInLevel }}/100</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute } from 'vue-router'
import api, { tokenStorage } from '@/services/api'

const stats = ref({
  xp: 0,
  level: 1,
  current_streak: 0,
  xp_to_next_level: 100,
})

const xpInLevel = computed(() => stats.value.xp % 100)
const xpPercent = computed(() => xpInLevel.value)
const route = useRoute()

const fetchStats = async () => {
  if (!tokenStorage.get()) {
    stats.value = {
      xp: 0,
      level: 1,
      current_streak: 0,
      xp_to_next_level: 100,
    }
    return
  }

  try {
    const { data } = await api.get('/user/stats')
    stats.value = data
  } catch (e) {
    stats.value = {
      xp: 0,
      level: 1,
      current_streak: 0,
      xp_to_next_level: 100,
    }
  }
}

watch(() => route.path, () => {
  fetchStats()
})

const handleUserUpdate = () => {
  fetchStats()
}

onMounted(() => {
  fetchStats()
  window.addEventListener('quizflex-user-updated', handleUserUpdate)
})

onBeforeUnmount(() => {
  window.removeEventListener('quizflex-user-updated', handleUserUpdate)
})
</script>