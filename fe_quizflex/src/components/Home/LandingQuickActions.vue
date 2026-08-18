<template>
  <section class="w-full">
    <div class="grid gap-5 md:grid-cols-3">

      <!-- ===================================================== -->
      <!-- CARD 1: LÀM QUIZ -->
      <!-- ===================================================== -->
      <router-link
        to="/quizzes"
        class="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_4px_16px_rgba(15,23,42,0.06)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[#7C3AED] hover:shadow-[0_8px_24px_rgba(15,23,42,0.08)]"
      >
        <div>

          <!-- Icon -->
          <div
            class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#7C3AED] text-white shadow-sm"
          >
            <svg
              class="h-6 w-6"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <!-- Document -->
              <path
                d="M6 3h8l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"
              />

              <!-- Fold -->
              <path
                d="M14 3v5h5"
              />

              <!-- Lines -->
              <path
                d="M8 12h6"
              />

              <path
                d="M8 16h5"
              />

              <path
                d="M8 8h2"
              />
            </svg>
          </div>

          <!-- Title -->
          <h3
            class="mt-5 text-xl font-bold text-[#0F172A] group-hover:text-[#7C3AED] transition-colors"
          >
            Làm quiz
          </h3>

          <!-- Description -->
          <p
            class="mt-2 text-sm leading-relaxed text-[#64748B]"
          >
            Khám phá hàng nghìn câu hỏi và kiểm tra kiến thức của bạn mọi lúc mọi nơi.
          </p>
        </div>

        <!-- Bottom -->
        <div
          class="mt-6 flex items-center gap-1 text-sm font-bold text-[#7C3AED]"
        >
          <span>
            Khám phá ngay
          </span>

          <span
            class="transition-transform duration-200 group-hover:translate-x-1"
          >
            →
          </span>
        </div>
      </router-link>


      <!-- ===================================================== -->
      <!-- CARD 2: PHÒNG BÀI TẬP -->
      <!-- ===================================================== -->
      <router-link
        to="/homework-rooms"
        class="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_4px_16px_rgba(15,23,42,0.06)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[#2563EB] hover:shadow-[0_8px_24px_rgba(15,23,42,0.08)]"
      >
        <div>

          <!-- Icon -->
          <div
            class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#2563EB] text-white shadow-sm"
          >
            <svg
              class="h-6 w-6"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <!-- Book -->
              <path
                d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v17H6.5A2.5 2.5 0 0 0 4 22V5.5z"
              />

              <path
                d="M4 5.5V22"
              />

              <path
                d="M8 7h8"
              />

              <path
                d="M8 11h8"
              />

              <path
                d="M8 15h5"
              />
            </svg>
          </div>

          <!-- Title -->
          <h3
            class="mt-5 text-xl font-bold text-[#0F172A] group-hover:text-[#2563EB] transition-colors"
          >
            Phòng bài tập
          </h3>

          <!-- Description -->
          <p
            class="mt-2 text-sm leading-relaxed text-[#64748B]"
          >
            Tham gia phòng học, hoàn thành bài tập được giao và theo dõi tiến độ học tập chi tiết.
          </p>
        </div>

        <!-- Bottom -->
        <div
          class="mt-6 flex items-center gap-1 text-sm font-bold text-[#2563EB]"
        >
          <span>
            Vào phòng học
          </span>

          <span
            class="transition-transform duration-200 group-hover:translate-x-1"
          >
            →
          </span>
        </div>
      </router-link>


      <!-- ===================================================== -->
      <!-- CARD 3: PHÒNG THI ĐẤU -->
      <!-- ===================================================== -->
      <router-link
        to="/live-rooms"
        class="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_4px_16px_rgba(15,23,42,0.06)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[#F59E0B] hover:shadow-[0_8px_24px_rgba(15,23,42,0.08)]"
      >
        <div>

          <!-- Icon -->
          <div
            class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#F59E0B] text-white shadow-sm"
          >
            <svg
              class="h-6 w-6"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <!-- Trophy -->
              <path
                d="M8 4h8v5a4 4 0 0 1-8 0V4z"
              />

              <path
                d="M8 6H5a2 2 0 0 0 2 4"
              />

              <path
                d="M16 6h3a2 2 0 0 1-2 4"
              />

              <path
                d="M12 13v4"
              />

              <path
                d="M9 21h6"
              />

              <path
                d="M10 17h4"
              />
            </svg>
          </div>

          <!-- Title -->
          <h3
            class="mt-5 text-xl font-bold text-[#0F172A] group-hover:text-[#F59E0B] transition-colors"
          >
            Phòng thi đấu
          </h3>

          <!-- Description -->
          <p
            class="mt-2 text-sm leading-relaxed text-[#64748B]"
          >
            Thi đấu trực tiếp cùng bạn bè và người chơi khác trong thời gian thực kiểu Kahoot.
          </p>
        </div>

        <!-- Bottom -->
        <div
          class="mt-6 flex items-center gap-1 text-sm font-bold text-[#F59E0B]"
        >
          <span>
            Tham gia ngay
          </span>

          <span
            class="transition-transform duration-200 group-hover:translate-x-1"
          >
            →
          </span>
        </div>
      </router-link>

    </div>
  </section>
</template>

<script setup>
// QuizFlex - Modern Education Feature Cards
</script>
