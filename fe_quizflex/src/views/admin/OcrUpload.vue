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

  <section v-else class="grid gap-6 pb-28">
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
              :class="getValidationClass('quiz.title')"
              data-validation-key="quiz.title"
              placeholder="Ví dụ: Ôn tập Toán 12 chương 1"
              maxlength="255"
              @input="handleValidatedInput('quiz.title')"
            />
            <p
              v-if="validationErrors['quiz.title']"
              class="mt-2 text-xs font-bold text-rose-400"
            >
              {{ validationErrors["quiz.title"] }}
            </p>
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
                :class="getValidationClass('quiz.duration')"
                data-validation-key="quiz.duration"
                @input="handleValidatedInput('quiz.duration')"
              />

              <span class="text-sm font-bold text-[var(--muted)]">phút</span>
            </div>
            <p
              v-if="validationErrors['quiz.duration']"
              class="mt-2 text-xs font-bold text-rose-400"
            >
              {{ validationErrors["quiz.duration"] }}
            </p>
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
              :class="getValidationClass('quiz.default_points')"
              data-validation-key="quiz.default_points"
              @input="handleValidatedInput('quiz.default_points')"
            />
            <p
              v-if="validationErrors['quiz.default_points']"
              class="mt-2 text-xs font-bold text-rose-400"
            >
              {{ validationErrors["quiz.default_points"] }}
            </p>
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

      <!-- AI Assistant -->
      <div
        class="mt-6 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5"
      >
        <!-- Header -->
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <p
              class="text-xs font-black uppercase tracking-[0.18em] text-[var(--primary)]"
            >
              AI Assistant
            </p>

            <h2 class="mt-1 text-xl font-black text-[var(--text)]">
              Tạo thêm câu hỏi bằng AI
            </h2>

            <p class="mt-1 text-xs leading-6 text-[var(--muted)]">
              Tạo câu mới dựa trên câu đã chọn hoặc theo mức độ mong muốn.
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <span
              class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--muted)]"
            >
              {{
                selectedQuestionIds.length
                  ? `Đã chọn ${selectedQuestionIds.length} câu`
                  : "Chưa chọn câu"
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

            <button
              type="button"
              class="rounded-full border border-[var(--border)] bg-[var(--surface)] px-3 py-1 text-xs font-black text-[var(--text)]"
              :aria-expanded="aiAssistantOpen"
              @click="aiAssistantOpen = !aiAssistantOpen"
            >
              {{ aiAssistantOpen ? "Thu gọn" : "Mở gợi ý" }}
            </button>
          </div>
        </div>

        <!-- Content -->
        <div
          v-show="aiAssistantOpen"
          ref="aiAssistantContent"
          class="ai-assistant-content"
        >
          <!-- Selected questions -->
          <div
            v-if="selectedQuestions.length"
            class="mt-4 flex flex-wrap gap-2"
          >
            <span
              v-for="question in selectedQuestions"
              :key="question.id"
              class="rounded-full border border-[var(--border)] bg-[var(--surface)] px-3 py-1 text-xs font-black text-[var(--muted)]"
            >
              Câu {{ getQuestionNumber(question.id) }}
            </span>
          </div>

          <!-- Action -->
          <div class="mt-5">
            <label
              class="text-xs font-black uppercase tracking-wide text-[var(--muted)]"
            >
              Bạn muốn AI tạo theo cách nào?
            </label>

            <div class="mt-3 grid gap-3 md:grid-cols-2">
              <!-- Similar -->
              <button
                type="button"
                class="rounded-2xl border p-4 text-left transition"
                :class="
                  aiOptions.action === 'similar'
                    ? 'border-[var(--primary)] bg-[var(--chip-active)]'
                    : 'border-[var(--border)] bg-[var(--surface)] hover:border-[var(--border-strong)]'
                "
                @click="aiOptions.action = 'similar'"
              >
                <div class="flex items-start gap-3">
                  <span class="text-xl">✨</span>

                  <div>
                    <p class="text-sm font-black text-[var(--text)]">
                      Tạo câu tương tự
                    </p>

                    <p class="mt-1 text-xs leading-5 text-[var(--muted)]">
                      Dựa trên nội dung và dạng của những câu bạn đã chọn.
                    </p>
                  </div>
                </div>
              </button>

              <!-- Difficulty -->
              <button
                type="button"
                class="rounded-2xl border p-4 text-left transition"
                :class="
                  aiOptions.action === 'generate_by_difficulty'
                    ? 'border-[var(--primary)] bg-[var(--chip-active)]'
                    : 'border-[var(--border)] bg-[var(--surface)] hover:border-[var(--border-strong)]'
                "
                @click="aiOptions.action = 'generate_by_difficulty'"
              >
                <div class="flex items-start gap-3">
                  <span class="text-xl">🎯</span>

                  <div>
                    <p class="text-sm font-black text-[var(--text)]">
                      Tạo theo độ khó
                    </p>

                    <p class="mt-1 text-xs leading-5 text-[var(--muted)]">
                      Tạo thêm câu hỏi phù hợp với nội dung bộ đề và mức độ bạn
                      chọn.
                    </p>
                  </div>
                </div>
              </button>
            </div>
          </div>

          <!-- Similar warning -->
          <div
            v-if="aiOptions.action === 'similar' && !selectedQuestions.length"
            class="mt-4 rounded-2xl border border-amber-500/20 bg-amber-500/5 p-4"
          >
            <p class="text-xs font-bold leading-6 text-amber-300">
              💡 Hãy chọn ít nhất một câu trong bộ đề để AI có dữ liệu tạo câu
              tương tự.
            </p>
          </div>

          <!-- Options -->
          <div
            class="mt-5 grid gap-4"
            :class="
              aiOptions.action === 'generate_by_difficulty'
                ? 'md:grid-cols-[1fr_1fr_auto]'
                : 'md:grid-cols-[1fr_auto]'
            "
          >
            <!-- Difficulty: chỉ hiện khi cần -->
            <div v-if="aiOptions.action === 'generate_by_difficulty'">
              <label class="text-xs font-black uppercase text-[var(--muted)]">
                Độ khó
              </label>

              <select
                v-model="aiOptions.difficulty"
                class="mt-2 w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-bold text-[var(--text)] outline-none"
              >
                <option value="easy">Dễ</option>
                <option value="medium">Trung bình</option>
                <option value="hard">Khó</option>
                <option value="advanced">Nâng cao</option>
              </select>
            </div>

            <!-- Count -->
            <div>
              <label class="text-xs font-black uppercase text-[var(--muted)]">
                Số câu muốn tạo
              </label>

              <input
                v-model.number="aiOptions.count"
                type="number"
                min="1"
                max="20"
                class="mt-2 w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-bold text-[var(--text)] outline-none"
              />
            </div>

            <!-- Generate -->
            <div class="flex items-end">
              <button
                type="button"
                class="btn-primary w-full whitespace-nowrap disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="
                  aiLoading ||
                  (aiOptions.action === 'similar' && !selectedQuestions.length)
                "
                @click="generateAiSuggestions(aiOptions.action)"
              >
                {{ aiLoading ? "AI đang tạo..." : "✨ Tạo câu hỏi" }}
              </button>
            </div>
          </div>

          <!-- Loading -->
          <div
            v-if="aiLoading"
            class="mt-5 rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4"
          >
            <p class="text-sm font-black text-[var(--text)]">
              ✨ AI đang tạo câu hỏi...
            </p>

            <p class="mt-1 text-xs text-[var(--muted)]">
              Quá trình này có thể mất vài giây.
            </p>
          </div>

          <!-- Result -->
          <div
            v-if="!aiLoading && aiSuggestions.length"
            class="mt-5 grid gap-4"
          >
            <div
              v-for="item in aiSuggestions"
              :key="item.id"
              class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4"
            >
              <!-- Header -->
              <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm font-black text-[var(--primary)]">
                  {{ item.type_label || "Câu hỏi AI" }}
                </p>

                <span
                  v-if="
                    aiOptions.action === 'generate_by_difficulty' &&
                    item.difficulty_label
                  "
                  class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--muted)]"
                >
                  {{ item.difficulty_label }}
                </span>
              </div>

              <!-- Question -->
              <p
                data-ai-latex
                class="mt-3 text-sm font-bold leading-7 text-[var(--text)]"
              >
                {{ item.question }}
              </p>

              <!-- Options -->
              <div v-if="item.options" class="mt-3 grid gap-2">
                <div
                  v-for="(value, key) in item.options"
                  :key="key"
                  class="rounded-xl bg-[var(--surface-soft)] px-3 py-2 text-xs font-bold text-[var(--muted)]"
                >
                  <span class="mr-1 font-black text-[var(--primary)]">
                    {{ key }}.
                  </span>

                  <span data-ai-latex>
                    {{ value }}
                  </span>
                </div>
              </div>

              <!-- Solution summary -->
              <div
                v-if="item.solution_summary"
                class="mt-3 rounded-xl bg-[var(--surface-soft)] p-3"
              >
                <p class="text-xs font-black uppercase text-[var(--muted)]">
                  💡 Hướng giải
                </p>

                <p
                  data-ai-latex
                  class="mt-2 text-xs leading-6 text-[var(--muted)]"
                >
                  {{ item.solution_summary }}
                </p>
              </div>

              <!-- Knowledge -->
              <div
                v-if="item.knowledge_points?.length"
                class="mt-3 flex flex-wrap gap-2"
              >
                <span
                  v-for="point in item.knowledge_points"
                  :key="point"
                  class="rounded-full bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--muted)]"
                >
                  {{ point }}
                </span>
              </div>

              <!-- Add -->
              <button
                type="button"
                class="mt-4 rounded-full bg-[var(--primary)] px-4 py-2 text-xs font-black text-white"
                @click="applyAiSuggestion(item)"
              >
                + Thêm vào đề
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-8 grid gap-5">
        <div
          v-for="(q, index) in questions"
          :key="q.id"
          class="scroll-mb-28 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5"
          :class="[
            getQuestionValidationClass(q),
            {
              'ai-review-highlight': aiReviewHighlightId === q.id,
              'ai-suggestion-highlight': aiSuggestionHighlightId === q.id,
            },
          ]"
          :data-question-id="q.id"
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
            :class="getValidationClass(questionValidationKey(q))"
            :data-validation-key="questionValidationKey(q)"
            :style="getNormalTextareaStyle(q.question)"
            @input="handleValidatedInput(questionValidationKey(q))"
          ></textarea>

          <div
            v-else
            class="mt-3 rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4"
            :class="getValidationClass(questionValidationKey(q))"
            :data-validation-key="questionValidationKey(q)"
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
                    @input="handleValidatedInput(questionValidationKey(q))"
                  ></textarea>

                  <math-field
                    v-else
                    :value="block.value"
                    :style="getMathBlockStyle(block.value)"
                    virtual-keyboard-mode="manual"
                    class="editor-math-field"
                    @input="
                      updateMathBlock(
                        q.question_blocks,
                        blockIndex,
                        $event,
                        questionValidationKey(q),
                      )
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

          <p
            v-if="validationErrors[questionValidationKey(q)]"
            class="mt-2 text-xs font-bold text-rose-400"
          >
            {{ validationErrors[questionValidationKey(q)] }}
          </p>

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
                :class="getValidationClass(optionValidationKey(q, optionKey))"
                :data-validation-key="optionValidationKey(q, optionKey)"
                :style="getNormalTextareaStyle(q.options[optionKey])"
                @input="handleValidatedInput(optionValidationKey(q, optionKey))"
              ></textarea>

              <div
                v-else
                class="mt-2 rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-3"
                :class="getValidationClass(optionValidationKey(q, optionKey))"
                :data-validation-key="optionValidationKey(q, optionKey)"
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
                        @input="
                          handleValidatedInput(
                            optionValidationKey(q, optionKey),
                          )
                        "
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
                            optionValidationKey(q, optionKey),
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

              <p
                v-if="validationErrors[optionValidationKey(q, optionKey)]"
                class="mt-2 text-xs font-bold text-rose-400"
              >
                {{ validationErrors[optionValidationKey(q, optionKey)] }}
              </p>
            </div>
          </div>

          <div
            v-if="q.type === 'single_choice'"
            class="mt-4 rounded-2xl"
            :class="getAnswerValidationClass(q)"
            :data-validation-key="answerValidationKey(q)"
          >
            <label class="text-xs font-black uppercase text-[var(--muted)]">
              Đáp án đúng
            </label>

            <select
              v-model="q.correct_answer"
              class="mt-2 w-full rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4 text-sm font-bold text-[var(--text)] outline-none"
              @change="handleValidatedInput(answerValidationKey(q))"
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
            <p
              v-if="validationErrors[answerValidationKey(q)]"
              class="mt-2 text-xs font-bold text-rose-400"
            >
              {{ validationErrors[answerValidationKey(q)] }}
            </p>
          </div>

          <div
            v-else-if="q.type === 'multi_choice'"
            class="mt-4 rounded-2xl p-1"
            :class="getAnswerValidationClass(q)"
            :data-validation-key="answerValidationKey(q)"
          >
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
                  @change="handleValidatedInput(answerValidationKey(q))"
                />

                {{ optionKey }}
              </label>
            </div>
            <p
              v-if="validationErrors[answerValidationKey(q)]"
              class="mt-2 text-xs font-bold text-rose-400"
            >
              {{ validationErrors[answerValidationKey(q)] }}
            </p>
          </div>

          <div
            v-else
            class="mt-4 rounded-2xl p-1"
            :class="getAnswerValidationClass(q)"
            :data-validation-key="answerValidationKey(q)"
          >
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
                  @input="handleValidatedInput(answerValidationKey(q))"
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

            <p
              v-if="validationErrors[answerValidationKey(q)]"
              class="mt-2 text-xs font-bold text-rose-400"
            >
              {{ validationErrors[answerValidationKey(q)] }}
            </p>

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
    </article>
  </section>

  <Teleport to="body">
    <div v-if="currentView === 'edit'" class="editor-save-bar">
      <div class="editor-save-actions">
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
        <button
          class="btn-ghost"
          type="button"
          :disabled="aiReviewLoading || saving || !questions.length"
          @click="reviewQuizWithAi"
        >
          <template v-if="aiReviewLoading"> AI đang phân tích... </template>

          <template v-else-if="!aiReviewResult"> ✨ AI Review </template>

          <template v-else-if="aiReviewIsStale"> ⚠️ Xem AI Review cũ </template>

          <template v-else> ✓ Xem AI Review </template>
        </button>
        <button
          class="btn-primary"
          type="button"
          :disabled="saving"
          @click="saveQuestions"
        >
          {{ saving ? "Đang lưu..." : "Lưu câu hỏi" }}
        </button>
      </div>
    </div>
  </Teleport>
  <Teleport to="body">
    <Transition name="ai-review">
      <div v-if="aiReviewOpen" class="fixed inset-0 z-[60]">
        <!-- overlay -->
        <div
          class="absolute inset-0 bg-black/40 backdrop-blur-[2px]"
          @click="aiReviewOpen = false"
        ></div>

        <!-- drawer -->
        <aside
          class="absolute bottom-0 right-0 top-0 w-full max-w-[480px] overflow-y-auto border-l border-[var(--border)] bg-[var(--surface)] p-6 shadow-2xl"
        >
          <div class="flex items-start justify-between gap-4">
            <div>
              <p
                class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]"
              >
                AI Review
              </p>

              <h2 class="mt-2 text-2xl font-black text-[var(--text)]">
                Phân tích bộ đề
              </h2>

              <p class="mt-2 text-sm text-[var(--muted)]">
                AI chỉ đưa ra nhận xét và gợi ý, không tự thay đổi câu hỏi.
              </p>
            </div>

            <button
              type="button"
              class="grid h-10 w-10 place-items-center rounded-full border border-[var(--border)] bg-[var(--surface-soft)] font-black text-[var(--text)]"
              @click="aiReviewOpen = false"
            >
              ×
            </button>
          </div>
          <div
            v-if="aiReviewResult && aiReviewIsStale && !aiReviewLoading"
            class="mt-6 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-4"
          >
            <div class="flex items-start gap-3">
              <span class="text-xl"> ⚠️ </span>

              <div class="flex-1">
                <p class="text-sm font-black text-amber-300">
                  Quiz đã thay đổi
                </p>

                <p class="mt-1 text-xs leading-6 text-[var(--muted)]">
                  Kết quả bên dưới là kết quả phân tích trước khi Quiz được
                  chỉnh sửa.
                </p>

                <p class="mt-2 text-xs font-bold text-[var(--muted)]">
                  Việc xem kết quả cũ không sử dụng thêm AI.
                </p>

                <button
                  type="button"
                  class="mt-4 rounded-full bg-[var(--primary)] px-4 py-2 text-xs font-black text-white disabled:opacity-50"
                  :disabled="aiReviewLoading"
                  @click="refreshAiReview"
                >
                  ✨ Phân tích lại bằng AI
                </button>
              </div>
            </div>
          </div>
          <!-- Loading -->
          <div
            v-if="aiReviewLoading"
            class="mt-8 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5"
          >
            <p class="font-black text-[var(--text)]">
              ✨ AI đang đọc toàn bộ Quiz...
            </p>

            <p class="mt-2 text-sm text-[var(--muted)]">
              Đang kiểm tra nội dung, phân bố và các câu cần xem lại.
            </p>
          </div>

          <template v-else-if="aiReviewResult">
            <!-- Tổng quan -->
            <section class="mt-6">
              <h3 class="text-sm font-black text-[var(--text)]">Tổng quan</h3>

              <div
                class="mt-3 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4"
              >
                <p class="text-sm leading-7 text-[var(--muted)]">
                  {{ aiReviewResult.summary }}
                </p>
              </div>
            </section>

            <!-- Topics -->
            <section v-if="aiReviewResult.topics?.length" class="mt-6">
              <h3 class="text-sm font-black text-[var(--text)]">
                Phân bố nội dung
              </h3>

              <div class="mt-3 grid gap-2">
                <div
                  v-for="topic in aiReviewResult.topics"
                  :key="topic.name"
                  class="flex items-center justify-between rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-3"
                >
                  <span class="text-sm font-bold text-[var(--text)]">
                    {{ topic.name }}
                  </span>

                  <span class="text-xs font-black text-[var(--primary)]">
                    {{ topic.count }} câu
                    <template v-if="topic.percentage">
                      · {{ topic.percentage }}%
                    </template>
                  </span>
                </div>
              </div>
            </section>

            <!-- Issues -->
            <section v-if="aiReviewResult.issues?.length" class="mt-6">
              <div class="flex items-center justify-between">
                <h3 class="text-sm font-black text-[var(--text)]">
                  Điểm nên xem lại
                </h3>

                <span
                  class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-black text-amber-400"
                >
                  {{ aiReviewResult.issues.length }}
                </span>
              </div>

              <div class="mt-3 grid gap-3">
                <article
                  v-for="(issue, index) in aiReviewResult.issues"
                  :key="`${issue.question_id}-${index}`"
                  class="rounded-2xl border border-amber-500/20 bg-amber-500/5 p-4"
                >
                  <div class="flex items-start gap-3">
                    <span class="text-lg">⚠️</span>

                    <div class="min-w-0 flex-1">
                      <p
                        v-if="issue.question_id"
                        class="text-xs font-black uppercase text-amber-400"
                      >
                        Câu {{ getQuestionNumber(issue.question_id) }}
                      </p>

                      <p
                        class="mt-1 text-sm font-semibold leading-6 text-[var(--text)]"
                      >
                        {{ issue.message }}
                      </p>

                      <button
                        v-if="issue.question_id"
                        type="button"
                        class="mt-3 text-xs font-black text-[var(--primary)]"
                        @click="
                          goToReviewQuestion(issue.question_id);
                          aiReviewOpen = false;
                        "
                      >
                        Đi tới câu →
                      </button>
                    </div>
                  </div>
                </article>
              </div>
            </section>

            <!-- Suggestions -->
            <section v-if="aiReviewResult.suggestions?.length" class="mt-6">
              <h3 class="text-sm font-black text-[var(--text)]">
                Gợi ý cải thiện
              </h3>

              <div class="mt-3 grid gap-2">
                <div
                  v-for="(suggestion, index) in aiReviewResult.suggestions"
                  :key="index"
                  class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-4"
                >
                  <p class="text-sm leading-6 text-[var(--muted)]">
                    💡 {{ suggestion }}
                  </p>
                </div>
              </div>
            </section>
          </template>
        </aside>
      </div>
    </Transition>
  </Teleport>
  <!-- Custom Toast Message -->
  <!-- <div
    v-if="toast.isOpen"
    class="fixed bottom-24 right-6 z-50 flex items-center gap-3 rounded-2xl border px-4 py-3 shadow-lg backdrop-blur-xl transition-all duration-300"
    :class="
      toast.type === 'success'
        ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400 shadow-[0_12px_40px_rgba(16,185,129,0.1)]'
        : 'border-rose-500/30 bg-rose-500/10 text-rose-400 shadow-[0_12px_40px_rgba(244,63,94,0.1)]'
    "
  >
    <span class="text-base">{{ toast.type === "success" ? "✅" : "❌" }}</span>
    <span class="text-sm font-bold">{{ toast.message }}</span>
  </div> -->
</template>

<script setup>
import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  ref,
  inject,
} from "vue";
import { useRoute, useRouter } from "vue-router";
import draggable from "vuedraggable";
import { renderMathInElement } from "mathlive";
import "mathlive/static.css";
import { importOcrQuiz, ocrApi, aiQuizApi } from "@/services/api";

// ==================== 1. KHỞI TẠO VÀ CẤU HÌNH ====================

const showConfirm = inject("showConfirm");
const showToast = inject("showToast");

const route = useRoute();
const router = useRouter();

const questionBase = computed(() =>
  route.path.startsWith("/dashboard")
    ? "/dashboard/questions"
    : "/admin/questions",
);

// ==================== 2. TRẠNG THÁI GIAO DIỆN ====================

const fileInput = ref(null);
const fileName = ref("");
const progress = ref(0);
const isUploading = ref(false);
const errorMessage = ref("");
const loadingText = ref("Đang tải file...");
const progressTimer = ref(null);
const ALLOWED_OCR_TYPES = [
  "image/png",
  "image/jpeg",
  "image/jpg",
  "image/webp",
  "image/bmp",
  "image/tiff",
  "application/pdf",
];
const MAX_OCR_FILE_SIZE = 20 * 1024 * 1024;
const AI_LATEX_RENDER_OPTIONS = {
  TeX: {
    processEnvironments: true,
    delimiters: {
      inline: [
        ["\\(", "\\)"],
        ["$", "$"],
      ],
      display: [
        ["$$", "$$"],
        ["\\[", "\\]"],
      ],
    },
  },
};

const currentView = ref("upload");
const ocrMode = ref("math");
const questions = ref([]);
const questionImageInputs = ref([]);
const showReadyMessage = ref(false);
const saving = ref(false);
const aiAssistantOpen = ref(true);
const aiAssistantContent = ref(null);
const aiReviewOpen = ref(false);
const aiReviewLoading = ref(false);
const aiReviewResult = ref(null);
const aiReviewHighlightId = ref(null);
const aiSuggestionHighlightId = ref(null);
const lastAiReviewQuestions = ref({});
const isDirty = ref(false);
const selectedQuestionIds = ref([]);
const aiSuggestions = ref([]);
const aiLoading = ref(false);
const validationErrors = ref({});

const aiOptions = ref({
  action: "similar",
  difficulty: "medium",
  count: 3,
  scope: "selected",
});

const getQuestionNumber = (id) => {
  const index = questions.value.findIndex((q) => String(q.id) === String(id));

  return index >= 0 ? index + 1 : null;
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

// ==================== 3. VALIDATE VÀ THEO DÕI THAY ĐỔI ====================

// Đánh dấu dữ liệu đã thay đổi nhưng chưa lưu.
const markDirty = () => {
  isDirty.value = true;
};

const questionValidationKey = (question) => `question:${question.id}:content`;
const optionValidationKey = (question, optionKey) =>
  `question:${question.id}:option:${optionKey}`;
const answerValidationKey = (question) =>
  `question:${question.id}:correct_answer`;

const getValidationClass = (key) =>
  validationErrors.value[key] ? "validation-error" : "";

const getAnswerValidationClass = (question) =>
  getValidationClass(answerValidationKey(question));

const getQuestionValidationClass = (question) => {
  const prefix = `question:${question.id}:`;
  return Object.keys(validationErrors.value).some((key) =>
    key.startsWith(prefix),
  )
    ? "question-validation-error"
    : "";
};

const clearValidationError = (key) => {
  if (!validationErrors.value[key]) return;

  const nextErrors = { ...validationErrors.value };
  delete nextErrors[key];
  validationErrors.value = nextErrors;
};

const handleValidatedInput = (key) => {
  clearValidationError(key);
  markDirty();
};

// Cuộn đến trường đầu tiên đang lỗi.
const scrollToValidationError = async (key) => {
  await nextTick();

  const target = Array.from(
    document.querySelectorAll("[data-validation-key]"),
  ).find((element) => element.dataset.validationKey === key);

  if (!target) return;

  target.scrollIntoView({ behavior: "smooth", block: "center" });

  window.setTimeout(() => {
    const focusTarget = target.matches("input, textarea, select, math-field")
      ? target
      : target.querySelector("input, textarea, select, math-field");

    focusTarget?.focus({ preventScroll: true });
  }, 450);
};

// Hiển thị danh sách lỗi validate trên giao diện.
const showValidationErrors = async (errors) => {
  validationErrors.value = errors;
  const firstKey = Object.keys(errors)[0];

  if (firstKey) {
    showToast(errors[firstKey], "error");
    await scrollToValidationError(firstKey);
  }

  return Boolean(firstKey);
};

// Kiểm tra dữ liệu Quiz trước khi gửi lên server.
const validateQuizPayload = async (payload) => {
  const errors = {};

  if (!payload.quiz.title.trim()) {
    errors["quiz.title"] = "Vui lòng nhập tên bộ đề.";
  }

  payload.questions.forEach((question, index) => {
    const sourceQuestion = questions.value[index];
    if (!sourceQuestion) return;

    if (!String(question.question || "").trim()) {
      errors[questionValidationKey(sourceQuestion)] =
        `Vui lòng nhập nội dung câu ${index + 1}.`;
    }

    if (question.type !== "fill_blank") {
      Object.entries(question.options || {}).forEach(([optionKey, value]) => {
        if (!String(value || "").trim()) {
          errors[optionValidationKey(sourceQuestion, optionKey)] =
            `Vui lòng nhập đáp án ${optionKey} của câu ${index + 1}.`;
        }
      });
    }

    const hasCorrectAnswer =
      question.type === "single_choice"
        ? Boolean(question.correct_answer)
        : Array.isArray(question.correct_answer) &&
          question.correct_answer.some((answer) => String(answer || "").trim());

    if (!hasCorrectAnswer) {
      errors[answerValidationKey(sourceQuestion)] =
        `Vui lòng chọn hoặc nhập đáp án đúng cho câu ${index + 1}.`;
    }
  });

  return !(await showValidationErrors(errors));
};

// Đổi key validate của server sang key trên giao diện.
const mapServerValidationKey = (serverKey) => {
  if (serverKey === "title" || serverKey === "quiz.title") {
    return "quiz.title";
  }

  const questionMatch = serverKey.match(/^questions\.(\d+)(?:\.(.*))?$/);
  if (!questionMatch) return null;

  const question = questions.value[Number(questionMatch[1])];
  if (!question) return null;

  const field = questionMatch[2] || "question";
  const optionMatch = field.match(/^options\.([A-Za-z0-9_-]+)$/);

  if (optionMatch) {
    return optionValidationKey(question, optionMatch[1]);
  }

  if (/^(correct_answer|answers)(\.|$)/.test(field)) {
    return answerValidationKey(question);
  }

  return questionValidationKey(question);
};

// Gắn lỗi server vào đúng câu hỏi hoặc đáp án.
const applyServerValidationErrors = async (serverErrors = {}) => {
  const errors = {};

  Object.entries(serverErrors).forEach(([serverKey, messages]) => {
    const key = mapServerValidationKey(serverKey);
    if (!key || errors[key]) return;

    errors[key] = Array.isArray(messages) ? messages[0] : String(messages);
  });

  return showValidationErrors(errors);
};

// ==================== 4. CẢNH BÁO RỜI TRANG ====================

// Cảnh báo khi reload hoặc đóng tab nếu chưa lưu.
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

// ==================== 5. XỬ LÝ NỘI DUNG TOÁN HỌC ====================

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

// Tách văn bản và công thức LaTeX thành các block sửa riêng.
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

// Ghép các block thành chuỗi để lưu dữ liệu.
const blocksToString = (blocks = []) => {
  return blocks
    .map((block) => {
      if (block.type === "math") return `$${block.value}$`;
      return block.value;
    })
    .join("")
    .trim();
};

// ==================== 6. CHUẨN HÓA CÂU HỎI ====================

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

// Chuẩn hóa một câu hỏi về đúng cấu trúc của trình sửa.
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

// ==================== 7. THAO TÁC TRONG TRÌNH SỬA ====================

// Chuyển giữa chế độ nhập thường và chế độ Math.
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

// Bổ sung đủ các đáp án mặc định khi đổi loại câu hỏi.
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

// Cập nhật dữ liệu khi thay đổi loại câu hỏi.
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

const updateMathBlock = (blocks, index, event, validationKey = null) => {
  blocks[index].value = event.target.value;
  validationKey ? handleValidatedInput(validationKey) : markDirty();
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

const removeBlock = (blocks, index) => {
  blocks.splice(index, 1);

  if (!blocks.length) {
    blocks.push(createBlock("text", ""));
  }

  markDirty();
};

// ==================== 8. ẢNH CỦA CÂU HỎI ====================

// Lưu ref của input ảnh theo vị trí câu hỏi.
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

// Kiểm tra và thêm ảnh vào câu hỏi.
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

// ==================== 9. TẠO PAYLOAD LƯU QUIZ ====================

// Tạo danh sách câu hỏi để gửi lên API.
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

// Tạo payload đầy đủ gồm thông tin Quiz và câu hỏi.
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

// ==================== 10. UPLOAD VÀ OCR ====================

// Chạy thanh tiến trình giả trong lúc chờ API OCR.
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

// Dừng thanh tiến trình OCR.
const stopFakeProgress = () => {
  if (progressTimer.value) {
    clearInterval(progressTimer.value);
    progressTimer.value = null;
  }
};

// Kiểm tra file và gọi API OCR.
const handleFile = async (event) => {
  if (isUploading.value) return;

  const file = event.target.files?.[0];
  if (!file) return;

  if (!ALLOWED_OCR_TYPES.includes(file.type)) {
    errorMessage.value =
      "Định dạng file không được hỗ trợ. Chỉ chấp nhận PNG, JPG, JPEG, WEBP, BMP, TIFF, PDF.";
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
    aiReviewResult.value = null;
    lastAiReviewQuestions.value = null;
    aiReviewOpen.value = false;

    sessionStorage.setItem(
      "quizflex_questions",
      JSON.stringify(buildSavePayload()),
    );

    // Dữ liệu OCR mới tạo vẫn chưa được lưu vào database.
    isDirty.value = true;
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

// ==================== 11. ĐIỀU HƯỚNG VÀ ĐẶT LẠI ====================

// Xin xác nhận trước khi bỏ dữ liệu chưa lưu.
const confirmAndRun = (action) => {
  if (!isDirty.value) {
    action();
    return;
  }

  if (showConfirm) {
    showConfirm(
      "Xác nhận rời đi",
      "Bạn đang có thay đổi chưa lưu. Tiếp tục sẽ mất dữ liệu.",
      action,
    );
  } else {
    if (
      window.confirm("Bạn đang có thay đổi chưa lưu. Tiếp tục sẽ mất dữ liệu.")
    ) {
      action();
    }
  }
};

// Quay lại màn hình upload OCR.
const goBackUpload = () => {
  confirmAndRun(() => {
    currentView.value = "upload";
  });
};

// Xóa file và toàn bộ dữ liệu đang chỉnh sửa.
const resetFile = () => {
  if (isUploading.value) return;
  confirmAndRun(() => {
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
    aiReviewResult.value = null;
    lastAiReviewQuestions.value = null;
    aiReviewOpen.value = false;
    aiReviewHighlightId.value = null;

    sessionStorage.removeItem("quizflex_questions");
    sessionStorage.removeItem("quizflex_quiz_payload");

    if (fileInput.value) {
      fileInput.value.value = "";
    }
  });
};

// ==================== 12. QUẢN LÝ VÀ LƯU CÂU HỎI ====================

// Thêm câu hỏi mới và cuộn xuống vị trí vừa thêm.
const addQuestion = async (type = "single_choice") => {
  const newQuestion = normalizeQuestion({
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
  });

  questions.value.push(newQuestion);

  markDirty();

  await nextTick();

  const newQuestionElement = Array.from(
    document.querySelectorAll("[data-question-id]"),
  ).find((element) => element.dataset.questionId === String(newQuestion.id));

  if (!newQuestionElement) return;

  newQuestionElement.scrollIntoView({
    behavior: "smooth",
    block: "end",
  });

  window.setTimeout(() => {
    const questionInput = Array.from(
      newQuestionElement.querySelectorAll("[data-validation-key]"),
    ).find(
      (element) =>
        element.dataset.validationKey === questionValidationKey(newQuestion),
    );

    const focusTarget = questionInput?.matches(
      "input, textarea, select, math-field",
    )
      ? questionInput
      : questionInput?.querySelector("input, textarea, select, math-field");

    focusTarget?.focus({ preventScroll: true });
  }, 450);
};

// Xóa một câu hỏi khỏi danh sách.
const removeQuestion = (index) => {
  questions.value.splice(index, 1);
  markDirty();
};

// Kiểm tra nhanh dữ liệu trước khi lưu.
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

      if (
        q.type === "multi_choice" &&
        (!Array.isArray(q.correct_answer) || !q.correct_answer.length)
      ) {
        return `Câu ${index + 1} chưa chọn đáp án đúng.`;
      }
    } else {
      if (
        !Array.isArray(q.correct_answer) ||
        !q.correct_answer.some((answer) => String(answer).trim())
      ) {
        return `Câu ${index + 1} chưa có đáp án điền đúng.`;
      }
    }
  }

  return "";
};

// Lưu bộ đề qua API và chuyển sang trang danh sách.
const saveQuestions = async () => {
  const payload = buildQuizPayload();

  const isValid = await validateQuizPayload(payload);
  if (!isValid) return;

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

    const handledValidation = await applyServerValidationErrors(
      error?.response?.data?.errors,
    );

    if (!handledValidation) {
      showToast(
        error?.response?.data?.message || "Có lỗi xảy ra khi lưu bộ đề.",
        "error",
      );
    }
  } finally {
    saving.value = false;
  }
};

// ==================== 13. GỢI Ý CÂU HỎI BẰNG AI ====================

// Chạy nhanh hành động AI được đề xuất trong phần phân tích.
const runAnalysisAction = async (actionItem) => {
  aiOptions.value.action = actionItem.action;
  aiOptions.value.difficulty = actionItem.difficulty || "medium";
  aiOptions.value.count = actionItem.count || 3;
  aiOptions.value.scope = "all";

  await generateAiSuggestions(actionItem.action);
};

const clearSelectedQuestions = () => {
  selectedQuestionIds.value = [];
};

// Render các đoạn LaTeX trong kết quả gợi ý AI.
const renderAiSuggestionLatex = async () => {
  await nextTick();

  if (!aiAssistantContent.value) return;

  aiAssistantContent.value
    .querySelectorAll("[data-ai-latex]")
    .forEach((element) =>
      renderMathInElement(element, AI_LATEX_RENDER_OPTIONS),
    );
};

// Gọi API để tạo gợi ý AI theo lựa chọn hiện tại.
const generateAiSuggestions = async (key) => {
  const needSelected = ["similar"];

  if (key === "similar" && !selectedQuestions.value.length) {
    showToast("Hãy chọn ít nhất 1 câu để AI tạo câu tương tự.", "error");

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
    await renderAiSuggestionLatex();
  } catch (error) {
    showToast(error?.response?.data?.message || "AI gợi ý thất bại.", "error");
  } finally {
    aiLoading.value = false;
  }
};

// ==================== 14. AI REVIEW BỘ ĐỀ ====================

// Chuẩn hóa dữ liệu dùng để so sánh các lần review.
const getQuestionReviewData = (q) => {
  return normalizeAiQuestion(q);
};

const getQuestionFingerprint = (q) => {
  return JSON.stringify(getQuestionReviewData(q));
};

// Lấy các câu mới hoặc đã chỉnh sửa từ lần review trước.
const getChangedReviewQuestions = () => {
  // Chưa từng review -> gửi toàn bộ
  if (!aiReviewResult.value) {
    return questions.value;
  }

  return questions.value.filter((q) => {
    const previous = lastAiReviewQuestions.value[q.id];

    // Câu mới
    if (!previous) {
      return true;
    }

    // Câu đã sửa
    return previous !== getQuestionFingerprint(q);
  });
};

// Lưu dấu vết các câu vừa được AI review.
const saveAiReviewQuestionSnapshots = (reviewedQuestions) => {
  const next = {
    ...lastAiReviewQuestions.value,
  };

  reviewedQuestions.forEach((q) => {
    next[q.id] = getQuestionFingerprint(q);
  });

  lastAiReviewQuestions.value = next;
};

// Tạo payload review toàn bộ hoặc các câu đã thay đổi.
const buildAiReviewPayload = (changedQuestions, fullReview = false) => {
  const quizPayload = buildQuizPayload();

  return {
    action: "review",

    mode: fullReview ? "full" : "incremental",

    quiz: quizPayload.quiz,

    questions: changedQuestions.map((q) => normalizeAiQuestion(q)),
  };
};

// Kiểm tra kết quả review có cũ hơn dữ liệu hiện tại không.
const aiReviewIsStale = computed(() => {
  // Chưa từng Review
  if (!aiReviewResult.value) {
    return false;
  }

  const reviewedIds = Object.keys(lastAiReviewQuestions.value);

  // Số câu hiện tại khác số câu đã Review
  // => có thêm hoặc xóa câu
  if (reviewedIds.length !== questions.value.length) {
    return true;
  }

  // Kiểm tra từng câu có bị sửa không
  return questions.value.some((q) => {
    const previous = lastAiReviewQuestions.value[q.id];

    // Không có snapshot => câu mới
    if (!previous) {
      return true;
    }

    return previous !== getQuestionFingerprint(q);
  });
});

// Gọi API review và gộp kết quả khi cần.
const requestAiReview = async (forceFullReview = false) => {
  if (!questions.value.length) {
    showToast("Chưa có câu hỏi để AI phân tích.", "error");
    return;
  }

  const changedQuestions =
    forceFullReview || !aiReviewResult.value
      ? questions.value
      : getChangedReviewQuestions();

  // Không có câu nào thay đổi
  if (!changedQuestions.length) {
    aiReviewOpen.value = true;

    showToast("Không có câu hỏi nào thay đổi.", "success");

    return;
  }

  const isFullReview = forceFullReview || !aiReviewResult.value;

  const payload = buildAiReviewPayload(changedQuestions, isFullReview);

  try {
    aiReviewOpen.value = true;
    aiReviewLoading.value = true;

    const { data } = await aiQuizApi.review(payload);
    if (isFullReview) {
      aiReviewResult.value = data;
    } else {
      mergeAiReviewResult(data, changedQuestions);
    }

    saveAiReviewQuestionSnapshots(changedQuestions);
  } catch (error) {
    showToast(
      error?.response?.data?.message || "AI phân tích Quiz thất bại.",
      "error",
    );
  } finally {
    aiReviewLoading.value = false;
  }
};

// Gộp kết quả review mới với kết quả cũ.
const mergeAiReviewResult = (newResult, reviewedQuestions) => {
  if (!aiReviewResult.value) {
    aiReviewResult.value = newResult;
    return;
  }

  // Frontend tự biết chính xác câu nào vừa gửi đi Review.
  // Không phụ thuộc AI trả reviewed_question_ids.
  const changedIds = new Set(reviewedQuestions.map((q) => String(q.id)));

  const oldIssues = aiReviewResult.value.issues || [];

  const newIssues = newResult.issues || [];

  // Xóa toàn bộ issue cũ liên quan tới
  // những câu vừa được Review lại.
  const remainingOldIssues = oldIssues.filter((issue) => {
    const questionId = String(issue.question_id ?? "");

    const relatedId = String(issue.related_question_id ?? "");

    return !changedIds.has(questionId) && !changedIds.has(relatedId);
  });

  aiReviewResult.value = {
    ...aiReviewResult.value,

    issues: [...remainingOldIssues, ...newIssues],
  };
};

// Mở kết quả đã có hoặc review lần đầu.
const reviewQuizWithAi = async () => {
  if (!questions.value.length) {
    showToast("Chưa có câu hỏi để AI phân tích.", "error");

    return;
  }

  // Đã có kết quả rồi
  // -> chỉ mở drawer
  // -> KHÔNG gọi API
  if (aiReviewResult.value) {
    aiReviewOpen.value = true;
    return;
  }

  // Chưa từng review
  // -> lần đầu mới gọi API
  await requestAiReview();
};

// Review lại các câu hỏi đã thay đổi.
const refreshAiReview = async () => {
  await requestAiReview();
};

// Cuộn đến câu hỏi được AI nhắc tới.
const goToReviewQuestion = async (questionId) => {
  aiReviewHighlightId.value = questionId;

  await nextTick();

  const element = document.querySelector(`[data-question-id="${questionId}"]`);

  if (!element) return;

  element.scrollIntoView({
    behavior: "smooth",
    block: "center",
  });

  setTimeout(() => {
    if (aiReviewHighlightId.value === questionId) {
      aiReviewHighlightId.value = null;
    }
  }, 2500);
};

// ==================== 15. ÁP DỤNG VÀ CHUẨN HÓA DỮ LIỆU AI ====================

// Thêm câu hỏi AI gợi ý vào bộ đề.
const applyAiSuggestion = async (item) => {
  if (item.type === "fix" || item.type === "analysis") {
    showToast("Gợi ý này chỉ để xem, chưa áp dụng trực tiếp.", "error");
    return;
  }

  const newQuestion = normalizeQuestion({
    type: item.type || "single_choice",
    question: item.question,
    options: item.options,
    correct_answer: item.correct_answer,
    images: [],
  });

  questions.value.push(newQuestion);
  markDirty();

  // Đánh dấu câu mới để nhấp nháy.
  aiSuggestionHighlightId.value = newQuestion.id;

  // Chờ Vue render rồi cuộn đến câu mới.
  await nextTick();

  document
    .querySelector(`[data-question-id="${newQuestion.id}"]`)
    ?.scrollIntoView({
      behavior: "smooth",
      block: "center",
    });

  // Tắt hiệu ứng sau 2,6 giây.
  window.setTimeout(() => {
    if (aiSuggestionHighlightId.value === newQuestion.id) {
      aiSuggestionHighlightId.value = null;
    }
  }, 2600);

  showToast("Đã thêm câu hỏi vào bộ đề thành công!", "success");
};

// Tạo payload gửi đến API gợi ý AI.
const buildAiPayload = (action) => {
  const sourceQuestions =
    aiOptions.value.scope === "selected"
      ? selectedQuestions.value
      : questions.value;
  const q = {
    images: sourceQuestions.flatMap((question) => question.images || []),
  };

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
      difficulty:
        action === "generate_by_difficulty" ? aiOptions.value.difficulty : null,
      scope: action === "similar" ? "selected" : "all",
      keep_grade_scope: true,
      has_images: Boolean(q.images?.length),
      image_count: q.images?.length || 0,
    },
  };
};

// Chuẩn hóa câu hỏi trước khi gửi cho AI.
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

    question_number: getQuestionNumber(q.id),

    type: q.type,
    question,

    options: q.type === "fill_blank" ? null : options,

    correct_answer: q.correct_answer,

    has_images: Boolean(q.images?.length),
    image_count: q.images?.length || 0,
  };
};
</script>

<style scoped>
.editor-save-bar {
  position: fixed;
  right: 0;
  bottom: 0;
  left: 0;
  z-index: 40;
  border-top: 1px solid var(--border);
  background: color-mix(in srgb, var(--surface) 92%, transparent);
  padding: 0.85rem 1.25rem;
  box-shadow: 0 -12px 40px rgba(15, 23, 42, 0.12);
  backdrop-filter: blur(18px);
}

.editor-save-actions {
  display: flex;
  width: 100%;
  max-width: 1440px;
  margin: 0 auto;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 0.75rem;
}

.validation-error {
  border-color: rgb(244 63 94) !important;
  box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.16) !important;
}

.question-validation-error {
  border-color: rgba(244, 63, 94, 0.55) !important;
}

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

.ai-review-highlight {
  border-color: rgba(168, 85, 247, 0.9) !important;
  box-shadow:
    0 0 0 3px rgba(168, 85, 247, 0.18),
    0 16px 40px rgba(168, 85, 247, 0.12) !important;
  transition:
    border-color 0.25s ease,
    box-shadow 0.25s ease;
}

.ai-review-enter-active,
.ai-review-leave-active {
  transition: opacity 0.25s ease;
}

.ai-review-enter-from,
.ai-review-leave-to {
  opacity: 0;
}

.ai-suggestion-highlight {
  animation: ai-suggestion-flash 0.85s ease-in-out 3;
}

@keyframes ai-suggestion-flash {
  0%,
  100% {
    border-color: var(--border);
    box-shadow: none;
  }

  50% {
    border-color: rgba(16, 185, 129, 0.95);
    box-shadow:
      0 0 0 4px rgba(16, 185, 129, 0.2),
      0 16px 36px rgba(16, 185, 129, 0.16);
  }
}
</style>
