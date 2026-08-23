<template>
  <section class="grid gap-6 min-w-0 mx-auto w-full max-w-[1500px] 2xl:grid-cols-[minmax(0,1fr)_360px]">
    <form
      class="relative min-w-0 overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 sm:p-8 shadow-[var(--shadow-soft)] backdrop-blur-2xl"
      @submit.prevent="saveQuiz"
    >
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="pointer-events-none absolute -left-24 top-1/2 h-64 w-64 rounded-full bg-[var(--accent)]/10 blur-3xl"></div>

      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">
          Manual editor
        </p>

        <h1 class="text-3xl font-black tracking-[-0.04em] text-[var(--text)]">
          {{ isEditMode ? "Sửa quiz" : "Tạo quiz thủ công" }}
        </h1>

        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">
          Lưu quiz, câu hỏi, đáp án và ảnh bìa trực tiếp vào backend.
        </p>

        <div class="mt-8 grid gap-5">
          <div class="grid gap-4">
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Tiêu đề quiz *
              <input
                v-model="form.title"
                class="field"
                placeholder="Ví dụ: Ôn tập Sinh học lớp 10"
                required
              />
            </label>
          </div>

          <label class="grid gap-2 text-sm font-black text-[var(--text)]">
            Mô tả
            <textarea
              v-model="form.description"
              class="field min-h-24"
              placeholder="Mô tả ngắn cho người làm quiz"
            ></textarea>
          </label>

          <!-- Phân loại Chuẩn hóa EdTech: Cấp học - Khối lớp - Bộ môn -->
          <div class="grid gap-4 md:grid-cols-3">
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Cấp học
              <select v-model="form.education_level_id" class="field" @change="onTaxonomyChange">
                <option value="">-- Chọn cấp học --</option>
                <option v-for="level in taxonomyLevels" :key="level.id" :value="level.id">{{ level.name }}</option>
              </select>
            </label>

            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Khối lớp
              <select v-model="form.grade_id" class="field" @change="onTaxonomyChange">
                <option value="">-- Chọn khối lớp --</option>
                <option v-for="grade in formAvailableGrades" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
              </select>
            </label>

            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Bộ môn *
              <select v-model="form.subject_id" class="field" required @change="onTaxonomyChange">
                <option value="">-- Chọn bộ môn * --</option>
                <option v-for="subject in formAvailableSubjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
              </select>
            </label>
          </div>

          <!-- Chủ đề bộ môn: Gợi ý từ Kho + Nhập tự do -->
          <div class="grid gap-4 md:grid-cols-2">
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Chủ đề có sẵn (Gợi ý)
              <select v-model="selectedBankTopic" class="field cursor-pointer" @change="onBankTopicSelect">
                <option value="">-- Chọn chủ đề từ kho câu hỏi --</option>
                <option v-for="top in topicsList" :key="top.topic_name" :value="top.topic_name">
                  {{ top.topic_name }} ({{ top.total_questions }} câu)
                </option>
              </select>
            </label>

            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Tên Chủ đề hiển thị
              <input
                v-model="form.topic_name"
                class="field"
                placeholder="VD: Hàm số bậc hai, Unit 3..."
                @input="onTopicInputChange"
              />
            </label>
          </div>

          <section class="overflow-hidden rounded-[1.7rem] border border-[var(--border)] bg-[var(--surface-soft)] shadow-[var(--shadow-card)]">
            <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_280px]">
              <div class="p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                  <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-[var(--primary)]">
                      Ảnh bìa
                    </p>

                    <h2 class="mt-2 text-2xl font-black tracking-[-0.05em] text-[var(--text)]">
                      Cover của quiz
                    </h2>

                    <p class="mt-2 text-sm leading-6 text-[var(--muted)]">
                      Chỉ upload file ảnh từ máy. Nếu chưa chọn ảnh, hệ thống dùng gradient dự phòng.
                    </p>
                  </div>

                  <button
                    class="btn-ghost !px-4 !py-2 text-xs"
                    type="button"
                    @click="openCoverPicker"
                  >
                    Chọn ảnh
                  </button>
                </div>

                <input
                  ref="coverInput"
                  class="hidden"
                  type="file"
                  accept="image/png,image/jpeg,image/jpg,image/webp,image/gif"
                  @change="handleCoverFileChange"
                />

                <div class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1fr)_240px]">
                  <div class="rounded-[1.35rem] border border-[var(--border)] bg-[var(--surface)] p-4">
                    <p class="text-sm font-black text-[var(--text)]">File ảnh bìa</p>

                    <p class="mt-1 text-xs font-bold leading-5 text-[var(--muted)]">
                      {{ selectedCoverLabel }}
                    </p>

                    <button
                      class="btn-ghost mt-4 !px-4 !py-2 text-xs"
                      type="button"
                      @click="openCoverPicker"
                    >
                      Upload ảnh
                    </button>
                  </div>

                  <div class="grid gap-2 text-sm font-black text-[var(--text)]">
                    Avatar người tạo

                    <div class="flex min-h-[72px] items-center gap-3 rounded-[1.35rem] border border-[var(--border)] bg-[var(--surface)] px-4 py-3">
                      <UserAvatar
                        :user="creatorProfile"
                        size-class="h-12 w-12"
                        text-class="text-sm"
                        ring-class="ring-2 ring-white/10"
                      />

                      <div class="min-w-0">
                        <p class="truncate text-sm font-black text-[var(--text)]">
                          {{ creatorProfile.name || "Người tạo hiện tại" }}
                        </p>

                        <p class="truncate text-[11px] font-bold text-[var(--muted)]">
                          Avatar lấy từ hồ sơ.
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                  <button
                    v-if="form.cover || form.coverFile"
                    type="button"
                    class="rounded-full border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-xs font-black text-rose-300 transition hover:-translate-y-0.5"
                    @click="removeCover"
                  >
                    Xóa bìa
                  </button>
                </div>
              </div>

              <div
                class="relative min-h-[230px] overflow-hidden border-t border-[var(--border)] lg:border-l lg:border-t-0"
                :style="{ background: coverBackground }"
              >
                <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/15 to-white/10"></div>

                <div class="absolute bottom-4 left-4 right-4">
                  <div class="mb-3">
                    <UserAvatar
                      :user="creatorProfile"
                      size-class="h-12 w-12"
                      rounded-class="rounded-2xl"
                      text-class="text-sm"
                      ring-class="ring-2 ring-white/10"
                      shadow-class="shadow-xl"
                    />
                  </div>

                  <p class="line-clamp-2 text-lg font-black leading-6 text-white drop-shadow">
                    {{ form.title || "Quiz chưa đặt tên" }}
                  </p>

                  <p class="mt-1 text-xs font-bold text-white/75">
                    {{ form.category || "Danh mục" }} • {{ difficultyText }}
                  </p>
                </div>
              </div>
            </div>
          </section>

          <div class="grid gap-4 md:grid-cols-2">
            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Thời gian phút
              <input
                v-model.number="form.durationMinutes"
                class="field"
                type="number"
                min="1"
                max="1440"
              />
            </label>

            <label class="grid gap-2 text-sm font-black text-[var(--text)]">
              Độ khó
              <select v-model="form.difficulty" class="field">
                <option value="easy">Dễ</option>
                <option value="medium">Vừa</option>
                <option value="hard">Khó</option>
              </select>
            </label>
          </div>
        </div>

        <div class="mt-8">
          <div class="mb-4 flex items-center justify-between gap-4">
            <h2 class="text-2xl font-black tracking-[-0.05em] text-[var(--text)]">
              Visibility
            </h2>

            <VisibilityBadge :value="form.visibility" />
          </div>

          <div class="grid gap-3 md:grid-cols-3">
            <button
              v-for="option in visibilityOptions"
              :key="option.value"
              type="button"
              class="relative overflow-hidden rounded-[1.4rem] border p-4 text-left transition duration-300 hover:-translate-y-1 active:scale-[0.98]"
              :class="
                form.visibility === option.value
                  ? 'border-[var(--border-strong)] bg-[var(--chip-active)] shadow-[0_18px_44px_rgba(155,44,255,0.16)]'
                  : 'border-[var(--border)] bg-[var(--surface-soft)] hover:border-[var(--border-strong)]'
              "
              @click="form.visibility = option.value"
            >
              <div
                v-if="form.visibility === option.value"
                class="absolute inset-0 bg-gradient-to-br from-[var(--primary)]/10 via-[var(--primary-2)]/10 to-[var(--accent)]/10"
              ></div>

              <div class="relative z-10">
                <div class="mb-3 grid h-10 w-10 place-items-center rounded-2xl bg-[var(--surface)] text-xs font-black text-[var(--primary)]">
                  {{ option.short }}
                </div>

                <b class="block text-[var(--text)]">{{ option.title }}</b>

                <p class="mt-2 text-xs font-semibold leading-5 text-[var(--muted)]">
                  {{ option.description }}
                </p>
              </div>
            </button>
          </div>

          <label
            v-if="form.visibility === 'group'"
            class="mt-4 grid gap-2 text-sm font-black text-[var(--text)]"
          >
            Room gắn với quiz
            <input
              v-model="form.roomCode"
              class="field"
              placeholder="VD: QZ24"
            />
          </label>
        </div>

        <div class="mt-8 grid gap-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.18em] text-[var(--primary)]">
                Question builder
              </p>

              <h2 class="mt-1 text-2xl font-black tracking-[-0.05em] text-[var(--text)]">
                Câu hỏi và đáp án
              </h2>

              <p class="mt-2 text-sm text-[var(--muted)]">
                Phần này đã thay bằng editor của OCR/math.
              </p>
            </div>

            <div class="flex flex-wrap gap-2">
              <button
                class="btn-primary !px-4 !py-2 text-xs flex items-center gap-1.5"
                type="button"
                @click="openImportModal"
              >
                <span>📚 Chọn từ Kho & Ngân hàng câu hỏi</span>
              </button>

              <button
                class="btn-ghost !px-4 !py-2 text-xs"
                type="button"
                @click="addQuestion('single_choice')"
              >
                + Một đáp án
              </button>

              <button
                class="btn-ghost !px-4 !py-2 text-xs"
                type="button"
                @click="addQuestion('multi_choice')"
              >
                + Nhiều đáp án
              </button>

              <button
                class="btn-ghost !px-4 !py-2 text-xs"
                type="button"
                @click="addQuestion('fill_blank')"
              >
                + Điền đáp án
              </button>
            </div>
          </div>

          <div
            v-for="(q, index) in questions"
            :key="q.id"
            :ref="(el) => setQuestionCardRef(el, index)"
            class="min-w-0 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5 sm:p-6 transition hover:border-[var(--border-strong)]"
          >
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div class="flex flex-wrap items-center gap-2">
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

            <div v-if="q.allow_math" class="mt-3 flex flex-wrap items-center gap-2">
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
              placeholder="Nhập nội dung câu hỏi..."
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
                    ></textarea>

                    <math-field
                      v-else
                      :value="block.value"
                      :style="getMathBlockStyle(block.value)"
                      virtual-keyboard-mode="manual"
                      class="editor-math-field"
                      @input="updateMathBlock(q.question_blocks, blockIndex, $event)"
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

            <div v-if="q.type !== 'fill_blank'" class="mt-4 grid gap-3 md:grid-cols-2">
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
                  placeholder="Nhập đáp án..."
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
                        ></textarea>

                        <math-field
                          v-else
                          :value="block.value"
                          :style="getMathBlockStyle(block.value)"
                          virtual-keyboard-mode="manual"
                          class="editor-math-field"
                          @input="updateMathBlock(q.option_blocks[optionKey], blockIndex, $event)"
                        />

                        <button
                          type="button"
                          class="editor-block-remove"
                          @click="removeBlock(q.option_blocks[optionKey], blockIndex)"
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

      </div>
    </form>

    <div class="fixed bottom-4 right-4 z-[60] flex flex-col gap-2 rounded-[1.4rem] border border-[var(--border)] bg-[var(--surface)]/95 p-3 shadow-[var(--shadow-card)] backdrop-blur-xl">
      <button class="btn-ghost !px-4 !py-2 text-xs" type="button" @click="addQuestion('single_choice')">
        Thêm câu hỏi
      </button>
      <button class="btn-primary !px-4 !py-2 text-xs" type="button" :disabled="isSaving" @click="saveQuiz">
        {{ isSaving ? "Đang lưu..." : "Lưu quiz" }}
      </button>
    </div>

    <div
      v-if="toastMessage"
      class="fixed inset-0 z-[80] flex items-center justify-center bg-black/60 px-4 backdrop-blur-sm"
      @click="clearToast"
    >
      <div
        class="w-full max-w-md rounded-[2rem] border px-6 py-6 text-center shadow-[0_30px_80px_rgba(0,0,0,0.35)]"
        :class="toastType === 'success' ? 'border-emerald-500/30 bg-[var(--surface)] text-emerald-200' : 'border-rose-500/30 bg-[var(--surface)] text-rose-200'"
        @click.stop
      >
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full text-2xl font-black"
          :class="toastType === 'success' ? 'bg-emerald-500/15' : 'bg-rose-500/15'"
        >
          {{ toastType === 'success' ? '✓' : '!' }}
        </div>
        <h3 class="text-xl font-black text-[var(--text)]">{{ toastType === 'success' ? 'Thông báo' : 'Có lỗi xảy ra' }}</h3>
        <p class="mt-3 text-sm font-semibold leading-7 text-[var(--muted)]">{{ toastMessage }}</p>
      </div>
    </div>

    <aside class="grid content-start gap-5">
      <article class="overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <div class="relative h-52" :style="{ background: coverBackground }">
          <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-white/10"></div>

          <div class="absolute bottom-5 right-5">
            <UserAvatar
              :user="creatorProfile"
              size-class="h-14 w-14"
              rounded-class="rounded-2xl"
              text-class="text-sm"
              ring-class="ring-2 ring-white/10"
              shadow-class="shadow-xl"
            />
          </div>
        </div>

        <div class="p-6">
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">
            Live preview
          </p>

          <h3 class="mt-2 text-2xl font-black text-[var(--text)]">
            {{ form.title || "Quiz chưa đặt tên" }}
          </h3>

          <p class="mt-2 text-sm leading-6 text-[var(--muted)]">
            {{ form.description || "Mô tả sẽ hiển thị ở đây." }}
          </p>

          <div class="mt-4 flex flex-wrap items-center gap-2">
            <span v-if="selectedSubjectName" class="rounded-full bg-emerald-600/80 px-2.5 py-0.5 text-[11px] font-black text-white backdrop-blur shadow-sm">
              Môn {{ selectedSubjectName }}
            </span>
            <span v-if="form.topic_name" class="rounded-full bg-purple-600/80 px-2.5 py-0.5 text-[11px] font-black text-white backdrop-blur shadow-sm">
              Chủ đề: {{ form.topic_name }}
            </span>
            <VisibilityBadge :value="form.visibility" />

            <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-black text-[var(--muted)]">
              {{ difficultyText }}
            </span>

            <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-black text-[var(--muted)]">
              {{ questions.length }} câu
            </span>
          </div>
        </div>
      </article>

      <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">
          Kiểm tra nhanh
        </p>

        <div class="mt-5 grid gap-3">
          <div
            v-for="item in checklist"
            :key="item"
            class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 text-sm font-bold text-[var(--muted)]"
          >
            {{ item }}
          </div>
        </div>
      </article>
    </aside>
  </section>
    <!-- MODAL 📚 CHỌN CÂU HỎI TỪ NGÂN HÀNG (QUIZFLEX BALANCED LAYOUT & VISUAL REDESIGN) -->
    <div
      v-if="isImportModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 sm:p-6 md:p-8 backdrop-blur-md overflow-y-auto"
      @click.self="isImportModalOpen = false"
    >
      <div
        class="relative w-full max-w-2xl max-h-[88vh] my-auto flex flex-col rounded-[2.2rem] border border-purple-500/25 bg-[#0b0717] shadow-[0_25px_80px_rgba(0,0,0,0.85)] overflow-hidden"
      >
        <!-- 1. HEADER CỐ ĐỊNH (RỘNG RÃI, KHÔNG LẸM MÉP) -->
        <div
          class="flex items-start justify-between px-7 pt-6 pb-4 border-b border-purple-500/20 bg-[#0b0717] shrink-0 gap-4"
        >
          <div>
            <h3 class="text-lg sm:text-xl font-black text-[#f8f7ff] tracking-tight leading-tight">
              Chọn câu hỏi
            </h3>
            <p class="text-xs font-semibold text-[#a4a1b5] mt-1">
              Chọn câu hỏi từ ngân hàng để thêm vào Quiz
            </p>
          </div>
          <button
            type="button"
            class="h-9 w-9 rounded-full shrink-0 flex items-center justify-center text-base font-bold text-[#a4a1b5] hover:text-[#f8f7ff] hover:bg-[#161324] transition"
            @click="isImportModalOpen = false"
          >
            ✕
          </button>
        </div>

        <!-- 2. SEARCH BAR (VÙNG CHÍNH TÌM KIẾM - SVG ICON TÁCH BIỆT HẲN VỚI TEXT) -->
        <div class="px-7 pt-5 pb-3 bg-[#141022]/40 shrink-0">
          <div class="relative flex items-center w-full">
            <svg class="absolute left-3.5 h-4 w-4 text-[#a4a1b5] pointer-events-none shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="importFilters.search"
              type="text"
              class="w-full rounded-xl border border-purple-500/20 bg-[#141022] text-xs font-semibold text-[#f8f7ff] placeholder-[#8a859e] pl-10 pr-4 py-2.5 outline-none transition-all duration-200 focus:border-[#9b2cff] focus:ring-1 focus:ring-[#9b2cff]/40 shadow-[inset_0_1px_3px_rgba(0,0,0,0.3)]"
              placeholder="Tìm kiếm câu hỏi..."
              @keyup.enter="loadImportBank(1)"
            />
          </div>
        </div>

        <!-- 3. FILTERS BAR (ĐẦY ĐỦ 5 BỘ LỌC: CẤP HỌC - KHỐI LỚP - MÔN HỌC - CHỦ ĐỀ - ĐỘ KHÓ) -->
        <div class="px-7 pb-4 bg-[#141022]/40 border-b border-purple-500/20 shrink-0 space-y-2.5">
          <!-- Hàng 1: Cấp học - Khối lớp - Môn học -->
          <div class="grid grid-cols-3 gap-2.5">
            <!-- Cấp học -->
            <div class="relative flex items-center">
              <select
                v-model="importFilters.education_level_id"
                class="w-full appearance-none rounded-xl border border-purple-500/20 bg-[#141022] text-xs font-semibold text-[#f8f7ff] pl-3 pr-8 py-2.5 h-10 outline-none transition-all duration-200 focus:border-[#9b2cff] focus:ring-1 focus:ring-[#9b2cff]/40 cursor-pointer"
                :class="importFilters.education_level_id ? 'border-[#9b2cff]/60 bg-[#9b2cff]/10' : ''"
                @change="onModalTaxonomyChange"
              >
                <option value="" class="bg-[#141022] text-[#f8f7ff]">-- Cấp học --</option>
                <option
                  v-for="level in taxonomyLevels"
                  :key="level.id"
                  :value="level.id"
                  class="bg-[#141022] text-[#f8f7ff]"
                >
                  {{ level.name }}
                </option>
              </select>
              <svg class="absolute right-3 h-3.5 w-3.5 text-[#9b96b0] pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </div>

            <!-- Khối lớp -->
            <div class="relative flex items-center">
              <select
                v-model="importFilters.grade_id"
                class="w-full appearance-none rounded-xl border border-purple-500/20 bg-[#141022] text-xs font-semibold text-[#f8f7ff] pl-3 pr-8 py-2.5 h-10 outline-none transition-all duration-200 focus:border-[#9b2cff] focus:ring-1 focus:ring-[#9b2cff]/40 cursor-pointer"
                :class="importFilters.grade_id ? 'border-[#9b2cff]/60 bg-[#9b2cff]/10' : ''"
                @change="onModalTaxonomyChange"
              >
                <option value="" class="bg-[#141022] text-[#f8f7ff]">-- Khối lớp --</option>
                <option
                  v-for="grade in modalAvailableGrades"
                  :key="grade.id"
                  :value="grade.id"
                  class="bg-[#141022] text-[#f8f7ff]"
                >
                  {{ grade.name }}
                </option>
              </select>
              <svg class="absolute right-3 h-3.5 w-3.5 text-[#9b96b0] pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </div>

            <!-- Môn học -->
            <div class="relative flex items-center">
              <select
                v-model="importFilters.subject_id"
                class="w-full appearance-none rounded-xl border border-purple-500/20 bg-[#141022] text-xs font-semibold text-[#f8f7ff] pl-3 pr-8 py-2.5 h-10 outline-none transition-all duration-200 focus:border-[#9b2cff] focus:ring-1 focus:ring-[#9b2cff]/40 cursor-pointer"
                :class="importFilters.subject_id ? 'border-[#9b2cff]/60 bg-[#9b2cff]/10' : ''"
                @change="onModalTaxonomyChange"
              >
                <option value="" class="bg-[#141022] text-[#f8f7ff]">-- Môn học --</option>
                <option
                  v-for="subject in modalAvailableSubjects"
                  :key="subject.id"
                  :value="subject.id"
                  class="bg-[#141022] text-[#f8f7ff]"
                >
                  {{ subject.name }}
                </option>
              </select>
              <svg class="absolute right-3 h-3.5 w-3.5 text-[#9b96b0] pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </div>

          <!-- Hàng 2: Chủ đề - Độ khó -->
          <div class="grid grid-cols-2 gap-2.5">
            <!-- Chủ đề -->
            <div class="relative flex items-center">
              <select
                v-model="importFilters.topic_name"
                class="w-full appearance-none rounded-xl border border-purple-500/20 bg-[#141022] text-xs font-semibold text-[#f8f7ff] pl-3 pr-8 py-2.5 h-10 outline-none transition-all duration-200 focus:border-[#9b2cff] focus:ring-1 focus:ring-[#9b2cff]/40 cursor-pointer"
                :class="importFilters.topic_name ? 'border-[#9b2cff]/60 bg-[#9b2cff]/10' : ''"
                @change="loadImportBank(1)"
              >
                <option value="" class="bg-[#141022] text-[#f8f7ff]">-- Chủ đề --</option>
                <option
                  v-for="top in modalTopicsList"
                  :key="top.topic_name"
                  :value="top.topic_name"
                  class="bg-[#141022] text-[#f8f7ff]"
                >
                  {{ top.topic_name }} ({{ top.total_questions }})
                </option>
              </select>
              <svg class="absolute right-3 h-3.5 w-3.5 text-[#9b96b0] pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </div>

            <!-- Độ khó -->
            <div class="relative flex items-center">
              <select
                v-model="importFilters.difficulty"
                class="w-full appearance-none rounded-xl border border-purple-500/20 bg-[#141022] text-xs font-semibold text-[#f8f7ff] pl-3 pr-8 py-2.5 h-10 outline-none transition-all duration-200 focus:border-[#9b2cff] focus:ring-1 focus:ring-[#9b2cff]/40 cursor-pointer"
                :class="importFilters.difficulty ? 'border-[#9b2cff]/60 bg-[#9b2cff]/10' : ''"
                @change="loadImportBank(1)"
              >
                <option value="" class="bg-[#141022] text-[#f8f7ff]">-- Độ khó --</option>
                <option value="easy" class="bg-[#141022] text-[#f8f7ff]">Dễ</option>
                <option value="medium" class="bg-[#141022] text-[#f8f7ff]">Trung bình</option>
                <option value="hard" class="bg-[#141022] text-[#f8f7ff]">Khó</option>
              </select>
              <svg class="absolute right-3 h-3.5 w-3.5 text-[#9b96b0] pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </div>
        </div>

        <!-- 4. SUB-HEADER BAR (ĐẾM TỔNG SỐ CÂU HỎI TRONG DATABASE & TRANG HIỆN TẠI) -->
        <div
          class="px-7 py-3 border-b border-purple-500/20 bg-[#141022]/40 shrink-0 flex items-center justify-between text-xs font-bold text-[#a4a1b5]"
        >
          <span>
            Tổng cộng <strong class="text-[#f8f7ff] font-extrabold">{{ totalImportBankItems }}</strong> câu hỏi
            <span v-if="lastImportBankPage > 1" class="text-[11px] font-normal text-[#8a859e] ml-1">
              (Trang {{ currentImportBankPage }}/{{ lastImportBankPage }})
            </span>
          </span>
          <button
            v-if="importBankItems.length > 0"
            type="button"
            class="text-xs font-extrabold text-[#9b2cff] hover:text-[#cf30ff] transition"
            @click="toggleSelectAllImportBank"
          >
            {{ isAllImportSelected ? 'Bỏ chọn trang này' : 'Chọn tất cả trang này' }}
          </button>
        </div>

        <!-- 5. QUESTION LIST (COMPACT SELECTION CARDS + PHÂN TRANG) -->
        <div
          class="flex-1 overflow-y-auto min-h-[220px] max-h-[46vh] space-y-2.5 p-6 scrollbar-soft"
        >
          <div
            v-if="isLoadingImportBank"
            class="py-16 text-center text-xs font-bold text-[#a4a1b5] flex flex-col items-center justify-center gap-2"
          >
            <div class="h-6 w-6 animate-spin rounded-full border-2 border-[#9b2cff] border-t-transparent"></div>
            <span>Đang tải danh sách câu hỏi...</span>
          </div>

          <div
            v-else-if="importBankItems.length === 0"
            class="py-16 text-center text-xs font-bold text-[#a4a1b5]"
          >
            Không tìm thấy câu hỏi phù hợp.
          </div>

          <template v-else>
            <div
              v-for="item in importBankItems"
              :key="item.id"
              class="group flex items-start gap-4 p-4 rounded-2xl border transition-all duration-200 cursor-pointer"
              :class="
                selectedImportIds.includes(item.id)
                  ? 'border-[#9b2cff] bg-[#9b2cff]/12 shadow-[0_0_20px_rgba(155,44,255,0.12)]'
                  : 'border-purple-500/15 bg-[#141022]/60 hover:border-purple-500/40 hover:bg-[#1a152b]'
              "
              @click="toggleImportSelect(item.id)"
            >
              <!-- Checkbox Tinh Tế -->
              <div
                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-lg border transition-all duration-200"
                :class="
                  selectedImportIds.includes(item.id)
                    ? 'border-[#9b2cff] bg-[#9b2cff] text-white shadow-[0_0_8px_#9b2cff]'
                    : 'border-purple-500/20 bg-[#161324] group-hover:border-[#a4a1b5]'
                "
              >
                <svg
                  v-if="selectedImportIds.includes(item.id)"
                  class="h-3.5 w-3.5 stroke-[3]"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M5 13l4 4L19 7"
                  />
                </svg>
              </div>

              <!-- Nội dung & Metadata Subline -->
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 mb-1">
                  <span class="font-mono text-xs font-black text-[#9b2cff]">#{{ item.id }}</span>
                </div>
                <p class="text-xs sm:text-sm font-bold text-[#f8f7ff] leading-snug line-clamp-2">
                  {{ item.content || item.text }}
                </p>
                <p class="text-[11px] font-semibold text-[#a4a1b5] mt-1.5 truncate">
                  {{ getQuestionMetaText(item) }}
                </p>
              </div>
            </div>

            <!-- PHÂN TRANG CHO PHÉP XEM KHÔNG GIỚI HẠN TOÀN BỘ CÂU HỎI -->
            <div
              v-if="lastImportBankPage > 1"
              class="flex items-center justify-between pt-4 pb-1 border-t border-purple-500/20 mt-3 text-xs"
            >
              <button
                type="button"
                class="px-3.5 py-1.5 rounded-xl border border-purple-500/20 bg-[#141022] text-[#a4a1b5] hover:text-[#f8f7ff] hover:border-purple-500/40 disabled:opacity-30 disabled:cursor-not-allowed transition font-semibold"
                :disabled="currentImportBankPage <= 1"
                @click="loadImportBank(currentImportBankPage - 1)"
              >
                ◀ Trang trước
              </button>
              <span class="font-bold text-[#f8f7ff]">
                Trang {{ currentImportBankPage }} / {{ lastImportBankPage }}
              </span>
              <button
                type="button"
                class="px-3.5 py-1.5 rounded-xl border border-purple-500/20 bg-[#141022] text-[#a4a1b5] hover:text-[#f8f7ff] hover:border-purple-500/40 disabled:opacity-30 disabled:cursor-not-allowed transition font-semibold"
                :disabled="currentImportBankPage >= lastImportBankPage"
                @click="loadImportBank(currentImportBankPage + 1)"
              >
                Trang sau ▶
              </button>
            </div>
          </template>
        </div>

        <!-- 6. FOOTER CỐ ĐỊNH (CÂN ĐỐI & NHẸ NHÀNG) -->
        <div
          class="flex items-center justify-between px-7 py-4 border-t border-purple-500/20 bg-[#0b0717] shrink-0"
        >
          <span class="text-xs font-bold text-[#a4a1b5]">
            Đã chọn
            <strong class="text-[#f8f7ff] font-extrabold text-sm ml-1">{{ selectedImportIds.length }}</strong>
            câu
          </span>
          <div class="flex items-center gap-3">
            <button
              type="button"
              class="btn-ghost !px-5 !py-2.5 text-xs font-extrabold rounded-full"
              @click="isImportModalOpen = false"
            >
              Hủy
            </button>
            <button
              type="button"
              class="btn-primary !px-5 !py-2.5 text-xs font-extrabold rounded-full shadow-[0_0_20px_rgba(155,44,255,0.3)] disabled:opacity-40 disabled:shadow-none disabled:cursor-not-allowed disabled:transform-none"
              :disabled="selectedImportIds.length === 0"
              @click="confirmImportQuestions"
            >
              Thêm {{ selectedImportIds.length }} câu
            </button>
          </div>
        </div>
      </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import draggable from "vuedraggable";
import "mathlive";
import "mathlive/static.css";
import VisibilityBadge from "@/components/common/VisibilityBadge.vue";
import UserAvatar from "@/components/common/UserAvatar.vue";
import {
  buildEditorDraftFromQuiz,
  coverToBackground,
  currentUserStorage,
  difficultyLabel,
  normalizeQuestion as normalizeApiQuestion,
  questionsBankApi,
  quizzesApi,
  taxonomyApi,
} from "@/services/api";

const taxonomyLevels = ref([]);
const taxonomyAllSubjects = ref([]);
const topicsList = ref([]);
const selectedBankTopic = ref("");

const fetchFormTaxonomies = async () => {
  try {
    const data = await taxonomyApi.tree();
    if (data) {
      taxonomyLevels.value = data.education_levels || [];
      taxonomyAllSubjects.value = data.subjects || [];
    }
  } catch (e) {
    console.error("Không tải được danh mục taxonomy:", e);
  }
};

const fetchTopicsList = async () => {
  try {
    const params = {
      education_level_id: form.education_level_id || undefined,
      grade_id: form.grade_id || undefined,
      subject_id: form.subject_id || undefined,
    };
    const data = await questionsBankApi.fetchTopics(params);
    topicsList.value = Array.isArray(data)
      ? data
      : data?.data && Array.isArray(data.data)
      ? data.data
      : [];
  } catch (e) {
    console.error("Không tải được danh sách Chủ đề từ kho:", e);
  }
};

const onTaxonomyChange = () => {
  form.grade_id = form.grade_id || "";
  fetchTopicsList();
};

const onBankTopicSelect = () => {
  if (selectedBankTopic.value) {
    form.topic_name = selectedBankTopic.value;
  }
};

const onTopicInputChange = () => {
  const text = (form.topic_name || "").trim().toLowerCase();
  if (!text) {
    selectedBankTopic.value = "";
  } else {
    const found = topicsList.value.find(
      (t) => t.topic_name.toLowerCase() === text,
    );
    selectedBankTopic.value = found ? found.topic_name : "";
  }
};

const formAvailableGrades = computed(() => {
  if (!form.education_level_id) {
    return taxonomyLevels.value.flatMap((l) => l.grades || []);
  }
  const level = taxonomyLevels.value.find(
    (l) => l.id === Number(form.education_level_id),
  );
  return level ? level.grades || [] : [];
});

const formAvailableSubjects = computed(() => {
  if (!form.grade_id) {
    return taxonomyAllSubjects.value;
  }
  const grade = formAvailableGrades.value.find(
    (g) => g.id === Number(form.grade_id),
  );
  return grade && grade.subjects ? grade.subjects : taxonomyAllSubjects.value;
});

const selectedSubjectName = computed(() => {
  if (!form.subject_id) return "";
  const sub = taxonomyAllSubjects.value.find((s) => s.id === Number(form.subject_id));
  return sub ? sub.name : "";
});

const isImportModalOpen = ref(false);
const isLoadingImportBank = ref(false);
const importBankItems = ref([]);
const selectedImportIds = ref([]);
const modalTopicsList = ref([]);

const totalImportBankItems = ref(0);
const currentImportBankPage = ref(1);
const lastImportBankPage = ref(1);

const importFilters = reactive({
  search: "",
  education_level_id: "",
  grade_id: "",
  subject_id: "",
  topic_name: "",
  difficulty: "",
  page: 1,
});

const modalAvailableGrades = computed(() => {
  if (!importFilters.education_level_id) {
    return taxonomyLevels.value.flatMap((l) => l.grades || []);
  }
  const level = taxonomyLevels.value.find(
    (l) => l.id === Number(importFilters.education_level_id),
  );
  return level ? level.grades || [] : [];
});

const modalAvailableSubjects = computed(() => {
  if (!importFilters.grade_id) {
    return taxonomyAllSubjects.value;
  }
  const grade = modalAvailableGrades.value.find(
    (g) => g.id === Number(importFilters.grade_id),
  );
  return grade && grade.subjects ? grade.subjects : taxonomyAllSubjects.value;
});

const fetchModalTopicsList = async () => {
  try {
    const params = {
      education_level_id: importFilters.education_level_id || undefined,
      grade_id: importFilters.grade_id || undefined,
      subject_id: importFilters.subject_id || undefined,
    };
    const data = await questionsBankApi.fetchTopics(params);
    modalTopicsList.value = Array.isArray(data) ? data : [];
  } catch (e) {
    console.error("Không tải được danh sách Chủ đề modal:", e);
  }
};

const onModalTaxonomyChange = () => {
  importFilters.grade_id = importFilters.grade_id || "";
  fetchModalTopicsList();
  loadImportBank(1);
};

const openImportModal = async () => {
  isImportModalOpen.value = true;
  selectedImportIds.value = [];
  importFilters.education_level_id = form.education_level_id || "";
  importFilters.grade_id = form.grade_id || "";
  importFilters.subject_id = form.subject_id || "";
  importFilters.topic_name = form.topic_name || "";
  await fetchModalTopicsList();
  await loadImportBank(1);
};

const loadImportBank = async (page = 1) => {
  isLoadingImportBank.value = true;
  importFilters.page = page;
  try {
    const res = await questionsBankApi.fetchBank({
      search: importFilters.search || undefined,
      education_level_id: importFilters.education_level_id || undefined,
      grade_id: importFilters.grade_id || undefined,
      subject_id: importFilters.subject_id || undefined,
      topic_name: importFilters.topic_name || undefined,
      difficulty: importFilters.difficulty || undefined,
      page: importFilters.page,
      per_page: 20,
    });
    importBankItems.value = res.items || [];
    totalImportBankItems.value = res.total || 0;
    currentImportBankPage.value = res.currentPage || 1;
    lastImportBankPage.value = res.lastPage || 1;
  } catch (err) {
    console.error("Không tải được ngân hàng câu hỏi:", err);
  } finally {
    isLoadingImportBank.value = false;
  }
};

const isAllImportSelected = computed(() => {
  if (importBankItems.value.length === 0) return false;
  return importBankItems.value.every((item) =>
    selectedImportIds.value.includes(item.id),
  );
});

const toggleSelectAllImportBank = () => {
  if (isAllImportSelected.value) {
    selectedImportIds.value = [];
  } else {
    selectedImportIds.value = importBankItems.value.map((item) => item.id);
  }
};

const formatDifficultyLabel = (diff) => {
  if (diff === "easy") return "Dễ";
  if (diff === "hard") return "Khó";
  return "Trung bình";
};

const getQuestionMetaText = (item) => {
  const parts = [];
  const subjectName = item.subject_name || item.subject?.name;
  if (subjectName) parts.push(subjectName);
  if (item.topic_name) parts.push(item.topic_name);
  parts.push(formatDifficultyLabel(item.difficulty));
  parts.push(item.is_public ? "Public" : "Private");
  return parts.join(" · ");
};

const toggleImportSelect = (id) => {
  const idx = selectedImportIds.value.indexOf(id);
  if (idx > -1) {
    selectedImportIds.value.splice(idx, 1);
  } else {
    selectedImportIds.value.push(id);
  }
};

const confirmImportQuestions = () => {
  const selectedItems = importBankItems.value.filter((item) =>
    selectedImportIds.value.includes(item.id),
  );
  if (selectedItems.length > 0) {
    const formatted = normalizeEditorQuestions(selectedItems);
    questions.value.push(...formatted);
    if (typeof showToast === 'function') showToast(`Đã chèn ${selectedItems.length} câu hỏi vào Quiz!`, "success");
  }
  selectedImportIds.value = [];
  isImportModalOpen.value = false;
};

const route = useRoute();
const router = useRouter();

const questionBase = computed(() =>
  route.path.startsWith("/dashboard")
    ? "/dashboard/questions"
    : "/admin/questions",
);

const visibilityOptions = [
  {
    value: "public",
    short: "PUB",
    title: "Public",
    description: "Ai cũng có thể xem và làm bài.",
  },
  {
    value: "private",
    short: "PRI",
    title: "Private",
    description: "Chỉ hiển thị trong khu quản trị.",
  },
  {
    value: "group",
    short: "GRP",
    title: "Group",
    description: "Gắn quiz với room code.",
  },
];

const isEditMode = computed(() => Boolean(route.params.id));
const isSaving = ref(false);
const errorMessage = ref("");
const successMessage = ref("");
const toastMessage = ref("");
const toastType = ref("success");
const toastTimer = ref(null);
const coverInput = ref(null);
const coverPreview = ref("");
const questionImageInputs = ref([]);
const questionCardRefs = ref([]);
const ocrMode = ref("math");

const form = reactive({
  title: "",
  education_level_id: "",
  grade_id: "",
  subject_id: "",
  topic_name: "",
  description: "",
  cover: "",
  coverFile: null,
  removeCover: false,
  durationMinutes: 12,
  difficulty: "medium",
  category: "",
  visibility: route.query.visibility === "group" ? "group" : "public",
  roomCode:
    route.query.visibility === "group"
      ? `GR${Math.floor(1000 + Math.random() * 9000)}`
      : "",
});

const questions = ref([]);
const checklist = [
  "Có tiêu đề rõ ràng",
  "Đã chọn Bộ môn học (*)",
  "Có ảnh bìa hoặc gradient dự phòng",
  "Ít nhất 1 câu hỏi",
  "Mỗi câu có đáp án",
  "Mỗi câu có đáp án đúng",
  "Group quiz cần có room code",
];

const dragOptions = {
  animation: 180,
  handle: ".drag-handle",
  ghostClass: "drag-ghost",
};

const difficultyText = computed(() => difficultyLabel(form.difficulty));
const creatorProfile = ref(
  currentUserStorage.get() || { name: "Guest", email: "", avatar: "" },
);
const coverBackground = computed(() =>
  coverToBackground(coverPreview.value || form.cover),
);
const selectedCoverLabel = computed(() => {
  if (form.coverFile) return `Đã chọn: ${form.coverFile.name}`;
  if (form.cover) return "Đang dùng ảnh bìa đã lưu.";
  return "Chưa có ảnh bìa. Hệ thống sẽ dùng gradient dự phòng.";
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

  const source = Array.isArray(options)
    ? Object.fromEntries(options.map((item) => [item.key, item.text]))
    : options || {};

  return {
    A: source.A || "",
    B: source.B || "",
    C: source.C || "",
    D: source.D || "",
  };
};

const normalizeEditorQuestion = (item = {}) => {
  const type = item.type || "single_choice";
  const questionText = item.question || item.text || "";
  const options = normalizeOptions(item.options || item.answers, type);

  const correctFromAnswers = Array.isArray(item.answers)
    ? item.answers.find((answer) => answer.is_correct)?.key
    : null;

  const correctAnswer =
    item.correct_answer ||
    item.correct ||
    correctFromAnswers ||
    (type === "multi_choice" ? [] : type === "fill_blank" ? [""] : null);

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
    backend_id: item.backend_id || item.id || null,
    type,
    question: questionText,
    question_blocks: parseBlocks(questionText),
    options,
    option_blocks: optionBlocks,
    correct_answer:
      type === "multi_choice"
        ? Array.isArray(correctAnswer)
          ? correctAnswer
          : correctAnswer
            ? [correctAnswer]
            : []
        : type === "fill_blank"
          ? Array.isArray(correctAnswer)
            ? correctAnswer
            : correctAnswer
              ? [correctAnswer]
              : [""]
          : correctAnswer || null,
    points: Number(item.points) || 10,
    images: item.images || [],
    allow_math: ocrMode.value === "math",
    editor_mode: shouldUseMath ? "math" : "normal",
  };
};

const normalizeEditorQuestions = (items = []) => {
  return items.map((item) => normalizeEditorQuestion(item));
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
};

const addFillAnswer = (question) => {
  if (!Array.isArray(question.correct_answer)) {
    question.correct_answer = [];
  }

  question.correct_answer.push("");
};

const removeFillAnswer = (question, index) => {
  question.correct_answer.splice(index, 1);

  if (!question.correct_answer.length) {
    question.correct_answer.push("");
  }
};

const addTextBlock = (blocks) => {
  blocks.push(createBlock("text", ""));
};

const addMathBlock = (blocks) => {
  blocks.push(createBlock("math", "x^2"));
};

const updateMathBlock = (blocks, index, event) => {
  blocks[index].value = event.target.value;
};

const removeBlock = (blocks, index) => {
  blocks.splice(index, 1);

  if (!blocks.length) {
    blocks.push(createBlock("text", ""));
  }
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
    errorMessage.value = "File đính kèm phải là ảnh.";
    showToast(errorMessage.value, "error");
    event.target.value = "";
    return;
  }

  if (file.size > 4 * 1024 * 1024) {
    errorMessage.value = "Ảnh câu hỏi tối đa 4MB.";
    showToast(errorMessage.value, "error");
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
};

const removeQuestionImage = (question, index) => {
  question.images.splice(index, 1);
};

const setQuestionCardRef = (el, index) => {
  questionCardRefs.value[index] = el;
};

const scrollToLastQuestion = () => {
  nextTick(() => {
    const lastIndex = questions.value.length - 1;
    const target = questionCardRefs.value[lastIndex];
    target?.scrollIntoView({ behavior: "smooth", block: "start" });
  });
};

const clearToast = () => {
  if (toastTimer.value) {
    clearTimeout(toastTimer.value);
    toastTimer.value = null;
  }
  toastMessage.value = "";
};

const showToast = (message, type = "success") => {
  clearToast();
  toastMessage.value = message;
  toastType.value = type;
  toastTimer.value = window.setTimeout(() => {
    clearToast();
  }, 5000);
};

const addQuestion = (type = "single_choice") => {
  questions.value.push(
    normalizeEditorQuestion({
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

  scrollToLastQuestion();
};

const removeQuestion = (index) => {
  if (questions.value.length === 1) {
    errorMessage.value = "Quiz cần ít nhất 1 câu hỏi.";
    showToast(errorMessage.value, "error");
    return;
  }

  questions.value.splice(index, 1);
  questionCardRefs.value.splice(index, 1);
};

const buildSavePayload = () => {
  return questions.value.map((q, index) => {
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

    const answers =
      q.type === "fill_blank"
        ? []
        : Object.keys(options).map((key, answerIndex) => ({
            key,
            text: String(options[key] || "").trim(),
            order: answerIndex,
            is_correct:
              q.type === "multi_choice"
                ? q.correct_answer.includes(key)
                : q.correct_answer === key,
          }));

    return {
      id: q.backend_id || undefined,
      type: q.type,
      question,
      text: question,
      options: q.type === "fill_blank" ? null : options,
      correct_answer: q.correct_answer,
      correct: q.type === "single_choice" ? q.correct_answer : undefined,
      order: index,
      points: Number(q.points) || 10,
      answers,
      images: (q.images || []).map((image) => ({
        name: image.name,
        type: image.type,
        size: image.size,
        preview: image.preview,
      })),
    };
  });
};

const openCoverPicker = () => coverInput.value?.click();

const revokeCoverPreview = () => {
  if (coverPreview.value?.startsWith("blob:")) {
    URL.revokeObjectURL(coverPreview.value);
  }
};

const clearSelectedCoverFile = () => {
  revokeCoverPreview();
  form.coverFile = null;
  coverPreview.value = "";

  if (coverInput.value) coverInput.value.value = "";
};

const handleCoverFileChange = (event) => {
  const [file] = event.target.files || [];
  if (!file) return;

  if (!file.type.startsWith("image/")) {
    errorMessage.value = "File bìa phải là ảnh.";
    event.target.value = "";
    return;
  }

  if (file.size > 4 * 1024 * 1024) {
    errorMessage.value = "Ảnh bìa tối đa 4MB.";
    event.target.value = "";
    return;
  }

  revokeCoverPreview();
  form.coverFile = file;
  form.removeCover = false;
  coverPreview.value = URL.createObjectURL(file);
  errorMessage.value = "";
};

const removeCover = () => {
  clearSelectedCoverFile();
  form.cover = "";
  form.removeCover = true;
};

const makePayload = () => {
  const currentUser = currentUserStorage.get();

  const payload = {
    user_id: currentUser?.id,
    title: form.title.trim(),
    education_level_id: form.education_level_id || undefined,
    grade_id: form.grade_id || undefined,
    subject_id: form.subject_id || undefined,
    topic_name: form.topic_name ? form.topic_name.trim() : undefined,
    category: form.topic_name ? form.topic_name.trim() : undefined,
    description: form.description.trim(),
    duration_minutes: Number(form.durationMinutes) || 12,
    difficulty: form.difficulty,
    visibility: form.visibility,
    roomCode: form.roomCode.trim(),
    questions: buildSavePayload(),
  };

  if (form.coverFile) {
    payload.cover_file = form.coverFile;
  } else if (form.removeCover) {
    payload.remove_cover = true;
  }

  return payload;
};

const validateBeforeSave = () => {
  if (!form.title.trim()) return "Bạn chưa nhập tiêu đề quiz.";
  if (!form.subject_id) return "Vui lòng chọn Bộ môn cho bài quiz.";

  const duration = Number(form.durationMinutes);
  if (!Number.isFinite(duration) || duration < 1 || duration > 1440)
    return "Thời gian làm bài phải từ 1 đến 1440 phút.";

  if (form.visibility === "group" && !form.roomCode.trim()) {
    return "Quiz dạng group cần có room code.";
  }

  if (form.visibility === "group" && !form.roomCode.trim()) {
    return "Quiz dạng group cần có room code.";
  }

  if (questions.value.length === 0) return "Quiz cần ít nhất 1 câu hỏi.";

  for (const [index, q] of questions.value.entries()) {
    const questionText =
      q.editor_mode === "math" ? blocksToString(q.question_blocks) : q.question;

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

      if (q.type === "multi_choice" && !q.correct_answer.length) {
        return `Câu ${index + 1} chưa chọn đáp án đúng.`;
      }
    }

    if (q.type === "fill_blank") {
      if (!q.correct_answer.some((answer) => String(answer).trim())) {
        return `Câu ${index + 1} chưa có đáp án điền đúng.`;
      }
    }
  }

  return "";
};

const saveQuiz = async () => {
  errorMessage.value = validateBeforeSave();
  successMessage.value = "";

  if (errorMessage.value) {
    showToast(errorMessage.value, "error");
    return;
  }

  isSaving.value = true;

  try {
    const payload = makePayload();

    const saved = isEditMode.value
      ? await quizzesApi.update(route.params.id, payload)
      : await quizzesApi.create(payload);

    successMessage.value = isEditMode.value
      ? "Đã cập nhật quiz."
      : "Đã tạo quiz mới.";
    showToast(successMessage.value, "success");

    if (!isEditMode.value) {
      router.push(`${questionBase.value}/edit/${saved.id}`);
    }
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message || `Lưu thất bại: ${error.message}`;
    showToast(errorMessage.value, "error");
  } finally {
    isSaving.value = false;
  }
};

const loadQuizForEdit = async () => {
  if (!isEditMode.value) return;

  try {
    const quiz = await quizzesApi.getForEdit(route.params.id);

    form.title = quiz.title || "";
    form.education_level_id = quiz.education_level_id || "";
    form.grade_id = quiz.grade_id || "";
    form.subject_id = quiz.subject_id || "";
    form.topic_name = quiz.topic_name || "";
    await fetchTopicsList();
    onTopicInputChange();
    form.description = quiz.description || "";
    form.cover = quiz.cover || "";
    form.coverFile = null;
    form.removeCover = false;
    coverPreview.value = "";
    form.durationMinutes =
      quiz.duration_minutes || Math.ceil((quiz.time_limit_seconds || 720) / 60);
    form.difficulty = quiz.difficulty || "medium";
    form.visibility = quiz.visibility || "public";
    form.roomCode = quiz.room_code || "";

    questions.value = normalizeEditorQuestions(
      (quiz.questions || []).map((question) => {
        const normalized = normalizeApiQuestion(question);

        return {
          id: normalized.id,
          question: normalized.question,
          type: normalized.type || "single_choice",
          options: Object.fromEntries(
            (normalized.answers || []).map((answer) => [answer.key, answer.text]),
          ),
          correct_answer: normalized.correct || normalized.correct_answer || "A",
          points: normalized.points || 10,
          images: normalized.images || [],
        };
      }),
    );

    if (questions.value.length === 0) addQuestion("single_choice");
  } catch (error) {
    errorMessage.value = `Không tải được quiz để sửa: ${error.message}`;
  }
};

const hydrateDraft = (draft) => {
  const normalized = buildEditorDraftFromQuiz(draft);

  form.title = normalized.title || form.title;
  form.tag = normalized.tag || "AI";
  form.description = normalized.description || form.description;
  form.durationMinutes = normalized.durationMinutes || form.durationMinutes;
  form.difficulty = normalized.difficulty || form.difficulty;
  form.category = normalized.category || form.category;
  form.visibility = normalized.visibility || form.visibility;
  form.roomCode = normalized.roomCode || form.roomCode;

  questions.value = normalized.questions.length
    ? normalizeEditorQuestions(
        normalized.questions.map((question) => ({
          id: question.id || null,
          question: question.text || "",
          type: question.type || "single_choice",
          options: Object.fromEntries(
            (question.answers || []).map((answer) => [answer.key, answer.text]),
          ),
          correct_answer: question.correct || "A",
          points: question.points || 10,
          images: question.images || [],
        })),
      )
    : [];

  if (!questions.value.length) addQuestion("single_choice");
};

const loadOcrDraft = () => {
  if (isEditMode.value) return;

  const draft = sessionStorage.getItem("quizflex_ai_draft");
  if (draft) {
    try {
      const parsed = JSON.parse(draft);
      hydrateDraft(parsed);
      sessionStorage.removeItem("quizflex_ai_draft");
      return;
    } catch {
      sessionStorage.removeItem("quizflex_ai_draft");
    }
  }

  const rawQuestions = sessionStorage.getItem("quizflex_questions");
  if (rawQuestions) {
    try {
      const parsed = JSON.parse(rawQuestions);

      if (Array.isArray(parsed) && parsed.length) {
        form.tag = "OCR";
        form.category = "OCR";
        form.visibility = "private";
        questions.value = normalizeEditorQuestions(parsed);
        sessionStorage.removeItem("quizflex_questions");
        return;
      }
    } catch {
      sessionStorage.removeItem("quizflex_questions");
    }
  }

  const text = sessionStorage.getItem("quizflex_ocr_text");
  if (text) {
    form.description = text.slice(0, 500);
  }

  if (!questions.value.length) {
    addQuestion("single_choice");
  }
};

const syncCreatorProfile = (event) => {
  creatorProfile.value =
    event?.detail ??
    currentUserStorage.get() ?? { name: "Guest", email: "", avatar: "" };
};

onMounted(async () => {
  syncCreatorProfile();
  window.addEventListener("quizflex-user-updated", syncCreatorProfile);
  window.addEventListener("storage", syncCreatorProfile);

  await fetchFormTaxonomies();
  await fetchTopicsList();
  await loadQuizForEdit();
  loadOcrDraft();
});

onBeforeUnmount(() => {
  clearToast();
  revokeCoverPreview();
  window.removeEventListener("quizflex-user-updated", syncCreatorProfile);
  window.removeEventListener("storage", syncCreatorProfile);
});
</script>

<style scoped>
.math-block-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
  max-width: 100%;
  overflow-x: auto;
  padding: 0.25rem 0.35rem;
}

.editor-block {
  position: relative;
  display: inline-flex;
  flex: 0 1 auto;
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
  font-weight: 900;
  color: rgb(253, 164, 175);
  cursor: pointer;
}

math-field::part(menu-toggle) {
  display: none !important;
}

.field {
  width: 100%;
  border-radius: 1rem;
  border: 1px solid var(--border);
  background: var(--surface-soft);
  padding: 0.9rem 1rem;
  color: var(--text);
  font-size: 0.95rem;
  font-weight: 700;
  outline: none;
}

select.field {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23a4a1b5' stroke-width='2.5'><path stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/></svg>");
  background-repeat: no-repeat;
  background-position: right 1rem center;
  background-size: 1.1rem 1.1rem;
  padding-right: 2.6rem;
}

.field:focus {
  border-color: var(--border-strong);
}
</style>
