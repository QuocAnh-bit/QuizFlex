<template>
  <section class="grid gap-6 py-8">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl sm:p-8">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">My Rooms</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)] sm:text-5xl">Room được mời và quản lý</h1>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Room là nơi chứa Homework và Live Quiz. Practice vẫn nằm riêng ở thư viện đề public.</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <router-link class="btn-ghost" to="/join-room">Join room</router-link>
          <button v-if="canManageRooms" class="btn-primary" type="button" @click="isCreateOpen = !isCreateOpen">{{ isCreateOpen ? 'Đóng form' : 'Tạo Room' }}</button>
          <button v-else class="btn-primary" type="button" @click="requireUpgrade">Tạo Room</button>
        </div>
      </div>
    </article>

    <form v-if="canManageRooms && isCreateOpen" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]" @submit.prevent="createRoom">
      <div class="mb-5">
        <p class="text-xs font-black uppercase tracking-[0.18em] text-[var(--primary)]">Room Management</p>
        <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Tạo room mới</h2>
      </div>
      <div class="grid gap-4 lg:grid-cols-2">
        <label class="grid gap-2 text-sm font-black text-[var(--text)]">
          Tên room
          <input v-model="form.name" class="field" placeholder="Ví dụ: Lớp Sinh 10A" />
        </label>
        <label class="grid gap-2 text-sm font-black text-[var(--text)]">
          Loại room
          <select v-model="form.type" class="field">
            <option value="homework">Homework</option>
            <option value="live">Live</option>
          </select>
        </label>
        <label class="grid gap-2 text-sm font-black text-[var(--text)] lg:col-span-2">
          Mô tả
          <textarea v-model="form.description" class="field min-h-24" placeholder="Mô tả ngắn cho học sinh"></textarea>
        </label>
        <label class="grid gap-2 text-sm font-black text-[var(--text)]">
          Số thành viên tối đa
          <input v-model.number="form.max_players" class="field" min="1" max="500" type="number" />
        </label>
      </div>
      <div class="mt-5 flex flex-wrap items-center gap-3">
        <button class="btn-primary disabled:cursor-not-allowed disabled:opacity-60" type="submit" :disabled="isSaving">{{ isSaving ? 'Đang tạo...' : 'Lưu room' }}</button>
        <p v-if="createMessage" class="text-sm font-bold text-emerald-300">{{ createMessage }}</p>
      </div>
    </form>

    <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">Đang tải room...</div>
    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

    <div v-if="!isLoading && rooms.length" class="grid gap-4 lg:grid-cols-2">
      <article
        v-for="room in rooms"
        :key="room.id"
        class="cursor-pointer rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] transition hover:-translate-y-1 hover:border-[var(--border-strong)]"
        role="button"
        tabindex="0"
        @click="goToRoom(room.id)"
        @keyup.enter="goToRoom(room.id)"
      >
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <span class="rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--primary)]">{{ room.type || 'homework' }}</span>
            <h2 class="mt-3 text-2xl font-black text-[var(--text)]">{{ room.name || `Room #${room.id}` }}</h2>
            <p class="mt-2 line-clamp-2 text-sm leading-6 text-[var(--muted)]">{{ room.description || 'Chưa có mô tả.' }}</p>
          </div>
          <span class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-3 text-sm font-black text-[var(--text)]">{{ room.code }}</span>
        </div>
        <div class="mt-5 grid gap-3 sm:grid-cols-3">
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Thành viên</p>
            <b class="mt-1 block text-sm text-[var(--text)]">{{ room.members_count ?? '-' }}</b>
          </div>
          <div v-if="room.type !== 'live'" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Homework</p>
            <b class="mt-1 block text-sm text-[var(--text)]">{{ room.assignments_count ?? '-' }}</b>
          </div>
          <div v-else class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Live Quiz</p>
            <b class="mt-1 block text-sm text-[var(--text)]">Realtime</b>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Trạng thái</p>
            <b class="mt-1 block text-sm text-[var(--text)]">{{ room.status || 'waiting' }}</b>
          </div>
        </div>
        <div class="mt-5 flex justify-end">
          <button class="btn-primary px-4 py-2 text-xs" type="button" @click.stop="goToRoom(room.id)">Vào room</button>
        </div>
      </article>
    </div>

    <div v-if="!isLoading && !rooms.length && !errorMessage" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center shadow-[var(--shadow-card)]">
      <h3 class="text-2xl font-black text-[var(--text)]">Chưa có room</h3>
      <p class="mt-2 text-sm text-[var(--muted)]">Join room bằng mã giáo viên gửi hoặc tạo room nếu tài khoản của bạn là VIP.</p>
      <div class="mt-5 flex flex-wrap justify-center gap-3">
        <router-link class="btn-ghost" to="/join-room">Join room</router-link>
        <button class="btn-primary" type="button" @click="canManageRooms ? isCreateOpen = true : requireUpgrade()">Tạo Room</button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { authApi, currentUserStorage, roomsApi } from '@/services/api'

const router = useRouter()
const rooms = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const isCreateOpen = ref(false)
const errorMessage = ref('')
const createMessage = ref('')

const form = reactive({
  name: '',
  description: '',
  type: 'homework',
  max_players: 50,
})

const currentUser = computed(() => currentUserStorage.get() || {})
const canManageRooms = computed(() => ['vip', 'admin'].includes(String(currentUser.value.role || '').toLowerCase()))

const requireUpgrade = () => {
  window.alert('Tạo Room, Giao Homework và Live Quiz là tính năng VIP. Vui lòng nâng cấp để sử dụng.')
  router.push('/upgrade')
}

const goToRoom = (roomId) => {
  const target = router.resolve({
    name: 'room-detail',
    params: { roomId: String(roomId) },
  }).href
  window.location.assign(target)
}

const loadRooms = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    if (localStorage.getItem('mock_user_id')) {
      await authApi.me().catch(() => null)
    }
    rooms.value = await roomsApi.list()
  } catch (error) {
    if (error.status === 401) {
      errorMessage.value = 'Bạn cần đăng nhập để xem danh sách room.'
      return
    }
    rooms.value = []
    errorMessage.value = `Không tải được room: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

const createRoom = async () => {
  if (!canManageRooms.value) {
    requireUpgrade()
    return
  }

  if (!form.name.trim()) {
    errorMessage.value = 'Bạn chưa nhập tên room.'
    return
  }

  isSaving.value = true
  errorMessage.value = ''
  createMessage.value = ''

  try {
    const room = await roomsApi.create({
      name: form.name.trim(),
      description: form.description.trim() || null,
      type: form.type,
      max_players: form.max_players || 50,
    })
    createMessage.value = 'Tạo room thành công.'
    form.name = ''
    form.description = ''
    form.type = 'homework'
    form.max_players = 50
    isCreateOpen.value = false
    await loadRooms()
    if (room?.id) router.push(`/rooms/${room.id}`)
  } catch (error) {
    errorMessage.value = `Không tạo được room: ${error.message}`
  } finally {
    isSaving.value = false
  }
}

onMounted(loadRooms)
</script>
