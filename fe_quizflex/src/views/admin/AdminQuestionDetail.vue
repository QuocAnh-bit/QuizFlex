<template>
  <section class="space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-slate-500">
      <router-link to="/admin" class="hover:text-[#7C3AED] transition-colors">
        Dashboard
      </router-link>
      <ChevronRight class="h-4 w-4" />
      <router-link to="/admin/question-bank" class="hover:text-[#7C3AED] transition-colors">
        Ngân hàng câu hỏi
      </router-link>
      <ChevronRight class="h-4 w-4" />
      <span class="font-medium text-slate-900">Chi tiết #{{ questionId }}</span>
    </div>

    <!-- Header -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex items-start gap-4">
          <button
            type="button"
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-[#7C3AED] hover:text-white"
            title="Quay lại"
            @click="goBack"
          >
            <ArrowLeft class="h-5 w-5" />
          </button>

          <div>
            <div class="flex flex-wrap items-center gap-3">
              <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Chi tiết câu hỏi #{{ questionId }}
              </h1>
              <span
                class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium"
                :class="Boolean(question.is_public)
                  ? 'bg-emerald-50 text-emerald-700'
                  : 'bg-amber-50 text-amber-700'"
              >
                <component :is="Boolean(question.is_public) ? Globe : Lock" class="h-3.5 w-3.5" />
                {{ Boolean(question.is_public) ? 'Công khai' : 'Riêng tư' }}
              </span>
            </div>
            <p class="mt-1 text-sm text-slate-500">
              Xem nội dung, đáp án, phân loại, tác giả và các quiz đang sử dụng câu hỏi này.
            </p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-medium transition cursor-pointer"
            :class="Boolean(question.is_public)
              ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100'
              : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'"
            @click="toggleVisibility"
          >
            <component :is="Boolean(question.is_public) ? Lock : Globe" class="h-4 w-4" />
            {{ Boolean(question.is_public) ? 'Gỡ công khai' : 'Công khai' }}
          </button>

          <button
            v-if="question.bank_submission_status === 'approved' && Boolean(question.origin_question_id) && Boolean(question.is_public)"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-medium text-rose-700 transition hover:bg-rose-100"
            @click="deleteQuestion"
          >
            <Trash2 class="h-4 w-4" />
            Xóa
          </button>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div
      v-if="isLoading"
      class="rounded-2xl border border-slate-200 bg-white py-16 text-center shadow-sm"
    >
      <div class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-4 border-[#7C3AED] border-t-transparent"></div>
      <p class="text-sm font-medium text-slate-500">Đang tải chi tiết câu hỏi #{{ questionId }}...</p>
    </div>

    <!-- Error -->
    <div
      v-else-if="errorMessage"
      class="rounded-2xl border border-rose-200 bg-rose-50 p-6"
    >
      <div class="flex items-start gap-3">
        <AlertTriangle class="h-5 w-5 shrink-0 text-rose-600" />
        <div>
          <p class="text-sm font-medium text-rose-800">{{ errorMessage }}</p>
          <button
            type="button"
            class="mt-3 text-sm font-medium text-rose-700 hover:underline"
            @click="$router.push('/admin/question-bank')"
          >
            ← Quay lại danh sách câu hỏi
          </button>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <div v-else class="grid gap-6 lg:grid-cols-12">
      <!-- LEFT COLUMN -->
      <div class="space-y-6 lg:col-span-8">
        <!-- Question Content -->
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
          <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-4">
            <span class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-[#7C3AED]">
              <FileText class="h-4 w-4" />
              Nội dung câu hỏi
            </span>
            <span class="text-xs font-medium text-slate-500">ID: #{{ question.id }}</span>
          </div>

          <div class="text-lg font-semibold leading-relaxed text-slate-900 whitespace-pre-line sm:text-xl">
            {{ question.content }}
          </div>

          <div
            v-if="question.image_url"
            class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-slate-50"
          >
            <img
              :src="question.image_url"
              alt="Question Image"
              class="max-h-80 w-full object-contain p-3"
            />
          </div>
        </article>

        <!-- Answers -->
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
          <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-4">
            <span class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-[#7C3AED]">
              <ListChecks class="h-4 w-4" />
              Phương án đáp án
            </span>
            <span class="text-xs font-medium text-slate-500">
              {{ (question.answers || []).length }} lựa chọn
            </span>
          </div>

          <div class="space-y-3">
            <div
              v-for="ans in question.answers"
              :key="ans.id"
              class="flex items-start gap-4 rounded-xl border p-4 transition"
              :class="ans.is_correct
                ? 'border-emerald-200 bg-emerald-50'
                : 'border-slate-200 bg-slate-50'"
            >
              <span
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
                :class="ans.is_correct
                  ? 'bg-emerald-600 text-white'
                  : 'border border-slate-200 bg-white text-slate-700'"
              >
                {{ ans.key || ans.answer_key }}
              </span>

              <div
                class="min-w-0 flex-1 pt-1 text-sm leading-relaxed sm:text-base"
                :class="ans.is_correct ? 'font-medium text-emerald-900' : 'text-slate-700'"
              >
                {{ ans.content }}
              </div>

              <div
                v-if="ans.is_correct"
                class="shrink-0 rounded-md bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700"
              >
                Đáp án đúng
              </div>
            </div>
          </div>
        </article>

        <!-- Quizzes using this question -->
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
          <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-4">
            <span class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-[#7C3AED]">
              <BookOpen class="h-4 w-4" />
              Quiz đang sử dụng câu hỏi này
            </span>
            <span class="text-xs font-medium text-slate-500">
              {{ (question.using_quizzes || []).length }} bài
            </span>
          </div>

          <div v-if="(question.using_quizzes || []).length > 0" class="grid gap-3 sm:grid-cols-2">
            <div
              v-for="quizItem in question.using_quizzes"
              :key="quizItem.id"
              class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-slate-300"
            >
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-slate-900" :title="quizItem.title">
                  {{ quizItem.title }}
                </p>
                <p class="mt-0.5 text-xs text-slate-500">
                  ID: #{{ quizItem.id }} ·
                  {{ quizItem.is_public ? 'Công khai' : 'Riêng tư' }}
                </p>
              </div>
              <router-link
                :to="`/admin/quizzes/${quizItem.id}`"
                class="shrink-0 text-sm font-medium text-[#7C3AED] hover:underline"
              >
                Xem →
              </router-link>
            </div>
          </div>

          <div v-else class="py-8 text-center text-sm text-slate-500">
            Câu hỏi này chưa được gắn vào bài quiz nào (thuộc ngân hàng chung).
          </div>
        </article>

        <!-- Reports (if any) -->
        <article
          v-if="(question.reports || []).length > 0"
          class="rounded-2xl border border-rose-200 bg-rose-50 p-6 shadow-sm sm:p-8"
        >
          <div class="mb-4 flex items-center justify-between border-b border-rose-100 pb-4">
            <span class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-rose-700">
              <AlertTriangle class="h-4 w-4" />
              Ticket báo cáo
            </span>
            <span class="text-xs font-medium text-rose-600">
              {{ question.reports.length }} lượt
            </span>
          </div>

          <div class="space-y-3">
            <div
              v-for="rep in question.reports"
              :key="rep.id"
              class="rounded-xl border border-rose-100 bg-white p-4 space-y-2"
            >
              <div class="flex items-center justify-between text-xs">
                <span class="font-medium text-rose-700">
                  Người báo cáo: {{ rep.reporter_name }}
                </span>
                <span class="text-slate-500">{{ formatDate(rep.created_at) }}</span>
              </div>
              <p class="text-sm text-slate-800">
                Lý do:
                <span class="font-medium text-amber-700">
                  {{ rep.reason || 'Báo cáo sai sót câu hỏi' }}
                </span>
              </p>
              <p v-if="rep.description" class="text-sm italic text-slate-600">
                “{{ rep.description }}”
              </p>
            </div>
          </div>
        </article>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="space-y-6 lg:col-span-4">
        <!-- Classification -->
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
          <h3 class="flex items-center gap-2 border-b border-slate-100 pb-3 text-xs font-semibold uppercase tracking-wider text-[#7C3AED]">
            <Settings class="h-4 w-4" />
            Phân loại & Cấu hình
          </h3>

          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500">Độ khó</span>
            <span
              class="rounded-md px-2.5 py-1 text-xs font-medium"
              :class="{
                'bg-emerald-50 text-emerald-700': question.difficulty === 'easy',
                'bg-amber-50 text-amber-700': question.difficulty === 'medium',
                'bg-rose-50 text-rose-700': question.difficulty === 'hard'
              }"
            >
              {{ question.difficulty === 'easy' ? 'Dễ' : question.difficulty === 'hard' ? 'Khó' : 'Trung bình' }}
            </span>
          </div>

          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500">Điểm số</span>
            <span class="font-semibold text-slate-900">{{ question.points || 10 }} điểm</span>
          </div>

          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500">Môn học</span>
            <span class="font-medium text-slate-900">
              {{ question.subject_name || question.subject?.name || 'Chưa phân loại' }}
            </span>
          </div>

          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500">Khối lớp</span>
            <span class="font-medium text-slate-900">
              {{ question.grade_name || question.grade?.name || 'Chưa phân loại' }}
            </span>
          </div>

          <div v-if="question.topic_name" class="flex items-center justify-between text-sm">
            <span class="text-slate-500">Chủ đề</span>
            <span class="font-medium text-[#7C3AED]">{{ question.topic_name }}</span>
          </div>
        </article>

        <!-- Author & timestamps -->
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
          <h3 class="flex items-center gap-2 border-b border-slate-100 pb-3 text-xs font-semibold uppercase tracking-wider text-[#7C3AED]">
            <User class="h-4 w-4" />
            Tác giả & Thời gian
          </h3>

          <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#7C3AED] text-base font-bold text-white">
              {{ (question.author?.name || 'U').charAt(0).toUpperCase() }}
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold text-slate-900">
                {{ question.author?.name || 'Vô danh' }}
              </p>
              <p class="truncate text-xs text-slate-500">
                {{ question.author?.email || 'N/A' }}
              </p>
            </div>
          </div>

          <div class="space-y-2 border-t border-slate-100 pt-3 text-sm">
            <div class="flex justify-between">
              <span class="text-slate-500">Ngày tạo</span>
              <span class="font-medium text-slate-900">{{ formatDate(question.created_at) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Cập nhật gần nhất</span>
              <span class="font-medium text-slate-900">{{ formatDate(question.updated_at) }}</span>
            </div>
          </div>
        </article>

        <!-- Quick actions -->
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-3">
          <button
            type="button"
            class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 cursor-pointer"
            @click="goBack"
          >
            <ArrowLeft class="h-4 w-4" />
            Quay lại
          </button>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ChevronRight,
  ArrowLeft,
  Pencil,
  Globe,
  Lock,
  Trash2,
  AlertTriangle,
  FileText,
  ListChecks,
  BookOpen,
  Settings,
  User
} from 'lucide-vue-next'
import { adminQuestionsApi } from '@/services/api'

const route = useRoute()
const router = useRouter()
const showToast = inject('showToast')
const showConfirm = inject('showConfirm')

const questionId = route.params.id
const question = ref({})
const isLoading = ref(true)
const errorMessage = ref('')

const goBack = () => {
  if (route.query.from === 'reports') {
    router.push('/admin/report-tickets')
  } else if (window.history.length > 1) {
    router.back()
  } else {
    router.push('/admin/question-bank')
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const fetchQuestionDetail = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const data = await adminQuestionsApi.get(questionId)
    question.value = data || {}
  } catch (err) {
    errorMessage.value = `Không thể tải câu hỏi #${questionId}: ${err.message}`
  } finally {
    isLoading.value = false
  }
}

const toggleVisibility = async () => {
  try {
    const updated = await adminQuestionsApi.toggleVisibility(questionId)
    const isPub = Boolean(updated?.data?.is_public ?? updated?.is_public)
    question.value.is_public = isPub
    if (showToast) {
      showToast(
        isPub ? 'Đã công khai câu hỏi thành công.' : 'Đã gỡ công khai câu hỏi.',
        'success'
      )
    }
  } catch (err) {
    if (showToast) showToast(`Cập nhật thất bại: ${err.message}`, 'error')
  }
}

const deleteQuestion = async () => {
  const msg = `Bạn có chắc muốn chuyển câu hỏi #${questionId} vào thùng rác?`
  const executeAction = async () => {
    try {
      await adminQuestionsApi.remove(questionId)
      if (showToast) showToast('Đã chuyển câu hỏi vào thùng rác.', 'success')
      router.push('/admin/question-bank')
    } catch (err) {
      if (showToast) showToast(`Xóa thất bại: ${err.message}`, 'error')
    }
  }

  if (showConfirm) {
    showConfirm('Xóa câu hỏi', msg, executeAction)
  } else if (confirm(msg)) {
    executeAction()
  }
}

onMounted(() => {
  fetchQuestionDetail()
})
</script>