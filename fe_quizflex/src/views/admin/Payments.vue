<template>
  <section class="grid gap-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--accent)]/15 blur-3xl"></div>
      <div class="relative z-10"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Payment</p><h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Gói VIP & thanh toán</h1><p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Giao diện chọn gói, checkout mock và lịch sử thanh toán. Tiền vẫn là giả, nỗi đau subscription thì rất thật.</p></div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
      <article v-for="plan in paymentPlans" :key="plan.id" class="relative overflow-hidden rounded-[2rem] border p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl transition duration-300 hover:-translate-y-2" :class="plan.popular ? 'border-[var(--border-strong)] bg-[var(--chip-active)]' : 'border-[var(--border)] bg-[var(--surface)] hover:border-[var(--border-strong)]'">
        <div v-if="plan.popular" class="absolute right-5 top-5 rounded-full bg-gradient-to-br from-[var(--primary)] to-[var(--primary-2)] px-3 py-1 text-xs font-black text-white">{{ plan.badge }}</div>
        <p class="text-sm font-black text-[var(--primary)]">{{ plan.name }}</p>
        <div class="mt-4 flex items-end gap-1"><b class="text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ plan.price }}</b><span class="pb-1 text-sm font-bold text-[var(--muted)]">{{ plan.period }}</span></div>
        <div class="mt-6 grid gap-3"><div v-for="feature in plan.features" :key="feature" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-sm font-bold text-[var(--muted)]">✓ {{ feature }}</div></div>
        <button class="mt-6 w-full rounded-full px-5 py-3 text-sm font-black transition active:scale-95" :class="plan.popular ? 'bg-gradient-to-br from-[var(--primary)] to-[var(--primary-2)] text-white shadow-[0_18px_38px_rgba(155,44,255,0.28)]' : 'border border-[var(--border)] bg-[var(--surface-soft)] text-[var(--text)] hover:border-[var(--border-strong)]'" type="button">Chọn gói</button>
      </article>
    </div>

    <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Checkout mock</p><h2 class="mt-2 text-2xl font-black text-[var(--text)]">Thông tin thanh toán</h2><div class="mt-5 grid gap-4"><input class="field" placeholder="Tên trên thẻ" /><input class="field" placeholder="Số thẻ: 4242 4242 4242 4242" /><div class="grid gap-4 sm:grid-cols-2"><input class="field" placeholder="MM / YY" /><input class="field" placeholder="CVC" /></div><button class="btn-primary w-full" type="button">Thanh toán mock</button></div></article>
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">History</p><h2 class="mt-2 text-2xl font-black text-[var(--text)]">Lịch sử thanh toán</h2><div class="mt-5 grid gap-3"><div v-for="item in paymentHistory" :key="item.id" class="rounded-[1.4rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4"><div class="flex flex-wrap items-center justify-between gap-3"><div><b class="text-[var(--text)]">{{ item.id }} • {{ item.name }}</b><p class="mt-1 text-sm text-[var(--muted)]">{{ item.plan }} • {{ item.date }}</p></div><span class="font-black text-[var(--primary)]">{{ item.amount }}</span></div></div></div></article>
    </div>
  </section>
</template>
<script setup>
import { paymentHistory, paymentPlans } from '@/data/demoData'
</script>
