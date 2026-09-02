<template>
  <div class="space-y-3">
    <p class="text-xs text-slate-500">Chọn đáp án đúng cho nhận định trên.</p>
    <div class="grid gap-2.5 sm:grid-cols-2">
      <label v-for="answer in question.answers" :key="answer.id" class="flex min-h-[52px] cursor-pointer items-center gap-3 rounded-xl border px-3 py-2.5 transition" :class="answer.is_correct ? 'border-emerald-300 bg-emerald-50/60' : 'border-slate-200 bg-white hover:border-violet-200 hover:bg-violet-50/20'">
        <input type="radio" :name="`true-false-${question.id}`" :checked="answer.is_correct" class="h-4 w-4 accent-emerald-600" @change="setCorrect(answer.id)" />
        <span class="grid h-8 w-8 place-items-center rounded-lg" :class="answer.content === 'Đúng' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"><Check v-if="answer.content === 'Đúng'" class="h-4 w-4" /><X v-else class="h-4 w-4" /></span>
        <span class="text-sm font-black text-slate-800">{{ answer.content }}</span>
      </label>
    </div>
  </div>
</template>
<script setup>
import { Check, X } from 'lucide-vue-next'
const props = defineProps({ question: { type: Object, required: true } })
const setCorrect = (id) => props.question.answers.forEach((answer) => { answer.is_correct = answer.id === id })
</script>
