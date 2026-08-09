<template>
  <section class="mx-auto grid max-w-3xl gap-6 py-8">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Tạo phòng bài tập</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Tạo phòng bài tập</h1>
        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Tạo phòng để giao quiz cho thành viên. Mã phòng sẽ được backend sinh tự động.</p>
      </div>
    </article>

    <div class="grid gap-4 sm:grid-cols-3">
      <StatCard value="Code" label="Mã phòng" hint="Backend sinh tự động" />
      <StatCard value="Chủ phòng" label="Quyền quản lý" hint="Chủ phòng giao bài" />
      <StatCard value="Assignments" label="Bài tập" hint="Giao nhiều quiz trong một phòng" />
    </div>

    <form class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]" @submit.prevent="submitForm">
      <div class="grid gap-5">
        <label class="grid gap-2">
          <span class="text-sm font-black text-[var(--text)]">Tên phòng</span>
          <input v-model.trim="form.name" class="field" maxlength="255" placeholder="VD: Ôn tập chương 1" />
        </label>

        <label class="grid gap-2">
          <span class="text-sm font-black text-[var(--text)]">Mô tả</span>
          <textarea v-model.trim="form.description" class="field min-h-32 resize-y" placeholder="Mô tả ngắn cho thành viên trong phòng"></textarea>
        </label>
      </div>

      <div v-if="successMessage" class="mt-5 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-300">{{ successMessage }}</div>
      <div v-if="errorMessage" class="mt-5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

      <div class="mt-6 flex flex-wrap justify-end gap-3">
        <router-link class="btn-ghost" to="/homework-rooms">Hủy</router-link>
        <button class="btn-primary" type="submit" :disabled="isSubmitting">{{ isSubmitting ? 'Đang tạo...' : 'Tạo phòng' }}</button>
      </div>
    </form>
  </section>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import StatCard from '@/components/cards/StatCard.vue'
import { homeworkApi, currentUserStorage } from '@/services/api'

const router = useRouter()
const form = reactive({
  name: '',
  description: '',
  join_policy: 'open',
})
const isSubmitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const currentUser = currentUserStorage.get()

onMounted(() => {
  const role = String(currentUser?.role || 'free').toLowerCase()
  if (!['admin', 'plus', 'pro', 'ultra'].includes(role)) {
    router.replace('/upgrade')
  }
})

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
      join_policy: form.join_policy,
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
