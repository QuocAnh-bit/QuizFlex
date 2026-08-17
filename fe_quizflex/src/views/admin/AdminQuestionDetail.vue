<template>
  <section class="grid gap-6">
    <!-- Top Breadcrumb & Actions Bar -->
    <div class="space-y-4">
      <div class="flex items-center gap-2 text-xs font-bold text-[var(--muted)]">
        <router-link to="/admin" class="hover:text-[var(--primary)] transition">Dashboard</router-link>
        <span>&rsaquo;</span>
        <router-link to="/admin/question-bank" class="hover:text-[var(--primary)] transition">Question Bank</router-link>
        <span>&rsaquo;</span>
        <span class="text-[var(--text)]">Chi tiết câu hỏi #{{ questionId }}</span>
      </div>

      <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
        <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-center">
          <div class="flex items-center gap-4">
            <button
              type="button"
              class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] text-lg text-[var(--text)] transition hover:bg-[var(--primary)] hover:text-white"
              title="Quay lại"
              @click="goBack"
            >
              ←
            </button>
            <div>
              <div class="flex items-center gap-3">
                <h1 class="text-3xl font-black tracking-[-0.05em] text-[var(--text)]">
                  Chi tiết câu hỏi #{{ questionId }}
                </h1>
                <span
                  class="rounded-full border px-3 py-1 text-xs font-black"
                  :class="Boolean(question.is_public) ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400' : 'border-amber-500/30 bg-amber-500/10 text-amber-400'"
                >
                  {{ Boolean(question.is_public) ? '🌐 Public (Công khai)' : '🔒 Private (Riêng tư)' }}
                </span>
              </div>
              <p class="mt-1 text-xs sm:text-sm text-[var(--muted)]">
                Xem toàn bộ nội dung, phương án đáp án, phân loại danh mục, tác giả và danh sách Quiz đang sử dụng câu hỏi này.
              </p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-wrap items-center gap-2">
            <router-link
              :to="`/admin/questions/${questionId}/edit`"
              class="btn-primary flex items-center gap-2 px-5 py-2.5 text-xs shadow-lg"
            >
              <span>✏️</span>
              <span>Chỉnh sửa</span>
            </router-link>

            <button
              type="button"
              class="rounded-full border px-4 py-2 text-xs font-black transition hover:-translate-y-0.5"
              :class="Boolean(question.is_public) ? 'border-amber-500/30 bg-amber-500/10 text-amber-300 hover:bg-amber-500/20' : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20'"
              @click="toggleVisibility"
            >
              {{ Boolean(question.is_public) ? '🔒 Gỡ công khai' : '🌐 Công khai' }}
            </button>

            <button
              type="button"
              class="rounded-full border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-xs font-black text-rose-400 transition hover:bg-rose-500 hover:text-white"
              @click="deleteQuestion"
            >
              🗑️ Xóa
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-12 text-center text-sm font-bold text-[var(--muted)] shadow-[var(--shadow-card)]">
      <div class="mx-auto mb-3 h-10 w-10 animate-spin rounded-full border-4 border-[var(--primary)] border-t-transparent"></div>
      Đang tải chi tiết câu hỏi #{{ questionId }}...
    </div>

    <!-- Error State -->
    <div v-else-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-6 text-sm font-bold text-rose-300">
      ⚠️ {{ errorMessage }}
      <div class="mt-4">
        <button type="button" class="btn-ghost text-xs" @click="$router.push('/admin/question-bank')">
          ← Quay lại danh sách câu hỏi
        </button>
      </div>
    </div>

    <!-- Main 2-Column Content Layout -->
    <div v-else class="grid gap-6 lg:grid-cols-12">
      <!-- LEFT COLUMN (65% width - Reading & Moderation Focus) -->
      <div class="space-y-6 lg:col-span-8">
        <!-- 1. Question Content Card -->
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 sm:p-8 shadow-[var(--shadow-card)] backdrop-blur-2xl">
          <div class="flex items-center justify-between border-b border-[var(--border)] pb-4 mb-4">
            <span class="text-xs font-black uppercase tracking-wider text-[var(--primary)]">📝 Nội dung câu hỏi</span>
            <span class="text-xs font-bold text-[var(--muted)]">Mã ID: #{{ question.id }}</span>
          </div>

          <div class="text-lg sm:text-xl font-extrabold text-[var(--text)] leading-relaxed whitespace-pre-line">
            {{ question.content }}
          </div>

          <!-- Optional Image Attachment -->
          <div v-if="question.image_url" class="mt-5 overflow-hidden rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)]">
            <img :src="question.image_url" alt="Question Image" class="max-h-80 w-full object-contain p-2" />
          </div>
        </article>

        <!-- 2. Answers Breakdown Card -->
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 sm:p-8 shadow-[var(--shadow-card)] backdrop-blur-2xl">
          <div class="flex items-center justify-between border-b border-[var(--border)] pb-4 mb-4">
            <span class="text-xs font-black uppercase tracking-wider text-[var(--primary)]">🎯 Danh sách phương án đáp án</span>
            <span class="text-xs font-bold text-[var(--muted)]">Tổng số: {{ (question.answers || []).length }} lựa chọn</span>
          </div>

          <div class="space-y-3">
            <div
              v-for="ans in question.answers"
              :key="ans.id"
              class="flex items-start gap-4 rounded-2xl border p-4 transition"
              :class="ans.is_correct ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300 font-bold shadow-lg shadow-emerald-500/5' : 'border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)]'"
            >
              <span
                class="grid h-8 w-8 shrink-0 place-items-center rounded-xl font-black text-xs"
                :class="ans.is_correct ? 'bg-emerald-500 text-slate-950' : 'bg-[var(--surface)] text-[var(--text)] border border-[var(--border)]'"
              >
                {{ ans.key || ans.answer_key }}
              </span>

              <div class="flex-1 min-w-0 pt-1 text-sm sm:text-base leading-relaxed" :class="{ 'text-[var(--text)]': !ans.is_correct }">
                {{ ans.content }}
              </div>

              <div v-if="ans.is_correct" class="shrink-0 rounded-full bg-emerald-500/20 border border-emerald-500/40 px-3 py-1 text-xs font-black text-emerald-400">
                ✓ ĐÁP ÁN ĐÚNG
              </div>
            </div>
          </div>
        </article>

        <!-- 3. Quizzes Using This Question Card -->
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 sm:p-8 shadow-[var(--shadow-card)] backdrop-blur-2xl">
          <div class="flex items-center justify-between border-b border-[var(--border)] pb-4 mb-4">
            <span class="text-xs font-black uppercase tracking-wider text-[var(--primary)]">📖 Bài Quiz đang sử dụng câu hỏi này</span>
            <span class="text-xs font-bold text-[var(--muted)]">{{ (question.using_quizzes || []).length }} bài Quiz</span>
          </div>

          <div v-if="(question.using_quizzes || []).length > 0" class="grid gap-3 sm:grid-cols-2">
            <div
              v-for="quizItem in question.using_quizzes"
              :key="quizItem.id"
              class="flex items-center justify-between gap-3 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 transition hover:border-[var(--border-strong)]"
            >
              <div class="min-w-0 flex-1">
                <p class="font-bold text-sm text-[var(--text)] truncate" :title="quizItem.title">
                  {{ quizItem.title }}
                </p>
                <span class="mt-1 inline-block text-[11px] text-[var(--muted)]">
                  ID: #{{ quizItem.id }} • {{ quizItem.is_public ? '🌐 Public' : '🔒 Private' }}
                </span>
              </div>
              <router-link
                :to="`/admin/quizzes/${quizItem.id}`"
                class="btn-ghost !px-3 !py-1 text-xs font-bold text-[var(--primary)] shrink-0"
              >
                Xem Quiz &rsaquo;
              </router-link>
            </div>
          </div>

          <div v-else class="text-center py-6 text-xs font-bold text-[var(--muted)]">
            Câu hỏi này chưa gắn trực tiếp với bài Quiz cố định nào (Thuộc Ngân hàng câu hỏi chung).
          </div>
        </article>

        <!-- 4. Report Tickets Card (If reported) -->
        <article v-if="(question.reports || []).length > 0" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/5 p-6 sm:p-8 shadow-xl backdrop-blur-2xl">
          <div class="flex items-center justify-between border-b border-rose-500/20 pb-4 mb-4">
            <span class="text-xs font-black uppercase tracking-wider text-rose-400">⚠️ Ticket Báo cáo vi phạm / Lỗi câu hỏi</span>
            <span class="text-xs font-bold text-rose-300">{{ question.reports.length }} lượt báo cáo</span>
          </div>

          <div class="space-y-3">
            <div
              v-for="rep in question.reports"
              :key="rep.id"
              class="rounded-2xl border border-rose-500/20 bg-slate-950/40 p-4 space-y-2"
            >
              <div class="flex items-center justify-between text-xs font-bold">
                <span class="text-rose-300">🚩 Người báo cáo: {{ rep.reporter_name }}</span>
                <span class="text-[var(--muted)]">{{ formatDate(rep.created_at) }}</span>
              </div>
              <p class="text-xs font-semibold text-[var(--text)]">Lý do: <span class="text-amber-300">{{ rep.reason || 'Báo cáo sai sót câu hỏi' }}</span></p>
              <p v-if="rep.description" class="text-xs text-[var(--muted)] italic">"{{ rep.description }}"</p>
            </div>
          </div>
        </article>
      </div>

      <!-- RIGHT COLUMN (35% width - Metadata & Author Focus) -->
      <div class="space-y-6 lg:col-span-4">
        <!-- 1. Metadata & Classification Card -->
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl space-y-4">
          <h3 class="text-xs font-black uppercase tracking-wider text-[var(--primary)] border-b border-[var(--border)] pb-3">
            ⚙️ Phân loại & Cấu hình
          </h3>

          <!-- Difficulty -->
          <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-[var(--muted)]">Độ khó:</span>
            <span
              class="rounded-full border px-3 py-1 font-bold"
              :class="question.difficulty === 'easy' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : question.difficulty === 'hard' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20'"
            >
              📊 {{ question.difficulty === 'easy' ? 'Dễ' : question.difficulty === 'hard' ? 'Khó' : 'Trung bình' }}
            </span>
          </div>

          <!-- Points -->
          <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-[var(--muted)]">Điểm số:</span>
            <span class="font-black text-[var(--text)]">⭐ {{ question.points || 10 }} điểm</span>
          </div>

          <!-- Subject -->
          <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-[var(--muted)]">Môn học:</span>
            <span class="font-bold text-emerald-400">📖 {{ question.subject_name || question.subject?.name || 'Chưa phân loại' }}</span>
          </div>

          <!-- Grade -->
          <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-[var(--muted)]">Khối lớp:</span>
            <span class="font-bold text-indigo-400">🎓 {{ question.grade_name || question.grade?.name || 'Chưa phân loại' }}</span>
          </div>

          <!-- Topic -->
          <div class="flex items-center justify-between text-xs" v-if="question.topic_name">
            <span class="font-bold text-[var(--muted)]">Chủ đề:</span>
            <span class="font-bold text-[var(--primary)]">🏷️ {{ question.topic_name }}</span>
          </div>
        </article>

        <!-- 2. Author & Timestamps Card -->
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl space-y-4">
          <h3 class="text-xs font-black uppercase tracking-wider text-[var(--primary)] border-b border-[var(--border)] pb-3">
            👤 Tác giả & Thời gian
          </h3>

          <!-- Author Info -->
          <div class="flex items-center gap-3">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-[var(--primary)] to-[var(--primary-2)] text-white font-black text-lg">
              {{ (question.author?.name || 'U').charAt(0).toUpperCase() }}
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-black text-[var(--text)] truncate">{{ question.author?.name || 'Vô danh' }}</p>
              <p class="text-xs text-[var(--muted)] truncate">{{ question.author?.email || 'N/A' }}</p>
            </div>
          </div>

          <div class="space-y-2 border-t border-[var(--border)] pt-3 text-xs font-bold text-[var(--muted)]">
            <div class="flex justify-between">
              <span>Ngày tạo:</span>
              <span class="text-[var(--text)]">{{ formatDate(question.created_at) }}</span>
            </div>
            <div class="flex justify-between">
              <span>Cập nhật gần nhất:</span>
              <span class="text-[var(--text)]">{{ formatDate(question.updated_at) }}</span>
            </div>
          </div>
        </article>

        <!-- 3. Navigation Controls Card -->
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl space-y-3">
          <router-link
            :to="`/admin/questions/${questionId}/edit`"
            class="btn-primary w-full justify-center text-xs font-bold"
          >
            ✏️ Chỉnh sửa câu hỏi này
          </router-link>

          <button
            type="button"
            class="btn-ghost w-full justify-center text-xs font-bold text-[var(--muted)]"
            @click="goBack"
          >
            ← Quay lại
          </button>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'
import { useRoute, useRouter } from 'vue-router'
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
  if (!dateStr) return '--'
  const d = new Date(dateStr)
  return d.toLocaleDateString('vi-VN', {
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
      showToast(isPub ? 'Đã công khai câu hỏi thành công.' : 'Đã gỡ công khai (Set Private) câu hỏi.', 'success')
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
