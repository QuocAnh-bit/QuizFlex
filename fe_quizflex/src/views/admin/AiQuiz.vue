<template>
  <section class="grid gap-6 xl:grid-cols-[1fr_420px]">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-28 -top-28 h-80 w-80 rounded-full bg-[var(--accent-2)]/20 blur-3xl"></div>
      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ $t('admin_views.AiQuiz.badge') }}</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ $t('admin_views.AiQuiz.title') }}</h1>
        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">{{ $t('admin_views.AiQuiz.description') }}</p>
        <div class="mt-6 grid gap-4 md:grid-cols-3">
          <div v-for="stat in quotaStats" :key="stat.label" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4"><p class="text-xs font-bold text-[var(--muted)]">{{ stat.label }}</p><b class="mt-1 block text-2xl font-black text-[var(--text)]">{{ stat.value }}</b></div>
        </div>
        <div class="mt-8 grid gap-4"><textarea v-model="prompt" class="field min-h-44" :placeholder="$t('admin_views.AiQuiz.prompt_placeholder')"></textarea><div class="grid gap-4 md:grid-cols-4"><select v-model="settings.count" class="field"><option value="10">{{ $t('admin_views.AiQuiz.count_10') }}</option><option value="15">{{ $t('admin_views.AiQuiz.count_15') }}</option><option value="20">{{ $t('admin_views.AiQuiz.count_20') }}</option></select><select v-model="settings.difficulty" class="field"><option value="easy">{{ $t('admin_views.AiQuiz.difficulty_easy') }}</option><option value="medium">{{ $t('admin_views.AiQuiz.difficulty_medium') }}</option><option value="hard">{{ $t('admin_views.AiQuiz.difficulty_hard') }}</option></select><select v-model="settings.language" class="field"><option value="vi">{{ $t('admin_views.AiQuiz.language_vi') }}</option><option value="en">{{ $t('admin_views.AiQuiz.language_en') }}</option></select><select v-model="settings.visibility" class="field"><option value="private">{{ $t('admin_views.AiQuiz.visibility_private') }}</option><option value="public">{{ $t('admin_views.AiQuiz.visibility_public') }}</option><option value="group">{{ $t('admin_views.AiQuiz.visibility_group') }}</option></select></div>
          <router-link class="rounded-[1.5rem] border border-dashed border-[var(--border-strong)] bg-[var(--chip-active)] p-5 text-center transition hover:-translate-y-1" :to="`${questionBase}/ocr`"><p class="font-black text-[var(--text)]">{{ $t('admin_views.AiQuiz.ocr_link_title') }}</p><p class="mt-1 text-sm text-[var(--muted)]">{{ $t('admin_views.AiQuiz.ocr_link_description') }}</p></router-link>
          <button class="btn-primary w-fit" type="button" @click="prepareDraft">{{ $t('admin_views.AiQuiz.prepare_button') }}</button></div>
      </div>
    </article>
    <aside class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ $t('admin_views.AiQuiz.preview_badge') }}</p><h2 class="mt-2 text-2xl font-black text-[var(--text)]">{{ $t('admin_views.AiQuiz.preview_title') }}</h2><div v-if="!generated" class="mt-6 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-6 text-center text-sm font-bold text-[var(--muted)]">{{ $t('admin_views.AiQuiz.empty_preview') }}</div><div v-else class="mt-6 grid gap-3"><div class="rounded-[1.4rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4"><b class="text-[var(--text)]">{{ $t('admin_views.AiQuiz.request_label') }}</b><p class="mt-2 text-sm text-[var(--muted)]">{{ prompt }}</p></div><router-link class="btn-primary text-center" :to="`${questionBase}/create`">{{ $t('admin_views.AiQuiz.edit_button') }}</router-link></div></aside>
  </section>
</template>
<script setup>
import { computed, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { currentUserStorage } from '@/services/api'

const route = useRoute()
const { t } = useI18n()
const questionBase = computed(() => route.path.startsWith('/dashboard') ? '/dashboard/questions' : '/admin/questions')
const prompt = ref('')
const generated = ref(false)
const settings = reactive({ count: '10', difficulty: 'medium', language: 'vi', visibility: 'private' })
const user = computed(() => currentUserStorage.get())
const quotaStats = computed(() => [{ label: t('admin_views.AiQuiz.quota.role'), value: user.value?.role_label || user.value?.role || t('admin_views.AiQuiz.guest') }, { label: t('admin_views.AiQuiz.quota.ai_remaining'), value: user.value?.ai_quota_remaining ?? 0 }, { label: t('admin_views.AiQuiz.quota.ocr_remaining'), value: user.value?.role === 'vip' ? 120 : 3 }])
const prepareDraft = () => {
  generated.value = true
  sessionStorage.setItem('quizflex_ai_prompt', JSON.stringify({ prompt: prompt.value, settings }))
}
</script>
