<template>
  <AnswerList :question="question" mode="single" @add-answer="$emit('add-answer')" @remove-answer="$emit('remove-answer', $event)" @edit-formula="$emit('edit-formula', $event)" />
</template>
<script setup>
import AnswerList from './shared/AnswerList.vue'
defineProps({ question: { type: Object, required: true } })
defineEmits(['add-answer', 'remove-answer', 'edit-formula'])
</script>
