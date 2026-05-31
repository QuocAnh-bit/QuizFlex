<template>
  <div class="app-container py-10">
    <h1 class="mb-6 text-2xl font-black">🏆 Bảng xếp hạng</h1>

    <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] overflow-hidden">
      <div
        v-for="(user, i) in leaderboard"
        :key="user.rank"
        :class="[
          'flex items-center gap-4 px-5 py-4 border-b border-[var(--border)] last:border-0 transition',
          user.is_me ? 'bg-[var(--primary)]/10' : 'hover:bg-[var(--surface-soft)]',
        ]"
      >
        <!-- Rank -->
        <div class="w-8 text-center font-bold text-lg">
          <span v-if="i === 0">🥇</span>
          <span v-else-if="i === 1">🥈</span>
          <span v-else-if="i === 2">🥉</span>
          <span v-else class="text-sm text-[var(--muted)]">#{{ user.rank }}</span>
        </div>

        <!-- Avatar -->
        <div
          :class="[
            'flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold',
            user.is_me
              ? 'bg-[var(--primary)] text-white'
              : 'bg-[var(--surface-soft)] text-[var(--text)]',
          ]"
        >
          {{ user.name.charAt(0) }}
        </div>

        <!-- Name & Level -->
        <div class="flex-1">
          <div class="font-bold text-sm">
            {{ user.name }}
            <span v-if="user.is_me" class="ml-1 text-xs text-[var(--primary)]">(bạn)</span>
          </div>
          <div class="text-xs text-[var(--muted)]">Level {{ user.level }}</div>
        </div>

        <!-- XP -->
        <div class="font-black text-[var(--primary)]">{{ user.xp }} XP</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const leaderboard = ref([])

onMounted(async () => {
  const { data } = await axios.get('/api/leaderboard')
  leaderboard.value = data
})
</script>