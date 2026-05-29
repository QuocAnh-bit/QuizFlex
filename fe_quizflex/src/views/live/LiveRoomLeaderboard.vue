<template>
  <section class="grid gap-6 py-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <router-link class="btn-ghost" to="/live-rooms">Live Room</router-link>
      <button class="btn-ghost" type="button" :disabled="isLoading" @click="loadLeaderboard">
        {{ isLoading ? 'Đang tải...' : 'Tải lại' }}
      </button>
    </div>

    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)]">
      <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Leaderboard</p>
      <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Bảng xếp hạng live room</h1>
      <p class="mt-3 text-sm leading-7 text-[var(--muted)]">Chỉ xếp hạng người chơi đã join live room. Host không nằm trong bảng này.</p>
    </article>

    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
      <div v-if="leaderboard.length" class="overflow-hidden rounded-[1.5rem] border border-[var(--border)]">
        <div class="hidden grid-cols-[80px_minmax(220px,1.4fr)_120px_130px_150px_150px_160px] gap-3 border-b border-[var(--border)] bg-[var(--surface-soft)] px-4 py-3 text-xs font-black uppercase tracking-[0.16em] text-[var(--muted)] lg:grid">
          <span>Rank</span>
          <span>Người chơi</span>
          <span>Điểm</span>
          <span>Số câu đúng</span>
          <span>Đã trả lời</span>
          <span>Trạng thái</span>
          <span>Hoàn thành</span>
        </div>

        <article
          v-for="entry in leaderboard"
          :key="entry.user_id"
          class="grid gap-3 border-b border-[var(--border)] px-4 py-4 last:border-b-0 lg:grid-cols-[80px_minmax(220px,1.4fr)_120px_130px_150px_150px_160px] lg:items-center"
        >
          <b class="text-2xl font-black text-[var(--primary)]">#{{ entry.rank }}</b>
          <div>
            <h3 class="font-black text-[var(--text)]">{{ entry.user?.name || `User #${entry.user_id}` }}</h3>
            <p class="mt-1 text-xs font-bold text-[var(--muted)]">{{ entry.user?.email || 'Chưa có email' }}</p>
          </div>
          <p class="text-sm font-black text-[var(--text)]">{{ entry.score }}</p>
          <p class="text-sm font-black text-[var(--text)]">{{ entry.correct_count }}</p>
          <p class="text-sm font-black text-[var(--text)]">{{ entry.answered_count }}/{{ entry.total_questions }}</p>
          <span class="w-fit rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--primary)]">{{ entry.is_finished ? 'Hoàn thành' : 'Đang chơi' }}</span>
          <p class="text-sm font-bold text-[var(--muted)]">{{ formatDateTime(entry.finished_at) }}</p>
        </article>
      </div>

      <div v-else class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-10 text-center">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Empty</p>
        <h2 class="mt-2 text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Chưa có người chơi</h2>
        <p class="mt-3 text-sm leading-7 text-[var(--muted)]">Leaderboard sẽ xuất hiện khi có player join live room.</p>
      </div>
    </article>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { getEcho } from '@/echo'
import { liveRoomApi } from '@/services/api'

const route = useRoute()
const liveRoomId = computed(() => route.params.liveRoomId)
const leaderboard = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
let pollTimer = null
let liveChannel = null

const formatDateTime = (value) => {
  if (!value) return '-'
  return new Date(value).toLocaleString('vi-VN')
}

const loadLeaderboard = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    leaderboard.value = await liveRoomApi.getLiveLeaderboard(liveRoomId.value)
  } catch (error) {
    errorMessage.value = error.message || 'Không tải được leaderboard.'
  } finally {
    isLoading.value = false
  }
}

const realtimeLog = (eventName, event) => {
  console.log('[realtime]', eventName, new Date().toISOString(), event)
}

const applyLeaderboardPayload = (eventName, event) => {
  realtimeLog(eventName, event)
  if (Array.isArray(event?.leaderboard)) {
    leaderboard.value = event.leaderboard
    errorMessage.value = ''
    return
  }

  loadLeaderboard()
}

const subscribeToRealtime = () => {
  try {
    liveChannel = getEcho().private(`live-room.${liveRoomId.value}`)
    liveChannel
      .listen('.live.leaderboard.updated', (event) => applyLeaderboardPayload('live.leaderboard.updated', event))
      .listen('.live.room.finished', (event) => applyLeaderboardPayload('live.room.finished', event))
  } catch {
    liveChannel = null
  }
}

const leaveRealtime = () => {
  if (!liveChannel) return

  getEcho().leave(`live-room.${liveRoomId.value}`)
  liveChannel = null
}

onMounted(async () => {
  subscribeToRealtime()
  await loadLeaderboard()
  pollTimer = setInterval(loadLeaderboard, 15000)
})

onBeforeUnmount(() => {
  clearInterval(pollTimer)
  leaveRealtime()
})
</script>
