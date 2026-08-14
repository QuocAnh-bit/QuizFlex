<template>
  <section class="grid gap-6 py-8">
    <!-- Modal Cảnh báo Đăng nhập đa Tab / Thiết bị cho Host -->
    <transition name="fade">
      <div v-if="isDuplicateTab" class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-md">
        <div class="w-full max-w-md rounded-[2rem] border border-rose-500/40 bg-[var(--surface)] p-6 shadow-2xl text-center">
          <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-rose-500/10 text-3xl text-rose-400">
            ⚠️
          </div>
          <h3 class="mt-4 text-xl font-black tracking-tight text-[var(--text)]">Phiên điều khiển bị gián đoạn</h3>
          <p class="mt-2 text-sm leading-relaxed text-[var(--muted)]">
            Tài khoản quản lý của bạn đang được mở trong một trình duyệt hoặc thiết bị khác. Bạn đã bị đưa ra khỏi phiên điều khiển này.
          </p>
          <div class="mt-6">
            <button type="button" class="btn-primary w-full text-center" @click="confirmAndLeave">
              Xác nhận và rời phòng
            </button>
          </div>
        </div>
      </div>
    </transition>

    <div class="flex flex-wrap items-center justify-between gap-3">
      <router-link class="btn-ghost" to="/live-rooms">Phòng thi đấu</router-link>
      <router-link class="btn-ghost" :to="`/live-rooms/${liveRoomId}/leaderboard`">Leaderboard</router-link>
    </div>

    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

    <div v-else class="grid gap-6">
      <!-- Banner Banned -->
      <div v-if="liveRoom.status === 'banned'" class="rounded-[2rem] border border-amber-500/30 bg-amber-500/10 p-5 text-sm font-bold text-amber-300">
        Phòng này đã bị quản trị viên khóa. Bạn chỉ có thể xem thông tin phòng và không thể thực hiện bất kỳ thao tác quản lý nào.
      </div>

      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)]">
      <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Điều khiển chủ phòng</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ liveRoom.title || 'Phòng thi đấu' }}</h1>
          <p class="mt-3 text-sm font-bold text-[var(--muted)]">Chủ phòng chỉ theo dõi phòng, không làm bài.</p>
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

      <div v-if="liveRoom.status !== 'banned'" class="mt-6 flex flex-wrap gap-3">
        <button v-if="hasLoadedRoom && roomStatus === 'waiting'" class="btn-primary" type="button" :disabled="isActionLoading || isDuplicateTab" @click="startLive">
          {{ isActionLoading ? 'Đang start...' : 'Start Live' }}
        </button>
        <button v-if="hasLoadedRoom && ['waiting', 'playing'].includes(roomStatus)" class="btn-ghost" type="button" :disabled="isActionLoading || isDuplicateTab" @click="finishLive">
          {{ isActionLoading ? 'Đang xử lý...' : 'Finish Live' }}
        </button>
      </div>
    </article>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
      <!-- Cột tiến độ người chơi (Bên trái) -->
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <div class="flex items-center justify-between gap-3">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Progress</p>
            <h2 class="mt-1 text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Tiến độ người chơi</h2>
          </div>
          <span class="rounded-full bg-violet-500/10 px-3 py-1 text-xs font-bold text-violet-400">Realtime</span>
        </div>

        <div v-if="sortedPlayersProgress.length" class="mt-5 overflow-x-auto max-h-[600px] overflow-y-auto rounded-2xl border border-[var(--border)]">
          <table class="w-full text-left border-collapse">
            <thead class="sticky top-0 bg-[var(--surface-strong)] z-20 border-b border-[var(--border)]">
              <tr class="text-xs font-black uppercase tracking-[0.12em] text-[var(--muted)]">
                <th class="px-4 py-3 text-center">#</th>
                <th class="px-4 py-3">Người chơi</th>
                <th class="px-4 py-3 d-none d-md-table-cell">Email</th>
                <th class="px-4 py-3 text-center">Điểm</th>
                <th class="px-4 py-3 text-center">Đã trả lời</th>
                <th class="px-4 py-3 text-center">Đúng</th>
                <th class="px-4 py-3">Tiến độ</th>
                <th class="px-4 py-3 text-center">Trạng thái</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(player, idx) in sortedPlayersProgress" :key="player.user_id"
                  class="border-b border-[var(--border)] last:border-b-0 transition duration-150 hover:bg-[var(--surface-soft)]"
                  :class="[
                    idx === 0 ? 'bg-yellow-500/5 hover:bg-yellow-500/10 border-l-4 border-l-yellow-500' :
                    idx === 1 ? 'bg-slate-300/5 hover:bg-slate-300/10 border-l-4 border-l-slate-400' :
                    idx === 2 ? 'bg-amber-600/5 hover:bg-amber-700/10 border-l-4 border-l-amber-600' : ''
                  ]">
                <td class="px-4 py-3 text-center font-black" 
                    :class="[
                      idx === 0 ? 'text-yellow-400' :
                      idx === 1 ? 'text-slate-300' :
                      idx === 2 ? 'text-amber-500' : 'text-[var(--muted)]'
                    ]">
                  {{ idx + 1 }}
                </td>
                <td class="px-4 py-3 font-bold text-[var(--text)] whitespace-nowrap">
                  {{ player.user?.name || `User #${player.user_id}` }}
                </td>
                <td class="px-4 py-3 text-xs text-[var(--muted)] whitespace-nowrap d-none d-md-table-cell">
                  {{ player.user?.email || 'Chưa có email' }}
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="inline-flex items-center justify-center rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--primary)] whitespace-nowrap">
                    {{ player.score }} điểm
                  </span>
                </td>
                <td class="px-4 py-3 text-center font-bold text-[var(--text)] whitespace-nowrap">
                  {{ player.answered_count ?? player.current_question_index }} / {{ player.total_questions ?? monitor.total_questions ?? 0 }}
                </td>
                <td class="px-4 py-3 text-center font-black text-emerald-400">
                  {{ player.correct_count }}
                </td>
                <td class="px-4 py-3 min-w-[140px]">
                  <div class="flex items-center gap-2">
                    <div class="h-2 w-20 overflow-hidden rounded-full bg-[var(--surface)]">
                      <div class="h-full rounded-full bg-gradient-to-r from-[var(--primary)] to-[var(--primary-2)] transition-all duration-500" 
                           :style="{ width: `${progressPercent(player)}%` }">
                      </div>
                    </div>
                    <span class="text-xs font-bold text-[var(--text)]">{{ progressPercent(player)}}%</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-black border"
                        :class="player.status === 'disconnected' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' :
                                (player.is_finished || player.player_finished) ? 'bg-sky-500/10 text-sky-400 border-sky-500/20' : 
                                'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'">
                    <span class="h-1.5 w-1.5 rounded-full" 
                          :class="player.status === 'disconnected' ? 'bg-rose-400' :
                                  (player.is_finished || player.player_finished) ? 'bg-sky-400' : 
                                  'bg-emerald-400'"></span>
                    <span>
                      {{ player.status === 'disconnected' ? 'Disconnected' :
                         (player.is_finished || player.player_finished) ? 'Finished' : 'Playing' }}
                    </span>
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="mt-5 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-8 text-center text-sm font-bold text-[var(--muted)]">
          Chưa có người chơi tham gia.
        </div>
      </article>

      <!-- Cột Bảng xếp hạng (Bên phải) -->
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Leaderboard</p>
        <h2 class="mt-1 text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Bảng xếp hạng</h2>

        <div v-if="leaderboard.length" class="mt-5 relative">
          <TransitionGroup name="leaderboard-list" tag="div" class="grid gap-3 max-h-[600px] overflow-y-auto pr-1">
            <div v-for="entry in leaderboard" :key="entry.user_id" 
                 class="relative flex flex-col gap-3 rounded-2xl border p-4 transition duration-300 hover:scale-[1.02]"
                 :class="[
                   entry.rank === 1 ? 'border-yellow-500/40 bg-yellow-500/5 shadow-[0_0_15px_rgba(234,179,8,0.12)]' : 
                   entry.rank === 2 ? 'border-slate-300/40 bg-slate-400/5 shadow-[0_0_15px_rgba(203,213,225,0.1)]' : 
                   entry.rank === 3 ? 'border-amber-600/40 bg-amber-700/5 shadow-[0_0_15px_rgba(180,83,9,0.1)]' : 
                   'border-[var(--border)] bg-[var(--surface-soft)]'
                 ]">
              
              <!-- Floating Points animation for this user -->
              <transition name="float-points">
                <span v-if="activeFloatingPoints[entry.user_id]" class="floating-points" :key="activeFloatingPoints[entry.user_id].id">
                  {{ activeFloatingPoints[entry.user_id].amount }}
                </span>
              </transition>

              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                  <span class="w-6 text-center text-lg font-black">
                    {{ getRankIcon(entry.rank) || `#${entry.rank}` }}
                  </span>
                  
                  <!-- Avatar Gradient -->
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full font-black text-white text-xs"
                       :style="getAvatarStyle(entry.user?.name || entry.user_id)">
                    {{ getAvatarInitial(entry.user?.name || entry.user_id) }}
                  </div>
                  
                  <div>
                    <h3 class="text-sm font-black text-[var(--text)] truncate max-w-[120px]">{{ entry.user?.name || `User #${entry.user_id}` }}</h3>
                    <p class="text-[10px] font-bold text-[var(--muted)]">Đúng {{ entry.correct_count }} / {{ entry.total_questions }}</p>
                  </div>
                </div>
                
                <div class="text-right">
                  <span class="text-lg font-black text-[var(--primary)]">{{ entry.score }}</span>
                </div>
              </div>

              <!-- Thanh progress mini -->
              <div class="w-full">
                <div class="h-1.5 overflow-hidden rounded-full bg-[var(--surface)]">
                  <div class="h-full rounded-full bg-gradient-to-r from-[var(--primary)] to-[var(--primary-2)] transition-all duration-500" 
                       :style="{ width: `${entry.total_questions ? (entry.answered_count / entry.total_questions) * 100 : 0}%` }">
                  </div>
                </div>
              </div>
            </div>
          </TransitionGroup>
        </div>
        <div v-else class="mt-5 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-8 text-center text-sm font-bold text-[var(--muted)]">
          Chưa có điểm.
        </div>
      </article>
    </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, inject } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { getEcho, getTabId } from '@/echo'
import { currentUserStorage, liveRoomApi } from '@/services/api'

const showConfirm = inject('showConfirm')
const showToast = inject('showToast')

const route = useRoute()
const router = useRouter()
const liveRoomId = computed(() => route.params.liveRoomId)
const currentUser = currentUserStorage.get()
const monitor = ref({})
const liveRoom = ref({})
const isActionLoading = ref(false)
const errorMessage = ref('')
const lastRealtimeAt = ref(0)
const activeFloatingPoints = ref({})

// Multi-tab Collision State
const isDuplicateTab = ref(false)
const myTabId = getTabId()
const myJoinedAt = ref(Date.now())

let pollTimer = null
let liveChannel = null
const realtimeFreshMs = 8000

const getAvatarInitial = (name) => {
  if (!name) return '?'
  return String(name).trim().charAt(0).toUpperCase()
}

const getAvatarStyle = (name) => {
  const colors = [
    ['#9b2cff', '#cf30ff'],
    ['#ff7a45', '#ff4d6d'],
    ['#16f2b3', '#0b8793'],
    ['#ec4899', '#f43f5e'],
    ['#3b82f6', '#1d4ed8'],
    ['#10b981', '#047857']
  ]
  const str = String(name || '')
  let hash = 0
  for (let i = 0; i < str.length; i++) {
    hash = str.charCodeAt(i) + ((hash << 5) - hash)
  }
  const index = Math.abs(hash) % colors.length
  const [c1, c2] = colors[index]
  return {
    background: `linear-gradient(135deg, ${c1}, ${c2})`,
    boxShadow: `0 4px 10px rgba(0, 0, 0, 0.2)`
  }
}

const getRankIcon = (rank) => {
  if (rank === 1) return '🥇'
  if (rank === 2) return '🥈'
  if (rank === 3) return '🥉'
  return ''
}

const confirmAndLeave = () => {
  leaveRealtime()
  router.push('/live-rooms')
}

const getMemberUserId = (m) => Number(m?.user_id ?? m?.info?.user_id ?? (typeof m?.id === 'number' ? m.id : null))
const getMemberTabId = (m) => String(m?.tab_id ?? m?.info?.tab_id ?? m?.id ?? '')
const getMemberJoinedAt = (m) => Number(m?.joined_at ?? m?.info?.joined_at ?? 0)

const handleDuplicateSession = () => {
  if (isDuplicateTab.value) return
  isDuplicateTab.value = true

  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }

  leaveRealtime()
}

const triggerFloatingPoints = (userId, amount) => {
  activeFloatingPoints.value[userId] = {
    amount: `+${amount}`,
    id: Date.now()
  }
  setTimeout(() => {
    if (activeFloatingPoints.value[userId] && activeFloatingPoints.value[userId].amount === `+${amount}`) {
      delete activeFloatingPoints.value[userId]
    }
  }, 1500)
}

const hasLoadedRoom = computed(() => Boolean(liveRoom.value.id))
const roomStatus = computed(() => monitor.value.room_status || liveRoom.value.status || 'loading')
const playersProgress = computed(() => monitor.value.players_progress || [])
const sortedPlayersProgress = computed(() => {
  const list = [...playersProgress.value]
  return list.sort((a, b) => {
    const scoreDiff = (b.score ?? 0) - (a.score ?? 0)
    if (scoreDiff !== 0) return scoreDiff
    
    const correctDiff = (b.correct_count ?? 0) - (a.correct_count ?? 0)
    if (correctDiff !== 0) return correctDiff
    
    const aFinished = a.is_finished || a.player_finished || a.finished_at ? 1 : 0
    const bFinished = b.is_finished || b.player_finished || b.finished_at ? 1 : 0
    if (bFinished !== aFinished) return bFinished - aFinished
    
    if (a.finished_at && b.finished_at) {
      return new Date(a.finished_at).getTime() - new Date(b.finished_at).getTime()
    }
    
    return (a.user_id ?? 0) - (b.user_id ?? 0)
  })
})
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
  if (isDuplicateTab.value) return
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
  
  const playerPayload = event?.player
  if (playerPayload?.user_id) {
    const existingPlayer = (monitor.value.players_progress || []).find(
      p => Number(p.user_id) === Number(playerPayload.user_id)
    )
    if (existingPlayer) {
      const diff = Number(playerPayload.score) - Number(existingPlayer.score)
      if (diff > 0) {
        triggerFloatingPoints(playerPayload.user_id, diff)
      }
    }
  }

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
  if (isDuplicateTab.value) return

  try {
    myJoinedAt.value = Date.now()
    liveChannel = getEcho().join(`live-room.${liveRoomId.value}`)

    liveChannel
      .here((members) => {
        realtimeLog('presence.here', members)
        const myUserId = Number(currentUser?.id || currentUserStorage.get()?.id)
        if (!myUserId) return

        const myServerMember = (members || []).find((m) => getMemberTabId(m) === myTabId)
        const myServerJoinedAt = getMemberJoinedAt(myServerMember) || myJoinedAt.value

        const duplicate = (members || []).find((m) => {
          const otherUserId = getMemberUserId(m)
          const otherTabId = getMemberTabId(m)
          const otherJoinedAt = getMemberJoinedAt(m)
          return otherUserId === myUserId && otherTabId !== myTabId && otherJoinedAt > myServerJoinedAt
        })

        if (duplicate) {
          handleDuplicateSession()
        }
      })
      .joining((member) => {
        realtimeLog('presence.joining', member)
        const myUserId = Number(currentUser?.id || currentUserStorage.get()?.id)
        if (!myUserId) return

        const newUserId = getMemberUserId(member)
        const newTabId = getMemberTabId(member)

        if (newUserId === myUserId && newTabId !== myTabId) {
          handleDuplicateSession()
        }
      })
      .listen('.live.player.joined', handlePlayerJoined)
      .listen('.live.room.started', handleRoomStarted)
      .listen('.live.answer.submitted', handleAnswerSubmitted)
      .listen('.live.leaderboard.updated', handleLeaderboardUpdated)
      .listen('.live.room.finished', handleRoomFinished)
  } catch (err) {
    console.error('[realtime] Subscribe presence error:', err)
    liveChannel = null
  }
}

const leaveRealtime = () => {
  if (!liveChannel) return

  getEcho().leave(`live-room.${liveRoomId.value}`)
  liveChannel = null
}

const startLive = async () => {
  if (isDuplicateTab.value) return
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
  if (isDuplicateTab.value) return
  if (showConfirm) {
    showConfirm(
      'Kết thúc phòng thi đấu',
      'Bạn có chắc muốn kết thúc phòng thi đấu này không? Trận đấu đang diễn ra sẽ kết thúc và lưu điểm.',
      async () => {
        await executeFinishLive()
      }
    )
  } else {
    if (!window.confirm('Bạn có chắc muốn kết thúc trận đấu không?')) return
    await executeFinishLive()
  }
}

const executeFinishLive = async () => {
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
    if (showToast) {
      showToast('Đã kết thúc trận đấu thành công.', 'success')
    }
  } catch (error) {
    errorMessage.value = error.message || 'Không kết thúc được trận đấu.'
    if (showToast) {
      showToast(errorMessage.value, 'error')
    }
  } finally {
    isActionLoading.value = false
  }
}

onMounted(async () => {
  await loadMonitor(true)
  if (Number(liveRoom.value.host_id) !== Number(currentUser?.id)) {
    errorMessage.value = 'Bạn không có quyền quản lý phòng trực tuyến này.'
    return
  }
  subscribeToRealtime()
  pollTimer = setInterval(loadMonitor, 10000)
})

onBeforeUnmount(() => {
  clearInterval(pollTimer)
  leaveRealtime()
})
</script>

<style scoped>
.leaderboard-list-move {
  transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1);
}
.leaderboard-list-enter-active,
.leaderboard-list-leave-active {
  transition: all 0.5s ease;
}
.leaderboard-list-enter-from,
.leaderboard-list-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}
.leaderboard-list-leave-active {
  position: absolute;
  width: 100%;
}

@keyframes floatUpFade {
  0% {
    transform: translateY(0) scale(0.8);
    opacity: 0;
  }
  15% {
    transform: translateY(-15px) scale(1.25);
    opacity: 1;
  }
  100% {
    transform: translateY(-50px) scale(0.9);
    opacity: 0;
  }
}
.floating-points {
  position: absolute;
  top: 15px;
  right: 15px;
  font-size: 1.25rem;
  font-weight: 900;
  color: var(--accent-2);
  animation: floatUpFade 1.4s forwards cubic-bezier(0.18, 0.89, 0.32, 1.28);
  pointer-events: none;
  z-index: 50;
  text-shadow: 0 4px 10px rgba(0,0,0,0.5);
}

/* Scrollbar styling for Leaderboard container */
.max-h-\[600px\]::-webkit-scrollbar {
  width: 5px;
}
.max-h-\[600px\]::-webkit-scrollbar-track {
  background: transparent;
}
.max-h-\[600px\]::-webkit-scrollbar-thumb {
  background: var(--border);
  border-radius: 99px;
}
.max-h-\[600px\]::-webkit-scrollbar-thumb:hover {
  background: var(--border-strong);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
