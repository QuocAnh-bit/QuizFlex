<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[110] grid place-items-center bg-slate-950/45 p-4 backdrop-blur-sm" @click.self="close">
      <section class="flex max-h-[92dvh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <header class="flex items-start justify-between border-b border-slate-200 px-5 py-4">
          <div><p class="text-[11px] font-black uppercase tracking-[.16em] text-fuchsia-600">QuizFlex AI</p><h2 class="mt-1 text-xl font-black text-slate-900">Tạo câu hỏi bằng AI</h2><p class="mt-1 text-xs text-slate-500">AI sử dụng ngữ cảnh Quiz ở nền để tạo câu hỏi nháp.</p></div>
          <button type="button" class="grid h-9 w-9 place-items-center rounded-xl text-slate-400 hover:bg-slate-100" aria-label="Đóng" @click="close"><X class="h-5 w-5" /></button>
        </header>

        <div class="min-h-0 flex-1 overflow-y-auto p-5">
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <label class="field-label">Yêu cầu tạo câu hỏi<textarea v-model="form.prompt" rows="4" maxlength="5000" class="field mt-1 resize-none" :disabled="isBusy" placeholder="Ví dụ: Tạo câu trắc nghiệm kiểm tra kiến thức về lực ma sát, có tình huống thực tế." /></label>
            <div class="mt-3 flex flex-wrap items-end gap-3">
              <label class="field-label w-28">Số câu<input v-model.number="form.count" type="number" min="1" max="20" class="field mt-1" :disabled="isBusy" /></label>
              <label class="field-label w-40">Độ khó<select v-model="form.difficulty" class="field mt-1" :disabled="isBusy"><option value="easy">Dễ</option><option value="medium">Trung bình</option><option value="hard">Khó</option></select></label>
              <button type="button" class="ml-auto rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-4 py-2.5 text-xs font-black text-white shadow-lg disabled:cursor-not-allowed disabled:opacity-50" :disabled="isBusy || !canGenerate" @click="generate"><span v-if="isBusy">Đang tạo…</span><span v-else>Tạo câu hỏi</span></button>
            </div>
          </div>

          <div v-if="errorMessage" class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs font-bold text-rose-700"><p>{{ errorMessage }}</p><button type="button" class="mt-2 underline" :disabled="isBusy" @click="generate">Thử lại</button></div>
          <div v-if="isBusy" class="grid min-h-48 place-items-center"><div class="text-center"><span class="mx-auto block h-9 w-9 animate-spin rounded-full border-4 border-fuchsia-100 border-t-fuchsia-600"></span><p class="mt-3 text-sm font-black text-slate-700">AI đang tạo câu hỏi...</p><p class="mt-1 text-xs text-slate-500">{{ statusLabel }}</p></div></div>

          <div v-else-if="results.length" class="mt-5 space-y-2">
            <div class="flex items-center justify-between"><h3 class="text-sm font-black text-slate-900">Kết quả AI · {{ results.length }} câu</h3><button type="button" class="text-xs font-black text-violet-700" @click="toggleAll">{{ selectedIds.length === results.length ? 'Bỏ chọn tất cả' : 'Chọn tất cả' }}</button></div>
            <article v-for="(question, index) in results" :key="question.id" class="flex cursor-pointer gap-3 rounded-xl border p-3 transition" :class="selectedIds.includes(question.id) ? 'border-violet-400 bg-violet-50' : 'border-slate-200 hover:border-violet-200'" @click="toggle(question.id)">
              <input type="checkbox" class="mt-1 h-4 w-4 accent-violet-600" :checked="selectedIds.includes(question.id)" @click.stop @change="toggle(question.id)" />
              <div class="min-w-0 flex-1"><p class="text-sm font-bold leading-6 text-slate-900"><MathText :content="question.content" /></p><div class="mt-2 grid gap-1.5 sm:grid-cols-2"><div v-for="answer in question.answers" :key="answer.id || answer.content" class="rounded-lg px-2.5 py-2 text-xs" :class="answer.is_correct ? 'bg-emerald-50 font-bold text-emerald-800' : 'bg-white text-slate-600'"><MathText :content="answer.content" compact /></div></div></div>
              <span class="shrink-0 rounded-md bg-fuchsia-50 px-2 py-1 text-[10px] font-black text-fuchsia-700">Câu {{ index + 1 }}</span>
            </article>
          </div>
        </div>

        <footer class="flex items-center justify-between border-t border-slate-200 px-5 py-4"><span class="text-xs font-bold text-slate-500">Đã chọn: <b class="text-violet-700">{{ selectedIds.length }}</b> câu</span><div class="flex gap-2"><button type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-black" @click="close">Hủy</button><button type="button" class="rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-black text-white disabled:opacity-40" :disabled="!selectedIds.length || isBusy" @click="addSelected">Thêm {{ selectedIds.length }} câu</button></div></footer>
      </section>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue'
import { X } from 'lucide-vue-next'
import { getAiQuestionJob, normalizeAiQuestions, startAiQuestionGeneration } from '@/services/quiz-editor/aiQuestionApi.js'
import MathText from '../MathText.vue'

const props = defineProps({
  quizContext: { type: Object, default: () => ({}) },
  initialResults: { type: Array, default: () => [] },
  initialSelectedIds: { type: Array, default: () => [] },
})
const emit = defineEmits(['select', 'close', 'state-change'])
const form = reactive({ prompt: '', count: 5, difficulty: props.quizContext.difficulty || 'medium', education_level_id: props.quizContext.education_level_id || '', grade_id: props.quizContext.grade_id || '', subject_id: props.quizContext.subject_id || '', topic_name: props.quizContext.topic_name || '', curriculum_unit_ids: Array.isArray(props.quizContext.curriculum_unit_ids) ? [...props.quizContext.curriculum_unit_ids] : [] })
const results = ref(props.initialResults.map((question) => ({ ...question })))
const selectedIds = ref(props.initialSelectedIds.filter((id) => results.value.some((question) => question.id === id)))
const isBusy = ref(false)
const errorMessage = ref('')
const statusLabel = ref('Đang gửi yêu cầu...')
let pollTimer
let cancelled = false

const publishState = () => emit('state-change', {
  results: results.value.map((question) => ({ ...question })),
  selectedIds: [...selectedIds.value],
})

const taxonomyComplete = computed(() => Boolean(form.education_level_id && form.grade_id && form.subject_id))
const canGenerate = computed(() => form.prompt.trim() && Number(form.count) >= 1 && Number(form.count) <= 20 && taxonomyComplete.value)
const waitForJob = async (jobId) => {
  while (!cancelled) {
    const job = await getAiQuestionJob(jobId)
    statusLabel.value = job.current_step === 'calling_ai_api' ? 'AI đang suy nghĩ...' : 'Đang chuẩn hóa kết quả...'
    if (job.status === 'completed') return job
    if (job.status === 'failed') throw new Error(job.error_message || 'AI tạo câu hỏi thất bại.')
    await new Promise((resolve) => { pollTimer = setTimeout(resolve, 700) })
  }
  throw new Error('Đã hủy yêu cầu hiển thị.')
}
const generate = async () => {
  if (!canGenerate.value || isBusy.value) return
  const requestPrompt = form.prompt.trim()
  isBusy.value = true; errorMessage.value = ''; results.value = []; selectedIds.value = []; publishState(); statusLabel.value = 'Đang gửi yêu cầu...'
  try {
    const started = await startAiQuestionGeneration({ prompt: requestPrompt, count: Number(form.count), difficulty: form.difficulty, language: 'vi', education_level_id: form.education_level_id ? Number(form.education_level_id) : undefined, grade_id: form.grade_id ? Number(form.grade_id) : undefined, subject_id: form.subject_id ? Number(form.subject_id) : undefined, topic_name: form.topic_name || undefined, curriculum_unit_ids: form.curriculum_unit_ids })
    const job = await waitForJob(started.job_id)
    results.value = normalizeAiQuestions(job)
    if (!results.value.length) throw new Error('AI không trả về câu hỏi hợp lệ.')
    selectedIds.value = results.value.map((question) => question.id)
    publishState()
    form.prompt = ''
  } catch (error) { if (!cancelled) { form.prompt = requestPrompt; errorMessage.value = error?.response?.data?.message || error?.message || 'Không thể tạo câu hỏi bằng AI' } } finally { if (!cancelled) isBusy.value = false }
}
const toggle = (id) => { selectedIds.value = selectedIds.value.includes(id) ? selectedIds.value.filter((item) => item !== id) : [...selectedIds.value, id]; publishState() }
const toggleAll = () => { selectedIds.value = selectedIds.value.length === results.value.length ? [] : results.value.map((question) => question.id); publishState() }
const addSelected = () => emit('select', results.value.filter((question) => selectedIds.value.includes(question.id)).map((question) => ({ ...question, source_type: 'ai' })))
const close = () => { publishState(); emit('close') }
onBeforeUnmount(() => { cancelled = true; clearTimeout(pollTimer) })
</script>

<style scoped>
.field-label { @apply flex flex-col gap-1.5 text-[10px] font-black uppercase tracking-wider text-slate-500; }
.field { @apply w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold normal-case tracking-normal text-slate-800 outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100 disabled:bg-slate-100; }
</style>
