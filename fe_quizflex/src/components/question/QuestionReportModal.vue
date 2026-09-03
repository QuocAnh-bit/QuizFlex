<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm animate-fadeIn"
    @click.self="closeModal"
  >
    <div class="relative w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl space-y-5 animate-scaleUp">
      <!-- 1. SUCCESS CONFIRMATION STATE -->
      <div v-if="isSuccessState" class="space-y-5 py-2 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 shadow-xs">
          <CheckCircle2 :size="34" />
        </div>

        <div class="space-y-1.5">
          <h3 class="text-lg font-black text-slate-900">
            Báo cáo câu hỏi đã được gửi thành công!
          </h3>
          <p class="text-xs text-slate-500 font-medium max-w-md mx-auto">
            Cảm ơn bạn đã đóng góp phản ánh chất lượng cho Ngân hàng câu hỏi QuizFlex.
          </p>
        </div>

        <!-- Details Card -->
        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 text-left space-y-2.5 text-xs text-slate-700">
          <div class="flex items-center justify-between border-b border-slate-200/80 pb-2">
            <span class="text-slate-500 font-medium">Mã câu hỏi:</span>
            <span class="font-mono font-bold text-slate-900">#{{ questionId }}</span>
          </div>

          <div class="flex items-center justify-between border-b border-slate-200/80 pb-2">
            <span class="text-slate-500 font-medium">Lý do phản ánh:</span>
            <span class="font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 text-[11px]">{{ selectedReason }}</span>
          </div>

          <div class="pt-1 space-y-1 text-[11px] text-slate-600">
            <p class="font-bold text-slate-800 flex items-center gap-1.5">
              <Clock :size="14" class="text-amber-500" />
              <span>Quy trình xử lý tiếp theo:</span>
            </p>
            <p class="leading-relaxed pl-5">
              Tác giả câu hỏi có <strong>7 ngày</strong> để tiếp nhận và gửi bản đính chính. Hệ thống và Ban Quản Trị sẽ thẩm định tính hợp lệ hoặc tự động gỡ khỏi Ngân hàng chung nếu không sửa.
            </p>
          </div>
        </div>

        <!-- Action buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-2.5 pt-2">
          <button
            type="button"
            class="w-full sm:w-auto rounded-xl border border-slate-200 px-5 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
            @click="closeModal"
          >
            Đóng
          </button>
          <button
            type="button"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#7C3AED] px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#6D28D9] transition cursor-pointer"
            @click="goToMyReports"
          >
            <FileText :size="14" />
            <span>Xem Báo cáo của tôi ↗</span>
          </button>
        </div>
      </div>

      <!-- 2. FORM STATE (NORMAL REPORT SUBMISSION) -->
      <template v-else>
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
          <div class="line-clamp-2 italic text-slate-800">“<MathText :content="questionSnippet" compact />”</div>
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
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, inject } from 'vue'
import { useRouter } from 'vue-router'
import { Flag, X, AlertCircle, CheckCircle2, Clock, FileText } from 'lucide-vue-next'
import { reportApi } from '@/services/api'
import MathText from '@/components/MathText.vue'

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
const router = useRouter()
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
const isSuccessState = ref(false)

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    selectedReason.value = reasons[0]
    description.value = ''
    errorMessage.value = ''
    isSuccessState.value = false
  }
})

const closeModal = () => {
  if (isSubmitting.value) return
  errorMessage.value = ''
  isSuccessState.value = false
  emit('close')
}

const goToMyReports = () => {
  closeModal()
  router.push('/my-reports')
}

const submitReport = async () => {
  if (!selectedReason.value) return

  const parsedId = Number(props.questionId)
  if (!parsedId || isNaN(parsedId)) {
    errorMessage.value = 'Không tìm thấy thông tin ID câu hỏi cần báo cáo.'
    return
  }

  isSubmitting.value = true
  errorMessage.value = ''

  try {
    const res = await reportApi.createQuestionReport({
      question_id: parsedId,
      reason: selectedReason.value,
      description: description.value.trim() || undefined,
    })

    const successMsg = res?.message || 'Báo cáo câu hỏi đã được gửi thành công.'
    if (showToast) {
      showToast(successMsg, 'success')
    }
    emit('reported')
    isSuccessState.value = true
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
