<template>
  <section class="grid gap-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--accent)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Room Management</p><h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Room quiz</h1><p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Danh sách room lấy từ các quiz có room_code trong backend.</p></div>
        <router-link class="btn-primary" to="/admin/questions/create">Tạo group quiz</router-link>
      </div>
    </div>

    <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">Đang tải room...</div>
    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

    <div class="grid gap-4 md:grid-cols-3">
      <router-link v-for="room in rooms" :key="room.id" :to="`/quizzes/${room.id}`" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-4 text-left shadow-[var(--shadow-card)] transition duration-300 hover:-translate-y-1 hover:border-[var(--border-strong)]">
        <div class="flex items-center justify-between gap-3"><b class="text-[var(--text)]">{{ room.title }}</b><span class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--primary)]">{{ room.roomCode }}</span></div>
        <p class="mt-2 text-sm text-[var(--muted)]">{{ room.questions }} câu • {{ room.attempts }} lượt làm • {{ room.duration }}</p>
      </router-link>
    </div>

    <div v-if="!isLoading && rooms.length === 0" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">Chưa có group quiz nào.</div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { normalizeQuizCard, quizzesApi } from '@/services/api'
const rooms = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const loadRooms = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const data = await quizzesApi.list({ visibility: 'group', per_page: 100 })
    rooms.value = data.map(normalizeQuizCard)
  } catch (error) {
    errorMessage.value = `Không tải được room: ${error.message}`
  } finally {
    isLoading.value = false
  }
}
onMounted(loadRooms)
</script>
