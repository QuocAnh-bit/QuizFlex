<template>
  <Teleport to="body">
    <div v-if="open" class="math-formula-overlay fixed inset-0 z-[140] flex items-start justify-center overflow-y-auto bg-slate-950/35 p-4 pt-[clamp(1rem,8vh,5rem)] backdrop-blur-[2px]" @click.self="cancel">
      <section class="math-formula-dialog w-full max-w-xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="math-formula-title">
        <header class="flex items-center justify-between px-5 pb-3 pt-4">
          <h2 id="math-formula-title" class="text-lg font-black text-slate-900">{{ editing ? 'Sửa công thức' : 'Nhập công thức' }}</h2>
          <button type="button" class="grid h-9 w-9 place-items-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Đóng" @click="cancel"><X class="h-5 w-5" /></button>
        </header>
        <div class="px-5 pb-4">
          <component :is="'math-field'" ref="mathField" class="math-formula-field" math-virtual-keyboard-policy="manual" smart-fence @input="handleInput" />
        </div>
        <footer class="flex items-center justify-between border-t border-slate-200 bg-slate-50/70 px-5 py-3.5"><span class="text-xs font-semibold text-slate-500">Dùng bàn phím MathLive bên dưới</span><div class="flex gap-2"><button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700 hover:bg-slate-50" @click="cancel">Hủy</button><button type="button" class="rounded-xl bg-violet-600 px-5 py-2.5 text-xs font-black text-white shadow-lg shadow-violet-200 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!draft.trim()" @click="confirmFormula">{{ editing ? 'Cập nhật' : 'Nhập vào' }}</button></div></footer>
      </section>
    </div>
  </Teleport>
</template>

<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { X } from 'lucide-vue-next'
import 'mathlive'
import 'mathlive/fonts.css'

const props = defineProps({ modelValue: { type: String, default: '' }, open: { type: Boolean, default: false }, editing: { type: Boolean, default: false } })
const emit = defineEmits(['update:modelValue', 'close', 'confirm'])
const mathField = ref(null)
const draft = ref('')
const hideKeyboard = () => window.mathVirtualKeyboard?.hide({ animate: true })
const focusMathField = async () => {
  await nextTick()
  if (!mathField.value) return
  mathField.value.value = draft.value
  mathField.value.mathVirtualKeyboardPolicy = 'manual'
  mathField.value.focus()
  window.mathVirtualKeyboard?.show({ animate: true })
}
const handleInput = (event) => { draft.value = event.target.value; emit('update:modelValue', draft.value) }
const cancel = () => { hideKeyboard(); emit('close') }
const confirmFormula = () => { if (!draft.value.trim()) return; hideKeyboard(); emit('confirm', draft.value.trim()) }
watch(() => props.open, (isOpen) => { if (isOpen) { draft.value = props.modelValue; focusMathField() } else hideKeyboard() }, { immediate: true })
onBeforeUnmount(hideKeyboard)
</script>

<style scoped>
.math-formula-field { display: block; width: 100%; min-height: 64px; border: 1px solid rgb(139 92 246); border-radius: 0.75rem; background: white; padding: 0.75rem; color: rgb(30 41 59); font-size: 1.2rem; outline: none; box-shadow: 0 0 0 2px rgb(237 233 254); }
@media (max-width: 639px) {
  .math-formula-overlay { padding: .5rem; padding-top: .5rem; }
  .math-formula-dialog { border-radius: 1rem; }
  .math-formula-dialog header, .math-formula-dialog > div, .math-formula-dialog footer { padding-left: .875rem; padding-right: .875rem; }
  .math-formula-dialog footer { align-items: flex-end; gap: .5rem; }
  .math-formula-dialog footer > span { max-width: 7rem; font-size: .65rem; }
  .math-formula-field { min-height: 56px; font-size: 1rem; }
}
</style>
<style>body > .ML__keyboard { --keyboard-zindex: 170; max-width: 100vw; }</style>
