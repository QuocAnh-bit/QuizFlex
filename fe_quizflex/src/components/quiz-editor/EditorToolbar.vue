<template>
  <aside class="editor-toolbar relative flex flex-col items-center border-l border-slate-200 bg-white py-3">
    <div class="editor-toolbar-actions flex w-full flex-col items-center gap-1.5 px-2">
      <button type="button" class="toolbar-icon toolbar-icon-primary" title="Thiết lập" aria-label="Thiết lập">
        <Settings class="h-4 w-4" />
      </button>
      <button type="button" class="toolbar-icon" title="Công cụ AI" aria-label="Mở công cụ AI" :disabled="aiReviewDisabled" @click="aiMenuOpen = !aiMenuOpen">
        <Sparkles class="h-4 w-4" />
      </button>
      <div v-if="aiMenuOpen" class="ai-menu">
        <button type="button" @click="chooseAiAction('generate-ai')"><Bot class="h-4 w-4" /><span><b>Tạo câu hỏi bằng AI</b><small>Tạo câu mới theo chủ đề Quiz</small></span></button>
        <button type="button" :disabled="!question" @click="chooseAiAction('review-quiz')"><ScanSearch class="h-4 w-4" /><span><b>AI nhận xét bộ đề</b><small>Phân tích toàn bộ câu hỏi</small></span></button>
        <button type="button" :disabled="!question" @click="chooseAiAction('similar-questions')"><ListChecks class="h-4 w-4" /><span><b>Tạo câu hỏi tương tự</b><small>Chọn câu mẫu ở danh sách trái</small></span></button>
      </div>
      <button type="button" class="toolbar-icon" title="Hình ảnh" aria-label="Thêm hình ảnh cho câu hỏi" :disabled="!question" @click="$emit('add-image')">
        <Image class="h-4 w-4" />
      </button>

      <span class="my-1 h-px w-7 bg-slate-200"></span>

      <button type="button" class="toolbar-icon" title="Di chuyển lên" aria-label="Di chuyển câu hỏi lên" :disabled="!canMoveUp" @click="$emit('move-question', -1)">
        <ArrowUp class="h-4 w-4" />
      </button>
      <button type="button" class="toolbar-icon" title="Di chuyển xuống" aria-label="Di chuyển câu hỏi xuống" :disabled="!canMoveDown" @click="$emit('move-question', 1)">
        <ArrowDown class="h-4 w-4" />
      </button>
      <button type="button" class="toolbar-icon" title="Nhân bản" aria-label="Nhân bản câu hỏi" :disabled="!question" @click="$emit('duplicate-question')">
        <Copy class="h-4 w-4" />
      </button>
      <button type="button" class="toolbar-icon toolbar-icon-danger" title="Xóa" aria-label="Xóa câu hỏi" :disabled="!question" @click="$emit('remove-question')">
        <Trash2 class="h-4 w-4" />
      </button>
    </div>
  </aside>
</template>

<script setup>
import { ref } from 'vue'
import { ArrowDown, ArrowUp, Bot, Copy, Image, ListChecks, ScanSearch, Settings, Sparkles, Trash2 } from 'lucide-vue-next'

defineProps({ question: { type: Object, default: null }, canMoveUp: { type: Boolean, default: false }, canMoveDown: { type: Boolean, default: false }, aiReviewDisabled: Boolean })
const emit = defineEmits(['generate-ai', 'review-quiz', 'similar-questions', 'add-image', 'move-question', 'duplicate-question', 'remove-question'])
const aiMenuOpen = ref(false)
const chooseAiAction = (action) => { aiMenuOpen.value = false; emit(action) }
</script>

<style scoped>
.toolbar-icon { @apply grid h-10 w-10 place-items-center rounded-xl border border-transparent text-slate-500 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-600 disabled:cursor-not-allowed disabled:opacity-40; }
.toolbar-icon-primary { @apply bg-violet-100 text-violet-700; }
.toolbar-icon-danger { @apply hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600; }
.ai-menu { @apply absolute right-[58px] top-14 z-30 w-64 overflow-hidden rounded-xl border border-slate-200 bg-white p-1.5 shadow-2xl; }
.ai-menu button { @apply flex w-full items-start gap-3 rounded-lg px-3 py-2.5 text-left text-slate-600 transition hover:bg-violet-50 hover:text-violet-700 disabled:cursor-not-allowed disabled:opacity-40; }
.ai-menu button > svg { @apply mt-0.5 shrink-0; }
.ai-menu span { @apply grid gap-0.5; }
.ai-menu b { @apply text-xs; }
.ai-menu small { @apply text-[10px] font-semibold text-slate-400; }
@media (max-width: 1023px) {
  .editor-toolbar { flex-direction: row; justify-content: center; padding: .375rem max(.5rem, env(safe-area-inset-right)) max(.375rem, env(safe-area-inset-bottom)) max(.5rem, env(safe-area-inset-left)); }
  .editor-toolbar-actions { width: auto; max-width: 100%; flex-direction: row; justify-content: center; gap: .25rem; overflow-x: auto; padding: 0; }
  .editor-toolbar-actions > span { width: 1px; height: 1.75rem; margin: 0 .125rem; }
  .toolbar-icon { width: 2.5rem; height: 2.5rem; flex-shrink: 0; }
  .ai-menu { position: fixed; right: .75rem; bottom: 4.25rem; top: auto; }
}
@media (max-width: 420px) { .editor-toolbar-actions { justify-content: space-between; width: 100%; } .toolbar-icon { width: 2.25rem; height: 2.25rem; } }
</style>
