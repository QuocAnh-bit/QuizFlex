<template>
  <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_400px]">
    <form class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl" @submit.prevent="saveQuiz">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="pointer-events-none absolute -left-24 top-1/2 h-64 w-64 rounded-full bg-[var(--accent)]/10 blur-3xl"></div>

      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Manual editor</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ isEditMode ? 'Sửa quiz' : 'Tạo quiz thủ công' }}</h1>
        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Lưu quiz, câu hỏi, đáp án và ảnh bìa trực tiếp vào backend. Form này giờ đã có mặt mũi hơn một bảng nhập liệu hành chính.</p>

        <div class="mt-8 grid gap-5">
          <div class="grid gap-4 md:grid-cols-[1fr_220px]">
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Tiêu đề quiz
              <input v-model="form.title" class="field" placeholder="Ví dụ: Ôn tập Sinh học lớp 10" />
            </label>
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Tag
              <input v-model="form.tag" class="field" placeholder="Sinh học" />
            </label>
          </div>

          <label class="grid gap-2 text-sm font-black text-[var(--text)]">
            Mô tả
            <textarea v-model="form.description" class="field min-h-28" placeholder="Mô tả ngắn cho người làm quiz"></textarea>
          </label>

          <section class="overflow-hidden rounded-[1.7rem] border border-[var(--border)] bg-[var(--surface-soft)] shadow-[var(--shadow-card)]">
            <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_280px]">
              <div class="p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                  <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-[var(--primary)]">Ảnh bìa</p>
                    <h2 class="mt-2 text-2xl font-black tracking-[-0.05em] text-[var(--text)]">Cover của quiz</h2>
                    <p class="mt-2 text-sm leading-6 text-[var(--muted)]">Chỉ upload file ảnh từ máy. Nếu chưa chọn ảnh, hệ thống dùng gradient dự phòng cho đỡ trống trải.</p>
                  </div>
                  <button class="btn-ghost !px-4 !py-2 text-xs" type="button" @click="openCoverPicker">Chọn ảnh</button>
                </div>

                <input ref="coverInput" class="hidden" type="file" accept="image/png,image/jpeg,image/jpg,image/webp,image/gif" @change="handleCoverFileChange" />

                <div class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1fr)_240px]">
                  <div class="rounded-[1.35rem] border border-[var(--border)] bg-[var(--surface)] p-4">
                    <p class="text-sm font-black text-[var(--text)]">File ảnh bìa</p>
                    <p class="mt-1 text-xs font-bold leading-5 text-[var(--muted)]">{{ selectedCoverLabel }}</p>
                    <button class="btn-ghost mt-4 !px-4 !py-2 text-xs" type="button" @click="openCoverPicker">Upload ảnh</button>
                  </div>

                  <div class="grid gap-2 text-sm font-black text-[var(--text)]">
                    Avatar người tạo
                    <div class="flex min-h-[72px] items-center gap-3 rounded-[1.35rem] border border-[var(--border)] bg-[var(--surface)] px-4 py-3">
                      <UserAvatar :user="creatorProfile" size-class="h-12 w-12" text-class="text-sm" ring-class="ring-2 ring-white/10" />
                      <div class="min-w-0">
                        <p class="truncate text-sm font-black text-[var(--text)]">{{ creatorProfile.name || 'Người tạo hiện tại' }}</p>
                        <p class="truncate text-[11px] font-bold text-[var(--muted)]">Avatar lấy từ hồ sơ.</p>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                  <button v-if="form.cover || form.coverFile" type="button" class="rounded-full border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-xs font-black text-rose-300 transition hover:-translate-y-0.5" @click="removeCover">Xóa bìa</button>
                </div>
              </div>

              <div class="relative min-h-[230px] overflow-hidden border-t border-[var(--border)] lg:border-l lg:border-t-0" :style="{ background: coverBackground }">
                <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/15 to-white/10"></div>
                <div class="absolute bottom-4 left-4 right-4">
                  <div class="mb-3"><UserAvatar :user="creatorProfile" size-class="h-12 w-12" rounded-class="rounded-2xl" text-class="text-sm" ring-class="ring-2 ring-white/10" shadow-class="shadow-xl" /></div>
                  <p class="line-clamp-2 text-lg font-black leading-6 text-white drop-shadow">{{ form.title || 'Quiz chưa đặt tên' }}</p>
                  <p class="mt-1 text-xs font-bold text-white/75">{{ form.category || 'Danh mục' }} • {{ difficultyText }}</p>
                </div>
              </div>
            </div>
          </section>

          <div class="grid gap-4 md:grid-cols-3">
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Thời gian phút
              <input v-model.number="form.durationMinutes" class="field" type="number" min="1" max="1440" />
            </label>
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Độ khó
              <select v-model="form.difficulty" class="field">
                <option value="easy">Dễ</option>
                <option value="medium">Vừa</option>
                <option value="hard">Khó</option>
              </select>
            </label>
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Danh mục
              <input v-model="form.category" class="field" placeholder="Khoa học" />
            </label>
          </div>
        </div>

        <div class="mt-8">
          <div class="mb-4 flex items-center justify-between gap-4">
            <h2 class="text-2xl font-black tracking-[-0.05em] text-[var(--text)]">Visibility</h2>
            <VisibilityBadge :value="form.visibility" />
          </div>
          <div class="grid gap-3 md:grid-cols-3">
            <button v-for="option in visibilityOptions" :key="option.value" type="button" class="relative overflow-hidden rounded-[1.4rem] border p-4 text-left transition duration-300 hover:-translate-y-1 active:scale-[0.98]" :class="form.visibility === option.value ? 'border-[var(--border-strong)] bg-[var(--chip-active)] shadow-[0_18px_44px_rgba(155,44,255,0.16)]' : 'border-[var(--border)] bg-[var(--surface-soft)] hover:border-[var(--border-strong)]'" @click="form.visibility = option.value">
              <div v-if="form.visibility === option.value" class="absolute inset-0 bg-gradient-to-br from-[var(--primary)]/10 via-[var(--primary-2)]/10 to-[var(--accent)]/10"></div>
              <div class="relative z-10">
                <div class="mb-3 grid h-10 w-10 place-items-center rounded-2xl bg-[var(--surface)] text-xs font-black text-[var(--primary)]">{{ option.short }}</div>
                <b class="block text-[var(--text)]">{{ option.title }}</b>
                <p class="mt-2 text-xs font-semibold leading-5 text-[var(--muted)]">{{ option.description }}</p>
              </div>
            </button>
          </div>
          <label v-if="form.visibility === 'group'" class="mt-4 grid gap-2 text-sm font-black text-[var(--text)]">
            Room gắn với quiz
            <input v-model="form.roomCode" class="field" placeholder="VD: QZ24" />
          </label>
        </div>

        <div class="mt-8 grid gap-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.18em] text-[var(--primary)]">Question builder</p>
              <h2 class="mt-1 text-2xl font-black tracking-[-0.05em] text-[var(--text)]">Câu hỏi và đáp án</h2>
            </div>
            <button class="btn-ghost !px-4 !py-2 text-xs" type="button" @click="addQuestion">Thêm câu hỏi</button>
          </div>

          <div v-for="(question, index) in questions" :key="question.localId" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4 transition hover:border-[var(--border-strong)]">
            <div class="mb-3 flex items-center justify-between gap-3">
              <b class="text-[var(--text)]">Câu {{ index + 1 }}</b>
              <button type="button" class="text-sm font-black text-[var(--danger)]" @click="removeQuestion(index)">Xóa</button>
            </div>
            <div class="grid gap-3 md:grid-cols-[1fr_120px]">
              <input v-model="question.text" class="field" placeholder="Nội dung câu hỏi" />
              <input v-model.number="question.points" class="field" type="number" min="1" max="1000" />
            </div>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
              <input v-for="answer in question.answers" :key="answer.key" v-model="answer.text" class="field" :placeholder="`Đáp án ${answer.key}`" />
            </div>
            <label class="mt-3 grid gap-2 text-sm font-black text-[var(--text)]">
              Đáp án đúng
              <select v-model="question.correct" class="field">
                <option>A</option>
                <option>B</option>
                <option>C</option>
                <option>D</option>
              </select>
            </label>
          </div>
        </div>

        <div v-if="errorMessage" class="mt-5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ errorMessage }}</div>
        <div v-if="successMessage" class="mt-5 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-300">{{ successMessage }}</div>
        <div class="mt-6 flex flex-wrap gap-3">
          <button class="btn-ghost" type="button" @click="addQuestion">Thêm câu hỏi</button>
          <button class="btn-primary" type="submit" :disabled="isSaving">{{ isSaving ? 'Đang lưu...' : 'Lưu quiz' }}</button>
        </div>
      </div>
    </form>

    <aside class="grid content-start gap-5">
      <article class="overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <div class="relative h-52" :style="{ background: coverBackground }">
          <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-white/10"></div>
          <div class="absolute bottom-5 right-5"><UserAvatar :user="creatorProfile" size-class="h-14 w-14" rounded-class="rounded-2xl" text-class="text-sm" ring-class="ring-2 ring-white/10" shadow-class="shadow-xl" /></div>
        </div>
        <div class="p-6">
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Live preview</p>
          <h3 class="mt-2 text-2xl font-black text-[var(--text)]">{{ form.title || 'Quiz chưa đặt tên' }}</h3>
          <p class="mt-2 text-sm leading-6 text-[var(--muted)]">{{ form.description || 'Mô tả sẽ hiển thị ở đây.' }}</p>
          <div class="mt-4 flex flex-wrap gap-2">
            <VisibilityBadge :value="form.visibility" />
            <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-black text-[var(--muted)]">{{ difficultyText }}</span>
            <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-black text-[var(--muted)]">{{ questions.length }} câu</span>
          </div>
        </div>
      </article>

      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Kiểm tra nhanh</p>
        <div class="mt-5 grid gap-3">
          <div v-for="item in checklist" :key="item" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 text-sm font-bold text-[var(--muted)]">{{ item }}</div>
        </div>
      </article>
    </aside>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import VisibilityBadge from '@/components/common/VisibilityBadge.vue'
import UserAvatar from '@/components/common/UserAvatar.vue'
import { coverToBackground, currentUserStorage, difficultyLabel, normalizeQuestion, quizzesApi } from '@/services/api'

const route = useRoute()
const router = useRouter()
const questionBase = computed(() => route.path.startsWith('/dashboard') ? '/dashboard/questions' : '/admin/questions')

const visibilityOptions = [
  { value: 'public', short: 'PUB', title: 'Public', description: 'Ai cũng có thể xem và làm bài.' },
  { value: 'private', short: 'PRI', title: 'Private', description: 'Chỉ hiển thị trong khu quản trị.' },
  { value: 'group', short: 'GRP', title: 'Group', description: 'Gắn quiz với room code.' },
]

const isEditMode = computed(() => Boolean(route.params.id))
const isSaving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const coverInput = ref(null)
const coverPreview = ref('')

const form = reactive({
  title: '',
  tag: '',
  description: '',
  cover: '',
  coverFile: null,
  removeCover: false,
  durationMinutes: 12,
  difficulty: 'medium',
  category: 'Khoa học',
  visibility: route.query.visibility === 'group' ? 'group' : 'public',
  roomCode: route.query.visibility === 'group' ? `GR${Math.floor(1000 + Math.random() * 9000)}` : '',
})

const makeBlankQuestion = () => ({
  localId: `${Date.now()}-${Math.random()}`,
  id: null,
  text: '',
  correct: 'A',
  points: 10,
  answers: [{ key: 'A', text: '' }, { key: 'B', text: '' }, { key: 'C', text: '' }, { key: 'D', text: '' }],
})

const questions = ref([makeBlankQuestion()])
const checklist = ['Có tiêu đề rõ ràng', 'Có ảnh bìa hoặc gradient dự phòng', 'Ít nhất 1 câu hỏi', 'Mỗi câu có 4 đáp án', 'Mỗi câu có đáp án đúng', 'Group quiz cần có room code']
const difficultyText = computed(() => difficultyLabel(form.difficulty))
const creatorProfile = ref(currentUserStorage.get() || { name: 'Guest', email: '', avatar: '' })
const coverBackground = computed(() => coverToBackground(coverPreview.value || form.cover))
const selectedCoverLabel = computed(() => {
  if (form.coverFile) return `Đã chọn: ${form.coverFile.name}`
  if (form.cover) return 'Đang dùng ảnh bìa đã lưu.'
  return 'Chưa có ảnh bìa. Hệ thống sẽ dùng gradient dự phòng.'
})

const addQuestion = () => questions.value.push(makeBlankQuestion())
const removeQuestion = (index) => {
  if (questions.value.length === 1) {
    errorMessage.value = 'Quiz cần ít nhất 1 câu hỏi, xóa hết thì backend cũng chỉ biết thở dài.'
    return
  }
  questions.value.splice(index, 1)
}

const openCoverPicker = () => coverInput.value?.click()

const revokeCoverPreview = () => {
  if (coverPreview.value?.startsWith('blob:')) URL.revokeObjectURL(coverPreview.value)
}

const clearSelectedCoverFile = () => {
  revokeCoverPreview()
  form.coverFile = null
  coverPreview.value = ''
  if (coverInput.value) coverInput.value.value = ''
}

const handleCoverFileChange = (event) => {
  const [file] = event.target.files || []
  if (!file) return

  if (!file.type.startsWith('image/')) {
    errorMessage.value = 'File bìa phải là ảnh.'
    event.target.value = ''
    return
  }

  if (file.size > 4 * 1024 * 1024) {
    errorMessage.value = 'Ảnh bìa tối đa 4MB. Đừng bắt server nuốt cả album cưới.'
    event.target.value = ''
    return
  }

  revokeCoverPreview()
  form.coverFile = file
  form.removeCover = false
  coverPreview.value = URL.createObjectURL(file)
  errorMessage.value = ''
}

const removeCover = () => {
  clearSelectedCoverFile()
  form.cover = ''
  form.removeCover = true
}

const makePayload = () => {
  const currentUser = currentUserStorage.get()
  const payload = {
    user_id: currentUser?.id,
    title: form.title.trim(),
    tag: form.tag.trim(),
    description: form.description.trim(),
    duration_minutes: Number(form.durationMinutes) || 12,
    difficulty: form.difficulty,
    category: form.category.trim() || 'General',
    visibility: form.visibility,
    roomCode: form.roomCode.trim(),
    questions: questions.value.map((question, index) => ({
      id: question.id || undefined,
      text: question.text.trim(),
      correct: question.correct,
      order: index,
      points: Number(question.points) || 10,
      answers: question.answers.map((answer, answerIndex) => ({
        id: answer.id || undefined,
        key: answer.key,
        text: answer.text.trim(),
        order: answerIndex,
        is_correct: answer.key === question.correct,
      })),
    })),
  }

  if (form.coverFile) {
    payload.cover_file = form.coverFile
  } else if (form.removeCover) {
    payload.remove_cover = true
  }

  return payload
}

const validateBeforeSave = () => {
  if (!form.title.trim()) return 'Bạn chưa nhập tiêu đề quiz.'
  if (form.visibility === 'group' && !form.roomCode.trim()) return 'Quiz dạng group cần có room code.'
  if (questions.value.length === 0) return 'Quiz cần ít nhất 1 câu hỏi.'

  for (const [index, question] of questions.value.entries()) {
    if (!question.text.trim()) return `Câu ${index + 1} chưa có nội dung.`
    if (question.answers.length < 2) return `Câu ${index + 1} cần ít nhất 2 đáp án.`
    if (question.answers.some((answer) => !answer.text.trim())) return `Câu ${index + 1} còn đáp án trống.`
    if (!question.answers.some((answer) => answer.key === question.correct)) return `Câu ${index + 1} chưa chọn đáp án đúng.`
  }

  return ''
}

const saveQuiz = async () => {
  errorMessage.value = validateBeforeSave()
  successMessage.value = ''

  if (errorMessage.value) return

  isSaving.value = true

  try {
    const payload = makePayload()
    const saved = isEditMode.value ? await quizzesApi.update(route.params.id, payload) : await quizzesApi.create(payload)
    successMessage.value = isEditMode.value ? 'Đã cập nhật quiz.' : 'Đã tạo quiz mới.'
    router.push(`${questionBase.value}/edit/${saved.id}`)
  } catch (error) {
    errorMessage.value = `Lưu thất bại: ${error.message}`
  } finally {
    isSaving.value = false
  }
}

const loadQuizForEdit = async () => {
  if (!isEditMode.value) return

  try {
    const quiz = await quizzesApi.get(route.params.id)
    form.title = quiz.title || ''
    form.tag = quiz.tag || ''
    form.description = quiz.description || ''
    form.cover = quiz.cover || ''
    form.coverFile = null
    form.removeCover = false
    coverPreview.value = ''
    form.durationMinutes = quiz.duration_minutes || Math.ceil((quiz.time_limit_seconds || 720) / 60)
    form.difficulty = quiz.difficulty || 'medium'
    form.category = quiz.category || 'Khoa học'
    form.visibility = quiz.visibility || 'public'
    form.roomCode = quiz.room_code || ''

    questions.value = (quiz.questions || []).map((question) => {
      const normalized = normalizeQuestion(question)
      return {
        localId: `q-${normalized.id}`,
        id: normalized.id,
        text: normalized.question,
        correct: normalized.correct || 'A',
        points: normalized.points || 10,
        answers: normalized.answers.map((answer) => ({ id: answer.id, key: answer.key, text: answer.text })),
      }
    })

    if (questions.value.length === 0) questions.value = [makeBlankQuestion()]
  } catch (error) {
    errorMessage.value = `Không tải được quiz để sửa: ${error.message}`
  }
}

const loadOcrDraft = () => {
  if (isEditMode.value) return

  const text = sessionStorage.getItem('quizflex_ocr_text')
  if (!text) return

  form.description = text.slice(0, 500)
  questions.value = [makeBlankQuestion()]
}

const syncCreatorProfile = (event) => {
  creatorProfile.value = event?.detail ?? currentUserStorage.get() ?? { name: 'Guest', email: '', avatar: '' }
}

onMounted(async () => {
  syncCreatorProfile()
  window.addEventListener('quizflex-user-updated', syncCreatorProfile)
  window.addEventListener('storage', syncCreatorProfile)
  await loadQuizForEdit()
  loadOcrDraft()
})

onBeforeUnmount(() => {
  revokeCoverPreview()
  window.removeEventListener('quizflex-user-updated', syncCreatorProfile)
  window.removeEventListener('storage', syncCreatorProfile)
})
</script>
