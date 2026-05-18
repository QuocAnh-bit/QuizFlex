<template>
  <section class="grid gap-6 py-8">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-8 text-center shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute left-1/2 top-0 h-80 w-80 -translate-x-1/2 rounded-full bg-[var(--primary)]/20 blur-3xl"></div>
      <div class="relative z-10"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Upgrade</p><h1 class="mt-3 text-5xl font-black tracking-[-0.07em] text-[var(--text)]">Nâng cấp VIP</h1><p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-[var(--muted)]">Mở khóa tạo quiz, tạo room, AI/OCR nhiều hơn và report. Một chút VIP để đỡ làm user thường trong đau khổ.</p></div>
    </div>
    <div class="grid gap-5 lg:grid-cols-3"><article v-for="plan in paymentPlans" :key="plan.id" class="rounded-[2rem] border p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl transition hover:-translate-y-2" :class="plan.popular ? 'border-[var(--border-strong)] bg-[var(--chip-active)]' : 'border-[var(--border)] bg-[var(--surface)]' "><p class="font-black text-[var(--primary)]">{{ plan.name }}</p><h2 class="mt-4 text-4xl font-black text-[var(--text)]">{{ plan.price }}</h2><p class="text-sm text-[var(--muted)]">{{ plan.period }}</p><div class="mt-6 grid gap-3"><div v-for="feature in plan.features" :key="feature" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-sm font-bold text-[var(--muted)]">✓ {{ feature }}</div></div><router-link class="btn-primary mt-6 w-full" to="/register">Chọn gói</router-link></article></div>
  </section>
</template>
<script setup>
import { paymentPlans } from '@/data/demoData'
</script>
