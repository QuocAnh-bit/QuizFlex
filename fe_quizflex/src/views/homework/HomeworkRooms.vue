<template>
  <section class="grid gap-6 py-8">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Phòng bài tập</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Phòng bài tập</h1>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Quản lý phòng giao quiz, tham gia bằng mã và theo dõi các bài được giao.</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <router-link class="btn-ghost" to="/homework-rooms/join">Tham gia bằng mã</router-link>
          <router-link v-if="canCreateHomeworkRoom" class="btn-primary" to="/homework-rooms/create">Tạo phòng</router-link>
          <router-link v-else class="btn-primary" to="/upgrade">Nâng cấp tài khoản</router-link>
        </div>
      </div>
    </article>

    <!-- Skeleton loading UI -->
    <div v-if="isLoading && !rooms.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <div v-for="i in 3" :key="i" class="animate-pulse rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
        <div class="flex items-start justify-between gap-3">
          <div class="w-2/3 space-y-2">
            <div class="h-6 w-3/4 rounded-lg bg-[var(--surface-soft)]"></div>
            <div class="h-4 w-full rounded-lg bg-[var(--surface-soft)]"></div>
          </div>
          <div class="h-6 w-16 rounded-full bg-[var(--surface-soft)]"></div>
        </div>
        <div class="mt-5 grid grid-cols-2 gap-3">
          <div class="h-14 rounded-2xl bg-[var(--surface-soft)]"></div>
          <div class="h-14 rounded-2xl bg-[var(--surface-soft)]"></div>
        </div>
        <div class="mt-5 flex items-center justify-between">
          <div class="h-6 w-20 rounded-full bg-[var(--surface-soft)]"></div>
          <div class="h-9 w-24 rounded-xl bg-[var(--surface-soft)]"></div>
        </div>
      </div>
    </div>

    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

    <div v-if="rooms.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <article v-for="room in rooms" :key="room.id" class="group rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] transition duration-300 hover:-translate-y-1 hover:border-[var(--border-strong)]">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h2 class="text-xl font-black text-[var(--text)]">{{ room.name || 'Phòng bài tập' }}</h2>
            <p class="mt-2 line-clamp-2 text-sm leading-6 text-[var(--muted)]">{{ room.description || 'Chưa có mô tả.' }}</p>
          </div>
          <span class="shrink-0 rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--primary)]">{{ room.code || 'NO CODE' }}</span>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3">
            <p class="text-xs font-bold text-[var(--muted)]">Thành viên</p>
            <p class="mt-1 text-lg font-black text-[var(--text)]">{{ room.members_count ?? '-' }}</p>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3">
            <p class="text-xs font-bold text-[var(--muted)]">Quyền</p>
            <p class="mt-1 text-lg font-black text-[var(--text)]">{{ roleForRoom(room) }}</p>
          </div>
        </div>

        <div class="mt-5 flex items-center justify-between gap-3">
          <StatusBadge :value="room.status || 'active'" />
          <router-link class="btn-ghost" :to="`/homework-rooms/${room.id}`">Vào phòng</router-link>
        </div>
      </article>
    </div>

    <article v-if="!isLoading && !rooms.length && !errorMessage" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center shadow-[var(--shadow-card)]">
      <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Empty</p>
      <h2 class="mt-2 text-3xl font-black tracking-[-0.05em] text-[var(--text)]">Chưa có phòng bài tập nào</h2>
      <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-[var(--muted)]">Tạo phòng mới hoặc tham gia phòng bằng mã được chia sẻ.</p>
      <div class="mt-6 flex justify-center gap-3">
        <router-link class="btn-ghost" to="/homework-rooms/join">Tham gia bằng mã</router-link>
        <router-link v-if="canCreateHomeworkRoom" class="btn-primary" to="/homework-rooms/create">Tạo phòng</router-link>
        <router-link v-else class="btn-primary" to="/upgrade">Nâng cấp tài khoản</router-link>
      </div>
    </article>
  </section>
</template>

<script setup>
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
  isLoading.value = true
  errorMessage.value = ''

  try {
    rooms.value = await homeworkApi.getHomeworkRooms()
  } catch (error) {
    errorMessage.value = `Không tải được Phòng bài tập: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

onMounted(loadRooms)
</script>
