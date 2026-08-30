<template>
  <div
    :id="`quiz-question-card-${index}`"
    :data-question-uid="question._uid || question.id"
    class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 space-y-4 shadow-sm transition hover:border-purple-200"
  >
    <!-- Header: Question Number & Actions -->
    <div class="flex items-center justify-between border-b border-slate-100 pb-3 flex-wrap gap-2">
      <div class="flex items-center gap-2.5">
        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-[#7C3AED] text-white font-black text-xs shadow-sm">
          {{ index + 1 }}
        </span>
        <div class="flex items-center gap-2">
          <h3 class="text-sm font-bold text-slate-900">
            Câu hỏi số {{ index + 1 }}
          </h3>
          <span v-if="question.id" class="font-mono text-[11px] text-purple-600 font-bold bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200/60">
            #{{ question.id }}
          </span>
        </div>
      </div>

      <!-- Action Buttons: Move Up, Move Down, Delete -->
      <div class="flex items-center gap-1 sm:gap-1.5">
        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-600 transition hover:border-[#7C3AED] hover:text-[#7C3AED] disabled:opacity-40 disabled:pointer-events-none cursor-pointer"
          :disabled="index === 0"
          title="Di chuyển câu hỏi lên trên"
          @click="$emit('move-up', index)"
        >
          <ArrowUp class="h-3.5 w-3.5" />
          <span class="hidden sm:inline">Lên</span>
        </button>

        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-600 transition hover:border-[#7C3AED] hover:text-[#7C3AED] disabled:opacity-40 disabled:pointer-events-none cursor-pointer"
          :disabled="index === total - 1"
          title="Di chuyển câu hỏi xuống dưới"
          @click="$emit('move-down', index)"
        >
          <ArrowDown class="h-3.5 w-3.5" />
          <span class="hidden sm:inline">Xuống</span>
        </button>

        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50/50 px-2.5 py-1 text-xs font-bold text-rose-600 transition hover:bg-rose-100 hover:text-rose-700 cursor-pointer ml-1"
          title="Xóa câu hỏi này"
          @click="$emit('remove', index)"
        >
          <Trash2 class="h-3.5 w-3.5" />
          <span>Xóa câu</span>
        </button>
      </div>
    </div>

    <!-- 1. Question Content Input -->
    <div class="space-y-1.5">
      <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700">
        <span class="h-3.5 w-1 rounded-full bg-[#7C3AED]"></span>
        <span>Nội dung câu hỏi <span class="text-rose-500">*</span></span>
      </label>
      <textarea
        v-model="question.content"
        rows="3"
        required
        class="w-full resize-none rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-medium leading-relaxed text-slate-900 outline-none transition focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20"
        placeholder="Nhập nội dung câu hỏi tại đây..."
      ></textarea>
    </div>

    <!-- 2. Question Configuration: Type, Points, Difficulty -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
      <div class="space-y-1">
        <label class="text-[11px] font-bold text-slate-600 block">Loại câu hỏi</label>
        <select
          v-model="question.type"
          class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 cursor-pointer"
          @change="handleTypeChange"
        >
          <option value="single_choice">Trắc nghiệm 1 đáp án đúng</option>
          <option value="multi_choice">Nhiều đáp án đúng</option>
          <option value="true_false">Đúng / Sai</option>
        </select>
      </div>

      <div class="space-y-1">
        <div class="flex items-center justify-between">
          <label class="text-[11px] font-bold text-slate-600">Điểm số</label>
          <span
            v-if="question.is_manual"
            class="inline-flex items-center gap-0.5 text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1 py-0.2 rounded"
            title="Điểm số đã được chỉnh sửa thủ công"
          >
            <Lock :size="9" />
            <span>Đã chỉnh</span>
          </span>
          <button
            v-if="question.is_manual"
            type="button"
            @click="resetToAuto"
            class="text-[10px] text-slate-400 hover:text-purple-600 underline cursor-pointer"
            title="Trả về tự động phân bổ điểm"
          >
            Tự động
          </button>
        </div>
        <input
          data-points-input
          v-model.number="question.points"
          type="number"
          step="any"
          min="0.01"
          max="10"
          @input="onPointsInput"
          class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-purple-700 outline-none focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 text-center"
        />
      </div>

      <div class="space-y-1">
        <label class="text-[11px] font-bold text-slate-600 block">Độ khó</label>
        <select
          v-model="question.difficulty"
          class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20 cursor-pointer"
        >
          <option value="easy">Dễ (Nhận biết)</option>
          <option value="medium">Vừa (Thông hiểu)</option>
          <option value="hard">Khó (Vận dụng)</option>
        </select>
      </div>
    </div>

    <!-- 3. Answers Section (Clean UI Matching Create Quiz) -->
    <div class="space-y-3 pt-3 border-t border-slate-100">
      <div class="flex items-center justify-between gap-2">
        <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700">
          <span class="h-3.5 w-1 rounded-full bg-[#7C3AED]"></span>
          <span>Danh sách đáp án (Tick chọn đáp án đúng)</span>
        </label>

        <button
          v-if="question.type !== 'true_false'"
          type="button"
          class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-[#7C3AED] transition hover:bg-purple-50 cursor-pointer"
          @click="addAnswerChoice"
        >
          <Plus class="h-3.5 w-3.5" />
          <span>Thêm đáp án</span>
        </button>
      </div>

      <!-- Answers List -->
      <div class="grid gap-2.5">
        <div
          v-for="(ans, aIdx) in question.answers"
          :key="ans._uid || ans.id || aIdx"
          class="flex items-center gap-2.5 rounded-xl border p-2 sm:p-2.5 transition"
          :class="ans.is_correct
            ? 'border-emerald-300 bg-emerald-50/70 shadow-sm'
            : 'border-slate-200 bg-slate-50/60 hover:border-slate-300'"
        >
          <!-- Answer Key Badge (A, B, C, D) -->
          <span
            class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-xs font-bold transition"
            :class="ans.is_correct
              ? 'bg-emerald-500 text-white shadow-sm'
              : 'bg-white text-slate-700 border border-slate-200'"
          >
            {{ getAnswerKey(aIdx) }}
          </span>

          <!-- Answer Content Input -->
          <input
            v-model="ans.content"
            type="text"
            required
            class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20"
            :placeholder="`Nội dung đáp án ${getAnswerKey(aIdx)}...`"
          />

          <!-- Correct Answer Selector Pill -->
          <label
            class="flex shrink-0 cursor-pointer select-none items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-bold transition"
            :class="ans.is_correct
              ? 'border-emerald-300 bg-emerald-100 text-emerald-800'
              : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300'"
          >
            <!-- Radio for Single Choice / True False -->
            <input
              v-if="question.type !== 'multi_choice'"
              type="radio"
              :name="'correct_choice_' + questionUid"
              :checked="ans.is_correct"
              class="h-3.5 w-3.5 cursor-pointer accent-emerald-500"
              @change="setCorrectSingle(aIdx)"
            />
            <!-- Checkbox for Multiple Choice -->
            <input
              v-else
              type="checkbox"
              v-model="ans.is_correct"
              class="h-3.5 w-3.5 rounded cursor-pointer accent-emerald-500"
            />

            <span class="inline-flex items-center gap-1">
              <Check v-if="ans.is_correct" class="h-3 w-3" />
              <span>{{ ans.is_correct ? 'Đúng' : 'Đánh dấu đúng' }}</span>
            </span>
          </label>

          <!-- Remove Answer Button -->
          <button
            v-if="question.type !== 'true_false' && question.answers.length > 2"
            type="button"
            class="grid h-7 w-7 shrink-0 place-items-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 cursor-pointer"
            aria-label="Xóa đáp án này"
            title="Xóa đáp án này"
            @click="removeAnswerChoice(aIdx)"
          >
            <X class="h-3.5 w-3.5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  ArrowUp,
  ArrowDown,
  Trash2,
  Plus,
  Check,
  X,
  Lock,
} from 'lucide-vue-next'

const props = defineProps({
  question: {
    type: Object,
    required: true,
  },
  index: {
    type: Number,
    required: true,
  },
  total: {
    type: Number,
    required: true,
  },
})

const emit = defineEmits(['move-up', 'move-down', 'remove', 'manual-point-change', 'reset-point-auto'])

const onPointsInput = () => {
  props.question.is_manual = true
  emit('manual-point-change', { index: props.index, question: props.question })
}

const resetToAuto = () => {
  props.question.is_manual = false
  emit('reset-point-auto', { index: props.index, question: props.question })
}

// Generate or use stable unique local ID for scoped radio names
const questionUid = computed(() => {
  if (!props.question._uid) {
    props.question._uid = `q_${props.question.id || 'new'}_${Math.random().toString(36).substring(2, 9)}`
  }
  return props.question._uid
})

const getAnswerKey = (idx) => {
  return String.fromCharCode(65 + idx)
}

const handleTypeChange = () => {
  if (props.question.type === 'true_false') {
    // Standardize to 2 answers for True/False
    props.question.answers = [
      { id: null, _uid: `ans_${Math.random()}`, content: 'Đúng', is_correct: true, order: 0 },
      { id: null, _uid: `ans_${Math.random()}`, content: 'Sai', is_correct: false, order: 1 },
    ]
  } else if (props.question.type === 'single_choice') {
    // Ensure only 1 correct answer is selected
    const firstCorrectIndex = props.question.answers.findIndex(a => a.is_correct)
    props.question.answers.forEach((a, idx) => {
      a.is_correct = idx === (firstCorrectIndex >= 0 ? firstCorrectIndex : 0)
    })
  }
}

const addAnswerChoice = () => {
  const currentCount = props.question.answers.length
  props.question.answers.push({
    id: null,
    _uid: `ans_${Date.now()}_${Math.random().toString(36).substring(2, 7)}`,
    content: '',
    is_correct: false,
    order: currentCount,
  })
}

const removeAnswerChoice = (aIdx) => {
  if (props.question.answers.length <= 2) return
  const wasCorrect = props.question.answers[aIdx].is_correct
  props.question.answers.splice(aIdx, 1)

  // If removed answer was the only correct one in single_choice, select the first remaining
  if (wasCorrect && props.question.type !== 'multi_choice') {
    if (!props.question.answers.some(a => a.is_correct)) {
      props.question.answers[0].is_correct = true
    }
  }
}

const setCorrectSingle = (selectedIdx) => {
  props.question.answers.forEach((ans, idx) => {
    ans.is_correct = idx === selectedIdx
  })
}
</script>
