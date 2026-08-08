<script setup>
import { ref, computed, onMounted } from 'vue'
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
  questions: []
})

const SUPPORTED_TYPES = ['single_choice']

const hasUnsupportedQuestionTypes = computed(() =>
  form.value.questions.some((q) => !SUPPORTED_TYPES.includes(q.type))
)

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
      visibility: quiz.room_code ? 'group' : (quiz.is_public ? 'public' : 'private'),

      questions: (quiz.questions || []).map(q => ({
        id: q.id,
        content: q.content,
        type: q.type,
        points: q.points,
        order: q.order,

        answers: (q.answers || []).map(a => ({
          id: a.id,
          content: a.content,
          is_correct: a.is_correct,
          order: a.order
        }))
      }))
    }

  } catch (err) {
    console.error(err)
    showToast('Không tải được quiz', 'error')
  } finally {
    loading.value = false
  }
}
const addQuestion = () => {
  form.value.questions.push({
    id: null,
    content: '',
    type: 'single_choice',
    points: 10,
    order: form.value.questions.length + 1,
    answers: [
      {
        id: null,
        content: '',
        is_correct: true,
        order: 1
      },
      {
        id: null,
        content: '',
        is_correct: false,
        order: 2
      },
      {
        id: null,
        content: '',
        is_correct: false,
        order: 3
      },
      {
        id: null,
        content: '',
        is_correct: false,
        order: 4
      }
    ]
  })
}
const removeQuestion = (index) => {

  form.value.questions.splice(index,1)

}

const addAnswer = (question) => {

  question.answers.push({

    id:null,

    content:'',

    is_correct:false,

    order:question.answers.length

  })

}

const removeAnswer = (question,index)=>{

  if(question.answers.length<=2){

    showToast('Câu hỏi phải có ít nhất 2 đáp án','error')

    return

  }

  question.answers.splice(index,1)

}

const setCorrect=(question,answer)=>{

  question.answers.forEach(item=>{

      item.is_correct=false

  })

  answer.is_correct=true

}

const validateBeforeSave = () => {
  if (!form.value.title.trim()) return 'Bạn chưa nhập tên quiz.'

  if (form.value.questions.length === 0) return 'Quiz cần ít nhất 1 câu hỏi.'

  for (const [index, question] of form.value.questions.entries()) {
    if (!question.content.trim()) return `Câu ${index + 1} chưa có nội dung.`

    if (question.answers.length < 2) return `Câu ${index + 1} cần ít nhất 2 đáp án.`

    if (question.answers.some((answer) => !answer.content.trim())) {
      return `Câu ${index + 1} còn đáp án trống.`
    }

    if (!question.answers.some((answer) => answer.is_correct)) {
      return `Câu ${index + 1} chưa chọn đáp án đúng.`
    }
  }

  return ''
}

const saveQuiz = async () => {

  const validationError = validateBeforeSave()
  if (validationError) {
    showToast(validationError, 'error')
    return
  }

  if (hasUnsupportedQuestionTypes.value) {
    showToast('Quiz này có câu hỏi "Nhiều đáp án" hoặc "Điền đáp án" — vui lòng sửa bằng Quiz Editor chính để tránh mất dữ liệu.', 'error')
    return
  }

  try{

      saving.value=true

      await api.put(`/quizzes/${route.params.id}`,{

          title: form.value.title.trim(),

          description: form.value.description.trim(),

          category: form.value.category.trim(),

          difficulty:form.value.difficulty,

          visibility:form.value.visibility,

          questions:form.value.questions

      })

      showToast('Cập nhật thành công')

      setTimeout(()=>{

          router.push('/admin/report-tickets')

      },1200)

  }

  catch(err){

      console.error(err)

      showToast('Cập nhật thất bại','error')

  }

  finally{

      saving.value=false

  }

}

onMounted(fetchQuiz)
</script>
<template>
  <section class="grid gap-6 min-w-0">

    <!-- Loading -->
    <div
      v-if="loading"
      class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]"
    >
      Đang tải thông tin quiz...
    </div>

    <div v-else class="max-w-5xl w-full min-w-0 mx-auto">

      <!-- Header -->

      <div
        class="relative min-w-0 overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 sm:p-8 shadow-[var(--shadow-soft)] backdrop-blur-2xl mb-6"
      >

        <div
          class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-amber-500/10 blur-3xl"
        ></div>

        <div
          class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end"
        >

          <div>

            <p
              class="text-xs font-black uppercase tracking-[0.2em] text-amber-400"
            >
              Edit Quiz
            </p>

            <h1
              class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]"
            >
              Sửa Quiz
            </h1>

            <p
              class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]"
            >
              Bạn có thể sửa thông tin quiz, câu hỏi và đáp án.
            </p>

          </div>

          <RouterLink
            to="/admin/quizzes"
            class="btn-ghost"
          >
            ← Quay lại
          </RouterLink>

        </div>

      </div>

      <!-- Card -->

      <article
        class="min-w-0 rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 sm:p-8 shadow-[var(--shadow-card)] backdrop-blur-2xl space-y-6"
      >

        <div>

          <label class="block text-sm font-bold mb-2">
            Tên Quiz
          </label>

          <input
            v-model="form.title"
            class="field w-full"
          >

        </div>

        <div>

          <label class="block text-sm font-bold mb-2">
            Mô tả
          </label>

          <textarea
            v-model="form.description"
            rows="3"
            class="field w-full"
          >
          </textarea>


        </div>

        <div>

          <label class="block text-sm font-bold mb-2">
            Danh mục
          </label>

          <input
            v-model="form.category"
            class="field w-full"
          >

        </div>

        <div class="grid grid-cols-2 gap-5">

          <div>

            <label class="block text-sm font-bold mb-2">
              Độ khó
            </label>

            <select
              v-model="form.difficulty"
              class="field w-full"
            >
              <option value="easy">Dễ</option>
              <option value="medium">Vừa</option>
              <option value="hard">Khó</option>
            </select>

          </div>

          <div>

            <label class="block text-sm font-bold mb-2">
              Hiển thị
            </label>

            <select
              v-model="form.visibility"
              class="field w-full"
            >
              <option value="public">
                🌐 Public
              </option>

              <option value="private">
                🔒 Private
              </option>

              <option value="group">
                👥 Group
              </option>

            </select>

          </div>

        </div>

        <div
          class="border-t border-[var(--border)] pt-6"
        >

          <div
            class="flex items-center justify-between"
          >

            <h2
              class="text-2xl font-black"
            >
              Danh sách câu hỏi
            </h2>
<button
  class="btn-primary"
  @click="addQuestion"
>
  ➕ Thêm câu hỏi
</button>
</div>
          </div>

          <!-- 1B-2 sẽ bắt đầu từ đây -->
           <div
  v-for="(question, qIndex) in form.questions"
  :key="question.id ?? qIndex"
  class="mt-6 min-w-0 rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-5 sm:p-6 space-y-5"
>

  <div class="flex items-center justify-between">

    <h3 class="text-lg font-black">
      Câu {{ qIndex + 1 }}
    </h3>

    <button
      class="btn-ghost text-red-500"
      @click="removeQuestion(qIndex)"
    >
      🗑 Xóa
    </button>

  </div>

  <div v-if="hasUnsupportedQuestionTypes" class="mt-4 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm font-bold text-amber-400">
    ⚠️ Quiz này có câu hỏi dạng "Nhiều đáp án" hoặc "Điền đáp án" — trang này chưa hỗ trợ sửa đầy đủ. Dùng Quiz Editor chính để tránh mất dữ liệu khi lưu.
  </div>

  <!-- Nội dung -->

  <div>

    <label class="block text-sm font-bold mb-2">
      Nội dung câu hỏi
    </label>

    <textarea
      v-model="question.content"
      rows="2"
      class="field w-full"
    />

  </div>

  <!-- Điểm -->

  <div class="grid grid-cols-2 gap-4">

    <div>

      <label class="block text-sm font-bold mb-2">
        Điểm
      </label>

      <input
        type="number"
        min="1"
        class="field w-full"
        v-model="question.points"
      >

    </div>

    <div>

      <label class="block text-sm font-bold mb-2">
        Loại câu hỏi
      </label>

      <select
        class="field w-full"
        v-model="question.type"
        :disabled="question.type !== 'single_choice'"
      >
        <option value="single_choice">
          Một đáp án
        </option>
      </select>

      <p v-if="question.type !== 'single_choice'" class="mt-2 text-xs font-bold text-amber-500">
        ⚠️ Loại câu hỏi này chưa được hỗ trợ sửa ở trang này. Vui lòng dùng Quiz Editor chính.
      </p>

    </div>

  </div>

  <!-- Đáp án -->

  <div>

    <div
      class="flex items-center justify-between mb-3"
    >

      <h4 class="font-bold">
        Đáp án
      </h4>

      <button
        class="btn-ghost"
        @click="addAnswer(question)"
      >
        + Thêm đáp án
      </button>

    </div>

    <div

      v-for="(answer,aIndex) in question.answers"

      :key="answer.id ?? aIndex"

      class="flex items-center gap-3 mb-3"

    >

      <input

        type="radio"

        :name="'correct-'+qIndex"

        :checked="answer.is_correct"

        @change="setCorrect(question,answer)"

      >

      <input

        v-model="answer.content"

        class="field flex-1"

        placeholder="Nhập đáp án..."

      >

      <button

        class="btn-ghost text-red-500"

        @click="removeAnswer(question,aIndex)"

      >

        ✖

      </button>

    </div>

  </div>

</div>

<!-- <div class="pt-8 border-t border-[var(--border)]">

  <div class="flex gap-3">

    <button

      class="btn-primary"

      :disabled="saving || hasUnsupportedQuestionTypes"

      @click="saveQuiz"

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

</div> -->

</article>

</div>
  <div class="fixed bottom-4 right-4 z-[60] flex flex-col gap-2 rounded-[1.4rem] border border-[var(--border)] bg-[var(--surface)]/95 p-3 shadow-[var(--shadow-card)] backdrop-blur-xl">
    <RouterLink to="/admin/report-tickets" class="btn-ghost !px-4 !py-2 text-xs text-center">
      Hủy
    </RouterLink>
    <button class="btn-primary !px-4 !py-2 text-xs" type="button" :disabled="saving || hasUnsupportedQuestionTypes" @click="saveQuiz">
      {{ saving ? 'Đang lưu...' : '💾 Lưu thay đổi' }}
    </button>
  </div>

<transition name="slide-up">

  <div

    v-if="toast.isOpen"

    class="fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-2xl border px-4 py-3 shadow-lg backdrop-blur-xl"

    :class="toast.type==='success'
      ?'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
      :'border-rose-500/30 bg-rose-500/10 text-rose-400'"

  >

    <span>

      {{ toast.type==='success' ? '✅' : '❌' }}

    </span>

    <span class="font-bold">

      {{ toast.message }}

    </span>

  </div>

</transition>

</section>

</template>
<!-- 
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
</style> -->

<style scoped>
.field{
  width:100%;
  border:1px solid var(--border);
  background:var(--input-bg);
  border-radius:16px;
  padding:12px 16px;
  color:var(--text);
  font-size:.95rem;
  font-weight:600;
  outline:none;
  transition:.25s;
}

.field:focus{
  border-color:var(--border-strong);
  box-shadow:0 0 0 4px rgba(245,158,11,.12);
}

textarea.field{
  resize:vertical;
  min-height:90px;
}

.btn-primary{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:.5rem;
  padding:.8rem 1.3rem;
  border-radius:16px;
  font-weight:700;
  color:#fff;
  background:linear-gradient(135deg,#f59e0b,#f97316);
  transition:.25s;
}

.btn-primary:hover{
  transform:translateY(-2px);
  box-shadow:0 14px 28px rgba(245,158,11,.25);
}

.btn-primary:disabled{
  opacity:.6;
  cursor:not-allowed;
}

.btn-ghost{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:.5rem;
  padding:.8rem 1.2rem;
  border-radius:16px;
  border:1px solid var(--border);
  background:transparent;
  color:var(--text);
  font-weight:700;
  transition:.25s;
}

.btn-ghost:hover{
  background:rgba(255,255,255,.05);
}

.question-card{
  border:1px solid var(--border);
  border-radius:24px;
  padding:24px;
  background:var(--surface);
  transition:.25s;
}

.question-card:hover{
  border-color:var(--border-strong);
  transform:translateY(-2px);
}

.answer-item{
  display:flex;
  align-items:center;
  gap:12px;
  margin-bottom:12px;
}

.answer-item input[type="radio"]{
  width:18px;
  height:18px;
  cursor:pointer;
}

.slide-up-enter-active,
.slide-up-leave-active{
  transition:all .3s cubic-bezier(.16,1,.3,1);
}

.slide-up-enter-from,
.slide-up-leave-to{
  opacity:0;
  transform:translateY(20px);
}

@media(max-width:768px){

  .btn-primary,
  .btn-ghost{
    width:100%;
  }

}
</style>