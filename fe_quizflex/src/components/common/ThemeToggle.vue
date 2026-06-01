<template>
  <button
    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-[var(--border)] bg-[var(--surface-soft)] text-base hover:bg-[var(--border-light)] hover:shadow-lg active:scale-95 transition duration-200"
    type="button"
    @click="toggleTheme"
    :title="label"
  >
    {{ icon }}
  </button>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'

const theme = ref('dark')

const label = computed(() => theme.value === 'dark' ? 'Giao diện tối' : 'Giao diện sáng')
const icon = computed(() => theme.value === 'dark' ? '☾' : '☀')

function applyTheme(value) {
  theme.value = value
  document.documentElement.setAttribute('data-theme', value)
  localStorage.setItem('quizflex-theme', value)
}

function toggleTheme() {
  applyTheme(theme.value === 'dark' ? 'light' : 'dark')
}

onMounted(() => {
  const savedTheme = localStorage.getItem('quizflex-theme')
  applyTheme(savedTheme || 'dark')
})
</script>
