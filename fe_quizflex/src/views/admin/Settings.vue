<template>
  <section class="max-w-5xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Cấu hình hệ thống</p>
        <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Cài đặt hệ thống</h1>
        <p class="mt-1 text-sm text-slate-600">Cấu hình định mức tài nguyên AI, OCR và tùy chọn hiển thị mặc định.</p>
      </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <!-- AI & OCR Limits -->
      <article class="card p-6 space-y-4">
        <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Định mức tài nguyên (AI & OCR)</h2>
        <div class="space-y-3">
          <div v-for="row in limits" :key="row.role" class="rounded-xl border border-slate-100 bg-slate-50 p-3.5 space-y-2">
            <b class="text-xs font-bold text-slate-900 block">{{ row.role }}</b>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <div>
                <span class="text-[10px] text-slate-400 block font-semibold">AI Quota</span>
                <input class="field text-xs mt-1" :value="row.ai" />
              </div>
              <div>
                <span class="text-[10px] text-slate-400 block font-semibold">OCR Quota</span>
                <input class="field text-xs mt-1" :value="row.ocr" />
              </div>
            </div>
          </div>
        </div>
      </article>

      <!-- Visibility Defaults -->
      <article class="card p-6 space-y-4 flex flex-col justify-between">
        <div class="space-y-4">
          <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Hiển thị mặc định khi tạo Quiz</h2>
          <div class="space-y-2">
            <label 
              v-for="item in visibility" 
              :key="item" 
              class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 p-3.5 text-xs font-semibold text-slate-800 cursor-pointer hover:border-purple-200 transition"
            >
              <span>{{ item }}</span>
              <input type="radio" name="visibility" class="text-[#7C3AED]" :checked="item === 'Private'" />
            </label>
          </div>
        </div>

        <button class="btn-primary w-full py-2.5 text-xs" type="button">
          Lưu cài đặt hệ thống
        </button>
      </article>
    </div>
  </section>
</template>

<script setup>
const limits = [
  { role: 'Admin', ai: 'Unlimited', ocr: 'Unlimited' },
  { role: 'Ultra', ai: '1500 lượt/tháng', ocr: 'Không giới hạn' },
  { role: 'Pro', ai: '350 lượt/tháng', ocr: '50 lượt/tháng' },
  { role: 'Plus', ai: '100 lượt/tháng', ocr: '10 lượt/tháng' },
  { role: 'Free (Thường)', ai: '10 lượt/tháng', ocr: 'Yêu cầu gói Plus' }
]
const visibility = ['Public', 'Private', 'Group']
</script>
