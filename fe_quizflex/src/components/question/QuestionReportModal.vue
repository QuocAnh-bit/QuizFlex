<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-md" @click.self="closeModal">
    <div class="relative w-full max-w-lg rounded-[2rem] border border-[var(--border-strong)] bg-[var(--surface-strong)] p-6 shadow-2xl backdrop-blur-2xl text-[var(--text)] overflow-hidden">
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-[var(--border)] pb-4 mb-4">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-500/20 text-rose-400 border border-rose-500/30">
            <Flag :size="20" />
          </div>
          <div>
            <h3 class="text-xl font-black text-white">Báo cáo vi phạm câu hỏi #{{ questionId }}</h3>
            <p class="text-xs text-[var(--muted)]">Giúp QuizFlex làm sạch nội dung bằng cách báo cáo sai sót</p>
          </div>
        </div>
        <button
          type="button"
          class="flex h-8 w-8 items-center justify-center rounded-xl text-[var(--muted)] hover:text-rose-400 hover:bg-rose-500/10 transition cursor-pointer"
          aria-label="Đóng"
          title="Đóng"
          @click="closeModal"
        >
          <X :size="16" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="submitReport" class="space-y-4">
        <div>
          <label class="block text-xs font-black uppercase tracking-wider text-[var(--muted)] mb-2">Lý do báo cáo vi phạm *</label>
          <div class="space-y-2 text-xs font-bold">
            <label v-for="r in reasons" :key="r" class="flex items-center gap-3 rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 cursor-pointer hover:border-[var(--primary)]/50 transition">
              <input type="radio" v-model="selectedReason" :value="r" name="report_reason" class="accent-[var(--primary)] h-4 w-4" />
              <span>{{ r }}</span>
            </label>
          </div>
        </div>

        <div>
          <label class="block text-xs font-black uppercase tracking-wider text-[var(--muted)] mb-2">Mô tả chi tiết thêm (Không bắt buộc)</label>
          <textarea
            v-model="description"
            rows="3"
            class="field w-full text-xs"
            placeholder="Mô tả cụ thể sai ở đâu hoặc phản ánh chi tiết..."
          ></textarea>
        </div>

        <!-- Action buttons -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-[var(--border)]">
          <button type="button" class="btn-ghost text-xs" @click="closeModal">Hủy</button>
          <button type="submit" class="btn-primary text-xs flex items-center gap-2" :disabled="isSubmitting || !selectedReason">
            <Flag :size="13" />
            <span>{{ isSubmitting ? 'Đang gửi...' : 'Gửi báo cáo' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, inject } from 'vue'
import { Flag, X } from 'lucide-vue-next'
import { reportApi } from '@/services/api'

const props = defineProps({
  questionId: { type: [Number, String], required: true },
  isOpen: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'reported'])
const showToast = inject('showToast')

const reasons = [
  'Sai đáp án / Phương án đúng không chính xác',
  'Đề bài thiếu thông tin / Lỗi hiển thị',
  'Nội dung thô tục / Vi phạm thuần phong mỹ tục',
  'Spam / Thông tin giả mạo'
]

const selectedReason = ref(reasons[0])
const description = ref('')
const isSubmitting = ref(false)

const closeModal = () => {
  emit('close')
}

const submitReport = async () => {
  if (!selectedReason.value) return
  isSubmitting.value = true

  try {
    await reportApi.create({
      question_id: props.questionId,
      reason: selectedReason.value,
      description: description.value.trim()
    })

    if (showToast) showToast('Cảm ơn bạn! Báo cáo đã được gửi tới Quản trị viên.', 'success')
    emit('reported')
    closeModal()
  } catch (err) {
    if (showToast) showToast(`Gửi báo cáo thất bại: ${err.message}`, 'error')
  } finally {
    isSubmitting.value = false
  }
}
</script>
