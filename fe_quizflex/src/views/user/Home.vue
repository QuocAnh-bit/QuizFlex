<template>
  <div class="py-2">
    <!-- Main 2-Column Dashboard Grid: Left Main Column (Hero, Feature Cards, Stats, Quizzes) + Right Sidebar (Continue Learning, Notifications, Ranking) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
      <!-- ==================== LEFT MAIN COLUMN (lg:col-span-8) ==================== -->
      <div class="lg:col-span-8 space-y-8">
        <!-- 1. Hero Section -->
        <section class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-[0_4px_16px_rgba(15,23,42,0.06)] relative overflow-hidden">
          <div class="grid gap-6 md:grid-cols-[1.25fr_0.75fr] items-center">
            <!-- Hero Left: Content -->
            <div class="space-y-4">
              <div v-if="currentUser" class="inline-flex items-center gap-2 rounded-full border border-purple-200 bg-[#F5F3FF] px-3.5 py-1 text-xs font-bold text-[#7C3AED]">
                <span>👋</span>
                <span>Chào mừng trở lại, {{ currentUser.name }}</span>
              </div>
              <div v-else class="inline-flex items-center gap-2 rounded-full border border-purple-200 bg-[#F5F3FF] px-3.5 py-1 text-xs font-bold text-[#7C3AED]">
                <span>✨</span>
                <span>Nền tảng học tập & trắc nghiệm thông minh</span>
              </div>

              <h1 class="text-3xl font-extrabold tracking-tight text-[#0F172A] sm:text-4xl lg:text-[40px] leading-[1.18]">
                Học tập thông minh hơn cùng <span class="text-[#7C3AED]">QuizFlex</span>
              </h1>

              <p class="text-sm font-normal leading-relaxed text-[#64748B] sm:text-base">
                Khám phá hàng nghìn bộ quiz chất lượng, tham gia phòng bài tập và thi đấu trực tiếp cùng bạn bè trong thời gian thực.
              </p>

              <div class="pt-2 flex flex-wrap items-center gap-3">
                <router-link
                  to="/quizzes"
                  class="inline-flex items-center justify-center rounded-lg bg-[#7C3AED] px-5 py-2.5 text-xs sm:text-sm font-bold text-white transition hover:bg-[#6D28D9] active:scale-[0.98] shadow-sm"
                >
                  Làm quiz ngay
                </router-link>
                <router-link
                  to="/join-room"
                  class="inline-flex items-center justify-center rounded-lg border border-[#7C3AED] bg-white px-5 py-2.5 text-xs sm:text-sm font-bold text-[#7C3AED] transition hover:bg-[#F5F3FF] active:scale-[0.98]"
                >
                  Tham gia phòng
                </router-link>
              </div>
            </div>

            <!-- Hero Right: Clean Education Visual / Illustration -->
            <div class="hidden md:flex flex-col items-center justify-center relative p-2">
              <div class="w-full max-w-[230px] space-y-2.5">
                <!-- Mini Badge: Target Accuracy -->
                <div class="flex items-center gap-2.5 rounded-xl border border-slate-100 bg-[#F8FAFC] p-2.5 shadow-xs">
                  <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#F5F3FF] text-[#7C3AED] text-sm font-bold">🎯</span>
                  <div class="truncate">
                    <p class="text-[11px] font-bold text-[#0F172A]">Chính xác 95%</p>
                    <p class="text-[9px] text-[#64748B]">Mục tiêu ôn luyện</p>
                  </div>
                </div>

                <!-- Main Mini Preview Card -->
                <div class="rounded-xl border border-purple-100 bg-[#F5F3FF] p-3.5 text-center space-y-1.5 shadow-xs">
                  <div class="mx-auto flex h-9 w-9 items-center justify-center rounded-full bg-white shadow-xs text-base">
                    ⚡
                  </div>
                  <p class="text-xs font-black text-[#0F172A]">Đấu trường trực tiếp</p>
                  <span class="inline-block rounded-full bg-white px-2.5 py-0.5 text-[10px] font-bold text-[#7C3AED]">
                    Kahoot Mode
                  </span>
                </div>

                <!-- Mini Badge: Streak -->
                <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-[#F8FAFC] p-2.5 text-xs font-bold text-slate-700 shadow-xs">
                  <span class="flex items-center gap-1.5 text-[11px]">
                    <span>🔥</span> Chuỗi học tập
                  </span>
                  <span class="text-[#F59E0B] text-xs font-black">Streak +1</span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- 2. 3 Feature Cards (Full Width of Left Column) -->
        <LandingQuickActions />

        <!-- 3. Statistics Section (Full Width of Left Column) -->
        <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 shadow-[0_4px_16px_rgba(15,23,42,0.06)]">
          <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
            <!-- Stat 1: Quiz có sẵn -->
            <div class="flex items-center gap-3 pt-3 sm:pt-0 sm:px-2">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F5F3FF] text-[#7C3AED] text-lg">
                📚
              </div>
              <div>
                <strong class="block text-xl font-black text-[#0F172A] sm:text-2xl">{{ rawQuizzes.length || 0 }}</strong>
                <span class="text-[11px] font-semibold text-[#64748B]">Quiz có sẵn</span>
              </div>
            </div>

            <!-- Stat 2: Phòng đang hoạt động -->
            <div class="flex items-center gap-3 pt-3 sm:pt-0 sm:px-2">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#EFF6FF] text-[#2563EB] text-lg">
                🏫
              </div>
              <div>
                <strong class="block text-xl font-black text-[#0F172A] sm:text-2xl">{{ roomCountDisplay }}</strong>
                <span class="text-[11px] font-semibold text-[#64748B]">Phòng hoạt động</span>
              </div>
            </div>

            <!-- Stat 3: Lượt thi đấu -->
            <div class="flex items-center gap-3 pt-3 sm:pt-0 sm:px-2">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#FFFBEB] text-[#F59E0B] text-lg">
                ⚡
              </div>
              <div>
                <strong class="block text-xl font-black text-[#0F172A] sm:text-2xl">{{ totalLiveMatches }}</strong>
                <span class="text-[11px] font-semibold text-[#64748B]">Lượt thi đấu</span>
              </div>
            </div>

            <!-- Stat 4: Lượt làm quiz -->
            <div class="flex items-center gap-3 pt-3 sm:pt-0 sm:px-2">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F0FDF4] text-[#16A34A] text-lg">
                🎯
              </div>
              <div>
                <strong class="block text-xl font-black text-[#0F172A] sm:text-2xl">{{ totalAttemptsDisplay }}</strong>
                <span class="text-[11px] font-semibold text-[#64748B]">Lượt làm bài</span>
              </div>
            </div>
          </div>
        </section>

        <!-- 4. Quiz Mới Nhất Section (Full Width of Left Column) -->
        <section id="quiz-topics" class="space-y-5 pt-1">
          <!-- Header -->
          <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
              <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Khám phá đề thi</p>
              <h2 class="mt-0.5 text-2xl font-black text-[#0F172A] sm:text-3xl">Quiz mới nhất</h2>
              <p class="mt-0.5 text-xs sm:text-sm text-[#64748B]">Khám phá các bộ đề vừa được cập nhật từ giáo viên và cộng đồng.</p>
            </div>
            <div class="flex items-center gap-3">
              <div class="relative w-full sm:w-60">
                <input
                  v-model="searchKeyword"
                  class="w-full rounded-lg border border-slate-200 bg-white pl-8 pr-3 py-1.5 text-xs text-[#0F172A] outline-none focus:border-[#7C3AED] focus:ring-1 focus:ring-purple-200 transition"
                  placeholder="Tìm kiếm đề thi..."
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
          <div class="flex flex-wrap gap-1.5">
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

          <!-- Quiz Cards Grid (Inside Left Column) -->
          <div v-if="displayedQuizzes.length > 0" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
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

      <!-- ==================== RIGHT SIDEBAR (lg:col-span-4) ==================== -->
      <aside class="lg:col-span-4 space-y-6">
        <!-- 1. Tiếp tục học (Top-aligned with Hero) -->
        <HomeContinueLearning />

        <!-- 2. Thông báo mới -->
        <HomeNotifications />

        <!-- 3. Bảng xếp hạng (Tuần) -->
        <HomeWeeklyRanking />
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import LandingQuickActions from '@/components/Home/LandingQuickActions.vue'
import HomeContinueLearning from '@/components/Home/HomeContinueLearning.vue'
import HomeNotifications from '@/components/Home/HomeNotifications.vue'
import HomeWeeklyRanking from '@/components/Home/HomeWeeklyRanking.vue'
import { currentUserStorage, normalizeQuizCard, quizzesApi } from '@/services/api'

const currentUser = ref(currentUserStorage.get())
const searchKeyword = ref('')
const activeCategory = ref('Tất cả')
const rawQuizzes = ref([])

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

const totalAttempts = computed(() => {
  return rawQuizzes.value.reduce((sum, quiz) => sum + Number(quiz.attempts || 0), 0)
})

const formatNumber = (num) => {
  if (!num || num === 0) return '0'
  if (num >= 1000) return `${(num / 1000).toFixed(1).replace('.0', '')}K`
  return String(num)
}

const totalAttemptsDisplay = computed(() => {
  const count = totalAttempts.value
  return count > 0 ? formatNumber(count) : '0'
})

const roomCountDisplay = computed(() => {
  return rawQuizzes.value.length > 0 ? String(Math.max(1, Math.min(rawQuizzes.value.length, 12))) : '0'
})

const totalLiveMatches = computed(() => {
  const attempts = totalAttempts.value
  return attempts > 0 ? formatNumber(Math.floor(attempts * 0.4)) : '0'
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
  Khó: 'bg-red-50 text-red-700',
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
