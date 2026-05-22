<template>
  <section class="relative my-10">
    <div class="pointer-events-none absolute -left-20 top-10 h-72 w-72 rounded-full bg-[var(--primary)]/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-20 top-20 h-72 w-72 rounded-full bg-[var(--accent)]/10 blur-3xl"></div>

    <div class="relative grid gap-5 xl:grid-cols-2">
      <article
        class="group relative min-h-[340px] overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-soft)] backdrop-blur-2xl transition duration-500 hover:-translate-y-2 hover:border-[var(--border-strong)] hover:shadow-[0_28px_90px_rgba(0,0,0,0.32)]"
      >
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-[var(--primary)]/18 via-transparent to-[var(--accent)]/10"></div>
        <div class="pointer-events-none absolute -right-28 -top-28 h-80 w-80 rounded-full bg-[var(--primary)]/25 blur-3xl transition duration-700 group-hover:scale-125 group-hover:opacity-80"></div>
        <div class="pointer-events-none absolute -bottom-28 left-10 h-72 w-72 rounded-full bg-[var(--accent-2)]/10 blur-3xl transition duration-700 group-hover:scale-110"></div>

        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-[var(--border-strong)] to-transparent"></div>

        <div class="relative z-10 grid h-full gap-8 p-6 sm:p-8 lg:grid-cols-[1.08fr_0.92fr] lg:p-9">
          <div class="flex flex-col justify-center">
            <div
              class="mb-5 flex w-fit items-center gap-2 rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-4 py-2 text-sm font-black text-[var(--primary)] shadow-[0_12px_30px_rgba(155,44,255,0.16)]"
            >
              <span class="h-2 w-2 rounded-full bg-[var(--primary)] shadow-[0_0_16px_var(--primary)]"></span>
              {{ $t('components.LandingQuickActions.create_manual_badge') }}
            </div>

            <h2 class="max-w-md text-4xl font-black leading-[0.92] tracking-[-0.07em] text-[var(--text)] sm:text-3xl">
              {{ $t('components.LandingQuickActions.create_manual_title') }}
              <span class="block bg-gradient-to-r from-[var(--primary)] via-[var(--primary-2)] to-[var(--accent)] bg-clip-text text-transparent">
                {{ $t('components.LandingQuickActions.create_manual_highlight') }}
              </span>
            </h2>

            <p class="mt-5 max-w-md text-sm font-medium leading-7 text-[var(--muted)] sm:text-base">
              {{ $t('components.LandingQuickActions.create_manual_description') }}
            </p>

            <div class="mt-6 flex flex-wrap gap-2">
              <span
                v-for="item in createFeatures"
                :key="item"
                class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-2 text-xs font-extrabold text-[var(--muted)] transition duration-300 group-hover:border-[var(--border-strong)] group-hover:text-[var(--text)]"
              >
                {{ item }}
              </span>
            </div>

            <div class="mt-8 flex flex-wrap items-center gap-3">
              <router-link
                to="/dashboard/questions/create"
                class="group/btn relative inline-flex min-h-12 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-[var(--primary)] via-[var(--primary-2)] to-[var(--accent)] px-6 text-sm font-black text-white shadow-[0_18px_38px_rgba(155,44,255,0.28)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_24px_55px_rgba(155,44,255,0.38)] active:scale-95"
              >
                <span class="absolute inset-0 -translate-x-[120%] bg-gradient-to-r from-transparent via-white/35 to-transparent transition duration-700 group-hover/btn:translate-x-[120%]"></span>
                <span class="relative z-10">{{ $t('components.LandingQuickActions.create_button') }}</span>
              </router-link>

              <span class="text-xs font-bold text-[var(--muted)]">
                {{ $t('components.LandingQuickActions.create_button_note') }}
              </span>
            </div>
          </div>

          <div class="relative grid place-items-center">
            <div class="absolute h-64 w-64 rounded-full bg-[var(--chip-active)] blur-2xl"></div>

            <div
              class="relative w-full max-w-[310px] overflow-hidden rounded-[1.75rem] border border-[var(--border)] bg-[var(--surface)] shadow-[0_26px_70px_rgba(0,0,0,0.24)] transition duration-500 group-hover:-translate-y-1 group-hover:rotate-1 group-hover:border-[var(--border-strong)]"
            >
              <div class="flex h-12 items-center justify-between border-b border-[var(--border)] bg-[var(--surface-soft)] px-4">
                <div class="flex gap-2">
                  <span class="h-2.5 w-2.5 rounded-full bg-[#ff5f57]"></span>
                  <span class="h-2.5 w-2.5 rounded-full bg-[#ffbd2e]"></span>
                  <span class="h-2.5 w-2.5 rounded-full bg-[#28c840]"></span>
                </div>

                <span class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-[10px] font-black text-[var(--primary)]">
                  EDITOR
                </span>
              </div>

              <div class="grid gap-3 p-4">
                <div class="rounded-2xl border border-[var(--border)] bg-gradient-to-br from-[var(--surface-soft)] to-[rgba(155,44,255,0.12)] p-4">
                  <p class="text-xs font-black text-[var(--primary)]">
                    {{ $t('components.LandingQuickActions.question_number') }}
                  </p>
                  <h3 class="mt-2 text-sm font-black leading-5 text-[var(--text)]">
                    {{ $t('components.LandingQuickActions.question_text') }}
                  </h3>
                </div>

                <div
                  v-for="answer in previewAnswers"
                  :key="answer.key"
                  class="flex items-center gap-3 rounded-2xl border p-3 text-xs font-bold transition duration-300"
                  :class="answer.active
                    ? 'border-[var(--border-strong)] bg-[var(--chip-active)] text-[var(--text)]'
                    : 'border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)]'"
                >
                  <span
                    class="grid h-7 w-7 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-[var(--primary)] to-[var(--primary-2)] text-xs font-black text-white"
                  >
                    {{ answer.key }}
                  </span>
                  {{ answer.text }}
                </div>

                <div class="mt-1 grid grid-cols-3 gap-2">
                  <div
                    v-for="stat in editorStats"
                    :key="stat.label"
                    class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-center"
                  >
                    <strong class="block text-sm font-black text-[var(--text)]">
                      {{ stat.value }}
                    </strong>
                    <span class="mt-1 block text-[10px] font-bold text-[var(--muted)]">
                      {{ stat.label }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </article>

      <article
        class="group relative min-h-[340px] overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-soft)] backdrop-blur-2xl transition duration-500 hover:-translate-y-2 hover:border-[var(--border-strong)] hover:shadow-[0_28px_90px_rgba(0,0,0,0.32)]"
      >
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-[var(--accent-2)]/12 via-transparent to-[var(--primary-2)]/14"></div>
        <div class="pointer-events-none absolute -right-28 -top-28 h-80 w-80 rounded-full bg-[var(--accent-2)]/20 blur-3xl transition duration-700 group-hover:scale-125 group-hover:opacity-80"></div>
        <div class="pointer-events-none absolute -bottom-28 left-10 h-72 w-72 rounded-full bg-[var(--primary-2)]/12 blur-3xl transition duration-700 group-hover:scale-110"></div>

        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-[var(--border-strong)] to-transparent"></div>

        <div class="relative z-10 grid h-full gap-8 p-6 sm:p-8 lg:grid-cols-[1.08fr_0.92fr] lg:p-9">
          <div class="flex flex-col justify-center">
            <div
              class="mb-5 flex w-fit items-center gap-2 rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-4 py-2 text-sm font-black text-[var(--primary)] shadow-[0_12px_30px_rgba(155,44,255,0.16)]"
            >
              <span class="h-2 w-2 rounded-full bg-[var(--accent-2)] shadow-[0_0_16px_var(--accent-2)]"></span>
              {{ $t('components.LandingQuickActions.ai_badge') }}
            </div>

            <h2 class="max-w-md text-4xl font-black leading-[0.92] tracking-[-0.07em] text-[var(--text)] sm:text-5xl">
              {{ $t('components.LandingQuickActions.ai_title') }}
              <span class="block bg-gradient-to-r from-[var(--accent-2)] via-[var(--primary-2)] to-[var(--accent)] bg-clip-text text-transparent">
                {{ $t('components.LandingQuickActions.ai_highlight') }}
              </span>
            </h2>

            <p class="mt-5 max-w-md text-sm font-medium leading-7 text-[var(--muted)] sm:text-base">
              {{ $t('components.LandingQuickActions.ai_description') }}
            </p>

            <div class="mt-6 flex flex-wrap gap-2">
              <span
                v-for="item in aiFeatures"
                :key="item"
                class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-2 text-xs font-extrabold text-[var(--muted)] transition duration-300 group-hover:border-[var(--border-strong)] group-hover:text-[var(--text)]"
              >
                {{ item }}
              </span>
            </div>

            <div class="mt-8 flex flex-wrap items-center gap-3">
              <router-link
                to="/dashboard/questions/ai"
                class="group/btn relative inline-flex min-h-12 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-[var(--accent-2)] via-[var(--primary-2)] to-[var(--accent)] px-6 text-sm font-black text-white shadow-[0_18px_38px_rgba(155,44,255,0.28)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_24px_55px_rgba(155,44,255,0.38)] active:scale-95"
              >
                <span class="absolute inset-0 -translate-x-[120%] bg-gradient-to-r from-transparent via-white/35 to-transparent transition duration-700 group-hover/btn:translate-x-[120%]"></span>
                <span class="relative z-10">{{ $t('components.LandingQuickActions.ai_button') }}</span>
              </router-link>

              <span class="text-xs font-bold text-[var(--muted)]">
                {{ $t('components.LandingQuickActions.ai_button_note') }}
              </span>
            </div>
          </div>

          <div class="relative grid place-items-center">
            <div class="absolute h-64 w-64 rounded-full bg-[var(--chip-active)] blur-2xl"></div>

            <div class="relative grid h-60 w-60 place-items-center rounded-full border border-[var(--border)] bg-[var(--surface-soft)] shadow-[0_26px_70px_rgba(0,0,0,0.22)] transition duration-500 group-hover:-translate-y-1 group-hover:border-[var(--border-strong)]">
              <div class="absolute inset-5 rounded-full border border-dashed border-[var(--border-strong)] opacity-80"></div>
              <div class="absolute inset-12 rounded-full border border-dashed border-[var(--border-strong)] opacity-50"></div>

              <div class="absolute left-7 top-10 h-3 w-3 rounded-full bg-[var(--primary)] shadow-[0_0_22px_var(--primary)]"></div>
              <div class="absolute right-10 top-8 h-4 w-4 rounded-full bg-[var(--accent-2)] shadow-[0_0_24px_var(--accent-2)]"></div>
              <div class="absolute bottom-10 left-12 h-4 w-4 rounded-full bg-[var(--accent)] shadow-[0_0_24px_var(--accent)]"></div>

              <div
                class="relative z-10 grid h-28 w-28 place-items-center rounded-[2rem] bg-gradient-to-br from-[var(--primary)] via-[var(--primary-2)] to-[var(--accent)] text-4xl font-black tracking-[-0.1em] text-white shadow-[0_24px_60px_rgba(155,44,255,0.4)] transition duration-500 group-hover:scale-105 group-hover:rotate-3"
              >
                AI
              </div>

              <div class="absolute bottom-7 right-7 grid gap-2">
                <span class="h-2 w-24 rounded-full bg-gradient-to-r from-[var(--accent-2)] to-transparent"></span>
                <span class="h-2 w-16 rounded-full bg-gradient-to-r from-[var(--primary-2)] to-transparent"></span>
                <span class="h-2 w-20 rounded-full bg-gradient-to-r from-[var(--accent)] to-transparent"></span>
              </div>
            </div>
          </div>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const createFeatures = [
  t('components.LandingQuickActions.create_feature_1'),
  t('components.LandingQuickActions.create_feature_2'),
  t('components.LandingQuickActions.create_feature_3'),
]

const aiFeatures = [
  t('components.LandingQuickActions.ai_feature_1'),
  t('components.LandingQuickActions.ai_feature_2'),
  t('components.LandingQuickActions.ai_feature_3'),
]

const previewAnswers = [
  {
    key: 'A',
    text: t('components.LandingQuickActions.answer_a'),
    active: true,
  },
  {
    key: 'B',
    text: t('components.LandingQuickActions.answer_b'),
    active: false,
  },
]

const editorStats = [
  {
    value: t('components.LandingQuickActions.stat_value_1'),
    label: t('components.LandingQuickActions.stat_questions'),
  },
  {
    value: t('components.LandingQuickActions.stat_value_2'),
    label: t('components.LandingQuickActions.stat_time'),
  },
  {
    value: t('components.LandingQuickActions.stat_value_3'),
    label: t('components.LandingQuickActions.stat_level'),
  },
]
</script>