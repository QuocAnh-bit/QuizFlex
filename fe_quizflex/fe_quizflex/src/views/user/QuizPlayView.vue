<template>
  <section class="grid gap-6 py-8">
    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-8 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Quiz player</p>
      <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Trang làm bài đã chuyển sang luồng API</h1>
      <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Dùng đường dẫn /quizzes/:id/play để bắt đầu attempt, đếm ngược thời gian, nộp bài và lưu kết quả.</p>
      <router-link class="btn-primary mt-6 inline-flex" to="/quizzes">Chọn quiz</router-link>
    </article>
  </section>
</template>
