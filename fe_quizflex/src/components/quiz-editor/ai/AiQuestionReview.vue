<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[120] grid place-items-center overflow-y-auto bg-slate-950/45 p-4 backdrop-blur-sm" @click.self="$emit('close')">
      <section class="flex max-h-[90dvh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="ai-review-title">
        <header class="flex items-start justify-between border-b border-slate-200 px-5 py-4"><div><p class="text-[10px] font-black uppercase tracking-[.16em] text-fuchsia-600">QuizFlex AI</p><h2 id="ai-review-title" class="mt-1 text-xl font-black text-slate-900">AI phân tích câu hỏi</h2><p class="mt-1 text-xs text-slate-500">AI chỉ đưa ra nhận xét và gợi ý, không tự thay đổi câu hỏi.</p></div><button type="button" class="grid h-9 w-9 place-items-center rounded-xl text-slate-400 hover:bg-slate-100" aria-label="Đóng" @click="$emit('close')"><X class="h-5 w-5" /></button></header>

        <div class="min-h-0 flex-1 overflow-y-auto p-5">
          <div v-if="isLoading" class="grid min-h-64 place-items-center text-center"><div><LoaderCircle class="mx-auto h-9 w-9 animate-spin text-fuchsia-600" /><p class="mt-3 text-sm font-black text-slate-800">AI đang phân tích...</p><p class="mt-1 text-xs text-slate-500">Đang kiểm tra nội dung, đáp án và độ phù hợp với Quiz.</p></div></div>
          <div v-else-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 p-5 text-center"><p class="text-sm font-black text-rose-800">Không thể phân tích câu hỏi lúc này.</p><p class="mt-1 text-xs text-rose-600">{{ errorMessage }}</p><button type="button" class="mt-4 rounded-xl border border-rose-300 bg-white px-4 py-2 text-xs font-black text-rose-700" @click="requestReview">Thử lại</button></div>
          <template v-else-if="review">
            <section class="rounded-xl border p-4" :class="review.overallStatus === 'good' ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'"><div class="flex items-center justify-between gap-3"><p class="text-xs font-black uppercase tracking-wider" :class="review.overallStatus === 'good' ? 'text-emerald-700' : 'text-amber-700'">Đánh giá tổng quan</p><span class="rounded-full bg-white px-2.5 py-1 text-xs font-black text-slate-700">{{ review.score }}/100</span></div><div class="mt-2 text-sm leading-6 text-slate-800"><MathText :content="review.summary" /></div></section>

            <section v-if="review.issues.length" class="mt-5"><h3 class="text-[10px] font-black uppercase tracking-wider text-slate-500">Vấn đề phát hiện</h3><div class="mt-2 space-y-2"><div v-for="(issue, index) in review.issues" :key="`${issue.category}-${index}`" class="flex gap-2 rounded-xl border px-3 py-2.5 text-xs" :class="issueClass[issue.severity]"><component :is="issue.severity === 'error' ? CircleX : issue.severity === 'info' ? Info : TriangleAlert" class="mt-0.5 h-4 w-4 shrink-0" /><div><p class="font-black">{{ categoryLabels[issue.category] || issue.category }}</p><div class="mt-0.5 leading-5"><MathText :content="issue.message" compact /></div></div></div></div></section>

            <section v-if="review.suggestions.length" class="mt-5"><h3 class="text-[10px] font-black uppercase tracking-wider text-slate-500">Gợi ý</h3><div class="mt-2 space-y-2"><div v-for="(suggestion, index) in review.suggestions" :key="`${suggestion.field}-${index}`" class="rounded-xl border border-violet-100 bg-violet-50/60 p-3"><p class="text-xs font-black text-violet-800">{{ fieldLabels[suggestion.field] || suggestion.field }}</p><div class="mt-1 text-xs leading-5 text-slate-700"><MathText :content="suggestion.reason" compact /></div></div></div></section>

            <section class="mt-5 rounded-xl border border-slate-200 p-4"><p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Câu hỏi đã phân tích</p><div class="mt-2 text-sm font-bold leading-6 text-slate-900"><MathText :content="question.content" /></div><div class="mt-3 space-y-1.5"><div v-for="(answer, index) in displayedAnswers" :key="answer.id || index" class="flex gap-2 rounded-lg bg-slate-50 px-3 py-2 text-xs"><span class="font-black" :class="answer.is_correct || question.type === 'fill_in' ? 'text-emerald-700' : 'text-slate-500'">{{ String.fromCharCode(65 + index) }}.</span><MathText :content="answer.content" compact /></div></div></section>
          </template>
        </div>

        <footer class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4"><button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700" @click="$emit('close')">Đóng</button><button type="button" class="rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-black text-white disabled:cursor-not-allowed disabled:opacity-40" :disabled="isLoading || !hasApplicableSuggestion" @click="applySuggestion">Áp dụng gợi ý</button></footer>
      </section>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { CircleX, Info, LoaderCircle, TriangleAlert, X } from 'lucide-vue-next'
import MathText from '@/components/MathText.vue'
import { reviewQuestion } from '@/services/quiz-editor/questionReviewApi.js'

const props = defineProps({ question: { type: Object, required: true }, quizContext: { type: Object, required: true } })
const emit = defineEmits(['close', 'apply', 'loading'])
const review = ref(null)
const isLoading = ref(false)
const errorMessage = ref('')
const targetQuestionId = props.question.id
let requestVersion = 0
const displayedAnswers = computed(() => props.question.type === 'fill_in' ? props.question.accepted_answers || [] : props.question.answers || [])
const hasApplicableSuggestion = computed(() => review.value && Object.keys(review.value.suggestedQuestion || {}).some((key) => ['content', 'answers', 'accepted_answers'].includes(key)))
const issueClass = { info: 'border-sky-100 bg-sky-50 text-sky-800', warning: 'border-amber-200 bg-amber-50 text-amber-800', error: 'border-rose-200 bg-rose-50 text-rose-800' }
const categoryLabels = { accuracy: 'Độ chính xác', clarity: 'Độ rõ ràng', grade: 'Phù hợp với lớp', subject: 'Phù hợp với môn', topic: 'Phù hợp với chủ đề', difficulty: 'Độ khó', answers: 'Chất lượng đáp án', correct_answer: 'Đáp án đúng', ambiguity: 'Khả năng gây hiểu nhầm', language: 'Chính tả và diễn đạt', other: 'Nhận xét khác' }
const fieldLabels = { content: 'Nội dung câu hỏi', answers: 'Danh sách đáp án', accepted_answers: 'Đáp án được chấp nhận', explanation: 'Giải thích' }

const requestReview = async () => {
  if (isLoading.value) return
  const version = ++requestVersion
  isLoading.value = true; errorMessage.value = ''; emit('loading', true)
  try { const result = await reviewQuestion(props.quizContext, props.question); if (version === requestVersion && props.question.id === targetQuestionId) review.value = result }
  catch (error) { if (version === requestVersion) errorMessage.value = error?.response?.data?.message || 'Vui lòng thử lại sau.' }
  finally { if (version === requestVersion) { isLoading.value = false; emit('loading', false) } }
}
const applySuggestion = () => { if (hasApplicableSuggestion.value) emit('apply', { questionId: targetQuestionId, suggestedQuestion: review.value.suggestedQuestion }) }
onMounted(requestReview)
onBeforeUnmount(() => { requestVersion += 1; emit('loading', false) })
</script>
