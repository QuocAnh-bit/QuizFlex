<template>
  <section class="grid gap-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Admin UI</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Quản lý user</h1>
        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Bảng user, filter theo role/status, search theo tên/email và action khóa/mở tài khoản. Chưa có DB, nhưng FE thì cứ diễn cho sang đã.</p>
      </div>
    </div>

    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <div class="grid gap-4 lg:grid-cols-[1fr_180px_180px]">
        <div class="flex items-center gap-3 rounded-2xl border border-[var(--border)] bg-[var(--input-bg)] px-4 py-3 focus-within:border-[var(--border-strong)]"><span>🔍</span><input v-model="search" class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]" placeholder="Tìm user theo tên hoặc email" /></div>
        <select v-model="roleFilter" class="field"><option value="all">Tất cả role</option><option value="admin">Admin</option><option value="vip">VIP</option><option value="user">Thường</option></select>
        <select v-model="statusFilter" class="field"><option value="all">Tất cả trạng thái</option><option value="active">Active</option><option value="locked">Locked</option></select>
      </div>
    </article>

    <article class="overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <div class="hidden grid-cols-[1.3fr_0.7fr_0.7fr_0.7fr_0.6fr] gap-4 border-b border-[var(--border)] bg-[var(--surface-soft)] px-5 py-4 text-xs font-black uppercase tracking-[0.15em] text-[var(--muted)] lg:grid">
        <span>User</span><span>Role</span><span>Status</span><span>AI usage</span><span>Action</span>
      </div>
      <div class="grid divide-y divide-[var(--border)]">
        <div v-for="user in filteredUsers" :key="user.id" class="grid gap-4 p-5 transition hover:bg-[var(--surface-soft)] lg:grid-cols-[1.3fr_0.7fr_0.7fr_0.7fr_0.6fr] lg:items-center">
          <div><b class="text-[var(--text)]">{{ user.name }}</b><p class="mt-1 text-sm text-[var(--muted)]">{{ user.email }} • joined {{ user.joinedAt }}</p></div>
          <span class="w-fit rounded-full px-3 py-1 text-xs font-black" :class="roleClass(user.role)">{{ roleText(user.role) }}</span>
          <span class="w-fit rounded-full px-3 py-1 text-xs font-black" :class="user.status === 'active' ? 'bg-emerald-500/15 text-emerald-400' : 'bg-rose-500/15 text-rose-400'">{{ user.status }}</span>
          <div><b class="text-[var(--text)]">{{ user.aiUsed }}</b><p class="text-xs text-[var(--muted)]">{{ user.sessions }} session active</p></div>
          <button class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-2 text-xs font-black text-[var(--text)] transition hover:border-[var(--border-strong)] hover:bg-[var(--chip-active)]" type="button">{{ user.status === 'active' ? 'Khóa' : 'Mở' }}</button>
        </div>
      </div>
    </article>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'
import { users } from '@/data/demoData'
const search = ref('')
const roleFilter = ref('all')
const statusFilter = ref('all')
const filteredUsers = computed(() => {
  const keyword = search.value.trim().toLowerCase()
  return users.filter((user) => {
    const matchesKeyword = !keyword || [user.name, user.email].join(' ').toLowerCase().includes(keyword)
    const matchesRole = roleFilter.value === 'all' || user.role === roleFilter.value
    const matchesStatus = statusFilter.value === 'all' || user.status === statusFilter.value
    return matchesKeyword && matchesRole && matchesStatus
  })
})
const roleText = (role) => ({ admin: 'Admin', vip: 'VIP', user: 'Thường' }[role] || role)
const roleClass = (role) => ({ admin: 'bg-purple-500/15 text-purple-300', vip: 'bg-amber-500/15 text-amber-400', user: 'bg-slate-500/15 text-slate-300' }[role] || 'bg-[var(--chip-active)] text-[var(--primary)]')
</script>
