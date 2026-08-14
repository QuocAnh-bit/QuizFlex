<template>
  <section class="my-10">
    <div class="grid gap-6 md:grid-cols-3">
      <!-- Card 1: Làm quiz (Purple Accent) -->
      <router-link
        to="/quizzes"
        class="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_4px_16px_rgba(15,23,42,0.06)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[#7C3AED] hover:shadow-[0_8px_24px_rgba(15,23,42,0.08)]"
      >
        <div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#F5F3FF] text-2xl text-[#7C3AED]">
            📝
          </div>
          <h3 class="mt-5 text-xl font-bold text-[#0F172A] group-hover:text-[#7C3AED] transition-colors">
            Làm quiz
          </h3>
          <p class="mt-2 text-sm leading-relaxed text-[#64748B]">
            Khám phá hàng nghìn câu hỏi và kiểm tra kiến thức của bạn mọi lúc mọi nơi.
          </p>
        </div>
        <div class="mt-6 flex items-center gap-1 text-sm font-bold text-[#7C3AED]">
          <span>Khám phá ngay</span>
          <span class="transition-transform duration-200 group-hover:translate-x-1">→</span>
        </div>
      </router-link>

      <!-- Card 2: Phòng bài tập (Blue Accent) -->
      <router-link
        to="/homework-rooms"
        class="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_4px_16px_rgba(15,23,42,0.06)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[#2563EB] hover:shadow-[0_8px_24px_rgba(15,23,42,0.08)]"
      >
        <div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#EFF6FF] text-2xl text-[#2563EB]">
            📚
          </div>
          <h3 class="mt-5 text-xl font-bold text-[#0F172A] group-hover:text-[#2563EB] transition-colors">
            Phòng bài tập
          </h3>
          <p class="mt-2 text-sm leading-relaxed text-[#64748B]">
            Tham gia phòng học, hoàn thành bài tập được giao và theo dõi tiến độ học tập chi tiết.
          </p>
        </div>
        <div class="mt-6 flex items-center gap-1 text-sm font-bold text-[#2563EB]">
          <span>Vào phòng học</span>
          <span class="transition-transform duration-200 group-hover:translate-x-1">→</span>
        </div>
      </router-link>

      <!-- Card 3: Phòng thi đấu (Orange Accent) -->
      <router-link
        to="/live-rooms"
        class="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_4px_16px_rgba(15,23,42,0.06)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[#F59E0B] hover:shadow-[0_8px_24px_rgba(15,23,42,0.08)]"
      >
        <div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#FFFBEB] text-2xl text-[#F59E0B]">
            ⚡
          </div>
          <h3 class="mt-5 text-xl font-bold text-[#0F172A] group-hover:text-[#F59E0B] transition-colors">
            Phòng thi đấu
          </h3>
          <p class="mt-2 text-sm leading-relaxed text-[#64748B]">
            Thi đấu trực tiếp cùng bạn bè và người chơi khác trong thời gian thực kiểu Kahoot.
          </p>
        </div>
        <div class="mt-6 flex items-center gap-1 text-sm font-bold text-[#F59E0B]">
          <span>Tham gia ngay</span>
          <span class="transition-transform duration-200 group-hover:translate-x-1">→</span>
        </div>
      </router-link>
    </div>
  </section>
</template>

<script setup>
// Modern Education 3 Feature Cards Component
</script>