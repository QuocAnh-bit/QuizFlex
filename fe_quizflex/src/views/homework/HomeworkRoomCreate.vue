<template>
  <section class="mx-auto grid max-w-3xl gap-6 py-8">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Create</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Tạo Room Homework</h1>
        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Tạo phòng để giao quiz cho thành viên. Mã phòng sẽ được backend sinh tự động.</p>
      </div>
    </article>

    <form class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]" @submit.prevent="submitForm">
      <div class="grid gap-5">
        <label class="grid gap-2">
          <span class="text-sm font-black text-[var(--text)]">Tên room</span>
          <input v-model.trim="form.name" class="field" maxlength="255" placeholder="VD: Ôn tập chương 1" />
        </label>

        <label class="grid gap-2">
          <span class="text-sm font-black text-[var(--text)]">Mô tả</span>
          <textarea v-model.trim="form.description" class="field min-h-32 resize-y" placeholder="Mô tả ngắn cho thành viên trong room"></textarea>
        </label>
      </div>

      <div v-if="successMessage" class="mt-5 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-300">{{ successMessage }}</div>
      <div v-if="errorMessage" class="mt-5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

      <div class="mt-6 flex flex-wrap justify-end gap-3">
        <router-link class="btn-ghost" to="/homework-rooms">Hủy</router-link>
        <button class="btn-primary" type="submit" :disabled="isSubmitting">{{ isSubmitting ? 'Đang tạo...' : 'Tạo room' }}</button>
      </div>
    </form>
  </section>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { homeworkApi } from '@/services/api'

const router = useRouter()
const form = reactive({
  name: '',
  description: '',
})
const isSubmitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const submitForm = async () => {
  errorMessage.value = ''
  successMessage.value = ''

  if (!form.name) {
    errorMessage.value = 'Bạn cần nhập tên room.'
    return
  }

  isSubmitting.value = true

  try {
    const room = await homeworkApi.createHomeworkRoom({
      name: form.name,
      description: form.description || null,
    })

    successMessage.value = room?.code ? `Tạo room thành công. Mã phòng: ${room.code}` : 'Tạo room thành công.'
    window.setTimeout(() => router.push('/homework-rooms'), 700)
  } catch (error) {
    errorMessage.value = `Không tạo được room: ${error.message}`
  } finally {
    isSubmitting.value = false
  }
}
</script>
