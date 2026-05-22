<template>
  <section class="grid gap-6">
    <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Settings</p><h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Cài đặt hệ thống</h1><p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Cấu hình giới hạn theo role và visibility mặc định cho quiz mới.</p></div>
    <div class="grid gap-6 xl:grid-cols-2">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">AI limits</p><div class="mt-5 grid gap-3"><div v-for="row in limits" :key="row.role" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4"><div class="mb-3"><b class="text-[var(--text)]">{{ row.role }}</b></div><div class="grid gap-3 md:grid-cols-2"><input class="field" :value="row.ai" /><input class="field" :value="row.ocr" /></div></div></div></article>
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Visibility defaults</p><div class="mt-5 grid gap-3"><label v-for="item in visibility" :key="item" class="flex items-center justify-between rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4"><span class="font-bold text-[var(--text)]">{{ item }}</span><input type="radio" name="visibility" class="h-4 w-4 accent-[var(--primary)]" :checked="item === 'Private'" /></label></div><button class="btn-primary mt-5" type="button">Lưu cài đặt</button></article>
    </div>
  </section>
</template>
<script setup>
const limits = [{ role: 'Admin', ai: 'Unlimited', ocr: 'Unlimited' }, { role: 'VIP', ai: '500 lượt/tháng', ocr: '120 lượt/tháng' }, { role: 'Thường', ai: '10 lượt/tháng', ocr: '3 lượt/tháng' }]
const visibility = ['Public', 'Private', 'Group']
</script>
