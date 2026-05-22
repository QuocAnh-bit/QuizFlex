<template>
  <button class="btn-ghost px-4 py-2" type="button" @click="toggleTheme">
    <span>{{ icon }}</span>
    <span class="hidden sm:inline">{{ label }}</span>
  </button>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const theme = ref('dark')

const label = computed(() => theme.value === 'dark' ? t('components.ThemeToggle.dark_mode') : t('components.ThemeToggle.light_mode'))
const icon = computed(() => theme.value === 'dark' ? t('components.ThemeToggle.dark_icon') : t('components.ThemeToggle.light_icon'))

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
