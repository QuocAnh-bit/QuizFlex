<template>
  <section class="grid gap-6 py-8 lg:grid-cols-[1fr_380px]">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-8 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Join Room</p>
        <h1 class="mt-2 text-5xl font-black tracking-[-0.07em] text-[var(--text)]">Tham gia phòng</h1>
        <p class="mt-4 max-w-2xl text-sm leading-7 text-[var(--muted)]">Nhập mã room được giáo viên gửi để xem danh sách homework và bắt đầu làm bài.</p>

        <form class="mt-8 rounded-[2rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5" @submit.prevent="joinRoom">
          <input
            v-model="roomCode"
            class="w-full bg-transparent text-center text-4xl font-black uppercase tracking-[0.18em] text-[var(--text)] outline-none placeholder:text-[var(--muted)] sm:text-5xl"
            maxlength="12"
            placeholder="ROOM"
            autocomplete="off"
          />
          <button class="btn-primary mt-5 w-full disabled:cursor-not-allowed disabled:opacity-60" type="submit" :disabled="isJoining">
            {{ isJoining ? 'Đang vào room...' : 'Vào room' }}
          </button>
        </form>

        <div v-if="errorMessage" class="mt-5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ errorMessage }}</div>
        <div v-if="successMessage" class="mt-5 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-300">{{ successMessage }}</div>
      </div>
    </article>

    <aside class="grid content-start gap-4">
      <article class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-5 text-sm font-bold text-[var(--muted)] shadow-[var(--shadow-card)]">
        Sau khi vào room, bạn sẽ thấy các homework đang mở, deadline và trạng thái làm bài của mình.
      </article>

      <article v-if="joinedRoom" class="rounded-[1.5rem] border border-[var(--border-strong)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
        <p class="text-xs font-black uppercase tracking-[0.18em] text-[var(--primary)]">Room vừa vào</p>
        <h2 class="mt-2 text-xl font-black text-[var(--text)]">{{ joinedRoom.name || `Room #${joinedRoom.id}` }}</h2>
        <p class="mt-2 text-sm leading-6 text-[var(--muted)]">{{ joinedRoom.description || 'Chưa có mô tả room.' }}</p>
        <router-link class="btn-ghost mt-4 w-full" :to="`/rooms/${joinedRoom.id}/homework`">Xem homework</router-link>
      </article>
    </aside>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { roomsApi } from '@/services/api'

const router = useRouter()
const roomCode = ref('')
const joinedRoom = ref(null)
const isJoining = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const normalizeJoinedRoom = (payload) => payload?.room || payload

const joinRoom = async () => {
  errorMessage.value = ''
  successMessage.value = ''
  joinedRoom.value = null

  const code = roomCode.value.trim().toUpperCase()
  if (!code) {
    errorMessage.value = 'Bạn chưa nhập mã room.'
    return
  }

  isJoining.value = true

  try {
    const payload = await roomsApi.join(code)
    const room = normalizeJoinedRoom(payload)
    if (!room?.id) {
      errorMessage.value = 'API join room không trả về room id.'
      return
    }

    joinedRoom.value = room
    successMessage.value = 'Tham gia room thành công.'
    router.push(`/rooms/${room.id}`)
  } catch (error) {
    if (error.status === 401) {
      errorMessage.value = 'Bạn cần đăng nhập trước khi tham gia room.'
      return
    }
    errorMessage.value = `Không vào được room: ${error.message}`
  } finally {
    isJoining.value = false
  }
}
</script>
