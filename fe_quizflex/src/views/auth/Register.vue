<template>
  <div class="relative grid min-h-[700px] overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface-strong)] shadow-[var(--shadow-soft)] lg:grid-cols-[0.92fr_1.08fr]">
    <aside class="relative hidden min-h-[700px] overflow-hidden p-8 text-white lg:flex lg:flex-col lg:justify-between"><div class="absolute inset-0 bg-gradient-to-br from-[var(--primary)]/90 via-[var(--primary-2)]/75 to-[var(--accent)]/55"></div><div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.28),transparent_28%),linear-gradient(to_bottom,rgba(0,0,0,0.03),rgba(0,0,0,0.58))]"></div><div class="relative z-10"><BrandLogo to="/" size="lg" /></div><div class="relative z-10"><div class="mb-6 inline-flex rounded-full border border-white/25 bg-white/15 px-4 py-2 text-sm font-black backdrop-blur-xl">{{ $t('auth.register_page.account_badge') }}</div><h2 class="max-w-md text-5xl font-black leading-[0.95] tracking-[-0.075em]">{{ $t('auth.register_page.hero_title') }}</h2><p class="mt-5 max-w-md text-base font-medium leading-8 text-white/75">{{ $t('auth.register_page.hero_description') }}</p></div><div class="relative z-10 grid grid-cols-2 gap-3"><div class="rounded-[1.25rem] border border-white/15 bg-white/10 p-4 backdrop-blur-xl"><p class="text-xs font-bold text-white/60">{{ $t('auth.register_page.sample_quiz_stat_label') }}</p><p class="mt-1 text-2xl font-black">50+</p></div><div class="rounded-[1.25rem] border border-white/15 bg-white/10 p-4 backdrop-blur-xl"><p class="text-xs font-bold text-white/60">{{ $t('auth.register_page.mode_stat_label') }}</p><p class="mt-1 text-2xl font-black">3</p></div></div></aside>
    <section class="relative grid content-center px-5 py-8 sm:px-8 lg:px-14"><div class="mx-auto w-full max-w-[540px]"><div class="mb-8 lg:hidden"><BrandLogo to="/" size="lg" /></div><div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl sm:p-8"><div class="pointer-events-none absolute -right-20 -top-20 h-52 w-52 rounded-full bg-[var(--primary)]/15 blur-3xl"></div><div class="relative z-10"><div class="mb-6 inline-flex rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-4 py-2 text-sm font-black text-[var(--primary)]">{{ $t('auth.register_page.form_badge') }}</div><h1 class="text-4xl font-black leading-none tracking-[-0.065em] text-[var(--text)]">{{ $t('auth.register_page.form_title') }}</h1><p class="mt-3 text-sm font-medium leading-7 text-[var(--muted)]">{{ $t('auth.register_page.form_description') }}</p>
      <form class="mt-8 grid gap-5" @submit.prevent="handleRegister">
        <div class="rounded-[1.4rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4">
          <div class="flex items-start gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-gradient-to-br from-[var(--primary)] to-[var(--primary-2)] text-xs font-black text-white">U</span>
            <div>
              <b class="block text-sm font-black text-[var(--text)]">{{ $t('auth.register_page.user_account_title') }}</b>
              <span class="mt-2 block text-xs font-semibold leading-5 text-[var(--muted)]">{{ $t('auth.register_page.user_account_description') }}</span>
            </div>
          </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2"><FieldInput :label="$t('auth.register_page.full_name_label')" v-model="form.fullName" :error="errors.fullName" :placeholder="$t('auth.register_page.full_name_placeholder')" prefix="ID" autocomplete="name" /><FieldInput :label="$t('auth.register_page.username_label')" v-model="form.username" :error="errors.username" :placeholder="$t('auth.register_page.username_placeholder')" prefix="@" autocomplete="username" /></div>
        <FieldInput :label="$t('auth.email')" v-model="form.email" :error="errors.email" type="email" :placeholder="$t('auth.register_page.email_placeholder')" prefix="✦" autocomplete="email" />
        <label class="grid gap-2 text-sm font-black text-[var(--text)]">{{ $t('auth.password') }}<div class="flex items-center gap-3 rounded-2xl border bg-[var(--input-bg)] px-4 py-3 transition focus-within:border-[var(--border-strong)]" :class="errors.password ? 'border-rose-500/50' : 'border-[var(--border)]'"><span class="text-sm font-black text-[var(--primary)]">#</span><input v-model="form.password" class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]" :type="isPasswordVisible ? 'text' : 'password'" :placeholder="$t('auth.register_page.password_placeholder')" autocomplete="new-password" /><button type="button" class="text-xs font-black text-[var(--primary)]" @click="isPasswordVisible = !isPasswordVisible">{{ isPasswordVisible ? $t('auth.register_page.hide_password') : $t('auth.register_page.show_password') }}</button></div><span v-if="errors.password" class="text-xs font-bold text-rose-400">{{ errors.password }}</span></label>
        <label class="flex items-start gap-3 text-sm font-semibold text-[var(--muted)]"><input v-model="form.acceptTerms" type="checkbox" class="mt-1 h-4 w-4 accent-[var(--primary)]" />{{ $t('auth.register_page.accept_terms') }}</label><span v-if="errors.acceptTerms" class="text-xs font-bold text-rose-400">{{ errors.acceptTerms }}</span>
        <button type="submit" class="btn-primary w-full">{{ $t('auth.register_page.create_account') }}</button><div v-if="successMessage" class="rounded-2xl border border-emerald-500/25 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-400">{{ successMessage }}</div>
      </form>
      <p class="mt-7 text-center text-sm font-semibold text-[var(--muted)]">{{ $t('auth.register_page.has_account') }} <router-link class="font-black text-[var(--primary)]" to="/login">{{ $t('auth.login') }}</router-link></p></div></div></div></section>
  </div>
</template>
<script setup>
import { reactive, ref, defineComponent, h, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { authApi } from '@/services/api'
import BrandLogo from '@/components/common/BrandLogo.vue'

const router = useRouter()
const route = useRoute()
const { t } = useI18n()
const FieldInput = defineComponent({ props: { modelValue: String, label: String, error: String, placeholder: String, prefix: String, type: { type: String, default: 'text' }, autocomplete: String }, emits: ['update:modelValue'], setup(props, { emit }) { return () => h('label', { class: 'grid gap-2 text-sm font-black text-[var(--text)]' }, [props.label, h('div', { class: ['flex items-center gap-3 rounded-2xl border bg-[var(--input-bg)] px-4 py-3 transition focus-within:border-[var(--border-strong)]', props.error ? 'border-rose-500/50' : 'border-[var(--border)]'] }, [h('span', { class: 'text-sm font-black text-[var(--primary)]' }, props.prefix), h('input', { class: 'w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]', value: props.modelValue, type: props.type, placeholder: props.placeholder, autocomplete: props.autocomplete, onInput: (event) => emit('update:modelValue', event.target.value) })]), props.error ? h('span', { class: 'text-xs font-bold text-rose-400' }, props.error) : null]) } })
const isPasswordVisible = ref(false)
const successMessage = ref('')
const form = reactive({ fullName: '', username: '', email: '', password: '', acceptTerms: true })
const errors = reactive({ fullName: '', username: '', email: '', password: '', acceptTerms: '' })

onMounted(() => {
  const state = history.state
  if (state && state.email) {
    form.email = state.email
  }
  const params = new URLSearchParams(window.location.search)
  if (params.get('email')) {
    form.email = params.get('email')
  }
})

const validate = () => { errors.fullName = form.fullName.trim() ? '' : t('auth.validation.full_name_required'); errors.username = form.username.trim().length >= 3 ? '' : t('auth.validation.username_min_3'); errors.email = !form.email ? t('auth.validation.email_required') : !/^\S+@\S+\.\S+$/.test(form.email) ? t('auth.validation.email_invalid') : ''; errors.password = form.password.length >= 8 ? '' : t('auth.validation.password_min_8'); errors.acceptTerms = form.acceptTerms ? '' : t('auth.validation.accept_terms_required'); return Object.values(errors).every((error) => !error) }
const handleRegister = async () => {
  successMessage.value = ''
  if (!validate()) return
  try {
    await authApi.register({
      name: form.fullName,
      username: form.username,
      email: form.email,
      password: form.password,
      role: 'USER',
    })
    successMessage.value = t('auth.register_page.success_message')
    setTimeout(() => {
      const query = { email: form.email }
      if (route.query.plan) {
        query.plan = route.query.plan
      }
      router.push({ path: '/login', query })
    }, 1000)
  } catch (error) {
    successMessage.value = ''
    errors.email = error.message
  }
}
</script>
