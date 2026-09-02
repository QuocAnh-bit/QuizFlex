<template>
  <section class="mx-auto grid w-full max-w-[1400px] gap-6 min-w-0 xl:grid-cols-[minmax(0,1fr)_380px]">
    <!-- LOADING -->
    <div
      v-if="isLoadingData"
      class="relative min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm xl:col-span-2"
    >
      <div class="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-4 border-indigo-500 border-t-transparent"></div>
      <p class="text-sm font-medium text-slate-600">
        {{ isCreateMode ? 'Đang chuẩn bị câu hỏi mới...' : `Đang tải dữ liệu câu hỏi #${questionId}...` }}
      </p>
    </div>

    <!-- ERROR -->
    <div
      v-else-if="fetchError"
      class="relative min-w-0 overflow-hidden rounded-2xl border border-rose-200 bg-rose-50 p-8 shadow-sm xl:col-span-2"
    >
      <div class="flex items-start gap-3">
        <div class="rounded-lg bg-rose-500/10 p-2 text-rose-600">
          <AlertTriangle class="h-5 w-5" />
        </div>
        <div class="grid gap-1">
          <span class="text-sm font-bold text-slate-900">Không thể tải câu hỏi</span>
          <span class="text-sm text-slate-600">{{ fetchError }}</span>
        </div>
      </div>
      <div class="mt-5">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 cursor-pointer"
          @click="goBack"
        >
          <ArrowLeft class="h-4 w-4" />
          Quay lại kho câu hỏi
        </button>
      </div>
    </div>

    <template v-else>
      <!-- MAIN FORM -->
      <form
        class="relative min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8"
        @submit.prevent="saveQuestion"
      >
        <div class="grid gap-6">
          <!-- Back + Title -->
          <div>
            <button
              type="button"
              class="mb-3 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-indigo-600 cursor-pointer"
              @click="goBack"
            >
              <ArrowLeft class="h-4 w-4" />
              Quay lại kho câu hỏi
            </button>

            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-purple-500/10 text-purple-500">
                <Pencil class="h-5 w-5" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2.5">
                  <h1 class="flex items-center gap-2.5 text-3xl font-black tracking-[-0.04em] text-[var(--text)]">
                    {{ isCreateMode ? 'Tạo câu hỏi mới' : `Chỉnh sửa câu hỏi #${questionId}` }}
                    <span class="inline-block h-2 w-2 rounded-full bg-indigo-500"></span>
                  </h1>

                  <span
                    v-if="originalQuestion?.is_locked_by_admin"
                    class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700"
                  >
                    <Lock class="h-3.5 w-3.5" />
                    Đã bị Admin gỡ công khai
                  </span>
                </div>

                <p class="mt-1 text-sm font-medium leading-6 text-[var(--muted)]">
                  {{ isCreateMode ? 'Nhập nội dung, đáp án và thông tin phân loại. Câu hỏi mới sẽ được lưu riêng tư vào Kho câu hỏi của bạn.' : 'Cập nhật nội dung câu hỏi, các lựa chọn đáp án và thông tin phân loại. Sau khi lưu, bạn có thể gửi duyệt lại từ trang Kho câu hỏi của tôi.' }}
                </p>
              </div>
            </div>
          </div>

          <!-- 1. Nội dung câu hỏi -->
          <div class="grid gap-3 pt-1">
            <label for="edit-question-content" class="flex items-center gap-2 text-base font-semibold text-slate-900">
              <span class="h-4 w-1 rounded-full bg-indigo-500"></span>
              1. Nội dung câu hỏi
            </label>
            <div
              id="edit-question-content"
              class="rounded-xl border border-slate-200 bg-white px-4 py-3 transition focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/15"
            >
              <MixedContentEditor
                v-model="form.content"
                compact
                placeholder="Nhập nội dung câu hỏi tại đây..."
                @edit-formula="openFormulaEditor(form, $event)"
              />
              <button type="button" class="mt-2 inline-flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-semibold text-indigo-600 hover:bg-indigo-50" @click="openFormulaEditor(form)">
                <Sigma class="h-3.5 w-3.5" /> Chèn công thức
              </button>
            </div>
            <QuestionImageUploader v-model="form.image_url" label="Hình ảnh minh họa cho câu hỏi (Tùy chọn)" />
          </div>

          <!-- 2. Phân loại -->
          <div class="grid gap-4 border-t border-slate-200 pt-5">
            <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900">
              <span class="h-4 w-1 rounded-full bg-indigo-500"></span>
              2. Phân loại
            </h2>

            <div class="grid gap-4 md:grid-cols-2">
              <label class="grid gap-1.5 text-xs font-medium text-slate-600">
                Cấp học
                <select
                  v-model="form.education_level_id"
                  class="w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15"
                  @change="onLevelChange"
                >
                  <option value="">Tất cả cấp học</option>
                  <option v-for="level in taxonomyLevels" :key="level.id" :value="level.id">
                    {{ level.name }}
                  </option>
                </select>
              </label>

              <label class="grid gap-1.5 text-xs font-medium text-slate-600">
                Khối lớp
                <select
                  v-model="form.grade_id"
                  class="w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15"
                  @change="onGradeSubjectChange"
                >
                  <option value="">Tất cả khối lớp</option>
                  <option v-for="grade in availableGrades" :key="grade.id" :value="grade.id">
                    {{ grade.name }}
                  </option>
                </select>
              </label>

              <label class="grid gap-1.5 text-xs font-medium text-slate-600">
                <span class="flex items-center gap-1 font-semibold text-slate-700">
                  Bộ môn
                  <span class="text-rose-500 font-bold">*</span>
                </span>
                <select
                  id="edit-question-subject-select"
                  v-model="form.subject_id"
                  class="w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15"
                  @change="onGradeSubjectChange"
                >
                  <option value="">-- Chọn bộ môn --</option>
                  <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">
                    {{ subject.name }}
                  </option>
                </select>
              </label>

              <label class="grid gap-1.5 text-xs font-medium text-slate-600">
                Độ khó
                <select
                  v-model="form.difficulty"
                  class="w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15"
                >
                  <option value="easy">Dễ (Nhận biết)</option>
                  <option value="medium">Vừa (Thông hiểu)</option>
                  <option value="hard">Khó (Vận dụng)</option>
                </select>
              </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
              <label class="grid gap-1.5 text-xs font-medium text-slate-600">
                Chọn Chủ đề có sẵn (từ Ngân hàng)
                <select
                  v-model="selectedBankTopic"
                  class="w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15"
                  @change="onBankTopicSelect"
                >
                  <option value="">-- Chọn chủ đề từ kho --</option>
                  <option
                    v-for="top in topicsList"
                    :key="top.topic_name || top"
                    :value="top.topic_name || top"
                  >
                    {{ top.topic_name || top }}{{ top.total_questions ? ` (${top.total_questions} câu)` : '' }}
                  </option>
                </select>
              </label>

              <label class="grid gap-1.5 text-xs font-medium text-slate-600">
                Tên Chủ đề (hoặc nhập chủ đề mới)
                <input
                  v-model="form.topic_name"
                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15"
                  placeholder="VD: Hàm số, Tiếng Anh B1, Lịch sử Việt Nam..."
                  @input="onTopicInputChange"
                />
              </label>
            </div>
          </div>

          <!-- 3. Đáp án -->
          <div id="edit-answers-section" class="grid gap-4 border-t border-slate-200 pt-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                <span class="h-4 w-1 rounded-full bg-indigo-500"></span>
                <span>3. Đáp án</span>
              </h2>

              <div class="flex flex-wrap items-center gap-2.5">
                <!-- Question Type Segmented Switcher -->
                <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1 text-xs font-semibold">
                  <button
                    type="button"
                    class="rounded-lg px-3 py-1.5 transition cursor-pointer"
                    :class="form.type === 'single_choice'
                      ? 'bg-white text-indigo-600 font-bold shadow-xs border border-slate-200/80'
                      : 'text-slate-600 hover:text-slate-900'"
                    @click="switchQuestionType('single_choice')"
                  >
                    1 đáp án đúng
                  </button>
                  <button
                    type="button"
                    class="rounded-lg px-3 py-1.5 transition cursor-pointer"
                    :class="form.type === 'multi_choice'
                      ? 'bg-white text-indigo-600 font-bold shadow-xs border border-slate-200/80'
                      : 'text-slate-600 hover:text-slate-900'"
                    @click="switchQuestionType('multi_choice')"
                  >
                    Nhiều đáp án đúng
                  </button>
                </div>

              </div>
            </div>

            <!-- Type Hint Callout -->
            <div
              v-if="form.type === 'multi_choice'"
              class="rounded-xl border border-purple-200 bg-purple-50/70 px-3.5 py-2 text-xs text-purple-900 flex items-center gap-2"
            >
              <span class="font-bold uppercase tracking-wider text-[10px] bg-purple-200 text-purple-800 px-1.5 py-0.5 rounded">Nhiều đáp án</span>
              <span>Bạn có thể đánh dấu chọn <strong>từ 2 đáp án đúng trở lên</strong> bằng cách tick vào các ô vuông bên phải.</span>
            </div>

            <AnswerList
              :question="form"
              :mode="form.type === 'multi_choice' ? 'multi' : 'single'"
              @add-answer="addAnswerChoice"
              @remove-answer="removeAnswerChoice"
              @edit-formula="openFormulaEditor($event.target, $event.formula)"
            />
          </div>

          <!-- 4. Lưu trữ & Kiểm duyệt -->
          <div class="grid gap-4 border-t border-slate-200 pt-5">
            <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900">
              <span class="h-4 w-1 rounded-full bg-[#7C3AED]"></span>
              4. Lưu trữ & Kiểm duyệt
            </h2>

            <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-4">
              <div class="flex items-start gap-3">
                <div class="rounded-lg bg-amber-500/10 p-2 text-amber-600">
                  <Lock class="h-5 w-5" />
                </div>
                <div class="grid gap-1">
                  <span class="text-sm font-bold text-slate-900">
                    Lưu vào Kho câu hỏi cá nhân (Riêng tư)
                  </span>
                  <span class="text-xs leading-relaxed text-slate-600">
                    Mọi chỉnh sửa sẽ được lưu trong kho cá nhân của bạn. Để đưa câu hỏi vào
                    <strong>Ngân hàng câu hỏi chung</strong>, bạn có thể bấm
                    <strong>"Gửi duyệt vào Ngân hàng"</strong> trong trang Kho câu hỏi của tôi sau khi lưu.
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer actions (in-form) -->
          <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 pt-6">
            <button
              type="button"
              class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 cursor-pointer"
              @click="goBack"
            >
              Hủy
            </button>

            <button
              type="submit"
              class="rounded-xl bg-[#7C3AED] px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-[#7C3AED]/20 transition hover:bg-[#6D28D9] disabled:opacity-50 cursor-pointer"
              :disabled="isSubmitting"
            >
              {{ isSubmitting ? 'Đang lưu...' : (isCreateMode ? 'Lưu câu hỏi' : 'Lưu thay đổi') }}
            </button>
          </div>
        </div>
      </form>

      <!-- Floating Sticky Action Bar (Luôn hiển thị khi cuộn trang lên/xuống) -->
      <div
        class="fixed bottom-5 right-5 sm:right-8 z-40 flex items-center gap-2.5 rounded-2xl border border-slate-200/90 bg-white/95 p-2 px-3 sm:px-4 shadow-xl shadow-purple-900/10 backdrop-blur-md transition"
      >
        <button
          type="button"
          class="hidden sm:inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer"
          @click="goBack"
        >
          Hủy
        </button>

        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-xl bg-[#7C3AED] hover:bg-[#6D28D9] px-4 sm:px-5 py-2 text-xs font-bold text-white shadow-md shadow-purple-500/25 transition disabled:opacity-50 cursor-pointer active:scale-95"
          :disabled="isSubmitting"
          @click="saveQuestion"
        >
          <Save class="h-3.5 w-3.5" />
          <span>{{ isSubmitting ? 'Đang lưu...' : (isCreateMode ? 'Lưu câu hỏi' : 'Lưu thay đổi') }}</span>
        </button>
      </div>

      <!-- LIVE PREVIEW -->
      <aside class="grid content-start gap-5">
        <article class="sticky top-6 grid gap-5 overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <!-- Header -->
          <div class="flex items-center justify-between border-b border-slate-200 pb-4">
            <h3 class="text-base font-semibold text-slate-900">Live Preview</h3>
            <span class="inline-flex items-center gap-1.5 rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
              <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
              Live
            </span>
          </div>

          <!-- Meta path -->
          <div
            v-if="selectedLevelName || selectedGradeName || selectedSubjectName"
            class="flex flex-wrap items-center gap-1.5 text-xs font-medium text-slate-500"
          >
            <span v-if="selectedLevelName" class="font-semibold text-slate-800">{{ selectedLevelName }}</span>
            <span v-if="selectedLevelName && (selectedGradeName || selectedSubjectName)" class="text-slate-300">•</span>
            <span v-if="selectedGradeName" class="font-semibold text-slate-800">{{ selectedGradeName }}</span>
            <span v-if="selectedGradeName && selectedSubjectName" class="text-slate-300">•</span>
            <span v-if="selectedSubjectName" class="font-semibold text-slate-800">{{ selectedSubjectName }}</span>
          </div>

          <!-- Badges -->
          <div class="flex flex-wrap items-center gap-2">
            <!-- Question Type Badge -->
            <span
              class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-bold"
              :class="form.type === 'multi_choice'
                ? 'border-purple-200 bg-purple-50 text-purple-700'
                : 'border-slate-200 bg-slate-50 text-slate-600'"
            >
              {{ form.type === 'multi_choice' ? 'Nhiều đáp án' : '1 đáp án' }}
            </span>

            <span
              class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-semibold"
              :class="{
                'border-emerald-200 bg-emerald-50 text-emerald-700': form.difficulty === 'easy',
                'border-amber-200 bg-amber-50 text-amber-700': form.difficulty === 'medium',
                'border-rose-200 bg-rose-50 text-rose-700': form.difficulty === 'hard'
              }"
            >
              <span
                class="h-1.5 w-1.5 rounded-full"
                :class="{
                  'bg-emerald-500': form.difficulty === 'easy',
                  'bg-amber-500': form.difficulty === 'medium',
                  'bg-rose-500': form.difficulty === 'hard'
                }"
              ></span>
              {{ difficultyText(form.difficulty) }}
            </span>

            <span
              class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-semibold"
              :class="form.is_public
                ? 'border-slate-300 bg-slate-100 text-slate-800'
                : 'border-amber-200 bg-amber-50 text-amber-700'"
            >
              <component :is="form.is_public ? Globe : Lock" class="h-3.5 w-3.5" />
              {{ form.is_public ? 'Công khai' : 'Riêng tư' }}
            </span>

            <span
              v-if="form.topic_name"
              class="inline-flex max-w-[200px] items-center gap-1 truncate rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600"
              :title="form.topic_name"
            >
              <span class="font-semibold text-slate-400">#</span>
              <span class="truncate">{{ form.topic_name }}</span>
            </span>
          </div>

          <!-- Question content -->
          <div class="border-y border-slate-200 py-4 space-y-3">
            <div class="text-sm font-semibold leading-relaxed text-slate-900 break-words">
              <MathText v-if="form.content.trim()" :content="form.content" class="block" />
              <span v-else class="text-sm font-normal italic text-slate-400">
                [Nội dung câu hỏi sẽ xuất hiện tại đây khi bạn nhập...]
              </span>
            </div>
            <div v-if="form.image_url" class="flex w-full justify-center pb-1 pt-2"><QuestionImage :src="form.image_url" size="compact" max-height="max-h-40 sm:max-h-48 max-w-full" rounded="rounded-xl" container-bg-class="bg-slate-50/70" container-border-class="border border-slate-200/80 shadow-2xs hover:border-purple-300" allow-zoom /></div>
          </div>

          <!-- Answers preview -->
          <div class="grid gap-2.5">
            <div class="flex items-center justify-between px-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
              <span>Phương án đáp án</span>
              <span>{{ form.answers.length }} phương án</span>
            </div>

            <div
              v-for="ans in form.answers"
              :key="ans.key"
              class="flex items-center gap-3 rounded-xl border p-3 text-xs transition"
              :class="ans.is_correct
                ? 'border-emerald-200 bg-emerald-50'
                : 'border-slate-200 bg-slate-50'"
            >
              <span
                class="grid h-7 w-7 shrink-0 place-items-center rounded-lg text-xs font-bold"
                :class="ans.is_correct
                  ? 'bg-emerald-500 text-white'
                  : 'border border-slate-200 bg-white text-slate-700'"
              >
                {{ ans.key }}
              </span>

              <span class="min-w-0 flex-1 text-xs font-medium">
                <MathText
                  v-if="ans.content.trim()"
                  :content="ans.content"
                  compact
                  :class="ans.is_correct ? 'font-semibold text-emerald-800' : 'text-slate-700'"
                />
                <span v-else class="italic text-slate-400">Nhập nội dung đáp án {{ ans.key }}...</span>
              </span>

              <span
                v-if="ans.is_correct"
                class="inline-flex shrink-0 items-center gap-1 rounded-md border border-emerald-200 bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700"
              >
                <Check class="h-3 w-3" />
                Đáp án đúng
              </span>
            </div>
          </div>
        </article>
      </aside>
    </template>
    <MathFormulaEditor
      v-model="formulaDraft"
      :open="formulaEditorOpen"
      :editing="Boolean(formulaTarget?.formula)"
      @close="closeFormulaEditor"
      @confirm="applyFormula"
    />
  </section>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MathText from '@/components/MathText.vue'
import {
  ArrowLeft,
  AlertTriangle,
  Lock,
  Globe,
  Check,
  X,
  Pencil,
  Save,
  Sigma,
} from 'lucide-vue-next'
import { formatApiErrorMessage, myQuestionsApi, questionsBankApi, taxonomyApi } from '@/services/api'
import { useAppLoading } from '@/composables/useAppLoading'
import MixedContentEditor from '@/components/quiz-editor/MixedContentEditor.vue'
import MathFormulaEditor from '@/components/quiz-editor/MathFormulaEditor.vue'
import AnswerList from '@/components/quiz-editor/question-types/shared/AnswerList.vue'
import QuestionImage from '@/components/question/QuestionImage.vue'
import QuestionImageUploader from '@/components/question/QuestionImageUploader.vue'

const { beginTask, endTask } = useAppLoading()

const route = useRoute()
const router = useRouter()
const showToast = inject('showToast')
const props = defineProps({ create: Boolean })

const questionId = computed(() => Number(route.params.id))
const isCreateMode = computed(() => Boolean(props.create))
const isLoadingData = ref(true)
const fetchError = ref('')
const isSubmitting = ref(false)
const originalQuestion = ref(null)

const taxonomyLevels = ref([])
const allSubjects = ref([])
const topicsList = ref([])
const selectedBankTopic = ref('')
const formulaEditorOpen = ref(false)
const formulaDraft = ref('')
const formulaTarget = ref(null)

const openFormulaEditor = (target, formula = null) => {
  formulaTarget.value = { target, formula }
  formulaDraft.value = formula?.latex || ''
  formulaEditorOpen.value = true
}

const closeFormulaEditor = () => {
  formulaEditorOpen.value = false
  formulaTarget.value = null
}

const wrapFormula = (latex, raw = '') => raw.startsWith('$$')
  ? `$$${latex}$$`
  : raw.startsWith('\\[')
    ? `\\[${latex}\\]`
    : raw.startsWith('\\(')
      ? `\\(${latex}\\)`
      : `$${latex}$`

const applyFormula = (latex) => {
  const current = formulaTarget.value
  if (!current?.target) return
  const content = current.target.content || ''
  if (current.formula) {
    current.target.content = `${content.slice(0, current.formula.start)}${wrapFormula(latex, current.formula.raw)}${content.slice(current.formula.end)}`
  } else {
    current.target.content = `${content}${content ? ' ' : ''}${wrapFormula(latex)}`
  }
  closeFormulaEditor()
}

const form = reactive({
  content: '',
  image_url: '',
  type: 'single_choice',
  difficulty: 'medium',
  education_level_id: '',
  grade_id: '',
  subject_id: '',
  topic_name: '',
  is_public: false,
  answers: []
})

const fetchTopicsList = async () => {
  try {
    const params = {
      education_level_id: form.education_level_id || undefined,
      grade_id: form.grade_id || undefined,
      subject_id: form.subject_id || undefined,
    }
    const data = await questionsBankApi.fetchTopics(params)
    topicsList.value = Array.isArray(data) ? data : (data?.data && Array.isArray(data.data) ? data.data : [])
  } catch (e) {
    console.error('Không tải được danh sách Chủ đề từ kho:', e)
  }
}

const onBankTopicSelect = () => {
  if (selectedBankTopic.value) {
    form.topic_name = selectedBankTopic.value
  }
}

const onTopicInputChange = () => {
  const text = (form.topic_name || '').trim().toLowerCase()
  if (!text) {
    selectedBankTopic.value = ''
  } else {
    const found = topicsList.value.find(
      (t) => (t.topic_name || t || '').toString().toLowerCase() === text
    )
    selectedBankTopic.value = found ? (found.topic_name || found) : ''
  }
}

const onTaxonomyChange = () => {
  fetchTopicsList()
}

const onGradeSubjectChange = () => {
  fetchTopicsList()
}

const onLevelChange = () => {
  form.grade_id = ''
  fetchTopicsList()
}

const switchQuestionType = (newType) => {
  form.type = newType
  if (newType === 'single_choice') {
    const firstCorrectIdx = form.answers.findIndex((a) => a.is_correct)
    const targetIdx = firstCorrectIdx >= 0 ? firstCorrectIdx : 0
    form.answers.forEach((ans, idx) => {
      ans.is_correct = idx === targetIdx
    })
  }
}

const difficultyText = (diff) => {
  switch (diff) {
    case 'easy': return 'Dễ'
    case 'hard': return 'Khó'
    default: return 'Vừa'
  }
}

const availableGrades = computed(() => {
  if (!form.education_level_id) {
    return taxonomyLevels.value.flatMap(l => l.grades || [])
  }
  const level = taxonomyLevels.value.find(l => l.id === Number(form.education_level_id))
  return level ? level.grades || [] : []
})

const availableSubjects = computed(() => {
  if (!form.grade_id) {
    return allSubjects.value
  }
  const grade = availableGrades.value.find(g => g.id === Number(form.grade_id))
  return grade && grade.subjects && grade.subjects.length ? grade.subjects : allSubjects.value
})

const selectedLevelName = computed(() => {
  if (!form.education_level_id) return ''
  const item = taxonomyLevels.value.find(l => l.id === Number(form.education_level_id))
  return item ? item.name : ''
})

const selectedGradeName = computed(() => {
  if (!form.grade_id) return ''
  const item = availableGrades.value.find(g => g.id === Number(form.grade_id))
  return item ? item.name : ''
})

const selectedSubjectName = computed(() => {
  if (!form.subject_id) return ''
  const item = availableSubjects.value.find(s => s.id === Number(form.subject_id))
  return item ? item.name : ''
})

const chrKey = (idx) => String.fromCharCode(65 + idx)

const addAnswerChoice = () => {
  const nextKey = chrKey(form.answers.length)
  form.answers.push({ key: nextKey, content: '', is_correct: false })
}

const removeAnswerChoice = (targetIdx) => {
  if (form.answers.length <= 2) return
  form.answers.splice(targetIdx, 1)
  form.answers.forEach((ans, idx) => {
    ans.key = chrKey(idx)
  })
  if (!form.answers.some(a => a.is_correct)) {
    form.answers[0].is_correct = true
  }
}

const goBack = () => {
  router.push(isCreateMode.value ? '/dashboard/my-questions' : `/dashboard/my-questions?question_id=${questionId.value}`)
}

const loadQuestionDetail = async () => {
  isLoadingData.value = true
  fetchError.value = ''
  if (isCreateMode.value) {
    form.answers = [
      { key: 'A', content: '', is_correct: true },
      { key: 'B', content: '', is_correct: false },
      { key: 'C', content: '', is_correct: false },
      { key: 'D', content: '', is_correct: false },
    ]
    isLoadingData.value = false
    return
  }
  try {
    const res = await myQuestionsApi.fetchBank({ question_id: questionId.value })
    const items = res.items || []
    const q = items.find(item => item.id === questionId.value) || items[0]
    
    if (!q) {
      throw new Error(`Không tìm thấy câu hỏi #${questionId.value} trong kho cá nhân.`)
    }

    originalQuestion.value = q
    form.content = q.content || q.text || ''
    form.image_url = q.image_url || ''
    form.difficulty = q.difficulty || 'medium'
    form.education_level_id = q.education_level_id || ''
    form.grade_id = q.grade_id || ''
    form.subject_id = q.subject_id || ''
    form.topic_name = q.topic_name || ''
    selectedBankTopic.value = q.topic_name || ''
    form.is_public = Boolean(q.is_public)
    await fetchTopicsList()

    form.answers = (q.answers || []).map((ans, idx) => ({
      id: ans.id,
      key: ans.key || chrKey(idx),
      content: ans.content || ans.text || '',
      is_correct: Boolean(ans.is_correct)
    }))

    const correctAnswersCount = form.answers.filter(a => a.is_correct).length
    form.type = q.type || (correctAnswersCount > 1 ? 'multi_choice' : 'single_choice')

    if (form.answers.length === 0) {
      form.answers = [
        { key: 'A', content: '', is_correct: true },
        { key: 'B', content: '', is_correct: false },
        { key: 'C', content: '', is_correct: false },
        { key: 'D', content: '', is_correct: false },
      ]
    }
  } catch (err) {
    fetchError.value = err.message || 'Lỗi khi tải chi tiết câu hỏi.'
  } finally {
    isLoadingData.value = false
  }
}

const focusAndHighlightElement = (targetId) => {
  if (!targetId) return
  const el = document.getElementById(targetId)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' })
    if (typeof el.focus === 'function') {
      el.focus()
    }
    if (typeof el.select === 'function' && el.tagName !== 'SELECT') {
      el.select()
    }
    el.classList.add('ring-4', 'ring-rose-500/60', 'border-rose-500')
    setTimeout(() => {
      el.classList.remove('ring-4', 'ring-rose-500/60', 'border-rose-500')
    }, 2500)
  }
}

const validateBeforeSave = () => {
  if (!form.content.trim()) {
    return { message: 'Vui lòng nhập nội dung câu hỏi.', targetId: 'edit-question-content' }
  }

  if (!form.subject_id) {
    return { message: 'Vui lòng chọn Bộ môn cho câu hỏi trước khi lưu.', targetId: 'edit-question-subject-select' }
  }

  for (let i = 0; i < form.answers.length; i++) {
    if (!form.answers[i].content.trim()) {
      return {
        message: `Vui lòng nhập nội dung cho Đáp án ${form.answers[i].key || String.fromCharCode(65 + i)}.`,
        targetId: 'edit-answers-section'
      }
    }
  }

  const correctCount = form.answers.filter((a) => a.is_correct).length
  if (form.type === 'single_choice') {
    if (correctCount !== 1) {
      return { message: 'Vui lòng đánh dấu chọn đúng 1 đáp án.', targetId: 'edit-answers-section' }
    }
  } else if (form.type === 'multi_choice') {
    if (correctCount < 2) {
      return { message: 'Câu hỏi nhiều đáp án đúng cần đánh dấu ít nhất 2 đáp án đúng.', targetId: 'edit-answers-section' }
    }
  }

  return null
}

const saveQuestion = async () => {
  const errorInfo = validateBeforeSave()
  if (errorInfo) {
    if (showToast) showToast(errorInfo.message, 'error')
    focusAndHighlightElement(errorInfo.targetId)
    return
  }

  isSubmitting.value = true

  try {
    const payload = {
      content: form.content.trim(),
      image_url: form.image_url ? form.image_url.trim() : null,
      type: form.type,
      difficulty: form.difficulty,
      education_level_id: form.education_level_id || null,
      grade_id: form.grade_id || null,
      subject_id: form.subject_id || null,
      topic_name: form.topic_name ? form.topic_name.trim() : null,
      is_public: Boolean(form.is_public),
      answers: form.answers.map(ans => ({
        id: ans.id,
        content: ans.content.trim(),
        key: ans.key,
        is_correct: Boolean(ans.is_correct),
      })),
    }

    if (isCreateMode.value) {
      await myQuestionsApi.createQuestion(payload)
      if (showToast) showToast('Đã lưu câu hỏi mới vào Kho cá nhân.', 'success')
      router.push('/dashboard/my-questions?created=1')
    } else {
      await myQuestionsApi.update(questionId.value, payload)
      if (showToast) showToast('Đã lưu thay đổi thành công! Vui lòng bấm "Gửi duyệt" tại Kho câu hỏi để gửi yêu cầu kiểm duyệt lại cho Admin.', 'success')
      router.push(`/dashboard/my-questions?highlight=${questionId.value}&updated=1`)
    }
  } catch (err) {
    const msg = formatApiErrorMessage(err, 'Cập nhật thất bại. Vui lòng kiểm tra lại.')
    if (showToast) showToast(msg, 'error')
  } finally {
    isSubmitting.value = false
  }
}

onMounted(async () => {
  beginTask()
  try {
    try {
      const data = await taxonomyApi.tree()
      const payload = data?.education_levels ? data : (data?.data ?? data)
      if (payload) {
        taxonomyLevels.value = payload.education_levels || payload.educationLevels || []
        allSubjects.value = payload.subjects || []
      }
    } catch (e) {
      console.error('Không tải được taxonomy:', e)
    }
    await loadQuestionDetail()
  } finally {
    endTask()
  }
})
</script>
