<template>
  <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
    <form class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl" @submit.prevent="saveQuiz">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Manual editor</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Tạo quiz thủ công</h1>
        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Chọn visibility ngay khi tạo đề: Public, Private hoặc Group. Vâng, cuối cùng quyền truy cập cũng có nhà cửa đàng hoàng.</p>

        <div class="mt-8 grid gap-5">
          <div class="grid gap-4 md:grid-cols-[1fr_220px]">
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">Tiêu đề quiz<input v-model="form.title" class="field" placeholder="Ví dụ: Ôn tập Sinh học lớp 10" /></label>
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">Tag<input v-model="form.tag" class="field" placeholder="Sinh học" /></label>
          </div>
          <label class="grid gap-2 text-sm font-black text-[var(--text)]">Mô tả<textarea v-model="form.description" class="field min-h-28" placeholder="Mô tả ngắn cho người làm quiz"></textarea></label>
          <div class="grid gap-4 md:grid-cols-3">
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">Thời gian<input v-model="form.duration" class="field" placeholder="12 phút" /></label>
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">Độ khó<select v-model="form.difficulty" class="field"><option>Dễ</option><option>Vừa</option><option>Khó</option></select></label>
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">Danh mục<select v-model="form.category" class="field"><option>Khoa học</option><option>Ngôn ngữ</option><option>Giải trí</option><option>Lịch sử</option><option>Trivia</option></select></label>
          </div>
        </div>

        <div class="mt-8">
          <div class="mb-4 flex items-center justify-between gap-4"><h2 class="text-2xl font-black tracking-[-0.05em] text-[var(--text)]">Visibility</h2><VisibilityBadge :value="form.visibility" /></div>
          <div class="grid gap-3 md:grid-cols-3">
            <button v-for="option in visibilityOptions" :key="option.value" type="button" class="relative overflow-hidden rounded-[1.4rem] border p-4 text-left transition duration-300 hover:-translate-y-1 active:scale-[0.98]" :class="form.visibility === option.value ? 'border-[var(--border-strong)] bg-[var(--chip-active)] shadow-[0_18px_44px_rgba(155,44,255,0.16)]' : 'border-[var(--border)] bg-[var(--surface-soft)] hover:border-[var(--border-strong)]'" @click="form.visibility = option.value">
              <div v-if="form.visibility === option.value" class="absolute inset-0 bg-gradient-to-br from-[var(--primary)]/10 via-[var(--primary-2)]/10 to-[var(--accent)]/10"></div>
              <div class="relative z-10"><div class="mb-3 text-2xl">{{ option.icon }}</div><b class="block text-[var(--text)]">{{ option.title }}</b><p class="mt-2 text-xs font-semibold leading-5 text-[var(--muted)]">{{ option.description }}</p></div>
            </button>
          </div>
          <label v-if="form.visibility === 'group'" class="mt-4 grid gap-2 text-sm font-black text-[var(--text)]">Room gắn với quiz<input v-model="form.roomCode" class="field" placeholder="VD: QZ24" /></label>
        </div>

        <div class="mt-8 grid gap-4">
          <div v-for="(question, index) in questions" :key="question.id" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <div class="mb-3 flex items-center justify-between"><b class="text-[var(--text)]">Câu {{ index + 1 }}</b><button type="button" class="text-sm font-black text-[var(--danger)]" @click="removeQuestion(index)">Xóa</button></div>
            <input v-model="question.text" class="field" placeholder="Nội dung câu hỏi" />
            <div class="mt-3 grid gap-3 md:grid-cols-2"><input v-for="answer in question.answers" :key="answer.key" v-model="answer.text" class="field" :placeholder="`Đáp án ${answer.key}`" /></div>
            <label class="mt-3 grid gap-2 text-sm font-black text-[var(--text)]">Đáp án đúng<select v-model="question.correct" class="field"><option>A</option><option>B</option><option>C</option><option>D</option></select></label>
          </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3"><button class="btn-ghost" type="button" @click="addQuestion">Thêm câu hỏi</button><button class="btn-primary" type="submit">Lưu quiz</button></div>
      </div>
    </form>

    <aside class="grid content-start gap-5">
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Live preview</p>
        <h3 class="mt-2 text-2xl font-black text-[var(--text)]">{{ form.title || 'Quiz chưa đặt tên' }}</h3>
        <p class="mt-2 text-sm leading-6 text-[var(--muted)]">{{ form.description || 'Mô tả sẽ hiển thị ở đây.' }}</p>
        <div class="mt-4 flex flex-wrap gap-2"><VisibilityBadge :value="form.visibility" /><span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-black text-[var(--muted)]">{{ form.difficulty }}</span><span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-black text-[var(--muted)]">{{ questions.length }} câu</span></div>
      </article>
      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Checklist</p>
        <div class="mt-5 grid gap-3"><div v-for="item in checklist" :key="item" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 text-sm font-bold text-[var(--muted)]">✓ {{ item }}</div></div>
      </article>
    </aside>
  </section>
</template>

<script setup>
import { reactive, ref } from 'vue'
import VisibilityBadge from '@/components/common/VisibilityBadge.vue'
import { visibilityOptions } from '@/data/demoData'

const form = reactive({ title: '', tag: 'Sinh học', description: '', duration: '12 phút', difficulty: 'Vừa', category: 'Khoa học', visibility: 'public', roomCode: '' })
const questions = ref([
  { id: 1, text: 'Đâu là đáp án đúng?', correct: 'A', answers: [{ key: 'A', text: '' }, { key: 'B', text: '' }, { key: 'C', text: '' }, { key: 'D', text: '' }] },
  { id: 2, text: 'Khái niệm quan trọng nhất là gì?', correct: 'A', answers: [{ key: 'A', text: '' }, { key: 'B', text: '' }, { key: 'C', text: '' }, { key: 'D', text: '' }] },
])
const checklist = ['Có tiêu đề rõ ràng', 'Ít nhất 2 câu hỏi', 'Mỗi câu có đáp án đúng', 'Chọn trạng thái visibility', 'Group quiz cần có room code']
const addQuestion = () => questions.value.push({ id: Date.now(), text: '', correct: 'A', answers: [{ key: 'A', text: '' }, { key: 'B', text: '' }, { key: 'C', text: '' }, { key: 'D', text: '' }] })
const removeQuestion = (index) => questions.value.splice(index, 1)
const saveQuiz = () => console.log('Save quiz mock', { form, questions: questions.value })
</script>
