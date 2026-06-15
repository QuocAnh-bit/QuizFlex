<template>
  <div class="mx-auto max-w-xl rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-8 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
    <BrandLogo to="/" size="lg" />
    <div class="mt-8 inline-flex rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-4 py-2 text-sm font-black text-[var(--primary)]">Khôi phục tài khoản</div>
    <h1 class="mt-4 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Quên mật khẩu?</h1>
    <p class="mt-3 leading-7 text-[var(--muted)]">Nhập email để chuẩn bị luồng khôi phục mật khẩu khi backend mail được cấu hình.</p>
    <form class="mt-8 grid gap-4" @submit.prevent>
      <input class="field" type="email" placeholder="you@example.com" />
      <button class="btn-primary w-full" type="submit">Gửi link khôi phục</button>
      <router-link class="btn-ghost w-full" to="/login">Quay lại đăng nhập</router-link>
    </form>
  </div>
</template>
<script setup>
import BrandLogo from '@/components/common/BrandLogo.vue'
</script>
