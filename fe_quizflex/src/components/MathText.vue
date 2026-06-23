<template>
  <span v-html="rendered"></span>
</template>

<script setup>
import { computed } from "vue";
import katex from "katex";
import "katex/dist/katex.min.css";

const props = defineProps({
  text: {
    type: String,
    default: "",
  },
});

const rendered = computed(() => {
  if (!props.text) return "";

  return props.text.replace(/\$(.*?)\$/g, (_, formula) => {
    try {
      console.log(props.text);

      return katex.renderToString(formula.trim(), {
        throwOnError: false,
        displayMode: false, // inline
      });
    } catch {
      return `${formula}$`;
    }
  });
});
</script>
