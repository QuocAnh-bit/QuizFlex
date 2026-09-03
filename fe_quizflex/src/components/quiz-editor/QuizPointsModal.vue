<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[100] grid place-items-center bg-slate-950/60 p-4" @click.self="$emit('close')">
      <section class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl">
        <div class="flex items-start justify-between gap-4">
          <div><h2 class="text-base font-black text-slate-900">Thiết lập điểm</h2><p class="mt-1 text-xs text-slate-500">Áp dụng nhanh cho {{ questionCount }} câu hỏi.</p></div>
          <button type="button" class="rounded-lg px-2 py-1 font-black text-slate-400 hover:bg-slate-100" @click="$emit('close')">✕</button>
        </div>

        <div class="mt-5">
          <div class="rounded-xl border border-violet-200 bg-violet-50/50 p-4">
            <p class="text-xs font-black text-slate-800">Chia đều theo tổng điểm</p>
            <p class="mt-1 text-[11px] text-slate-500">Phần lẻ được cân bằng ở câu cuối để tổng điểm luôn chính xác.</p>
            <div class="mt-3 flex items-center gap-2">
              <input v-model.number="targetTotal" type="number" min="0.01" step="1" class="min-w-0 flex-1 rounded-xl border border-violet-200 bg-white px-3 py-2.5 text-sm font-bold outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100" />
              <span class="text-xs font-bold text-slate-500">tổng điểm</span>
              <button type="button" class="apply-button" :disabled="!validTotal || !questionCount" @click="$emit('distribute', targetTotal)">Chia đều</button>
            </div>
          </div>
        </div>
      </section>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({ questionCount: { type: Number, default: 0 }, currentTotal: { type: Number, default: 0 } })
defineEmits(['distribute', 'close'])
const targetTotal = ref(props.currentTotal > 0 ? props.currentTotal : 100)
const validTotal = computed(() => Number.isFinite(Number(targetTotal.value)) && Number(targetTotal.value) >= props.questionCount * 0.01)
</script>

<style scoped>
.apply-button { @apply shrink-0 rounded-xl bg-violet-600 px-3 py-2.5 text-xs font-black text-white hover:bg-violet-700 disabled:cursor-not-allowed disabled:bg-slate-300; }
</style>
