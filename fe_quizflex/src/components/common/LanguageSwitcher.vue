<template>
  <div class="relative language-switcher-container" ref="containerRef">
    <button
      type="button"
      @click="isOpen = !isOpen"
      :title="$t('common.language')"
      class="flex h-9 shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 active:scale-95 shadow-sm"
    >
      <span>{{ currentLocale.code.toUpperCase() }}</span>
    </button>

    <transition name="dropdown-slide">
      <div
        v-if="isOpen"
        class="absolute right-0 top-full mt-2 w-40 rounded-xl border border-slate-200 bg-white p-1 shadow-xl z-50"
      >
        <button
          v-for="item in locales"
          :key="item.code"
          type="button"
          @click="selectLocale(item.code)"
          :class="[
            'flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-xs font-semibold transition',
            locale === item.code
              ? 'bg-purple-50 text-[#7C3AED] font-bold'
              : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900'
          ]"
        >
          <span class="text-sm leading-none">{{ item.flag }}</span>
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