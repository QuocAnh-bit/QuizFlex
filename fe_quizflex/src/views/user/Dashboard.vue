<template>
  <section class="grid gap-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl sm:p-8">
      <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
        <div>
          <p class="text-sm font-black uppercase tracking-[0.2em] text-[var(--primary)]">Dashboard người dùng</p>
          <h1 class="mt-3 text-4xl font-black tracking-[-0.06em] text-[var(--text)] sm:text-5xl">Không gian học và tạo quiz của bạn</h1>
          <p class="mt-4 max-w-2xl text-sm font-medium leading-7 text-[var(--muted)]">Trang này tách khỏi dashboard admin. User và VIP dùng khu vực này để tạo quiz, quản lý kho quiz, OCR, AI và room.</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <router-link class="btn-primary" to="/dashboard/questions/create">Tạo quiz</router-link>
          <router-link class="btn-ghost" to="/dashboard/questions">Kho quiz</router-link>
        </div>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <router-link
        v-for="item in quickActions"
        :key="item.to"
        :to="item.to"
        class="group rounded-[1.75rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] transition hover:-translate-y-1 hover:border-[var(--border-strong)] hover:bg-[var(--chip-active)]"
      >
        <div class="grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-[var(--primary)] to-[var(--primary-2)] text-xs font-black text-white shadow-[0_16px_34px_rgba(155,44,255,0.25)]">{{ item.icon }}</div>
        <h2 class="mt-5 text-xl font-black text-[var(--text)]">{{ item.title }}</h2>
        <p class="mt-2 text-sm font-medium leading-6 text-[var(--muted)]">{{ item.desc }}</p>
      </router-link>
    </div>
  </section>
</template>

<script setup>
const quickActions = [
  { title: 'Kho quiz của tôi', desc: 'Xem, lọc, sửa và xóa quiz bạn đã tạo.', to: '/dashboard/questions', icon: 'QZ' },
  { title: 'Tạo quiz thủ công', desc: 'Tạo quiz mới với câu hỏi, đáp án, thời gian và ảnh bìa.', to: '/dashboard/questions/create', icon: '+' },
  { title: 'AI Generator', desc: 'Sinh bản nháp quiz bằng AI rồi chỉnh lại trước khi lưu.', to: '/dashboard/questions/ai', icon: 'AI' },
  { title: 'OCR Upload', desc: 'Upload ảnh hoặc tài liệu để trích xuất câu hỏi bằng OCR.', to: '/dashboard/questions/ocr', icon: 'OC' },
  { title: 'Room', desc: 'Tạo và quản lý room quiz realtime cho nhóm học.', to: '/dashboard/rooms', icon: 'RT' },
  { title: 'Kết quả của tôi', desc: 'Xem lịch sử làm bài và điểm số đã lưu.', to: '/results', icon: 'RS' },
]
</script>
