<template>
  <section class="grid gap-6 py-4">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Khám phá kiến thức</p>
        <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Danh sách bộ câu hỏi</h1>
        <p class="mt-1 text-sm text-slate-600">Lựa chọn bộ quiz phù hợp để luyện tập, thi đấu hoặc kiểm tra kiến thức.</p>
      </div>
      <router-link class="btn-primary text-xs shrink-0 self-start sm:self-auto" to="/dashboard/questions/create">
        + Tạo quiz mới
      </router-link>
    </div>

    <!-- Filters Bar -->
    <article class="card p-4 sm:p-5">
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_180px_160px_160px_auto]">
        <input v-model="filters.search" class="field text-xs" placeholder="Tìm kiếm quiz, chủ đề, tag..." @keyup.enter="loadQuizzes" />
        <select v-model="filters.visibility" class="field text-xs" @change="loadQuizzes">
          <option value="all">Tất cả chế độ</option>
          <option value="public">Công khai (Public)</option>
          <option value="private">Riêng tư (Private)</option>
          <option value="group">Nhóm (Group)</option>
        </select>
        <select v-model="filters.difficulty" class="field text-xs" @change="loadQuizzes">
          <option value="">Tất cả độ khó</option>
          <option value="easy">Dễ</option>
          <option value="medium">Vừa</option>
          <option value="hard">Khó</option>
        </select>
        <select v-model="filters.tag" class="field text-xs">
          <option value="all">Tất cả tag</option>
          <option value="AI">AI</option>
          <option v-for="tag in tags" :key="tag" :value="tag">{{ tag }}</option>
        </select>
        <button class="btn-secondary text-xs" type="button" @click="loadQuizzes">Tìm kiếm</button>
      </div>
    </article>

    <!-- 1. LOADING STATE -->
    <AppLoadingState 
      v-if="isLoading" 
      title="Đang tải danh sách quiz..." 
      message="Vui lòng chờ trong giây lát để hệ thống tổng hợp danh sách các bộ câu hỏi."
      icon="📚"
    />

    <!-- 2. ERROR STATE -->
    <AppErrorState 
      v-else-if="errorMessage" 
      title="Không thể tải danh sách quiz"
      :message="errorMessage" 
      @retry="loadQuizzes"
    />

    <!-- 3. LOADED STATE -->
    <template v-else>
      <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <article
          v-for="quiz in filteredQuizzes"
          :key="quiz.id"
          class="card overflow-hidden card-hover group flex flex-col justify-between"
        >
          <router-link :to="`/quizzes/${quiz.id}`" class="block">
            <div class="relative h-32 bg-slate-100" :style="{ background: quiz.cover }">
              <div class="absolute left-3 top-3 rounded-md bg-slate-900/80 px-2 py-0.5 text-[10px] font-bold uppercase text-white">
                {{ quiz.badge || 'Quiz' }}
              </div>
              <div class="absolute bottom-3 right-3 grid h-10 w-10 place-items-center rounded-xl bg-white shadow-sm text-base">
                {{ quiz.icon || '📝' }}
              </div>
            </div>
            <div class="p-5 space-y-3">
              <div class="flex items-center gap-2">
                <VisibilityBadge :value="quiz.visibility" />
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600">{{ quiz.difficulty }}</span>
              </div>
              <h2 class="text-base font-bold text-slate-900 group-hover:text-[#7C3AED] transition line-clamp-2">
                {{ quiz.title }}
              </h2>
              <p class="line-clamp-2 text-xs text-slate-500 leading-relaxed">
                {{ quiz.description || 'Chưa có mô tả chi tiết.' }}
              </p>
              <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100 text-center text-xs">
                <div class="rounded-lg bg-slate-50 p-2">
                  <b class="block text-slate-900 font-bold">{{ quiz.questions }}</b>
                  <span class="text-[10px] text-slate-400 font-semibold">Câu hỏi</span>
                </div>
                <div class="rounded-lg bg-slate-50 p-2">
                  <b class="block text-slate-900 font-bold">{{ quiz.duration }}</b>
                  <span class="text-[10px] text-slate-400 font-semibold">Thời gian</span>
                </div>
                <div class="rounded-lg bg-slate-50 p-2">
                  <b class="block text-[#7C3AED] font-bold">{{ quiz.avgScore }}%</b>
                  <span class="text-[10px] text-slate-400 font-semibold">Điểm TB</span>
                </div>
              </div>
            </div>
          </router-link>
        </article>
      </div>

      <div v-if="filteredQuizzes.length === 0" class="card p-12 text-center text-slate-500">
        <span class="text-3xl block mb-2">🔍</span>
        <h3 class="text-lg font-bold text-slate-800">Không tìm thấy quiz phù hợp</h3>
        <p class="mt-1 text-xs">Hãy thử thay đổi tiêu chí bộ lọc hoặc tạo quiz mới trong tài khoản của bạn.</p>
      </div>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import AppLoadingState from '@/components/common/AppLoadingState.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import VisibilityBadge from '@/components/common/VisibilityBadge.vue'
import { currentUserStorage, normalizeQuizCard, quizzesApi } from '@/services/api'

const route = useRoute()

const quizzes = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const tags = ref([])

const filters = reactive({
  search: '',
  visibility: currentUserStorage.get() ? 'all' : 'public',
  difficulty: '',
  tag: 'all',
})

const applyRouteFilters = () => {
  filters.search = typeof route.query.search === 'string' ? route.query.search : ''
  filters.difficulty = typeof route.query.difficulty === 'string' ? route.query.difficulty : ''
  filters.tag = typeof route.query.tag === 'string' ? route.query.tag : 'all'

  const visibility = typeof route.query.visibility === 'string' ? route.query.visibility : ''
  filters.visibility = ['all', 'public', 'private', 'group'].includes(visibility)
    ? visibility
    : (currentUserStorage.get() ? 'all' : 'public')
}

const loadQuizzes = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const params = {
      search: filters.search || undefined,
      visibility: filters.visibility === 'all' ? undefined : filters.visibility,
      difficulty: filters.difficulty || undefined,
    }
    const data = await quizzesApi.list(params)
    quizzes.value = data.map(normalizeQuizCard)
    tags.value = [...new Set(quizzes.value.map((quiz) => quiz.tag).filter(Boolean).filter((tag) => tag !== 'AI'))]
  } catch (error) {
    errorMessage.value = `Không tải được quiz: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

const filteredQuizzes = computed(() => quizzes.value.filter((quiz) => filters.tag === 'all' || quiz.tag === filters.tag))

onMounted(async () => {
  applyRouteFilters()
  await loadQuizzes()
})
</script>
