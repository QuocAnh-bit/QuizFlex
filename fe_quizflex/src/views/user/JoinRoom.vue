<template>
  <section class="grid gap-6 py-8 lg:grid-cols-[1fr_380px]">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-8 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Join Room</p><h1 class="mt-2 text-5xl font-black tracking-[-0.07em] text-[var(--text)]">Tham gia phòng bằng mã</h1><p class="mt-4 max-w-2xl text-sm leading-7 text-[var(--muted)]">Nhập room code để tìm group quiz từ backend và bắt đầu làm bài.</p><div class="mt-8 rounded-[2rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5"><input v-model="roomCode" class="w-full bg-transparent text-center text-5xl font-black tracking-[0.22em] text-[var(--text)] outline-none" maxlength="12" /><button class="btn-primary mt-5 w-full" type="button" @click="joinRoom">Join room</button></div><div v-if="errorMessage" class="mt-5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ errorMessage }}</div></div>
    </article>
    <aside class="grid content-start gap-4"><div v-for="room in rooms" :key="room.id" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]"><div class="flex items-center justify-between"><b class="text-[var(--text)]">{{ room.title }}</b><span class="font-black text-[var(--primary)]">{{ room.roomCode }}</span></div><p class="mt-2 text-sm text-[var(--muted)]">{{ room.questions }} câu • {{ room.duration }}</p></div><div v-if="rooms.length === 0" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-5 text-sm font-bold text-[var(--muted)]">Chưa có room public trong backend.</div></aside>
  </section>
</template>
<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { normalizeQuizCard, quizzesApi } from '@/services/api'
const router = useRouter()
const roomCode = ref('')
const rooms = ref([])
const errorMessage = ref('')
const loadRooms = async () => {
  try {
    const data = await quizzesApi.list({ visibility: 'group', per_page: 20 })
    rooms.value = data.map(normalizeQuizCard)
  } catch {
    rooms.value = []
  }
}
const joinRoom = async () => {
  errorMessage.value = ''
  const code = roomCode.value.trim()
  if (!code) {
    errorMessage.value = 'Bạn chưa nhập room code.'
    return
  }
  const foundLocal = rooms.value.find((room) => room.roomCode?.toLowerCase() === code.toLowerCase())
  if (foundLocal) {
    router.push(`/quizzes/${foundLocal.id}/play`)
    return
  }
  try {
    const data = await quizzesApi.list({ search: code, visibility: 'group' })
    const found = data.map(normalizeQuizCard).find((room) => room.roomCode?.toLowerCase() === code.toLowerCase())
    if (!found) {
      errorMessage.value = 'Không tìm thấy room code này.'
      return
    }
    router.push(`/quizzes/${found.id}/play`)
  } catch (error) {
    errorMessage.value = `Không tìm được room: ${error.message}`
  }
}
onMounted(loadRooms)
</script>
