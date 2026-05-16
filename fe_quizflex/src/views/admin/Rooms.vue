<template>
  <section class="grid gap-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--accent)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Room Management</p><h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Room realtime</h1><p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Hai layout riêng: Host điều khiển session, Member theo dõi tiến độ và bảng điểm. Cuối cùng room không còn là một cái thẻ cô đơn.</p></div>
        <div class="flex flex-wrap gap-3"><button class="btn-ghost" type="button" @click="activeMode = 'member'">Member view</button><button class="btn-primary" type="button" @click="activeMode = 'host'">Host view</button></div>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
      <button v-for="room in rooms" :key="room.id" type="button" class="rounded-[1.5rem] border p-4 text-left transition duration-300 hover:-translate-y-1" :class="selectedRoom.id === room.id ? 'border-[var(--border-strong)] bg-[var(--chip-active)] shadow-[0_18px_44px_rgba(155,44,255,0.16)]' : 'border-[var(--border)] bg-[var(--surface)] hover:border-[var(--border-strong)]'" @click="selectedRoom = room; activeMode = room.mode">
        <div class="flex items-center justify-between gap-3"><b class="text-[var(--text)]">{{ room.title }}</b><span class="rounded-full px-3 py-1 text-xs font-black" :class="room.status === 'live' ? 'bg-emerald-500/15 text-emerald-400' : room.status === 'waiting' ? 'bg-amber-500/15 text-amber-400' : 'bg-slate-500/15 text-slate-300'">{{ room.status }}</span></div>
        <p class="mt-2 text-sm text-[var(--muted)]">{{ room.id }} • {{ room.members }} thành viên • {{ room.quiz }}</p>
      </button>
    </div>

    <div v-if="activeMode === 'host'" class="grid gap-6 xl:grid-cols-[1fr_380px]">
      <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
        <div class="relative z-10"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Host Control</p><h2 class="mt-2 text-3xl font-black text-[var(--text)]">{{ selectedRoom.title }}</h2><div class="mt-5 grid gap-4 md:grid-cols-4"><div v-for="stat in roomStats" :key="stat.label" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4"><p class="text-xs font-bold text-[var(--muted)]">{{ stat.label }}</p><b class="mt-1 block text-2xl font-black text-[var(--text)]">{{ stat.value }}</b></div></div><div class="mt-6 flex flex-wrap gap-3"><button class="btn-primary" type="button">Start quiz</button><button class="btn-ghost" type="button">Pause</button><button class="rounded-full bg-rose-500/15 px-5 py-3 text-sm font-black text-rose-400" type="button">Đóng room</button></div></div>
      </article>
      <aside class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Room Code</p><div class="mt-4 rounded-[1.5rem] border border-[var(--border-strong)] bg-[var(--chip-active)] p-6 text-center text-4xl font-black tracking-[0.2em] text-[var(--text)]">{{ selectedRoom.id }}</div><p class="mt-4 text-sm leading-6 text-[var(--muted)]">Gửi mã này cho thành viên. User thường join được bằng mã, không cần tạo room.</p></aside>
    </div>

    <div v-else class="grid gap-6 xl:grid-cols-[1fr_380px]">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Member View</p><h2 class="mt-2 text-3xl font-black text-[var(--text)]">Đang tham gia {{ selectedRoom.title }}</h2><p class="mt-3 text-sm leading-7 text-[var(--muted)]">Bạn là thành viên room. Có thể xem bảng điểm realtime, trạng thái câu hỏi và tiến độ session, nhưng không điều khiển start/stop.</p><div class="mt-6 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5"><div class="mb-3 flex justify-between text-sm font-bold text-[var(--muted)]"><span>Tiến độ session</span><span>7 / 10 câu</span></div><div class="h-3 overflow-hidden rounded-full bg-[var(--surface)]"><div class="h-full w-[70%] rounded-full bg-gradient-to-r from-[var(--primary)] to-[var(--accent)]"></div></div></div></article>
      <aside class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl"><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Join by code</p><input v-model="joinCode" class="field mt-4 text-center text-2xl font-black tracking-[0.2em]" placeholder="QZ24" /><button class="btn-primary mt-4 w-full" type="button">Join room</button></aside>
    </div>

    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <div class="mb-5 flex items-center justify-between"><div><p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Leaderboard</p><h2 class="mt-2 text-2xl font-black text-[var(--text)]">Người chơi realtime</h2></div><span class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-black text-emerald-400">Live</span></div>
      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4"><div v-for="(player, index) in roomPlayers" :key="player.name" class="rounded-[1.4rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4"><div class="flex items-center justify-between"><b class="text-[var(--text)]">#{{ index + 1 }} {{ player.name }}</b><span class="badge">{{ player.score }}</span></div><p class="mt-2 text-sm text-[var(--muted)]">{{ player.status }}</p></div></div>
    </article>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'
import { roomPlayers, rooms as roomData } from '@/data/demoData'
const rooms = roomData
const selectedRoom = ref(rooms[0])
const activeMode = ref('host')
const joinCode = ref('QZ24')
const roomStats = computed(() => [
  { label: 'Mã phòng', value: selectedRoom.value.id },
  { label: 'Thành viên', value: selectedRoom.value.members },
  { label: 'Điểm TB', value: `${selectedRoom.value.avgScore}%` },
  { label: 'Bắt đầu', value: selectedRoom.value.startedAt },
])
</script>
