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
import { Trash2, Library, Search, X, Check, GripVertical } from "lucide-vue-next";
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
  formatApiErrorMessage,
  normalizeQuestion as normalizeApiQuestion,
  questionsBankApi,
  quizzesApi,
  taxonomyApi,
} from "@/services/api";

const route = useRoute();
const router = useRouter();

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
  visibility: route.query?.visibility === "group" ? "group" : "public",
  roomCode:
    route.query?.visibility === "group"
      ? `GR${Math.floor(1000 + Math.random() * 9000)}`
      : "",
});

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

const focusAndHighlightElement = (targetId, questionIndex = null) => {
  let el = null;
  if (questionIndex !== null && questionCardRefs.value && questionCardRefs.value[questionIndex]) {
    el = questionCardRefs.value[questionIndex];
  } else if (targetId) {
    el = document.getElementById(targetId);
  }

  if (el) {
    el.scrollIntoView({ behavior: "smooth", block: "center" });
    if (typeof el.focus === "function") {
      el.focus();
    }
    el.classList.add("ring-4", "ring-rose-500/60", "border-rose-500");
    setTimeout(() => {
      el.classList.remove("ring-4", "ring-rose-500/60", "border-rose-500");
    }, 2500);
  }
};

const validateBeforeSave = () => {
  if (!form.title.trim()) {
    return { message: "Bạn chưa nhập tiêu đề quiz.", targetId: "quiz-title-input" };
  }
  if (!form.subject_id) {
    return { message: "Vui lòng chọn Bộ môn cho bài quiz.", targetId: "quiz-subject-select" };
  }

  const duration = Number(form.durationMinutes);
  if (!Number.isFinite(duration) || duration < 1 || duration > 1440) {
    return { message: "Thời gian làm bài phải từ 1 đến 1440 phút.", targetId: "quiz-duration-input" };
  }

  if (form.visibility === "group" && !form.roomCode.trim()) {
    return { message: "Quiz dạng group cần có room code.", targetId: "quiz-room-code-input" };
  }

  if (questions.value.length === 0) {
    return { message: "Quiz cần ít nhất 1 câu hỏi." };
  }

  for (const [index, q] of questions.value.entries()) {
    const questionText =
      q.editor_mode === "math" ? blocksToString(q.question_blocks) : q.question;

    if (!questionText.trim()) {
      return { message: `Câu ${index + 1} chưa có nội dung.`, questionIndex: index };
    }

    if (q.type !== "fill_blank") {
      const options = {};

      Object.keys(q.options || {}).forEach((key) => {
        options[key] =
          q.editor_mode === "math"
            ? blocksToString(q.option_blocks[key])
            : q.options[key];
      });

      if (Object.values(options).some((value) => !String(value).trim())) {
        return { message: `Câu ${index + 1} còn đáp án trống.`, questionIndex: index };
      }

      if (q.type === "single_choice" && !q.correct_answer) {
        return { message: `Câu ${index + 1} chưa chọn đáp án đúng.`, questionIndex: index };
      }

      if (q.type === "multi_choice" && !q.correct_answer.length) {
        return { message: `Câu ${index + 1} chưa chọn đáp án đúng.`, questionIndex: index };
      }
    }

    if (q.type === "fill_blank") {
      if (!q.correct_answer.some((answer) => String(answer).trim())) {
        return { message: `Câu ${index + 1} chưa có đáp án điền đúng.`, questionIndex: index };
      }
    }
  }

  return null;
};

const saveQuiz = async () => {
  const errorInfo = validateBeforeSave();
  successMessage.value = "";

  if (errorInfo) {
    errorMessage.value = errorInfo.message;
    showToast(errorInfo.message, "error");
    focusAndHighlightElement(errorInfo.targetId, errorInfo.questionIndex);
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
    errorMessage.value = formatApiErrorMessage(error, "Lưu thất bại. Vui lòng kiểm tra lại thông tin.");
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