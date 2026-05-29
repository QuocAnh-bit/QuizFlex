<template>
  <section class="grid gap-6 py-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <router-link class="btn-ghost" to="/live-rooms">Quay lại Live Room</router-link>
      <router-link class="btn-ghost" to="/live-rooms/create">Tạo live room</router-link>
    </div>

    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)]">
      <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Join Live</p>
      <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Tham gia live room</h1>
      <p class="mt-3 max-w-3xl text-sm leading-7 text-[var(--muted)]">Nhập mã phòng do host chia sẻ để vào màn chơi live quiz.</p>
    </article>

    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

    <form class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]" @submit.prevent="handleJoin">
      <label class="grid gap-2">
        <span class="text-sm font-black text-[var(--text)]">Mã live room</span>
        <input v-model.trim="code" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-3 text-lg font-black uppercase tracking-[0.12em] text-[var(--text)] outline-none focus:border-[var(--border-strong)]" placeholder="ABC123" maxlength="12" />
      </label>

      <div class="mt-6 flex justify-end">
        <button class="btn-primary disabled:cursor-not-allowed disabled:opacity-50" type="submit" :disabled="isSubmitting">
          {{ isSubmitting ? 'Đang tham gia...' : 'Tham gia' }}
        </button>
      </div>
    </form>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { liveRoomApi } from '@/services/api'

const router = useRouter()
const code = ref('')
const isSubmitting = ref(false)
const errorMessage = ref('')

const handleJoin = async () => {
  if (!code.value) {
    errorMessage.value = 'Vui lòng nhập mã live room.'
    return
  }

  isSubmitting.value = true
  errorMessage.value = ''

  try {
    const data = await liveRoomApi.joinLiveRoom(code.value)
    const liveRoomId = data.live_room?.id
    if (!liveRoomId) throw new Error('Response không có live room id.')
    router.push(`/live-rooms/${liveRoomId}/play`)
  } catch (error) {
    errorMessage.value = error.message || 'Không tham gia được live room.'
  } finally {
    isSubmitting.value = false
  }
}
</script>
