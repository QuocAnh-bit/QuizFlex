<template>
  <section v-if="isLoadingDrafts" class="grid min-h-[calc(100dvh-140px)] place-items-center px-4" role="status" aria-live="polite">
    <div class="text-center"><span class="mx-auto block h-10 w-10 animate-spin rounded-full border-4 border-violet-100 border-t-violet-600"></span><h1 class="mt-4 text-base font-black text-slate-900">Đang chuẩn bị trang tạo Quiz...</h1><p class="mt-1 text-xs text-slate-500">Đang kiểm tra các Quiz bạn còn làm dở.</p></div>
  </section>
  <section v-else class="mx-auto w-full max-w-5xl py-6">
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
      <header class="border-b border-slate-200 bg-gradient-to-r from-violet-50 to-fuchsia-50 px-6 py-6 sm:px-8"><p class="text-[11px] font-black uppercase tracking-[.18em] text-violet-600">QuizFlex Studio</p><h1 class="mt-2 text-2xl font-black text-slate-900">Tạo Quiz mới</h1><p class="mt-1 text-sm text-slate-500">Thiết lập thông tin cho bộ câu hỏi của bạn.</p></header>
      <div class="p-6 sm:p-8">
        <QuizSetupForm :is-submitting="isCreating" :external-error="createError" submit-label="Bắt đầu" require-topic @submit="createDraft" @cancel="router.back()" />
        <div v-if="createdQuizId" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs font-bold text-amber-800">Quiz #{{ createdQuizId }} đã được tạo. Nếu chưa chuyển trang, <button type="button" class="underline" @click="navigateToEditor">mở Editor</button>.</div>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="showDraftDecision" class="fixed inset-0 z-[110] grid place-items-center overflow-y-auto bg-slate-950/45 p-4 backdrop-blur-sm">
        <section class="w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="unfinished-quizzes-title">
          <header class="border-b border-slate-200 bg-gradient-to-r from-amber-50 to-violet-50 px-5 py-5 sm:px-6"><p class="text-[10px] font-black uppercase tracking-[.16em] text-amber-700">Quiz đang làm dở</p><h2 id="unfinished-quizzes-title" class="mt-1 text-xl font-black text-slate-900">Bạn muốn tiếp tục Quiz cũ?</h2><p class="mt-1 text-xs leading-5 text-slate-600">Bạn đang có Quiz chưa có câu hỏi. Hãy tiếp tục một Quiz hoặc tạo một Quiz hoàn toàn mới.</p></header>
          <div class="grid gap-3 p-5 sm:grid-cols-3">
            <button v-for="draft in recentEmptyDrafts" :key="draft.id" type="button" class="group rounded-xl border border-slate-200 bg-white p-4 text-left transition hover:border-violet-300 hover:bg-violet-50/40 hover:shadow-sm" @click="continueDraft(draft.id)">
              <span class="block truncate text-sm font-black text-slate-900">{{ draft.title || 'Quiz chưa đặt tên' }}</span>
              <span class="mt-1 block truncate text-[11px] font-semibold text-slate-500">{{ [draft.subject_name, draft.grade_name].filter(Boolean).join(' · ') || 'Quiz rỗng' }}</span>
              <span class="mt-1 block truncate text-[11px] text-slate-500">{{ draft.topic_name || 'Chưa có chủ đề' }}</span>
              <span class="mt-3 block text-xs font-black text-violet-700">Tiếp tục sửa →</span>
            </button>
          </div>
          <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-6"><p class="text-[11px] font-semibold text-slate-500">Tối đa 3 Quiz rỗng gần nhất được hiển thị.</p><button type="button" class="rounded-xl bg-violet-600 px-5 py-3 text-xs font-black text-white shadow-md shadow-violet-200 transition hover:bg-violet-700" @click="showDraftDecision = false">Tạo Quiz mới</button></footer>
        </section>
      </div>
    </Teleport>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import QuizSetupForm from '@/components/quiz-create/QuizSetupForm.vue'
import { createEmptyQuizDraft, getRecentEmptyQuizDrafts } from '@/services/quiz-editor/quizDraftApi.js'

const router = useRouter()
const isCreating = ref(false)
const createError = ref('')
const createdQuizId = ref(null)
const recentEmptyDrafts = ref([])
const isLoadingDrafts = ref(true)
const showDraftDecision = ref(false)
const editorQuery = { openQuestionSources: '1' }
const navigateToEditor = async () => {
  if (!createdQuizId.value) return
  await router.push({ name: 'quiz-editor-v2-edit', params: { id: createdQuizId.value }, query: editorQuery })
}
const continueDraft = (quizId) => router.push({ name: 'quiz-editor-v2-edit', params: { id: quizId }, query: editorQuery })
const createDraft = async (form) => {
  if (isCreating.value || createdQuizId.value) return
  isCreating.value = true; createError.value = ''
  try { const quiz = await createEmptyQuizDraft(form); createdQuizId.value = quiz?.id; if (!createdQuizId.value) throw new Error('Backend không trả về mã Quiz.'); await navigateToEditor() } catch (error) { createError.value = createdQuizId.value ? 'Quiz đã được tạo nhưng chưa thể mở Editor. Hãy dùng nút mở Editor bên dưới.' : (error?.response?.data?.message || error?.message || 'Không thể tạo Quiz Draft.') } finally { isCreating.value = false }
}
onMounted(async () => {
  try { recentEmptyDrafts.value = await getRecentEmptyQuizDrafts(3); showDraftDecision.value = recentEmptyDrafts.value.length > 0 }
  catch { recentEmptyDrafts.value = [] }
  finally { isLoadingDrafts.value = false }
})
</script>
