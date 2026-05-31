<template>
  <section class="mx-auto max-w-2xl py-12">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-8 text-center shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Join Homework</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)] md:text-5xl">Tham gia bằng mã room</h1>
        <p class="mt-4 text-sm leading-7 text-[var(--muted)]">Nhập mã room homework để tham gia và xem các bài được giao.</p>

        <form class="mt-8 rounded-[2rem] border border-[var(--border)] bg-[var(--surface-soft)] p-6" @submit.prevent="joinRoom">
          <input
            v-model.trim="roomCode"
            class="w-full bg-transparent text-center text-4xl font-black uppercase tracking-[0.22em] text-[var(--text)] outline-none placeholder:tracking-normal placeholder:text-2xl"
            maxlength="12"
            placeholder="MÃ ROOM"
          />
          <button class="btn-primary mt-6 w-full" type="submit" :disabled="isSubmitting">{{ isSubmitting ? 'Đang tham gia...' : 'Tham gia' }}</button>
        </form>

        <div class="mt-5 grid gap-3 text-left sm:grid-cols-2">
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-black uppercase tracking-[0.16em] text-[var(--primary)]">Assignment</p>
            <p class="mt-2 text-sm font-bold leading-6 text-[var(--muted)]">Sau khi join, bạn sẽ thấy các bài được giao trong room.</p>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-black uppercase tracking-[0.16em] text-[var(--primary)]">Result</p>
            <p class="mt-2 text-sm font-bold leading-6 text-[var(--muted)]">Kết quả hiển thị theo cấu hình của chủ room.</p>
          </div>
        </div>

        <div v-if="successMessage" class="mt-5 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-300">{{ successMessage }}</div>
        <div v-if="errorMessage" class="mt-5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

        <router-link class="btn-ghost mt-6 inline-flex" to="/homework-rooms">Quay lại danh sách</router-link>
      </div>
    </article>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { homeworkApi } from '@/services/api'

const router = useRouter()
const roomCode = ref('')
const isSubmitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const joinRoom = async () => {
  errorMessage.value = ''
  successMessage.value = ''

  const code = roomCode.value.trim().toUpperCase()
  if (!code) {
    errorMessage.value = 'Bạn cần nhập mã room.'
    return
  }

  isSubmitting.value = true

  try {
    await homeworkApi.joinHomeworkRoom(code)
    successMessage.value = 'Tham gia room thành công.'
    window.setTimeout(() => router.push('/homework-rooms'), 700)
  } catch (error) {
    errorMessage.value = `Không tham gia được room: ${error.message}`
  } finally {
    isSubmitting.value = false
  }
}
</script>
