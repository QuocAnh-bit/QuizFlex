<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const quiz = ref(null)
const loading = ref(false)
const averageScore = ref(0)

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

onMounted(() => {
  fetchQuizDetail()
})
</script>

<template>
  <section class="grid gap-6">
    <!-- Loading -->
    <div v-if="loading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">
      Đang tải chi tiết quiz...
    </div>

    <template v-if="quiz">
      <!-- Header -->
      <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
        <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Quiz Details</p>
            <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ quiz.title }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">{{ quiz.description || 'Không có mô tả cho bộ quiz này.' }}</p>
          </div>
          <div class="flex flex-wrap gap-3">
            <RouterLink to="/admin/quizzes" class="btn-ghost">
              ← Quay lại
            </RouterLink>
            <RouterLink :to="`/admin/quizzes/${quiz.id}/edit`" class="btn-primary">
              ✏️ Sửa Quiz
            </RouterLink>
          </div>
        </div>
      </div>

      <!-- Stats Grid & Info -->
      <div class="grid gap-6 md:grid-cols-3">
        <!-- Main Info -->
        <article class="md:col-span-2 rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl space-y-4">
          <h2 class="text-xl font-black text-[var(--text)] mb-4">Thông tin cơ bản</h2>
          <div class="grid gap-4 sm:grid-cols-2 text-sm text-[var(--text)]">
            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
              <span class="block text-xs font-bold text-[var(--muted)]">Người tạo</span>
              <span class="text-base font-black">{{ quiz.user?.name || quiz.author || 'Chưa có' }}</span>
            </div>
            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
              <span class="block text-xs font-bold text-[var(--muted)]">Danh mục</span>
              <span class="text-base font-black">{{ quiz.category || 'Chưa có' }}</span>
            </div>
            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
              <span class="block text-xs font-bold text-[var(--muted)]">Độ khó</span>
              <span class="text-base font-black uppercase">{{ quiz.difficulty }}</span>
            </div>
            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
              <span class="block text-xs font-bold text-[var(--muted)]">Trạng thái</span>
              <span
                class="text-base font-black"
                :class="quiz.is_public ? 'text-emerald-400' : 'text-rose-400'"
              >
                {{ quiz.is_public ? '🌐 Public' : '🔒 Private' }}
              </span>
            </div>
          </div>
        </article>

        <!-- Stats -->
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl flex flex-col justify-between">
          <div>
            <h2 class="text-xl font-black text-[var(--text)] mb-4">Thống kê chung</h2>
            <div class="grid grid-cols-2 gap-4">
              <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-center">
                <b class="block text-2xl text-[var(--text)]">{{ quiz.questions_count }}</b>
                <span class="text-xs font-bold text-[var(--muted)]">Câu hỏi</span>
              </div>
              <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-center">
                <b class="block text-2xl text-[var(--text)]">{{ quiz.attempts_count }}</b>
                <span class="text-xs font-bold text-[var(--muted)]">Lượt làm</span>
              </div>
            </div>
          </div>
          <div class="mt-6 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 text-center">
            <span class="block text-xs font-bold text-[var(--muted)]">Điểm số trung bình</span>
            <b class="text-3xl text-[var(--primary)]">{{ averageScore }}%</b>
          </div>
        </article>
      </div>

      <!-- Questions List -->
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl space-y-6">
        <h2 class="text-xl font-black text-[var(--text)]">Danh sách câu hỏi</h2>
        <div class="grid gap-4">
          <div
            v-for="(question, index) in quiz.questions"
            :key="question.id"
            class="rounded-3xl border border-[var(--border)] p-5 bg-[var(--surface-soft)] transition hover:border-[var(--border-strong)]"
          >
            <div class="flex items-start justify-between gap-4 mb-4">
              <h3 class="text-base font-black text-[var(--text)]">
                Câu {{ index + 1 }}: {{ question.content }}
              </h3>
              <span class="rounded-full bg-[var(--surface)] border border-[var(--border)] px-3 py-1 text-xs font-bold text-[var(--muted)]">
                {{ question.points ?? 10 }} pts
              </span>
            </div>

            <div class="grid gap-2">
              <div
                v-for="answer in question.answers"
                :key="answer.id"
                class="flex items-center justify-between p-3 rounded-2xl border text-sm"
                :class="answer.is_correct ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400 font-bold' : 'bg-[var(--surface)] border-[var(--border)] text-[var(--muted)]'"
              >
                <span>{{ answer.content }}</span>
                <span v-if="answer.is_correct" class="text-xs uppercase tracking-wider font-black">
                  ✓ Đáp án đúng
                </span>
              </div>
            </div>
          </div>
        </div>
      </article>

      <!-- Attempt History -->
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl space-y-6">
        <h2 class="text-xl font-black text-[var(--text)]">Lịch sử làm bài</h2>
        <div class="overflow-x-auto rounded-2xl border border-[var(--border)]">
          <table class="w-full border-collapse text-left text-sm text-[var(--text)]">
            <thead>
              <tr class="border-b border-[var(--border)] bg-[var(--surface-soft)] text-xs font-black uppercase tracking-wider text-[var(--muted)]">
                <th class="p-4">Người dùng</th>
                <th class="p-4 text-center">Điểm</th>
                <th class="p-4 text-center">Trạng thái</th>
                <th class="p-4 text-center">Thời gian</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--border)]">
              <tr v-for="attempt in quiz.attempts" :key="attempt.id" class="transition hover:bg-[var(--surface-soft)]">
                <td class="p-4 font-bold">{{ attempt.user?.name || 'Guest' }}</td>
                <td class="p-4 text-center font-bold text-[var(--primary)]">{{ attempt.score }}%</td>
                <td class="p-4 text-center">
                  <span
                    class="rounded-full px-3 py-1 text-xs font-black"
                    :class="attempt.status === 'completed' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400'"
                  >
                    {{ attempt.status === 'completed' ? 'Hoàn thành' : 'Đang làm' }}
                  </span>
                </td>
                <td class="p-4 text-center text-[var(--muted)]">{{ new Date(attempt.created_at).toLocaleString('vi-VN') }}</td>
              </tr>
              <tr v-if="!quiz.attempts?.length">
                <td colspan="4" class="text-center py-10 text-[var(--muted)] font-bold">Chưa có lịch sử làm bài</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </template>
  </section>
</template>
