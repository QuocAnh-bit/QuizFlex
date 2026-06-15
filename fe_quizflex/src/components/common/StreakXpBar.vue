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
    // reset về mặc định nếu chưa đăng nhập hoặc lỗi
    stats.value = {
      xp: 0,
      level: 1,
      current_streak: 0,
      xp_to_next_level: 100,
    }
  }
}

// Watch thay đổi đường dẫn (đặc biệt khi vừa làm xong quiz và chuyển trang)
watch(() => route.path, () => {
  fetchStats()
})

// Đồng bộ khi đăng nhập/đăng xuất/cập nhật user
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