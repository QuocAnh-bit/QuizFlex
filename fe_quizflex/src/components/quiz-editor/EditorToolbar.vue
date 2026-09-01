<template>
  <aside class="editor-toolbar flex flex-col items-center border-l border-slate-200 bg-white py-3">
    <div class="editor-toolbar-actions flex w-full flex-col items-center gap-1.5 px-2">
      <button type="button" class="toolbar-icon toolbar-icon-primary" title="Thiết lập" aria-label="Thiết lập">
        <Settings class="h-4 w-4" />
      </button>
      <button type="button" class="toolbar-icon" title="AI" aria-label="AI">
        <Sparkles class="h-4 w-4" />
      </button>
      <button type="button" class="toolbar-icon" title="Hình ảnh" aria-label="Hình ảnh">
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
import { ArrowDown, ArrowUp, Copy, Image, Settings, Sparkles, Trash2 } from 'lucide-vue-next'

defineProps({ question: { type: Object, default: null }, canMoveUp: { type: Boolean, default: false }, canMoveDown: { type: Boolean, default: false } })
defineEmits(['move-question', 'duplicate-question', 'remove-question'])
</script>

<style scoped>
.toolbar-icon { @apply grid h-10 w-10 place-items-center rounded-xl border border-transparent text-slate-500 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-600 disabled:cursor-not-allowed disabled:opacity-40; }
.toolbar-icon-primary { @apply bg-violet-100 text-violet-700; }
.toolbar-icon-danger { @apply hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600; }
@media (max-width: 1023px) {
  .editor-toolbar { flex-direction: row; justify-content: center; padding: .375rem max(.5rem, env(safe-area-inset-right)) max(.375rem, env(safe-area-inset-bottom)) max(.5rem, env(safe-area-inset-left)); }
  .editor-toolbar-actions { width: auto; max-width: 100%; flex-direction: row; justify-content: center; gap: .25rem; overflow-x: auto; padding: 0; }
  .editor-toolbar-actions > span { width: 1px; height: 1.75rem; margin: 0 .125rem; }
  .toolbar-icon { width: 2.5rem; height: 2.5rem; flex-shrink: 0; }
}
@media (max-width: 420px) { .editor-toolbar-actions { justify-content: space-between; width: 100%; } .toolbar-icon { width: 2.25rem; height: 2.25rem; } }
</style>
