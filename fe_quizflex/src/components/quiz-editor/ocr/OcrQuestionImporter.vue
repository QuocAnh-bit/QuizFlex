<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[110] grid place-items-center bg-slate-950/45 p-4 backdrop-blur-sm" @click.self="close">
      <section class="flex max-h-[92dvh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <header class="flex items-start justify-between border-b border-slate-200 px-5 py-4"><div><p class="text-[11px] font-black uppercase tracking-[.16em] text-sky-600">QuizFlex OCR</p><h2 class="mt-1 text-xl font-black text-slate-900">Quét ảnh hoặc tài liệu</h2><p class="mt-1 text-xs text-slate-500">{{ contextLabel }}</p><span class="mt-2 inline-flex rounded-lg px-2.5 py-1 text-[10px] font-black" :class="ocrAllowed ? 'bg-sky-50 text-sky-700' : 'bg-rose-50 text-rose-700'">{{ ocrQuotaLabel }}</span></div><button type="button" class="grid h-9 w-9 place-items-center rounded-xl text-slate-400 hover:bg-slate-100" aria-label="Đóng" @click="close"><X class="h-5 w-5" /></button></header>
        <div class="min-h-0 flex-1 overflow-y-auto p-5">
          <input ref="fileInput" type="file" class="hidden" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" @change="selectFile" />
          <button type="button" class="flex w-full flex-col items-center rounded-2xl border-2 border-dashed border-sky-200 bg-sky-50/50 px-5 py-7 text-center transition hover:border-sky-400" :disabled="isProcessing" @click="fileInput?.click()"><UploadCloud class="h-8 w-8 text-sky-600" /><span class="mt-2 text-sm font-black text-slate-800">Chọn ảnh hoặc PDF</span><span class="mt-1 text-xs text-slate-500">JPG, JPEG, PNG, WEBP hoặc PDF · tối đa 20MB</span></button>
          <div v-if="file" class="mt-3 flex items-center gap-3 rounded-xl border border-slate-200 p-3"><img v-if="imagePreview" :src="imagePreview" alt="Xem trước tài liệu" class="h-16 w-16 rounded-lg object-cover" /><FileText v-else class="h-9 w-9 text-rose-500" /><div class="min-w-0 flex-1"><p class="truncate text-xs font-black text-slate-800">{{ file.name }}</p><p class="mt-1 text-[11px] text-slate-500">{{ fileTypeLabel }} · {{ formatSize(file.size) }}</p></div><button type="button" class="text-xs font-black text-violet-700" :disabled="isProcessing" @click="fileInput?.click()">Chọn file khác</button></div>
          <div class="mt-3 flex justify-end"><button type="button" class="rounded-xl bg-sky-600 px-5 py-3 text-xs font-black text-white shadow-lg shadow-sky-100 disabled:cursor-not-allowed disabled:opacity-40" :disabled="!file || isProcessing" @click="processFile">{{ isProcessing ? 'Đang xử lý tài liệu...' : 'Quét tài liệu' }}</button></div>
          <div v-if="errorMessage" class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs font-bold text-rose-700"><p>{{ errorMessage }}</p><button v-if="file" type="button" class="mt-2 underline" :disabled="isProcessing" @click="processFile">Thử lại</button></div>
          <div v-if="isProcessing" class="grid min-h-44 place-items-center"><div class="text-center"><span class="mx-auto block h-9 w-9 animate-spin rounded-full border-4 border-sky-100 border-t-sky-600"></span><p class="mt-3 text-sm font-black text-slate-700">Đang xử lý tài liệu...</p><p class="mt-1 text-xs text-slate-500">OCR và AI đang nhận diện cấu trúc câu hỏi.</p></div></div>
          <div v-else-if="results.length" class="mt-5 space-y-2"><div class="flex items-center justify-between"><div><h3 class="text-sm font-black text-slate-900">Đã nhận diện {{ results.length }} câu hỏi</h3><p v-if="invalidCount" class="mt-1 text-[11px] font-bold text-amber-700">{{ invalidCount }} câu chưa đủ dữ liệu nên chưa thể chọn.</p><p v-if="warningCount" class="mt-1 text-[11px] font-bold text-rose-700">{{ warningCount }} câu chưa có đáp án đúng — vẫn có thể thêm và chọn lại trong Editor.</p></div><button type="button" class="text-xs font-black text-violet-700" @click="toggleAll">{{ allValidSelected ? 'Bỏ chọn tất cả' : 'Chọn tất cả hợp lệ' }}</button></div>
            <article v-for="(item, index) in results" :key="item.question.id" class="flex gap-3 rounded-xl border p-3" :class="item.invalidReason ? 'border-amber-200 bg-amber-50/50' : item.warning ? 'cursor-pointer border-rose-200 bg-rose-50/50 hover:border-rose-300' : selectedIds.includes(item.question.id) ? 'cursor-pointer border-violet-400 bg-violet-50' : 'cursor-pointer border-slate-200 hover:border-violet-200'" @click="!item.invalidReason && toggle(item.question.id)"><input type="checkbox" class="mt-1 h-4 w-4 accent-violet-600" :checked="selectedIds.includes(item.question.id)" :disabled="Boolean(item.invalidReason)" @click.stop @change="toggle(item.question.id)" /><div class="min-w-0 flex-1"><p class="text-sm font-bold leading-6 text-slate-900"><MathText :content="item.question.content" /></p><div v-if="item.question.answers.length" class="mt-2 grid gap-1.5 sm:grid-cols-2"><div v-for="answer in item.question.answers" :key="answer.id" class="rounded-lg px-2.5 py-2 text-xs" :class="answer.is_correct ? 'bg-emerald-50 font-bold text-emerald-800' : 'bg-white text-slate-600'"><MathText :content="answer.content" compact /></div></div><p v-if="item.invalidReason || item.warning" class="mt-2 text-[11px] font-black" :class="item.invalidReason ? 'text-amber-700' : 'text-rose-700'">{{ item.invalidReason || item.warning }}</p></div><span class="shrink-0 rounded-md bg-sky-50 px-2 py-1 text-[10px] font-black text-sky-700">Câu {{ index + 1 }}</span></article>
          </div>
        </div>
        <footer class="flex items-center justify-between border-t border-slate-200 px-5 py-4"><span class="text-xs font-bold text-slate-500">Đã chọn: <b class="text-violet-700">{{ selectedIds.length }}</b> câu</span><div class="flex gap-2"><button type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-black" @click="close">Hủy</button><button type="button" class="rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-black text-white disabled:opacity-40" :disabled="!selectedIds.length || isProcessing" @click="addSelected">Thêm {{ selectedIds.length }} câu</button></div></footer>
      </section>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { FileText, UploadCloud, X } from 'lucide-vue-next'
import { authApi, currentUserStorage, taxonomyApi } from '@/services/api.js'
import { normalizeOcrQuestions, scanOcrDocument } from '@/services/quiz-editor/quizEditorOcrApi.js'
import MathText from '../../MathText.vue'

const props = defineProps({ quizContext: { type: Object, default: () => ({}) } })
const emit = defineEmits(['select', 'close'])
const fileInput = ref(null)
const file = ref(null)
const imagePreview = ref('')
const results = ref([])
const selectedIds = ref([])
const isProcessing = ref(false)
const errorMessage = ref('')
const quotaUser = ref(currentUserStorage.get())
const contextLabel = ref('Đang lấy thông tin lớp và môn học...')
const validItems = computed(() => results.value.filter((item) => !item.invalidReason))
const invalidCount = computed(() => results.value.length - validItems.value.length)
const warningCount = computed(() => validItems.value.filter((item) => item.warning).length)
const allValidSelected = computed(() => validItems.value.length > 0 && selectedIds.value.length === validItems.value.length)
const fileTypeLabel = computed(() => file.value?.type === 'application/pdf' ? 'PDF' : 'Ảnh')
const ocrAllowed = computed(() => Boolean(quotaUser.value?.ocr_allowed))
const ocrQuotaLabel = computed(() => quotaUser.value?.ocr_quota_unlimited ? 'OCR không giới hạn' : ocrAllowed.value ? `Còn ${Number(quotaUser.value?.ocr_quota_remaining || 0)} lượt OCR trong tháng` : 'OCR chưa khả dụng hoặc đã hết lượt tháng này')
const formatSize = (bytes) => `${(bytes / 1024 / 1024).toFixed(2)} MB`
const revokePreview = () => { if (imagePreview.value) URL.revokeObjectURL(imagePreview.value); imagePreview.value = '' }
const selectFile = (event) => {
  const selected = event.target.files?.[0]
  if (!selected || isProcessing.value) return
  const allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf']
  errorMessage.value = ''; results.value = []; selectedIds.value = []; revokePreview()
  if (!allowed.includes(selected.type)) { file.value = null; errorMessage.value = 'Định dạng không hỗ trợ. Chỉ chấp nhận JPG, JPEG, PNG, WEBP và PDF.'; event.target.value = ''; return }
  if (selected.size > 20 * 1024 * 1024) { file.value = null; errorMessage.value = 'File vượt quá dung lượng tối đa 20MB.'; event.target.value = ''; return }
  file.value = selected
  if (selected.type.startsWith('image/')) imagePreview.value = URL.createObjectURL(selected)
}
const processFile = async () => {
  if (!file.value || isProcessing.value) return
  if (!ocrAllowed.value) { errorMessage.value = ocrQuotaLabel.value; return }
  isProcessing.value = true; errorMessage.value = ''; results.value = []; selectedIds.value = []
  try { const payload = await scanOcrDocument(file.value); results.value = normalizeOcrQuestions(payload, file.value.name); selectedIds.value = validItems.value.map((item) => item.question.id); if (!results.value.length) throw new Error('Không nhận diện được câu hỏi nào trong tài liệu.'); quotaUser.value = await authApi.me() } catch (error) { errorMessage.value = error?.response?.data?.error || error?.response?.data?.message || error?.message || 'Không thể xử lý tài liệu.' } finally { isProcessing.value = false }
}
const toggle = (id) => { if (!validItems.value.some((item) => item.question.id === id)) return; selectedIds.value = selectedIds.value.includes(id) ? selectedIds.value.filter((item) => item !== id) : [...selectedIds.value, id] }
const toggleAll = () => { selectedIds.value = allValidSelected.value ? [] : validItems.value.map((item) => item.question.id) }
const addSelected = () => emit('select', validItems.value.filter((item) => selectedIds.value.includes(item.question.id)).map((item) => item.question))
const close = () => emit('close')
onMounted(async () => { try { quotaUser.value = await authApi.me(); const data = await taxonomyApi.tree(); const levels = data?.education_levels || data?.educationLevels || []; const level = levels.find((item) => Number(item.id) === Number(props.quizContext.education_level_id)); const grade = level?.grades?.find((item) => Number(item.id) === Number(props.quizContext.grade_id)); const subject = grade?.subjects?.find((item) => Number(item.id) === Number(props.quizContext.subject_id)); contextLabel.value = `${grade?.name || 'Lớp đã chọn'} • ${subject?.name || 'Môn học đã chọn'}` } catch { contextLabel.value = 'Sử dụng Lớp và Môn học của Quiz hiện tại.' } })
onBeforeUnmount(revokePreview)
</script>
