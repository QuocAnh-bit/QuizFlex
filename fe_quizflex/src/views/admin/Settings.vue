<template>
  <section class="grid gap-6">
    <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ $t('admin_views.Settings.badge') }}</p><h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ $t('admin_views.Settings.title') }}</h1><p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">{{ $t('admin_views.Settings.description') }}</p></div>
    <div class="grid gap-6 xl:grid-cols-2">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ $t('admin_views.Settings.ai_limits_badge') }}</p><div class="mt-5 grid gap-3"><div v-for="row in limits" :key="row.role" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4"><div class="mb-3"><b class="text-[var(--text)]">{{ row.role }}</b></div><div class="grid gap-3 md:grid-cols-2"><input class="field" :value="row.ai" /><input class="field" :value="row.ocr" /></div></div></div></article>
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ $t('admin_views.Settings.visibility_defaults_badge') }}</p><div class="mt-5 grid gap-3"><label v-for="item in visibility" :key="item.value" class="flex items-center justify-between rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4"><span class="font-bold text-[var(--text)]">{{ item.label }}</span><input type="radio" name="visibility" class="h-4 w-4 accent-[var(--primary)]" :checked="item.value === 'private'" /></label></div><button class="btn-primary mt-5" type="button">{{ $t('admin_views.Settings.save_button') }}</button></article>
    </div>
  </section>
</template>
<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const limits = computed(() => [{ role: t('admin_views.Settings.roles.admin'), ai: t('admin_views.Settings.unlimited'), ocr: t('admin_views.Settings.unlimited') }, { role: 'VIP', ai: t('admin_views.Settings.ai_500'), ocr: t('admin_views.Settings.ocr_120') }, { role: t('admin_views.Settings.roles.user'), ai: t('admin_views.Settings.ai_10'), ocr: t('admin_views.Settings.ocr_3') }])
const visibility = computed(() => [{ value: 'public', label: 'Public' }, { value: 'private', label: 'Private' }, { value: 'group', label: 'Group' }])
</script>
