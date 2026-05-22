<template>
  <div class="relative grid min-h-[680px] overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface-strong)] shadow-[var(--shadow-soft)] lg:grid-cols-[0.92fr_1.08fr]">
    <aside class="relative hidden min-h-[680px] overflow-hidden p-8 text-white lg:flex lg:flex-col lg:justify-between"><div class="absolute inset-0 bg-gradient-to-br from-[var(--primary)]/90 via-[var(--primary-2)]/70 to-[var(--accent)]/55"></div><div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.28),transparent_28%),linear-gradient(to_bottom,rgba(0,0,0,0.05),rgba(0,0,0,0.58))]"></div><div class="relative z-10"><BrandLogo to="/" size="lg" /></div><div class="relative z-10"><div class="mb-6 inline-flex rounded-full border border-white/25 bg-white/15 px-4 py-2 text-sm font-black backdrop-blur-xl">{{ $t('auth.login_page.workspace_badge') }}</div><h2 class="max-w-md text-5xl font-black leading-[0.95] tracking-[-0.075em]">{{ $t('auth.login_page.hero_title') }}</h2><p class="mt-5 max-w-md text-base font-medium leading-8 text-white/75">{{ $t('auth.login_page.hero_description') }}</p></div><div class="relative z-10 grid grid-cols-2 gap-3"><div class="rounded-[1.25rem] border border-white/15 bg-white/10 p-4 backdrop-blur-xl"><p class="text-xs font-bold text-white/60">{{ $t('auth.login_page.quiz_stat_label') }}</p><p class="mt-1 text-2xl font-black">248</p></div><div class="rounded-[1.25rem] border border-white/15 bg-white/10 p-4 backdrop-blur-xl"><p class="text-xs font-bold text-white/60">{{ $t('auth.login_page.ai_usage_stat_label') }}</p><p class="mt-1 text-2xl font-black">8.2k</p></div></div></aside>
    <section class="relative grid content-center px-5 py-8 sm:px-8 lg:px-14"><div class="mx-auto w-full max-w-[470px]"><div class="mb-8 lg:hidden"><BrandLogo to="/" size="lg" /></div><div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl sm:p-8"><div class="pointer-events-none absolute -right-20 -top-20 h-52 w-52 rounded-full bg-[var(--primary)]/15 blur-3xl"></div><div class="relative z-10"><div class="mb-6 inline-flex rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-4 py-2 text-sm font-black text-[var(--primary)]">{{ $t('auth.login_page.form_badge') }}</div><h1 class="text-4xl font-black leading-none tracking-[-0.065em] text-[var(--text)]">{{ $t('auth.login_page.form_title') }}</h1><p class="mt-3 text-sm font-medium leading-7 text-[var(--muted)]">{{ $t('auth.login_page.form_description') }}</p>
      <form class="mt-8 grid gap-5" @submit.prevent="handleLogin">
        <label class="grid gap-2 text-sm font-black text-[var(--text)]">{{ $t('auth.email') }}<div class="flex items-center gap-3 rounded-2xl border bg-[var(--input-bg)] px-4 py-3 transition focus-within:border-[var(--border-strong)]" :class="errors.email ? 'border-rose-500/50' : 'border-[var(--border)]'"><span class="text-sm font-black text-[var(--primary)]">✦</span><input v-model="form.email" class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]" type="email" :placeholder="$t('auth.login_page.email_placeholder')" autocomplete="email" /></div><span v-if="errors.email" class="text-xs font-bold text-rose-400">{{ errors.email }}</span></label>
        <label class="grid gap-2 text-sm font-black text-[var(--text)]">{{ $t('auth.password') }}<div class="flex items-center gap-3 rounded-2xl border bg-[var(--input-bg)] px-4 py-3 transition focus-within:border-[var(--border-strong)]" :class="errors.password ? 'border-rose-500/50' : 'border-[var(--border)]'"><span class="text-sm font-black text-[var(--primary)]">#</span><input v-model="form.password" class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]" :type="isPasswordVisible ? 'text' : 'password'" :placeholder="$t('auth.login_page.password_placeholder')" autocomplete="current-password" /><button type="button" class="rounded-full px-3 py-1 text-xs font-black text-[var(--primary)] transition hover:bg-[var(--chip-active)]" @click="isPasswordVisible = !isPasswordVisible">{{ isPasswordVisible ? $t('auth.login_page.hide_password') : $t('auth.login_page.show_password') }}</button></div><span v-if="errors.password" class="text-xs font-bold text-rose-400">{{ errors.password }}</span></label>
        <div class="flex flex-wrap items-center justify-between gap-3 text-sm"><label class="flex cursor-pointer items-center gap-3 font-bold text-[var(--muted)]"><input v-model="form.remember" type="checkbox" class="h-4 w-4 accent-[var(--primary)]" />{{ $t('auth.login_page.remember_me') }}</label><router-link to="/forgot-password" class="font-black text-[var(--primary)]">{{ $t('auth.login_page.forgot_password') }}</router-link></div>
        <button type="submit" class="btn-primary w-full">{{ $t('auth.login') }}</button>
        <div v-if="successMessage" class="rounded-2xl border border-emerald-500/25 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-400">{{ successMessage }}</div>
      </form>
      <p class="mt-7 text-center text-sm font-semibold text-[var(--muted)]">{{ $t('auth.login_page.no_account') }} <button type="button" class="font-black text-[var(--primary)]" @click="goToRegister">{{ $t('auth.login_page.register_now') }}</button></p></div></div></div></section>
  </div>
</template>
<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { authApi, getDefaultRouteForRole } from '@/services/api'
import BrandLogo from '@/components/common/BrandLogo.vue'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const isPasswordVisible = ref(false)
const successMessage = ref('')
const form = reactive({ email: '', password: '', remember: true })
const errors = reactive({ email: '', password: '' })

onMounted(() => {
  const state = history.state
  if (route.query.email) {
    form.email = route.query.email
  }
  if (state && state.email) {
    form.email = state.email
  }
})

const validate = () => { errors.email = !form.email ? t('auth.validation.email_required') : !/^\S+@\S+\.\S+$/.test(form.email) ? t('auth.validation.email_invalid') : ''; errors.password = !form.password ? t('auth.validation.password_required') : form.password.length < 6 ? t('auth.validation.password_min_6') : ''; return !errors.email && !errors.password }

const safeRedirect = (value) => {
  if (!value || typeof value !== 'string') return ''
  if (!value.startsWith('/') || value.startsWith('//')) return ''
  return value
}

const handleLogin = async () => {
  successMessage.value = ''
  if (!validate()) return
  try {
    const user = await authApi.login({ email: form.email, password: form.password })
    successMessage.value = t('auth.login_page.success_message')

    const query = {}
    let targetPath = safeRedirect(route.query.redirect) || getDefaultRouteForRole(user.role)
    if (route.query.plan) {
      targetPath = '/upgrade'
      query.plan = route.query.plan
    }

    setTimeout(() => {
      router.push({ path: targetPath, query })
    }, 1000)
  } catch (error) {
    successMessage.value = ''
    errors.password = error.message
  }
}

const goToRegister = () => {
  router.push({ path: '/register', state: { email: form.email } })
}
</script>
