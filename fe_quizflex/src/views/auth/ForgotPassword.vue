<template>
  <div class="mx-auto max-w-xl rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-8 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
    <BrandLogo to="/" size="lg" />
    <div class="mt-8 inline-flex rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-4 py-2 text-sm font-black text-[var(--primary)]">{{ $t('auth.forgot_password_page.badge') }}</div>
    <h1 class="mt-4 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ $t('auth.forgot_password_page.title') }}</h1>
    <p class="mt-3 leading-7 text-[var(--muted)]">{{ $t('auth.forgot_password_page.description') }}</p>
    <form class="mt-8 grid gap-4" @submit.prevent>
      <input class="field" type="email" :placeholder="$t('auth.forgot_password_page.email_placeholder')" />
      <button class="btn-primary w-full" type="submit">{{ $t('auth.forgot_password_page.submit_button') }}</button>
      <router-link class="btn-ghost w-full" to="/login">{{ $t('auth.forgot_password_page.back_to_login') }}</router-link>
    </form>
  </div>
</template>
<script setup>
import { useI18n } from 'vue-i18n'
import BrandLogo from '@/components/common/BrandLogo.vue'

const { t } = useI18n()
</script>
