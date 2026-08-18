<template>
  <section class="mx-auto grid w-full max-w-[1500px] gap-6 2xl:grid-cols-[minmax(0,1fr)_360px]">
    <!-- Main Form -->
    <form
      class="relative min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8"
      @submit.prevent="saveQuiz"
    >
      <div class="relative z-10">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#7C3AED]">
          Manual editor
        </p>

        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
          {{ isEditMode ? "Sửa quiz" : "Tạo quiz thủ công" }}
        </h1>

        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
          Lưu quiz, câu hỏi, đáp án và ảnh bìa trực tiếp vào backend.
        </p>

        <!-- Basic info -->
        <div class="mt-8 grid gap-5">
          <div class="grid gap-2">
            <label class="text-sm font-semibold text-slate-900">
              Tiêu đề quiz *
            </label>
            <input
              id="quiz-title-input"
              v-model="form.title"
              class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
              placeholder="Ví dụ: Ôn tập Sinh học lớp 10"
              required
            />
          </div>

          <div class="grid gap-2">
            <label class="text-sm font-semibold text-slate-900">Mô tả</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
              placeholder="Mô tả ngắn cho người làm quiz"
            ></textarea>
          </div>

          <!-- Taxonomy -->
          <div class="grid gap-4 md:grid-cols-3">
            <div class="grid gap-2">
              <label class="text-sm font-semibold text-slate-900">Cấp học</label>
              <select
                v-model="form.education_level_id"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
                @change="onTaxonomyChange"
              >
                <option value="">-- Chọn cấp học --</option>
                <option v-for="level in taxonomyLevels" :key="level.id" :value="level.id">
                  {{ level.name }}
                </option>
              </select>
            </div>

            <div class="grid gap-2">
              <label class="text-sm font-semibold text-slate-900">Khối lớp</label>
              <select
                v-model="form.grade_id"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
                @change="onTaxonomyChange"
              >
                <option value="">-- Chọn khối lớp --</option>
                <option v-for="grade in formAvailableGrades" :key="grade.id" :value="grade.id">
                  {{ grade.name }}
                </option>
              </select>
            </div>

            <div class="grid gap-2">
              <label class="text-sm font-semibold text-slate-900">Bộ môn *</label>
              <select
                id="quiz-subject-select"
                v-model="form.subject_id"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
                required
                @change="onTaxonomyChange"
              >
                <option value="">-- Chọn bộ môn * --</option>
                <option v-for="subject in formAvailableSubjects" :key="subject.id" :value="subject.id">
                  {{ subject.name }}
                </option>
              </select>
            </div>
          </div>

          <!-- Topic -->
          <div class="grid gap-4 md:grid-cols-2">
            <div class="grid gap-2">
              <label class="text-sm font-semibold text-slate-900">Chủ đề có sẵn (Gợi ý)</label>
              <select
                v-model="selectedBankTopic"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
                @change="onBankTopicSelect"
              >
                <option value="">-- Chọn chủ đề từ kho câu hỏi --</option>
                <option v-for="top in topicsList" :key="top.topic_name" :value="top.topic_name">
                  {{ top.topic_name }} ({{ top.total_questions }} câu)
                </option>
              </select>
            </div>

            <div class="grid gap-2">
              <label class="text-sm font-semibold text-slate-900">Tên Chủ đề hiển thị</label>
              <input
                v-model="form.topic_name"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
                placeholder="VD: Hàm số bậc hai, Unit 3..."
                @input="onTopicInputChange"
              />
            </div>
          </div>

          <!-- Cover -->
          <section class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
            <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_280px]">
              <div class="p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#7C3AED]">
                      Ảnh bìa
                    </p>
                    <h2 class="mt-1 text-xl font-bold text-slate-900">Cover của quiz</h2>
                    <p class="mt-1 text-sm text-slate-500">
                      Chỉ upload file ảnh từ máy. Nếu chưa chọn ảnh, hệ thống dùng gradient dự phòng.
                    </p>
                  </div>
                  <button
                    type="button"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
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
                  <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-sm font-semibold text-slate-900">File ảnh bìa</p>
                    <p class="mt-1 text-xs text-slate-500">{{ selectedCoverLabel }}</p>
                    <button
                      type="button"
                      class="mt-3 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                      @click="openCoverPicker"
                    >
                      Upload ảnh
                    </button>
                  </div>

                  <div class="grid gap-2">
                    <p class="text-sm font-semibold text-slate-900">Avatar người tạo</p>
                    <div class="flex min-h-[72px] items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                      <UserAvatar
                        :user="creatorProfile"
                        size-class="h-12 w-12"
                        text-class="text-sm"
                      />
                      <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-900">
                          {{ creatorProfile.name || "Người tạo hiện tại" }}
                        </p>
                        <p class="truncate text-xs text-slate-500">Avatar lấy từ hồ sơ.</p>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-if="form.cover || form.coverFile" class="mt-4">
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-100"
                    @click="removeCover"
                  >
                    <Trash2 class="h-3.5 w-3.5" />
                    Xóa bìa
                  </button>
                </div>
              </div>

              <!-- Cover preview -->
              <div
                class="relative min-h-[230px] overflow-hidden border-t border-slate-200 lg:border-l lg:border-t-0"
                :style="{ background: coverBackground }"
              >
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                <div class="absolute bottom-4 left-4 right-4">
                  <UserAvatar
                    :user="creatorProfile"
                    size-class="h-12 w-12"
                    rounded-class="rounded-xl"
                    text-class="text-sm"
                    class="mb-3"
                  />
                  <p class="line-clamp-2 text-lg font-bold leading-6 text-white drop-shadow">
                    {{ form.title || "Quiz chưa đặt tên" }}
                  </p>
                  <p class="mt-1 text-xs font-medium text-white/80">
                    {{ form.category || "Danh mục" }} • {{ difficultyText }}
                  </p>
                </div>
              </div>
            </div>
          </section>

          <!-- Duration & Difficulty -->
          <div class="grid gap-4 md:grid-cols-2">
            <div class="grid gap-2">
              <label class="text-sm font-semibold text-slate-900">Thời gian (phút)</label>
              <input
                v-model.number="form.durationMinutes"
                type="number"
                min="1"
                max="1440"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
              />
            </div>

            <div class="grid gap-2">
              <label class="text-sm font-semibold text-slate-900">Độ khó</label>
              <select
                v-model="form.difficulty"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
              >
                <option value="easy">Dễ</option>
                <option value="medium">Vừa</option>
                <option value="hard">Khó</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Visibility -->
        <div class="mt-8">
          <div class="mb-4 flex items-center justify-between gap-4">
            <h2 class="text-xl font-bold text-slate-900">Visibility</h2>
            <VisibilityBadge :value="form.visibility" />
          </div>

          <div class="grid gap-3 md:grid-cols-3">
            <button
              v-for="option in visibilityOptions"
              :key="option.value"
              type="button"
              class="relative overflow-hidden rounded-xl border p-4 text-left transition"
              :class="form.visibility === option.value
                ? 'border-[#7C3AED] bg-[#F5F3FF] shadow-sm'
                : 'border-slate-200 bg-white hover:border-slate-300'"
              @click="form.visibility = option.value"
            >
              <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-white text-xs font-bold text-[#7C3AED] border border-slate-200">
                {{ option.short }}
              </div>
              <b class="block text-slate-900">{{ option.title }}</b>
              <p class="mt-1.5 text-xs leading-5 text-slate-500">
                {{ option.description }}
              </p>
            </button>
          </div>

          <div v-if="form.visibility === 'group'" class="mt-4 grid gap-2">
            <label class="text-sm font-semibold text-slate-900">Room gắn với quiz</label>
            <input
              v-model="form.roomCode"
              class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
              placeholder="VD: QZ24"
            />
          </div>
        </div>

        <!-- Question Builder -->
        <div class="mt-8 grid gap-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p class="text-xs font-semibold uppercase tracking-wider text-[#7C3AED]">
                Question builder
              </p>
              <h2 class="mt-1 text-xl font-bold text-slate-900">Câu hỏi và đáp án</h2>
              <p class="mt-1 text-sm text-slate-500">
                Phần này đã thay bằng editor của OCR/math.
              </p>
            </div>

            <div class="flex flex-wrap gap-2">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg bg-[#7C3AED] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#6D28D9]"
                @click="openImportModal"
              >
                <Library class="h-4 w-4" />
                Chọn từ Kho & Ngân hàng
              </button>

              <button
                type="button"
                class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="addQuestion('single_choice')"
              >
                + Một đáp án
              </button>
              <button
                type="button"
                class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="addQuestion('multi_choice')"
              >
                + Nhiều đáp án
              </button>
              <button
                type="button"
                class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="addQuestion('fill_blank')"
              >
                + Điền đáp án
              </button>
            </div>
          </div>

          <!-- Question cards -->
          <div
            v-for="(q, index) in questions"
            :key="q.id"
            :ref="(el) => setQuestionCardRef(el, index)"
            class="min-w-0 rounded-2xl border border-slate-200 bg-slate-50 p-5 sm:p-6 transition hover:border-slate-300"
          >
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-semibold text-[#7C3AED]">Câu {{ index + 1 }}</p>
                <select
                  v-model="q.type"
                  class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 outline-none"
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
                  class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                  @click="openQuestionImagePicker(index)"
                >
                  + Ảnh
                </button>
                <button
                  type="button"
                  class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-medium text-rose-700 hover:bg-rose-100"
                  @click="removeQuestion(index)"
                >
                  Xóa
                </button>
              </div>
            </div>

            <!-- Editor mode toggle -->
            <div v-if="q.allow_math" class="mt-3 flex flex-wrap items-center gap-2">
              <span class="text-xs font-medium text-slate-500">Chế độ sửa</span>
              <button
                type="button"
                class="rounded-lg px-3 py-1 text-xs font-medium transition"
                :class="q.editor_mode === 'normal'
                  ? 'bg-[#7C3AED] text-white'
                  : 'bg-white text-slate-600 border border-slate-200'"
                @click="setQuestionMode(q, 'normal')"
              >
                Thường
              </button>
              <button
                type="button"
                class="rounded-lg px-3 py-1 text-xs font-medium transition"
                :class="q.editor_mode === 'math'
                  ? 'bg-[#7C3AED] text-white'
                  : 'bg-white text-slate-600 border border-slate-200'"
                @click="setQuestionMode(q, 'math')"
              >
                Math
              </button>
            </div>
            <div v-else class="mt-3 inline-flex rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-500">
              Chế độ mặc định
            </div>

            <!-- Question content -->
            <textarea
              v-if="q.editor_mode === 'normal'"
              v-model="q.question"
              rows="3"
              class="mt-3 w-full resize-none rounded-xl border border-slate-200 bg-white p-4 text-sm font-medium leading-6 text-slate-900 outline-none focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20"
              :style="getNormalTextareaStyle(q.question)"
              placeholder="Nhập nội dung câu hỏi..."
            ></textarea>

            <div v-else class="mt-3 rounded-xl border border-slate-200 bg-white p-4">
              <draggable
                v-model="q.question_blocks"
                item-key="id"
                class="math-block-row"
                v-bind="dragOptions"
              >
                <template #item="{ element: block, index: blockIndex }">
                  <div class="editor-block group">
                    <button type="button" class="drag-handle">
                      <GripVertical class="h-4 w-4" />
                    </button>

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
                      <X class="h-3.5 w-3.5" />
                    </button>
                  </div>
                </template>
              </draggable>

              <div class="mt-3 flex flex-wrap gap-2">
                <button
                  type="button"
                  class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                  @click="addTextBlock(q.question_blocks)"
                >
                  + Chữ
                </button>
                <button
                  type="button"
                  class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                  @click="addMathBlock(q.question_blocks)"
                >
                  + Công thức
                </button>
              </div>
            </div>

            <!-- Question images -->
            <div v-if="q.images?.length" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
              <div
                v-for="(image, imageIndex) in q.images"
                :key="image.id"
                class="overflow-hidden rounded-xl border border-slate-200 bg-white p-2"
              >
                <img :src="image.preview" :alt="image.name" class="h-40 w-full rounded-lg object-contain" />
                <div class="mt-2 flex items-center justify-between gap-2">
                  <p class="truncate text-xs text-slate-500">{{ image.name }}</p>
                  <button
                    type="button"
                    class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700 hover:bg-rose-100"
                    @click="removeQuestionImage(q, imageIndex)"
                  >
                    Xóa ảnh
                  </button>
                </div>
              </div>
            </div>

            <!-- Options (single / multi) -->
            <div v-if="q.type !== 'fill_blank'" class="mt-4 grid gap-3 md:grid-cols-2">
              <div v-for="(optionText, optionKey) in q.options" :key="optionKey">
                <label class="text-xs font-medium uppercase text-slate-500">
                  Đáp án {{ optionKey }}
                </label>

                <textarea
                  v-if="q.editor_mode === 'normal'"
                  v-model="q.options[optionKey]"
                  rows="1"
                  class="mt-2 w-full resize-none rounded-xl border border-slate-200 bg-white p-3 text-sm font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20"
                  :style="getNormalTextareaStyle(q.options[optionKey])"
                  placeholder="Nhập đáp án..."
                ></textarea>

                <div v-else class="mt-2 rounded-xl border border-slate-200 bg-white p-3">
                  <draggable
                    v-model="q.option_blocks[optionKey]"
                    item-key="id"
                    class="math-block-row"
                    v-bind="dragOptions"
                  >
                    <template #item="{ element: block, index: blockIndex }">
                      <div class="editor-block group">
                        <button type="button" class="drag-handle">
                          <GripVertical class="h-4 w-4" />
                        </button>
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
                          <X class="h-3.5 w-3.5" />
                        </button>
                      </div>
                    </template>
                  </draggable>
                  <div class="mt-2 flex flex-wrap gap-2">
                    <button type="button" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50" @click="addTextBlock(q.option_blocks[optionKey])">+ Chữ</button>
                    <button type="button" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50" @click="addMathBlock(q.option_blocks[optionKey])">+ Math</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Correct answer selectors -->
            <div v-if="q.type === 'single_choice'" class="mt-4">
              <label class="text-xs font-medium uppercase text-slate-500">Đáp án đúng</label>
              <select
                v-model="q.correct_answer"
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white p-3 text-sm font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20"
              >
                <option :value="null">Chưa chọn</option>
                <option v-for="(_, optionKey) in q.options" :key="optionKey" :value="optionKey">
                  {{ optionKey }}
                </option>
              </select>
            </div>

            <div v-else-if="q.type === 'multi_choice'" class="mt-4">
              <label class="text-xs font-medium uppercase text-slate-500">Đáp án đúng nhiều lựa chọn</label>
              <div class="mt-2 flex flex-wrap gap-2">
                <label
                  v-for="(_, optionKey) in q.options"
                  :key="optionKey"
                  class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700"
                >
                  <input type="checkbox" :value="optionKey" v-model="q.correct_answer" class="rounded border-slate-300 text-[#7C3AED] focus:ring-[#7C3AED]" />
                  {{ optionKey }}
                </label>
              </div>
            </div>

            <div v-else class="mt-4">
              <label class="text-xs font-medium uppercase text-slate-500">Các đáp án điền đúng</label>
              <div class="mt-2 grid gap-2">
                <div v-for="(answer, answerIndex) in q.correct_answer" :key="answerIndex" class="flex gap-2">
                  <textarea
                    v-model="q.correct_answer[answerIndex]"
                    rows="1"
                    class="w-full resize-none rounded-xl border border-slate-200 bg-white p-3 text-sm font-medium text-slate-900 outline-none focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/20"
                    :style="getNormalTextareaStyle(q.correct_answer[answerIndex])"
                    placeholder="Nhập một đáp án đúng..."
                  ></textarea>
                  <button
                    type="button"
                    class="rounded-lg border border-rose-200 bg-rose-50 px-3 text-xs font-medium text-rose-700 hover:bg-rose-100"
                    @click="removeFillAnswer(q, answerIndex)"
                  >
                    Xóa
                  </button>
                </div>
              </div>
              <button
                type="button"
                class="mt-3 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                @click="addFillAnswer(q)"
              >
                + Thêm đáp án đúng
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

    <!-- Floating actions -->
    <div class="fixed bottom-4 right-4 z-[60] flex flex-col gap-2 rounded-xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur-sm">
      <button
        type="button"
        class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        @click="addQuestion('single_choice')"
      >
        Thêm câu hỏi
      </button>
      <button
        type="button"
        class="rounded-lg bg-[#7C3AED] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#6D28D9] disabled:opacity-60"
        :disabled="isSaving"
        @click="saveQuiz"
      >
        {{ isSaving ? "Đang lưu..." : "Lưu quiz" }}
      </button>
    </div>

    <!-- Toast -->
    <div
      v-if="toastMessage"
      class="fixed inset-0 z-[80] flex items-center justify-center bg-black/40 px-4"
      @click="clearToast"
    >
      <div
        class="w-full max-w-md rounded-2xl border bg-white px-6 py-6 text-center shadow-xl"
        :class="toastType === 'success' ? 'border-emerald-200' : 'border-rose-200'"
        @click.stop
      >
        <div
          class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full text-xl font-bold"
          :class="toastType === 'success' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'"
        >
          {{ toastType === 'success' ? '✓' : '!' }}
        </div>
        <h3 class="text-lg font-semibold text-slate-900">
          {{ toastType === 'success' ? 'Thông báo' : 'Có lỗi xảy ra' }}
        </h3>
        <p class="mt-2 text-sm text-slate-500">{{ toastMessage }}</p>
      </div>
    </div>

    <!-- Sidebar -->
    <aside class="grid content-start gap-5">
      <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="relative h-52" :style="{ background: coverBackground }">
          <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
          <div class="absolute bottom-5 right-5">
            <UserAvatar
              :user="creatorProfile"
              size-class="h-14 w-14"
              rounded-class="rounded-xl"
              text-class="text-sm"
            />
          </div>
        </div>
        <div class="p-6">
          <p class="text-xs font-semibold uppercase tracking-wider text-[#7C3AED]">Live preview</p>
          <h3 class="mt-2 text-xl font-bold text-slate-900">
            {{ form.title || "Quiz chưa đặt tên" }}
          </h3>
          <p class="mt-2 text-sm leading-6 text-slate-500">
            {{ form.description || "Mô tả sẽ hiển thị ở đây." }}
          </p>
          <div class="mt-4 flex flex-wrap items-center gap-2">
            <span v-if="selectedSubjectName" class="rounded-md bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
              Môn {{ selectedSubjectName }}
            </span>
            <span v-if="form.topic_name" class="rounded-md bg-[#F5F3FF] px-2.5 py-0.5 text-xs font-medium text-[#7C3AED]">
              Chủ đề: {{ form.topic_name }}
            </span>
            <VisibilityBadge :value="form.visibility" />
            <span class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-xs font-medium text-slate-600">
              {{ difficultyText }}
            </span>
            <span class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-xs font-medium text-slate-600">
              {{ questions.length }} câu
            </span>
          </div>
        </div>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#7C3AED]">Kiểm tra nhanh</p>
        <div class="mt-4 grid gap-2.5">
          <div
            v-for="item in checklist"
            :key="item"
            class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-600"
          >
            {{ item }}
          </div>
        </div>
      </article>
    </aside>

    <!-- Import Modal (cleaned) -->
    <div
      v-if="isImportModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 sm:p-6"
      @click.self="isImportModalOpen = false"
    >
      <div class="relative flex max-h-[88vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
        <!-- Header -->
        <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
          <div>
            <h3 class="text-lg font-semibold text-slate-900">Chọn câu hỏi</h3>
            <p class="mt-0.5 text-sm text-slate-500">Chọn câu hỏi từ ngân hàng để thêm vào Quiz</p>
          </div>
          <button
            type="button"
            class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600"
            @click="isImportModalOpen = false"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <!-- Search -->
        <div class="border-b border-slate-100 px-6 py-4">
          <div class="relative">
            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              v-model="importFilters.search"
              type="text"
              class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm focus:border-[#7C3AED] focus:outline-none focus:ring-2 focus:ring-[#7C3AED]/20"
              placeholder="Tìm kiếm câu hỏi..."
              @keyup.enter="loadImportBank(1)"
            />
          </div>
        </div>

        <!-- Filters -->
        <div class="space-y-2.5 border-b border-slate-100 px-6 py-4">
          <div class="grid grid-cols-3 gap-2.5">
            <select v-model="importFilters.education_level_id" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-[#7C3AED] focus:outline-none" @change="onModalTaxonomyChange">
              <option value="">-- Cấp học --</option>
              <option v-for="level in taxonomyLevels" :key="level.id" :value="level.id">{{ level.name }}</option>
            </select>
            <select v-model="importFilters.grade_id" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-[#7C3AED] focus:outline-none" @change="onModalTaxonomyChange">
              <option value="">-- Khối lớp --</option>
              <option v-for="grade in modalAvailableGrades" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
            </select>
            <select v-model="importFilters.subject_id" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-[#7C3AED] focus:outline-none" @change="onModalTaxonomyChange">
              <option value="">-- Môn học --</option>
              <option v-for="subject in modalAvailableSubjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-2.5">
            <select v-model="importFilters.topic_name" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-[#7C3AED] focus:outline-none" @change="loadImportBank(1)">
              <option value="">-- Chủ đề --</option>
              <option v-for="top in modalTopicsList" :key="top.topic_name" :value="top.topic_name">{{ top.topic_name }} ({{ top.total_questions }})</option>
            </select>
            <select v-model="importFilters.difficulty" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-[#7C3AED] focus:outline-none" @change="loadImportBank(1)">
              <option value="">-- Độ khó --</option>
              <option value="easy">Dễ</option>
              <option value="medium">Trung bình</option>
              <option value="hard">Khó</option>
            </select>
          </div>
        </div>

        <!-- Sub header -->
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-3 text-sm text-slate-500">
          <span>
            Tổng cộng <strong class="text-slate-900">{{ totalImportBankItems }}</strong> câu hỏi
            <span v-if="lastImportBankPage > 1" class="ml-1 text-xs">(Trang {{ currentImportBankPage }}/{{ lastImportBankPage }})</span>
          </span>
          <button
            v-if="importBankItems.length > 0"
            type="button"
            class="text-sm font-medium text-[#7C3AED] hover:underline"
            @click="toggleSelectAllImportBank"
          >
            {{ isAllImportSelected ? 'Bỏ chọn trang này' : 'Chọn tất cả trang này' }}
          </button>
        </div>

        <!-- List -->
        <div class="flex-1 space-y-2.5 overflow-y-auto p-6 min-h-[220px] max-h-[46vh]">
          <div v-if="isLoadingImportBank" class="flex flex-col items-center justify-center gap-2 py-16 text-sm text-slate-500">
            <div class="h-6 w-6 animate-spin rounded-full border-2 border-[#7C3AED] border-t-transparent"></div>
            Đang tải danh sách câu hỏi...
          </div>
          <div v-else-if="importBankItems.length === 0" class="py-16 text-center text-sm text-slate-500">
            Không tìm thấy câu hỏi phù hợp.
          </div>
          <template v-else>
            <div
              v-for="item in importBankItems"
              :key="item.id"
              class="group flex cursor-pointer items-start gap-4 rounded-xl border p-4 transition"
              :class="selectedImportIds.includes(item.id)
                ? 'border-[#7C3AED] bg-[#F5F3FF]'
                : 'border-slate-200 bg-white hover:border-slate-300'"
              @click="toggleImportSelect(item.id)"
            >
              <div
                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-md border transition"
                :class="selectedImportIds.includes(item.id)
                  ? 'border-[#7C3AED] bg-[#7C3AED] text-white'
                  : 'border-slate-300 bg-white'"
              >
                <Check v-if="selectedImportIds.includes(item.id)" class="h-3.5 w-3.5" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="mb-1 flex items-center gap-2">
                  <span class="font-mono text-xs font-semibold text-[#7C3AED]">#{{ item.id }}</span>
                </div>
                <p class="line-clamp-2 text-sm font-medium text-slate-900">
                  {{ item.content || item.text }}
                </p>
                <p class="mt-1 truncate text-xs text-slate-500">
                  {{ getQuestionMetaText(item) }}
                </p>
              </div>
            </div>

            <div v-if="lastImportBankPage > 1" class="mt-3 flex items-center justify-between border-t border-slate-100 pt-4 text-sm">
              <button
                type="button"
                class="rounded-lg border border-slate-200 px-3 py-1.5 font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40"
                :disabled="currentImportBankPage <= 1"
                @click="loadImportBank(currentImportBankPage - 1)"
              >
                ← Trang trước
              </button>
              <span class="font-medium text-slate-900">
                Trang {{ currentImportBankPage }} / {{ lastImportBankPage }}
              </span>
              <button
                type="button"
                class="rounded-lg border border-slate-200 px-3 py-1.5 font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40"
                :disabled="currentImportBankPage >= lastImportBankPage"
                @click="loadImportBank(currentImportBankPage + 1)"
              >
                Trang sau →
              </button>
            </div>
          </template>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between border-t border-slate-100 px-6 py-4">
          <span class="text-sm text-slate-500">
            Đã chọn <strong class="text-slate-900">{{ selectedImportIds.length }}</strong> câu
          </span>
          <div class="flex items-center gap-3">
            <button
              type="button"
              class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
              @click="isImportModalOpen = false"
            >
              Hủy
            </button>
            <button
              type="button"
              class="rounded-lg bg-[#7C3AED] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#6D28D9] disabled:opacity-40"
              :disabled="selectedImportIds.length === 0"
              @click="confirmImportQuestions"
            >
              Thêm {{ selectedImportIds.length }} câu
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
// ... giữ nguyên toàn bộ logic script từ file gốc (không thay đổi business logic)
// Chỉ cần import thêm Lucide icons:

import {
  Trash2,
  Library,
  Search,
  X,
  Check,
  GripVertical
} from 'lucide-vue-next'

// (phần còn lại của <script setup> giữ nguyên như file bạn gửi)
</script>

<style scoped>
/* Giữ nguyên phần style math-block, editor-block... từ file gốc */
/* Chỉ cần đảm bảo các class không dùng blur / glassmorphism nặng */
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
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem 0 0 0.5rem;
  background: #f8fafc;
  color: #64748b;
  cursor: grab;
}
.editor-text-input,
.editor-math-field {
  min-width: 120px;
  max-width: min(100%, 900px);
  border-radius: 0 0.5rem 0.5rem 0;
  border: 1px solid #e2e8f0;
  border-left: 0;
  padding: 0.85rem 2.7rem 0.85rem 0.95rem;
  font-size: 0.95rem;
  font-weight: 600;
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
  color: #0f172a;
  background: #f8fafc;
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
  background: rgba(244, 63, 94, 0.12);
  color: #e11d48;
  cursor: pointer;
}
math-field::part(menu-toggle) {
  display: none !important;
}
</style>