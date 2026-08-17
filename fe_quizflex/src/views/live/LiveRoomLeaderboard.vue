<template>
  <section class="max-w-5xl mx-auto py-4 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <router-link class="btn-secondary text-xs" to="/live-rooms">← Quay lại phòng thi đấu</router-link>
      <button class="btn-secondary text-xs px-3.5 py-1.5" type="button" :disabled="isLoading" @click="loadLeaderboard">
        {{ isLoading ? 'Đang tải...' : '🔄 Cập nhật' }}
      </button>
    </div>

    <!-- Header Card -->
    <div class="card p-6 sm:p-8 space-y-2">
      <span class="rounded-full bg-amber-50 border border-amber-200 px-3 py-0.5 text-xs font-bold text-amber-700">
        Kết quả trực tiếp
      </span>
      <h1 class="text-2xl sm:text-3xl font-black text-slate-900 pt-1">Bảng xếp hạng thi đấu</h1>
      <p class="text-xs text-slate-600">Thứ hạng và điểm số của tất cả người chơi trong phòng thi đấu này.</p>
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700">{{ errorMessage }}</div>

    <div v-if="isBanned" class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700">
      Phòng đã bị quản trị viên khóa và hiện không thể sử dụng.
    </div>

    <div v-if="leaderboard.length" class="space-y-6">
      <!-- Clean Podium Top 3 -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end pt-4">
        <!-- 2nd Place (Silver) -->
        <div v-if="leaderboard.length > 1" class="card p-6 text-center border-slate-300 bg-slate-50/50 order-2 md:order-1 space-y-3">
          <span class="text-3xl block">🥈</span>
          <div class="h-14 w-14 rounded-full bg-slate-200 text-slate-700 font-bold flex items-center justify-center mx-auto text-base">
            {{ getAvatarInitial(leaderboard[1].user?.name || leaderboard[1].user_id) }}
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-sm truncate">{{ leaderboard[1].user?.name || `User #${leaderboard[1].user_id}` }}</h3>
            <p class="text-[11px] text-slate-400 truncate">{{ leaderboard[1].user?.email || '-' }}</p>
          </div>
          <div class="pt-2 border-t border-slate-200">
            <span class="text-2xl font-black text-slate-800">{{ leaderboard[1].score }}đ</span>
            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Đúng {{ leaderboard[1].correct_count }}/{{ leaderboard[1].total_questions }} câu</p>
          </div>
        </div>

        <!-- 1st Place (Gold) -->
        <div v-if="leaderboard.length > 0" class="card p-6 text-center border-2 border-amber-400 bg-amber-50/40 order-1 md:order-2 space-y-3 relative shadow-md">
          <span class="text-4xl block">👑 🥇</span>
          <div class="h-16 w-16 rounded-full bg-amber-400 text-white font-bold flex items-center justify-center mx-auto text-xl shadow-sm">
            {{ getAvatarInitial(leaderboard[0].user?.name || leaderboard[0].user_id) }}
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-base truncate">{{ leaderboard[0].user?.name || `User #${leaderboard[0].user_id}` }}</h3>
            <p class="text-xs text-slate-500 truncate">{{ leaderboard[0].user?.email || '-' }}</p>
          </div>
          <div class="pt-2 border-t border-amber-200">
            <span class="text-3xl font-black text-amber-600">{{ leaderboard[0].score }}đ</span>
            <p class="text-xs text-amber-800 font-bold mt-0.5">Đúng {{ leaderboard[0].correct_count }}/{{ leaderboard[0].total_questions }} câu</p>
          </div>
        </div>

        <!-- 3rd Place (Bronze) -->
        <div v-if="leaderboard.length > 2" class="card p-6 text-center border-amber-300 bg-amber-50/20 order-3 md:order-3 space-y-3">
          <span class="text-3xl block">🥉</span>
          <div class="h-14 w-14 rounded-full bg-amber-100 text-amber-800 font-bold flex items-center justify-center mx-auto text-base">
            {{ getAvatarInitial(leaderboard[2].user?.name || leaderboard[2].user_id) }}
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-sm truncate">{{ leaderboard[2].user?.name || `User #${leaderboard[2].user_id}` }}</h3>
            <p class="text-[11px] text-slate-400 truncate">{{ leaderboard[2].user?.email || '-' }}</p>
          </div>
          <div class="pt-2 border-t border-slate-200">
            <span class="text-2xl font-black text-amber-700">{{ leaderboard[2].score }}đ</span>
            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Đúng {{ leaderboard[2].correct_count }}/{{ leaderboard[2].total_questions }} câu</p>
          </div>
        </div>
      </div>

      <!-- Rest of leaderboard table -->
      <article v-if="leaderboard.length > 3" class="card overflow-hidden">
        <div class="p-5 border-b border-slate-100">
          <h2 class="text-sm font-bold text-slate-900">Các thứ hạng tiếp theo</h2>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50 text-slate-400 font-bold uppercase text-[10px]">
                <th class="py-3 px-4">Thứ hạng</th>
                <th class="py-3 px-4">Người chơi</th>
                <th class="py-3 px-4">Điểm số</th>
                <th class="py-3 px-4">Số câu đúng</th>
                <th class="py-3 px-4">Tiến độ</th>
                <th class="py-3 px-4">Trạng thái</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-for="entry in leaderboard.slice(3)" :key="entry.user_id" class="hover:bg-slate-50">
                <td class="py-3.5 px-4 font-bold text-slate-400">#{{ entry.rank }}</td>
                <td class="py-3.5 px-4">
                  <span class="font-bold text-slate-900 block truncate max-w-xs">{{ entry.user?.name || `User #${entry.user_id}` }}</span>
                  <span class="text-[10px] text-slate-400 block truncate max-w-xs">{{ entry.user?.email || '-' }}</span>
                </td>
                <td class="py-3.5 px-4 font-bold text-[#7C3AED]">{{ entry.score }}đ</td>
                <td class="py-3.5 px-4 text-emerald-700 font-semibold">✔ {{ entry.correct_count }} câu</td>
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-2">
                    <div class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-100">
                      <div class="h-full rounded-full bg-[#7C3AED]" :style="{ width: `${entry.total_questions ? (entry.answered_count / entry.total_questions) * 100 : 0}%` }"></div>
                    </div>
                    <span class="text-[10px] text-slate-500">{{ entry.answered_count }}/{{ entry.total_questions }}</span>
                  </div>
                </td>
                <td class="py-3.5 px-4">
                  <StatusBadge :value="entry.is_finished ? 'finished' : 'playing'" :label="entry.is_finished ? 'Hoàn thành' : 'Đang chơi'" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>

    <!-- Empty state -->
    <div v-else class="card p-10 text-center text-slate-400 text-xs">
      Chưa có người chơi tham gia.
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
