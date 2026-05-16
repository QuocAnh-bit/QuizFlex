<template>
  <section class="grid gap-6 py-8 xl:grid-cols-[360px_1fr]">
    <aside class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 text-center shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-20 -top-20 h-56 w-56 rounded-full bg-[var(--primary)]/20 blur-3xl"></div>
      <div class="relative z-10">
        <div class="mx-auto grid h-24 w-24 place-items-center rounded-[2rem] bg-gradient-to-br from-[var(--primary)] via-[var(--primary-2)] to-[var(--accent)] text-4xl font-black text-white shadow-[0_24px_60px_rgba(155,44,255,0.35)]">N</div>
        <h1 class="mt-5 text-3xl font-black tracking-[-0.06em] text-[var(--text)]">Ngọc QuizFlex</h1>
        <p class="mt-2 text-sm text-[var(--muted)]">vip.user@example.com</p>
        <span class="mt-4 inline-flex rounded-full bg-amber-500/15 px-4 py-2 text-xs font-black text-amber-400">VIP account</span>
      </div>
    </aside>
    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Profile</p><h2 class="mt-2 text-3xl font-black text-[var(--text)]">Hồ sơ cá nhân</h2><p class="mt-2 text-sm text-[var(--muted)]">Xem và sửa thông tin tài khoản. Form mock thôi, chưa có backend để cãi nhau.</p>
      <form class="mt-6 grid gap-4" @submit.prevent="saveProfile">
        <div class="grid gap-4 md:grid-cols-2"><label class="grid gap-2 text-sm font-black text-[var(--text)]">Họ tên<input v-model="profile.name" class="field" /></label><label class="grid gap-2 text-sm font-black text-[var(--text)]">Username<input v-model="profile.username" class="field" /></label></div>
        <label class="grid gap-2 text-sm font-black text-[var(--text)]">Email<input v-model="profile.email" class="field" type="email" /></label>
        <label class="grid gap-2 text-sm font-black text-[var(--text)]">Giới thiệu<textarea v-model="profile.bio" class="field min-h-28"></textarea></label>
        <div class="grid gap-4 md:grid-cols-3"><div v-for="item in stats" :key="item.label" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4"><p class="text-xs font-bold text-[var(--muted)]">{{ item.label }}</p><b class="mt-1 block text-2xl font-black text-[var(--text)]">{{ item.value }}</b></div></div>
        <button class="btn-primary w-fit" type="submit">Lưu thay đổi</button>
      </form>
    </article>
  </section>
</template>
<script setup>
import { reactive } from 'vue'
const profile = reactive({ name: 'Ngọc QuizFlex', username: 'ngoc.quiz', email: 'vip.user@example.com', bio: 'Tôi dùng QuizFlex để tạo quiz, chạy room và xem report học tập.' })
const stats = [{ label: 'Quiz đã tạo', value: 24 }, { label: 'Quiz đã làm', value: 18 }, { label: 'Điểm TB', value: '87%' }]
const saveProfile = () => console.log('Save profile mock', profile)
</script>
