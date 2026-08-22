<template>
  <section class="max-w-6xl mx-auto py-4 space-y-6">
    <!-- Loading -->
    <div v-if="loading" class="card p-10 text-center text-xs text-slate-400">
      Đang tải chi tiết quiz...
    </div>

    <template v-if="quiz">
      <!-- Header -->
      <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Chi tiết đề thi</p>
          <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">{{ quiz.title }}</h1>
          <p class="mt-1 text-sm text-slate-600 max-w-2xl">{{ quiz.description || 'Không có mô tả cho bộ quiz này.' }}</p>
        </div>
        <div class="flex items-center gap-2.5">
          <RouterLink to="/admin/quizzes" class="btn-secondary text-xs px-3.5 py-1.5">
            ← Quay lại
          </RouterLink>
          <template v-if="quiz.review_status === 'pending_review'">
            <button
              type="button"
              class="btn-primary text-xs px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white flex items-center gap-1"
              :disabled="isProcessing"
              @click="handleApproveQuiz"
            >
              <Check :size="13" />
              <span>Duyệt công khai</span>
            </button>
            <button
              type="button"
              class="btn-secondary text-xs px-3.5 py-1.5 text-rose-700 hover:bg-rose-50 border-rose-200 flex items-center gap-1"
              :disabled="isProcessing"
              @click="openRejectModal"
            >
              <X :size="13" />
              <span>Từ chối</span>
            </button>
          </template>
        </div>
      </div>

      <!-- Review Status Banner -->
      <div v-if="quiz.review_status === 'pending_review'" class="flex items-start gap-3 rounded-2xl border border-amber-500/40 bg-amber-500/10 p-4 text-xs font-bold text-amber-900 shadow-sm">
        <Clock class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" />
        <div class="grid gap-0.5">
          <span class="text-amber-800">Bài Quiz này đang gửi yêu cầu kiểm duyệt để được Công khai</span>
          <span class="text-[11px] font-normal text-amber-700">Khi duyệt, toàn bộ câu hỏi từ kho cá nhân sẽ được tự động snapshot vào Ngân hàng câu hỏi.</span>
        </div>
      </div>

      <div v-else-if="quiz.review_status === 'rejected'" class="flex items-start gap-3 rounded-2xl border border-rose-500/40 bg-rose-500/10 p-4 text-xs font-bold text-rose-900 shadow-sm">
        <AlertCircle class="mt-0.5 h-4 w-4 shrink-0 text-rose-500" />
        <div class="grid gap-0.5">
          <span class="text-rose-800">Yêu cầu công khai đã bị từ chối: "{{ quiz.rejection_reason || 'Nội dung chưa đạt tiêu chuẩn' }}"</span>
        </div>
      </div>

      <!-- Stats Grid & Info -->
      <div class="grid gap-6 md:grid-cols-3">
        <!-- Main Info -->
        <article class="md:col-span-2 card p-6 space-y-4">
          <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Thông tin cấu hình</h2>
          <div class="grid gap-3 sm:grid-cols-2 text-xs">
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
              <span class="block text-[10px] font-bold uppercase text-slate-400">Người tạo</span>
              <span class="text-slate-900 font-bold block mt-0.5">{{ quiz.user?.name || quiz.author || 'Chưa rõ' }}</span>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
              <span class="block text-[10px] font-bold uppercase text-slate-400">Chế độ tạo</span>
              <span class="text-purple-700 font-bold uppercase block mt-0.5">{{ quiz.creation_mode || 'manual' }}</span>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
              <span class="block text-[10px] font-bold uppercase text-slate-400">Trạng thái duyệt</span>
              <span class="font-bold uppercase block mt-0.5" :class="quiz.review_status === 'approved' ? 'text-emerald-700' : (quiz.review_status === 'pending_review' ? 'text-amber-600' : 'text-slate-700')">
                {{ quiz.review_status || 'draft' }}
              </span>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
              <span class="block text-[10px] font-bold uppercase text-slate-400">Hiển thị</span>
              <span
                class="mt-0.5 flex items-center gap-1.5 font-bold"
                :class="quiz.is_public ? 'text-emerald-700' : 'text-slate-600'"
              >
                <Globe v-if="quiz.is_public" class="h-3.5 w-3.5" />
                <Lock v-else class="h-3.5 w-3.5" />
                <span>{{ quiz.is_public ? 'Công khai (Public)' : 'Riêng tư (Private)' }}</span>
              </span>
            </div>
          </div>
        </article>

        <!-- Stats -->
        <article class="card p-6 flex flex-col justify-between space-y-4">
          <div>
            <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Hiệu suất quiz</h2>
            <div class="grid grid-cols-2 gap-3 mt-3">
              <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-center">
                <b class="block text-2xl font-black text-slate-900">{{ quiz.questions_count }}</b>
                <span class="text-[10px] font-bold uppercase text-slate-400">Câu hỏi</span>
              </div>
              <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-center">
                <b class="block text-2xl font-black text-slate-900">{{ quiz.attempts_count }}</b>
                <span class="text-[10px] font-bold uppercase text-slate-400">Lượt làm</span>
              </div>
            </div>
          </div>
          <div class="rounded-xl border border-purple-100 bg-purple-50 p-3.5 text-center">
            <span class="block text-[10px] font-bold uppercase text-[#7C3AED]">Điểm số trung bình</span>
            <b class="text-2xl font-black text-[#7C3AED]">{{ averageScore }}%</b>
          </div>
        </article>
      </div>

      <!-- Questions List -->
      <article class="card p-6 space-y-5">
        <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Danh sách câu hỏi ({{ quiz.questions?.length ?? 0 }})</h2>
        <div class="grid gap-3">
          <div
            v-for="(question, index) in quiz.questions"
            :key="question.id"
            class="rounded-xl border border-slate-100 bg-slate-50 p-4 space-y-3"
          >
            <div class="flex items-start justify-between gap-3">
              <h3 class="text-xs font-bold text-slate-900 leading-relaxed">
                Câu {{ index + 1 }}: {{ question.content }}
              </h3>
              <span class="rounded bg-white border border-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-500 shrink-0">
                {{ question.points ?? 10 }} pts
              </span>
            </div>

            <div class="grid gap-1.5 sm:grid-cols-2 text-xs">
              <div
                v-for="answer in question.answers"
                :key="answer.id"
                class="flex items-center justify-between p-2.5 rounded-lg border text-xs"
                :class="answer.is_correct ? 'bg-emerald-50 border-emerald-200 text-emerald-800 font-bold' : 'bg-white border-slate-200 text-slate-700'"
              >
                <span>{{ answer.content }}</span>
                <span v-if="answer.is_correct" class="text-[10px] uppercase font-bold text-emerald-700">
                  ✓ Đúng
                </span>
              </div>
            </div>
          </div>
        </div>
      </article>

      <!-- Attempt History -->
      <article class="card p-6 space-y-4">
        <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Lịch sử làm bài gần nhất</h2>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="border-b border-slate-100 text-slate-400 uppercase text-[10px] font-bold">
                <th class="py-2.5 px-3">Người dùng</th>
                <th class="py-2.5 px-3 text-center">Điểm</th>
                <th class="py-2.5 px-3 text-center">Trạng thái</th>
                <th class="py-2.5 px-3 text-right">Thời gian</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-for="attempt in quiz.attempts" :key="attempt.id" class="hover:bg-slate-50">
                <td class="py-2.5 px-3 font-bold text-slate-900">{{ attempt.user?.name || 'Khách' }}</td>
                <td class="py-2.5 px-3 text-center font-bold text-[#7C3AED]">{{ attempt.score }}%</td>
                <td class="py-2.5 px-3 text-center">
                  <span
                    class="rounded px-2 py-0.5 text-[10px] font-bold"
                    :class="attempt.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                  >
                    {{ attempt.status === 'completed' ? 'Hoàn thành' : 'Đang làm' }}
                  </span>
                </td>
                <td class="py-2.5 px-3 text-right text-slate-400 text-[11px]">{{ new Date(attempt.created_at).toLocaleString('vi-VN') }}</td>
              </tr>
              <tr v-if="!quiz.attempts?.length">
                <td colspan="4" class="py-6 text-center text-slate-400 text-xs">Chưa có ai làm bài thi này.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </template>

    <!-- REJECT REASON MODAL -->
    <div
      v-if="isRejectModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn"
    >
      <div class="card p-6 max-w-md w-full space-y-4 shadow-2xl animate-scaleUp">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-base font-black text-rose-600 flex items-center gap-2">
            <AlertCircle :size="18" />
            <span>Từ chối phê duyệt Quiz</span>
          </h3>
          <button
            type="button"
            class="text-slate-400 hover:text-slate-600 text-sm font-bold"
            @click="isRejectModalOpen = false"
          >
            ✕
          </button>
        </div>

        <p class="text-xs leading-relaxed text-slate-600">
          Vui lòng nhập lý do từ chối cụ thể để tác giả chỉnh sửa lại bài Quiz.
        </p>

        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-700 block">Lý do từ chối <span class="text-rose-500">*</span></label>
          <textarea
            v-model="rejectionReason"
            rows="4"
            class="field text-xs resize-none w-full"
            placeholder="Ví dụ: Câu số 2 chưa chọn đáp án đúng..."
            required
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <button
            type="button"
            class="btn-secondary text-xs px-4 py-2"
            :disabled="isProcessing"
            @click="isRejectModalOpen = false"
          >
            Hủy
          </button>
          <button
            type="button"
            class="btn-primary text-xs px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white flex items-center gap-1.5"
            :disabled="isProcessing || !rejectionReason.trim()"
            @click="handleRejectQuiz"
          >
            <span>{{ isProcessing ? 'Đang xử lý...' : 'Xác nhận từ chối' }}</span>
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Globe, Lock, Clock, AlertCircle, Check, X } from 'lucide-vue-next'
import api, { quizReviewApi } from '@/services/api'

const route = useRoute()
const router = useRouter()
const showToast = inject('showToast')
const showConfirm = inject('showConfirm')

const quiz = ref(null)
const loading = ref(false)
const isProcessing = ref(false)
const averageScore = ref(0)
const isRejectModalOpen = ref(false)
const rejectionReason = ref('')

const goBack = () => {
  if (route.query.from === 'reports') {
    router.push('/admin/report-tickets')
  } else if (window.history.length > 1) {
    router.back()
  } else {
    router.push('/admin/quizzes')
  }
}

const fetchQuizDetail = async () => {
  try {
    loading.value = true
    const res = await api.get(`/admin/quizzes/${route.params.id}`)
    quiz.value = res.data.data.quiz
    averageScore.value = res.data.data.average_score
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

const handleApproveQuiz = () => {
  const quizTitle = quiz.value?.title || ''
  const message = `Bạn có chắc chắn muốn phê duyệt và công khai bài Quiz "${quizTitle}"? Các câu hỏi từ kho cá nhân của người dùng sẽ được tạo bản sao vào Ngân hàng câu hỏi.`

  if (showConfirm) {
    showConfirm('Xác nhận phê duyệt Quiz', message, async () => {
      await executeApproveQuiz()
    })
  } else {
    executeApproveQuiz()
  }
}

const executeApproveQuiz = async () => {
  isProcessing.value = true
  try {
    const res = await quizReviewApi.adminApprove(quiz.value.id)
    if (showToast) showToast(res.message || 'Phê duyệt thành công!', 'success')
    await fetchQuizDetail()
  } catch (error) {
    const msg = error.response?.data?.message || error.message || 'Phê duyệt thất bại'
    if (showToast) showToast(msg, 'error')
  } finally {
    isProcessing.value = false
  }
}

const openRejectModal = () => {
  rejectionReason.value = ''
  isRejectModalOpen.value = true
}

const handleRejectQuiz = async () => {
  if (!rejectionReason.value.trim()) return

  isProcessing.value = true
  try {
    const res = await quizReviewApi.adminReject(quiz.value.id, rejectionReason.value.trim())
    if (showToast) showToast(res.message || 'Đã từ chối bài Quiz thành công!', 'success')
    isRejectModalOpen.value = false
    await fetchQuizDetail()
  } catch (error) {
    const msg = error.response?.data?.message || error.message || 'Từ chối thất bại'
    if (showToast) showToast(msg, 'error')
  } finally {
    isProcessing.value = false
  }
}

onMounted(() => {
  fetchQuizDetail()
})
</script>
