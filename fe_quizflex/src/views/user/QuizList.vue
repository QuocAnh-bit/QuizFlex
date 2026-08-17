<template>
  <section class="grid gap-6 py-8">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Quiz Catalog</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Danh sách quiz</h1>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Lọc nhanh theo Cấp học, Khối lớp và Bộ môn giảng dạy để tiếp cận chính xác kiến thức cần thiết cho Giáo viên và Học sinh.</p>
        </div>
        <router-link class="btn-primary" to="/dashboard/questions/create">Tạo quiz mới</router-link>
      </div>
    </div>

    <!-- ADVANCED TAXONOMY & SEARCH FILTER BAR -->
    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-2xl grid gap-4">
      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <!-- 1. Cấp học Filter -->
        <select v-model="filters.education_level_id" class="field" @change="onLevelChange">
          <option value="">🎓 Tất cả Cấp học</option>
          <option v-for="level in taxonomyLevels" :key="level.id" :value="level.id">
            {{ level.name }}
          </option>
        </select>

        <!-- 2. Lớp học Filter -->
        <select v-model="filters.grade_id" class="field" @change="loadQuizzes">
          <option value="">🏫 Tất cả Lớp</option>
          <option v-for="grade in availableGrades" :key="grade.id" :value="grade.id">
            {{ grade.name }}
          </option>
        </select>

        <!-- 3. Bộ môn Filter -->
        <select v-model="filters.subject_id" class="field" @change="loadQuizzes">
          <option value="">📖 Tất cả Môn học</option>
          <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">
            {{ subject.name }}
          </option>
        </select>

        <!-- 4. Độ khó -->
        <select v-model="filters.difficulty" class="field" @change="loadQuizzes">
          <option value="">⚡ Tất cả độ khó</option>
          <option value="easy">Dễ</option>
          <option value="medium">Vừa</option>
          <option value="hard">Khó</option>
        </select>
      </div>

      <div class="grid gap-3 xl:grid-cols-[1fr_auto_auto_auto]">
        <input v-model="filters.search" class="field" placeholder="Tìm tên quiz, chuyên đề, từ khóa..." @keyup.enter="loadQuizzes" />
        <select v-model="filters.visibility" class="field xl:w-44" @change="loadQuizzes">
          <option value="all">Tất cả Chế độ</option>
          <option value="public">Public</option>
          <option value="private">Private</option>
          <option value="group">Group</option>
        </select>
        <button class="btn-primary" type="button" @click="loadQuizzes">🔍 Lọc dữ liệu</button>
        <button v-if="hasActiveFilters" class="btn-ghost text-xs text-rose-500" type="button" @click="resetFilters">✖ Đặt lại</button>
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
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <article v-for="quiz in filteredQuizzes" :key="quiz.id" class="group overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-card)] transition duration-300 hover:-translate-y-2 hover:border-[var(--border-strong)]">
          <router-link :to="`/quizzes/${quiz.id}`" class="block">
            <div class="relative h-36" :style="{ background: quiz.cover }">
              <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-white/10"></div>
              <div class="absolute left-4 top-4 flex flex-wrap gap-1.5">
                <span v-if="quiz.grade_name" class="rounded-full bg-indigo-600/80 px-2.5 py-0.5 text-[10px] font-black text-white backdrop-blur">{{ quiz.grade_name }}</span>
                <span v-if="quiz.subject_name" class="rounded-full bg-emerald-600/80 px-2.5 py-0.5 text-[10px] font-black text-white backdrop-blur">{{ quiz.subject_name }}</span>
                <span v-else-if="quiz.topic_name" class="rounded-full bg-purple-600/80 px-2.5 py-0.5 text-[10px] font-black text-white backdrop-blur">{{ quiz.topic_name }}</span>
              </div>
              <div class="absolute bottom-4 right-4 grid h-12 w-12 place-items-center rounded-2xl bg-white/90 text-sm font-black text-slate-900 shadow-xl">{{ quiz.icon }}</div>
            </div>
            <div class="p-5">
              <div class="mb-3 flex flex-wrap items-center gap-2">
                <VisibilityBadge :value="quiz.visibility" />
                <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-black text-[var(--muted)]">{{ quiz.difficulty }}</span>
                <span v-if="quiz.topic_name && quiz.topic_name !== quiz.subject_name" class="rounded-full border border-[var(--primary)]/30 bg-[var(--primary)]/10 px-3 py-1 text-xs font-bold text-[var(--primary)] truncate max-w-[180px]">Chủ đề: {{ quiz.topic_name }}</span>
              </div>
              <h2 class="text-xl font-black tracking-[-0.04em] text-[var(--text)] transition group-hover:text-[var(--primary)] line-clamp-1">{{ quiz.title }}</h2>
              <p class="mt-2 line-clamp-2 text-sm leading-6 text-[var(--muted)]">{{ quiz.description || 'Chưa có mô tả.' }}</p>
              <div class="mt-5 grid grid-cols-3 gap-2">
                <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-center"><b class="block text-[var(--text)]">{{ quiz.questions }}</b><span class="text-[10px] font-bold text-[var(--muted)]">Câu</span></div>
                <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-center"><b class="block text-[var(--text)]">{{ quiz.duration }}</b><span class="text-[10px] font-bold text-[var(--muted)]">Thời gian</span></div>
                <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-center"><b class="block text-[var(--text)]">{{ quiz.avgScore }}%</b><span class="text-[10px] font-bold text-[var(--muted)]">TB</span></div>
              </div>
            </div>
          </router-link>
        </article>
      </div>

      <div v-if="filteredQuizzes.length === 0" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center shadow-[var(--shadow-card)]">
        <h3 class="text-2xl font-black text-[var(--text)]">Không tìm thấy quiz phù hợp</h3>
        <p class="mt-2 text-sm text-[var(--muted)]">Hãy thử điều chỉnh bộ lọc Cấp học, Lớp hoặc Môn học.</p>
        <button class="btn-ghost mt-4 text-xs font-black text-[var(--primary)]" @click="resetFilters">Xem tất cả bài thi</button>
      </div>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLoadingState from '@/components/common/AppLoadingState.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import VisibilityBadge from '@/components/common/VisibilityBadge.vue'
import { currentUserStorage, normalizeQuizCard, quizzesApi, taxonomyApi } from '@/services/api'

const route = useRoute()
const router = useRouter()

const quizzes = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const tags = ref([])

const taxonomyLevels = ref([])
const allSubjects = ref([])

const filters = reactive({
  search: '',
  visibility: currentUserStorage.get() ? 'all' : 'public',
  difficulty: '',
  education_level_id: '',
  grade_id: '',
  subject_id: '',
  tag: 'all',
})

const fetchTaxonomyTree = async () => {
  try {
    const data = await taxonomyApi.tree()
    const payload = data?.education_levels ? data : (data?.data ?? data)
    if (payload) {
      taxonomyLevels.value = payload.education_levels || payload.educationLevels || []
      allSubjects.value = payload.subjects || []
    }
  } catch (e) {
    console.error('Không tải được danh mục Cấp học:', e)
  }
}

const availableGrades = computed(() => {
  if (!filters.education_level_id) {
    return taxonomyLevels.value.flatMap(l => l.grades || [])
  }
  const level = taxonomyLevels.value.find(l => l.id === Number(filters.education_level_id))
  return level ? level.grades || [] : []
})

const availableSubjects = computed(() => {
  if (!filters.grade_id) {
    return allSubjects.value
  }
  const grade = availableGrades.value.find(g => g.id === Number(filters.grade_id))
  return grade && grade.subjects && grade.subjects.length ? grade.subjects : allSubjects.value
})

const onLevelChange = () => {
  filters.grade_id = ''
  loadQuizzes()
}

const hasActiveFilters = computed(() => {
  return Boolean(filters.search || filters.education_level_id || filters.grade_id || filters.subject_id || filters.difficulty)
})

const resetFilters = () => {
  filters.search = ''
  filters.difficulty = ''
  filters.education_level_id = ''
  filters.grade_id = ''
  filters.subject_id = ''
  filters.tag = 'all'
  router.push({ path: '/quizzes' })
  loadQuizzes()
}

const applyRouteFilters = () => {
  filters.search = typeof route.query.search === 'string' ? route.query.search : ''
  filters.difficulty = typeof route.query.difficulty === 'string' ? route.query.difficulty : ''
  filters.education_level_id = route.query.education_level_id ? Number(route.query.education_level_id) : ''
  filters.grade_id = route.query.grade_id ? Number(route.query.grade_id) : ''
  filters.subject_id = route.query.subject_id ? Number(route.query.subject_id) : ''
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
      education_level_id: filters.education_level_id || undefined,
      grade_id: filters.grade_id || undefined,
      subject_id: filters.subject_id || undefined,
    }

    const data = await quizzesApi.list(params)
    const list = Array.isArray(data) ? data : (data?.data && Array.isArray(data.data) ? data.data : [])
    quizzes.value = list.map((q) => {
      const card = normalizeQuizCard(q)
      card.education_level_name = q.education_level_name
      card.grade_name = q.grade_name
      card.subject_name = q.subject_name
      card.topic_name = q.topic_name
      return card
    })
    tags.value = [...new Set(quizzes.value.map((quiz) => quiz.tag).filter(Boolean).filter((tag) => tag !== 'AI'))]
  } catch (error) {
    errorMessage.value = `Không tải được quiz: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

const filteredQuizzes = computed(() => quizzes.value)

watch(
  () => route.query,
  () => {
    applyRouteFilters()
    loadQuizzes()
  }
)

onMounted(async () => {
  await fetchTaxonomyTree()
  applyRouteFilters()
  await loadQuizzes()
})
</script>
