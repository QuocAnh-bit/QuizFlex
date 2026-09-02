<template>
  <section class="max-w-5xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Học tập theo nhóm</p>
        <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Phòng bài tập</h1>
        <p class="mt-1 text-sm text-slate-600">Quản lý lớp học, làm bài tập được giao và theo dõi tiến độ chi tiết.</p>
      </div>
      <div class="flex flex-wrap gap-2.5">
        <router-link class="btn-secondary text-xs px-4 py-2" to="/homework-rooms/join">
          Tham gia bằng mã
        </router-link>
        <router-link v-if="canCreateHomeworkRoom" class="btn-primary text-xs px-4 py-2" to="/homework-rooms/create">
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
            <h2 class="text-base font-bold text-slate-900 line-clamp-1">{{ room.name || 'Phòng bài tập' }}</h2>
            <span v-if="room.code" class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 shrink-0">
              {{ room.code }}
            </span>
          </div>
          <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
            {{ room.description || 'Chưa có mô tả phòng.' }}
          </p>
        </div>

        <div class="space-y-3 pt-2 border-t border-slate-100">
          <div class="grid grid-cols-2 gap-2 text-center text-xs">
            <div class="rounded-lg bg-slate-50 p-2">
              <span class="text-[10px] font-semibold text-slate-400 block uppercase">Thành viên</span>
              <b class="text-slate-900 font-bold mt-0.5 block">{{ room.members_count ?? '-' }}</b>
            </div>
            <div class="rounded-lg bg-slate-50 p-2">
              <span class="text-[10px] font-semibold text-slate-400 block uppercase">Vai trò</span>
              <b class="text-slate-900 font-bold mt-0.5 block">{{ roleForRoom(room) }}</b>
            </div>
          </div>

          <div class="flex items-center justify-between gap-2 pt-1">
            <StatusBadge :value="room.status || 'active'" />
            <router-link class="btn-primary text-xs px-3.5 py-1.5" :to="`/homework-rooms/${room.id}`">
              Vào phòng →
            </router-link>
          </div>
        </div>
      </article>
    </div>

    <!-- Empty State -->
    <article v-if="!isLoading && !rooms.length && !errorMessage" class="card p-12 text-center text-slate-500 space-y-3">
      <span class="text-4xl block">📚</span>
      <h2 class="text-xl font-bold text-slate-800">Chưa có phòng bài tập nào</h2>
      <p class="text-xs max-w-sm mx-auto">Tạo phòng học mới hoặc nhập mã tham gia phòng được chia sẻ từ giáo viên.</p>
      <div class="flex justify-center gap-2 pt-2">
        <router-link class="btn-secondary text-xs" to="/homework-rooms/join">Tham gia bằng mã</router-link>
        <router-link v-if="canCreateHomeworkRoom" class="btn-primary text-xs" to="/homework-rooms/create">Tạo phòng</router-link>
        <router-link v-else class="btn-primary text-xs" to="/upgrade">Nâng cấp tài khoản</router-link>
      </div>
    </article>
  </section>
</template>

<script setup>
import { useAppLoading } from '@/composables/useAppLoading'
const { beginTask, endTask } = useAppLoading()

import { onMounted, ref, computed } from 'vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { currentUserStorage, homeworkApi } from '@/services/api'

const rooms = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const currentUser = currentUserStorage.get()

const canCreateHomeworkRoom = computed(() => {
  const role = String(currentUser?.role || 'free').toLowerCase()
  return ['admin', 'plus', 'pro', 'ultra'].includes(role)
})

const roleForRoom = (room) => {
  if (Number(room.host_id) === Number(currentUser?.id)) return 'Chủ phòng'
  return room.role || room.member_role || 'Thành viên'
}

const loadRooms = async () => {
    beginTask()
    try {
isLoading.value = true
  errorMessage.value = ''

  try {
    rooms.value = await homeworkApi.getHomeworkRooms()
  } catch (error) {
    errorMessage.value = `Không tải được danh sách phòng: ${error.message}`
  } finally {
    isLoading.value = false
  }
    } finally {
      endTask()
    }
  }

onMounted(loadRooms)
</script>
