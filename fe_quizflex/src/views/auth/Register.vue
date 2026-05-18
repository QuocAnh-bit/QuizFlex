<template>
  <div class="relative grid min-h-[700px] overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface-strong)] shadow-[var(--shadow-soft)] lg:grid-cols-[0.92fr_1.08fr]">
    <aside class="relative hidden min-h-[700px] overflow-hidden p-8 text-white lg:flex lg:flex-col lg:justify-between"><div class="absolute inset-0 bg-gradient-to-br from-[var(--primary)]/90 via-[var(--primary-2)]/75 to-[var(--accent)]/55"></div><div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.28),transparent_28%),linear-gradient(to_bottom,rgba(0,0,0,0.03),rgba(0,0,0,0.58))]"></div><div class="relative z-10"><BrandLogo to="/" size="lg" /></div><div class="relative z-10"><div class="mb-6 inline-flex rounded-full border border-white/25 bg-white/15 px-4 py-2 text-sm font-black backdrop-blur-xl">QuizFlex Account</div><h2 class="max-w-md text-5xl font-black leading-[0.95] tracking-[-0.075em]">Tạo quiz thông minh hơn.</h2><p class="mt-5 max-w-md text-base font-medium leading-8 text-white/75">Tạo đề bằng tay, AI hoặc OCR. Chơi cá nhân hoặc realtime theo phòng.</p></div><div class="relative z-10 grid grid-cols-2 gap-3"><div class="rounded-[1.25rem] border border-white/15 bg-white/10 p-4 backdrop-blur-xl"><p class="text-xs font-bold text-white/60">Quiz mẫu</p><p class="mt-1 text-2xl font-black">50+</p></div><div class="rounded-[1.25rem] border border-white/15 bg-white/10 p-4 backdrop-blur-xl"><p class="text-xs font-bold text-white/60">Chế độ</p><p class="mt-1 text-2xl font-black">3</p></div></div></aside>
    <section class="relative grid content-center px-5 py-8 sm:px-8 lg:px-14"><div class="mx-auto w-full max-w-[540px]"><div class="mb-8 lg:hidden"><BrandLogo to="/" size="lg" /></div><div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl sm:p-8"><div class="pointer-events-none absolute -right-20 -top-20 h-52 w-52 rounded-full bg-[var(--primary)]/15 blur-3xl"></div><div class="relative z-10"><div class="mb-6 inline-flex rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-4 py-2 text-sm font-black text-[var(--primary)]">Tạo tài khoản mới</div><h1 class="text-4xl font-black leading-none tracking-[-0.065em] text-[var(--text)]">Bắt đầu với QuizFlex</h1><p class="mt-3 text-sm font-medium leading-7 text-[var(--muted)]">Chọn loại tài khoản và bắt đầu xây dựng kho quiz của bạn.</p>
      <form class="mt-8 grid gap-5" @submit.prevent="handleRegister">
        <div class="grid gap-3 sm:grid-cols-2"><button v-for="role in roles" :key="role.name" type="button" class="relative overflow-hidden rounded-[1.4rem] border p-4 text-left transition hover:-translate-y-0.5" :class="selectedRole === role.name ? 'border-[var(--border-strong)] bg-[var(--chip-active)] shadow-[0_16px_38px_rgba(155,44,255,0.18)]' : 'border-[var(--border)] bg-[var(--surface-soft)] hover:border-[var(--border-strong)]'" @click="selectedRole = role.name"><div class="flex items-start justify-between gap-3"><div><b class="block text-sm font-black text-[var(--text)]">{{ role.name }}</b><span class="mt-2 block text-xs font-semibold leading-5 text-[var(--muted)]">{{ role.description }}</span></div><span class="grid h-8 w-8 shrink-0 place-items-center rounded-full border text-xs font-black" :class="selectedRole === role.name ? 'border-[var(--border-strong)] bg-gradient-to-br from-[var(--primary)] to-[var(--primary-2)] text-white' : 'border-[var(--border)] text-[var(--muted)]'">{{ selectedRole === role.name ? '✓' : role.short }}</span></div></button></div>
        <div class="grid gap-4 sm:grid-cols-2"><FieldInput label="Họ và tên" v-model="form.fullName" :error="errors.fullName" placeholder="Nguyễn Văn A" prefix="ID" /><FieldInput label="Tên người dùng" v-model="form.username" :error="errors.username" placeholder="quizmaster" prefix="@" /></div>
        <FieldInput label="Email" v-model="form.email" :error="errors.email" type="email" placeholder="you@example.com" prefix="✦" />
        <label class="grid gap-2 text-sm font-black text-[var(--text)]">Mật khẩu<div class="flex items-center gap-3 rounded-2xl border bg-[var(--input-bg)] px-4 py-3 transition focus-within:border-[var(--border-strong)]" :class="errors.password ? 'border-rose-500/50' : 'border-[var(--border)]'"><span class="text-sm font-black text-[var(--primary)]">#</span><input v-model="form.password" class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]" :type="isPasswordVisible ? 'text' : 'password'" placeholder="Ít nhất 8 ký tự" /><button type="button" class="text-xs font-black text-[var(--primary)]" @click="isPasswordVisible = !isPasswordVisible">{{ isPasswordVisible ? 'Ẩn' : 'Hiện' }}</button></div><span v-if="errors.password" class="text-xs font-bold text-rose-400">{{ errors.password }}</span></label>
        <label class="flex items-start gap-3 text-sm font-semibold text-[var(--muted)]"><input v-model="form.acceptTerms" type="checkbox" class="mt-1 h-4 w-4 accent-[var(--primary)]" />Tôi đồng ý với điều khoản sử dụng và chính sách bảo mật.</label><span v-if="errors.acceptTerms" class="text-xs font-bold text-rose-400">{{ errors.acceptTerms }}</span>
        <button type="submit" class="btn-primary w-full">Tạo tài khoản</button><div v-if="successMessage" class="rounded-2xl border border-emerald-500/25 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-400">{{ successMessage }}</div>
      </form>
      <p class="mt-7 text-center text-sm font-semibold text-[var(--muted)]">Đã có tài khoản? <router-link class="font-black text-[var(--primary)]" to="/login">Đăng nhập</router-link></p></div></div></div></section>
  </div>
</template>
<script setup>
import { reactive, ref, defineComponent, h } from 'vue'
import { authApi } from '@/services/api'
import BrandLogo from '@/components/common/BrandLogo.vue'
const FieldInput = defineComponent({ props: { modelValue: String, label: String, error: String, placeholder: String, prefix: String, type: { type: String, default: 'text' } }, emits: ['update:modelValue'], setup(props, { emit }) { return () => h('label', { class: 'grid gap-2 text-sm font-black text-[var(--text)]' }, [props.label, h('div', { class: ['flex items-center gap-3 rounded-2xl border bg-[var(--input-bg)] px-4 py-3 transition focus-within:border-[var(--border-strong)]', props.error ? 'border-rose-500/50' : 'border-[var(--border)]'] }, [h('span', { class: 'text-sm font-black text-[var(--primary)]' }, props.prefix), h('input', { class: 'w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]', value: props.modelValue, type: props.type, placeholder: props.placeholder, onInput: (event) => emit('update:modelValue', event.target.value) })]), props.error ? h('span', { class: 'text-xs font-bold text-rose-400' }, props.error) : null]) } })
const selectedRole = ref('Tài khoản thường')
const isPasswordVisible = ref(false)
const successMessage = ref('')
const roles = [{ name: 'Tài khoản thường', short: 'U', description: 'Làm quiz, join room và dùng AI giới hạn.' }, { name: 'Tài khoản VIP', short: 'V', description: 'Tạo quiz, tạo room, dùng AI/OCR nhiều hơn.' }]
const form = reactive({ fullName: '', username: '', email: '', password: '', acceptTerms: true })
const errors = reactive({ fullName: '', username: '', email: '', password: '', acceptTerms: '' })
const validate = () => { errors.fullName = form.fullName.trim() ? '' : 'Vui lòng nhập họ tên.'; errors.username = form.username.trim().length >= 3 ? '' : 'Username tối thiểu 3 ký tự.'; errors.email = !form.email ? 'Email không được để trống.' : !/^\S+@\S+\.\S+$/.test(form.email) ? 'Email chưa đúng định dạng.' : ''; errors.password = form.password.length >= 8 ? '' : 'Mật khẩu tối thiểu 8 ký tự.'; errors.acceptTerms = form.acceptTerms ? '' : 'Bạn cần đồng ý điều khoản.'; return Object.values(errors).every((error) => !error) }
const handleRegister = async () => {
  successMessage.value = ''
  if (!validate()) return
  try {
    await authApi.register({
      name: form.fullName,
      email: form.email,
      password: form.password,
      role: selectedRole.value === 'Tài khoản VIP' ? 'VIP' : 'USER',
    })
    successMessage.value = 'Tạo tài khoản thành công.'
  } catch (error) {
    successMessage.value = ''
    errors.email = error.message
  }
}
</script>
