<template>
  <section class="max-w-5xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-amber-600">Đấu trường trực tiếp</p>
        <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Phòng thi đấu</h1>
        <p class="mt-1 text-sm text-slate-600">Tổ chức đấu trường thời gian thực kiểu Kahoot, mời bạn bè qua mã phòng và cùng tranh tài.</p>
      </div>
      <div class="flex flex-wrap gap-2.5">
        <router-link class="btn-secondary text-xs px-4 py-2" to="/live-rooms/join">
          Tham gia bằng mã
        </router-link>
        <router-link v-if="canCreateLiveRoom" class="btn-primary text-xs px-4 py-2" to="/live-rooms/create">
          + Tạo phòng mới
        </router-link>
        <router-link v-else class="btn-primary text-xs px-4 py-2" to="/upgrade">
          👑 Nâng cấp tài khoản
        </router-link>
      </div>
    </div>

    <!-- Skeleton Loading -->
    <div v-if="isLoading && !rooms.length" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <div v-for="i in 3" :key="i" class="card p-5 animate-pulse space-y-4">
        <div class="h-5 w-2/3 bg-slate-200 rounded"></div>
        <div class="h-4 w-full bg-slate-100 rounded"></div>
        <div class="grid grid-cols-2 gap-2 pt-2">
          <div class="h-10 bg-slate-100 rounded"></div>
          <div class="h-10 bg-slate-100 rounded"></div>
        </div>
      </div>
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700">
      {{ errorMessage }}
    </div>

    <!-- Rooms Grid -->
    <div v-if="rooms.length" class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
      <article
        v-for="room in rooms"
        :key="room.id"
        class="card p-5 card-hover flex flex-col justify-between space-y-4"
      >
        <div class="space-y-2">
          <div class="flex items-start justify-between gap-2">
            <h2 class="text-base font-bold text-slate-900 line-clamp-1">{{ room.name || 'Phòng thi đấu' }}</h2>
            <span v-if="room.code || room.pin" class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 shrink-0">
              {{ room.code || room.pin }}
            </span>
          </div>
          <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
            {{ room.description || 'Phòng thi đấu trực tuyến' }}
          </p>
        </div>

        <div class="space-y-3 pt-2 border-t border-slate-100">
          <div class="grid grid-cols-2 gap-2 text-center text-xs">
            <div class="rounded-lg bg-slate-50 p-2">
              <span class="text-[10px] font-semibold text-slate-400 block uppercase">Người chơi</span>
              <b class="text-slate-900 font-bold mt-0.5 block">{{ room.members_count ?? 0 }}</b>
            </div>
            <div class="rounded-lg bg-slate-50 p-2">
              <span class="text-[10px] font-semibold text-slate-400 block uppercase">Vai trò</span>
              <b class="text-slate-900 font-bold mt-0.5 block">{{ roleForRoom(room) }}</b>
            </div>
          </div>

          <div class="flex items-center justify-between gap-2 pt-1">
            <StatusBadge :value="room.status || 'waiting'" />
            <router-link
              class="btn-primary text-xs px-3.5 py-1.5"
              :to="room.is_host ? `/live-rooms/${room.id}/host` : `/live-rooms/${room.id}/play`"
            >
              Vào phòng →
            </router-link>
          </div>
        </div>
      </article>
    </div>

    <!-- Empty State -->
    <article v-if="!isLoading && !rooms.length && !errorMessage" class="card p-12 text-center text-slate-500 space-y-3">
      <span class="text-4xl block">🏆</span>
      <h2 class="text-xl font-bold text-slate-800">Chưa có phòng thi đấu nào</h2>
      <p class="text-xs max-w-sm mx-auto">Tạo phòng thi đấu mới từ bộ quiz của bạn hoặc nhập mã tham gia phòng từ giáo viên hoặc bạn bè.</p>
      <div class="flex justify-center gap-2 pt-2">
        <router-link class="btn-secondary text-xs" to="/live-rooms/join">Tham gia bằng mã</router-link>
        <router-link v-if="canCreateLiveRoom" class="btn-primary text-xs" to="/live-rooms/create">Tạo phòng</router-link>
        <router-link v-else class="btn-primary text-xs" to="/upgrade">Nâng cấp tài khoản</router-link>
      </div>
    </article>
  </section>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { currentUserStorage, liveRoomsApi } from '@/services/api'

const rooms = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const currentUser = currentUserStorage.get()

const canCreateLiveRoom = computed(() => {
  const role = String(currentUser?.role || 'free').toLowerCase()
  return ['admin', 'plus', 'pro', 'ultra'].includes(role)
})

const roleForRoom = (room) => {
  if (room.is_host || Number(room.host_id) === Number(currentUser?.id)) return 'Chủ phòng'
  return room.role || 'Người chơi'
}

const loadRooms = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    rooms.value = await liveRoomsApi.getLiveRooms()
  } catch (error) {
    errorMessage.value = `Không tải được danh sách phòng: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

onMounted(loadRooms)
</script>
