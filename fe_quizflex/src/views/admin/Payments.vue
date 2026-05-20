<template>
  <section class="grid gap-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="relative z-10"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Payment</p><h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Gói VIP & thanh toán</h1><p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Cấu hình gói nâng cấp và lịch sử thanh toán.</p></div>
    </div>
    <div class="grid gap-5 lg:grid-cols-3"><article v-for="plan in paymentPlans" :key="plan.id" class="rounded-[2rem] border p-6 shadow-[var(--shadow-card)]" :class="plan.popular ? 'border-[var(--border-strong)] bg-[var(--chip-active)]' : 'border-[var(--border)] bg-[var(--surface)]'"><p class="font-black text-[var(--primary)]">{{ plan.name }}</p><h2 class="mt-4 text-4xl font-black text-[var(--text)]">{{ plan.price }}</h2><p class="text-sm text-[var(--muted)]">{{ plan.period }}</p><div class="mt-6 grid gap-3"><span v-for="feature in plan.features" :key="feature" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-sm font-bold text-[var(--muted)]">{{ feature }}</span></div></article></div>
    <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Payment history</p><div class="mt-5 grid gap-3"><div v-for="row in paymentHistory" :key="row.id" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4"><div class="flex justify-between gap-3"><b class="text-[var(--text)]">{{ row.user }}</b><span class="font-black text-[var(--primary)]">{{ row.amount }}</span></div><p class="mt-1 text-sm text-[var(--muted)]">{{ row.plan }} • {{ row.status }} • {{ row.date }}</p></div></div></article>
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Checkout</p><h2 class="mt-2 text-2xl font-black text-[var(--text)]">Thông tin thanh toán</h2><div class="mt-5 grid gap-4"><input class="field" placeholder="Tên trên thẻ" /><input class="field" placeholder="Số thẻ" /><div class="grid gap-4 sm:grid-cols-2"><input class="field" placeholder="MM / YY" /><input class="field" placeholder="CVC" /></div><button class="btn-primary w-full" type="button">Thanh toán</button></div></article>
    </div>
  </section>
</template>
<script setup>
const paymentPlans = [
  { id: 'free', name: 'Free', price: '0đ', period: 'Cơ bản', popular: false, features: ['Làm quiz public', 'Xem lịch sử kết quả', 'Join group quiz'] },
  { id: 'vip', name: 'VIP', price: '99.000đ', period: 'Theo tháng', popular: true, features: ['Tạo nhiều quiz', 'Tạo group quiz', 'AI/OCR quota cao hơn'] },
  { id: 'team', name: 'Team', price: 'Liên hệ', period: 'Theo nhóm', popular: false, features: ['Quản lý nhiều user', 'Báo cáo tổng hợp', 'Hỗ trợ triển khai'] },
]
const paymentHistory = []
</script>
