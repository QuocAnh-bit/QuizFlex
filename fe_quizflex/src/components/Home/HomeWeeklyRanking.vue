<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_4px_16px_rgba(15,23,42,0.05)]"
  >
    <!-- Header -->
    <div
      class="flex items-center justify-between border-b border-slate-100 pb-4"
    >
      <div class="flex items-center gap-2.5">
        <!-- Trophy -->
        <span
          class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#FFFBEB] text-[#F59E0B]"
        >
          <svg
            class="h-4 w-4"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M8 4h8v5a4 4 0 0 1-8 0V4Z" />
            <path d="M8 6H5a2 2 0 0 0 2 4" />
            <path d="M16 6h3a2 2 0 0 1-2 4" />
            <path d="M12 13v4" />
            <path d="M9 21h6" />
            <path d="M10 17h4" />
          </svg>
        </span>

        <h3 class="text-sm font-bold tracking-wide text-[#334155]">
          BẢNG XẾP HẠNG
        </h3>
      </div>

      <router-link
        to="/leaderboard"
        class="text-sm font-bold text-[#7C3AED] hover:underline"
      >
        Xem tất cả →
      </router-link>
    </div>

    <!-- Loading -->
    <div
      v-if="isLoading"
      class="py-5 text-center text-xs text-slate-400"
    >
      <span
        class="mx-auto mb-2 block h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-[#7C3AED]"
      ></span>

      <p>Đang tải bảng xếp hạng...</p>
    </div>

    <!-- Empty -->
    <div
      v-else-if="topUsers.length === 0"
      class="py-5 text-center text-xs text-slate-400"
    >
      <div
        class="mx-auto mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-50"
      >
        <svg
          class="h-5 w-5"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.8"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M8 4h8v5a4 4 0 0 1-8 0V4Z" />
          <path d="M12 13v4" />
          <path d="M9 21h6" />
        </svg>
      </div>

      <p>Chưa có dữ liệu xếp hạng tuần này</p>
    </div>

    <!-- Users -->
    <div
      v-else
      class="mt-3 space-y-1"
    >
      <div
        v-for="(user, i) in topUsers"
        :key="user.user_id || i"
        class="flex items-center justify-between rounded-xl px-2.5 py-2.5 transition"
        :class="
          user.is_me
            ? 'bg-[#F5F3FF]'
            : 'hover:bg-slate-50'
        "
      >
        <!-- Left -->
        <div class="flex min-w-0 items-center gap-3">
          <!-- Rank -->
          <div
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-black"
            :class="getRankClass(i)"
          >
            {{ i + 1 }}
          </div>

          <!-- Avatar -->
          <div
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-purple-100 text-xs font-bold text-[#7C3AED]"
          >
            {{ (user.name || '?').charAt(0).toUpperCase() }}
          </div>

          <!-- User -->
          <div class="min-w-0">
            <p
              class="truncate text-sm font-bold leading-tight text-[#0F172A]"
            >
              {{ user.name }}

              <span
                v-if="user.is_me"
                class="ml-1 text-[10px] font-bold text-[#7C3AED]"
              >
                (Bạn)
              </span>
            </p>

            <p
              class="mt-0.5 text-[10px] font-medium text-slate-400"
            >
              Lv.{{ user.level || 1 }}
            </p>
          </div>
        </div>

        <!-- XP -->
        <div
          class="shrink-0 text-right text-sm font-black text-[#7C3AED]"
        >
          {{ user.xp }}

          <span
            class="text-[10px] font-semibold text-slate-400"
          >
            XP
          </span>
        </div>
      </div>

      <!-- Current user -->
      <div
        v-if="myRankNotInTop"
        class="mt-2 border-t border-slate-100 pt-2"
      >
        <div
          class="flex items-center justify-between rounded-xl bg-[#F5F3FF] px-2.5 py-2.5"
        >
          <div
            class="flex min-w-0 items-center gap-3"
          >
            <div
              class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#EDE9FE] text-xs font-black text-[#7C3AED]"
            >
              {{ myRankNotInTop.rank || '?' }}
            </div>

            <div
              class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-purple-100 text-xs font-bold text-[#7C3AED]"
            >
              {{
                (myRankNotInTop.name || 'B')
                  .charAt(0)
                  .toUpperCase()
              }}
            </div>

            <div class="min-w-0">
              <p
                class="truncate text-sm font-bold leading-tight text-[#0F172A]"
              >
                {{ myRankNotInTop.name }}

                <span
                  class="ml-1 text-[10px] font-bold text-[#7C3AED]"
                >
                  (Bạn)
                </span>
              </p>

              <p
                class="mt-0.5 text-[10px] text-slate-400"
              >
                Lv.{{ myRankNotInTop.level || 1 }}
              </p>
            </div>
          </div>

          <div
            class="shrink-0 text-right text-sm font-black text-[#7C3AED]"
          >
            {{ myRankNotInTop.xp }}

            <span
              class="text-[10px] font-semibold text-slate-400"
            >
              XP
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  ref,
  computed,
  onMounted,
  onBeforeUnmount,
} from 'vue'

import {
  gamificationApi,
  currentUserStorage,
} from '@/services/api'

const rawLeaderboard = ref([])
const isLoading = ref(true)
const currentUser = ref(currentUserStorage.get())

const leaderboardWithMe = computed(() => {
  const myId = currentUser.value?.id

  return rawLeaderboard.value.map((item, index) => ({
    ...item,
    rank: item.rank || index + 1,
    is_me:
      myId &&
      Number(item.user_id) === Number(myId),
  }))
})

const topUsers = computed(() =>
  leaderboardWithMe.value.slice(0, 4)
)

const myRankNotInTop = computed(() => {
  if (!currentUser.value) return null

  const myEntry =
    leaderboardWithMe.value.find(
      (item) => item.is_me
    )

  if (!myEntry) return null

  const isInsideTop =
    topUsers.value.some(
      (item) => item.is_me
    )

  return isInsideTop ? null : myEntry
})

const getRankClass = (index) => {
  if (index === 0) {
    return 'bg-[#FEF3C7] text-[#D97706]'
  }

  if (index === 1) {
    return 'bg-[#F1F5F9] text-[#64748B]'
  }

  if (index === 2) {
    return 'bg-[#FFEDD5] text-[#EA580C]'
  }

  return 'bg-slate-50 text-slate-400'
}

const loadLeaderboard = async () => {
  try {
    isLoading.value = true

    const data =
      await gamificationApi.getLeaderboard()

    rawLeaderboard.value =
      Array.isArray(data) ? data : []
  } catch (error) {
    console.warn(
      'Failed to load home leaderboard:',
      error
    )

    rawLeaderboard.value = []
  } finally {
    isLoading.value = false
  }
}

const syncUser = (event) => {
  currentUser.value =
    event?.detail ?? currentUserStorage.get()
}

onMounted(() => {
  loadLeaderboard()

  window.addEventListener(
    'quizflex-user-updated',
    syncUser
  )
})

onBeforeUnmount(() => {
  window.removeEventListener(
    'quizflex-user-updated',
    syncUser
  )
})
</script>