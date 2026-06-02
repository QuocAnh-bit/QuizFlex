<template>
  <section class="grid gap-6 py-8 xl:grid-cols-[minmax(0,1fr)_360px]">
    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)]">
      <div v-if="errorMessage" class="mb-5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ errorMessage }}</div>
      <div v-if="answerMessage" class="mb-5 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 text-sm font-bold text-[var(--text)]">{{ answerMessage }}</div>

      <template v-if="isWaiting">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Waiting</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Đang chờ host bắt đầu</h1>
        <p class="mt-3 text-sm leading-7 text-[var(--muted)]">Trang sẽ tự cập nhật mỗi 10 giây. Khi live bắt đầu, câu hỏi sẽ xuất hiện tại đây.</p>
      </template>

      <template v-else-if="isFinished">
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

      <div v-else class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-8 text-center text-sm font-bold text-[var(--muted)]">
        Đang tải câu hỏi live...
      </div>
    </article>

    <aside class="grid content-start gap-5">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Progress</p>
        <div class="mt-5 grid gap-3">
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Điểm</p>
            <p class="mt-1 text-2xl font-black text-[var(--text)]">{{ progress.current_score ?? 0 }}</p>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Số câu đúng</p>
            <p class="mt-1 text-2xl font-black text-[var(--text)]">{{ progress.correct_count ?? 0 }}</p>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Đã trả lời</p>
            <p class="mt-1 text-2xl font-black text-[var(--text)]">{{ progress.answered_count ?? 0 }}/{{ progress.total_questions ?? 0 }}</p>
          </div>
        </div>
      </article>

      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Leaderboard</p>
        <div v-if="leaderboard.length" class="mt-4 grid gap-2">
          <div v-for="entry in leaderboard.slice(0, 5)" :key="entry.user_id" class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3">
            <span class="text-sm font-black text-[var(--text)]">#{{ entry.rank }} {{ entry.user?.name || `User #${entry.user_id}` }}</span>
            <b class="text-[var(--primary)]">{{ entry.score }}</b>
          </div>
        </div>
        <div v-else class="mt-4 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-center text-sm font-bold text-[var(--muted)]">Chưa có leaderboard.</div>
      </article>
    </aside>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { getEcho } from '@/echo'
import { liveRoomApi, normalizeQuestion } from '@/services/api'

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
let pollTimer = null
let leaderboardTimer = null
let liveChannel = null
const realtimeFreshMs = 8000

const selectedAnswerClass = ['border-[var(--border-strong)]', 'bg-[var(--chip-active)]']
const defaultAnswerClass = ['border-[var(--border)]', 'bg-[var(--surface-soft)]', 'hover:border-[var(--border-strong)]']
const isWaiting = computed(() => roomStatus.value === 'waiting')
const isFinished = computed(() => roomStatus.value === 'finished' || progress.value.player_finished || progress.value.is_finished)
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
    if (message.includes('chua trong trang thai dang choi') || message.includes('chưa trong trạng thái')) {
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
  markRealtime()
  roomStatus.value = event?.status || 'playing'
  progress.value = {
    ...progress.value,
    total_questions: event?.total_questions ?? progress.value.total_questions,
    player_current_question_index: progress.value.player_current_question_index ?? 0,
    current_question_index: progress.value.current_question_index ?? 0,
    player_finished: false,
    is_finished: false,
  }
  if (Array.isArray(event?.leaderboard)) {
    leaderboard.value = event.leaderboard
  }

  if (event?.current_question) {
    question.value = normalizeQuestion(event.current_question)
    selectedAnswerId.value = null
    errorMessage.value = ''
    return
  }

  await loadCurrentQuestion(true)
}

const handleRoomFinished = async (event) => {
  realtimeLog('live.room.finished', event)
  markRealtime()
  roomStatus.value = event?.status || 'finished'
  question.value = { id: 0, question: '', answers: [] }
  answerMessage.value = event?.message || 'Live room da ket thuc.'
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

  try {
    const result = await liveRoomApi.answerLiveQuestion(liveRoomId.value, answerId)
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
    applyLeaderboardPayload(result.leaderboard)
    markRealtime()

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
  subscribeToRealtime()
  await loadCurrentQuestion(true)
  pollTimer = setInterval(loadCurrentQuestion, 10000)
  leaderboardTimer = setInterval(loadLeaderboard, 15000)
})

onBeforeUnmount(() => {
  clearInterval(pollTimer)
  clearInterval(leaderboardTimer)
  leaveRealtime()
})
</script>
