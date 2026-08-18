<template>
  <section class="max-w-6xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Không gian học tập</p>
        <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Bảng điều khiển cá nhân</h1>
        <p class="mt-1 text-sm text-slate-600">Quản lý kho quiz, ngân hàng câu hỏi, công cụ sinh đề AI, OCR và phòng học nhóm.</p>
      </div>
      <div class="flex items-center gap-2.5">
        <router-link class="btn-primary text-xs px-3.5 py-2" to="/dashboard/questions/create">
          + Tạo Quiz mới
        </router-link>
        <router-link class="btn-secondary text-xs px-3.5 py-2" to="/dashboard/questions">
          Kho Quiz của tôi
        </router-link>
      </div>
    </div>

    <!-- Quick action cards grid -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <router-link
        v-for="item in quickActions"
        :key="item.to"
        :to="item.to"
        class="card card-hover p-5 space-y-3 block"
      >
        <div class="h-10 w-10 rounded-xl bg-purple-50 text-[#7C3AED] flex items-center justify-center font-bold text-sm">
          {{ item.icon }}
        </div>
        <div>
          <h2 class="text-sm font-bold text-slate-900">{{ item.title }}</h2>
          <p class="mt-1 text-xs text-slate-500 leading-relaxed">{{ item.desc }}</p>
        </div>
      </router-link>
    </div>
  </section>
</template>

<script setup>
const quickActions = [
  { title: 'Kho quiz của tôi', desc: 'Xem, lọc, sửa và quản lý các bộ đề do bạn tạo.', to: '/dashboard/questions', icon: '📚' },
  { title: 'Kho câu hỏi của tôi', desc: 'Xem, sửa nội dung/đáp án và quản lý Thùng rác câu hỏi.', to: '/dashboard/my-questions', icon: '❓' },
  { title: 'Tạo quiz thủ công', desc: 'Tự biên soạn câu hỏi, cấu hình đáp án và thời gian làm bài.', to: '/dashboard/questions/create', icon: '✍️' },
  { title: 'AI Generator', desc: 'Sinh câu hỏi tự động với AI chỉ trong vài giây.', to: '/dashboard/questions/ai', icon: '🤖' },
  { title: 'OCR Upload', desc: 'Quét tài liệu ảnh hoặc PDF để trích xuất đề thi.', to: '/dashboard/questions/ocr', icon: '📄' },
  { title: 'Phòng Quiz nhóm', desc: 'Tạo mã phòng và cùng luyện tập trực tiếp với bạn bè.', to: '/dashboard/rooms', icon: '👥' },
  { title: 'Lịch sử & Kết quả', desc: 'Theo dõi điểm số, xếp hạng và tiến độ học tập.', to: '/results', icon: '📊' },
]
</script>
