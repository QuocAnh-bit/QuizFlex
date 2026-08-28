<template>
  <section class="max-w-5xl mx-auto py-4 space-y-6">
    <!-- Modal Cảnh báo Đăng nhập đa Tab / Thiết bị cho Host -->
    <transition name="fade">
      <div
        v-if="isDuplicateTab"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm"
      >
        <div
          class="w-full max-w-md rounded-2xl border border-red-200 bg-white p-6 shadow-xl text-center space-y-3"
        >
          <div
            class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-red-600"
          >
            <AlertTriangle :size="28" :stroke-width="2.5" />
          </div>

          <h3 class="text-lg font-bold text-slate-900">
            Phiên điều khiển bị gián đoạn
          </h3>

          <p class="text-xs text-slate-600 leading-relaxed">
            Tài khoản quản lý của bạn đang được mở trong một tab hoặc thiết bị
            khác. Bạn đã bị đưa ra khỏi phiên điều khiển này để tránh xung đột.
          </p>

          <div class="pt-2">
            <button
              type="button"
              class="btn-primary w-full text-xs py-2.5"
              @click="confirmAndLeave"
            >
              Xác nhận và rời phòng
            </button>
          </div>
        </div>
      </div>
    </transition>

    <div class="flex flex-wrap items-center justify-between gap-3">
      <router-link class="btn-secondary text-xs" to="/live-rooms">
        ← Quay lại phòng thi đấu
      </router-link>

      <router-link
        class="btn-secondary text-xs inline-flex items-center gap-1.5"
        :to="`/live-rooms/${liveRoomId}/leaderboard`"
      >
        <Trophy :size="15" :stroke-width="2.5" />
        <span>Bảng xếp hạng</span>
      </router-link>
    </div>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700"
    >
      {{ errorMessage }}
    </div>

    <div v-else class="space-y-6">
      <!-- Banner Banned -->
      <div
        v-if="liveRoom.status === 'banned'"
        class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs font-bold text-amber-800"
      >
        Phòng này đã bị quản trị viên khóa. Bạn chỉ có thể xem thông tin phòng
        và không thể thực hiện bất kỳ thao tác quản lý nào.
      </div>

      <!-- Main Room Card -->
      <article class="card p-6 sm:p-8 space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
          <div class="space-y-1">
            <span
              class="rounded-full bg-amber-50 border border-amber-200 px-3 py-0.5 text-xs font-bold text-amber-700"
            >
              Điều khiển chủ phòng
            </span>

            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 pt-1">
              {{ liveRoom.title || 'Phòng thi đấu' }}
            </h1>

            <p class="text-xs text-slate-500">
              Màn hình điều khiển dành riêng cho host. Bạn không tham gia làm bài.
            </p>
          </div>

          <div class="flex flex-col items-start sm:items-end gap-2">
            <span
              class="font-mono text-sm font-bold text-amber-700 bg-amber-50 px-3 py-1 rounded-lg border border-amber-200"
            >
              Mã phòng: {{ liveRoom.code || '-' }}
            </span>

            <StatusBadge :value="roomStatus" />
          </div>
        </div>

        <div
          class="grid grid-cols-2 gap-3 sm:grid-cols-4 pt-4 border-t border-slate-100 text-xs"
        >
          <div class="rounded-xl bg-slate-50 p-3">
            <span class="text-slate-400 font-bold uppercase text-[10px] block">
              Người chơi
            </span>
            <b class="text-slate-900 font-black text-lg block mt-0.5">
              {{ monitor.total_players ?? 0 }}
            </b>
          </div>

          <div class="rounded-xl bg-slate-50 p-3">
            <span class="text-slate-400 font-bold uppercase text-[10px] block">
              Đã nộp bài
            </span>
            <b class="text-slate-900 font-black text-lg block mt-0.5">
              {{ monitor.total_finished_players ?? 0 }}
            </b>
          </div>

          <div class="rounded-xl bg-slate-50 p-3">
            <span class="text-slate-400 font-bold uppercase text-[10px] block">
              Tổng số câu
            </span>
            <b class="text-slate-900 font-black text-lg block mt-0.5">
              {{ monitor.total_questions ?? 0 }}
            </b>
          </div>

          <div class="rounded-xl bg-slate-50 p-3">
            <span class="text-slate-400 font-bold uppercase text-[10px] block">
              Quiz gốc
            </span>
            <b class="text-slate-900 font-bold block mt-0.5 truncate">
              {{ liveRoom.quiz?.title || '-' }}
            </b>
          </div>
        </div>

        <div
          v-if="liveRoom.status !== 'banned'"
          class="flex flex-wrap gap-2.5 pt-4 border-t border-slate-100"
        >
          <button
            v-if="hasLoadedRoom && roomStatus === 'waiting'"
            class="btn-primary text-xs px-5 py-2.5 inline-flex items-center justify-center gap-1.5"
            type="button"
            :disabled="isActionLoading || isDuplicateTab"
            @click="startLive"
          >
            <template v-if="isActionLoading">
              Đang khởi động...
            </template>

            <template v-else>
              <Rocket :size="14" :stroke-width="2.5" />
              Bắt đầu trận đấu (Start Live)
            </template>
          </button>

          <button
            v-if="hasLoadedRoom && ['waiting', 'playing'].includes(roomStatus)"
            class="btn-danger text-xs px-4 py-2.5"
            type="button"
            :disabled="isActionLoading || isDuplicateTab"
            @click="finishLive"
          >
            {{ isActionLoading ? 'Đang xử lý...' : 'Kết thúc trận đấu (Finish Live)' }}
          </button>
        </div>
      </article>

      <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
        <!-- Players progress table -->
        <article class="card p-6 space-y-4">
          <div
            class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3"
          >
            <div>
              <h2 class="text-base font-bold text-slate-900">
                Tiến độ người chơi
              </h2>
              <p class="text-xs text-slate-500">
                Cập nhật kết quả làm bài thời gian thực
              </p>
            </div>

            <span
              class="rounded-full bg-purple-50 text-[#7C3AED] px-2.5 py-0.5 text-[10px] font-bold"
            >
              Realtime
            </span>
          </div>

          <div
            v-if="sortedPlayersProgress.length"
            class="overflow-x-auto max-h-[500px] overflow-y-auto"
          >
            <table class="w-full text-left text-xs">
              <thead
                class="sticky top-0 bg-slate-50 text-slate-400 font-bold uppercase text-[10px] border-b border-slate-100"
              >
                <tr>
                  <th class="py-3 px-3 text-center">#</th>
                  <th class="py-3 px-3">Người chơi</th>
                  <th class="py-3 px-3 text-center">Điểm</th>
                  <th class="py-3 px-3 text-center">Đã làm</th>
                  <th class="py-3 px-3 text-center">Đúng</th>
                  <th class="py-3 px-3">Tiến độ</th>
                  <th class="py-3 px-3 text-center">Trạng thái</th>
                </tr>
              </thead>

              <tbody class="divide-y divide-slate-100 font-medium">
                <tr
                  v-for="(player, idx) in sortedPlayersProgress"
                  :key="player.user_id"
                  class="hover:bg-slate-50"
                >
                  <td
                    class="py-3 px-3 text-center font-bold"
                    :class="
                      idx === 0
                        ? 'text-amber-500'
                        : idx === 1
                          ? 'text-slate-400'
                          : idx === 2
                            ? 'text-amber-700'
                            : 'text-slate-400'
                    "
                  >
                    <Medal
                      v-if="idx === 0"
                      :size="18"
                      :stroke-width="2.5"
                      class="mx-auto text-amber-500"
                    />

                    <Medal
                      v-else-if="idx === 1"
                      :size="18"
                      :stroke-width="2.5"
                      class="mx-auto text-slate-400"
                    />

                    <Medal
                      v-else-if="idx === 2"
                      :size="18"
                      :stroke-width="2.5"
                      class="mx-auto text-amber-700"
                    />

                    <span v-else>
                      {{ idx + 1 }}
                    </span>
                  </td>

                  <td class="py-3 px-3">
                    <span
                      class="font-bold text-slate-900 block truncate max-w-[140px]"
                    >
                      {{ player.user?.name || `User #${player.user_id}` }}
                    </span>

                    <span
                      class="text-[10px] text-slate-400 block truncate max-w-[140px]"
                    >
                      {{ player.user?.email || '-' }}
                    </span>
                  </td>

                  <td class="py-3 px-3 text-center font-black text-[#7C3AED]">
                    {{ player.score }}
                  </td>

                  <td class="py-3 px-3 text-center text-slate-700">
                    {{ player.answered_count ?? player.current_question_index }}
                    /
                    {{ player.total_questions ?? monitor.total_questions ?? 0 }}
                  </td>

                  <td class="py-3 px-3 text-center font-bold text-emerald-700">
                    {{ player.correct_count }}
                  </td>

                  <td class="py-3 px-3 min-w-[100px]">
                    <div class="flex items-center gap-2">
                      <div
                        class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-100"
                      >
                        <div
                          class="h-full rounded-full bg-[#7C3AED] transition-all duration-300"
                          :style="{ width: `${progressPercent(player)}%` }"
                        ></div>
                      </div>

                      <span class="text-[10px] text-slate-500 font-semibold">
                        {{ progressPercent(player) }}%
                      </span>
                    </div>
                  </td>

                  <td class="py-3 px-3 text-center">
                    <span
                      class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                      :class="
                        player.status === 'disconnected'
                          ? 'bg-red-50 text-red-700'
                          : player.is_finished ||
                              player.player_finished
                            ? 'bg-blue-50 text-blue-700'
                            : 'bg-emerald-50 text-emerald-700'
                      "
                    >
                      {{
                        player.status === 'disconnected'
                          ? 'Mất kết nối'
                          : player.is_finished || player.player_finished
                            ? 'Đã xong'
                            : 'Đang làm'
                      }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="p-8 text-center text-slate-400 text-xs">
            Chưa có người chơi tham gia. Hãy chia sẻ mã phòng cho mọi người.
          </div>
        </article>

        <!-- Leaderboard sidebar -->
        <article class="card p-5 space-y-4">
          <div class="border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-900">
              Bảng xếp hạng trực tiếp
            </h2>
            <p class="text-xs text-slate-500">
              Top người chơi dẫn đầu
            </p>
          </div>

          <div
            v-if="leaderboard.length"
            class="space-y-2 max-h-[480px] overflow-y-auto"
          >
            <div
              v-for="entry in leaderboard"
              :key="entry.user_id"
              class="relative rounded-xl border p-3 flex items-center justify-between gap-3 text-xs"
              :class="
                entry.rank === 1
                  ? 'border-amber-300 bg-amber-50/50'
                  : entry.rank === 2
                    ? 'border-slate-300 bg-slate-50'
                    : entry.rank === 3
                      ? 'border-amber-200 bg-amber-50/30'
                      : 'border-slate-100 bg-white'
              "
            >
              <div class="flex items-center gap-2.5 min-w-0">
                <span class="font-bold text-sm w-5 text-center">
                  <Medal
                    v-if="entry.rank === 1"
                    :size="17"
                    :stroke-width="2.5"
                    class="mx-auto text-amber-500"
                  />

                  <Medal
                    v-else-if="entry.rank === 2"
                    :size="17"
                    :stroke-width="2.5"
                    class="mx-auto text-slate-400"
                  />

                  <Medal
                    v-else-if="entry.rank === 3"
                    :size="17"
                    :stroke-width="2.5"
                    class="mx-auto text-amber-700"
                  />

                  <span v-else>
                    #{{ entry.rank }}
                  </span>
                </span>

                <div class="truncate">
                  <h4 class="font-bold text-slate-900 truncate">
                    {{ entry.user?.name || `User #${entry.user_id}` }}
                  </h4>

                  <p class="text-[10px] text-slate-400">
                    Đúng {{ entry.correct_count }} /
                    {{ entry.total_questions }} câu
                  </p>
                </div>
              </div>

              <div class="text-right shrink-0">
                <span class="font-black text-[#7C3AED] text-sm">
                  {{ entry.score }}
                </span>

                <span class="text-[10px] text-slate-400 block">
                  điểm
                </span>
              </div>
            </div>
          </div>

          <div v-else class="text-slate-400 text-xs text-center py-6">
            Chưa có dữ liệu điểm số.
          </div>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, inject } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  AlertTriangle,
  Trophy,
  Rocket,
  Medal,
} from 'lucide-vue-next'
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

const confirmAndLeave = () => {
  leaveRealtime()
  router.push('/live-rooms')
}

const getMemberUserId = (m) =>
  Number(
    m?.user_id ??
      m?.info?.user_id ??
      (typeof m?.id === 'number' ? m.id : null)
  )

const getMemberTabId = (m) =>
  String(m?.tab_id ?? m?.info?.tab_id ?? m?.id ?? '')

const getMemberJoinedAt = (m) =>
  Number(m?.joined_at ?? m?.info?.joined_at ?? 0)

const handleDuplicateSession = () => {
  if (isDuplicateTab.value) return
  isDuplicateTab.value = true

  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }

  leaveRealtime()
}

const hasLoadedRoom = computed(() => Boolean(liveRoom.value.id))

const roomStatus = computed(
  () => monitor.value.room_status || liveRoom.value.status || 'loading'
)

const playersProgress = computed(
  () => monitor.value.players_progress || []
)

const sortedPlayersProgress = computed(() => {
  const list = [...playersProgress.value]

  return list.sort((a, b) => {
    const scoreDiff = (b.score ?? 0) - (a.score ?? 0)
    if (scoreDiff !== 0) return scoreDiff

    const correctDiff =
      (b.correct_count ?? 0) - (a.correct_count ?? 0)

    if (correctDiff !== 0) return correctDiff

    const aFinished =
      a.is_finished || a.player_finished || a.finished_at ? 1 : 0

    const bFinished =
      b.is_finished || b.player_finished || b.finished_at ? 1 : 0

    if (bFinished !== aFinished) {
      return bFinished - aFinished
    }

    if (a.finished_at && b.finished_at) {
      return (
        new Date(a.finished_at).getTime() -
        new Date(b.finished_at).getTime()
      )
    }

    return (a.user_id ?? 0) - (b.user_id ?? 0)
  })
})

const leaderboard = computed(
  () => monitor.value.leaderboard || []
)

const progressPercent = (player) => {
  const total = Number(
    player.total_questions ??
      monitor.value.total_questions ??
      0
  )

  if (!total) return 0

  return Math.min(
    100,
    Math.round(
      (Number(
        player.answered_count ??
          player.current_question_index ??
          0
      ) /
        total) *
        100
    )
  )
}

const markRealtime = () => {
  lastRealtimeAt.value = Date.now()
}

const hasRecentRealtime = () =>
  Date.now() - lastRealtimeAt.value < realtimeFreshMs

const loadMonitor = async (force = false) => {
  if (isDuplicateTab.value) return
  if (!force && hasRecentRealtime()) return

  try {
    const data = await liveRoomApi.getLiveCurrentQuestion(
      liveRoomId.value
    )

    monitor.value = data
    liveRoom.value =
      data.live_room || liveRoom.value

    errorMessage.value = ''
  } catch (error) {
    errorMessage.value =
      error.message ||
      'Không tải được monitor live room.'
  }
}

const realtimeLog = (eventName, event) => {
  console.log(
    '[realtime]',
    eventName,
    new Date().toISOString(),
    event
  )
}

const upsertPlayerProgress = (player) => {
  if (!player?.user_id) return false

  const players = [
    ...(monitor.value.players_progress || []),
  ]

  const index = players.findIndex(
    (item) =>
      Number(item.user_id) ===
      Number(player.user_id)
  )

  if (index >= 0) {
    players[index] = {
      ...players[index],
      ...player,
    }
  } else {
    players.push(player)
  }

  monitor.value = {
    ...monitor.value,
    players_progress: players,
    total_players: players.length,
    total_finished_players: players.filter(
      (item) =>
        item.is_finished ||
        item.player_finished ||
        item.finished_at
    ).length,
  }

  return true
}

const applyLeaderboard = (leaderboardPayload) => {
  if (!Array.isArray(leaderboardPayload)) {
    return false
  }

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
      players_progress:
        event.players_progress,
      total_players:
        event.player_count ??
        event.players_progress.length,
      total_finished_players:
        event.players_progress.filter(
          (item) =>
            item.is_finished ||
            item.player_finished ||
            item.finished_at
        ).length,
      leaderboard: Array.isArray(
        event?.leaderboard
      )
        ? event.leaderboard
        : monitor.value.leaderboard,
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
  realtimeLog(
    'live.leaderboard.updated',
    event
  )

  markRealtime()

  if (!applyLeaderboard(event?.leaderboard)) {
    loadMonitor(true)
  }
}

const handleRoomStarted = (event) => {
  realtimeLog('live.room.started', event)
  markRealtime()

  const playersProgressPayload = Array.isArray(
    event?.players_progress
  )
    ? event.players_progress
    : monitor.value.players_progress

  const leaderboardPayload = Array.isArray(
    event?.leaderboard
  )
    ? event.leaderboard
    : monitor.value.leaderboard

  monitor.value = {
    ...monitor.value,
    room_status:
      event?.status || 'playing',
    total_questions:
      event?.total_questions ??
      monitor.value.total_questions,
    players_progress:
      playersProgressPayload,
    leaderboard:
      leaderboardPayload,
    total_players:
      Array.isArray(playersProgressPayload)
        ? playersProgressPayload.length
        : monitor.value.total_players,
    total_finished_players:
      Array.isArray(playersProgressPayload)
        ? playersProgressPayload.filter(
            (item) =>
              item.is_finished ||
              item.player_finished ||
              item.finished_at
          ).length
        : monitor.value.total_finished_players,
  }

  liveRoom.value = {
    ...liveRoom.value,
    status:
      event?.status || 'playing',
    started_at:
      event?.started_at ||
      liveRoom.value.started_at,
  }
}

const handleRoomFinished = (event) => {
  realtimeLog('live.room.finished', event)
  markRealtime()

  const playersProgressPayload = Array.isArray(
    event?.players_progress
  )
    ? event.players_progress
    : monitor.value.players_progress

  monitor.value = {
    ...monitor.value,
    room_status:
      event?.status || 'finished',
    leaderboard: Array.isArray(
      event?.leaderboard
    )
      ? event.leaderboard
      : monitor.value.leaderboard,
    players_progress:
      playersProgressPayload,
    total_players:
      event?.total_players ??
      (Array.isArray(playersProgressPayload)
        ? playersProgressPayload.length
        : monitor.value.total_players),
    total_finished_players:
      event?.total_finished_players ??
      (Array.isArray(playersProgressPayload)
        ? playersProgressPayload.filter(
            (item) =>
              item.is_finished ||
              item.player_finished ||
              item.finished_at
          ).length
        : monitor.value.total_finished_players),
  }

  liveRoom.value = {
    ...liveRoom.value,
    status:
      event?.status || 'finished',
    ended_at:
      event?.ended_at ||
      liveRoom.value.ended_at,
  }

  if (!Array.isArray(event?.leaderboard)) {
    loadMonitor(true)
  }
}

const subscribeToRealtime = () => {
  if (isDuplicateTab.value) return

  try {
    myJoinedAt.value = Date.now()

    liveChannel = getEcho().join(
      `live-room.${liveRoomId.value}`
    )

    liveChannel
      .here((members) => {
        realtimeLog(
          'presence.here',
          members
        )

        const myUserId = Number(
          currentUser?.id ||
            currentUserStorage.get()?.id
        )

        if (!myUserId) return

        const myServerMember =
          (members || []).find(
            (m) =>
              getMemberTabId(m) === myTabId
          )

        const myServerJoinedAt =
          getMemberJoinedAt(
            myServerMember
          ) || myJoinedAt.value

        const duplicate =
          (members || []).find((m) => {
            const otherUserId =
              getMemberUserId(m)

            const otherTabId =
              getMemberTabId(m)

            const otherJoinedAt =
              getMemberJoinedAt(m)

            return (
              otherUserId === myUserId &&
              otherTabId !== myTabId &&
              otherJoinedAt >
                myServerJoinedAt
            )
          })

        if (duplicate) {
          handleDuplicateSession()
        }
      })
      .joining((member) => {
        realtimeLog(
          'presence.joining',
          member
        )

        const myUserId = Number(
          currentUser?.id ||
            currentUserStorage.get()?.id
        )

        if (!myUserId) return

        const newUserId =
          getMemberUserId(member)

        const newTabId =
          getMemberTabId(member)

        if (
          newUserId === myUserId &&
          newTabId !== myTabId
        ) {
          handleDuplicateSession()
        }
      })
      .listen(
        '.live.player.joined',
        handlePlayerJoined
      )
      .listen(
        '.live.room.started',
        handleRoomStarted
      )
      .listen(
        '.live.answer.submitted',
        handleAnswerSubmitted
      )
      .listen(
        '.live.leaderboard.updated',
        handleLeaderboardUpdated
      )
      .listen(
        '.live.room.finished',
        handleRoomFinished
      )
  } catch (err) {
    console.error(
      '[realtime] Subscribe presence error:',
      err
    )

    liveChannel = null
  }
}

const leaveRealtime = () => {
  if (!liveChannel) return

  getEcho().leave(
    `live-room.${liveRoomId.value}`
  )

  liveChannel = null
}

const startLive = async () => {
  if (isDuplicateTab.value) return

  isActionLoading.value = true
  errorMessage.value = ''

  try {
    const data =
      await liveRoomApi.startLiveRoom(
        liveRoomId.value
      )

    liveRoom.value =
      data.live_room || liveRoom.value

    monitor.value =
      data.monitor || monitor.value
  } catch (error) {
    errorMessage.value =
      error.message ||
      'Không start được live room.'
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
    if (
      !window.confirm(
        'Bạn có chắc muốn kết thúc trận đấu không?'
      )
    ) {
      return
    }

    await executeFinishLive()
  }
}

const executeFinishLive = async () => {
  isActionLoading.value = true
  errorMessage.value = ''

  try {
    const data =
      await liveRoomApi.finishLiveRoom(
        liveRoomId.value
      )

    liveRoom.value =
      data.live_room || liveRoom.value

    monitor.value = {
      ...monitor.value,
      room_status:
        data.live_room?.status ||
        'finished',
      live_room:
        data.live_room ||
        liveRoom.value,
      leaderboard:
        data.leaderboard ||
        monitor.value.leaderboard ||
        [],
    }

    if (showToast) {
      showToast(
        'Đã kết thúc trận đấu thành công.',
        'success'
      )
    }
  } catch (error) {
    errorMessage.value =
      error.message ||
      'Không kết thúc được trận đấu.'

    if (showToast) {
      showToast(
        errorMessage.value,
        'error'
      )
    }
  } finally {
    isActionLoading.value = false
  }
}

onMounted(async () => {
  await loadMonitor(true)

  if (
    Number(liveRoom.value.host_id) !==
    Number(currentUser?.id)
  ) {
    errorMessage.value =
      'Bạn không có quyền quản lý phòng trực tuyến này.'

    return
  }

  subscribeToRealtime()
  pollTimer = setInterval(
    loadMonitor,
    10000
  )
})

onBeforeUnmount(() => {
  clearInterval(pollTimer)
  leaveRealtime()
})
</script>
