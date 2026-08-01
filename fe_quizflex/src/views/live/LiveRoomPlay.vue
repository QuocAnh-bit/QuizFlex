<template>
  <section class="grid gap-6 py-8 xl:grid-cols-[minmax(0,1fr)_360px]">
    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)]">
      <div v-if="errorMessage" class="mb-5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ errorMessage }}</div>
      
      <!-- Banner Phản hồi Kết quả câu trả lời -->
      <transition name="slide-up">
        <div v-if="lastAnswerResult" class="mb-5 overflow-hidden rounded-3xl border p-5 shadow-lg backdrop-blur-md transition-all duration-300" :class="lastAnswerResult.isCorrect ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-rose-500/30 bg-rose-500/10 text-rose-300'">
          <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl" :class="lastAnswerResult.isCorrect ? 'bg-emerald-500/20' : 'bg-rose-500/20'">
              <span class="text-2xl font-black">{{ lastAnswerResult.isCorrect ? '✔' : '✖' }}</span>
            </div>
            <div class="flex-grow">
              <h4 class="text-lg font-black leading-tight">{{ lastAnswerResult.isCorrect ? 'Chính xác!' : 'Sai rồi' }}</h4>
              <p class="mt-1 text-xs font-bold" :class="lastAnswerResult.isCorrect ? 'text-emerald-400' : 'text-rose-400'">{{ lastAnswerResult.rankMessage }}</p>
            </div>
            <div class="text-right">
              <span class="text-xl font-black" :class="lastAnswerResult.isCorrect ? 'text-emerald-400' : 'text-rose-400'">+{{ lastAnswerResult.scoreAwarded }} điểm</span>
            </div>
          </div>
        </div>
      </transition>

      <!-- Banner Banned cho Player -->
      <div v-if="isBanned" class="mb-5 rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-6 text-sm font-bold text-rose-300 flex items-center gap-3">
        <span>🚫</span>
        <span>Phòng đã bị quản trị viên khóa và hiện không thể sử dụng.</span>
      </div>

      <template v-else>
        <template v-if="isWaiting">
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Waiting</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Đang chờ host bắt đầu</h1>
          <p class="mt-3 text-sm leading-7 text-[var(--muted)]">Trang sẽ tự cập nhật mỗi 10 giây. Khi live bắt đầu, câu hỏi sẽ xuất hiện tại đây.</p>
        </template>
 
      <template v-if="isFinished">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Finished</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ roomStatus === 'finished' ? 'Live room đã kết thúc' : 'Bạn đã hoàn thành' }}</h1>
        <p class="mt-3 text-sm leading-7 text-[var(--muted)]">Điểm hiện tại: {{ progress.current_score ?? 0 }}, số câu đúng: {{ progress.correct_count ?? 0 }}/{{ progress.total_questions ?? 0 }}.</p>
        <router-link class="btn-primary mt-6 inline-flex" :to="`/live-rooms/${liveRoomId}/leaderboard`">Xem leaderboard</router-link>
      </template>
 
      <template v-else-if="question.id">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
          <div>
            <div class="mb-4 flex flex-wrap items-center gap-2">
              <span class="rounded-full bg-[var(--chip-active)] px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-[var(--primary)]">Câu {{ currentQuestionNumber }} / {{ progress.total_questions || 1 }}</span>
              <StatusBadge :value="roomStatus" />
            </div>
            <h1 class="max-w-4xl text-3xl font-black leading-tight tracking-[-0.055em] text-[var(--text)] sm:text-5xl">{{ question.question }}</h1>
          </div>
        </div>
 
        <div class="grid gap-4">
          <button
            v-for="answer in question.answers"
            :key="answer.id"
            type="button"
            class="group rounded-[1.35rem] border p-4 text-left transition duration-300 hover:-translate-y-1 active:scale-[0.99]"
            :class="selectedAnswerId === answer.id ? selectedAnswerClass : defaultAnswerClass"
            :disabled="isAnswering"
            @click="submitAnswer(answer.id)"
          >
            <div class="flex items-center gap-4">
              <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-[var(--primary)] to-[var(--primary-2)] text-sm font-black text-white">{{ answer.key }}</span>
              <span class="font-bold leading-7 text-[var(--text)]">{{ answer.text }}</span>
            </div>
          </button>
        </div>
      </template>
 
        <div v-else-if="!isFinished" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-8 text-center text-sm font-bold text-[var(--muted)]">
          Đang tải câu hỏi live...
        </div>
      </template>
    </article>
 
    <aside class="grid content-start gap-5">
      <!-- Card Thành tích cá nhân -->
      <article class="relative overflow-hidden rounded-[2rem] border bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] transition-all duration-300"
               :class="[
                 currentPlayerStats.rank === 1 ? 'border-yellow-500/40 shadow-[0_0_20px_rgba(234,179,8,0.15)]' :
                 currentPlayerStats.rank === 2 ? 'border-slate-300/40 shadow-[0_0_20px_rgba(203,213,225,0.12)]' :
                 currentPlayerStats.rank === 3 ? 'border-amber-600/40 shadow-[0_0_20px_rgba(180,83,9,0.12)]' :
                 'border-[var(--border-strong)] shadow-[var(--shadow-soft)]'
               ]">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Thành tích của bạn</p>
        
        <!-- Hiệu ứng điểm bay -->
        <transition name="float-points">
          <span v-if="floatingPoints" class="floating-points" :key="floatingPoints.id">
            {{ floatingPoints.amount }}
          </span>
        </transition>

        <div class="mt-4 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-4xl select-none">
              {{ currentPlayerStats.rank === 1 ? '🥇' : currentPlayerStats.rank === 2 ? '🥈' : currentPlayerStats.rank === 3 ? '🥉' : '🏆' }}
            </span>
            <div>
              <p class="text-xs font-bold text-[var(--muted)]">Thứ hạng</p>
              <h3 class="text-3xl font-black text-[var(--text)]">#{{ currentPlayerStats.rank }}</h3>
            </div>
          </div>
          <div class="text-right">
            <p class="text-xs font-bold text-[var(--muted)]">Tổng điểm</p>
            <h3 class="text-3xl font-black text-[var(--primary)]">{{ currentPlayerStats.score }}</h3>
          </div>
        </div>

        <!-- Khoảng cách điểm -->
        <div v-if="rankMessage" class="mt-4 rounded-xl bg-[var(--surface-soft)] p-3 text-xs font-bold text-[var(--text)] text-center border border-[var(--border)]">
          {{ rankMessage }}
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3">
          <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3">
            <p class="text-[10px] font-bold text-[var(--muted)] uppercase tracking-wider">Chính xác</p>
            <p class="mt-1 text-lg font-black text-emerald-400">✔ {{ currentPlayerStats.correct_count }}</p>
          </div>
          <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3">
            <p class="text-[10px] font-bold text-[var(--muted)] uppercase tracking-wider">Tiến trình</p>
            <p class="mt-1 text-lg font-black text-[var(--text)]">{{ currentPlayerStats.answered_count }}/{{ currentPlayerStats.total_questions }}</p>
          </div>
        </div>

        <!-- Thanh progress cá nhân -->
        <div class="mt-4">
          <div class="h-2 w-full overflow-hidden rounded-full bg-[var(--surface-soft)]">
            <div class="h-full rounded-full bg-gradient-to-r from-[var(--primary)] to-[var(--primary-2)] transition-all duration-500"
                 :style="{ width: `${currentPlayerStats.total_questions ? (currentPlayerStats.answered_count / currentPlayerStats.total_questions) * 100 : 0}%` }">
            </div>
          </div>
        </div>
      </article>

      <!-- Bảng xếp hạng Top 5 -->
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <div class="flex items-center justify-between">
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Bảng xếp hạng</p>
          <span class="rounded-full bg-violet-500/10 px-2 py-0.5 text-[10px] font-bold text-violet-400">Top 5</span>
        </div>

        <div v-if="leaderboard.length" class="mt-4 relative">
          <TransitionGroup name="leaderboard-list" tag="div" class="grid gap-2">
            <div
              v-for="entry in leaderboard.slice(0, 5)"
              :key="entry.user_id"
              class="flex items-center justify-between rounded-2xl border p-3 transition-all duration-300 hover:scale-[1.02]"
              :class="[
                Number(entry.user_id) === Number(currentUser?.id) ? 'border-[var(--primary)] bg-[var(--chip-active)]' : 'border-[var(--border)] bg-[var(--surface-soft)]',
                entry.rank === 1 ? 'border-yellow-500/30 bg-yellow-500/5' : entry.rank === 2 ? 'border-slate-300/30 bg-slate-400/5' : entry.rank === 3 ? 'border-amber-600/30 bg-amber-700/5' : ''
              ]"
            >
              <div class="flex items-center gap-3">
                <span class="w-6 text-center text-sm font-black">
                  {{ getRankIcon(entry.rank) || `#${entry.rank}` }}
                </span>
                
                <!-- Avatar Gradient đẹp mắt -->
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full font-black text-white text-xs"
                     :style="getAvatarStyle(entry.user?.name || entry.user_id)">
                  {{ getAvatarInitial(entry.user?.name || entry.user_id) }}
                </div>
                
                <span class="text-sm font-black text-[var(--text)] truncate max-w-[120px]"
                      :class="{ 'text-[var(--primary)]': Number(entry.user_id) === Number(currentUser?.id) }">
                  {{ entry.user?.name || `User #${entry.user_id}` }}
                </span>
              </div>
              <b class="text-sm font-black text-[var(--text)]">{{ entry.score }}</b>
            </div>
          </TransitionGroup>
        </div>
        <div v-else class="mt-4 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-center text-sm font-bold text-[var(--muted)]">
          Chưa có xếp hạng.
        </div>
      </article>
    </aside>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { getEcho } from '@/echo'
import { liveRoomApi, normalizeQuestion, currentUserStorage } from '@/services/api'
import audioService from '@/services/audioService'

const route = useRoute()
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

const selectedAnswerClass = ['border-[var(--border-strong)]', 'bg-[var(--chip-active)]']
const defaultAnswerClass = ['border-[var(--border)]', 'bg-[var(--surface-soft)]', 'hover:border-[var(--border-strong)]']
const isWaiting = computed(() => roomStatus.value === 'waiting')
const isFinished = computed(() => roomStatus.value === 'finished' || progress.value.player_finished || progress.value.is_finished)
const isBanned = computed(() => roomStatus.value === 'banned')
const currentQuestionNumber = computed(() => Number(progress.value.player_current_question_index ?? progress.value.current_question_index ?? 0) + 1)

const markRealtime = () => {
  lastRealtimeAt.value = Date.now()
}

const hasRecentRealtime = () => Date.now() - lastRealtimeAt.value < realtimeFreshMs

const loadLeaderboard = async (force = false) => {
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
    if (message.includes('Chưa trong trạng thái đang chơi') || message.includes('chưa trong trạng thái')) {
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

// const handleRoomStarted = async (event) => {
//   realtimeLog('live.room.started', event)
//   markRealtime()
// audioService.stopLobby()

// setTimeout(() => {
//   audioService.playCountdown()
// }, 3000)

//   roomStatus.value = event?.status || 'playing'
//   progress.value = {
//     ...progress.value,
//     total_questions: event?.total_questions ?? progress.value.total_questions,
//     player_current_question_index: progress.value.player_current_question_index ?? 0,
//     current_question_index: progress.value.current_question_index ?? 0,
//     player_finished: false,
//     is_finished: false,
//   }
//   if (Array.isArray(event?.leaderboard)) {
//     leaderboard.value = event.leaderboard
//   }

//   if (event?.current_question) {
//     question.value = normalizeQuestion(event.current_question)
//     selectedAnswerId.value = null
//     errorMessage.value = ''
//     return
//   }

//   await loadCurrentQuestion(true)
// }
const handleRoomStarted = async (event) => {
    realtimeLog('live.room.started', event)
        console.log("=== ROOM STARTED ===", event)

    audioService.stopLobby()

    roomStatus.value = event?.status || 'playing'

    await loadCurrentQuestion(true)
}

const handleRoomFinished = async (event) => {
  realtimeLog('live.room.finished', event)
  markRealtime()
console.log('Finish event', event)
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
  try {
    liveChannel = getEcho().private(`live-room.${liveRoomId.value}`)
    liveChannel
      .listen('.live.room.started', handleRoomStarted)
      .listen('.live.room.finished', handleRoomFinished)
      .listen('.live.leaderboard.updated', handleLeaderboardUpdated)
  } catch {
    liveChannel = null
  }
}

const leaveRealtime = () => {
  if (!liveChannel) return

  getEcho().leave(`live-room.${liveRoomId.value}`)
  liveChannel = null
}

const submitAnswer = async (answerId) => {
  if (isAnswering.value || isFinished.value || isWaiting.value) return
  selectedAnswerId.value = answerId
  isAnswering.value = true
  answerMessage.value = ''
  errorMessage.value = ''
  lastAnswerResult.value = null

  try {
    const oldRank = leaderboard.value.find(item => Number(item.user_id) === Number(currentUser.value?.id))?.rank || null
    const result = await liveRoomApi.answerLiveQuestion(liveRoomId.value, answerId)

    // Phát âm thanh
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
    
    // Phát nhạc khi người chơi hoàn thành
    if (result.player_finished || result.room_status === 'finished' || !result.has_next_question) {
      console.log('PLAY FINISH')
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
  audioService.playLobby()

  subscribeToRealtime()

  await loadCurrentQuestion(true)

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
  right: 25px;
  font-size: 1.5rem;
  font-weight: 900;
  color: var(--accent-2);
  animation: floatUpFade 1.4s forwards cubic-bezier(0.18, 0.89, 0.32, 1.28);
  pointer-events: none;
  z-index: 50;
  text-shadow: 0 4px 10px rgba(0,0,0,0.5);
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-up-enter-from {
  transform: translateY(20px);
  opacity: 0;
}
.slide-up-leave-to {
  transform: translateY(-20px);
  opacity: 0;
}
</style>
