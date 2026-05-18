<template>
  <section class="grid gap-6 py-8 lg:grid-cols-[1fr_380px]">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-8 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Join Room</p><h1 class="mt-2 text-5xl font-black tracking-[-0.07em] text-[var(--text)]">Tham gia phòng bằng mã</h1><p class="mt-4 max-w-2xl text-sm leading-7 text-[var(--muted)]">User thường có thể join room bằng mã, xem bảng điểm realtime và làm quiz trong session. Tạo room thì xin mời nâng cấp VIP, vì đời là vậy.</p><div class="mt-8 rounded-[2rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5"><input v-model="roomCode" class="w-full bg-transparent text-center text-5xl font-black tracking-[0.22em] text-[var(--text)] outline-none" maxlength="6" /><button class="btn-primary mt-5 w-full" type="button">Join room</button></div></div>
    </article>
    <aside class="grid content-start gap-4"><div v-for="room in rooms" :key="room.id" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]"><div class="flex items-center justify-between"><b class="text-[var(--text)]">{{ room.title }}</b><span class="font-black text-[var(--primary)]">{{ room.id }}</span></div><p class="mt-2 text-sm text-[var(--muted)]">{{ room.members }} thành viên • {{ room.status }}</p></div></aside>
  </section>
</template>
<script setup>
import { ref } from 'vue'
import { rooms } from '@/data/demoData'
const roomCode = ref('QZ24')
</script>
