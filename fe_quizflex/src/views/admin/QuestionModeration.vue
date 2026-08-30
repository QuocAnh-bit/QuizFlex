<template>
  <div class="rounded-2xl border border-purple-200 bg-purple-50 p-8 text-center space-y-4">
    <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-[#7C3AED] text-white">
      <CheckSquare class="h-7 w-7" />
    </div>
    <div class="space-y-1.5">
      <h2 class="text-xl font-bold text-slate-900">Đã hợp nhất vào "Duyệt câu hỏi vào Ngân hàng"</h2>
      <p class="text-xs text-slate-600 max-w-md mx-auto leading-relaxed">
        Module này đã được nâng cấp và chuyển tới trang quản lý yêu cầu duyệt câu hỏi vào Ngân hàng chuyên dụng.
      </p>
    </div>
    <div>
      <router-link
        to="/admin/question-bank-requests"
        class="inline-flex items-center gap-2 rounded-xl bg-[#7C3AED] px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-purple-700 transition"
      >
        <span>Đi tới Duyệt vào Ngân hàng câu hỏi</span>
        <ArrowRight class="h-4 w-4" />
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { CheckSquare, ArrowRight } from 'lucide-vue-next'

const router = useRouter()

onMounted(() => {
  router.replace('/admin/question-bank-requests')
})
</script>
