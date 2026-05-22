import { createI18n } from 'vue-i18n';
import vi from './locales/vi.json';
import en from './locales/en.json';
import ja from './locales/ja.json';
import zh from './locales/zh.json';

// Kiểm tra ngôn ngữ đã lưu trong localStorage, nếu không có thì mặc định là 'vi'
const savedLocale = localStorage.getItem('lang') || 'vi';

const i18n = createI18n({
  legacy: false, // Bắt buộc dùng false để chạy với Composition API
  locale: savedLocale,
  fallbackLocale: 'en', // Nếu thiếu text ở ngôn ngữ hiện tại, nó sẽ lấy tiếng Anh
  messages: {
    vi,
    en,
    ja,
    zh
  }
});

export default i18n;