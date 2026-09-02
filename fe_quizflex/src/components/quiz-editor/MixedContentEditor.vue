<template>
  <div class="relative">
    <div ref="editor" class="mixed-editor" :class="{ 'mixed-editor--compact': compact }" contenteditable="true" role="textbox" aria-multiline="true" :data-placeholder="placeholder" @input="handleInput" @click="handleClick" @keyup="rememberCaret" @mouseup="rememberCaret"></div>
    <span v-if="!modelValue" class="pointer-events-none absolute left-0 top-0 font-bold text-slate-300" :class="compact ? 'text-sm leading-5' : 'text-lg leading-7 sm:text-xl'">{{ placeholder }}</span>
  </div>
</template>

<script setup>
import { nextTick, onMounted, ref, watch } from 'vue'
import { renderMathContent } from '../katex'

const props = defineProps({ modelValue: { type: String, default: '' }, placeholder: { type: String, default: '' }, compact: { type: Boolean, default: false } })
const emit = defineEmits(['update:modelValue', 'edit-formula'])
const editor = ref(null)
const caretOffset = ref(0)

const formulaPattern = /\$\$([\s\S]+?)\$\$|\\\[([\s\S]+?)\\\]|\\\(([\s\S]+?)\\\)|\$([^$\n]+?)\$/g
const parseContent = (content) => {
  const parts = []
  let lastIndex = 0
  let match
  formulaPattern.lastIndex = 0
  while ((match = formulaPattern.exec(content)) !== null) {
    if (match.index > lastIndex) parts.push({ type: 'text', value: content.slice(lastIndex, match.index) })
    parts.push({ type: 'formula', raw: match[0], latex: match[1] ?? match[2] ?? match[3] ?? match[4] ?? '', start: match.index, end: match.index + match[0].length })
    lastIndex = match.index + match[0].length
  }
  if (lastIndex < content.length) parts.push({ type: 'text', value: content.slice(lastIndex) })
  return parts
}
const serializeNode = (node) => {
  if (node.nodeType === Node.TEXT_NODE) return node.textContent || ''
  if (node.nodeType !== Node.ELEMENT_NODE) return ''
  if (node.dataset?.formulaRaw) return node.dataset.formulaRaw
  if (node.tagName === 'BR') return '\n'
  const value = Array.from(node.childNodes).map(serializeNode).join('')
  return node.tagName === 'DIV' ? `${value}\n` : value
}
const serializeEditor = () => Array.from(editor.value?.childNodes || []).map(serializeNode).join('').replace(/\n$/, '')
const renderEditor = (content) => {
  if (!editor.value) return
  const fragment = document.createDocumentFragment()
  parseContent(content).forEach((part) => {
    if (part.type === 'text') {
      fragment.append(document.createTextNode(part.value))
      return
    }
    const formula = document.createElement('span')
    formula.className = 'mixed-editor-formula'
    formula.contentEditable = 'false'
    formula.dataset.formulaRaw = part.raw
    formula.dataset.formulaLatex = part.latex
    formula.setAttribute('role', 'button')
    formula.setAttribute('tabindex', '0')
    formula.setAttribute('title', 'Click để sửa công thức')
    formula.innerHTML = renderMathContent(part.raw, { compact: true })
    fragment.append(formula)
  })
  editor.value.replaceChildren(fragment)
}
const rememberCaret = () => {
  const selection = window.getSelection()
  if (!selection?.rangeCount || !editor.value?.contains(selection.anchorNode)) return
  const range = selection.getRangeAt(0).cloneRange()
  range.selectNodeContents(editor.value)
  range.setEnd(selection.anchorNode, selection.anchorOffset)
  const holder = document.createElement('div')
  holder.append(range.cloneContents())
  caretOffset.value = Array.from(holder.childNodes).map(serializeNode).join('').length
}
const handleInput = () => { const value = serializeEditor(); rememberCaret(); emit('update:modelValue', value) }
const handleClick = (event) => {
  const formulaElement = event.target.closest?.('[data-formula-raw]')
  if (!formulaElement) { rememberCaret(); return }
  const raw = formulaElement.dataset.formulaRaw
  const content = serializeEditor()
  let start = 0
  for (const node of editor.value.childNodes) {
    if (node === formulaElement) break
    start += serializeNode(node).length
  }
  emit('edit-formula', { latex: formulaElement.dataset.formulaLatex || '', raw, start, end: start + raw.length, content })
}
const getCursorOffset = () => caretOffset.value
const focus = async () => { await nextTick(); editor.value?.focus() }
defineExpose({ getCursorOffset, focus })

watch(() => props.modelValue, (value) => {
  if (serializeEditor() !== value) renderEditor(value)
})
onMounted(() => renderEditor(props.modelValue))
</script>

<style>
.mixed-editor { min-width: 0; max-width: 100%; min-height: 56px; white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word; border: 0; outline: none; color: rgb(15 23 42); font-size: 1.125rem; font-weight: 700; line-height: 1.75rem; }
.mixed-editor--compact { min-height: 1.25rem; font-size: 0.875rem; line-height: 1.25rem; }
.mixed-editor-formula { display: inline-flex; max-width: 100%; cursor: pointer; align-items: center; overflow-x: auto; vertical-align: middle; margin: 0 0.15rem; border-radius: 0.4rem; padding: 0.05rem 0.25rem; transition: background-color 150ms, box-shadow 150ms; }
.mixed-editor-formula:hover { background: rgb(245 243 255); box-shadow: 0 0 0 1px rgb(196 181 253); }
.mixed-editor-formula:focus { background: rgb(237 233 254); box-shadow: 0 0 0 2px rgb(196 181 253); outline: none; }
@media (min-width: 640px) { .mixed-editor:not(.mixed-editor--compact) { font-size: 1.25rem; } }
</style>
