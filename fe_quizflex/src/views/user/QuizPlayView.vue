<template>
  <section class="grid gap-6 py-8">
    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-8 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ $t('user_views.QuizPlayView.badge') }}</p>
      <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ $t('user_views.QuizPlayView.title') }}</h1>
      <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">{{ $t('user_views.QuizPlayView.description') }}</p>
      <router-link class="btn-primary mt-6 inline-flex" to="/quizzes">{{ $t('user_views.QuizPlayView.choose_quiz_button') }}</router-link>
    </article>
  </section>
</template>
<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>
