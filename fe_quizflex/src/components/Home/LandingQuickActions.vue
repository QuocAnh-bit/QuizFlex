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
              <path
                d="M14 3v5h5"
              />
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
      <!-- CARD 2: TẠO QUIZ BẰNG AI (Thay thế Phòng bài tập) -->
      <!-- ===================================================== -->
      <router-link
        to="/dashboard/questions/ai"
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
              <!-- Sparkles / AI -->
              <path
                d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3L12 3z"
              />
              <path
                d="M19 4v4"
              />
              <path
                d="M21 6h-4"
              />
            </svg>
          </div>

          <!-- Title -->
          <h3
            class="mt-5 text-xl font-bold text-[#0F172A] group-hover:text-[#2563EB] transition-colors"
          >
            Tạo quiz bằng AI
          </h3>

          <!-- Description -->
          <p
            class="mt-2 text-sm leading-relaxed text-[#64748B]"
          >
            Tự động tạo câu hỏi trắc nghiệm thông minh bám sát chương trình học chỉ trong vài giây.
          </p>
        </div>

        <!-- Bottom -->
        <div
          class="mt-6 flex items-center gap-1 text-sm font-bold text-[#2563EB]"
        >
          <span>
            Tạo quiz ngay
          </span>

          <span
            class="transition-transform duration-200 group-hover:translate-x-1"
          >
            →
          </span>
        </div>
      </router-link>


      <!-- ===================================================== -->
      <!-- CARD 3: QUÉT ĐỀ THI OCR (Thay thế Phòng thi đấu) -->
      <!-- ===================================================== -->
      <router-link
        to="/dashboard/questions/ocr"
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
              <!-- Camera / Scan OCR -->
              <path
                d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"
              />
              <circle
                cx="12"
                cy="13"
                r="3"
              />
            </svg>
          </div>

          <!-- Title -->
          <h3
            class="mt-5 text-xl font-bold text-[#0F172A] group-hover:text-[#F59E0B] transition-colors"
          >
            Quét đề thi OCR
          </h3>

          <!-- Description -->
          <p
            class="mt-2 text-sm leading-relaxed text-[#64748B]"
          >
            Tải lên hình ảnh hoặc tài liệu đề thi để tự động trích xuất câu hỏi và đáp án.
          </p>
        </div>

        <!-- Bottom -->
        <div
          class="mt-6 flex items-center gap-1 text-sm font-bold text-[#F59E0B]"
        >
          <span>
            Quét đề ngay
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
