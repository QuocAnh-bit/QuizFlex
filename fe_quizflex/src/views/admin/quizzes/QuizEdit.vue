<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const saving = ref(false)

const toast = ref({
  isOpen: false,
  message: '',
  type: 'success'
})

const showToast = (message, type = 'success') => {
  toast.value.message = message
  toast.value.type = type
  toast.value.isOpen = true
  setTimeout(() => {
    toast.value.isOpen = false
  }, 3000)
}

const form = ref({
  title: '',
  description: '',
  category: '',
  difficulty: 'medium',
  visibility: 'public',
})

const fetchQuiz = async () => {
  try {
    loading.value = true
    const res = await api.get(`/admin/quizzes/${route.params.id}`)
    const quiz = res.data.data.quiz
    form.value = {
      title: quiz.title || '',
      description: quiz.description || '',
      category: quiz.category || '',
      difficulty: quiz.difficulty || 'medium',
      visibility: quiz.is_public ? 'public' : 'private',
    }
  } catch (error) {
    console.error(error)
    showToast('Không tải được thông tin quiz', 'error')
  } finally {
    loading.value = false
  }
}

const saveQuiz = async () => {
  try {
    saving.value = true
    await api.put(`/quizzes/${route.params.id}`, {
      title: form.value.title,
      description: form.value.description,
      category: form.value.category,
      difficulty: form.value.difficulty,
      visibility: form.value.visibility,
    })
    showToast('Cập nhật quiz thành công', 'success')
    setTimeout(() => {
      router.push('/admin/quizzes')
    }, 1500)
  } catch (error) {
    console.error(error)
    showToast('Cập nhật thất bại', 'error')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchQuiz()
})
</script>

<template>
  <section class="grid gap-6">
    <!-- Loading -->
    <div v-if="loading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">
      Đang tải thông tin quiz...
    </div>

    <div v-else class="max-w-3xl">
      <!-- Header -->
      <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl mb-6">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-amber-500/10 blur-3xl"></div>
        <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-400">Edit Quiz Metadata</p>
            <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Sửa Quiz</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Cập nhật thông tin tiêu đề, mô tả, danh mục, độ khó hoặc chế độ hiển thị của bộ quiz này.</p>
          </div>
          <div class="flex flex-wrap gap-3">
            <RouterLink to="/admin/quizzes" class="btn-ghost">
              ← Quay lại
            </RouterLink>
          </div>
        </div>
      </div>

      <!-- Form Card -->
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl space-y-5">
        <!-- Tên quiz -->
        <div>
          <label class="block text-sm font-bold mb-2 text-[var(--text)]">Tên quiz</label>
          <div class="flex items-center gap-3 rounded-2xl border border-[var(--border)] bg-[var(--input-bg)] px-4 py-3 transition focus-within:border-[var(--border-strong)]">
            <input
              v-model="form.title"
              type="text"
              class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]"
              placeholder="Nhập tên quiz..."
            />
          </div>
        </div>

        <!-- Mô tả -->
        <div>
          <label class="block text-sm font-bold mb-2 text-[var(--text)]">Mô tả</label>
          <div class="flex items-center gap-3 rounded-2xl border border-[var(--border)] bg-[var(--input-bg)] px-4 py-3 transition focus-within:border-[var(--border-strong)]">
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)] resize-none"
              placeholder="Nhập mô tả cho bộ quiz..."
            />
          </div>
        </div>

        <!-- Danh mục -->
        <div>
          <label class="block text-sm font-bold mb-2 text-[var(--text)]">Danh mục</label>
          <div class="flex items-center gap-3 rounded-2xl border border-[var(--border)] bg-[var(--input-bg)] px-4 py-3 transition focus-within:border-[var(--border-strong)]">
            <input
              v-model="form.category"
              type="text"
              class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]"
              placeholder="Nhập danh mục..."
            />
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <!-- Độ khó -->
          <div>
            <label class="block text-sm font-bold mb-2 text-[var(--text)]">Độ khó</label>
            <select v-model="form.difficulty" class="field w-full">
              <option value="easy">Dễ</option>
              <option value="medium">Vừa</option>
              <option value="hard">Khó</option>
            </select>
          </div>

          <!-- Trạng thái -->
          <div>
            <label class="block text-sm font-bold mb-2 text-[var(--text)]">Chế độ hiển thị</label>
            <select v-model="form.visibility" class="field w-full">
              <option value="public">🌐 Public</option>
              <option value="private">🔒 Private</option>
              <option value="group">👥 Group</option>
            </select>
          </div>
        </div>

        <!-- Buttons -->
        <div class="flex gap-3 pt-4 border-t border-[var(--border)]">
          <button
            @click="saveQuiz"
            :disabled="saving"
            class="btn-primary"
          >
            {{ saving ? 'Đang lưu...' : '💾 Lưu thay đổi' }}
          </button>
          <RouterLink
            to="/admin/quizzes"
            class="btn-ghost"
          >
            Hủy
          </RouterLink>
        </div>
      </article>
    </div>

    <!-- Custom Toast Message -->
    <transition name="slide-up">
      <div v-if="toast.isOpen" class="fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-2xl border px-4 py-3 shadow-lg backdrop-blur-xl transition-all"
           :class="toast.type === 'success' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400 shadow-[0_12px_40px_rgba(16,185,129,0.1)]' : 'border-rose-500/30 bg-rose-500/10 text-rose-400 shadow-[0_12px_40px_rgba(244,63,94,0.1)]'">
        <span class="text-base">{{ toast.type === 'success' ? '✅' : '❌' }}</span>
        <span class="text-sm font-bold">{{ toast.message }}</span>
      </div>
    </transition>
  </section>
</template>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}
</style>