<template>
  <section class="grid min-h-[620px] items-center gap-12 py-10 lg:grid-cols-[1.05fr_0.95fr]">
    <div>
      <div class="mb-6 inline-flex rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-4 py-2 text-sm font-black text-[var(--primary)]">QuizFlex MVP • FE Mock</div>
      <h1 class="text-5xl font-black leading-[0.92] tracking-[-0.08em] text-[var(--text)] sm:text-6xl">
        Tạo quiz nhanh hơn. <span class="gradient-text">Chơi quiz cuốn hơn.</span>
      </h1>
      <p class="mt-7 max-w-2xl text-lg font-medium leading-8 text-[var(--muted)]">Landing, catalog quiz, join room, dashboard VIP/Admin, tạo quiz thủ công, AI generator, report và payment mock. FE đủ khung để sau này nối API, không phải đập đi xây lại như một bi kịch deadline.</p>
      <div class="mt-8 flex flex-wrap gap-3">
        <router-link class="btn-primary" to="/quiz/demo">Làm thử quiz</router-link>
        <router-link class="btn-ghost" to="/join-room">Join room</router-link>
        <router-link class="btn-ghost" to="/admin">Vào dashboard</router-link>
      </div>
      <div class="mt-10 grid max-w-2xl gap-4 sm:grid-cols-3">
        <div v-for="item in heroStats" :key="item.label" class="rounded-3xl border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-xl">
          <strong class="block text-3xl font-black tracking-[-0.05em] text-[var(--text)]">{{ item.value }}</strong>
          <span class="mt-1 block text-sm font-bold text-[var(--muted)]">{{ item.label }}</span>
        </div>
      </div>
    </div>

    <div class="relative">
      <div class="absolute -inset-8 rounded-[3rem] bg-gradient-to-br from-[var(--primary)] via-[var(--primary-2)] to-[var(--accent)] opacity-20 blur-3xl"></div>
      <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-soft)] backdrop-blur-2xl">
        <div class="flex h-12 items-center gap-2 border-b border-[var(--border)] bg-[var(--surface-soft)] px-5"><span class="h-3 w-3 rounded-full bg-[#ff5f57]"></span><span class="h-3 w-3 rounded-full bg-[#ffbd2e]"></span><span class="h-3 w-3 rounded-full bg-[#28c840]"></span><span class="ml-2 text-xs font-black text-[var(--muted)]">Live preview</span></div>
        <div class="grid gap-4 p-5">
          <div class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5">
            <div class="flex items-start justify-between gap-5">
              <div><p class="text-sm font-black text-[var(--primary)]">Quiz đang làm</p><h3 class="mt-2 text-xl font-black text-[var(--text)]">Kiến thức Sinh học cơ bản</h3><p class="mt-2 text-sm leading-6 text-[var(--muted)]">10 câu hỏi • 12 phút • Trung bình</p></div>
              <div class="grid h-20 w-20 place-items-center rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] text-xl font-black text-[var(--text)]">78%</div>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div v-for="card in previewCards" :key="card.label" class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-5"><strong class="block text-2xl font-black text-[var(--text)]">{{ card.value }}</strong><span class="text-sm font-bold text-[var(--muted)]">{{ card.label }}</span></div>
          </div>
          <div class="grid gap-3">
            <div v-for="answer in answers" :key="answer.key" class="flex items-center gap-3 rounded-2xl border p-3 text-sm font-bold" :class="answer.active ? 'border-[var(--border-strong)] bg-[var(--chip-active)] text-[var(--text)]' : 'border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)]'"><span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br from-[var(--primary)] to-[var(--primary-2)] text-xs font-black text-white">{{ answer.key }}</span>{{ answer.text }}</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <LandingQuickActions />

  <section id="quiz-topics" class="relative py-12">
    <div class="pointer-events-none absolute -top-24 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-[var(--primary)] opacity-10 blur-3xl"></div>
    <div class="relative z-10 mb-8 flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
      <div><p class="font-black text-[var(--primary)]">Quiz có sẵn</p><h2 class="mt-3 text-4xl font-black tracking-[-0.06em] text-[var(--text)] lg:text-5xl">Khám phá các chủ đề nổi bật</h2><p class="mt-4 max-w-2xl leading-7 text-[var(--muted)]">Chọn nhanh quiz theo chủ đề, làm thử hoặc dùng làm mẫu để tạo bộ câu hỏi riêng.</p></div>
      <div class="group flex w-full max-w-md items-center gap-3 rounded-full border border-[var(--border)] bg-[var(--surface)] px-4 py-3 shadow-[var(--shadow-card)] backdrop-blur-xl transition focus-within:border-[var(--border-strong)]"><span class="text-lg">🔍</span><input v-model="searchKeyword" class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]" placeholder="Tìm quiz, chủ đề, môn học..." /></div>
    </div>
    <div class="relative z-10 mb-8 flex flex-wrap gap-3">
      <button v-for="category in categoryTabs" :key="category" type="button" class="rounded-full border px-4 py-2 text-sm font-black transition hover:-translate-y-0.5 active:scale-95" :class="activeCategory === category ? 'border-[var(--border-strong)] bg-[var(--chip-active)] text-[var(--primary)] shadow-[0_12px_30px_rgba(155,44,255,0.18)]' : 'border-[var(--border)] bg-[var(--surface)] text-[var(--muted)] hover:border-[var(--border-strong)] hover:text-[var(--text)]'" @click="activeCategory = category">{{ category }}</button>
    </div>
    <div class="relative z-10 grid gap-10">
      <section v-for="section in filteredQuizSections" :key="section.id" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-4 shadow-xl backdrop-blur-xl transition hover:-translate-y-1 hover:border-[var(--border-strong)] sm:p-5">
        <div class="mb-5 flex items-center justify-between gap-4"><div><h3 class="text-xl font-black text-[var(--text)]">{{ section.title }}</h3><p class="mt-1 text-sm text-[var(--muted)]">{{ section.description }}</p></div><router-link class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-2 text-xs font-black text-[var(--primary)] transition hover:border-[var(--border-strong)] hover:bg-[var(--chip-active)]" :to="`/admin/questions?category=${section.id}`">Xem tất cả</router-link></div>
        <div class="scrollbar-soft flex gap-4 overflow-x-auto pb-2">
          <article v-for="quiz in section.quizzes" :key="quiz.id" class="group/card w-[198px] shrink-0 overflow-hidden rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] shadow-[var(--shadow-card)] transition duration-300 hover:-translate-y-2 hover:border-[var(--border-strong)] hover:bg-[var(--surface)]">
            <router-link :to="`/quiz/${quiz.id}`" class="block"><div class="relative h-[124px] overflow-hidden" :style="{ background: quiz.cover }"><div class="absolute inset-0 bg-gradient-to-t from-black/35 via-black/5 to-white/10"></div><div class="absolute left-3 top-3 rounded-full bg-black/55 px-3 py-1 text-[10px] font-black uppercase text-white backdrop-blur">{{ quiz.badge }}</div><div class="absolute bottom-3 right-3 grid h-10 w-10 place-items-center rounded-full bg-white/90 text-lg shadow-lg transition group-hover/card:scale-110">{{ quiz.icon }}</div></div><div class="p-4"><h4 class="line-clamp-2 min-h-[44px] text-sm font-black leading-5 text-[var(--text)] transition group-hover/card:text-[var(--primary)]">{{ quiz.title }}</h4><div class="mt-3 flex items-center gap-1 text-xs"><span class="font-black text-[var(--accent)]">{{ quiz.rating }}</span><span class="text-[var(--accent)]">★</span><span class="text-[var(--muted)]">by {{ quiz.author }}</span></div><div class="mt-3 flex flex-wrap items-center gap-2"><VisibilityBadge :value="quiz.visibility" /><span class="rounded-full px-3 py-1 text-[11px] font-black" :class="difficultyClass(quiz.difficulty)">{{ quiz.difficulty }}</span><span class="text-xs font-bold text-[var(--muted)]">{{ quiz.questions }} câu</span></div></div></router-link>
          </article>
        </div>
      </section>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'
import LandingQuickActions from '@/components/Home/LandingQuickActions.vue'
import VisibilityBadge from '@/components/common/VisibilityBadge.vue'
import { quizTopics } from '@/data/demoData'

const searchKeyword = ref('')
const activeCategory = ref('Tất cả')
const heroStats = [{ value: '12+', label: 'Màn hình FE' }, { value: '3', label: 'Vai trò mock' }, { value: '2', label: 'Theme sáng/tối' }]
const previewCards = [{ value: '24', label: 'Quiz đã tạo' }, { value: '156', label: 'Lượt làm bài' }, { value: '42', label: 'Lượt AI còn lại' }, { value: '87%', label: 'Điểm trung bình' }]
const answers = [{ key: 'A', text: 'Ti thể tạo ra năng lượng.', active: true }, { key: 'B', text: 'Ribosome lưu thông tin.', active: false }, { key: 'C', text: 'Nhân tế bào tiêu hóa.', active: false }]
const categoryTabs = ['Tất cả', ...new Set(quizTopics.map((section) => section.category))]
const filteredQuizSections = computed(() => {
  const keyword = searchKeyword.value.trim().toLowerCase()
  return quizTopics.filter((section) => activeCategory.value === 'Tất cả' || section.category === activeCategory.value).map((section) => {
    if (!keyword) return section
    return { ...section, quizzes: section.quizzes.filter((quiz) => [quiz.title, quiz.author, quiz.badge, quiz.difficulty, section.title].join(' ').toLowerCase().includes(keyword)) }
  }).filter((section) => section.quizzes.length > 0)
})
const difficultyClass = (difficulty) => ({ Dễ: 'bg-emerald-500/15 text-emerald-400', Vừa: 'bg-amber-500/15 text-amber-400', Khó: 'bg-rose-500/15 text-rose-400' }[difficulty] || 'bg-[var(--chip-active)] text-[var(--primary)]')
</script>
