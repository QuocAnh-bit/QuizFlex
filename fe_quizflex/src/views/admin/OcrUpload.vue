<template>
  <section class="grid gap-6 xl:grid-cols-[1fr_420px]">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--accent-2)]/15 blur-3xl"></div>
      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">OCR Upload</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Upload PDF / Image</h1>
        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Kéo thả tài liệu, xem progress và preview nội dung OCR trước khi chuyển thành quiz. Chưa gọi API, nhưng UI thì đã biết diễn như thật.</p>

        <label class="mt-8 grid min-h-[260px] cursor-pointer place-items-center rounded-[2rem] border-2 border-dashed border-[var(--border-strong)] bg-[var(--chip-active)] p-8 text-center transition duration-300 hover:-translate-y-1 hover:shadow-[0_24px_70px_rgba(155,44,255,0.18)]">
          <input class="hidden" type="file" accept=".pdf,image/*" @change="handleFile" />
          <div>
            <div class="mx-auto mb-5 grid h-20 w-20 place-items-center rounded-[1.5rem] bg-gradient-to-br from-[var(--primary)] to-[var(--primary-2)] text-3xl text-white shadow-[0_20px_50px_rgba(155,44,255,0.35)]">OCR</div>
            <h2 class="text-2xl font-black text-[var(--text)]">Thả file vào đây hoặc bấm để chọn</h2>
            <p class="mt-2 text-sm font-semibold text-[var(--muted)]">PDF, PNG, JPG, JPEG. Tối đa 20MB.</p>
          </div>
        </label>

        <div v-if="fileName" class="mt-6 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5">
          <div class="flex flex-wrap items-center justify-between gap-3"><div><b class="text-[var(--text)]">{{ fileName }}</b><p class="mt-1 text-sm text-[var(--muted)]">Đang xử lý OCR mock...</p></div><span class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-black text-emerald-400">{{ progress }}%</span></div>
          <div class="mt-4 h-3 overflow-hidden rounded-full bg-[var(--surface)]"><div class="h-full rounded-full bg-gradient-to-r from-[var(--primary)] via-[var(--primary-2)] to-[var(--accent)] transition-all duration-500" :style="{ width: `${progress}%` }"></div></div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3"><button class="btn-ghost" type="button" @click="resetFile">Xóa file</button><router-link class="btn-primary" to="/admin/questions/create">Chuyển sang editor</router-link></div>
      </div>
    </article>

    <aside class="grid content-start gap-5">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">OCR Preview</p>
        <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Kết quả trích xuất</h2>
        <div class="mt-5 max-h-[420px] overflow-auto rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm leading-7 text-[var(--muted)] scrollbar-soft">
          <p v-for="line in ocrLines" :key="line" class="mb-3">{{ line }}</p>
        </div>
      </article>
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">AI suggestion</p>
        <div class="mt-4 grid gap-3"><div v-for="item in suggestions" :key="item" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 text-sm font-bold text-[var(--muted)]">{{ item }}</div></div>
      </article>
    </aside>
  </section>
</template>

<script setup>
import { ref } from 'vue'
const fileName = ref('de-cuong-sinh-hoc.pdf')
const progress = ref(78)
const ocrLines = ['Chương 1: Tế bào và các bào quan chính.', 'Ti thể có vai trò chuyển hóa năng lượng, tạo ATP cho tế bào.', 'Ribosome tham gia quá trình tổng hợp protein.', 'Nhân tế bào chứa vật chất di truyền và điều khiển hoạt động sống.', 'Gợi ý: Có thể tạo 10 câu trắc nghiệm mức độ trung bình từ nội dung này.']
const suggestions = ['Tạo 10 câu mức trung bình', 'Ưu tiên câu hỏi khái niệm', 'Thêm 2 câu vận dụng', 'Giữ visibility: Private trước khi duyệt']
const handleFile = (event) => { const file = event.target.files?.[0]; if (file) { fileName.value = file.name; progress.value = 100 } }
const resetFile = () => { fileName.value = ''; progress.value = 0 }
</script>
