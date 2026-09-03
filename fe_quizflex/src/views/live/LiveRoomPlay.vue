<template>
  <section class="max-w-5xl mx-auto py-4 space-y-6">
    <!-- Modal Cảnh báo Đăng nhập đa Tab / Thiết bị -->
    <transition name="fade">
      <div v-if="isDuplicateTab" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl border border-red-200 bg-white p-6 shadow-xl text-center space-y-3">
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-2xl text-red-600">
            ⚠️
          </div>
          <h3 class="text-lg font-bold text-slate-900">Phiên thi đấu bị gián đoạn</h3>
          <p class="text-xs text-slate-600 leading-relaxed">
            Tài khoản của bạn đang được mở trong một tab hoặc thiết bị khác. Bạn đã bị đưa ra khỏi phòng thi đấu này.
          </p>
          <div class="pt-2">
            <button type="button" class="btn-primary w-full text-xs py-2.5" @click="confirmAndLeave">
              Xác nhận và rời phòng
            </button>
          </div>
        </div>
      </div>
    </transition>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
      <!-- Main Play Area -->
      <article class="card p-6 sm:p-8 space-y-6">
        <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700">{{ errorMessage }}</div>
        
        <!-- Answer Feedback Banner -->
        <transition name="slide-up">
          <div 
            v-if="lastAnswerResult" 
            class="rounded-xl border p-4 flex items-center justify-between gap-3"
            :class="lastAnswerResult.isCorrect ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800'"
          >
            <div class="flex items-center gap-3">
              <span class="text-2xl">{{ lastAnswerResult.isCorrect ? '✔' : '✖' }}</span>
              <div>
                <h4 class="text-sm font-bold">{{ lastAnswerResult.isCorrect ? 'Chính xác!' : 'Chưa chính xác' }}</h4>
                <p class="text-xs opacity-90">{{ lastAnswerResult.rankMessage }}</p>
              </div>
            </div>
            <span class="text-base font-black">+{{ lastAnswerResult.scoreAwarded }} điểm</span>
          </div>
        </transition>

        <!-- Banner Banned -->
        <div v-if="isBanned" class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700 flex items-center gap-2">
          <span>🚫</span>
          <span>Phòng thi đấu này đã bị quản trị viên khóa và không thể tiếp tục.</span>
        </div>

        <template v-else>
          <!-- Waiting State -->
          <div v-if="isWaiting" class="text-center py-10 space-y-3">
            <div class="h-14 w-14 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-2xl mx-auto animate-pulse">
              ⏳
            </div>
            <span class="rounded-full bg-amber-50 text-amber-700 px-3 py-0.5 text-xs font-bold">
              Phòng chờ
            </span>
            <h2 class="text-xl font-black text-slate-900 pt-1">Đang chờ chủ phòng bắt đầu trận đấu</h2>
            <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
              Hãy giữ màn hình này, khi chủ phòng bấm Bắt đầu, câu hỏi đầu tiên sẽ hiển thị tự động trên màn hình của bạn.
            </p>
          </div>

          <!-- Finished State -->
          <div v-else-if="isFinished" class="text-center py-10 space-y-4">
            <div class="h-16 w-16 rounded-full bg-purple-50 text-[#7C3AED] flex items-center justify-center text-3xl mx-auto">
              🎉
            </div>
            <div>
              <span class="rounded-full bg-purple-50 text-[#7C3AED] px-3 py-0.5 text-xs font-bold">
                Đã hoàn thành
              </span>
              <h2 class="text-2xl font-black text-slate-900 mt-2">
                {{ roomStatus === 'finished' ? 'Phòng thi đấu đã kết thúc' : 'Bạn đã hoàn thành tất cả câu hỏi!' }}
              </h2>
              <p class="text-xs text-slate-500 mt-1">
                Điểm số: <b class="text-[#7C3AED]">{{ progress.current_score ?? 0 }}</b> | Số câu đúng: <b class="text-emerald-700">{{ progress.correct_count ?? 0 }}/{{ progress.total_questions ?? 0 }}</b>
              </p>
            </div>
            <div class="pt-2">
              <router-link class="btn-primary text-xs px-5 py-2.5" :to="`/live-rooms/${liveRoomId}/leaderboard`">
                Xem bảng xếp hạng đầy đủ 🏆
              </router-link>
            </div>
          </div>

          <!-- Playing Question -->
          <template v-else-if="question.id">
            <div class="space-y-4 border-b border-slate-100 pb-5">
              <div class="flex items-center justify-between">
                <span class="rounded-full bg-purple-50 text-[#7C3AED] border border-purple-200 px-3 py-1 text-xs font-bold">
                  Câu {{ currentQuestionNumber }} / {{ progress.total_questions || 1 }}
                </span>
                <StatusBadge :value="roomStatus" />
              </div>

              <h2 class="text-xl font-bold leading-relaxed text-slate-900 sm:text-2xl pt-1">
                {{ question.question }}
              </h2>

              <!-- Optional Question Image -->
              <div v-if="question.image_url" class="py-2 flex justify-center">
                <QuestionImage
                  :src="question.image_url"
                  size="normal"
                  allow-zoom
                />
              </div>
            </div>

            <!-- Answers List -->
            <div class="grid gap-3 pt-2">
              <button
                v-for="answer in question.answers"
                :key="answer.id"
                type="button"
                class="flex items-center gap-3.5 rounded-xl border p-4 text-left transition duration-150 active:scale-[0.99]"
                :class="selectedAnswerId === answer.id
                  ? 'border-[#7C3AED] bg-purple-50 text-slate-900 shadow-sm'
                  : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50'"
                :disabled="isAnswering"
                @click="submitAnswer(answer.id)"
              >
                <span
                  class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-xs font-black transition"
                  :class="selectedAnswerId === answer.id ? 'bg-[#7C3AED] text-white' : 'bg-slate-100 text-slate-700'"
                >
                  {{ answer.key }}
                </span>
                <span class="font-medium text-sm leading-relaxed flex-1">
                  {{ answer.text }}
                </span>
              </button>
            </div>
          </template>

          <div v-else class="rounded-xl border border-slate-100 bg-slate-50 p-8 text-center text-xs text-slate-500">
            Đang đồng bộ câu hỏi tiếp theo...
          </div>
        </template>
      </article>

      <!-- Sidebar: User Stats & Top 5 -->
      <aside class="grid content-start gap-5">
        <!-- Personal Stats Card -->
        <article class="card p-5 space-y-4">
          <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Thành tích của bạn</span>
            <span class="text-xs font-bold text-[#7C3AED]">Trực tiếp</span>
          </div>

          <div class="grid grid-cols-2 gap-2 text-center text-xs">
            <div class="rounded-lg bg-slate-50 p-3">
              <span class="text-slate-400 font-bold uppercase text-[10px] block">Thứ hạng</span>
              <b class="text-slate-900 font-black text-2xl block mt-0.5">#{{ currentPlayerStats.rank }}</b>
            </div>
            <div class="rounded-lg bg-slate-50 p-3">
              <span class="text-slate-400 font-bold uppercase text-[10px] block">Tổng điểm</span>
              <b class="text-[#7C3AED] font-black text-2xl block mt-0.5">{{ currentPlayerStats.score }}</b>
            </div>
          </div>

          <div v-if="rankMessage" class="rounded-lg bg-purple-50 p-2.5 text-center text-xs font-semibold text-[#7C3AED] border border-purple-100">
            {{ rankMessage }}
          </div>

          <div class="grid grid-cols-2 gap-2 text-xs pt-1">
            <div class="rounded-lg border border-slate-100 p-2 text-center">
              <span class="text-slate-400 text-[10px] font-semibold block">Đúng</span>
              <b class="text-emerald-700 font-bold text-sm block mt-0.5">✔ {{ currentPlayerStats.correct_count }} câu</b>
            </div>
            <div class="rounded-lg border border-slate-100 p-2 text-center">
              <span class="text-slate-400 text-[10px] font-semibold block">Đã làm</span>
              <b class="text-slate-800 font-bold text-sm block mt-0.5">{{ currentPlayerStats.answered_count }}/{{ currentPlayerStats.total_questions }}</b>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
            <div 
              class="h-full rounded-full bg-[#7C3AED] transition-all duration-300"
              :style="{ width: `${currentPlayerStats.total_questions ? (currentPlayerStats.answered_count / currentPlayerStats.total_questions) * 100 : 0}%` }"
            ></div>
          </div>
        </article>

        <!-- Top 5 Leaderboard -->
        <article class="card p-5 space-y-4">
          <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-900">Bảng xếp hạng Top 5</span>
            <span class="rounded bg-slate-100 text-slate-600 px-2 py-0.5 text-[10px] font-bold">Live</span>
          </div>

          <div v-if="leaderboard.length" class="space-y-2">
            <div
              v-for="entry in leaderboard.slice(0, 5)"
              :key="entry.user_id"
              class="flex items-center justify-between rounded-lg p-2.5 text-xs transition"
              :class="Number(entry.user_id) === Number(currentUser?.id) ? 'bg-purple-50 border border-purple-200' : 'bg-slate-50 border border-slate-100'"
            >
              <div class="flex items-center gap-2 truncate">
                <span class="font-bold w-4 text-center">
                  {{ entry.rank === 1 ? '🥇' : entry.rank === 2 ? '🥈' : entry.rank === 3 ? '🥉' : entry.rank }}
                </span>
                <span class="font-bold truncate text-slate-800" :class="{ 'text-[#7C3AED] font-black': Number(entry.user_id) === Number(currentUser?.id) }">
                  {{ entry.user?.name || `User #${entry.user_id}` }}
                </span>
              </div>
              <b class="text-[#7C3AED] font-bold shrink-0">{{ entry.score }}đ</b>
            </div>
          </div>
          <div v-else class="text-center py-6 text-slate-400 text-xs">
            Chưa có bảng xếp hạng.
          </div>
        </article>
      </aside>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import StatusBadge from '@/components/common/StatusBadge.vue'
import QuestionImage from '@/components/question/QuestionImage.vue'
import { getEcho, getTabId } from '@/echo'
import { liveRoomApi, normalizeQuestion, currentUserStorage } from '@/services/api'
import audioService from '@/services/audioService'
import { useAppLoading } from '@/composables/useAppLoading'

const { beginTask, endTask } = useAppLoading()

const route = useRoute()
const router = useRouter()
const liveRoomId = computed(() => route.params.liveRoomId)
const progress = ref({})
const question = ref({ id: 0, question: '', answers: [] })
const leaderboard = ref([])
const roomStatus = ref('waiting')
const errorMessage = ref('')
const answerMessage = ref('')
const selectedAnswerId = ref(null)
const isAnswering = ref(false)
const lastRealtimeAt = ref(0)
const lastAnswerResult = ref(null)
const floatingPoints = ref(null)
const currentUser = computed(() => currentUserStorage.get())

// Multi-tab Collision State
const isDuplicateTab = ref(false)
const myTabId = getTabId()
const myJoinedAt = ref(Date.now())

const currentPlayerStats = computed(() => {
  const myUserId = currentUser.value?.id
  const entry = leaderboard.value.find(item => Number(item.user_id) === Number(myUserId))
  return {
    rank: entry?.rank || '-',
    score: entry?.score ?? progress.value.current_score ?? 0,
    correct_count: entry?.correct_count ?? progress.value.correct_count ?? 0,
    answered_count: entry?.answered_count ?? progress.value.answered_count ?? 0,
    total_questions: entry?.total_questions ?? progress.value.total_questions ?? 0,
  }
})

const rankMessage = computed(() => {
  const list = leaderboard.value
  if (!list.length) return ''
  const myUserId = currentUser.value?.id
  if (!myUserId) return ''
  
  const myIndex = list.findIndex(item => Number(item.user_id) === Number(myUserId))
  if (myIndex === -1) return ''
  
  const myEntry = list[myIndex]
  if (myIndex === 0) {
    return '🏆 Bạn đang dẫn đầu bảng!'
  }
  
  const aboveEntry = list[myIndex - 1]
  const gap = aboveEntry.score - myEntry.score
  const aboveName = aboveEntry.user?.name || `User #${aboveEntry.user_id}`
  return `Còn ${gap} điểm để vượt hạng ${aboveEntry.rank} (${aboveName})`
})

let pollTimer = null
let leaderboardTimer = null
let liveChannel = null
const realtimeFreshMs = 8000

const isWaiting = computed(() => roomStatus.value === 'waiting')
const isFinished = computed(() => roomStatus.value === 'finished' || progress.value.player_finished || progress.value.is_finished)
const isBanned = computed(() => roomStatus.value === 'banned')
const currentQuestionNumber = computed(() => Number(progress.value.player_current_question_index ?? progress.value.current_question_index ?? 0) + 1)

const markRealtime = () => {
  lastRealtimeAt.value = Date.now()
}

const hasRecentRealtime = () => Date.now() - lastRealtimeAt.value < realtimeFreshMs

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
  if (leaderboardTimer) {
    clearInterval(leaderboardTimer)
    leaderboardTimer = null
  }

  audioService.stopAll()
  leaveRealtime()
}

const loadLeaderboard = async (force = false) => {
  if (isDuplicateTab.value) return
  if (!force && hasRecentRealtime()) return

  try {
    leaderboard.value = await liveRoomApi.getLiveLeaderboard(liveRoomId.value)
  } catch {
    leaderboard.value = []
  }
}

const applyQuestionData = (data) => {
  progress.value = data || {}
  roomStatus.value = data?.room_status || roomStatus.value
  question.value = data?.question ? normalizeQuestion(data.question) : { id: 0, question: '', answers: [] }
  leaderboard.value = data?.leaderboard || leaderboard.value
  selectedAnswerId.value = null
}

const loadCurrentQuestion = async (force = false) => {
  if (isDuplicateTab.value) return
  if (!force && hasRecentRealtime()) return

  try {
    const data = await liveRoomApi.getLiveCurrentQuestion(liveRoomId.value)
    applyQuestionData(data)
    errorMessage.value = ''
    if (roomStatus.value === 'finished' || data?.player_finished || data?.is_finished) {
      await loadLeaderboard(true)
    }
  } catch (error) {
    const message = error.message || ''
    if (message.includes('khóa bởi quản trị viên') || error.response?.status === 403) {
      roomStatus.value = 'banned'
      errorMessage.value = message || 'Phòng trực tuyến này đã bị khóa.'
      return
    }
    if (message.includes('Chưa trong trạng thái đang chơi') || message.includes('chưa trong trạng thái') || message.includes('chưa bắt đầu') || message.includes('chờ chủ phòng')) {
      roomStatus.value = 'waiting'
      question.value = { id: 0, question: '', answers: [] }
      errorMessage.value = ''
      return
    }
    errorMessage.value = message || 'Không tải được câu hỏi live.'
  }
}

const realtimeLog = (eventName, event) => {
  console.log('[realtime]', eventName, new Date().toISOString(), event)
}

const applyLeaderboardPayload = (leaderboardPayload) => {
  if (!Array.isArray(leaderboardPayload)) return false

  leaderboard.value = leaderboardPayload
  return true
}

const handleRoomStarted = async (event) => {
  realtimeLog('live.room.started', event)
  audioService.stopLobby()
  roomStatus.value = event?.status || 'playing'
  await loadCurrentQuestion(true)
}

const handleRoomFinished = async (event) => {
  realtimeLog('live.room.finished', event)
  markRealtime()
  audioService.playFinish()

  roomStatus.value = event?.status || 'finished'
  question.value = { id: 0, question: '', answers: [] }
  answerMessage.value = event?.message || 'Live room đã kết thúc.'
  progress.value = {
    ...progress.value,
    player_finished: true,
    is_finished: true,
  }

  if (!applyLeaderboardPayload(event?.leaderboard)) {
    await loadLeaderboard(true)
  }
}

const handleLeaderboardUpdated = async (event) => {
  realtimeLog('live.leaderboard.updated', event)
  markRealtime()
  if (!applyLeaderboardPayload(event?.leaderboard) && isFinished.value) {
    await loadLeaderboard(true)
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
        const myUserId = Number(currentUser.value?.id || currentUserStorage.get()?.id)
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
        const myUserId = Number(currentUser.value?.id || currentUserStorage.get()?.id)
        if (!myUserId) return

        const newUserId = getMemberUserId(member)
        const newTabId = getMemberTabId(member)

        if (newUserId === myUserId && newTabId !== myTabId) {
          handleDuplicateSession()
        }
      })
      .listen('.live.room.started', handleRoomStarted)
      .listen('.live.room.finished', handleRoomFinished)
      .listen('.live.leaderboard.updated', handleLeaderboardUpdated)
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

const submitAnswer = async (answerId) => {
  if (isDuplicateTab.value || isAnswering.value || isFinished.value || isWaiting.value) return
  selectedAnswerId.value = answerId
  isAnswering.value = true
  answerMessage.value = ''
  errorMessage.value = ''
  lastAnswerResult.value = null

  try {
    const oldRank = leaderboard.value.find(item => Number(item.user_id) === Number(currentUser.value?.id))?.rank || null
    const result = await liveRoomApi.answerLiveQuestion(liveRoomId.value, answerId)

    if (result.is_correct) {
      audioService.playCorrect()
    } else {
      audioService.playWrong()
    }

    answerMessage.value = result.is_correct ? `Chính xác, +${result.score_awarded} điểm.` : 'Chưa chính xác, +0 điểm.'
    progress.value = {
      ...progress.value,
      current_score: result.current_score,
      correct_count: result.correct_count,
      player_current_question_index: result.next_question_index,
      current_question_index: result.next_question_index,
      answered_count: result.next_question_index,
      player_finished: result.player_finished,
      is_finished: result.player_finished,
    }
    roomStatus.value = result.room_status || roomStatus.value
    
    if (result.player_finished || result.room_status === 'finished' || !result.has_next_question) {
      audioService.playFinish()
    }
    
    applyLeaderboardPayload(result.leaderboard)
    markRealtime()

    const newRank = leaderboard.value.find(item => Number(item.user_id) === Number(currentUser.value?.id))?.rank || null
    let rankChangeMsg = ''
    if (oldRank !== null && newRank !== null) {
      if (newRank < oldRank) {
        rankChangeMsg = `↑ Bạn đã lên hạng ${newRank}`
      } else if (newRank > oldRank) {
        rankChangeMsg = `↓ Bạn bị tụt xuống hạng ${newRank}`
      } else {
        rankChangeMsg = `Bạn vẫn giữ hạng ${newRank}`
      }
    } else if (newRank !== null) {
      rankChangeMsg = `Bạn đang ở hạng ${newRank}`
    }

    lastAnswerResult.value = {
      isCorrect: result.is_correct,
      scoreAwarded: result.score_awarded,
      rankMessage: rankChangeMsg,
      timestamp: Date.now()
    }

    if (result.score_awarded > 0) {
      floatingPoints.value = {
        amount: `+${result.score_awarded}`,
        id: Date.now()
      }
      setTimeout(() => {
        floatingPoints.value = null
      }, 1500)
    }

    if (result.next_question) {
      question.value = normalizeQuestion(result.next_question)
      selectedAnswerId.value = null
    } else {
      question.value = { id: 0, question: '', answers: [] }
    }
  } catch (error) {
    errorMessage.value = error.message || 'Không gửi được câu trả lời.'
  } finally {
    isAnswering.value = false
  }
}

onMounted(async () => {
  beginTask()
  try {
    audioService.playLobby()
    subscribeToRealtime()
    await loadCurrentQuestion(true)
  } finally {
    endTask()
  }

  pollTimer = setInterval(loadCurrentQuestion, 10000)
  leaderboardTimer = setInterval(loadLeaderboard, 15000)
})

onBeforeUnmount(() => {
  audioService.stopAll()
  clearInterval(pollTimer)
  clearInterval(leaderboardTimer)
  leaveRealtime()
})
</script>
