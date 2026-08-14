<template>
  <section class="max-w-xl mx-auto py-12 px-4">
    <div class="card p-8 text-center space-y-4">
      <div class="h-14 w-14 rounded-full bg-purple-50 text-[#7C3AED] flex items-center justify-center text-2xl font-bold mx-auto">
        📝
      </div>
      <h1 class="text-xl font-black text-slate-900">Làm bài trắc nghiệm</h1>
      <p class="text-xs text-slate-600">Vui lòng chọn một đề thi từ danh sách để bắt đầu làm bài và tính thời gian.</p>
      <router-link class="btn-primary inline-flex text-xs px-4 py-2" to="/quizzes">
        Chọn đề thi ngay →
      </router-link>
    </div>
  </section>
</template>
