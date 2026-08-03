import { createI18n } from 'vue-i18n'
import vi from '@/locales/vi.json'
import en from '@/locales/en.json'
import zh from '@/locales/zh.json'
import ja from '@/locales/ja.json'

const SUPPORTED_LOCALES = ['vi', 'en', 'zh', 'ja']

const getInitialLocale = () => {
  const saved = localStorage.getItem('quizflex_locale')
  return SUPPORTED_LOCALES.includes(saved) ? saved : 'vi'
}

const i18n = createI18n({
  legacy: false, // bắt buộc để dùng useI18n() trong <script setup>
  locale: getInitialLocale(),
  fallbackLocale: 'vi',
  messages: { vi, en, zh, ja },
})

export default i18n