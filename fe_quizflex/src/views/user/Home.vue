<template>
  <div class="space-y-12 py-2">
    <!-- Hero Section -->
    <section class="grid items-center gap-8 lg:grid-cols-[1.15fr_0.85fr] pt-2 pb-6">
      <!-- Left Column: Hero Content -->
      <div class="space-y-5">
        <div v-if="currentUser" class="inline-flex items-center gap-2 rounded-full border border-purple-200 bg-[#F5F3FF] px-3.5 py-1 text-xs font-bold text-[#7C3AED]">
          <span>👋</span>
          <span>Chào mừng trở lại, {{ currentUser.name }}</span>
        </div>
        <div v-else class="inline-flex items-center gap-2 rounded-full border border-purple-200 bg-[#F5F3FF] px-3.5 py-1 text-xs font-bold text-[#7C3AED]">
          <span>✨</span>
          <span>Nền tảng học tập & trắc nghiệm thông minh</span>
        </div>

        <h1 class="text-4xl font-extrabold tracking-tight text-[#0F172A] sm:text-5xl lg:text-[54px] leading-[1.15]">
          Học tập thông minh hơn cùng <span class="text-[#7C3AED]">QuizFlex</span>
        </h1>
        
        <p class="max-w-xl text-base font-normal leading-relaxed text-[#64748B] sm:text-lg">
          Khám phá hàng nghìn bộ quiz chất lượng, tham gia phòng bài tập và thi đấu trực tiếp cùng bạn bè trong thời gian thực.
        </p>

        <div class="pt-2 flex flex-wrap items-center gap-3">
          <router-link
            to="/quizzes"
            class="inline-flex items-center justify-center rounded-lg bg-[#7C3AED] px-6 py-3 text-sm font-bold text-white transition hover:bg-[#6D28D9] active:scale-[0.98] shadow-sm"
          >
            Làm quiz ngay
          </router-link>
          <router-link
            to="/join-room"
            class="inline-flex items-center justify-center rounded-lg border border-[#7C3AED] bg-white px-6 py-3 text-sm font-bold text-[#7C3AED] transition hover:bg-[#F5F3FF] active:scale-[0.98]"
          >
            Tham gia phòng
          </router-link>
        </div>
      </div>

      <!-- Right Column: Continue Learning Card -->
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_4px_16px_rgba(15,23,42,0.06)] space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
          <div class="flex items-center gap-2">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#F5F3FF] text-[#7C3AED] text-xs font-bold">
              📖
            </span>
            <p class="text-xs font-bold uppercase tracking-wider text-[#64748B]">Tiếp tục học</p>
          </div>
          <span class="rounded-full bg-[#F5F3FF] px-2.5 py-0.5 text-xs font-bold text-[#7C3AED]">
            {{ featuredQuiz?.category || 'Tiếng Anh' }}
          </span>
        </div>

        <!-- Quiz Title & Meta -->
        <div>
          <h3 class="text-lg font-bold text-[#0F172A] line-clamp-1">
            {{ featuredQuiz?.title || 'Tiếng Anh THPT Quốc Gia' }}
          </h3>
          <div class="mt-2 flex items-center justify-between text-xs font-semibold text-[#64748B]">
            <span>{{ featuredQuiz?.questions || 40 }} câu hỏi · {{ featuredQuiz?.duration || '45 phút' }}</span>
            <span class="font-bold text-[#7C3AED]">{{ featuredQuiz?.avgScore ? Math.round(featuredQuiz.avgScore) : 68 }}% hoàn thành</span>
          </div>
        </div>

        <!-- Progress Bar -->
        <div class="h-2 w-full overflow-hidden rounded-full bg-[#E2E8F0]">
          <div
            class="h-full rounded-full bg-[#7C3AED] transition-all duration-500"
            :style="{ width: `${featuredQuiz?.avgScore ? Math.min(100, Math.round(featuredQuiz.avgScore)) : 68}%` }"
          ></div>
        </div>

        <!-- Footer Action -->
        <div class="flex items-center justify-between pt-1">
          <span class="text-xs text-[#64748B] font-medium">
            Độ khó: <b class="text-[#0F172A]">{{ featuredQuiz?.difficulty || 'Vừa' }}</b>
          </span>
          <router-link
            :to="firstQuizLink"
            class="inline-flex items-center justify-center rounded-lg bg-[#7C3AED] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#6D28D9] active:scale-[0.98]"
          >
            Tiếp tục làm →
          </router-link>
        </div>

        <!-- Mini Suggestion List -->
        <div v-if="previewQuizzes.length" class="pt-3 border-t border-slate-100 space-y-2">
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Gợi ý dành cho bạn</p>
          <div class="grid gap-1.5">
            <router-link
              v-for="quiz in previewQuizzes"
              :key="quiz.id"
              :to="`/quizzes/${quiz.id}`"
              class="flex items-center justify-between rounded-lg p-2 text-xs font-semibold text-slate-700 hover:bg-[#F8FAFC] hover:text-[#7C3AED] transition border border-slate-100"
            >
              <div class="flex items-center gap-2 truncate">
                <span class="text-xs">{{ quiz.icon || '📝' }}</span>
                <span class="truncate">{{ quiz.title }}</span>
              </div>
              <span class="shrink-0 text-slate-400 font-medium text-[11px]">{{ quiz.questions }} câu</span>
            </router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- 3 Feature Cards (Làm quiz / Phòng bài tập / Phòng thi đấu) -->
    <LandingQuickActions />

    <!-- Statistics Section -->
    <section class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-[0_4px_16px_rgba(15,23,42,0.06)]">
      <div class="grid grid-cols-2 gap-6 lg:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
        <!-- Stat 1 -->
        <div class="flex items-center gap-3.5 pt-4 sm:pt-0 sm:px-3">
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#F5F3FF] text-[#7C3AED] text-xl">
            📚
          </div>
          <div>
            <strong class="block text-2xl font-black text-[#0F172A] sm:text-3xl">{{ allQuizzes.length || '120+' }}</strong>
            <span class="text-xs font-semibold text-[#64748B]">Quiz có sẵn</span>
          </div>
        </div>

        <!-- Stat 2 -->
        <div class="flex items-center gap-3.5 pt-4 sm:pt-0 sm:px-3">
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#EFF6FF] text-[#2563EB] text-xl">
            🏫
          </div>
          <div>
            <strong class="block text-2xl font-black text-[#0F172A] sm:text-3xl">25</strong>
            <span class="text-xs font-semibold text-[#64748B]">Phòng đang hoạt động</span>
          </div>
        </div>

        <!-- Stat 3 -->
        <div class="flex items-center gap-3.5 pt-4 sm:pt-0 sm:px-3">
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#FFFBEB] text-[#F59E0B] text-xl">
            ⚡
          </div>
          <div>
            <strong class="block text-2xl font-black text-[#0F172A] sm:text-3xl">1.2K</strong>
            <span class="text-xs font-semibold text-[#64748B]">Lượt thi đấu</span>
          </div>
        </div>

        <!-- Stat 4 -->
        <div class="flex items-center gap-3.5 pt-4 sm:pt-0 sm:px-3">
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#F0FDF4] text-[#16A34A] text-xl">
            🎯
          </div>
          <div>
            <strong class="block text-2xl font-black text-[#0F172A] sm:text-3xl">
              {{ allQuizzes.reduce((sum, quiz) => sum + Number(quiz.attempts || 0), 0) || '15.6K' }}
            </strong>
            <span class="text-xs font-semibold text-[#64748B]">Lượt làm quiz</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Quiz Mới Nhất Section -->
    <section id="quiz-topics" class="space-y-6 pt-2">
      <!-- Section Header -->
      <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
          <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Khám phá đề thi</p>
          <h2 class="mt-1 text-2xl font-black text-[#0F172A] sm:text-3xl">Quiz mới nhất</h2>
          <p class="mt-1 text-sm text-[#64748B]">Khám phá các bộ đề vừa được cập nhật từ giáo viên và cộng đồng.</p>
        </div>
        <div class="flex items-center gap-3">
          <div class="relative w-full sm:w-64">
            <input
              v-model="searchKeyword"
              class="w-full rounded-lg border border-slate-200 bg-white pl-8 pr-3 py-1.5 text-xs text-[#0F172A] outline-none focus:border-[#7C3AED] focus:ring-1 focus:ring-purple-200 transition"
              placeholder="Tìm theo tên đề, tác giả..."
            />
            <span class="absolute left-2.5 top-2 text-xs text-slate-400">🔍</span>
          </div>
          <router-link
            to="/quizzes"
            class="text-xs font-bold text-[#7C3AED] hover:underline whitespace-nowrap"
          >
            Xem tất cả →
          </router-link>
        </div>
      </div>

      <!-- Category Filter Pills -->
      <div class="flex flex-wrap gap-2">
        <button
          v-for="category in categoryTabs"
          :key="category"
          type="button"
          class="rounded-lg border px-3 py-1.5 text-xs font-bold transition active:scale-95"
          :class="activeCategory === category
            ? 'border-[#7C3AED] bg-[#F5F3FF] text-[#7C3AED]'
            : 'border-slate-200 bg-white text-[#64748B] hover:bg-slate-50 hover:text-[#0F172A]'"
          @click="activeCategory = category"
        >
          {{ category }}
        </button>
      </div>

      <!-- 4 Quiz Cards Grid -->
      <div v-if="displayedQuizzes.length > 0" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <article
          v-for="quiz in displayedQuizzes"
          :key="quiz.id"
          class="group rounded-2xl border border-slate-200 bg-white shadow-[0_4px_16px_rgba(15,23,42,0.04)] overflow-hidden transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(15,23,42,0.08)] hover:border-purple-300 flex flex-col justify-between"
        >
          <router-link :to="`/quizzes/${quiz.id}`" class="block h-full flex flex-col justify-between">
            <!-- Thumbnail Cover Area -->
            <div class="relative h-28 overflow-hidden bg-[#F5F3FF]" :style="{ background: quiz.cover }">
              <div class="absolute left-2.5 top-2.5 rounded-md bg-white/90 px-2 py-0.5 text-[10px] font-bold text-slate-800 shadow-sm">
                {{ quiz.category || quiz.badge || 'Quiz' }}
              </div>
              <div class="absolute bottom-2.5 right-2.5 flex h-8 w-8 items-center justify-center rounded-lg bg-white shadow text-sm">
                {{ quiz.icon || '📝' }}
              </div>
            </div>

            <!-- Card Content -->
            <div class="p-4 space-y-2.5 flex-1 flex flex-col justify-between">
              <div>
                <h4 class="line-clamp-2 min-h-[38px] text-xs font-bold leading-snug text-[#0F172A] group-hover:text-[#7C3AED] transition-colors">
                  {{ quiz.title }}
                </h4>
                <div class="mt-1 flex items-center justify-between text-[11px] text-[#64748B]">
                  <span class="truncate max-w-[100px]">{{ quiz.author || 'QuizFlex' }}</span>
                  <span>⭐ <b class="text-slate-800">{{ quiz.rating || '5.0' }}</b></span>
                </div>
              </div>

              <!-- Card Bottom Details -->
              <div class="flex items-center justify-between pt-2.5 border-t border-slate-100 text-[11px] font-semibold text-[#64748B]">
                <span class="rounded px-1.5 py-0.5 text-[10px] font-bold" :class="difficultyClass(quiz.difficulty)">
                  {{ quiz.difficulty }}
                </span>
                <span>{{ quiz.questions }} câu hỏi</span>
              </div>
            </div>
          </router-link>
        </article>
      </div>

      <!-- Empty State -->
      <div v-else class="rounded-2xl border border-slate-200 bg-white p-12 text-center text-slate-500 space-y-2">
        <span class="text-3xl block">🔍</span>
        <p class="text-sm font-semibold text-slate-700">Không tìm thấy quiz nào phù hợp.</p>
        <p class="text-xs text-slate-400">Hãy thử đổi từ khóa tìm kiếm hoặc chọn danh mục khác.</p>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import LandingQuickActions from '@/components/Home/LandingQuickActions.vue'
import { currentUserStorage, getQuizWorkspaceBaseForRole, normalizeQuizCard, quizzesApi } from '@/services/api'

const currentUser = ref(currentUserStorage.get())
const searchKeyword = ref('')
const activeCategory = ref('Tất cả')
const rawQuizzes = ref([])
const questionBase = computed(() => getQuizWorkspaceBaseForRole(currentUser.value?.role))

const allQuizzes = computed(() => rawQuizzes.value)
const featuredQuiz = computed(() => allQuizzes.value[0] || null)
const previewQuizzes = computed(() => allQuizzes.value.slice(1, 4))

const firstQuizLink = computed(() => {
  const firstQuiz = allQuizzes.value[0]
  return firstQuiz ? `/quizzes/${firstQuiz.id}` : '/quizzes'
})

const categoryTabs = computed(() => ['Tất cả', ...new Set(rawQuizzes.value.map((q) => q.category).filter(Boolean))])

const displayedQuizzes = computed(() => {
  let list = rawQuizzes.value

  if (activeCategory.value !== 'Tất cả') {
    list = list.filter((q) => q.category === activeCategory.value)
  }

  if (searchKeyword.value.trim()) {
    const keyword = searchKeyword.value.trim().toLowerCase()
    list = list.filter((q) => [q.title, q.author, q.category, q.difficulty].join(' ').toLowerCase().includes(keyword))
  }

  return list
})

const loadQuizzes = async () => {
  try {
    const data = await quizzesApi.list({ visibility: 'public', per_page: 50 })
    rawQuizzes.value = data.map(normalizeQuizCard)
  } catch {
    rawQuizzes.value = []
  }
}

const syncCurrentUser = (event) => {
  currentUser.value = event?.detail ?? currentUserStorage.get()
}

const difficultyClass = (difficulty) => ({
  Dễ: 'bg-emerald-50 text-emerald-700',
  Vừa: 'bg-amber-50 text-amber-700',
  Khó: 'bg-red-50 text-red-700'
}[difficulty] || 'bg-purple-50 text-purple-700')

onMounted(() => {
  loadQuizzes()
  window.addEventListener('quizflex-user-updated', syncCurrentUser)
  window.addEventListener('storage', syncCurrentUser)
})

onBeforeUnmount(() => {
  window.removeEventListener('quizflex-user-updated', syncCurrentUser)
  window.removeEventListener('storage', syncCurrentUser)
})
</script>
