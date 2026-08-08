<template>
  <section
    v-if="currentView === 'upload'"
    class="grid gap-6 xl:grid-cols-[1fr_420px]"
  >
    <article
      class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl"
    >
      <div class="relative z-10">
        <p
          class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]"
        >
          OCR Upload
        </p>

        <h1
          class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]"
        >
          Upload Image OCR
        </h1>

        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">
          Upload ảnh, gọi backend OCR và preview nội dung trước khi chuyển thành
          quiz.
        </p>

        <div
          class="mt-6 grid gap-3 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4"
        >
          <p
            class="text-xs font-black uppercase tracking-[0.18em] text-[var(--muted)]"
          >
            Chọn loại đề trước khi OCR
          </p>

          <div class="flex flex-wrap gap-3">
            <button
              type="button"
              class="rounded-full px-4 py-2 text-sm font-black transition disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="isUploading"
              :class="
                ocrMode === 'normal'
                  ? 'bg-[var(--primary)] text-white'
                  : 'bg-[var(--chip-active)] text-[var(--muted)]'
              "
              @click="ocrMode = 'normal'"
            >
              Đề thường
            </button>

            <button
              type="button"
              class="rounded-full px-4 py-2 text-sm font-black transition disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="isUploading"
              :class="
                ocrMode === 'math'
                  ? 'bg-[var(--primary)] text-white'
                  : 'bg-[var(--chip-active)] text-[var(--muted)]'
              "
              @click="ocrMode = 'math'"
            >
              Đề toán / có công thức
            </button>
          </div>

          <p class="text-xs leading-6 text-[var(--muted)]">
            Đề toán sẽ yêu cầu AI bọc công thức bằng $...$ để editor tự render
            MathLive.
          </p>
        </div>

        <label
          :class="[
            'mt-6 grid min-h-[260px] place-items-center rounded-[2rem] border-2 border-dashed border-[var(--border-strong)] bg-[var(--chip-active)] p-8 text-center transition duration-300',
            isUploading
              ? 'cursor-not-allowed opacity-60'
              : 'cursor-pointer hover:-translate-y-1',
          ]"
        >
          <input
            ref="fileInput"
            class="hidden"
            type="file"
            accept="image/*,.pdf,application/pdf"
            :disabled="isUploading"
            @change="handleFile"
          />

          <div>
            <div
              class="mx-auto mb-5 grid h-20 w-20 place-items-center rounded-[1.5rem] bg-gradient-to-br from-[var(--primary)] to-[var(--primary-2)] text-3xl text-white"
            >
              OCR
            </div>

            <h2 class="text-2xl font-black text-[var(--text)]">
              {{
                isUploading
                  ? "Đang xử lý file..."
                  : "Thả file vào đây hoặc bấm để chọn"
              }}
            </h2>

            <p class="mt-2 text-sm font-semibold text-[var(--muted)]">
              PNG, JPG, JPEG, WEBP, BMP, TIFF, PDF. Tối đa 20MB.
            </p>
          </div>
        </label>

        <div
          v-if="fileName"
          class="mt-6 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5"
        >
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <b class="text-[var(--text)]">{{ fileName }}</b>
              <p class="mt-1 text-sm text-[var(--muted)]">
                {{ isUploading ? loadingText : "OCR đã sẵn sàng" }}
              </p>
            </div>

            <span
              class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-black text-emerald-400"
            >
              {{ Math.floor(progress) }}%
            </span>
          </div>

          <div
            class="mt-4 h-3 overflow-hidden rounded-full bg-[var(--surface)]"
          >
            <div
              class="h-full rounded-full bg-gradient-to-r from-[var(--primary)] via-[var(--primary-2)] to-[var(--accent)] transition-all duration-700"
              :class="{ 'animate-pulse': isUploading }"
              :style="{ width: `${progress}%` }"
            ></div>
          </div>
        </div>

        <div
          v-if="totalQuestions > 0"
          class="mt-5 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-300"
        >
          ✅ Đã render {{ totalQuestions }} câu hỏi
          <span v-if="showReadyMessage">. Đang chuyển sang editor...</span>
        </div>

        <div
          v-if="errorMessage"
          class="mt-5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300"
        >
          {{ errorMessage }}
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
          <button
            class="btn-ghost disabled:cursor-not-allowed disabled:opacity-50"
            type="button"
            :disabled="isUploading"
            @click="resetFile"
          >
            Xóa file
          </button>

          <button
            class="btn-primary disabled:cursor-not-allowed disabled:opacity-50"
            type="button"
            :disabled="isUploading || !totalQuestions"
            @click="currentView = 'edit'"
          >
            {{ isUploading ? "Đang xử lý..." : "Chuyển sang editor" }}
          </button>
        </div>
      </div>
    </article>

    <aside class="grid content-start gap-5">
      <article
        class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl"
      >
        <p
          class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]"
        >
          OCR Preview
        </p>

        <h2 class="mt-2 text-2xl font-black text-[var(--text)]">
          Kết quả trích xuất
        </h2>

        <div
          class="mt-5 max-h-[420px] overflow-auto rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm leading-7 text-[var(--muted)] scrollbar-soft"
        >
          <p v-for="line in ocrLines" :key="line" class="mb-3">
            {{ line }}
          </p>
        </div>
      </article>

      <article
        class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl"
      >
        <p
          class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]"
        >
          AI suggestion
        </p>

        <div class="mt-4 grid gap-3">
          <div
            v-for="item in suggestions"
            :key="item"
            class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 text-sm font-bold text-[var(--muted)]"
          >
            {{ item }}
          </div>
        </div>
      </article>
    </aside>
  </section>

  <section v-else class="grid gap-6">
    <article
      class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl"
    >
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p
            class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]"
          >
            Question Editor
          </p>

          <h1
            class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]"
          >
            Chỉnh sửa câu hỏi
          </h1>

          <p class="mt-3 text-sm text-[var(--muted)]">
            Kiểm tra lại câu hỏi OCR/AI trước khi lưu vào database.
          </p>

          <div
            class="mt-4 inline-flex rounded-full border border-emerald-500/30 bg-emerald-500/10 px-4 py-2 text-sm font-black text-emerald-300"
          >
            Tổng số câu hỏi: {{ totalQuestions }}
          </div>
        </div>

        <button class="btn-ghost" type="button" @click="goBackUpload">
          Quay lại OCR
        </button>
      </div>

      <div
        v-if="isDirty"
        class="mt-5 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm font-bold text-amber-300"
      >
        ⚠️ Bạn đang có thay đổi chưa lưu. Reload hoặc thoát trang sẽ mất dữ
        liệu.
      </div>

      <div
        class="mt-6 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5"
      >
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p
              class="text-xs font-black uppercase tracking-[0.18em] text-[var(--primary)]"
            >
              Quiz Information
            </p>

            <h2 class="mt-1 text-2xl font-black text-[var(--text)]">
              Thông tin bộ đề
            </h2>
          </div>

          <span
            class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--muted)]"
          >
            {{ quizInfo.status === "published" ? "Công khai" : "Nháp" }}
          </span>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
          <div>
            <label class="text-xs font-black uppercase text-[var(--muted)]">
              Tên bộ đề *
            </label>

            <input
              v-model="quizInfo.title"
              class="mt-2 w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-semibold text-[var(--text)] outline-none"
              placeholder="Ví dụ: Ôn tập Toán 12 chương 1"
              maxlength="255"
              @input="markDirty"
            />
          </div>

          <div>
            <label class="text-xs font-black uppercase text-[var(--muted)]">
              Môn học
            </label>

            <input
              v-model="quizInfo.subject"
              class="mt-2 w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-semibold text-[var(--text)] outline-none"
              placeholder="Ví dụ: Toán, Lý, Hóa..."
              @input="markDirty"
            />
          </div>

          <div>
            <label class="text-xs font-black uppercase text-[var(--muted)]">
              Lớp
            </label>

            <input
              v-model="quizInfo.grade"
              class="mt-2 w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-semibold text-[var(--text)] outline-none"
              placeholder="Ví dụ: 12"
              @input="markDirty"
            />
          </div>

          <div>
            <label class="text-xs font-black uppercase text-[var(--muted)]">
              Thời gian làm bài
            </label>

            <div class="mt-2 flex items-center gap-2">
              <input
                v-model.number="quizInfo.duration"
                type="number"
                min="1"
                max="1440"
                class="w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-semibold text-[var(--text)] outline-none"
                @input="markDirty"
              />

              <span class="text-sm font-bold text-[var(--muted)]">phút</span>
            </div>
          </div>

          <div>
            <label class="text-xs font-black uppercase text-[var(--muted)]">
              Điểm mặc định mỗi câu
            </label>

            <input
              v-model.number="quizInfo.default_points"
              type="number"
              min="1"
              max="1000"
              class="mt-2 w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-semibold text-[var(--text)] outline-none"
              @input="markDirty"
            />
          </div>

          <div>
            <label class="text-xs font-black uppercase text-[var(--muted)]">
              Trạng thái
            </label>

            <select
              v-model="quizInfo.status"
              class="mt-2 w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-bold text-[var(--text)] outline-none"
              @change="markDirty"
            >
              <option value="draft">Nháp</option>
              <option value="published">Công khai</option>
            </select>
          </div>
        </div>

        <div class="mt-4">
          <label class="text-xs font-black uppercase text-[var(--muted)]">
            Mô tả
          </label>

          <textarea
            v-model="quizInfo.description"
            rows="3"
            class="mt-2 w-full resize-none rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-semibold leading-7 text-[var(--text)] outline-none"
            placeholder="Ghi chú ngắn về bộ đề..."
            :style="getNormalTextareaStyle(quizInfo.description)"
            @input="markDirty"
          ></textarea>
        </div>
      </div>

      <div
        class="mt-6 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5"
      >
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p
              class="text-xs font-black uppercase tracking-[0.18em] text-[var(--primary)]"
            >
              AI Assistant
            </p>

            <h2 class="mt-1 text-xl font-black text-[var(--text)]">
              Gợi ý bằng AI
            </h2>
          </div>

          <div class="flex flex-wrap gap-2">
            <span
              class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--muted)]"
            >
              {{
                selectedQuestionIds.length
                  ? `Đã chọn ${selectedQuestionIds.length} câu`
                  : "Toàn bộ đề"
              }}
            </span>

            <button
              v-if="selectedQuestionIds.length"
              type="button"
              class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--text)]"
              @click="clearSelectedQuestions"
            >
              Bỏ chọn
            </button>
          </div>
        </div>

        <div v-if="selectedQuestions.length" class="mt-3 flex flex-wrap gap-2">
          <span
            v-for="(question, index) in selectedQuestions"
            :key="question.id"
            class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--muted)]"
          >
            Câu {{ getQuestionNumber(question.id) }}
          </span>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-[1.5fr_1fr_1fr_auto]">
          <div>
            <label class="text-xs font-black uppercase text-[var(--muted)]">
              AI muốn làm gì?
            </label>

            <select
              v-model="aiOptions.action"
              class="mt-2 w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-bold text-[var(--text)] outline-none"
            >
              <option value="similar">Tạo câu tương tự</option>
              <option value="generate_by_difficulty">Tạo theo độ khó</option>
              <option value="better_options">Tạo đáp án nhiễu</option>
              <option value="analyze_quiz">Đánh giá toàn bộ đề</option>
            </select>
          </div>

          <div>
            <label class="text-xs font-black uppercase text-[var(--muted)]">
              Độ khó
            </label>

            <select
              v-model="aiOptions.difficulty"
              class="mt-2 w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-bold text-[var(--text)] outline-none"
              :disabled="aiOptions.action === 'analyze_quiz'"
            >
              <option value="easy">Dễ</option>
              <option value="medium">Trung bình</option>
              <option value="hard">Khó</option>
              <option value="advanced">Nâng cao</option>
            </select>
          </div>

          <div>
            <label class="text-xs font-black uppercase text-[var(--muted)]">
              Số câu
            </label>

            <input
              v-model.number="aiOptions.count"
              type="number"
              min="1"
              max="20"
              class="mt-2 w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-bold text-[var(--text)] outline-none"
              :disabled="aiOptions.action === 'analyze_quiz'"
            />
          </div>

          <div class="flex items-end">
            <button
              type="button"
              class="btn-primary w-full"
              @click="generateAiSuggestions(aiOptions.action)"
            >
              Tạo gợi ý
            </button>
          </div>
        </div>
        <div
          v-if="aiLoading"
          class="mt-5 rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-bold text-[var(--muted)]"
        >
          AI đang xử lý...
        </div>

        <div v-if="aiSuggestions.length" class="mt-5 grid gap-4">
          <div
            v-for="item in aiSuggestions"
            :key="item.id"
            class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4"
          >
            <template v-if="item.type === 'analysis'">
              <p class="text-sm font-black text-[var(--primary)]">
                Đánh giá nhanh bộ đề
              </p>

              <p class="mt-3 text-sm font-bold leading-7 text-[var(--text)]">
                {{ item.summary }}
              </p>

              <div class="mt-3 grid gap-2">
                <p
                  v-for="point in item.recommendations"
                  :key="point"
                  class="rounded-xl bg-[var(--surface-soft)] p-3 text-xs font-bold leading-6 text-[var(--muted)]"
                >
                  {{ point }}
                </p>
                <div
                  v-if="item.actions?.length"
                  class="mt-4 flex flex-wrap gap-2"
                >
                  <button
                    v-for="actionItem in item.actions"
                    :key="actionItem.label"
                    type="button"
                    class="rounded-full bg-[var(--primary)] px-4 py-2 text-xs font-black text-white"
                    @click="runAnalysisAction(actionItem)"
                  >
                    {{ actionItem.label }}
                  </button>
                </div>
              </div>
            </template>

            <template v-else>
              <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm font-black text-[var(--primary)]">
                  {{ item.type_label }}
                </p>

                <span
                  class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--muted)]"
                >
                  {{ item.difficulty_label }}
                </span>
              </div>

              <p class="mt-3 text-sm font-bold leading-7 text-[var(--text)]">
                {{ item.question }}
              </p>

              <div
                v-if="item.options"
                class="mt-3 grid gap-2 text-xs font-bold text-[var(--muted)]"
              >
                <p v-for="(value, key) in item.options" :key="key">
                  {{ key }}. {{ value }}
                </p>
              </div>

              <div class="mt-3 rounded-xl bg-[var(--surface-soft)] p-3">
                <p class="text-xs font-black uppercase text-[var(--muted)]">
                  💡 Tóm tắt hướng giải
                </p>

                <p class="mt-2 text-xs leading-6 text-[var(--muted)]">
                  {{ item.solution_summary }}
                </p>
              </div>

              <div class="mt-3 flex flex-wrap gap-2">
                <span
                  v-for="point in item.knowledge_points"
                  :key="point"
                  class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--muted)]"
                >
                  {{ point }}
                </span>
              </div>

              <button
                type="button"
                class="mt-4 rounded-full bg-[var(--primary)] px-4 py-2 text-xs font-black text-white"
                @click="applyAiSuggestion(item)"
              >
                Thêm vào đề
              </button>
            </template>
          </div>
        </div>
      </div>

      <div class="mt-8 grid gap-5">
        <div
          v-for="(q, index) in questions"
          :key="q.id"
          class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5"
        >
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
              <label
                class="flex cursor-pointer items-center gap-2 rounded-full border border-[var(--border)] bg-[var(--surface)] px-3 py-1 text-xs font-black text-[var(--muted)]"
              >
                <input
                  type="checkbox"
                  :value="q.id"
                  v-model="selectedQuestionIds"
                />
                Chọn
              </label>

              <p class="text-sm font-black text-[var(--primary)]">
                Câu {{ index + 1 }}
              </p>

              <select
                v-model="q.type"
                class="rounded-full border border-[var(--border)] bg-[var(--surface)] px-3 py-1 text-xs font-black text-[var(--muted)] outline-none"
                @change="changeQuestionType(q)"
              >
                <option value="single_choice">Một đáp án</option>
                <option value="multi_choice">Nhiều đáp án</option>
                <option value="fill_blank">Điền đáp án</option>
              </select>
            </div>

            <div class="flex flex-wrap gap-2">
              <input
                :ref="(el) => setQuestionImageInputRef(el, index)"
                type="file"
                accept="image/*"
                class="hidden"
                @change="handleQuestionImage($event, q)"
              />

              <button
                type="button"
                class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--text)]"
                @click="openQuestionImagePicker(index)"
              >
                + Ảnh
              </button>

              <button
                type="button"
                class="rounded-full bg-rose-500/10 px-3 py-1 text-xs font-black text-rose-300"
                @click="removeQuestion(index)"
              >
                Xóa
              </button>
            </div>
          </div>

          <div
            v-if="q.allow_math"
            class="mt-3 flex flex-wrap items-center gap-2"
          >
            <span class="text-xs font-black uppercase text-[var(--muted)]">
              Chế độ sửa
            </span>

            <button
              type="button"
              class="rounded-full px-3 py-1 text-xs font-black transition"
              :class="
                q.editor_mode === 'normal'
                  ? 'bg-[var(--primary)] text-white'
                  : 'bg-[var(--surface)] text-[var(--muted)]'
              "
              @click="setQuestionMode(q, 'normal')"
            >
              Thường
            </button>

            <button
              type="button"
              class="rounded-full px-3 py-1 text-xs font-black transition"
              :class="
                q.editor_mode === 'math'
                  ? 'bg-[var(--primary)] text-white'
                  : 'bg-[var(--surface)] text-[var(--muted)]'
              "
              @click="setQuestionMode(q, 'math')"
            >
              Math
            </button>
          </div>

          <div
            v-else
            class="mt-3 inline-flex rounded-full border border-[var(--border)] bg-[var(--surface)] px-3 py-1 text-xs font-black text-[var(--muted)]"
          >
            Chế độ mặc định
          </div>

          <textarea
            v-if="q.editor_mode === 'normal'"
            v-model="q.question"
            rows="3"
            class="mt-3 w-full resize-none rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-semibold leading-7 text-[var(--text)] outline-none"
            :style="getNormalTextareaStyle(q.question)"
            @input="markDirty"
          ></textarea>

          <div
            v-else
            class="mt-3 rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4"
          >
            <draggable
              v-model="q.question_blocks"
              item-key="id"
              class="math-block-row"
              v-bind="dragOptions"
              @change="markDirty"
            >
              <template #item="{ element: block, index: blockIndex }">
                <div class="editor-block group">
                  <button type="button" class="drag-handle">☰</button>

                  <textarea
                    v-if="block.type === 'text'"
                    v-model="block.value"
                    rows="1"
                    class="editor-text-input"
                    placeholder="Nhập chữ..."
                    :style="getTextBlockStyle(block.value)"
                    @input="markDirty"
                  ></textarea>

                  <math-field
                    v-else
                    :value="block.value"
                    :style="getMathBlockStyle(block.value)"
                    virtual-keyboard-mode="manual"
                    class="editor-math-field"
                    @input="
                      updateMathBlock(q.question_blocks, blockIndex, $event)
                    "
                  />

                  <button
                    type="button"
                    class="editor-block-remove"
                    @click="removeBlock(q.question_blocks, blockIndex)"
                  >
                    ×
                  </button>
                </div>
              </template>
            </draggable>

            <div class="mt-3 flex flex-wrap gap-2">
              <button
                class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--text)]"
                type="button"
                @click="addTextBlock(q.question_blocks)"
              >
                + Chữ
              </button>

              <button
                class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--text)]"
                type="button"
                @click="addMathBlock(q.question_blocks)"
              >
                + Công thức
              </button>
            </div>
          </div>

          <div
            v-if="q.images?.length"
            class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
          >
            <div
              v-for="(image, imageIndex) in q.images"
              :key="image.id"
              class="overflow-hidden rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-2"
            >
              <img
                :src="image.preview"
                :alt="image.name"
                class="h-40 w-full rounded-xl object-contain"
              />

              <div class="mt-2 flex items-center justify-between gap-2">
                <p class="truncate text-xs font-bold text-[var(--muted)]">
                  {{ image.name }}
                </p>

                <button
                  type="button"
                  class="rounded-full bg-rose-500/10 px-3 py-1 text-xs font-black text-rose-300"
                  @click="removeQuestionImage(q, imageIndex)"
                >
                  Xóa ảnh
                </button>
              </div>
            </div>
          </div>

          <div
            v-if="q.type !== 'fill_blank'"
            class="mt-4 grid gap-3 md:grid-cols-2"
          >
            <div v-for="(optionText, optionKey) in q.options" :key="optionKey">
              <label class="text-xs font-black uppercase text-[var(--muted)]">
                Đáp án {{ optionKey }}
              </label>

              <textarea
                v-if="q.editor_mode === 'normal'"
                v-model="q.options[optionKey]"
                rows="1"
                class="mt-2 w-full resize-none rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-semibold leading-6 text-[var(--text)] outline-none"
                :style="getNormalTextareaStyle(q.options[optionKey])"
                @input="markDirty"
              ></textarea>

              <div
                v-else
                class="mt-2 rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-3"
              >
                <draggable
                  v-model="q.option_blocks[optionKey]"
                  item-key="id"
                  class="math-block-row"
                  v-bind="dragOptions"
                  @change="markDirty"
                >
                  <template #item="{ element: block, index: blockIndex }">
                    <div class="editor-block group">
                      <button type="button" class="drag-handle">☰</button>

                      <textarea
                        v-if="block.type === 'text'"
                        v-model="block.value"
                        rows="1"
                        class="editor-text-input"
                        placeholder="Nhập chữ..."
                        :style="getTextBlockStyle(block.value)"
                        @input="markDirty"
                      ></textarea>

                      <math-field
                        v-else
                        :value="block.value"
                        :style="getMathBlockStyle(block.value)"
                        virtual-keyboard-mode="manual"
                        class="editor-math-field"
                        @input="
                          updateMathBlock(
                            q.option_blocks[optionKey],
                            blockIndex,
                            $event,
                          )
                        "
                      />

                      <button
                        type="button"
                        class="editor-block-remove"
                        @click="
                          removeBlock(q.option_blocks[optionKey], blockIndex)
                        "
                      >
                        ×
                      </button>
                    </div>
                  </template>
                </draggable>

                <div class="mt-2 flex flex-wrap gap-2">
                  <button
                    class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--text)]"
                    type="button"
                    @click="addTextBlock(q.option_blocks[optionKey])"
                  >
                    + Chữ
                  </button>

                  <button
                    class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--text)]"
                    type="button"
                    @click="addMathBlock(q.option_blocks[optionKey])"
                  >
                    + Math
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div v-if="q.type === 'single_choice'" class="mt-4">
            <label class="text-xs font-black uppercase text-[var(--muted)]">
              Đáp án đúng
            </label>

            <select
              v-model="q.correct_answer"
              class="mt-2 w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-bold text-[var(--text)] outline-none"
              @change="markDirty"
            >
              <option :value="null">Chưa chọn</option>

              <option
                v-for="(_, optionKey) in q.options"
                :key="optionKey"
                :value="optionKey"
              >
                {{ optionKey }}
              </option>
            </select>
          </div>

          <div v-else-if="q.type === 'multi_choice'" class="mt-4">
            <label class="text-xs font-black uppercase text-[var(--muted)]">
              Đáp án đúng nhiều lựa chọn
            </label>

            <div class="mt-2 flex flex-wrap gap-2">
              <label
                v-for="(_, optionKey) in q.options"
                :key="optionKey"
                class="flex cursor-pointer items-center gap-2 rounded-full border border-[var(--border)] bg-[var(--surface)] px-4 py-2 text-sm font-bold text-[var(--text)]"
              >
                <input
                  type="checkbox"
                  :value="optionKey"
                  v-model="q.correct_answer"
                  @change="markDirty"
                />

                {{ optionKey }}
              </label>
            </div>
          </div>

          <div v-else class="mt-4">
            <label class="text-xs font-black uppercase text-[var(--muted)]">
              Các đáp án điền đúng
            </label>

            <div class="mt-2 grid gap-2">
              <div
                v-for="(answer, answerIndex) in q.correct_answer"
                :key="answerIndex"
                class="flex gap-2"
              >
                <textarea
                  v-model="q.correct_answer[answerIndex]"
                  rows="1"
                  class="w-full resize-none rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-semibold leading-6 text-[var(--text)] outline-none"
                  :style="getNormalTextareaStyle(q.correct_answer[answerIndex])"
                  placeholder="Nhập một đáp án đúng..."
                  @input="markDirty"
                ></textarea>

                <button
                  type="button"
                  class="rounded-full bg-rose-500/10 px-3 text-xs font-black text-rose-300"
                  @click="removeFillAnswer(q, answerIndex)"
                >
                  Xóa
                </button>
              </div>
            </div>

            <button
              type="button"
              class="mt-3 rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--text)]"
              @click="addFillAnswer(q)"
            >
              + Thêm đáp án đúng
            </button>
          </div>
        </div>
      </div>

      <div class="mt-8 flex flex-wrap gap-3">
        <button
          class="btn-ghost"
          type="button"
          @click="addQuestion('single_choice')"
        >
          + Một đáp án
        </button>

        <button
          class="btn-ghost"
          type="button"
          @click="addQuestion('multi_choice')"
        >
          + Nhiều đáp án
        </button>

        <button
          class="btn-ghost"
          type="button"
          @click="addQuestion('fill_blank')"
        >
          + Điền đáp án
        </button>

        <button class="btn-primary" type="button" @click="saveQuestions">
          Lưu câu hỏi
        </button>
      </div>
    </article>
  </section>

  <!-- Custom Toast Message -->
  <div
    v-if="toast.isOpen"
    class="fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-2xl border px-4 py-3 shadow-lg backdrop-blur-xl transition-all duration-300"
    :class="
      toast.type === 'success'
        ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400 shadow-[0_12px_40px_rgba(16,185,129,0.1)]'
        : 'border-rose-500/30 bg-rose-500/10 text-rose-400 shadow-[0_12px_40px_rgba(244,63,94,0.1)]'
    "
  >
    <span class="text-base">{{ toast.type === 'success' ? '✅' : '❌' }}</span>
    <span class="text-sm font-bold">{{ toast.message }}</span>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import draggable from "vuedraggable";
import "mathlive";
import "mathlive/static.css";
import { importOcrQuiz, ocrApi, aiQuizApi } from "@/services/api";

const route = useRoute();
const router = useRouter();

const questionBase = computed(() =>
  route.path.startsWith("/dashboard")
    ? "/dashboard/questions"
    : "/admin/questions",
);

const fileInput = ref(null);
const fileName = ref("");
const progress = ref(0);
const isUploading = ref(false);
const errorMessage = ref("");
const loadingText = ref("Đang tải file...");
const progressTimer = ref(null);
const ALLOWED_OCR_TYPES = [
  "image/png", "image/jpeg", "image/jpg", "image/webp", "image/bmp", "image/tiff",
  "application/pdf",
];
const MAX_OCR_FILE_SIZE = 20 * 1024 * 1024;

const currentView = ref("upload");
const ocrMode = ref("normal");
const questions = ref([]);
const questionImageInputs = ref([]);
const showReadyMessage = ref(false);
const saving = ref(false);
const isDirty = ref(false);
const selectedQuestionIds = ref([]);
const aiSuggestions = ref([]);
const aiLoading = ref(false);

const toast = ref({
  isOpen: false,
  message: "",
  type: "success",
});
let toastTimer = null;
const showToast = (message, type = "success") => {
  if (toastTimer) clearTimeout(toastTimer);
  toast.value.message = message;
  toast.value.type = type;
  toast.value.isOpen = true;
  toastTimer = setTimeout(() => {
    toast.value.isOpen = false;
  }, 3000);
};

const aiOptions = ref({
  action: "similar",
  difficulty: "medium",
  count: 3,
  scope: "selected",
});

const getQuestionNumber = (id) => {
  return questions.value.findIndex((q) => q.id === id) + 1;
};

const runAnalysisAction = async (actionItem) => {
  aiOptions.value.action = actionItem.action;
  aiOptions.value.difficulty = actionItem.difficulty || "medium";
  aiOptions.value.count = actionItem.count || 3;
  aiOptions.value.scope = "all";

  await generateAiSuggestions(actionItem.action);
};

const selectedQuestions = computed(() => {
  return questions.value.filter((q) =>
    selectedQuestionIds.value.includes(q.id),
  );
});

const quizInfo = ref({
  title: "",
  description: "",
  subject: "",
  grade: "",
  duration: 45,
  default_points: 10,
  status: "draft",
});

const totalQuestions = computed(() => questions.value.length);

const dragOptions = {
  animation: 180,
  handle: ".drag-handle",
  ghostClass: "drag-ghost",
};

const suggestions = [
  "Tạo 10 câu mức trung bình",
  "Ưu tiên câu hỏi khái niệm",
  "Thêm 2 câu vận dụng",
  "Giữ visibility: Private trước khi duyệt",
];

const markDirty = () => {
  isDirty.value = true;
};

const beforeUnloadHandler = (event) => {
  if (!isDirty.value) return;

  event.preventDefault();
  event.returnValue = "";
};

onMounted(() => {
  window.addEventListener("beforeunload", beforeUnloadHandler);
});

onBeforeUnmount(() => {
  window.removeEventListener("beforeunload", beforeUnloadHandler);
});

const ocrLines = computed(() => {
  if (!questions.value.length) {
    return ["Chưa có nội dung OCR. Upload ảnh để backend xử lý."];
  }

  return questions.value.map((item, index) => {
    return `Câu ${index + 1}: ${getQuestionText(item)}`;
  });
});

const createId = () => `${Date.now()}_${Math.random().toString(16).slice(2)}`;

const createBlock = (type, value = "") => ({
  id: createId(),
  type,
  value,
});

const hasMath = (text = "") => {
  return /(\$.*?\$|\\frac|\\sqrt|\\sum|\\int|\^|_|≤|≥|≠|π|∞|sin|cos|tan|log|ln|[A-Z]'{1}|[a-zA-Z]\d|\d+\/\d+)/.test(
    String(text),
  );
};

const parseBlocks = (text = "") => {
  const value = String(text || "");
  const regex = /\$(.*?)\$/g;
  const blocks = [];
  let lastIndex = 0;
  let match;

  while ((match = regex.exec(value)) !== null) {
    if (match.index > lastIndex) {
      blocks.push(createBlock("text", value.slice(lastIndex, match.index)));
    }

    blocks.push(createBlock("math", match[1]));

    lastIndex = regex.lastIndex;
  }

  if (lastIndex < value.length) {
    blocks.push(createBlock("text", value.slice(lastIndex)));
  }

  if (!blocks.length) {
    blocks.push(createBlock("text", value));
  }

  return blocks;
};

const blocksToString = (blocks = []) => {
  return blocks
    .map((block) => {
      if (block.type === "math") return `$${block.value}$`;
      return block.value;
    })
    .join("")
    .trim();
};

const normalizeOptions = (options, type = "single_choice") => {
  if (type === "fill_blank") return null;

  if (!options) {
    return {
      A: "",
      B: "",
      C: "",
      D: "",
    };
  }

  return {
    A: options.A || "",
    B: options.B || "",
    C: options.C || "",
    D: options.D || "",
  };
};

const getQuestionTypeLabel = (type) => {
  if (type === "multi_choice") return "Nhiều đáp án";
  if (type === "fill_blank") return "Điền đáp án";
  return "Một đáp án";
};

const normalizeQuestion = (item = {}) => {
  const type = item.type || "single_choice";
  const questionText = item.question || "";
  const options = normalizeOptions(item.options, type);

  const shouldUseMath =
    ocrMode.value === "math" &&
    (hasMath(questionText) ||
      Object.values(options || {}).some((option) => hasMath(option)));

  const optionBlocks = {};

  Object.entries(options || {}).forEach(([key, value]) => {
    optionBlocks[key] = parseBlocks(value);
  });

  return {
    id: item.id || createId(),
    type,
    question: questionText,
    question_blocks: parseBlocks(questionText),
    options,
    option_blocks: optionBlocks,
    correct_answer:
      type === "multi_choice"
        ? Array.isArray(item.correct_answer)
          ? item.correct_answer
          : []
        : type === "fill_blank"
          ? Array.isArray(item.correct_answer)
            ? item.correct_answer
            : item.correct_answer
              ? [item.correct_answer]
              : [""]
          : item.correct_answer || null,
    images: item.images || [],
    allow_math: ocrMode.value === "math",
    editor_mode: shouldUseMath ? "math" : "normal",
  };
};

const normalizeQuestions = (items = []) => {
  return items.map((item) => normalizeQuestion(item));
};

const getQuestionText = (question) => {
  if (question.editor_mode === "math") {
    return blocksToString(question.question_blocks);
  }

  return question.question || "";
};

const setQuestionMode = (question, mode) => {
  if (!question.allow_math && mode === "math") return;

  if (mode === "math") {
    question.question_blocks = parseBlocks(question.question);

    Object.keys(question.options || {}).forEach((key) => {
      question.option_blocks[key] = parseBlocks(question.options[key]);
    });
  } else {
    question.question = blocksToString(question.question_blocks);

    Object.keys(question.option_blocks || {}).forEach((key) => {
      question.options[key] = blocksToString(question.option_blocks[key]);
    });
  }

  question.editor_mode = mode;
  markDirty();
};

const ensureDefaultOptions = (question) => {
  if (!question.options) {
    question.options = {
      A: "",
      B: "",
      C: "",
      D: "",
    };
  }

  if (!question.option_blocks) {
    question.option_blocks = {};
  }

  Object.keys(question.options).forEach((key) => {
    if (!question.option_blocks[key]) {
      question.option_blocks[key] = parseBlocks(question.options[key]);
    }
  });
};

const changeQuestionType = (question) => {
  if (question.type === "fill_blank") {
    question.options = null;
    question.option_blocks = {};

    if (!Array.isArray(question.correct_answer)) {
      question.correct_answer = question.correct_answer
        ? [question.correct_answer]
        : [""];
    }

    if (!question.correct_answer.length) {
      question.correct_answer.push("");
    }
  }

  if (question.type === "single_choice") {
    ensureDefaultOptions(question);

    if (Array.isArray(question.correct_answer)) {
      question.correct_answer = question.correct_answer[0] || null;
    }
  }

  if (question.type === "multi_choice") {
    ensureDefaultOptions(question);

    if (!Array.isArray(question.correct_answer)) {
      question.correct_answer = question.correct_answer
        ? [question.correct_answer]
        : [];
    }
  }

  markDirty();
};

const addFillAnswer = (question) => {
  if (!Array.isArray(question.correct_answer)) {
    question.correct_answer = [];
  }

  question.correct_answer.push("");
  markDirty();
};

const removeFillAnswer = (question, index) => {
  question.correct_answer.splice(index, 1);

  if (!question.correct_answer.length) {
    question.correct_answer.push("");
  }

  markDirty();
};

const addTextBlock = (blocks) => {
  blocks.push(createBlock("text", ""));
  markDirty();
};

const addMathBlock = (blocks) => {
  blocks.push(createBlock("math", "x^2"));
  markDirty();
};

const updateMathBlock = (blocks, index, event) => {
  blocks[index].value = event.target.value;
  markDirty();
};

const getTextBlockStyle = (value = "") => {
  const length = String(value || "").length;
  const width = Math.min(Math.max(length + 10, 18), 90);

  return {
    width: `${width}ch`,
    height: "56px",
    minHeight: "56px",
  };
};

const getNormalTextareaStyle = (value = "") => {
  const text = String(value || "");
  const length = text.length;
  const rows = Math.max(1, Math.ceil(length / 95));

  return {
    minHeight: `${rows * 28 + 34}px`,
  };
};

const getMathBlockStyle = (value = "") => {
  const length = String(value || "").length;
  const width = Math.min(Math.max(length + 8, 16), 70);

  return {
    width: `${width}ch`,
  };
};

const setQuestionImageInputRef = (el, index) => {
  if (el) questionImageInputs.value[index] = el;
};

const openQuestionImagePicker = (index) => {
  questionImageInputs.value[index]?.click();
};

const fileToDataUrl = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();

    reader.onload = () => resolve(reader.result);
    reader.onerror = () => reject(new Error("Không đọc được ảnh"));
    reader.readAsDataURL(file);
  });
};

const handleQuestionImage = async (event, question) => {
  const file = event.target.files?.[0];
  if (!file) return;

  if (!file.type.startsWith("image/")) {
    showToast("File đính kèm phải là ảnh.", "error");
    event.target.value = "";
    return;
  }

  if (file.size > 4 * 1024 * 1024) {
    showToast("Ảnh câu hỏi tối đa 4MB.", "error");
    event.target.value = "";
    return;
  }

  const preview = await fileToDataUrl(file);

  if (!question.images) question.images = [];

  question.images.push({
    id: createId(),
    name: file.name,
    type: file.type,
    size: file.size,
    preview,
    file,
  });

  event.target.value = "";
  markDirty();
};

const removeQuestionImage = (question, index) => {
  question.images.splice(index, 1);
  markDirty();
};

const removeBlock = (blocks, index) => {
  blocks.splice(index, 1);

  if (!blocks.length) {
    blocks.push(createBlock("text", ""));
  }

  markDirty();
};

const buildSavePayload = () => {
  return questions.value.map((q) => {
    const question =
      q.editor_mode === "math" ? blocksToString(q.question_blocks) : q.question;

    const options = {};

    if (q.type !== "fill_blank") {
      Object.keys(q.options || {}).forEach((key) => {
        options[key] =
          q.editor_mode === "math"
            ? blocksToString(q.option_blocks[key])
            : q.options[key];
      });
    }

    return {
      type: q.type,
      question,
      options: q.type === "fill_blank" ? null : options,
      correct_answer: q.correct_answer,
      points: quizInfo.value.default_points || 10,
      images: (q.images || []).map((image) => ({
        name: image.name,
        type: image.type,
        size: image.size,
        preview: image.preview,
      })),
    };
  });
};

const buildQuizPayload = () => {
  return {
    quiz: {
      title: quizInfo.value.title,
      description: quizInfo.value.description,
      subject: quizInfo.value.subject,
      grade: quizInfo.value.grade,
      duration: quizInfo.value.duration,
      default_points: quizInfo.value.default_points,
      status: quizInfo.value.status,
    },
    questions: buildSavePayload(),
  };
};

const startFakeProgress = () => {
  stopFakeProgress();

  progress.value = 10;
  loadingText.value = "Đang tải file...";

  progressTimer.value = setInterval(() => {
    if (progress.value < 55) {
      progress.value += 3;
      loadingText.value = "Đang đọc nội dung OCR...";
    } else if (progress.value < 80) {
      progress.value += 1;
      loadingText.value = "AI đang phân tích câu hỏi...";
    } else if (progress.value < 92) {
      progress.value += 0.3;
      loadingText.value = "Đang chuẩn hóa dữ liệu...";
    } else {
      loadingText.value = "Sắp hoàn tất...";
    }
  }, 800);
};

const stopFakeProgress = () => {
  if (progressTimer.value) {
    clearInterval(progressTimer.value);
    progressTimer.value = null;
  }
};

const handleFile = async (event) => {
  if (isUploading.value) return;

  const file = event.target.files?.[0];
  if (!file) return;

  if (!ALLOWED_OCR_TYPES.includes(file.type)) {
    errorMessage.value = "Định dạng file không được hỗ trợ. Chỉ chấp nhận PNG, JPG, JPEG, WEBP, BMP, TIFF, PDF.";
    event.target.value = "";
    return;
  }

  if (file.size > MAX_OCR_FILE_SIZE) {
    errorMessage.value = "File vượt quá dung lượng tối đa 20MB.";
    event.target.value = "";
    return;
  }

  fileName.value = file.name;
  isUploading.value = true;
  errorMessage.value = "";
  showReadyMessage.value = false;

  try {
    startFakeProgress();

    const result = await ocrApi.scan(file, ocrMode.value);

    stopFakeProgress();

    progress.value = 100;
    loadingText.value = "Hoàn tất OCR";

    const responseData = result.data || result;
    const quizData = responseData.data || responseData.quizOrc || responseData;

    questions.value = normalizeQuestions(quizData.questions || []);

    sessionStorage.setItem(
      "quizflex_questions",
      JSON.stringify(buildSavePayload()),
    );

    isDirty.value = false;
    showReadyMessage.value = true;

    setTimeout(() => {
      currentView.value = "edit";
      showReadyMessage.value = false;
    }, 2000);
  } catch (error) {
    stopFakeProgress();

    const responseError =
      error.response?.data?.error ||
      error.response?.data?.message ||
      error.message;
    errorMessage.value = `OCR thất bại: ${responseError}`;
    progress.value = 0;

    if (fileInput.value) {
      fileInput.value.value = "";
    }
  } finally {
    isUploading.value = false;
  }
};

const confirmLoseChanges = () => {
  if (!isDirty.value) return true;

  return window.confirm(
    "Bạn đang có thay đổi chưa lưu. Tiếp tục sẽ mất dữ liệu.",
  );
};

const goBackUpload = () => {
  if (!confirmLoseChanges()) return;

  currentView.value = "upload";
};

const resetFile = () => {
  if (isUploading.value) return;
  if (!confirmLoseChanges()) return;

  stopFakeProgress();

  fileName.value = "";
  progress.value = 0;
  errorMessage.value = "";
  isUploading.value = false;
  loadingText.value = "Đang tải file...";
  questions.value = [];
  showReadyMessage.value = false;
  currentView.value = "upload";
  isDirty.value = false;
  quizInfo.value = {
    title: "",
    description: "",
    subject: "",
    grade: "",
    duration: 45,
    default_points: 10,
    status: "draft",
  };

  sessionStorage.removeItem("quizflex_questions");
  sessionStorage.removeItem("quizflex_quiz_payload");

  if (fileInput.value) {
    fileInput.value.value = "";
  }
};

const addQuestion = (type = "single_choice") => {
  questions.value.push(
    normalizeQuestion({
      type,
      question: "",
      options:
        type === "fill_blank"
          ? null
          : {
              A: "",
              B: "",
              C: "",
              D: "",
            },
      correct_answer:
        type === "multi_choice" ? [] : type === "fill_blank" ? [""] : null,
      images: [],
    }),
  );

  markDirty();
};

const removeQuestion = (index) => {
  questions.value.splice(index, 1);
  markDirty();
};

const validateBeforeSave = () => {
  const title = quizInfo.value.title.trim();
  if (!title) return "Vui lòng nhập tên bộ đề trước khi lưu.";
  if (title.length > 255) return "Tên bộ đề tối đa 255 ký tự.";

  const duration = Number(quizInfo.value.duration);
  if (!Number.isFinite(duration) || duration < 1 || duration > 1440) {
    return "Thời gian làm bài phải từ 1 đến 1440 phút.";
  }

  const points = Number(quizInfo.value.default_points);
  if (!Number.isFinite(points) || points < 1 || points > 1000) {
    return "Điểm mặc định mỗi câu phải từ 1 đến 1000.";
  }

  if (questions.value.length === 0) return "Bộ đề cần ít nhất 1 câu hỏi.";

  for (const [index, q] of questions.value.entries()) {
    const questionText = getQuestionText(q);
    if (!questionText.trim()) return `Câu ${index + 1} chưa có nội dung.`;

    if (q.type !== "fill_blank") {
      const options = {};
      Object.keys(q.options || {}).forEach((key) => {
        options[key] =
          q.editor_mode === "math"
            ? blocksToString(q.option_blocks[key])
            : q.options[key];
      });

      if (Object.values(options).some((value) => !String(value).trim())) {
        return `Câu ${index + 1} còn đáp án trống.`;
      }

      if (q.type === "single_choice" && !q.correct_answer) {
        return `Câu ${index + 1} chưa chọn đáp án đúng.`;
      }

      if (q.type === "multi_choice" && (!Array.isArray(q.correct_answer) || !q.correct_answer.length)) {
        return `Câu ${index + 1} chưa chọn đáp án đúng.`;
      }
    } else {
      if (!Array.isArray(q.correct_answer) || !q.correct_answer.some((answer) => String(answer).trim())) {
        return `Câu ${index + 1} chưa có đáp án điền đúng.`;
      }
    }
  }

  return "";
};

const saveQuestions = async () => {
  const validationError = validateBeforeSave();
  if (validationError) {
    showToast(validationError, "error");
    return;
  }

  const payload = buildQuizPayload();

  try {
    saving.value = true;

    const { data } = await importOcrQuiz(payload);

    sessionStorage.removeItem("quizflex_quiz_payload");
    isDirty.value = false;

    showToast("Lưu bộ đề thành công", "success");
    router.push(questionBase.value);

    console.log("Quiz đã lưu:", data);
  } catch (error) {
    console.error(error);

    showToast(error?.response?.data?.message || "Có lỗi xảy ra khi lưu bộ đề.", "error");
  } finally {
    saving.value = false;
  }
};

const clearSelectedQuestions = () => {
  selectedQuestionIds.value = [];
};

const generateAiSuggestions = async (key) => {
  const needSelected = ["similar", "better_options"];

  if (
    aiOptions.value.scope === "selected" &&
    needSelected.includes(key) &&
    !selectedQuestions.value.length
  ) {
    showToast("Vui lòng chọn ít nhất 1 câu để AI có dữ liệu gợi ý.", "error");
    return;
  }

  if (key !== "analyze_quiz") {
    const count = Number(aiOptions.value.count);
    if (!Number.isFinite(count) || count < 1 || count > 20) {
      showToast("Số câu AI tạo phải từ 1 đến 20.", "error");
      return;
    }
  }

  try {
    aiLoading.value = true;
    aiSuggestions.value = [];

    const { data } = await aiQuizApi.suggest(buildAiPayload(key));

    aiSuggestions.value = data.suggestions || [];
  } catch (error) {
    showToast(error?.response?.data?.message || "AI gợi ý thất bại.", "error");
  } finally {
    aiLoading.value = false;
  }
};

const applyAiSuggestion = (item) => {
  if (item.type === "fix" || item.type === "analysis") {
    showToast("Mock: gợi ý này chỉ để xem, chưa áp dụng trực tiếp.", "error");
    return;
  }

  questions.value.push(
    normalizeQuestion({
      type: item.type || "single_choice",
      question: item.question,
      options: item.options,
      correct_answer: item.correct_answer,
      images: [],
    }),
  );

  markDirty();
  showToast("Đã thêm câu hỏi vào bộ đề thành công!", "success");
};

const buildAiPayload = (action) => {
  return {
    action,
    quiz: buildQuizPayload(),
    selected_question_ids: selectedQuestionIds.value,
    selected_questions:
      aiOptions.value.scope === "selected"
        ? selectedQuestions.value.map((q) => normalizeAiQuestion(q))
        : [],
    options: {
      count: aiOptions.value.count || 5,
      difficulty: aiOptions.value.difficulty,
      scope: aiOptions.value.scope,
      keep_grade_scope: true,
    },
  };
};

const normalizeAiQuestion = (q) => {
  const question =
    q.editor_mode === "math" ? blocksToString(q.question_blocks) : q.question;

  const options = {};

  if (q.type !== "fill_blank") {
    Object.keys(q.options || {}).forEach((key) => {
      options[key] =
        q.editor_mode === "math"
          ? blocksToString(q.option_blocks[key])
          : q.options[key];
    });
  }

  return {
    id: q.id,
    type: q.type,
    question,
    options: q.type === "fill_blank" ? null : options,
    correct_answer: q.correct_answer,
  };
};
</script>

<style scoped>
.math-block-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
}

.editor-block {
  position: relative;
  display: inline-flex;
  flex: 0 0 auto;
  align-items: stretch;
  min-height: 56px;
  max-width: 100%;
}

.drag-handle {
  display: grid;
  width: 34px;
  min-width: 34px;
  place-items: center;
  border: 1px solid var(--border);
  border-radius: 0.8rem 0 0 0.8rem;
  background: var(--surface-soft);
  color: var(--muted);
  cursor: grab;
  font-weight: 900;
}

.drag-ghost {
  opacity: 0.45;
}

.editor-text-input,
.editor-math-field {
  min-width: 120px;
  max-width: min(100%, 900px);
  border-radius: 0 0.9rem 0.9rem 0;
  border: 1px solid var(--border);
  border-left: 0;
  padding: 0.85rem 2.7rem 0.85rem 0.95rem;
  font-size: 0.95rem;
  font-weight: 700;
  outline: none;
  box-sizing: border-box;
}

.editor-text-input {
  height: 56px;
  min-height: 56px;
  resize: none;
  overflow-x: auto;
  overflow-y: hidden;
  white-space: nowrap;
  word-break: normal;
  line-height: 1.6;
  color: var(--text);
  background: var(--surface-soft);
}

.editor-math-field {
  display: inline-flex;
  align-items: center;
  height: 56px;
  min-width: 150px;
  background: #fff;
  color: #111827;
  font-size: 1.05rem;
}

.editor-block-remove {
  position: absolute;
  right: 0.55rem;
  top: 50%;
  z-index: 10;
  transform: translateY(-50%);
  display: grid;
  height: 24px;
  width: 24px;
  place-items: center;
  border-radius: 999px;
  border: none;
  background: rgba(244, 63, 94, 0.16);
  font-size: 0.9rem;
  font-weight: 900;
  line-height: 1;
  color: rgb(253, 164, 175);
  transition: 0.2s ease;
  cursor: pointer;
}

.editor-block-remove:hover {
  background: rgba(244, 63, 94, 0.28);
}

/* Ẩn menu ⋮ */
math-field::part(menu-toggle) {
  display: none !important;
}
</style>
