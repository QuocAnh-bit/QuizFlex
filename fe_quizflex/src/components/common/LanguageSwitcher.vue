<template>
  <div class="relative language-switcher-container" ref="containerRef">
    <button
      type="button"
      @click="isOpen = !isOpen"
      :title="$t('common.language')"
      class="flex h-10 shrink-0 items-center gap-1.5 rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 text-sm font-bold text-[var(--muted)] transition duration-200 hover:-translate-y-0.5 hover:bg-[var(--border-light)] hover:text-[var(--text)] hover:shadow-lg active:scale-95"
    >
      <!-- <span class="text-base leading-none">{{ currentLocale.flag }}</span> -->
      <span class="hidden sm:inline">{{ currentLocale.code.toUpperCase() }}</span>
    </button>

    <transition name="dropdown-slide">
      <div
        v-if="isOpen"
        class="absolute right-0 top-full mt-2 w-44 rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-1.5 shadow-2xl backdrop-blur-xl z-50"
      >
        <button
          v-for="item in locales"
          :key="item.code"
          type="button"
          @click="selectLocale(item.code)"
          :class="[
            'flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm font-bold transition',
            locale === item.code
              ? 'bg-[var(--chip-active)] text-[var(--text)]'
              : 'text-[var(--muted)] hover:bg-[var(--surface-soft)] hover:text-[var(--text)]'
          ]"
        >
          <span class="text-base leading-none">{{ item.flag }}</span>
          <span>{{ item.label }}</span>
        </button>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'

const { locale } = useI18n()
const isOpen = ref(false)
const containerRef = ref(null)

// Tên hiển thị của mỗi ngôn ngữ giữ nguyên bản ngữ, không dịch theo locale hiện tại
const locales = [
  { code: 'vi', label: 'Tiếng Việt', flag: '🇻🇳' },
  { code: 'en', label: 'English', flag: '🇬🇧' },
  { code: 'zh', label: '中文', flag: '🇨🇳' },
  { code: 'ja', label: '日本語', flag: '🇯🇵' },
]

const currentLocale = computed(
  () => locales.find((item) => item.code === locale.value) || locales[0]
)

const selectLocale = (code) => {
  locale.value = code
  localStorage.setItem('quizflex_locale', code)
  isOpen.value = false
}

const closeDropdown = (e) => {
  if (containerRef.value && !containerRef.value.contains(e.target)) {
    isOpen.value = false
  }
}

onMounted(() => {
  window.addEventListener('click', closeDropdown)
})

onBeforeUnmount(() => {
  window.removeEventListener('click', closeDropdown)
})
</script>

<style scoped>
.dropdown-slide-enter-active,
.dropdown-slide-leave-active {
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.dropdown-slide-enter-from,
.dropdown-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.96);
}
</style>