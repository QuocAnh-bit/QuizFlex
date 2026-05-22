<template>
  <select v-model="currentLocale" @change="switchLanguage">
    <option value="vi">{{ $t('components.LanguageSwitcher.vi') }}</option>
    <option value="en">{{ $t('components.LanguageSwitcher.en') }}</option>
    <option value="ja">{{ $t('components.LanguageSwitcher.ja') }}</option>
    <option value="zh">{{ $t('components.LanguageSwitcher.zh') }}</option>
  </select>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();
const currentLocale = ref(locale.value);

const switchLanguage = () => {
  locale.value = currentLocale.value;
  // Lưu vào localStorage để lần sau truy cập lại không bị mất
  localStorage.setItem('lang', currentLocale.value);
};
</script>