<template>
  <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h1 class="text-2xl font-bold text-slate-900">{{ $t('user_views.User.title') }}</h1>
  </section>
</template>
<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>
