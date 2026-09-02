<template>
  <span v-if="rendered" class="math-content" :class="{ 'math-content--compact': compact }" v-html="rendered"></span>
</template>

<script setup>
import { computed } from 'vue'
import 'katex/dist/katex.min.css'
import { renderMathContent } from './katex'

const props = defineProps({
  text: { type: String, default: '' },
  content: { type: String, default: '' },
  compact: { type: Boolean, default: false },
  mathOnly: { type: Boolean, default: false },
})

const rendered = computed(() => renderMathContent(props.content || props.text, {
  compact: props.compact,
  mathOnly: props.mathOnly,
}))
</script>

<style>
.math-content { white-space: normal; overflow-wrap: anywhere; }
.math-content .math-content__block { display: block; max-width: 100%; overflow-x: auto; padding: 0.25rem 0; text-align: center; }
.math-content--compact { display: inline; }
.math-content--compact .katex { font-size: 1em; }
</style>
