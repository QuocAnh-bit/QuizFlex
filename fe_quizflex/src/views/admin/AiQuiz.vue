<template>
  <section class="max-w-6xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Trí tuệ nhân tạo</p>
        <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Tạo đề thi bằng AI</h1>
        <p class="mt-1 text-sm text-slate-600">Nhập chủ đề hoặc tài liệu, AI sẽ tự động sinh ngân hàng câu hỏi và tạo quiz hoàn chỉnh.</p>
      </div>
      <router-link :to="`${questionBase}/ocr`" class="btn-secondary inline-flex items-center gap-2 text-xs px-3.5 py-1.5">
        <FileText class="h-3.5 w-3.5" />
        <span>Chuyển sang OCR từ tài liệu</span>
      </router-link>
    </div>

    <!-- Quota Stats & Limit Row -->
    <div class="grid gap-4 sm:grid-cols-3">
      <router-link 
        v-for="stat in quotaStats" 
        :key="stat.label" 
        to="/profile?tab=subscription"
        class="card p-4 space-y-1 hover:border-[#7C3AED] transition"
      >
        <span class="text-[10px] font-bold uppercase text-slate-400 block">{{ stat.label }}</span>
        <strong class="text-xl font-black text-slate-900 block">{{ stat.value }}</strong>
        <span class="text-[10px] font-bold text-[#7C3AED] block">Xem gói nâng cấp →</span>
      </router-link>
    </div>

    <!-- Main Generator & Preview Layout -->
    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
      <!-- Left: Generator Form -->
      <article class="card p-6 sm:p-8 space-y-5">
        <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Cấu hình yêu cầu sinh đề</h2>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-purple-100 bg-gradient-to-br from-purple-50 via-white to-indigo-50 px-5 py-5">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-sm font-black text-slate-900">Chọn chương trình học</p>
                <p class="mt-1 text-[11px] leading-5 text-slate-500">
                  Hệ thống dùng thông tin này để tìm đúng ngữ cảnh RAG trước khi gọi AI.
                </p>
              </div>
              <span class="shrink-0 rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase tracking-wide text-[#7C3AED] shadow-sm ring-1 ring-purple-100">
                {{ currentTaxonomyStep <= 4 ? `Bước ${currentTaxonomyStep}/4` : 'Đã hoàn tất' }}
              </span>
            </div>

            <div class="mt-5 grid grid-cols-4 gap-2">
              <button
                v-for="step in taxonomySteps"
                :key="step.number"
                type="button"
                class="group flex min-w-0 flex-col items-center gap-1.5 rounded-xl px-1 py-2 transition"
                :class="step.number === currentTaxonomyStep
                  ? 'bg-white shadow-sm ring-1 ring-purple-100'
                  : 'hover:bg-white/70'"
                :disabled="step.number >= currentTaxonomyStep || isGenerating || isPolling"
                @click="goToTaxonomyStep(step.number)"
              >
                <span
                  class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-black transition"
                  :class="step.number < currentTaxonomyStep
                    ? 'bg-[#7C3AED] text-white shadow-sm shadow-purple-200'
                    : step.number === currentTaxonomyStep
                      ? 'bg-purple-100 text-[#7C3AED] ring-4 ring-purple-50'
                      : 'bg-slate-100 text-slate-400'"
                >
                  {{ step.number < currentTaxonomyStep ? '✓' : step.number }}
                </span>
                <span
                  class="truncate text-[10px] font-bold"
                  :class="step.number <= currentTaxonomyStep ? 'text-slate-700' : 'text-slate-400'"
                >
                  {{ step.label }}
                </span>
              </button>
            </div>
          </div>

          <div class="p-5">
            <div v-if="taxonomyLoading" class="flex items-center gap-3 rounded-xl bg-purple-50 px-4 py-4">
              <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-[#7C3AED]"></span>
              <p class="text-xs font-bold text-[#7C3AED]">Đang tải danh mục chương trình học...</p>
            </div>

            <div v-else-if="taxonomyError" class="rounded-xl border border-red-100 bg-red-50 p-4">
              <p class="text-xs font-bold text-red-700">{{ taxonomyError }}</p>
              <button type="button" class="mt-3 text-[11px] font-black text-red-700 underline" @click="loadTaxonomy">
                Thử tải lại
              </button>
            </div>

            <div v-else-if="currentTaxonomyStep === 1" class="space-y-3">
              <div>
                <label class="text-xs font-black text-slate-800">Bạn đang dạy ở cấp nào?</label>
                <p class="mt-1 text-[11px] text-slate-500">Chọn cấp học để lọc danh sách lớp phù hợp.</p>
              </div>
              <select
                v-model="taxonomyForm.education_level_id"
                class="field text-sm"
                :disabled="isGenerating || isPolling"
                @change="onEducationLevelChange"
              >
                <option value="">Chọn cấp học</option>
                <option v-for="level in taxonomyLevels" :key="level.id" :value="level.id">
                  {{ level.name }}
                </option>
              </select>
            </div>

            <div v-else-if="currentTaxonomyStep === 2" class="space-y-3">
              <div>
                <label class="text-xs font-black text-slate-800">Chọn lớp cần tạo đề</label>
                <p class="mt-1 text-[11px] text-slate-500">Cấp học đã chọn: {{ selectedEducationLevel?.name }}</p>
              </div>
              <select
                v-model="taxonomyForm.grade_id"
                class="field text-sm"
                :disabled="isGenerating || isPolling"
                @change="onGradeChange"
              >
                <option value="">Chọn lớp</option>
                <option v-for="grade in availableGrades" :key="grade.id" :value="grade.id">
                  {{ grade.name }}
                </option>
              </select>
            </div>

            <div v-else-if="currentTaxonomyStep === 3" class="space-y-3">
              <div>
                <label class="text-xs font-black text-slate-800">Chọn bộ môn</label>
                <p class="mt-1 text-[11px] text-slate-500">Danh sách môn học dành cho {{ selectedGrade?.name }}.</p>
              </div>
              <select
                v-model="taxonomyForm.subject_id"
                class="field text-sm"
                :disabled="isGenerating || isPolling"
                @change="onSubjectChange"
              >
                <option value="">Chọn bộ môn</option>
                <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">
                  {{ subject.name }}
                </option>
              </select>
            </div>

            <div v-else-if="currentTaxonomyStep === 4" class="space-y-4">
              <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <label class="text-xs font-black text-slate-800">Chọn chủ đề chính</label>
                  <p class="mt-1 text-[11px] text-slate-500">
                    Chủ đề được lấy từ dữ liệu đã RAG của {{ selectedSubject?.name }} - {{ selectedGrade?.name }}.
                  </p>
                </div>
                <span class="w-fit shrink-0 rounded-full bg-purple-100 px-3 py-1 text-[10px] font-black text-[#7C3AED]">
                  {{ selectedSubject?.name }}
                </span>
              </div>

              <div v-if="curriculumLoading" class="flex min-h-28 items-center justify-center gap-3 rounded-2xl border border-purple-100 bg-purple-50/60">
                <span class="h-5 w-5 animate-spin rounded-full border-2 border-purple-200 border-t-[#7C3AED]"></span>
                <p class="text-xs font-bold text-[#7C3AED]">Đang tìm chủ đề chương trình phù hợp...</p>
              </div>

              <div v-else-if="curriculumError" class="rounded-2xl border border-red-100 bg-red-50 p-4">
                <p class="text-xs font-black text-red-700">Không tải được chủ đề chương trình</p>
                <p class="mt-1 text-[11px] leading-5 text-red-600">{{ curriculumError }}</p>
                <button type="button" class="mt-3 text-[11px] font-black text-red-700 underline" @click="loadCurriculumOptions">
                  Thử tải lại
                </button>
              </div>

              <div
                v-else-if="!curriculumAvailable || curriculumOptions.length === 0"
                class="rounded-2xl border border-purple-200 bg-purple-50/50 p-5 space-y-4"
              >
                <div class="flex gap-3">
                  <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-purple-100 text-sm font-black text-[#7C3AED]">✎</span>
                  <div>
                    <p class="text-xs font-black text-slate-800">Nhập chủ đề theo yêu cầu</p>
                    <p class="mt-1 text-[11px] leading-5 text-slate-600">
                      Môn {{ selectedSubject?.name }} - {{ selectedGrade?.name }} chưa có chủ đề RAG định sẵn. Bạn hãy nhập tên chủ đề bài học để AI tạo câu hỏi:
                    </p>
                  </div>
                </div>

                <div class="rounded-xl border border-purple-200 bg-white p-3.5 space-y-2.5">
                  <label class="block text-xs font-bold text-slate-800">
                    Tên chủ đề / Bài học *
                  </label>
                  <div class="flex flex-col sm:flex-row gap-2">
                    <input
                      v-model="customTopicInput"
                      type="text"
                      class="field text-xs flex-1"
                      placeholder="Ví dụ: Các số trong phạm vi 10, Phép cộng, Hình học..."
                      @keyup.enter="confirmCustomTaxonomy"
                    />
                    <button
                      type="button"
                      class="btn-primary px-4 py-2 text-xs shrink-0 whitespace-nowrap"
                      :disabled="!customTopicInput.trim()"
                      @click="confirmCustomTaxonomy"
                    >
                      Dùng chủ đề này →
                    </button>
                  </div>
                </div>

                <div class="flex items-center justify-between pt-1 border-t border-purple-100">
                  <button type="button" class="btn-secondary px-3 py-1.5 text-[11px]" @click="goToTaxonomyStep(3)">
                    ← Chọn lại bộ môn
                  </button>
                </div>
              </div>

              <template v-else>
                <div class="flex items-center justify-between gap-2">
                  <div class="relative flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="11" cy="11" r="7"></circle>
                      <path d="m20 20-3.5-3.5"></path>
                    </svg>
                    <input
                      v-model="curriculumSearch"
                      class="field pl-9 text-xs"
                      type="search"
                      placeholder="Tìm chủ đề..."
                    />
                  </div>
                  <button
                    type="button"
                    class="text-[11px] font-bold text-[#7C3AED] hover:underline px-2 py-1 shrink-0"
                    @click="isCustomTopicMode = !isCustomTopicMode"
                  >
                    {{ isCustomTopicMode ? '← Chọn theo danh mục RAG' : '✎ Tự nhập chủ đề khác' }}
                  </button>
                </div>

                <div v-if="isCustomTopicMode" class="rounded-xl border border-purple-200 bg-white p-3.5 space-y-2.5">
                  <label class="block text-xs font-bold text-slate-800">
                    Nhập tên chủ đề bài học tùy chọn:
                  </label>
                  <div class="flex flex-col sm:flex-row gap-2">
                    <input
                      v-model="customTopicInput"
                      type="text"
                      class="field text-xs flex-1"
                      placeholder="Ví dụ: Luyện tập phép cộng trừ, Ôn thi học kỳ..."
                      @keyup.enter="confirmCustomTaxonomy"
                    />
                    <button
                      type="button"
                      class="btn-primary px-4 py-2 text-xs shrink-0 whitespace-nowrap"
                      :disabled="!customTopicInput.trim()"
                      @click="confirmCustomTaxonomy"
                    >
                      Dùng chủ đề này →
                    </button>
                  </div>
                </div>

                <template v-else>
                  <div class="flex items-center justify-between gap-3">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                      {{ filteredCurriculumOptions.length }} chủ đề phù hợp
                    </p>
                    <p v-if="selectedCurriculumOption" class="text-[10px] font-black text-emerald-600">Đã chọn 1 chủ đề</p>
                  </div>

                  <div v-if="filteredCurriculumOptions.length" class="max-h-80 space-y-2 overflow-y-auto pr-1">
                    <label
                      v-for="option in filteredCurriculumOptions"
                      :key="option._key"
                      class="block cursor-pointer rounded-2xl border p-4 transition"
                      :class="selectedCurriculumKey === option._key
                        ? 'border-[#7C3AED] bg-purple-50 shadow-sm'
                        : 'border-slate-200 bg-white hover:border-purple-200 hover:bg-slate-50'"
                    >
                      <input
                        v-model="selectedCurriculumKey"
                        class="sr-only"
                        type="radio"
                        name="curriculum-option"
                        :value="option._key"
                        :disabled="isGenerating || isPolling"
                      />
                      <span class="flex items-start gap-3">
                        <span
                          class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border"
                          :class="selectedCurriculumKey === option._key ? 'border-[#7C3AED] bg-[#7C3AED]' : 'border-slate-300 bg-white'"
                        >
                          <span v-if="selectedCurriculumKey === option._key" class="h-1.5 w-1.5 rounded-full bg-white"></span>
                        </span>
                        <span class="min-w-0 flex-1">
                          <span class="block text-xs font-black leading-5 text-slate-800">{{ option.label }}</span>
                        </span>
                      </span>
                    </label>
                  </div>

                  <div v-else class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5 text-center">
                    <p class="text-xs font-black text-slate-700">Không tìm thấy chủ đề khớp từ khóa</p>
                    <button type="button" class="mt-2 text-[11px] font-black text-[#7C3AED] underline" @click="curriculumSearch = ''">
                      Xóa từ khóa tìm kiếm
                    </button>
                  </div>

                  <div class="flex justify-end border-t border-slate-100 pt-4">
                    <button
                      type="button"
                      class="btn-primary px-5 py-2.5 text-xs"
                      :disabled="!selectedCurriculumOption"
                      @click="confirmTaxonomy"
                    >
                      Dùng chủ đề này →
                    </button>
                  </div>
                </template>
              </template>
            </div>

            <div v-else class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <div class="flex items-center gap-2">
                  <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-xs font-black text-emerald-700">✓</span>
                  <p class="text-xs font-black text-slate-900">Đã chọn đủ thông tin</p>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                  <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-bold text-slate-600">{{ selectedEducationLevel?.name }}</span>
                  <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-bold text-slate-600">{{ selectedGrade?.name }}</span>
                  <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-bold text-slate-600">{{ selectedSubject?.name }}</span>
                  <span class="rounded-full bg-purple-100 px-3 py-1 text-[10px] font-bold text-[#7C3AED]">{{ taxonomyForm.topic_name }}</span>
                </div>
              </div>
              <button
                type="button"
                class="btn-secondary shrink-0 px-4 py-2 text-[11px]"
                :disabled="isGenerating || isPolling"
                @click="goToTaxonomyStep(1)"
              >
                Chọn lại
              </button>
            </div>
          </div>
        </div>

        <div v-if="isTaxonomyComplete" class="space-y-2 rounded-2xl border border-purple-100 bg-purple-50/40 p-5">
          <div>
            <label class="text-sm font-black text-slate-900">Mô tả yêu cầu cho AI</label>
            <p class="mt-1 text-[11px] text-slate-500">Nêu dạng câu hỏi, yêu cầu đáp án hoặc tình huống bạn muốn AI tạo.</p>
          </div>
          <textarea 
            v-model="prompt" 
            class="field min-h-32 resize-none bg-white text-sm"
            rows="5"
            :disabled="isGenerating || isPolling"
            :placeholder="promptPlaceholder"
          ></textarea>
        </div>

        <div v-if="isTaxonomyComplete" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div class="space-y-1">
            <label class="text-[11px] font-bold text-slate-600 block">Số lượng câu</label>
            <select v-model.number="settings.count" class="field text-xs">
              <option :value="5">5 câu</option>
              <option :value="10">10 câu</option>
              <option :value="15">15 câu</option>
              <option :value="20">20 câu</option>
              <option :value="25">25 câu</option>
              <option :value="30">30 câu</option>
              <option :value="40">40 câu</option>
              <option :value="50">50 câu</option>
            </select>
          </div>

          <div class="space-y-1">
            <label class="text-[11px] font-bold text-slate-600 block">Độ khó</label>
            <select v-model="settings.difficulty" class="field text-xs">
              <option value="easy">Dễ</option>
              <option value="medium">Vừa</option>
              <option value="hard">Khó</option>
            </select>
          </div>

          <div class="space-y-1">
            <label class="text-[11px] font-bold text-slate-600 block">Hiển thị</label>
            <select v-model="settings.visibility" class="field text-xs">
              <option value="private">Private</option>
              <option value="public">Public</option>
              <option value="group">Group</option>
            </select>
          </div>
        </div>

        <!-- Action & Progress -->
        <div v-if="isTaxonomyComplete" class="pt-3 border-t border-slate-100 space-y-4">
          <div class="flex items-center gap-3">
            <button 
              class="btn-primary text-xs px-5 py-2.5" 
              type="button" 
              :disabled="!isTaxonomyComplete || isGenerating || isPolling"
              @click="generateQuiz"
            >
              {{ actionLabel }}
            </button>
            <span v-if="jobStatus" class="rounded bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600 uppercase">
              {{ statusLabel }}
            </span>
          </div>

          <!-- Progress bar -->
          <div v-if="isGenerating || isPolling" class="rounded-xl border border-purple-100 bg-purple-50 p-4 space-y-2">
            <div class="flex items-center justify-between text-xs">
              <span class="font-bold text-[#7C3AED]">{{ currentLabel }}</span>
              <span class="font-black text-[#7C3AED]">{{ Math.round(displayPercent) }}%</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-purple-200">
              <div 
                class="h-full rounded-full bg-[#7C3AED] transition-all duration-150" 
                :style="{ width: `${displayPercent}%` }"
              ></div>
            </div>
          </div>

          <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs font-bold text-red-700">
            {{ errorMessage }}
          </div>

          <div v-if="successMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs font-bold text-emerald-700">
            {{ successMessage }}
          </div>
        </div>
      </article>

      <!-- Right: Preview -->
      <aside class="card p-6 space-y-4">
        <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Bản xem trước câu hỏi</h2>

        <div v-if="!generatedQuiz" class="py-12 text-center text-xs text-slate-400">
          Chưa có nội dung. Hãy nhập prompt và bấm "Tạo quiz bằng AI".
        </div>

        <div v-else class="space-y-4 text-xs">
          <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 space-y-2">
            <b class="text-sm font-bold text-slate-900 block">{{ generatedQuiz.title }}</b>
            <p class="text-slate-500 line-clamp-2">{{ generatedQuiz.description || prompt }}</p>
            <div class="flex items-center gap-2 pt-1">
              <span class="rounded bg-white border border-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600">
                {{ generatedQuiz.questions?.length || 0 }} câu hỏi
              </span>
              <span class="rounded bg-white border border-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600">
                {{ Math.ceil((generatedQuiz.time_limit_seconds || 600) / 60) }} phút
              </span>
            </div>
          </div>

          <!-- Questions scrollable list -->
          <div class="max-h-[360px] overflow-y-auto space-y-3 pr-1">
            <div 
              v-for="(question, index) in generatedQuiz.questions" 
              :key="question.id || index" 
              class="rounded-xl border border-slate-100 bg-slate-50 p-3 space-y-2"
            >
              <p class="font-bold text-slate-900 leading-relaxed">
                Câu {{ index + 1 }}: <MathText :content="question.text || question.content || ''" compact />
              </p>
              <div class="space-y-1">
                <div 
                  v-for="answer in question.answers" 
                  :key="answer.id || answer.key" 
                  class="rounded-lg p-2 text-xs" 
                  :class="answer.is_correct ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'bg-white text-slate-700'"
                >
                  <span class="font-black">{{ answer.answer_key || answer.key }}.</span>
                  <MathText :content="answer.text || answer.content || ''" compact />
                </div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
            <router-link class="btn-primary text-xs text-center py-2" :to="`/quizzes/${generatedQuiz.id}/play`">
              Làm thử ngay →
            </router-link>
            <button class="btn-secondary text-xs py-2" type="button" @click="openInEditor">
              Chỉnh sửa đề
            </button>
          </div>
        </div>
      </aside>
    </div>
  </section>
</template>

<script setup>
import { useAppLoading } from '@/composables/useAppLoading'
const { beginTask, endTask } = useAppLoading()

import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { aiApi, authApi, currentUserStorage, curriculumApi, taxonomyApi } from '@/services/api'
import MathText from '@/components/MathText.vue'
import {
  Sparkles,
  FileText,
  // các icon khác đang có sẵn...
} from 'lucide-vue-next'
const route = useRoute()
const router = useRouter()
const questionBase = computed(() => route.path.startsWith('/dashboard') ? '/dashboard/questions' : '/admin/questions')

const prompt = ref('')
const MAX_PROMPT_LENGTH = 2000
const settings = ref({
  count: 10,
  difficulty: 'medium',
  language: 'vi',
  visibility: 'private',
})

const taxonomyLevels = ref([])
const taxonomyLoading = ref(false)
const taxonomyError = ref('')
const taxonomyConfirmed = ref(false)
const curriculumLoading = ref(false)
const curriculumError = ref('')
const curriculumAvailable = ref(true)
const curriculumOptions = ref([])
const curriculumSearch = ref('')
const selectedCurriculumKey = ref('')
let curriculumRequestId = 0
const taxonomySteps = [
  { number: 1, label: 'Cấp học' },
  { number: 2, label: 'Lớp' },
  { number: 3, label: 'Bộ môn' },
  { number: 4, label: 'Chủ đề' },
]
const taxonomyForm = ref({
  education_level_id: '',
  grade_id: '',
  subject_id: '',
  topic_name: '',
  curriculum_unit_ids: [],
})

const availableGrades = computed(() => {
  if (!taxonomyForm.value.education_level_id) return []

  const level = taxonomyLevels.value.find(
    (item) => item.id === Number(taxonomyForm.value.education_level_id)
  )

  return level?.grades || []
})

const selectedEducationLevel = computed(() => taxonomyLevels.value.find(
  (item) => item.id === Number(taxonomyForm.value.education_level_id)
) || null)

const selectedGrade = computed(() => availableGrades.value.find(
  (item) => item.id === Number(taxonomyForm.value.grade_id)
) || null)

const availableSubjects = computed(() => {
  if (!taxonomyForm.value.grade_id) return []

  return selectedGrade.value?.subjects || []
})

const selectedSubject = computed(() => availableSubjects.value.find(
  (item) => item.id === Number(taxonomyForm.value.subject_id)
) || null)

const selectedCurriculumOption = computed(() => curriculumOptions.value.find(
  (option) => option._key === selectedCurriculumKey.value
) || null)

const filteredCurriculumOptions = computed(() => {
  const keyword = curriculumSearch.value.trim().toLocaleLowerCase('vi')
  if (!keyword) return curriculumOptions.value

  return curriculumOptions.value.filter((option) =>
    option.label.toLocaleLowerCase('vi').includes(keyword)
  )
})

const currentTaxonomyStep = computed(() => {
  if (!taxonomyForm.value.education_level_id) return 1
  if (!taxonomyForm.value.grade_id) return 2
  if (!taxonomyForm.value.subject_id) return 3
  if (!taxonomyConfirmed.value) return 4
  return 5
})

const isTaxonomyComplete = computed(() => Boolean(
  taxonomyConfirmed.value
  && taxonomyForm.value.education_level_id
  && taxonomyForm.value.grade_id
  && taxonomyForm.value.subject_id
  && taxonomyForm.value.topic_name.trim()
))

const customTopicInput = ref('')
const isCustomTopicMode = ref(false)

const confirmCustomTaxonomy = () => {
  const topic = customTopicInput.value.trim()
  if (!topic) return
  taxonomyForm.value.topic_name = topic
  taxonomyForm.value.curriculum_unit_ids = []
  taxonomyConfirmed.value = true
}

const promptPlaceholder = computed(() => (
  isTaxonomyComplete.value
    ? 'Ví dụ: Tạo câu hỏi trắc nghiệm có giải thích đáp án và tình huống thực tế...'
    : 'Chọn đầy đủ thông tin chương trình học trước khi nhập prompt.'
))

const resetCurriculum = (clearOptions = true) => {
  curriculumRequestId += 1
  curriculumLoading.value = false
  taxonomyForm.value.topic_name = ''
  taxonomyForm.value.curriculum_unit_ids = []
  selectedCurriculumKey.value = ''
  curriculumSearch.value = ''
  curriculumError.value = ''
  curriculumAvailable.value = true
  if (clearOptions) curriculumOptions.value = []
}

const loadCurriculumOptions = async () => {
  if (!taxonomyForm.value.education_level_id || !taxonomyForm.value.grade_id || !taxonomyForm.value.subject_id) return

  const requestId = ++curriculumRequestId
  curriculumLoading.value = true
  curriculumError.value = ''
  curriculumAvailable.value = true
  curriculumOptions.value = []
  selectedCurriculumKey.value = ''
  curriculumSearch.value = ''

  try {
    const result = await curriculumApi.fetchOptions({
      education_level_id: Number(taxonomyForm.value.education_level_id),
      grade_id: Number(taxonomyForm.value.grade_id),
      subject_id: Number(taxonomyForm.value.subject_id),
    })
    if (requestId !== curriculumRequestId) return

    const seen = new Set()
    curriculumOptions.value = result.options.reduce((options, option, index) => {
      const ids = Array.isArray(option.curriculum_unit_ids)
        ? option.curriculum_unit_ids.map(Number).filter(Number.isInteger)
        : []
      if (!ids.length || !String(option.label || '').trim()) return options

      const duplicateKey = String(option.label)
        .trim()
        .toLocaleLowerCase('vi')
      if (seen.has(duplicateKey)) return options
      seen.add(duplicateKey)

      options.push({
        ...option,
        label: String(option.label).trim(),
        curriculum_unit_ids: ids,
        _key: `curriculum-${ids.join('-')}-${index}`,
      })
      return options
    }, [])
    curriculumAvailable.value = Boolean(result.available && curriculumOptions.value.length)
  } catch (error) {
    if (requestId !== curriculumRequestId) return
    curriculumAvailable.value = false
    curriculumError.value = error.response?.data?.message || error.message || 'Đã xảy ra lỗi không xác định.'
  } finally {
    if (requestId === curriculumRequestId) curriculumLoading.value = false
  }
}

const onEducationLevelChange = () => {
  taxonomyConfirmed.value = false
  taxonomyForm.value.grade_id = ''
  taxonomyForm.value.subject_id = ''
  resetCurriculum()
}

const onGradeChange = () => {
  taxonomyConfirmed.value = false
  taxonomyForm.value.subject_id = ''
  resetCurriculum()
}

const onSubjectChange = async () => {
  taxonomyConfirmed.value = false
  resetCurriculum()
  settings.value.language = selectedSubject.value?.code === 'english'
    ? 'en'
    : 'vi'
  if (taxonomyForm.value.subject_id) await loadCurriculumOptions()
}

const confirmTaxonomy = () => {
  const option = selectedCurriculumOption.value
  if (!option) return

  taxonomyForm.value.topic_name = option.label
  taxonomyForm.value.curriculum_unit_ids = [...option.curriculum_unit_ids]
  taxonomyConfirmed.value = true
}

const goToTaxonomyStep = (step) => {
  taxonomyConfirmed.value = false
  prompt.value = ''

  if (step <= 1) {
    taxonomyForm.value.education_level_id = ''
    taxonomyForm.value.grade_id = ''
    taxonomyForm.value.subject_id = ''
    resetCurriculum()
    return
  }

  if (step === 2) {
    taxonomyForm.value.grade_id = ''
    taxonomyForm.value.subject_id = ''
    resetCurriculum()
    return
  }

  if (step === 3) {
    taxonomyForm.value.subject_id = ''
    resetCurriculum()
    return
  }

  resetCurriculum(false)
}

const loadTaxonomy = async () => {
    beginTask()
    try {
taxonomyLoading.value = true
  taxonomyError.value = ''

  try {
    const data = await taxonomyApi.tree()
    taxonomyLevels.value = data?.education_levels || data?.educationLevels || []
  } catch (error) {
    taxonomyError.value = `Không tải được danh mục chương trình học: ${error.message}`
  } finally {
    taxonomyLoading.value = false
  }
    } finally {
      endTask()
    }
  }

const user = computed(() => currentUserStorage.get())
const ocrLimit = computed(() => {
  const role = String(user.value?.role || 'free').toLowerCase()
  if (['admin', 'ultra'].includes(role)) return 'Không giới hạn'
  if (role === 'pro') return '50 lượt/tháng'
  if (role === 'plus') return '10 lượt/tháng'
  return 'Yêu cầu gói Plus'
})
const quotaStats = computed(() => [
  { label: 'Vai trò hiện tại', value: user.value?.role_label || user.value?.role || 'Guest' },
  { label: 'Lượt sinh AI còn lại', value: user.value?.ai_quota_remaining ?? 0 },
  { label: 'Hạn mức quét OCR', value: ocrLimit.value },
])

const isGenerating = ref(false)
const isPolling = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const jobId = ref('')
const jobStatus = ref('')
const currentStep = ref('')
const generatedQuiz = ref(null)
let pollTimer = null

const statusLabel = computed(() => ({
  pending: 'Đang chờ',
  processing: 'Đang sinh quiz',
  completed: 'Hoàn tất',
  failed: 'Thất bại',
}[jobStatus.value] || jobStatus.value))

const actionLabel = computed(() => {
  if (isGenerating.value) return 'Đang gửi yêu cầu...'
  if (isPolling.value) return 'AI đang tạo quiz...'
  return 'Tạo quiz bằng AI'
})

const stepTargets = {
  '': 5,
  validate_prompt: 20,
  calling_ai_api: 70,
  parsing_ai_response: 85,
  saving_to_database: 95,
  completed: 100,
}

const stepLabels = {
  '': 'Đang khởi tạo...',
  validate_prompt: 'Đang kiểm tra và tối ưu câu lệnh...',
  calling_ai_api: 'AI đang suy nghĩ (có thể mất 10-20s)...',
  parsing_ai_response: 'Đang bóc tách dữ liệu câu hỏi...',
  saving_to_database: 'Đang đồng bộ đáp án vào hệ thống...',
  completed: 'Hoàn tất!',
}

const displayPercent = ref(0)
let rafId = null

const currentTarget = computed(() => stepTargets[currentStep.value] ?? displayPercent.value)
const currentLabel = computed(() => stepLabels[currentStep.value] ?? 'Đang xử lý...')

const startProgressLoop = () => {
  if (rafId) cancelAnimationFrame(rafId)
  rafId = requestAnimationFrame(animateProgress)
}

const stopProgressLoop = () => {
  if (rafId) {
    cancelAnimationFrame(rafId)
    rafId = null
  }
}

const animateProgress = () => {
  const target = currentTarget.value
  
  if (displayPercent.value < target) {
    const diff = target - displayPercent.value
    const step = diff > 5 ? Math.max(0.1, diff * 0.05) : 0.01
    displayPercent.value = Math.min(target, displayPercent.value + step)
  }
  rafId = requestAnimationFrame(animateProgress)
}

const isInvalidPromptMessage = (message = '') =>
  message.includes('Prompt chưa rõ nội dung') ||
  message.includes('Vui lòng nhập đúng prompt') ||
  message.includes('invalid_prompt')

const stopPolling = () => {
  if (pollTimer) {
    clearTimeout(pollTimer)
    pollTimer = null
  }
}

const pollJob = async () => {
  if (!jobId.value) return
  isPolling.value = true

  try {
    const job = await aiApi.getJob(jobId.value)
    jobStatus.value = job.status
    
    if (job.current_step) {
      currentStep.value = job.current_step 
    }

    if (job.status === 'completed' && job.quiz_id) {
      generatedQuiz.value = job.quiz_full || job.quiz || null
      await authApi.me()
      isPolling.value = false
      currentStep.value = 'completed' 
      displayPercent.value = 100
      stopPolling()
      stopProgressLoop()
      return
    }

    if (job.status === 'failed') {
      const jmsg = job.error_message || ''
      if (jmsg.includes('AI quota exhausted')) {
        errorMessage.value = 'Bạn đã hết token'
      } else {
        errorMessage.value = jmsg || 'AI tạo quiz thất bại.'
      }
      isPolling.value = false
      stopPolling()
      stopProgressLoop()
      return
    }

    pollTimer = setTimeout(pollJob, 500)
  } catch (error) {
    errorMessage.value = `Không lấy được trạng thái AI job: ${error.message}`
    isPolling.value = false
    stopPolling()
    stopProgressLoop()
  }
}

const generateQuiz = async () => {
  const trimmedPrompt = prompt.value.trim()

  if (!isTaxonomyComplete.value) {
    errorMessage.value = 'Vui lòng chọn đủ cấp học, lớp, bộ môn và chủ đề.'
    return
  }

  if (!trimmedPrompt) {
    errorMessage.value = 'Bạn cần nhập prompt để AI sinh quiz.'
    return
  }

  if (trimmedPrompt.length > MAX_PROMPT_LENGTH) {
    errorMessage.value = `Prompt quá dài (tối đa ${MAX_PROMPT_LENGTH} ký tự, hiện có ${trimmedPrompt.length}).`
    return
  }

  displayPercent.value = 0
  currentStep.value = 'validate_prompt'
  isGenerating.value = true
  errorMessage.value = ''
  successMessage.value = ''
  generatedQuiz.value = null
  jobId.value = ''
  jobStatus.value = ''
  stopPolling()
  startProgressLoop()

  try {
    const response = await aiApi.generate({
      prompt: trimmedPrompt,
      count: settings.value.count,
      difficulty: settings.value.difficulty,
      language: settings.value.language,
      visibility: settings.value.visibility,
      education_level_id: Number(taxonomyForm.value.education_level_id),
      grade_id: Number(taxonomyForm.value.grade_id),
      subject_id: Number(taxonomyForm.value.subject_id),
      topic_name: taxonomyForm.value.topic_name.trim(),
      curriculum_unit_ids: taxonomyForm.value.curriculum_unit_ids,
    })

    jobId.value = response.job_id
    jobStatus.value = response.status
    successMessage.value = 'Đã gửi yêu cầu sang AI. Hệ thống đang sinh quiz và lưu vào cơ sở dữ liệu.'
    await pollJob()
  } catch (error) {
    const msg = error?.message || String(error)

    if (isInvalidPromptMessage(msg)) {
      errorMessage.value = msg
    } else if (msg.includes('AI quota exhausted')) {
      errorMessage.value = 'Bạn đã hết token'
    } else {
      errorMessage.value = `Không tạo được AI quiz: ${msg}`
    }
    stopProgressLoop()
  } finally {
    isGenerating.value = false
  }
}

const openInEditor = () => {
  if (!generatedQuiz.value?.id) return
  router.push(`${questionBase.value}/edit/${generatedQuiz.value.id}`)
}

onMounted(loadTaxonomy)

onBeforeUnmount(() => {
  stopProgressLoop()
  stopPolling()
})
</script>
