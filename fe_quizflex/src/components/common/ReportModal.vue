<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-md rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-2xl">
      <h2 class="text-2xl font-black text-[var(--text)]">Báo cáo Quiz</h2>
      <p class="mt-2 text-sm text-[var(--muted)]">Vui lòng cho biết lý do bạn báo cáo bài Quiz này. Chúng tôi sẽ xem xét kỹ lưỡng.</p>

      <form @submit.prevent="submitReport" class="mt-6 grid gap-4">
        <!-- <div>
          <label class="mb-2 block text-sm font-bold text-[var(--muted)]">Lý do</label>
          <select v-model="form.reason" required class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-sm text-[var(--text)] outline-none focus:border-[var(--primary)]">
            <option value="" disabled>Chọn lý do...</option>
            <option value="Thông tin sai lệch">Thông tin sai lệch</option>
            <option value="Nội dung nhạy cảm, không phù hợp">Nội dung nhạy cảm, không phù hợp</option>
            <option value="Spam, quảng cáo">Spam, quảng cáo</option>
            <option value="Lý do khác">Lý do khác</option>
          </select>
        </div> -->
        <div>
          <label class="mb-2 block text-sm font-bold text-[var(--muted)]">Lý do</label>
          <select v-model="form.reason" required style="color-scheme: dark;" class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-sm text-[var(--text)] outline-none focus:border-[var(--primary)]">
            <option value="" disabled class="bg-gray-800 text-white">Chọn lý do...</option>
            <option value="Thông tin sai lệch" class="bg-gray-800 text-white">Thông tin sai lệch</option>
            <option value="Nội dung nhạy cảm, không phù hợp" class="bg-gray-800 text-white">Nội dung nhạy cảm, không phù hợp</option>
            <option value="Spam, quảng cáo" class="bg-gray-800 text-white">Spam, quảng cáo</option>
            <option value="Lý do khác" class="bg-gray-800 text-white">Lý do khác</option>
          </select>
        </div>

        <div>
          <label class="mb-2 block text-sm font-bold text-[var(--muted)]">Chi tiết thêm (tùy chọn)</label>
          <textarea v-model="form.description" rows="3" class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-sm text-[var(--text)] outline-none focus:border-[var(--primary)]" placeholder="Mô tả cụ thể vấn đề (không bắt buộc)..."></textarea>
        </div>

        <div v-if="errorMessage" class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-sm font-bold text-rose-500">{{ errorMessage }}</div>

        <div class="mt-4 flex justify-end gap-3">
          <button type="button" @click="closeModal" class="btn-ghost rounded-xl px-5 py-2 text-sm font-bold text-[var(--text)] hover:bg-[var(--surface-soft)]">Hủy</button>
          <button type="submit" :disabled="isSubmitting" class="rounded-xl bg-rose-500 px-5 py-2 text-sm font-bold text-white transition hover:bg-rose-600 disabled:opacity-50">
            {{ isSubmitting ? 'Đang gửi...' : 'Gửi báo cáo' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, inject } from 'vue'
import { reportApi } from '@/services/api'

const props = defineProps({
  quizId: {
    type: [Number, String],
    required: true
  },
  isOpen: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'success'])

const showToast = inject('showToast')

const form = reactive({
  reason: '',
  description: ''
})
const isSubmitting = ref(false)
const errorMessage = ref('')

const closeModal = () => {
  form.reason = ''
  form.description = ''
  errorMessage.value = ''
  emit('close')
}

const submitReport = async () => {
  isSubmitting.value = true
  errorMessage.value = ''
  try {
    await reportApi.create({
      quiz_id: props.quizId,
      reason: form.reason,
      description: form.description
    })
    if (showToast) {
      showToast('Cảm ơn bạn đã báo cáo. Quản trị viên sẽ xử lý sớm nhất!', 'success')
    }
    emit('success')
    closeModal()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Có lỗi xảy ra khi gửi báo cáo.'
  } finally {
    isSubmitting.value = false
  }
}
</script>
