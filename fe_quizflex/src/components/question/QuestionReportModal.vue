<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm animate-fadeIn"
    @click.self="closeModal"
  >
    <div class="relative w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl space-y-5 animate-scaleUp">
      <!-- Header -->
      <div class="flex items-start justify-between border-b border-slate-100 pb-4">
        <div class="flex items-center gap-3">
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 border border-rose-200 shadow-2xs">
            <Flag :size="22" />
          </div>
          <div>
            <h3 class="text-base sm:text-lg font-black text-slate-900 flex items-center gap-2">
              <span>Báo cáo câu hỏi</span>
              <span class="rounded-md bg-rose-100 px-2 py-0.5 font-mono text-xs font-black text-rose-800">
                #{{ questionId }}
              </span>
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Phản ánh sai sót giúp tác giả và Ban Quản Trị kịp thời đính chính
            </p>
          </div>
        </div>

        <button
          type="button"
          class="flex h-8 w-8 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition cursor-pointer"
          aria-label="Đóng"
          title="Đóng"
          @click="closeModal"
        >
          <X :size="18" />
        </button>
      </div>

      <!-- Question Preview Snippet (if available) -->
      <div v-if="questionSnippet" class="rounded-2xl border border-slate-100 bg-slate-50 p-3 text-xs text-slate-700 font-medium">
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Nội dung câu hỏi:</span>
        <p class="line-clamp-2 italic text-slate-800">"{{ questionSnippet }}"</p>
      </div>

      <!-- Error Alert -->
      <div
        v-if="errorMessage"
        class="rounded-2xl border border-rose-200 bg-rose-50 p-3.5 text-xs font-bold text-rose-700 flex items-center gap-2"
      >
        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Form -->
      <form @submit.prevent="submitReport" class="space-y-4">
        <div>
          <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-2">
            Lý do báo cáo vi phạm *
          </label>
          <div class="space-y-2 text-xs font-semibold">
            <label
              v-for="r in reasons"
              :key="r"
              class="flex items-center gap-3 rounded-2xl border p-3 cursor-pointer transition"
              :class="selectedReason === r ? 'border-rose-300 bg-rose-50/50 text-rose-950 shadow-2xs font-bold' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
            >
              <input
                type="radio"
                v-model="selectedReason"
                :value="r"
                name="report_reason"
                class="accent-rose-600 h-4 w-4 shrink-0"
              />
              <span>{{ r }}</span>
            </label>
          </div>
        </div>

        <div>
          <label class="block text-xs font-black uppercase tracking-wider text-slate-700 mb-1.5">
            Mô tả chi tiết thêm (Không bắt buộc)
          </label>
          <textarea
            v-model="description"
            rows="3"
            class="w-full rounded-2xl border border-slate-200 bg-slate-50 p-3 text-xs font-medium text-slate-900 outline-none focus:border-rose-500 focus:bg-white transition resize-none"
            placeholder="Mô tả cụ thể câu hỏi sai ở đâu, đáp án đúng nên là gì..."
          ></textarea>
        </div>

        <!-- Action buttons -->
        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <button
            type="button"
            class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer"
            :disabled="isSubmitting"
            @click="closeModal"
          >
            Hủy
          </button>
          <button
            type="submit"
            class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-rose-700 transition disabled:opacity-50 cursor-pointer"
            :disabled="isSubmitting || !selectedReason"
          >
            <Flag :size="13" />
            <span>{{ isSubmitting ? 'Đang gửi...' : 'Gửi báo cáo' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, inject } from 'vue'
import { Flag, X, AlertCircle } from 'lucide-vue-next'
import { reportApi } from '@/services/api'

const props = defineProps({
  questionId: {
    type: [Number, String],
    required: true,
  },
  questionSnippet: {
    type: String,
    default: '',
  },
  isOpen: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'reported'])
const showToast = inject('showToast')

const reasons = [
  'Sai đáp án / Phương án đúng không chính xác',
  'Đề bài thiếu thông tin / Lỗi hiển thị',
  'Nội dung thô tục / Vi phạm thuần phong mỹ tục',
  'Spam / Thông tin giả mạo',
  'Lý do khác',
]

const selectedReason = ref(reasons[0])
const description = ref('')
const isSubmitting = ref(false)
const errorMessage = ref('')

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    selectedReason.value = reasons[0]
    description.value = ''
    errorMessage.value = ''
  }
})

const closeModal = () => {
  if (isSubmitting.value) return
  errorMessage.value = ''
  emit('close')
}

const submitReport = async () => {
  if (!selectedReason.value) return
  isSubmitting.value = true
  errorMessage.value = ''

  try {
    const res = await reportApi.createQuestionReport({
      question_id: props.questionId,
      reason: selectedReason.value,
      description: description.value.trim(),
    })

    const successMsg = res?.message || 'Cảm ơn bạn! Báo cáo vi phạm đã được gửi tới Quản trị viên (Admin) và tác giả để đính chính trên Ngân hàng câu hỏi.'
    if (showToast) {
      showToast(successMsg, 'success')
    }
    emit('reported')
    closeModal()
  } catch (err) {
    const msg = err.response?.data?.message || err.message || 'Có lỗi xảy ra khi gửi báo cáo.'
    errorMessage.value = msg
    if (showToast) {
      showToast(msg, 'error')
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>
