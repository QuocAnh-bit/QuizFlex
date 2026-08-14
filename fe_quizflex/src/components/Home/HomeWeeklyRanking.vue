<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_4px_16px_rgba(15,23,42,0.06)] space-y-3.5">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
      <div class="flex items-center gap-2">
        <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-50 text-xs text-amber-600">🏆</span>
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Bảng xếp hạng (Tuần)</h3>
      </div>
      <router-link
        to="/leaderboard"
        class="text-xs font-bold text-[#7C3AED] hover:underline"
      >
        Xem tất cả →
      </router-link>
    </div>

    <!-- Content: Loading State -->
    <div v-if="isLoading" class="py-4 text-center text-xs text-slate-400 space-y-1">
      <span class="inline-block animate-spin">⏳</span>
      <p>Đang tải bảng xếp hạng...</p>
    </div>

    <!-- Content: Empty State -->
    <div v-else-if="topUsers.length === 0" class="py-4 text-center text-xs text-slate-400 space-y-1">
      <span class="text-xl block">🎖️</span>
      <p>Chưa có dữ liệu xếp hạng tuần này</p>
    </div>

    <!-- Content: Top Users List -->
    <div v-else class="space-y-1.5">
      <div
        v-for="(user, i) in topUsers"
        :key="user.user_id || i"
        class="flex items-center justify-between rounded-xl px-2.5 py-2 text-xs transition"
        :class="user.is_me ? 'bg-[#F5F3FF]  font-bold shadow-xs' : 'hover:bg-slate-50'"
      >
        <!-- Left: Rank & User Info -->
        <div class="flex items-center gap-2.5 truncate">
          <!-- Rank Icon/Number -->
          <span class="w-5 text-center font-black text-sm shrink-0">
            <template v-if="i === 0">🥇</template>
            <template v-else-if="i === 1">🥈</template>
            <template v-else-if="i === 2">🥉</template>
            <span v-else class="text-slate-400 text-xs font-bold">#{{ i + 1 }}</span>
          </span>

          <!-- Avatar / Initials -->
          <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-purple-100 text-[11px] font-bold text-[#7C3AED]">
            {{ (user.name || '?').charAt(0).toUpperCase() }}
          </div>

          <!-- Name & Level -->
          <div class="truncate">
            <p class="truncate font-bold text-slate-900 leading-tight">
              {{ user.name }}
              <span v-if="user.is_me" class="ml-1 text-[10px] font-bold text-[#7C3AED]">(Bạn)</span>
            </p>
            <p class="text-[10px] font-normal text-slate-400">Lv.{{ user.level || 1 }}</p>
          </div>
        </div>

        <!-- Right: XP -->
        <div class="shrink-0 text-right font-black text-[#7C3AED] text-xs">
          {{ user.xp }} <span class="text-[10px] font-semibold text-slate-400">XP</span>
        </div>
      </div>

      <!-- Current User Pinned Rank (if not in top displayed users) -->
      <div
        v-if="myRankNotInTop"
        class="mt-2.5 pt-2 border-t border-slate-100"
      >
        <div class="flex items-center justify-between rounded-xl bg-[#F5F3FF] border-l-[3px] border-l-[#7C3AED] px-2.5 py-2 text-xs font-bold">
          <div class="flex items-center gap-2.5 truncate">
            <span class="w-5 text-center font-bold text-[#7C3AED] text-xs shrink-0">
              #{{ myRankNotInTop.rank || '?' }}
            </span>
            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-purple-200 text-[11px] font-bold text-[#7C3AED]">
              {{ (myRankNotInTop.name || 'B').charAt(0).toUpperCase() }}
            </div>
            <div class="truncate">
              <p class="truncate text-slate-900 leading-tight">
                {{ myRankNotInTop.name }} <span class="text-[10px] font-bold text-[#7C3AED]">(Bạn)</span>
              </p>
              <p class="text-[10px] font-normal text-slate-500">Lv.{{ myRankNotInTop.level || 1 }}</p>
            </div>
          </div>
          <div class="shrink-0 text-right font-black text-[#7C3AED] text-xs">
            {{ myRankNotInTop.xp }} <span class="text-[10px] font-semibold text-slate-500">XP</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { gamificationApi, currentUserStorage } from '@/services/api'

const rawLeaderboard = ref([])
const isLoading = ref(true)
const currentUser = ref(currentUserStorage.get())

const leaderboardWithMe = computed(() => {
  const myId = currentUser.value?.id
  return rawLeaderboard.value.map((item, index) => ({
    ...item,
    rank: item.rank || index + 1,
    is_me: myId && Number(item.user_id) === Number(myId),
  }))
})

const topUsers = computed(() => leaderboardWithMe.value.slice(0, 4))

const myRankNotInTop = computed(() => {
  if (!currentUser.value) return null
  const myEntry = leaderboardWithMe.value.find((item) => item.is_me)
  if (!myEntry) return null
  const isInsideTop = topUsers.value.some((item) => item.is_me)
  return isInsideTop ? null : myEntry
})

const loadLeaderboard = async () => {
  try {
    isLoading.value = true
    const data = await gamificationApi.getLeaderboard()
    rawLeaderboard.value = Array.isArray(data) ? data : []
  } catch (error) {
    console.warn('Failed to load home leaderboard:', error)
    rawLeaderboard.value = []
  } finally {
    isLoading.value = false
  }
}

const syncUser = (event) => {
  currentUser.value = event?.detail ?? currentUserStorage.get()
}

onMounted(() => {
  loadLeaderboard()
  window.addEventListener('quizflex-user-updated', syncUser)
})

onBeforeUnmount(() => {
  window.removeEventListener('quizflex-user-updated', syncUser)
})
</script>
