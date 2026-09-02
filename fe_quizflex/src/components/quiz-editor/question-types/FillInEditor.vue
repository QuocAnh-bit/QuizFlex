<template>
  <div class="space-y-3">
    <div><h3 class="text-sm font-black text-slate-800">Đáp án được chấp nhận</h3><p class="mt-1 text-xs text-slate-500">Thêm các cách trả lời đúng có thể chấp nhận.</p></div>
    <div v-for="(answer, index) in question.accepted_answers" :key="answer.id" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3">
      <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-violet-50 text-xs font-black text-violet-600">{{ index + 1 }}</span>
      <MixedContentEditor v-model="answer.content" compact class="min-w-0 flex-1" placeholder="Nhập đáp án đúng..." @edit-formula="$emit('edit-formula', { type: 'fill_in_answer', target: answer, formula: $event })" />
      <button type="button" class="grid h-8 w-8 place-items-center rounded-lg text-slate-300 hover:bg-violet-50 hover:text-violet-600" title="Chèn công thức vào đáp án" @click="$emit('edit-formula', { type: 'fill_in_answer', target: answer })"><Sigma class="h-4 w-4" /><span class="sr-only">Công thức</span></button>
      <button type="button" class="grid h-8 w-8 place-items-center rounded-lg text-slate-300 hover:bg-rose-50 hover:text-rose-500" :disabled="question.accepted_answers.length <= 1" @click="$emit('remove-fill-answer', index)"><X class="h-4 w-4" /></button>
    </div>
    <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-dashed border-violet-300 bg-violet-50 px-4 py-2.5 text-xs font-black text-violet-700 hover:bg-violet-100" @click="$emit('add-fill-answer')"><Plus class="h-4 w-4" /> Thêm đáp án tương đương</button>
  </div>
</template>
<script setup>
import { Plus, Sigma, X } from 'lucide-vue-next'
import MixedContentEditor from '../MixedContentEditor.vue'
defineProps({ question: { type: Object, required: true } })
defineEmits(['add-fill-answer', 'remove-fill-answer', 'edit-formula'])
</script>
