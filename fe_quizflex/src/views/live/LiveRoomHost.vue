<template>
  <section class="grid gap-6 py-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <router-link class="btn-ghost" to="/live-rooms">Live Room</router-link>
      <router-link class="btn-ghost" :to="`/live-rooms/${liveRoomId}/leaderboard`">Leaderboard</router-link>
    </div>

    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)]">
      <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Host Monitor</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ liveRoom.title || 'Live Room' }}</h1>
          <p class="mt-3 text-sm font-bold text-[var(--muted)]">Host chỉ theo dõi phòng, không làm bài.</p>
        </div>
        <div class="grid gap-2 text-right">
          <span class="rounded-full bg-[var(--chip-active)] px-4 py-2 text-lg font-black tracking-[0.12em] text-[var(--primary)]">{{ liveRoom.code || '-' }}</span>
          <StatusBadge :value="roomStatus" />
        </div>
      </div>

      <div class="mt-6 grid gap-3 md:grid-cols-4">
        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
          <p class="text-xs font-bold text-[var(--muted)]">Người chơi</p>
          <p class="mt-1 text-2xl font-black text-[var(--text)]">{{ monitor.total_players ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
          <p class="text-xs font-bold text-[var(--muted)]">Đã hoàn thành</p>
          <p class="mt-1 text-2xl font-black text-[var(--text)]">{{ monitor.total_finished_players ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
          <p class="text-xs font-bold text-[var(--muted)]">Tổng câu</p>
          <p class="mt-1 text-2xl font-black text-[var(--text)]">{{ monitor.total_questions ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
          <p class="text-xs font-bold text-[var(--muted)]">Quiz</p>
          <p class="mt-1 text-sm font-black text-[var(--text)]">{{ liveRoom.quiz?.title || '-' }}</p>
        </div>
      </div>

      <div class="mt-6 flex flex-wrap gap-3">
        <button v-if="hasLoadedRoom && roomStatus === 'waiting'" class="btn-primary" type="button" :disabled="isActionLoading" @click="startLive">
          {{ isActionLoading ? 'Đang start...' : 'Start Live' }}
        </button>
        <button v-if="hasLoadedRoom && ['waiting', 'playing'].includes(roomStatus)" class="btn-ghost" type="button" :disabled="isActionLoading" @click="finishLive">
          {{ isActionLoading ? 'Đang xử lý...' : 'Finish Live' }}
        </button>
      </div>
    </article>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <div class="flex items-center justify-between gap-3">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Progress</p>
            <h2 class="mt-1 text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Tiến độ người chơi</h2>
          </div>
          <span class="text-xs font-bold text-[var(--muted)]">Polling 10s</span>
        </div>

        <div v-if="playersProgress.length" class="mt-5 grid gap-3">
          <article v-for="player in playersProgress" :key="player.user_id" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h3 class="font-black text-[var(--text)]">{{ player.user?.name || `User #${player.user_id}` }}</h3>
                <p class="mt-1 text-xs font-bold text-[var(--muted)]">{{ player.user?.email || 'Chưa có email' }}</p>
              </div>
              <span class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--primary)]">{{ player.score }} điểm</span>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-[var(--surface)]">
              <div class="h-full rounded-full bg-[var(--primary)]" :style="{ width: `${progressPercent(player)}%` }"></div>
            </div>
            <p class="mt-2 text-xs font-bold text-[var(--muted)]">{{ player.answered_count ?? player.current_question_index }}/{{ player.total_questions ?? monitor.total_questions ?? 0 }} câu, đúng {{ player.correct_count }}</p>
          </article>
        </div>

        <div v-else class="mt-5 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-8 text-center text-sm font-bold text-[var(--muted)]">Chưa có người chơi tham gia.</div>
      </article>

      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Leaderboard</p>
        <h2 class="mt-1 text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Bảng xếp hạng</h2>

        <div v-if="leaderboard.length" class="mt-5 grid gap-3">
          <div v-for="entry in leaderboard" :key="entry.user_id" class="flex items-center justify-between gap-3 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <div>
              <p class="font-black text-[var(--text)]">#{{ entry.rank }} {{ entry.user?.name || `User #${entry.user_id}` }}</p>
              <p class="mt-1 text-xs font-bold text-[var(--muted)]">Đúng {{ entry.correct_count }} / {{ entry.total_questions }}</p>
            </div>
            <b class="text-xl font-black text-[var(--primary)]">{{ entry.score }}</b>
          </div>
        </div>
        <div v-else class="mt-5 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-8 text-center text-sm font-bold text-[var(--muted)]">Chưa có điểm.</div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { getEcho } from '@/echo'
import { liveRoomApi } from '@/services/api'

const route = useRoute()
const liveRoomId = computed(() => route.params.liveRoomId)
const monitor = ref({})
const liveRoom = ref({})
const isActionLoading = ref(false)
const errorMessage = ref('')
const lastRealtimeAt = ref(0)
let pollTimer = null
let liveChannel = null
const realtimeFreshMs = 8000

const hasLoadedRoom = computed(() => Boolean(liveRoom.value.id))
const roomStatus = computed(() => monitor.value.room_status || liveRoom.value.status || 'loading')
const playersProgress = computed(() => monitor.value.players_progress || [])
const leaderboard = computed(() => monitor.value.leaderboard || [])

const progressPercent = (player) => {
  const total = Number(player.total_questions ?? monitor.value.total_questions ?? 0)
  if (!total) return 0
  return Math.min(100, Math.round((Number(player.answered_count ?? player.current_question_index ?? 0) / total) * 100))
}

const markRealtime = () => {
  lastRealtimeAt.value = Date.now()
}

const hasRecentRealtime = () => Date.now() - lastRealtimeAt.value < realtimeFreshMs

const loadMonitor = async (force = false) => {
  if (!force && hasRecentRealtime()) return

  try {
    const data = await liveRoomApi.getLiveCurrentQuestion(liveRoomId.value)
    monitor.value = data
    liveRoom.value = data.live_room || liveRoom.value
    errorMessage.value = ''
  } catch (error) {
    errorMessage.value = error.message || 'Không tải được monitor live room.'
  }
}

const realtimeLog = (eventName, event) => {
  console.log('[realtime]', eventName, new Date().toISOString(), event)
}

const upsertPlayerProgress = (player) => {
  if (!player?.user_id) return false

  const players = [...(monitor.value.players_progress || [])]
  const index = players.findIndex((item) => Number(item.user_id) === Number(player.user_id))
  if (index >= 0) {
    players[index] = { ...players[index], ...player }
  } else {
    players.push(player)
  }

  monitor.value = {
    ...monitor.value,
    players_progress: players,
    total_players: players.length,
    total_finished_players: players.filter((item) => item.is_finished || item.player_finished || item.finished_at).length,
  }

  return true
}

const applyLeaderboard = (leaderboardPayload) => {
  if (!Array.isArray(leaderboardPayload)) return false

  monitor.value = {
    ...monitor.value,
    leaderboard: leaderboardPayload,
  }

  return true
}

const handlePlayerJoined = (event) => {
  realtimeLog('live.player.joined', event)
  markRealtime()
  if (Array.isArray(event?.players_progress)) {
    monitor.value = {
      ...monitor.value,
      players_progress: event.players_progress,
      total_players: event.player_count ?? event.players_progress.length,
      total_finished_players: event.players_progress.filter((item) => item.is_finished || item.player_finished || item.finished_at).length,
      leaderboard: Array.isArray(event?.leaderboard) ? event.leaderboard : monitor.value.leaderboard,
    }
    return
  }

  if (!upsertPlayerProgress(event?.player)) {
    loadMonitor(true)
  }
  applyLeaderboard(event?.leaderboard)
}

const handleAnswerSubmitted = (event) => {
  realtimeLog('live.answer.submitted', event)
  markRealtime()
  if (!upsertPlayerProgress(event?.player)) {
    loadMonitor(true)
  }
  applyLeaderboard(event?.leaderboard)
}

const handleLeaderboardUpdated = (event) => {
  realtimeLog('live.leaderboard.updated', event)
  markRealtime()
  if (!applyLeaderboard(event?.leaderboard)) {
    loadMonitor(true)
  }
}

const handleRoomStarted = (event) => {
  realtimeLog('live.room.started', event)
  markRealtime()
  const playersProgressPayload = Array.isArray(event?.players_progress) ? event.players_progress : monitor.value.players_progress
  const leaderboardPayload = Array.isArray(event?.leaderboard) ? event.leaderboard : monitor.value.leaderboard

  monitor.value = {
    ...monitor.value,
    room_status: event?.status || 'playing',
    total_questions: event?.total_questions ?? monitor.value.total_questions,
    players_progress: playersProgressPayload,
    leaderboard: leaderboardPayload,
    total_players: Array.isArray(playersProgressPayload) ? playersProgressPayload.length : monitor.value.total_players,
    total_finished_players: Array.isArray(playersProgressPayload)
      ? playersProgressPayload.filter((item) => item.is_finished || item.player_finished || item.finished_at).length
      : monitor.value.total_finished_players,
  }
  liveRoom.value = {
    ...liveRoom.value,
    status: event?.status || 'playing',
    started_at: event?.started_at || liveRoom.value.started_at,
  }
}

const handleRoomFinished = (event) => {
  realtimeLog('live.room.finished', event)
  markRealtime()
  const playersProgressPayload = Array.isArray(event?.players_progress) ? event.players_progress : monitor.value.players_progress

  monitor.value = {
    ...monitor.value,
    room_status: event?.status || 'finished',
    leaderboard: Array.isArray(event?.leaderboard) ? event.leaderboard : monitor.value.leaderboard,
    players_progress: playersProgressPayload,
    total_players: event?.total_players ?? (Array.isArray(playersProgressPayload) ? playersProgressPayload.length : monitor.value.total_players),
    total_finished_players: event?.total_finished_players ?? (Array.isArray(playersProgressPayload)
      ? playersProgressPayload.filter((item) => item.is_finished || item.player_finished || item.finished_at).length
      : monitor.value.total_finished_players),
  }
  liveRoom.value = {
    ...liveRoom.value,
    status: event?.status || 'finished',
    ended_at: event?.ended_at || liveRoom.value.ended_at,
  }

  if (!Array.isArray(event?.leaderboard)) {
    loadMonitor(true)
  }
}

const subscribeToRealtime = () => {
  try {
    liveChannel = getEcho().private(`live-room.${liveRoomId.value}`)
    liveChannel
      .listen('.live.player.joined', handlePlayerJoined)
      .listen('.live.room.started', handleRoomStarted)
      .listen('.live.answer.submitted', handleAnswerSubmitted)
      .listen('.live.leaderboard.updated', handleLeaderboardUpdated)
      .listen('.live.room.finished', handleRoomFinished)
  } catch {
    liveChannel = null
  }
}

const leaveRealtime = () => {
  if (!liveChannel) return

  getEcho().leave(`live-room.${liveRoomId.value}`)
  liveChannel = null
}

const startLive = async () => {
  isActionLoading.value = true
  errorMessage.value = ''
  try {
    const data = await liveRoomApi.startLiveRoom(liveRoomId.value)
    liveRoom.value = data.live_room || liveRoom.value
    monitor.value = data.monitor || monitor.value
  } catch (error) {
    errorMessage.value = error.message || 'Không start được live room.'
  } finally {
    isActionLoading.value = false
  }
}

const finishLive = async () => {
  if (!window.confirm('Bạn có chắc muốn kết thúc live room không?')) return

  isActionLoading.value = true
  errorMessage.value = ''
  try {
    const data = await liveRoomApi.finishLiveRoom(liveRoomId.value)
    liveRoom.value = data.live_room || liveRoom.value
    monitor.value = {
      ...monitor.value,
      room_status: data.live_room?.status || 'finished',
      live_room: data.live_room || liveRoom.value,
      leaderboard: data.leaderboard || monitor.value.leaderboard || [],
    }
  } catch (error) {
    errorMessage.value = error.message || 'Không finish được live room.'
  } finally {
    isActionLoading.value = false
  }
}

onMounted(async () => {
  subscribeToRealtime()
  await loadMonitor(true)
  pollTimer = setInterval(loadMonitor, 10000)
})

onBeforeUnmount(() => {
  clearInterval(pollTimer)
  leaveRealtime()
})
</script>
