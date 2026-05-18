<template>
  <section class="grid gap-6 py-8 lg:grid-cols-[360px_1fr]">
    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
      <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Profile</p><h2 class="mt-2 text-3xl font-black text-[var(--text)]">Hồ sơ cá nhân</h2><p class="mt-2 text-sm text-[var(--muted)]">Thông tin tài khoản đang đăng nhập trên trình duyệt.</p>
      <div class="mt-6 grid gap-3 text-sm font-bold text-[var(--muted)]">
        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4"><span>Email</span><b class="mt-1 block text-[var(--text)]">{{ profile.email || 'Chưa đăng nhập' }}</b></div>
        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4"><span>Role</span><b class="mt-1 block text-[var(--text)]">{{ profile.role_label || profile.role || 'Guest' }}</b></div>
      </div>
    </article>
    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)]">
      <h1 class="text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Thông tin hiển thị</h1>
      <div class="mt-6 grid gap-4"><label class="grid gap-2 text-sm font-black text-[var(--text)]">Họ tên<input v-model="profile.name" class="field" /></label><label class="grid gap-2 text-sm font-black text-[var(--text)]">Email<input v-model="profile.email" class="field" disabled /></label><button class="btn-primary w-fit" type="button" @click="saveProfile">Lưu trên trình duyệt</button></div>
    </article>
  </section>
</template>
<script setup>
import { reactive } from 'vue'
import { currentUserStorage } from '@/services/api'
const profile = reactive(currentUserStorage.get() || { name: '', email: '', role: 'guest', role_label: 'Guest' })
const saveProfile = () => currentUserStorage.set(profile)
</script>
