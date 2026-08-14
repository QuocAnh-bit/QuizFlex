<template>
  <section class="grid gap-6 py-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <router-link class="btn-ghost" to="/live-rooms">Phòng thi đấu</router-link>
      <button class="btn-ghost" type="button" :disabled="isLoading" @click="loadLeaderboard">
        {{ isLoading ? 'Đang tải...' : 'Tải lại' }}
      </button>
    </div>

    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)]">
      <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Leaderboard</p>
      <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Bảng xếp hạng thi đấu</h1>
      <p class="mt-3 text-sm leading-7 text-[var(--muted)]">Chỉ xếp hạng người chơi đã tham gia phòng thi đấu. Chủ phòng không nằm trong bảng này.</p>
    </article>

    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

    <div v-if="isBanned" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">
      Phòng đã bị quản trị viên khóa và hiện không thể sử dụng.
    </div>

    <div v-if="leaderboard.length">
      <!-- Bục danh dự (Podium) cho Top 3 -->
      <div class="podium-container flex flex-col md:flex-row items-end justify-center gap-6 mb-8 mt-6">
        <!-- Hạng 2 (Silver) -->
        <div v-if="leaderboard.length > 1" class="podium-step podium-silver flex flex-col items-center p-6 rounded-3xl border border-slate-300/30 bg-slate-400/5 shadow-[0_0_20px_rgba(203,213,225,0.08)] w-full md:w-64 order-2 md:order-1" style="min-height: 280px; justify-content: flex-end;">
          <div class="flex flex-col items-center text-center w-full mb-auto">
            <span class="text-4xl mb-2 select-none">🥈</span>
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full font-black text-white text-xl mb-3 shadow-[0_0_15px_rgba(203,213,225,0.25)] border-2 border-slate-300"
                 :style="getAvatarStyle(leaderboard[1].user?.name || leaderboard[1].user_id)">
              {{ getAvatarInitial(leaderboard[1].user?.name || leaderboard[1].user_id) }}
            </div>
            <h3 class="text-lg font-black text-[var(--text)] truncate max-w-[200px]">{{ leaderboard[1].user?.name || `User #${leaderboard[1].user_id}` }}</h3>
            <p class="text-xs text-[var(--muted)] truncate max-w-[200px]">{{ leaderboard[1].user?.email || 'Chưa có email' }}</p>
          </div>
          <div class="w-full text-center mt-3 pt-3 border-t border-[var(--border)]">
            <span class="text-2xl font-black text-[var(--text)]">{{ leaderboard[1].score }}</span>
            <p class="text-xs font-bold text-slate-300 mt-1">Đúng {{ leaderboard[1].correct_count }}/{{ leaderboard[1].total_questions }} câu</p>
          </div>
        </div>

        <!-- Hạng 1 (Gold) -->
        <div v-if="leaderboard.length > 0" class="podium-step podium-gold relative flex flex-col items-center p-6 rounded-3xl border border-yellow-500/40 bg-yellow-500/5 shadow-[0_0_30px_rgba(234,179,8,0.18)] w-full md:w-72 order-1 md:order-2" style="min-height: 340px; justify-content: flex-end;">
          <div class="absolute -top-6 text-4xl select-none animate-bounce">👑</div>
          <div class="flex flex-col items-center text-center w-full mb-auto">
            <span class="text-4xl mb-2 select-none">🥇</span>
            <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full font-black text-white text-2xl mb-3 shadow-[0_0_20px_rgba(234,179,8,0.35)] border-2 border-yellow-400"
                 :style="getAvatarStyle(leaderboard[0].user?.name || leaderboard[0].user_id)">
              {{ getAvatarInitial(leaderboard[0].user?.name || leaderboard[0].user_id) }}
            </div>
            <h3 class="text-xl font-black text-[var(--text)] truncate max-w-[220px]">{{ leaderboard[0].user?.name || `User #${leaderboard[0].user_id}` }}</h3>
            <p class="text-xs text-[var(--muted)] truncate max-w-[220px]">{{ leaderboard[0].user?.email || 'Chưa có email' }}</p>
          </div>
          <div class="w-full text-center mt-3 pt-3 border-t border-[var(--border)]">
            <span class="text-3xl font-black text-yellow-400">{{ leaderboard[0].score }}</span>
            <p class="text-xs font-bold text-yellow-300 mt-1">Đúng {{ leaderboard[0].correct_count }}/{{ leaderboard[0].total_questions }} câu</p>
          </div>
        </div>

        <!-- Hạng 3 (Bronze) -->
        <div v-if="leaderboard.length > 2" class="podium-step podium-bronze flex flex-col items-center p-6 rounded-3xl border border-amber-600/30 bg-amber-700/5 shadow-[0_0_20px_rgba(180,83,9,0.08)] w-full md:w-64 order-3 md:order-3" style="min-height: 250px; justify-content: flex-end;">
          <div class="flex flex-col items-center text-center w-full mb-auto">
            <span class="text-4xl mb-2 select-none">🥉</span>
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full font-black text-white text-base mb-3 shadow-[0_0_15px_rgba(180,83,9,0.25)] border-2 border-amber-600"
                 :style="getAvatarStyle(leaderboard[2].user?.name || leaderboard[2].user_id)">
              {{ getAvatarInitial(leaderboard[2].user?.name || leaderboard[2].user_id) }}
            </div>
            <h3 class="text-base font-black text-[var(--text)] truncate max-w-[200px]">{{ leaderboard[2].user?.name || `User #${leaderboard[2].user_id}` }}</h3>
            <p class="text-xs text-[var(--muted)] truncate max-w-[200px]">{{ leaderboard[2].user?.email || 'Chưa có email' }}</p>
          </div>
          <div class="w-full text-center mt-3 pt-3 border-t border-[var(--border)]">
            <span class="text-xl font-black text-[var(--text)]">{{ leaderboard[2].score }}</span>
            <p class="text-xs font-bold text-amber-500 mt-1">Đúng {{ leaderboard[2].correct_count }}/{{ leaderboard[2].total_questions }} câu</p>
          </div>
        </div>
      </div>

      <!-- Danh sách từ Hạng 4 trở xuống -->
      <article v-if="leaderboard.length > 3" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <h2 class="text-xl font-black mb-4 text-[var(--text)]">Các thứ hạng tiếp theo</h2>
        
        <div class="overflow-hidden rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)]">
          <div class="hidden grid-cols-[100px_1fr_120px_130px_150px_160px] gap-3 border-b border-[var(--border)] bg-[var(--surface-soft)] px-6 py-3 text-xs font-black uppercase tracking-[0.16em] text-[var(--muted)] lg:grid">
            <span>Thứ hạng</span>
            <span>Người chơi</span>
            <span>Điểm số</span>
            <span>Số câu đúng</span>
            <span>Tiến độ</span>
            <span>Trạng thái</span>
          </div>

          <div
            v-for="entry in leaderboard.slice(3)"
            :key="entry.user_id"
            class="grid gap-3 border-b border-[var(--border)] px-6 py-4 last:border-b-0 lg:grid-cols-[100px_1fr_120px_130px_150px_160px] lg:items-center"
          >
            <b class="text-lg font-black text-[var(--muted)]">#{{ entry.rank }}</b>
            
            <div class="flex items-center gap-3">
              <!-- Avatar Gradient -->
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full font-black text-white text-xs"
                   :style="getAvatarStyle(entry.user?.name || entry.user_id)">
                {{ getAvatarInitial(entry.user?.name || entry.user_id) }}
              </div>
              <div>
                <h3 class="font-black text-sm text-[var(--text)] leading-none">{{ entry.user?.name || `User #${entry.user_id}` }}</h3>
                <p class="mt-1 text-xs text-[var(--muted)]">{{ entry.user?.email || 'Chưa có email' }}</p>
              </div>
            </div>

            <p class="text-sm font-black text-[var(--text)]">{{ entry.score }}</p>
            <p class="text-sm font-bold text-emerald-400">✔ {{ entry.correct_count }} câu</p>
            
            <div>
              <p class="text-xs font-bold text-[var(--text)] mb-1">{{ entry.answered_count }}/{{ entry.total_questions }} câu</p>
              <div class="h-1.5 w-24 overflow-hidden rounded-full bg-[var(--surface)]">
                <div class="h-full rounded-full bg-gradient-to-r from-[var(--primary)] to-[var(--primary-2)] transition-all duration-500" 
                     :style="{ width: `${entry.total_questions ? (entry.answered_count / entry.total_questions) * 100 : 0}%` }">
                </div>
              </div>
            </div>

            <div class="flex items-center justify-between lg:justify-start">
              <StatusBadge :value="entry.is_finished ? 'finished' : 'playing'" :label="entry.is_finished ? 'Hoàn thành' : 'Đang chơi'" />
            </div>
          </div>
        </div>
      </article>
    </div>

    <div v-else class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center shadow-[var(--shadow-card)]">
      <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Empty</p>
      <h2 class="mt-2 text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Chưa có người chơi</h2>
      <p class="mt-3 text-sm leading-7 text-[var(--muted)]">Leaderboard sẽ xuất hiện khi có player tham gia live room.</p>
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
const leaderboard = ref([])
const liveRoom = ref(null)
const isLoading = ref(false)
const errorMessage = ref('')
const lastRealtimeAt = ref(0)
const isBanned = computed(() => liveRoom.value?.status === 'banned' || errorMessage.value.includes('khóa bởi quản trị viên'))
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

const formatDateTime = (value) => {
  if (!value) return '-'
  return new Date(value).toLocaleString('vi-VN')
}

const markRealtime = () => {
  lastRealtimeAt.value = Date.now()
}

const hasRecentRealtime = () => Date.now() - lastRealtimeAt.value < realtimeFreshMs

const loadLeaderboard = async (force = false) => {
  if (!force && hasRecentRealtime()) return

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
  markRealtime()
  if (Array.isArray(event?.leaderboard)) {
    leaderboard.value = event.leaderboard
    errorMessage.value = ''
    return
  }

  loadLeaderboard(true)
}

const subscribeToRealtime = () => {
  try {
    liveChannel = getEcho().join(`live-room.${liveRoomId.value}`)
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
  try {
    liveRoom.value = await liveRoomApi.getLiveRoom(liveRoomId.value)
  } catch (error) {
    if (error.response?.status === 403) {
      errorMessage.value = error.message || 'Phòng trực tuyến này đã bị khóa.'
    }
  }
  await loadLeaderboard(true)
  pollTimer = setInterval(loadLeaderboard, 15000)
})

onBeforeUnmount(() => {
  clearInterval(pollTimer)
  leaveRealtime()
})
</script>

<style scoped>
.podium-container {
  perspective: 1000px;
}

.podium-step {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.podium-step:hover {
  transform: translateY(-8px) scale(1.02);
}

.podium-gold {
  background: linear-gradient(135deg, rgba(234, 179, 8, 0.08), rgba(234, 179, 8, 0.02)) !important;
  border-color: rgba(234, 179, 8, 0.4) !important;
  box-shadow: 0 10px 30px rgba(234, 179, 8, 0.15);
}

.podium-silver {
  background: linear-gradient(135deg, rgba(203, 213, 225, 0.08), rgba(203, 213, 225, 0.02)) !important;
  border-color: rgba(203, 213, 225, 0.3) !important;
  box-shadow: 0 10px 30px rgba(203, 213, 225, 0.1);
}

.podium-bronze {
  background: linear-gradient(135deg, rgba(180, 83, 9, 0.08), rgba(180, 83, 9, 0.02)) !important;
  border-color: rgba(180, 83, 9, 0.3) !important;
  box-shadow: 0 10px 30px rgba(180, 83, 9, 0.1);
}

@keyframes bounce {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-6px);
  }
}

.animate-bounce {
  animation: bounce 2s infinite ease-in-out;
}
</style>
