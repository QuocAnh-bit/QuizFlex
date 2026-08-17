<template>
  <section class="mx-auto max-w-2xl py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 space-y-2">
      <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Phòng bài tập</p>
      <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">Tạo phòng bài tập mới</h1>
      <p class="text-xs text-slate-600">Tạo không gian học tập chung để giao bài, quản lý thành viên và chấm điểm.</p>
    </div>

    <form class="card p-6 sm:p-8 space-y-5" @submit.prevent="submitForm">
      <div class="space-y-4">
        <label class="grid gap-1.5 text-xs font-bold text-slate-700">
          Tên phòng học <span class="text-red-500">*</span>
          <input v-model.trim="form.name" class="field text-xs" maxlength="255" placeholder="VD: Lớp Toán 12A1 - Ôn thi THPT" required />
        </label>

        <label class="grid gap-1.5 text-xs font-bold text-slate-700">
          Mô tả phòng học
          <textarea v-model.trim="form.description" class="field text-xs min-h-28 resize-y" placeholder="Mô tả ngắn gọn về nội dung hoặc quy định của phòng học..."></textarea>
        </label>
      </div>

      <div v-if="successMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-bold text-emerald-700">{{ successMessage }}</div>
      <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700">{{ errorMessage }}</div>

      <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
        <router-link class="btn-secondary text-xs px-4 py-2" to="/homework-rooms">Hủy</router-link>
        <button class="btn-primary text-xs px-5 py-2" type="submit" :disabled="isSubmitting">{{ isSubmitting ? 'Đang tạo...' : 'Tạo phòng ngay' }}</button>
      </div>
    </form>
  </section>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
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
    errorMessage.value = 'Bạn cần nhập tên phòng học.'
    return
  }

  isSubmitting.value = true

  try {
    const room = await homeworkApi.createHomeworkRoom({
      name: form.name,
      description: form.description || null,
      join_policy: form.join_policy,
    })

    successMessage.value = room?.code ? `Tạo phòng thành công! Mã phòng: ${room.code}` : 'Tạo phòng thành công!'
    window.setTimeout(() => router.push('/homework-rooms'), 600)
  } catch (error) {
    errorMessage.value = `Không tạo được phòng: ${error.message}`
  } finally {
    isSubmitting.value = false
  }
}
</script>
