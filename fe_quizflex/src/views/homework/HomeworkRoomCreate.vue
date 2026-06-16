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

    <div class="grid gap-4 sm:grid-cols-3">
      <StatCard value="Code" label="Mã room" hint="Backend sinh tự động" />
      <StatCard value="Owner" label="Quyền quản lý" hint="Chủ room giao bài" />
      <StatCard value="Assignments" label="Homework" hint="Giao nhiều quiz trong một room" />
    </div>

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

        <div class="grid gap-3">
          <span class="text-sm font-black text-[var(--text)]">Quyền tham gia</span>
          <div class="grid gap-3 md:grid-cols-2">
            <label class="cursor-pointer rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
              <input v-model="form.join_policy" class="sr-only" type="radio" value="open" />
              <span class="block text-sm font-black text-[var(--text)]">Ai có mã phòng đều có thể tham gia</span>
              <span class="mt-2 block text-xs font-bold leading-5 text-[var(--muted)]">Giữ hành vi hiện tại: chỉ cần nhập đúng mã room.</span>
              <span v-if="form.join_policy === 'open'" class="mt-3 inline-flex rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--primary)]">Đang chọn</span>
            </label>
            <label class="cursor-pointer rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
              <input v-model="form.join_policy" class="sr-only" type="radio" value="email_whitelist" />
              <span class="block text-sm font-black text-[var(--text)]">Chỉ email trong danh sách được phép tham gia</span>
              <span class="mt-2 block text-xs font-bold leading-5 text-[var(--muted)]">Người học cần đăng nhập bằng email đã được thêm.</span>
              <span v-if="form.join_policy === 'email_whitelist'" class="mt-3 inline-flex rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--primary)]">Đang chọn</span>
            </label>
          </div>
        </div>

        <label v-if="form.join_policy === 'email_whitelist'" class="grid gap-2">
          <span class="text-sm font-black text-[var(--text)]">Danh sách email được phép</span>
          <textarea
            v-model="form.allowedEmailText"
            class="field min-h-40 resize-y"
            placeholder="student1@example.com&#10;student2@example.com, student3@example.com"
          ></textarea>
          <span class="text-xs font-bold leading-5 text-[var(--muted)]">Nhập mỗi dòng một email hoặc phân tách bằng dấu phẩy.</span>
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
import StatCard from '@/components/cards/StatCard.vue'
import { homeworkApi } from '@/services/api'

const router = useRouter()
const form = reactive({
  name: '',
  description: '',
  join_policy: 'open',
  allowedEmailText: '',
})
const isSubmitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const parseAllowedEmails = () =>
  form.allowedEmailText
    .split(/[,\n;]/)
    .map((email) => email.trim().toLowerCase())
    .filter(Boolean)

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
      allowed_emails: form.join_policy === 'email_whitelist' ? parseAllowedEmails() : [],
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
