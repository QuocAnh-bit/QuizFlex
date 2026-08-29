<template>
  <section class="max-w-5xl mx-auto py-6 px-4 sm:px-6 space-y-6">
    <!-- PAGE LOADING STATE -->
    <div v-if="loading" class="card p-12 text-center text-xs text-slate-400 space-y-3">
      <div class="h-8 w-8 rounded-full border-2 border-purple-600 border-t-transparent animate-spin mx-auto"></div>
      <p class="font-medium text-slate-500">Đang tải dữ liệu bài Quiz...</p>
    </div>

    <!-- UNAUTHORIZED / PERMISSION DENIED ERROR -->
    <div v-else-if="unauthorizedError" class="card p-8 border-rose-300 bg-rose-50/80 text-rose-900 space-y-4 text-center">
      <div class="h-12 w-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto text-xl font-bold">
        ✕
      </div>
      <div class="space-y-1">
        <h2 class="text-base font-bold text-rose-900">Truy cập bị từ chối (403 Forbidden)</h2>
        <p class="text-xs text-rose-700 leading-relaxed max-w-md mx-auto">{{ unauthorizedError }}</p>
      </div>
      <button type="button" class="btn-primary text-xs px-4 py-2" @click="router.push('/dashboard/my-questions')">
        ← Về kho câu hỏi của tôi
      </button>
    </div>

    <!-- MAIN EDIT VIEW -->
    <div v-else class="space-y-6 animate-fadeIn">
      <!-- Top Action Bar -->
      <div class="card p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gradient-to-r from-white to-purple-50/40">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-purple-600">Chỉnh sửa Quiz</span>
            <span class="text-slate-300">•</span>
            <StatusBadge :value="form.review_status" />
          </div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 leading-tight">
            {{ form.title || 'Chỉnh sửa bài Quiz' }}
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            Chỉnh sửa thông tin bài Quiz và nội dung câu hỏi. Nhấn "Lưu thay đổi" để lưu vào hệ thống.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
          <button
            type="button"
            class="btn-secondary text-xs px-3.5 py-2 flex items-center gap-1.5"
            @click="isHistoryModalOpen = true"
          >
            <History :size="14" />
            <span>Lịch sử duyệt ({{ reviewHistory.length }})</span>
          </button>

          <button
            type="button"
            class="btn-secondary text-xs px-4 py-2 flex items-center gap-1.5 hover:border-purple-300"
            :disabled="saving"
            @click="handleSaveQuiz"
          >
            <Save :size="14" />
            <span>{{ saving ? 'Đang lưu...' : 'Lưu thay đổi' }}</span>
          </button>

          <button
            v-if="canSubmitReview"
            type="button"
            class="btn-primary text-xs px-4 py-2 flex items-center gap-1.5 shadow-md shadow-purple-200"
            :disabled="saving || isSubmittingReview"
            @click="openReviewModal"
          >
            <Send :size="14" />
            <span>Gửi duyệt</span>
          </button>
        </div>
      </div>

      <!-- REVIEW STATUS BANNERS -->
      <!-- Case 1: PENDING REVIEW -->
      <div
        v-if="form.review_status === 'pending_review'"
        class="flex items-start gap-3.5 rounded-2xl border border-amber-400 bg-amber-50/90 p-5 text-xs text-amber-900 shadow-sm"
      >
        <Clock class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
        <div class="grid gap-1 flex-1">
          <span class="font-bold text-sm text-amber-900">Bài Quiz đang chờ Admin kiểm duyệt công khai</span>
          <span class="leading-relaxed text-amber-800">
            Yêu cầu xét duyệt của bạn đang trong hàng đợi xử lý. Trong lúc này, nút gửi duyệt tạm khóa để tránh tạo yêu cầu trùng lặp. Bạn vẫn có thể cập nhật nội dung nếu cần.
          </span>
        </div>
      </div>

      <!-- Case 3: APPROVED -->
      <div
        v-else-if="form.review_status === 'approved'"
        class="flex items-start gap-3.5 rounded-2xl border border-emerald-400 bg-emerald-50/90 p-5 text-xs text-emerald-900 shadow-sm"
      >
        <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
        <div class="grid gap-1 flex-1">
          <span class="font-bold text-sm text-emerald-900">Quiz đã được phê duyệt công khai</span>
          <span class="leading-relaxed text-emerald-800">
            Bài Quiz này đã được duyệt và xuất bản công khai trong hệ thống QuizFlex.
          </span>
        </div>
      </div>

      <!-- SUCCESS POST-SAVE BANNER FOR REJECTED QUIZ -->
      <div
        v-if="saveSuccessNotice && form.review_status === 'rejected'"
        class="flex items-center justify-between gap-3 rounded-xl border border-purple-300 bg-purple-50 p-4 text-xs font-semibold text-purple-900 animate-fadeIn"
      >
        <div class="flex items-center gap-2">
          <Check class="h-4 w-4 text-purple-600 shrink-0" />
          <span>{{ saveSuccessNotice }}</span>
        </div>
        <button
          type="button"
          class="btn-primary text-xs px-3.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white shrink-0"
          @click="openReviewModal"
        >
          Gửi duyệt ngay →
        </button>
      </div>

      <!-- MAIN FORM CONTAINER -->
      <article class="card p-6 sm:p-8 space-y-6">
        <!-- 1. Metadata Section -->
        <div class="space-y-4 border-b border-slate-100 pb-6">
          <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider text-purple-700 flex items-center gap-2">
            <span class="h-4 w-1 rounded-full bg-[#7C3AED]"></span>
            <span>1. Thông tin chung</span>
          </h2>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 block">Tên Quiz <span class="text-rose-500">*</span></label>
            <input
              v-model="form.title"
              type="text"
              class="field text-xs w-full"
              placeholder="Nhập tên bài quiz..."
              required
            />
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 block">Mô tả nội dung</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="field text-xs w-full resize-none"
              placeholder="Nhập mô tả ngắn gọn về đề thi, kiến thức trọng tâm..."
            ></textarea>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700 block">Cấp học</label>
              <select v-model="form.education_level_id" class="field text-xs w-full">
                <option :value="null">-- Chọn cấp học --</option>
                <option v-for="el in taxonomy.educationLevels" :key="el.id" :value="el.id">{{ el.name }}</option>
              </select>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700 block">Khối lớp</label>
              <select v-model="form.grade_id" class="field text-xs w-full">
                <option :value="null">-- Chọn khối lớp --</option>
                <option v-for="gr in filteredGrades" :key="gr.id" :value="gr.id">{{ gr.name }}</option>
              </select>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700 block">Môn học</label>
              <select v-model="form.subject_id" class="field text-xs w-full">
                <option :value="null">-- Chọn môn học --</option>
                <option v-for="sub in filteredSubjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
              </select>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700 block">Chủ đề / Bài học</label>
              <input
                v-model="form.topic_name"
                type="text"
                class="field text-xs w-full"
                placeholder="VD: Đại số, Hình học..."
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700 block">Độ khó</label>
              <select v-model="form.difficulty" class="field text-xs w-full">
                <option value="easy">Dễ</option>
                <option value="medium">Vừa</option>
                <option value="hard">Khó</option>
              </select>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700 block">Thời gian làm bài (phút)</label>
              <input
                v-model.number="form.time_limit_minutes"
                type="number"
                min="1"
                max="300"
                class="field text-xs w-full"
                placeholder="VD: 15"
              />
            </div>

            <div class="space-y-1.5 flex flex-col justify-end">
              <label class="flex items-center gap-2 cursor-pointer pb-2 select-none">
                <input
                  v-model="form.shuffle_questions"
                  type="checkbox"
                  class="rounded accent-purple-600 h-4 w-4 cursor-pointer"
                />
                <span class="text-xs font-bold text-slate-700">Xáo trộn câu hỏi khi làm bài</span>
              </label>
            </div>
          </div>
        </div>

        <!-- 2. Questions List Section (Standard Question Editor Matching Create Quiz) -->
        <div class="space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3 flex-wrap gap-2">
            <div>
              <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider text-purple-700 flex items-center gap-2">
                <span class="h-4 w-1 rounded-full bg-[#7C3AED]"></span>
                <span>2. Danh sách câu hỏi ({{ form.questions.length }})</span>
              </h2>
              <p class="text-[11px] text-slate-500 mt-0.5">
                Mỗi câu hỏi cần có ít nhất 2 phương án đáp án và chọn đúng đáp án chính xác.
              </p>
            </div>
            <button
              type="button"
              class="btn-primary text-xs px-3.5 py-1.5 flex items-center gap-1.5 shadow-sm cursor-pointer"
              @click="openQuestionPicker"
            >
              <Plus :size="14" />
              <span>Thêm câu hỏi</span>
            </button>
          </div>

          <!-- Empty Questions State -->
          <div v-if="form.questions.length === 0" class="rounded-2xl border-2 border-dashed border-slate-200 p-8 text-center space-y-2">
            <p class="text-xs font-medium text-slate-500">Chưa có câu hỏi nào trong bài Quiz.</p>
            <button type="button" class="btn-primary text-xs px-4 py-2 inline-flex items-center gap-1.5 cursor-pointer" @click="openQuestionPicker">
              <Plus :size="14" />
              <span>Chọn câu hỏi cho Quiz</span>
            </button>
          </div>

          <div v-else class="space-y-4">
            <!-- SMART SCORING ALLOCATOR PANEL (EDIT MODE) -->
            <div
              id="score-allocator-banner"
              class="rounded-2xl border border-purple-100 bg-gradient-to-br from-purple-50/60 via-slate-50/40 to-white p-4 shadow-2xs space-y-3 transition-all duration-300"
              :class="{ 'ring-2 ring-amber-400 border-amber-300 bg-amber-50/40 shadow-md shadow-amber-500/10': !isTotalScoreValid }"
            >
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                  <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-purple-600 text-white shadow-xs">
                    <Sparkles :size="15" />
                  </div>
                  <div>
                    <h4 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                      <span>Phân bổ điểm số</span>
                      <span class="text-[10px] font-semibold text-purple-700 bg-purple-100 px-2 py-0.5 rounded-full">Chuẩn thang 10.00</span>
                    </h4>
                    <p class="text-[11px] text-slate-500 font-medium mt-0.5">Tự động chia điểm chuẩn xác cho toàn bộ câu hỏi hoặc phân bổ theo độ khó</p>
                  </div>
                </div>

                <!-- Tổng điểm Indicator & Nút Phân bổ lại -->
                <div class="flex items-center gap-2 shrink-0 self-start sm:self-auto">
                  <div
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-bold transition"
                    :class="isTotalScoreValid ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-300 bg-amber-50 text-amber-900'"
                  >
                    <span>Tổng điểm:</span>
                    <span class="font-mono text-sm">{{ totalAllocatedScoreFormatted }}</span>
                    <span v-if="!isTotalScoreValid" class="text-[11px] font-semibold text-amber-700 ml-1">
                      {{ totalAllocatedScore < 10.0 ? `⚠️ Thiếu ${(10.0 - totalAllocatedScore).toFixed(2)} điểm` : `⚠️ Thừa ${(totalAllocatedScore - 10.0).toFixed(2)} điểm` }}
                    </span>
                    <span v-else class="text-emerald-600 font-bold ml-0.5">✓</span>
                  </div>

                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-purple-200 bg-white text-purple-700 text-xs font-bold hover:bg-purple-50 transition cursor-pointer shadow-2xs active:scale-[0.98]"
                    @click="reallocatePoints(false)"
                    title="Cân bằng lại điểm cho các câu chưa chỉnh sửa"
                  >
                    <RotateCcw :size="13" />
                    <span>Phân bổ lại</span>
                  </button>
                </div>
              </div>

              <!-- Scoring Strategy Options -->
              <div class="flex flex-wrap items-center justify-between gap-3 pt-2.5 border-t border-purple-100/70 text-xs">
                <div class="flex flex-wrap items-center gap-4">
                  <label class="flex items-center gap-2 cursor-pointer text-slate-700 font-medium hover:text-slate-900">
                    <input
                      type="radio"
                      name="edit_scoring_strategy"
                      value="equal"
                      v-model="scoringStrategy"
                      @change="reallocatePoints(false)"
                      class="text-purple-600 focus:ring-purple-500 h-3.5 w-3.5"
                    />
                    <span>Chia đều (Mặc định)</span>
                  </label>

                  <label class="flex items-center gap-2 cursor-pointer text-slate-700 font-medium hover:text-slate-900">
                    <input
                      type="radio"
                      name="edit_scoring_strategy"
                      value="difficulty"
                      v-model="scoringStrategy"
                      @change="reallocatePoints(false)"
                      class="text-purple-600 focus:ring-purple-500 h-3.5 w-3.5"
                    />
                    <span>Theo độ khó (Dễ 1x • Vừa 2x • Khó 3x)</span>
                  </label>
                </div>

                <div v-if="manualEditedCount > 0" class="text-[11px] text-amber-700 font-medium bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200/80">
                  Đang có <b>{{ manualEditedCount }}</b> câu chỉnh sửa thủ công (giữ nguyên khi phân bổ lại)
                </div>
              </div>
            </div>

            <!-- Questions List with Reusable QuizQuestionEditor Component -->
            <div class="space-y-4">
              <QuizQuestionEditor
                v-for="(q, qIndex) in form.questions"
                :key="q._uid"
                :question="q"
                :index="qIndex"
                :total="form.questions.length"
                @move-up="moveQuestion($event, -1)"
                @move-down="moveQuestion($event, 1)"
                @remove="removeQuestion($event)"
                @manual-point-change="handleQuestionPointManualChange"
                @reset-point-auto="handleQuestionPointResetAuto($event)"
              />
            </div>
          </div>
        </div>

        <!-- Footer Actions Bar -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-6">
          <button
            type="button"
            class="btn-secondary text-xs px-4 py-2"
            @click="router.push('/dashboard/my-questions')"
          >
            ← Quay lại
          </button>

          <div class="flex items-center gap-2.5">
            <button
              type="button"
              class="btn-secondary text-xs px-4 py-2"
              :disabled="saving"
              @click="handleSaveQuiz"
            >
              <Save :size="13" />
              <span>{{ saving ? 'Đang lưu...' : 'Lưu thay đổi' }}</span>
            </button>

            <button
              v-if="canSubmitReview"
              type="button"
              class="btn-primary text-xs px-5 py-2 flex items-center gap-1.5"
              :disabled="saving || isSubmittingReview"
              @click="openReviewModal"
            >
              <Send :size="13" />
              <span>{{ isSubmittingReview ? 'Đang gửi...' : 'Gửi duyệt Quiz' }}</span>
            </button>
          </div>
        </div>
      </article>
    </div>

    <!-- SUBMIT REVIEW CONFIRMATION MODAL -->
    <div
      v-if="isReviewModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn"
    >
      <div class="card p-6 max-w-md w-full space-y-4 shadow-2xl animate-scaleUp">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
            <Send :size="18" class="text-purple-600" />
            <span>Gửi yêu cầu duyệt Quiz</span>
          </h3>
          <button type="button" class="text-slate-400 hover:text-slate-600 text-sm font-bold" @click="isReviewModalOpen = false">
            ✕
          </button>
        </div>

        <p class="text-xs leading-relaxed text-slate-600">
          Khi gửi duyệt, hệ thống sẽ tạo một bản Snapshot bất biến của bài Quiz (Revision mới) và thông báo cho Admin để kiểm duyệt công khai.
        </p>

        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-700 block">Lời nhắn cho Admin (Tùy chọn)</label>
          <textarea
            v-model="reviewRequestNote"
            rows="3"
            class="field text-xs resize-none w-full"
            placeholder="Ví dụ: Em đã sửa lại đáp án câu 3 theo góp ý lần trước..."
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <button type="button" class="btn-secondary text-xs px-4 py-2" :disabled="isSubmittingReview" @click="isReviewModalOpen = false">
            Hủy
          </button>
          <button
            type="button"
            class="btn-primary text-xs px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white flex items-center gap-1.5"
            :disabled="isSubmittingReview"
            @click="handleConfirmSubmitReview"
          >
            <Send :size="13" />
            <span>{{ isSubmittingReview ? 'Đang gửi...' : 'Xác nhận gửi duyệt' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- REVIEW HISTORY MODAL -->
    <div
      v-if="isHistoryModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn"
    >
      <div class="card max-w-2xl w-full max-h-[85vh] flex flex-col shadow-2xl animate-scaleUp overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50">
          <div>
            <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
              <History :size="18" class="text-purple-600" />
              <span>Lịch sử kiểm duyệt Quiz</span>
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">Toàn bộ các lần gửi duyệt và phản hồi từ Admin</p>
          </div>
          <button type="button" class="text-slate-400 hover:text-slate-600 text-sm font-bold" @click="isHistoryModalOpen = false">
            ✕
          </button>
        </div>

        <div class="p-6 overflow-y-auto space-y-4 flex-1">
          <div v-if="reviewHistory.length === 0" class="text-center py-8 text-xs text-slate-400">
            Chưa có lịch sử xét duyệt nào cho bài Quiz này.
          </div>

          <div
            v-for="(item, idx) in reviewHistory"
            :key="item.id ?? idx"
            class="rounded-xl border p-4 space-y-2.5 text-xs bg-white shadow-sm"
            :class="item.status === 'approved' ? 'border-emerald-300 bg-emerald-50/20' : (item.status === 'rejected' ? 'border-rose-300 bg-rose-50/20' : 'border-amber-300 bg-amber-50/20')"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="rounded bg-purple-100 text-purple-800 font-bold px-2 py-0.5 text-[10px]">
                  Revision #{{ item.revision_number || (reviewHistory.length - idx) }}
                </span>
                <StatusBadge :value="item.status" />
              </div>
              <span class="text-[11px] text-slate-400">{{ formatDate(item.created_at) }}</span>
            </div>

            <div v-if="item.request_note" class="rounded bg-purple-50/80 p-2 text-purple-900 border border-purple-200/50">
              <strong class="block text-[10px] uppercase text-purple-700">Lời nhắn của bạn:</strong>
              <p>{{ item.request_note }}</p>
            </div>

            <div v-if="item.rejection_reason" class="rounded bg-rose-50/80 p-2 text-rose-900 border border-rose-200">
              <strong class="block text-[10px] uppercase text-rose-700">Lý do từ chối từ Admin:</strong>
              <p>{{ item.rejection_reason }}</p>
            </div>

            <div v-if="item.reviewed_at" class="text-[11px] text-slate-400 pt-1 border-t border-slate-100 flex items-center justify-between">
              <span>Người duyệt: <strong class="text-slate-600">{{ item.reviewer_name || item.reviewer?.name || 'Admin' }}</strong></span>
              <span>Thời gian duyệt: {{ formatDate(item.reviewed_at) }}</span>
            </div>
          </div>
        </div>

        <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end shrink-0">
          <button type="button" class="btn-secondary text-xs px-4 py-2" @click="isHistoryModalOpen = false">
            Đóng
          </button>
        </div>
      </div>
    </div>

    <!-- QUESTION PICKER MODAL -->
    <QuestionPickerModal
      v-model="pickerSelectedIds"
      :is-open="isPickerModalOpen"
      :initial-filters="pickerFilters"
      :disabled-ids="existingQuestionIds"
      @close="isPickerModalOpen = false"
      @confirm="handlePickerConfirm"
    />
  </section>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  AlertCircle,
  Check,
  CheckCircle2,
  Clock,
  History,
  Plus,
  RotateCcw,
  Save,
  Send,
  Sparkles,
} from 'lucide-vue-next'
import api, { currentUserStorage, questionsBankApi, quizReviewApi, taxonomyApi } from '@/services/api'
import StatusBadge from '@/components/common/StatusBadge.vue'
import QuizQuestionEditor from '@/components/question/QuizQuestionEditor.vue'
import QuestionPickerModal from '@/components/question/QuestionPickerModal.vue'

const route = useRoute()
const router = useRouter()
const showToast = inject('showToast')

const loading = ref(true)
const saving = ref(false)
const isSubmittingReview = ref(false)
const unauthorizedError = ref('')
const saveSuccessNotice = ref('')

const isReviewModalOpen = ref(false)
const reviewRequestNote = ref('')
const isHistoryModalOpen = ref(false)
const reviewHistory = ref([])

const isPickerModalOpen = ref(false)
const pickerSelectedIds = ref([])

const taxonomy = reactive({
  educationLevels: [],
  grades: [],
  subjects: [],
})

const form = ref({
  id: null,
  title: '',
  description: '',
  education_level_id: null,
  grade_id: null,
  subject_id: null,
  topic_name: '',
  difficulty: 'medium',
  time_limit_minutes: 15,
  shuffle_questions: false,
  review_status: 'draft',
  rejection_reason: '',
  questions: [],
})

const currentUser = currentUserStorage.get()

// Check if user has permission to submit review (draft or rejected, not pending, and not admin)
const canSubmitReview = computed(() => {
  if (!currentUser || String(currentUser.role || '').toLowerCase() === 'admin') return false
  return form.value.review_status !== 'pending_review'
})

const currentRejectionReason = computed(() => {
  if (form.value.rejection_reason) return form.value.rejection_reason
  const latestRejected = reviewHistory.value.find(h => h.status === 'rejected')
  return latestRejected?.rejection_reason || ''
})

const filteredGrades = computed(() => {
  if (!form.value.education_level_id) return taxonomy.grades
  return taxonomy.grades.filter(g => Number(g.education_level_id) === Number(form.value.education_level_id))
})

const filteredSubjects = computed(() => {
  if (!form.value.grade_id) return taxonomy.subjects
  return taxonomy.subjects.filter(s => !s.grade_id || Number(s.grade_id) === Number(form.value.grade_id))
})

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

// --- BỘ PHÂN BỔ ĐIỂM THÔNG MINH (THANG 10.00 CHUẨN EDTECH) ---
const scoringStrategy = ref('equal') // 'equal' | 'difficulty'

const totalAllocatedScore = computed(() => {
  const qs = form.value.questions || []
  if (qs.length === 0) return 0
  const sum = qs.reduce((acc, q) => acc + (parseFloat(q.points) || 0), 0)
  return Math.round(sum * 100) / 100
})

const totalAllocatedScoreFormatted = computed(() => {
  return totalAllocatedScore.value.toFixed(2)
})

const isTotalScoreValid = computed(() => {
  if (!form.value.questions || form.value.questions.length === 0) return true
  return Math.abs(totalAllocatedScore.value - 10.0) < 0.005
})

const manualEditedCount = computed(() => {
  const qs = form.value.questions || []
  return qs.filter(q => Boolean(q.is_manual)).length
})

const reallocatePoints = (forceAll = false) => {
  const qs = form.value.questions || []
  const n = qs.length
  if (n === 0) return

  if (forceAll) {
    qs.forEach(q => { q.is_manual = false })
  }

  const autoQuestions = []
  let manualSum = 0

  qs.forEach(q => {
    if (q.is_manual && !forceAll) {
      manualSum += parseFloat(q.points) || 0
    } else {
      autoQuestions.push(q)
    }
  })

  const autoCount = autoQuestions.length
  if (autoCount === 0) return

  const remainingBudget = Math.max(0, Math.round((10.0 - manualSum) * 100) / 100)

  if (scoringStrategy.value === 'difficulty') {
    const weights = autoQuestions.map(q => {
      const diff = (q.difficulty || 'medium').toLowerCase()
      return diff === 'hard' ? 3 : (diff === 'easy' ? 1 : 2)
    })
    const totalWeight = weights.reduce((a, b) => a + b, 0)

    let allocatedSum = 0
    autoQuestions.forEach((q, idx) => {
      const w = weights[idx]
      if (idx === autoCount - 1) {
        q.points = Math.max(0.01, Math.round((remainingBudget - allocatedSum) * 100) / 100)
      } else {
        const pt = Math.max(0.01, Math.round(((w / totalWeight) * remainingBudget) * 100) / 100)
        q.points = pt
        allocatedSum += pt
      }
    })
  } else {
    // Chia đều
    const basePoint = Math.max(0.01, Math.round((remainingBudget / autoCount) * 100) / 100)
    let allocatedSum = 0
    autoQuestions.forEach((q, idx) => {
      if (idx === autoCount - 1) {
        q.points = Math.max(0.01, Math.round((remainingBudget - allocatedSum) * 100) / 100)
      } else {
        q.points = basePoint
        allocatedSum += basePoint
      }
    })
  }
}

const handleQuestionPointManualChange = () => {
  // Điểm đã được sửa thủ công ở từng câu
}

const handleQuestionPointResetAuto = ({ index }) => {
  if (form.value.questions[index]) {
    form.value.questions[index].is_manual = false
  }
  reallocatePoints(false)
}

const loadTaxonomy = async () => {
  try {
    const [edLevels, grs, subs] = await Promise.all([
      taxonomyApi.fetchEducationLevels().catch(() => []),
      taxonomyApi.fetchGrades().catch(() => []),
      taxonomyApi.fetchSubjects().catch(() => []),
    ])
    taxonomy.educationLevels = edLevels
    taxonomy.grades = grs
    taxonomy.subjects = subs
  } catch (e) {
    console.error('Lỗi tải danh mục môn học:', e)
  }
}

const fetchQuiz = async () => {
  loading.value = true
  unauthorizedError.value = ''
  saveSuccessNotice.value = ''

  try {
    if (!currentUser) {
      router.push({ path: '/login', query: { redirect: route.fullPath } })
      return
    }

    // Role verification: Admin is NOT allowed to edit quizzes
    if (String(currentUser.role || '').toLowerCase() === 'admin') {
      unauthorizedError.value = 'Tài khoản Admin không có quyền chỉnh sửa nội dung Quiz. Quyền chỉnh sửa thuộc về tác giả Quiz.'
      loading.value = false
      return
    }

    const res = await api.get(`/quizzes/${route.params.id}`)
    const quiz = res.data.data?.quiz || res.data.data || res.data

    // Ownership verification: Quiz must belong to current user
    if (quiz.user_id && Number(quiz.user_id) !== Number(currentUser.id)) {
      unauthorizedError.value = 'Bạn không có quyền chỉnh sửa bài Quiz của người dùng khác.'
      loading.value = false
      return
    }

    const timeLimitMinutes = quiz.time_limit_minutes || (quiz.time_limit_seconds ? Math.round(quiz.time_limit_seconds / 60) : 15)

    const loadedQuestions = (quiz.questions || []).map((q, idx) => ({
      id: q.id ?? null,
      _uid: `q_${q.id || 'loaded'}_${idx}_${Math.random().toString(36).substring(2, 7)}`,
      content: q.content || q.question || '',
      type: q.type || 'single_choice',
      difficulty: q.difficulty || 'medium',
      points: parseFloat(q.points ?? (q.pivot?.points ?? 1.0)) || 1.0,
      order: q.order ?? (q.pivot?.order ?? idx),
      is_manual: false,
      answers: (q.answers || []).map((a, aIdx) => ({
        id: a.id ?? null,
        _uid: `ans_${a.id || 'loaded'}_${aIdx}_${Math.random().toString(36).substring(2, 7)}`,
        content: a.content || '',
        key: a.key || a.answer_key || String.fromCharCode(65 + aIdx),
        is_correct: Boolean(a.is_correct),
        order: a.order ?? aIdx,
      })),
    }))

    form.value = {
      id: quiz.id,
      title: quiz.title || '',
      description: quiz.description || '',
      education_level_id: quiz.education_level_id ?? null,
      grade_id: quiz.grade_id ?? null,
      subject_id: quiz.subject_id ?? null,
      topic_name: quiz.topic_name || '',
      difficulty: quiz.difficulty || 'medium',
      time_limit_minutes: timeLimitMinutes,
      shuffle_questions: Boolean(quiz.shuffle_questions),
      review_status: quiz.review_status || 'draft',
      rejection_reason: quiz.rejection_reason || '',
      questions: loadedQuestions,
    }

    // Tự động phân bổ đều chuẩn thang 10.00 khi mở Quiz nếu tổng điểm ban đầu chưa bằng 10.00
    if (!isTotalScoreValid.value) {
      reallocatePoints(true)
    }

    // Load review history
    await loadReviewHistory(quiz.id)
  } catch (err) {
    console.error(err)
    unauthorizedError.value = err.response?.data?.message || err.message || 'Không thể tải thông tin bài Quiz.'
  } finally {
    loading.value = false
  }
}

const loadReviewHistory = async (quizId) => {
  try {
    const data = await quizReviewApi.getReviewHistory(quizId)
    reviewHistory.value = Array.isArray(data) ? data : (data.data || [])
  } catch (e) {
    console.error('Không thể tải lịch sử duyệt:', e)
    reviewHistory.value = []
  }
}

const existingQuestionIds = computed(() => {
  return form.value.questions.map(q => q.id).filter(Boolean)
})

const pickerFilters = computed(() => ({
  education_level_id: form.value.education_level_id || '',
  grade_id: form.value.grade_id || '',
  subject_id: form.value.subject_id || '',
  topic_name: form.value.topic_name || '',
  difficulty: form.value.difficulty || '',
}))

const openQuestionPicker = () => {
  pickerSelectedIds.value = []
  isPickerModalOpen.value = true
}

const normalizeQuestion = (q, idx) => {
  const qAnswers = Array.isArray(q.answers) ? q.answers : []
  return {
    id: q.id ?? null,
    _uid: `q_${q.id || 'picked'}_${Date.now()}_${idx}_${Math.random().toString(36).substring(2, 7)}`,
    content: q.content || q.text || q.question || '',
    type: q.type || 'single_choice',
    difficulty: q.difficulty || form.value.difficulty || 'medium',
    points: 1.0,
    is_manual: false,
    order: idx,
    answers: qAnswers.map((a, aIdx) => ({
      id: a.id ?? null,
      _uid: `ans_${a.id || 'picked'}_${Date.now()}_${aIdx}_${Math.random().toString(36).substring(2, 7)}`,
      content: a.content || a.text || '',
      key: a.key || a.answer_key || String.fromCharCode(65 + aIdx),
      is_correct: Boolean(a.is_correct),
      order: a.order ?? aIdx,
    })),
  }
}

const hydrateQuestionDetails = async (question) => {
  // If answers are missing or is_correct is not defined on any answer choice, fetch question details
  const hasAnswersWithCorrect = Array.isArray(question.answers) &&
    question.answers.length > 0 &&
    question.answers.some(a => a.is_correct !== undefined && a.is_correct !== null)

  if (!hasAnswersWithCorrect && question.id) {
    try {
      const detail = await questionsBankApi.getQuestion(question.id)
      if (detail) {
        return {
          ...question,
          ...detail,
          answers: detail.answers || question.answers || [],
        }
      }
    } catch (e) {
      console.error(`Không thể tải chi tiết câu hỏi #${question.id}:`, e)
    }
  }
  return question
}

const handlePickerConfirm = async ({ ids, questions }) => {
  if (!Array.isArray(questions) || questions.length === 0) return

  const existingIds = new Set(form.value.questions.map(q => q.id).filter(Boolean))
  // Filter out any duplicate questions that already exist in the quiz
  const newQuestions = questions.filter(q => !existingIds.has(q.id))

  if (newQuestions.length === 0) {
    if (showToast) showToast('Các câu hỏi đã chọn đều đã có trong bài Quiz.', 'info')
    return
  }

  // Hydrate full question details (including answers & correct flags) in parallel
  const hydratedList = await Promise.all(
    newQuestions.map(q => hydrateQuestionDetails(q))
  )

  const currentCount = form.value.questions.length
  hydratedList.forEach((q, idx) => {
    const normalized = normalizeQuestion(q, currentCount + idx)
    // If single choice has no answer marked correct, default first answer as fallback
    if (normalized.answers.length > 0 && !normalized.answers.some(a => a.is_correct)) {
      normalized.answers[0].is_correct = true
    }
    form.value.questions.push(normalized)
  })

  // Tự động phân bổ lại điểm khi thêm câu hỏi mới
  reallocatePoints(false)

  if (showToast) {
    showToast(`Đã thêm ${newQuestions.length} câu hỏi vào bài Quiz.`, 'success')
  }
}

const removeQuestion = (index) => {
  form.value.questions.splice(index, 1)
  // Update internal order index
  form.value.questions.forEach((q, idx) => {
    q.order = idx
  })
  // Tự động phân bổ lại điểm khi xóa câu hỏi
  reallocatePoints(false)
}

const moveQuestion = (index, direction) => {
  const targetIndex = index + direction
  if (targetIndex < 0 || targetIndex >= form.value.questions.length) return
  // Move entire Question object (all data + answers)
  const moved = form.value.questions.splice(index, 1)[0]
  form.value.questions.splice(targetIndex, 0, moved)
  // Update internal order index
  form.value.questions.forEach((q, idx) => {
    q.order = idx
  })
}

const validateForm = () => {
  if (!form.value.title.trim()) return 'Vui lòng nhập tên bài Quiz.'
  if (form.value.questions.length === 0) return 'Bài Quiz cần có ít nhất 1 câu hỏi.'

  if (!isTotalScoreValid.value) {
    const diff = Math.abs(10.0 - totalAllocatedScore.value).toFixed(2)
    if (totalAllocatedScore.value < 10.0) {
      return `Tổng điểm hiện tại là ${totalAllocatedScoreFormatted.value}/10.00 (đang thiếu ${diff} điểm). Vui lòng nhấn 'Phân bổ lại' hoặc cân chỉnh lại điểm các câu để đạt chuẩn 10.00 điểm.`
    } else {
      return `Tổng điểm hiện tại là ${totalAllocatedScoreFormatted.value}/10.00 (đang thừa ${diff} điểm). Vui lòng nhấn 'Phân bổ lại' hoặc cân chỉnh lại điểm các câu để đạt chuẩn 10.00 điểm.`
    }
  }

  for (const [qIdx, q] of form.value.questions.entries()) {
    if (!q.content.trim()) return `Câu hỏi số ${qIdx + 1} chưa có nội dung.`
    if (q.answers.length < 2) return `Câu hỏi số ${qIdx + 1} cần ít nhất 2 đáp án lựa chọn.`
    if (q.answers.some(a => !a.content.trim())) {
      return `Câu hỏi số ${qIdx + 1} có đáp án còn để trống.`
    }
    const correctCount = q.answers.filter(a => a.is_correct).length
    if (correctCount === 0) {
      return `Câu hỏi số ${qIdx + 1} chưa chọn đáp án đúng.`
    }
    if (q.type === 'single_choice' && correctCount > 1) {
      return `Câu hỏi số ${qIdx + 1} là trắc nghiệm 1 lựa chọn nhưng có nhiều đáp án đúng.`
    }
  }

  return ''
}

const focusProblematicQuestionPoint = async () => {
  await nextTick()
  const qs = form.value.questions || []
  const manualQ = qs.find(q => Boolean(q.is_manual))
  const targetQ = manualQ || qs.reduce((maxQ, q) => ((parseFloat(q.points) || 0) > (parseFloat(maxQ?.points) || 0) ? q : maxQ), qs[0])

  if (targetQ) {
    const qIndex = qs.findIndex(q => q === targetQ || q.id === targetQ.id || q._uid === targetQ._uid)
    const card = document.getElementById(`quiz-question-card-${qIndex}`) || document.querySelector(`[data-question-uid="${targetQ._uid || targetQ.id}"]`)
    if (card) {
      card.scrollIntoView({ behavior: 'smooth', block: 'center' })
      const pointInput = card.querySelector('input[data-points-input]') || card.querySelector('input[type="number"]')
      if (pointInput) {
        pointInput.focus()
        pointInput.select()
      }
    }
  }
}

const handleSaveQuiz = async () => {
  const validationError = validateForm()
  if (validationError) {
    if (showToast) showToast(validationError, 'error')
    if (!isTotalScoreValid.value) {
      await focusProblematicQuestionPoint()
    }
    return
  }

  saving.value = true
  saveSuccessNotice.value = ''

  try {
    const payload = {
      title: form.value.title.trim(),
      description: form.value.description.trim(),
      education_level_id: form.value.education_level_id,
      grade_id: form.value.grade_id,
      subject_id: form.value.subject_id,
      topic_name: form.value.topic_name ? form.value.topic_name.trim() : null,
      difficulty: form.value.difficulty,
      time_limit_minutes: form.value.time_limit_minutes,
      shuffle_questions: form.value.shuffle_questions,
      questions: form.value.questions.map((q, idx) => ({
        id: q.id,
        content: q.content.trim(),
        type: q.type,
        difficulty: q.difficulty || 'medium',
        points: parseFloat(q.points) || 1.0,
        order: idx,
        answers: q.answers.map((a, aIdx) => ({
          id: a.id,
          content: a.content.trim(),
          is_correct: Boolean(a.is_correct),
          order: aIdx,
        })),
      })),
    }

    await api.put(`/quizzes/${form.value.id}`, payload)

    if (showToast) {
      showToast('Đã lưu thay đổi thành công.', 'success')
    }

    if (form.value.review_status === 'rejected') {
      saveSuccessNotice.value = "Quiz chưa được gửi lại cho Admin. Hãy bấm 'Gửi duyệt' khi bạn đã hoàn tất chỉnh sửa."
    }

    // Refresh data to ensure all newly created IDs are synced
    await fetchQuiz()
  } catch (err) {
    console.error(err)
    const msg = err.response?.data?.message || err.message || 'Lỗi khi lưu bài Quiz.'
    if (showToast) showToast(msg, 'error')
  } finally {
    saving.value = false
  }
}

const openReviewModal = () => {
  const validationError = validateForm()
  if (validationError) {
    if (showToast) showToast(`Không thể gửi duyệt: ${validationError}`, 'error')
    return
  }
  reviewRequestNote.value = ''
  isReviewModalOpen.value = true
}

const handleConfirmSubmitReview = async () => {
  isSubmittingReview.value = true
  try {
    // 1. Ensure latest quiz content is saved first
    const payload = {
      title: form.value.title.trim(),
      description: form.value.description.trim(),
      education_level_id: form.value.education_level_id,
      grade_id: form.value.grade_id,
      subject_id: form.value.subject_id,
      topic_name: form.value.topic_name ? form.value.topic_name.trim() : null,
      difficulty: form.value.difficulty,
      time_limit_minutes: form.value.time_limit_minutes,
      shuffle_questions: form.value.shuffle_questions,
      questions: form.value.questions.map((q, idx) => ({
        id: q.id,
        content: q.content.trim(),
        type: q.type,
        difficulty: q.difficulty || 'medium',
        points: parseFloat(q.points) || 1.0,
        order: idx,
        answers: q.answers.map((a, aIdx) => ({
          id: a.id,
          content: a.content.trim(),
          is_correct: Boolean(a.is_correct),
          order: aIdx,
        })),
      })),
    }
    await api.put(`/quizzes/${form.value.id}`, payload)

    // 2. Submit Review Request
    const res = await quizReviewApi.requestReview(form.value.id, reviewRequestNote.value.trim() || undefined)

    if (showToast) {
      showToast(res.message || 'Đã gửi yêu cầu duyệt Quiz cho Admin.', 'success')
    }

    isReviewModalOpen.value = false
    form.value.review_status = 'pending_review'
    saveSuccessNotice.value = ''

    await fetchQuiz()
  } catch (err) {
    console.error(err)
    const msg = err.response?.data?.message || err.message || 'Không thể gửi yêu cầu duyệt.'
    if (showToast) showToast(msg, 'error')
  } finally {
    isSubmittingReview.value = false
  }
}

onMounted(async () => {
  await loadTaxonomy()
  await fetchQuiz()
})
</script>
