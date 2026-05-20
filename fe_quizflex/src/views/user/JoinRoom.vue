<template>
  <section class="mx-auto max-w-2xl py-12">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-8 shadow-[var(--shadow-soft)] backdrop-blur-2xl text-center">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Join Room</p>
        <h1 class="mt-2 text-4xl md:text-5xl font-black tracking-[-0.07em] text-[var(--text)]">Tham gia phòng bằng mã</h1>
        <p class="mt-4 text-sm leading-7 text-[var(--muted)]">Nhập room code của giáo viên cung cấp để tìm group quiz và bắt đầu làm bài.</p>
        
        <div class="mt-8 rounded-[2rem] border border-[var(--border)] bg-[var(--surface-soft)] p-6">
          <input 
            v-model="roomCode" 
            class="w-full bg-transparent text-center text-4xl md:text-5xl font-black tracking-[0.22em] text-[var(--text)] outline-none placeholder:tracking-normal placeholder:text-2xl" 
            placeholder="MÃ PHÒNG" 
            style="text-transform: uppercase;"
            maxlength="12" 
            @keyup.enter="joinRoom" 
          />
          <button class="btn-primary mt-6 w-full" type="button" @click="joinRoom">Tham gia ngay</button>
        </div>
        
        <div v-if="errorMessage" class="mt-5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ errorMessage }}</div>
      </div>
    </article>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { normalizeQuizCard, quizzesApi } from '@/services/api'

const router = useRouter()
const roomCode = ref('')
const errorMessage = ref('')

const joinRoom = async () => {
  errorMessage.value = ''
  const code = roomCode.value.trim()
  
  if (!code) {
    errorMessage.value = 'Bạn chưa nhập room code.'
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
</script>
