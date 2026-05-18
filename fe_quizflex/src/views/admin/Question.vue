<template>
  <section class="grid gap-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Quiz Library</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Kho quiz</h1>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Lọc quiz theo tag, độ khó và trạng thái visibility. Vâng, cuối cùng danh sách cũng chịu cư xử như một sản phẩm thật.</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <router-link class="btn-ghost" to="/admin/questions/ocr">Upload OCR</router-link>
          <router-link class="btn-ghost" to="/admin/questions/ai">AI Generator</router-link>
          <router-link class="btn-primary" to="/admin/questions/create">Tạo quiz</router-link>
        </div>
      </div>
    </div>

    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <div class="grid gap-4 xl:grid-cols-[1fr_auto_auto_auto] xl:items-center">
        <div class="flex items-center gap-3 rounded-2xl border border-[var(--border)] bg-[var(--input-bg)] px-4 py-3 transition focus-within:border-[var(--border-strong)]">
          <span class="text-lg">🔍</span>
          <input v-model="search" class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]" placeholder="Tìm theo tên quiz, tác giả, tag..." />
        </div>
        <select v-model="difficultyFilter" class="field xl:w-44"><option>Tất cả độ khó</option><option>Dễ</option><option>Vừa</option><option>Khó</option></select>
        <select v-model="tagFilter" class="field xl:w-44"><option>Tất cả tag</option><option v-for="tag in tags" :key="tag">{{ tag }}</option></select>
        <select v-model="visibilityFilter" class="field xl:w-48"><option value="all">Tất cả visibility</option><option value="public">Public</option><option value="private">Private</option><option value="group">Group</option></select>
      </div>

      <div class="mt-5 flex flex-wrap gap-2">
        <button v-for="item in visibilityChips" :key="item.value" type="button" class="rounded-full border px-4 py-2 text-xs font-black transition hover:-translate-y-0.5 active:scale-95" :class="visibilityFilter === item.value ? 'border-[var(--border-strong)] bg-[var(--chip-active)] text-[var(--primary)]' : 'border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)] hover:text-[var(--text)]'" @click="visibilityFilter = item.value">{{ item.label }}</button>
      </div>
    </article>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <article v-for="quiz in filteredQuizzes" :key="quiz.id" class="group relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-card)] backdrop-blur-2xl transition duration-300 hover:-translate-y-2 hover:border-[var(--border-strong)] hover:shadow-[0_24px_70px_rgba(0,0,0,0.24)]">
        <div class="relative h-36 overflow-hidden" :style="{ background: quiz.cover }">
          <div class="absolute inset-0 bg-gradient-to-t from-black/45 via-black/5 to-white/10"></div>
          <div class="absolute left-4 top-4 rounded-full bg-black/55 px-3 py-1 text-[10px] font-black text-white backdrop-blur">{{ quiz.badge }}</div>
          <div class="absolute bottom-4 right-4 grid h-12 w-12 place-items-center rounded-2xl bg-white/90 text-2xl shadow-xl transition group-hover:scale-110 group-hover:rotate-3">{{ quiz.icon }}</div>
        </div>
        <div class="p-5">
          <div class="mb-3 flex flex-wrap items-center gap-2">
            <VisibilityBadge :value="quiz.visibility" />
            <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-black text-[var(--muted)]">{{ quiz.difficulty }}</span>
          </div>
          <h3 class="text-xl font-black tracking-[-0.04em] text-[var(--text)] transition group-hover:text-[var(--primary)]">{{ quiz.title }}</h3>
          <p class="mt-2 text-sm leading-6 text-[var(--muted)]">{{ quiz.category }} • {{ quiz.tag }} • by {{ quiz.author }}</p>
          <div class="mt-5 grid grid-cols-3 gap-2">
            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-center"><b class="block text-[var(--text)]">{{ quiz.questions }}</b><span class="text-[10px] font-bold text-[var(--muted)]">Câu</span></div>
            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-center"><b class="block text-[var(--text)]">{{ quiz.attempts }}</b><span class="text-[10px] font-bold text-[var(--muted)]">Lượt</span></div>
            <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-center"><b class="block text-[var(--text)]">{{ quiz.avgScore }}%</b><span class="text-[10px] font-bold text-[var(--muted)]">Điểm</span></div>
          </div>
          <div class="mt-5 flex flex-wrap gap-2">
            <router-link class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-2 text-xs font-black text-[var(--text)] transition hover:border-[var(--border-strong)] hover:bg-[var(--chip-active)]" :to="`/quiz/${quiz.id}`">Xem</router-link>
            <router-link class="rounded-full bg-gradient-to-br from-[var(--primary)] to-[var(--primary-2)] px-4 py-2 text-xs font-black text-white" :to="`/admin/questions/edit/${quiz.id}`">Sửa</router-link>
          </div>
        </div>
      </article>
    </div>

    <div v-if="filteredQuizzes.length === 0" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center shadow-[var(--shadow-card)]">
      <div class="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-3xl bg-[var(--chip-active)] text-3xl">🔎</div>
      <h3 class="text-2xl font-black text-[var(--text)]">Không tìm thấy quiz</h3>
      <p class="mt-2 text-sm text-[var(--muted)]">Đổi filter đi, dữ liệu mock không có phép màu đâu.</p>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'
import VisibilityBadge from '@/components/common/VisibilityBadge.vue'
import { allQuizzes } from '@/data/demoData'

const search = ref('')
const difficultyFilter = ref('Tất cả độ khó')
const tagFilter = ref('Tất cả tag')
const visibilityFilter = ref('all')

const visibilityChips = [
  { value: 'all', label: 'Tất cả' },
  { value: 'public', label: '🌐 Public' },
  { value: 'private', label: '🔒 Private' },
  { value: 'group', label: '👥 Group' },
]

const tags = computed(() => [...new Set(allQuizzes.map((quiz) => quiz.tag))])

const filteredQuizzes = computed(() => {
  const keyword = search.value.trim().toLowerCase()
  return allQuizzes.filter((quiz) => {
    const matchesKeyword = !keyword || [quiz.title, quiz.author, quiz.category, quiz.tag, quiz.badge].join(' ').toLowerCase().includes(keyword)
    const matchesDifficulty = difficultyFilter.value === 'Tất cả độ khó' || quiz.difficulty === difficultyFilter.value
    const matchesTag = tagFilter.value === 'Tất cả tag' || quiz.tag === tagFilter.value
    const matchesVisibility = visibilityFilter.value === 'all' || quiz.visibility === visibilityFilter.value
    return matchesKeyword && matchesDifficulty && matchesTag && matchesVisibility
  })
})
</script>
