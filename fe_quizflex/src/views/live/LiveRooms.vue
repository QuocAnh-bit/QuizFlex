<template>
  <section class="grid gap-6 py-8">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)]">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10">
      <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Live Room</p>
      <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Chơi quiz trực tiếp</h1>
      <p class="mt-3 max-w-3xl text-sm leading-7 text-[var(--muted)]">
        Host tạo phòng từ quiz, người chơi tham gia bằng mã và tự trả lời theo tiến độ riêng. Bảng xếp hạng cập nhật realtime, polling giữ vai trò fallback.
      </p>
      </div>
    </article>

    <div class="grid gap-5 md:grid-cols-2">
      <router-link v-if="canCreateLiveRoom" class="group rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] transition hover:-translate-y-1 hover:border-[var(--border-strong)]" to="/live-rooms/create">
        <span class="rounded-full bg-[var(--chip-active)] px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-[var(--primary)]">Host</span>
        <h2 class="mt-5 text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Tạo live room</h2>
        <p class="mt-3 text-sm leading-7 text-[var(--muted)]">Dành cho tài khoản VIP tạo phòng, chia sẻ mã, start và theo dõi tiến độ người chơi.</p>
        <span class="btn-primary mt-6 inline-flex">Tạo live room</span>
      </router-link>

      <article v-else class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <span class="rounded-full bg-[var(--chip-active)] px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-[var(--primary)]">Host</span>
        <h2 class="mt-5 text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Tạo live room</h2>
        <p class="mt-3 text-sm leading-7 text-[var(--muted)]">Tài khoản VIP mới có thể tạo live room. Bạn vẫn có thể tham gia live room bằng mã.</p>
        <router-link class="btn-ghost mt-6 inline-flex" to="/upgrade">Nâng cấp VIP</router-link>
      </article>

      <router-link class="group rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] transition hover:-translate-y-1 hover:border-[var(--border-strong)]" to="/live-rooms/join">
        <span class="rounded-full bg-[var(--chip-active)] px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-[var(--primary)]">Player</span>
        <h2 class="mt-5 text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Tham gia live room</h2>
        <p class="mt-3 text-sm leading-7 text-[var(--muted)]">Nhập mã live room, chờ host bắt đầu và trả lời các câu hỏi theo tốc độ của bạn.</p>
        <span class="btn-ghost mt-6 inline-flex">Nhập mã phòng</span>
      </router-link>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { currentUserStorage } from '@/services/api'

const currentUser = currentUserStorage.get()
const canCreateLiveRoom = computed(() => String(currentUser?.role || 'user').toLowerCase() === 'vip')
</script>
