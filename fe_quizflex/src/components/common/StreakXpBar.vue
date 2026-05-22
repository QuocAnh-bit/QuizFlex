<template>
  <div class="flex items-center gap-3">
    <!-- Streak -->
    <div class="flex items-center gap-1.5 rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1.5 text-sm font-bold">
      🔥 <span class="text-orange-400">{{ stats.current_streak }} ngày</span>
    </div>

    <!-- XP & Level -->
    <div class="flex items-center gap-2 rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1.5 text-sm font-bold">
      <span class="text-[var(--primary)]">Lv.{{ stats.level }}</span>
      <div class="h-2 w-20 overflow-hidden rounded-full bg-[var(--border)]">
        <div
          class="h-full rounded-full bg-gradient-to-r from-[var(--primary)] to-[var(--accent)] transition-all duration-500"
          :style="{ width: xpPercent + '%' }"
        ></div>
      </div>
      <span class="text-[var(--muted)]">{{ xpInLevel }}/100</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const stats = ref({
  xp: 0,
  level: 1,
  current_streak: 0,
  xp_to_next_level: 100,
})

const xpInLevel = computed(() => stats.value.xp % 100)
const xpPercent = computed(() => xpInLevel.value)

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/user/stats')
    stats.value = data
  } catch (e) {
    // user belum login, tampilkan default
  }
})
</script>