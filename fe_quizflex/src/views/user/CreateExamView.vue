<template>
  <section
    class="mx-auto grid w-full max-w-[1400px] gap-6 min-w-0 xl:grid-cols-[minmax(0,1fr)_380px]"
  >
    <!-- MAIN FORM COLUMN -->
    <form
      class="relative min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 grid gap-6"
      @submit.prevent="handleCreateQuizSubmit"
    >
      <div class="relative z-10 grid gap-6">
        <!-- Top Navigation & Title Header -->
        <div>
          <button
            type="button"
            class="mb-3 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-purple-700 cursor-pointer"
            @click="goBack"
          >
            <ArrowLeft class="h-4 w-4" />
            <span>Quay lại</span>
          </button>

          <div
            class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
          >
            <div class="flex items-start gap-3">
              <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 ring-1 ring-purple-100 shadow-xs">
                <FileQuestion class="h-5 w-5" />
              </div>
              <div>
                <h1
                  class="flex items-center gap-2.5 text-3xl font-black tracking-[-0.04em] text-slate-900"
                >
                  <span>Tạo Quiz</span>
                  <span class="inline-block h-2 w-2 rounded-full bg-purple-600"></span>
                </h1>
                <p class="mt-0.5 text-xs text-slate-500 font-medium">
                  Soạn thảo đề thi mới, phân bổ độ khó hoặc đóng gói câu hỏi từ kho lưu trữ
                </p>
              </div>
            </div>

            <!-- Mode Selector Switcher -->
            <div
              class="inline-flex rounded-xl border border-slate-200 bg-slate-100/70 p-1 shrink-0 self-start sm:self-auto"
            >
              <button
                type="button"
                class="flex items-center gap-2 rounded-lg px-4 py-2 text-xs transition duration-150 cursor-pointer"
                :class="
                  mode === 'manual'
                    ? 'bg-[#7C3AED] text-white font-bold shadow-sm shadow-[#7C3AED]/25'
                    : 'text-slate-600 font-medium hover:text-slate-900 hover:bg-white/60'
                "
                @click="switchMode('manual')"
              >
                <Target :size="14" />
                <span>Chọn thủ công ({{ selectedIds.length }})</span>
              </button>
              <button
                type="button"
                class="flex items-center gap-2 rounded-lg px-4 py-2 text-xs transition duration-150 cursor-pointer"
                :class="
                  mode === 'random'
                    ? 'bg-[#7C3AED] text-white font-bold shadow-sm shadow-[#7C3AED]/25'
                    : 'text-slate-600 font-medium hover:text-slate-900 hover:bg-white/60'
                "
                @click="switchMode('random')"
              >
                <Sparkles :size="14" />
                <span>Tự động phân bổ</span>
              </button>
            </div>
          </div>

          <!-- Mode Scope Notice Alert -->
          <div
            class="mt-4 rounded-xl border p-3.5 flex items-start gap-3 text-xs leading-relaxed transition-all duration-200"
            :class="
              mode === 'manual'
                ? 'border-emerald-200/90 bg-emerald-50/70 text-emerald-950'
                : 'border-sky-200/90 bg-sky-50/70 text-sky-950'
            "
          >
            <div
              class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg mt-0.5"
              :class="mode === 'manual' ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700'"
            >
              <component
                :is="mode === 'manual' ? Target : Info"
                :size="15"
              />
            </div>
            <div class="space-y-0.5">
              <span class="font-bold text-slate-900">
                {{
                  mode === "manual"
                    ? "Chế độ Chọn thủ công:"
                    : "Chế độ Tự động phân bổ độ khó:"
                }}
              </span>
              <p class="text-slate-600">
                <template v-if="mode === 'manual'">
                  Bạn có thể chọn và kết hợp linh hoạt các câu hỏi từ
                  <strong class="text-slate-800">Kho câu hỏi cá nhân</strong> và
                  <strong class="text-slate-800">Ngân hàng câu hỏi công khai</strong>.
                </template>
                <template v-else>
                  Chế độ tự động chỉ sử dụng câu hỏi từ Ngân hàng câu hỏi đã
                  được kiểm duyệt. Muốn sử dụng câu hỏi riêng của bạn, hãy chọn
                  chế độ <strong class="text-slate-800">Chọn thủ công</strong>.
                </template>
              </p>
            </div>
          </div>
        </div>

        <!-- 1. THÔNG TIN QUIZ & PHÂN LOẠI KIẾN THỨC -->
        <div class="grid gap-4 pt-2">
          <label
            for="exam-title-input"
            class="flex items-center gap-2 text-base font-semibold text-slate-900"
          >
            <span class="h-4 w-1 rounded-full bg-indigo-500"></span>
            <span>1. Thông tin Quiz & Phân loại kiến thức</span>
          </label>

          <div class="grid gap-4">
            <div class="grid md:grid-cols-2 gap-4">
              <label class="grid gap-1.5 text-xs font-medium text-slate-700">
                <span>Tên Quiz / Đề thi <span class="text-purple-600 font-bold">*</span></span>
                <input
                  id="exam-title-input"
                  v-model="quizForm.title"
                  required
                  class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/10 shadow-2xs"
                  placeholder="VD: Kiểm tra 1 tiết Toán 10 - Ôn tập Hàm Số"
                />
              </label>

              <label class="grid gap-1.5 text-xs font-medium text-slate-700">
                Chủ đề hiển thị
                <input
                  v-model="quizForm.topic_name"
                  class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/10 shadow-2xs"
                  placeholder="VD: Đại số & Hàm số, Ôn tập chương 1..."
                  @input="onTopicInputChange"
                />
              </label>
            </div>

            <label class="grid gap-1.5 text-xs font-medium text-slate-700">
              Mô tả ngắn
              <textarea
                v-model="quizForm.description"
                rows="3"
                class="w-full resize-none rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium leading-relaxed text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/10 shadow-2xs"
                placeholder="Nhập ghi chú hoặc hướng dẫn làm bài..."
              ></textarea>
            </label>

            <!-- Taxonomy Sub-card -->
            <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-4 grid gap-3">
              <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700">
                <GraduationCap :size="15" class="text-purple-600" />
                <span>Phân loại & Bộ môn</span>
              </div>

              <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <label class="grid gap-1.5 text-xs font-medium text-slate-600">
                  Cấp học
                  <select
                    v-model="filters.education_level_id"
                    class="w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-900 outline-none transition hover:border-slate-300 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/10 shadow-2xs"
                    @change="onLevelChange"
                  >
                    <option value="">Tất cả cấp học</option>
                    <option
                      v-for="level in taxonomyLevels"
                      :key="level.id"
                      :value="level.id"
                    >
                      {{ level.name }}
                    </option>
                  </select>
                </label>

                <label class="grid gap-1.5 text-xs font-medium text-slate-600">
                  Khối lớp
                  <select
                    v-model="filters.grade_id"
                    class="w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-900 outline-none transition hover:border-slate-300 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/10 shadow-2xs"
                    @change="onGradeSubjectChange"
                  >
                    <option value="">Tất cả khối lớp</option>
                    <option
                      v-for="grade in availableGrades"
                      :key="grade.id"
                      :value="grade.id"
                    >
                      {{ grade.name }}
                    </option>
                  </select>
                </label>

                <label class="grid gap-1.5 text-xs font-medium text-slate-600">
                  <span>Bộ môn <span class="text-purple-600 font-bold">*</span></span>
                  <select
                    v-model="filters.subject_id"
                    class="w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-900 outline-none transition hover:border-slate-300 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/10 shadow-2xs"
                    @change="onGradeSubjectChange"
                  >
                    <option value="">Chọn bộ môn</option>
                    <option
                      v-for="subject in availableSubjects"
                      :key="subject.id"
                      :value="subject.id"
                    >
                      {{ subject.name }}
                    </option>
                  </select>
                </label>

                <label class="grid gap-1.5 text-xs font-medium text-slate-600">
                  Chủ đề từ Ngân hàng
                  <select
                    v-model="filters.topic_name"
                    class="w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-900 outline-none transition hover:border-slate-300 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/10 shadow-2xs"
                    @change="onTopicSelectChange"
                  >
                    <option value="">Tất cả chủ đề trong kho</option>
                    <option
                      v-for="top in topicsList"
                      :key="top.topic_name"
                      :value="top.topic_name"
                    >
                      {{ top.topic_name }} ({{ top.total_questions }} câu)
                    </option>
                  </select>
                </label>
              </div>
            </div>

            <!-- Ảnh bìa bộ đề (Cover Image Upload) -->
            <div class="grid gap-2 pt-1">
              <label
                class="text-xs font-medium text-slate-700 flex items-center justify-between"
              >
                <span class="flex items-center gap-1.5">
                  <ImageIcon :size="14" class="text-purple-600" />
                  <span class="font-semibold text-slate-800">Ảnh bìa Quiz</span>
                  <span class="text-slate-400 font-normal text-[11px]"
                    >(Tùy chọn)</span
                  >
                </span>
                <span
                  v-if="formCoverFile || quizForm.cover"
                  class="inline-flex items-center gap-1 text-emerald-600 text-[11px] font-semibold"
                >
                  <Check :size="12" />
                  <span>Đã chọn ảnh</span>
                </span>
              </label>

              <div
                class="rounded-xl border border-slate-200/90 bg-slate-50/70 p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 transition hover:border-purple-200"
              >
                <div class="flex items-center gap-3">
                  <div
                    class="h-16 w-24 rounded-xl overflow-hidden border border-slate-200 shrink-0 shadow-2xs transition-all duration-300"
                    :style="{ background: coverBackground }"
                  ></div>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-900">
                      Ảnh bìa hiển thị
                    </p>
                    <p
                      class="text-[11px] text-slate-500 mt-0.5 truncate max-w-[240px]"
                    >
                      {{ selectedCoverLabel }}
                    </p>
                  </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                  <input
                    ref="coverInput"
                    type="file"
                    accept="image/png,image/jpeg,image/jpg,image/webp,image/gif"
                    class="hidden"
                    @change="handleCoverFileChange"
                  />
                  <button
                    type="button"
                    class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition shadow-2xs cursor-pointer flex items-center gap-1.5"
                    @click="openCoverPicker"
                  >
                    <Camera :size="13" />
                    <span>{{
                      formCoverFile || quizForm.cover
                        ? "Đổi ảnh"
                        : "Tải ảnh lên"
                    }}</span>
                  </button>
                  <button
                    v-if="formCoverFile || quizForm.cover"
                    type="button"
                    class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-100 transition shadow-2xs cursor-pointer flex items-center gap-1"
                    @click="removeCover"
                  >
                    <X :size="13" />
                    <span>Xóa ảnh</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. CẤU HÌNH QUIZ & PHẠM VI HIỂN THỊ -->
        <div class="grid gap-4 pt-5 border-t border-slate-200">
          <h2
            class="flex items-center gap-2 text-base font-semibold text-slate-900"
          >
            <span class="h-4 w-1 rounded-full bg-indigo-500"></span>
            <span>2. Cấu hình Quiz</span>
          </h2>

          <!-- Chế độ THỦ CÔNG: Thông báo rõ ràng là mặc định Riêng tư, cần gửi duyệt để công khai -->
          <div
            v-if="mode === 'manual'"
            class="rounded-xl border border-amber-200 bg-amber-50/70 p-4 flex items-start gap-3.5 shadow-2xs"
          >
            <div
              class="h-9 w-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0"
            >
              <Lock :size="18" />
            </div>
            <div class="grid gap-1">
              <span
                class="font-bold text-sm text-slate-900 flex items-center gap-2"
              >
                <span>Chế độ hiển thị: Riêng tư</span>
                <span
                  class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200"
                  >Mặc định</span
                >
              </span>
              <p class="text-xs leading-relaxed text-slate-600">
                Quiz thủ công có thể sử dụng câu hỏi từ Kho câu hỏi của tôi và
                Ngân hàng câu hỏi. Vì câu hỏi trong Kho của tôi chưa được kiểm
                duyệt, Quiz sẽ được tạo ở chế độ riêng tư. Bạn có thể gửi yêu
                cầu công khai sau khi tạo bài Quiz.
              </p>
            </div>
          </div>

          <!-- Chế độ TỰ ĐỘNG: Cho phép chọn Riêng tư hoặc Công khai -->
          <div v-else class="grid gap-3">
            <div
              class="rounded-xl border border-purple-100 bg-purple-50/50 p-3 text-xs text-purple-900 flex items-center gap-2 shadow-2xs"
            >
              <Sparkles :size="15" class="text-purple-600 shrink-0" />
              <span
                >Quiz tự động chỉ sử dụng câu hỏi đã được kiểm duyệt trong Ngân
                hàng câu hỏi.</span
              >
            </div>
            <div class="grid md:grid-cols-2 gap-4">
              <label
                class="flex items-start gap-3 rounded-xl border p-4 cursor-pointer transition duration-150"
                :class="
                  quizForm.visibility === 'private'
                    ? 'border-amber-300 bg-amber-50/80 text-slate-900 shadow-xs ring-1 ring-amber-200/70'
                    : 'border-slate-200 bg-slate-50/50 text-slate-600 hover:border-slate-300 hover:bg-slate-50'
                "
              >
                <input
                  type="radio"
                  v-model="quizForm.visibility"
                  value="private"
                  class="mt-1 accent-amber-500 h-4 w-4 cursor-pointer"
                />
                <div class="grid gap-1">
                  <span
                    class="font-bold text-sm text-slate-900 flex items-center gap-1.5"
                  >
                    <div class="flex h-5 w-5 items-center justify-center rounded bg-amber-100 text-amber-700">
                      <Lock :size="12" />
                    </div>
                    <span>Riêng tư</span>
                  </span>
                  <span class="text-xs leading-relaxed text-slate-500"
                    >Chỉ lưu vào kho cá nhân của bạn.</span
                  >
                </div>
              </label>

              <label
                class="flex items-start gap-3 rounded-xl border p-4 cursor-pointer transition duration-150"
                :class="
                  quizForm.visibility === 'public'
                    ? 'border-purple-300 bg-purple-50/80 text-slate-900 shadow-xs ring-1 ring-purple-200/70'
                    : 'border-slate-200 bg-slate-50/50 text-slate-600 hover:border-slate-300 hover:bg-slate-50'
                "
              >
                <input
                  type="radio"
                  v-model="quizForm.visibility"
                  value="public"
                  class="mt-1 accent-[#7C3AED] h-4 w-4 cursor-pointer"
                />
                <div class="grid gap-1">
                  <span
                    class="font-bold text-sm text-slate-900 flex items-center gap-1.5"
                  >
                    <div class="flex h-5 w-5 items-center justify-center rounded bg-purple-100 text-purple-700">
                      <Globe :size="12" />
                    </div>
                    <span>Công khai</span>
                  </span>
                  <span class="text-xs leading-relaxed text-slate-500"
                    >Chia sẻ ngay cho cộng đồng cùng làm bài.</span
                  >
                </div>
              </label>
            </div>
          </div>

          <div class="grid md:grid-cols-2 gap-4 pt-1">
            <label class="grid gap-1.5 text-xs font-medium text-slate-700">
              Thời gian làm bài (phút)
              <div class="flex items-center gap-3">
                <input
                  v-model.number="quizForm.time_limit_minutes"
                  type="number"
                  min="1"
                  max="180"
                  class="w-32 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-center text-sm font-bold text-slate-900 outline-none transition hover:border-slate-300 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/10 shadow-2xs"
                />
                <span class="text-xs font-medium text-slate-500">phút</span>
              </div>
            </label>

            <div class="flex items-center pt-2 sm:pt-4">
              <label
                class="flex items-center gap-3 cursor-pointer text-xs font-medium text-slate-700 select-none"
              >
                <input
                  type="checkbox"
                  v-model="quizForm.shuffle_questions"
                  class="h-4 w-4 rounded accent-[#7C3AED] cursor-pointer"
                />
                <span class="font-semibold text-slate-800">Trộn thứ tự câu hỏi khi làm bài</span>
              </label>
            </div>
          </div>
        </div>

        <!-- 3. CHỌN & QUẢN LÝ CÂU HỎI -->
        <div class="grid gap-4 pt-5 border-t border-slate-200">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <h2
              class="flex items-center gap-2 text-base font-semibold text-slate-900"
            >
              <span class="h-4 w-1 rounded-full bg-indigo-500"></span>
              <span
                >3.
                {{
                  mode === "random"
                    ? "Phân bổ câu hỏi tự động"
                    : "Danh sách câu hỏi đã chọn"
                }}</span
              >
            </h2>

            <span
              v-if="mode === 'random'"
              class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600"
            >
              Khả dụng trong Ngân hàng:
              <strong class="text-purple-700 font-bold">{{ poolStats.total }}</strong>
              câu
            </span>
          </div>

          <!-- A. KHỐI TỰ ĐỘNG PHÂN BỔ ĐỘ KHÓ (RANDOM MODE) -->
          <div v-if="mode === 'random'" class="grid gap-4">
            <!-- Empty Bank State in Automatic Mode -->
            <div
              v-if="poolStats.total === 0"
              class="rounded-xl border border-amber-200 bg-amber-50/70 p-6 text-center grid gap-3 shadow-2xs"
            >
              <div class="flex justify-center">
                <BookOpen :size="36" class="text-amber-600" />
              </div>
              <div>
                <p class="text-sm font-bold text-slate-900">
                  Chưa có câu hỏi khả dụng trong Ngân hàng cho tiêu chí này
                </p>
                <p class="text-xs text-slate-600 mt-1 max-w-lg mx-auto">
                  Ngân hàng câu hỏi công khai hiện chưa có câu hỏi nào khớp với
                  Bộ môn / Chủ đề đã chọn. Bạn có thể chọn Bộ môn khác hoặc
                  chuyển sang chế độ <strong class="text-slate-800">Chọn thủ công</strong> để sử dụng
                  các câu hỏi từ <strong class="text-slate-800">Kho cá nhân</strong> của bạn.
                </p>
              </div>
              <div class="pt-2">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-2xs cursor-pointer"
                  @click="switchToManualAndOpenPicker"
                >
                  <Target :size="14" />
                  <span>Chuyển sang Chọn thủ công</span>
                </button>
              </div>
            </div>

            <!-- Normal Matrix Cards in Automatic Mode -->
            <div v-else class="grid gap-3.5">
              <div class="flex flex-wrap items-center justify-between gap-2.5">
                <!-- Quick Presets -->
                <div class="flex items-center gap-2">
                  <span class="text-xs font-medium text-slate-500">Mẫu nhanh:</span>
                  <div class="flex items-center gap-1.5">
                    <button
                      type="button"
                      class="rounded-lg border border-slate-200 bg-slate-50 hover:bg-purple-50 hover:text-purple-700 hover:border-purple-200 px-2.5 py-1 text-[11px] font-semibold text-slate-600 transition cursor-pointer"
                      @click="applyPreset('basic')"
                    >
                      10 câu cơ bản
                    </button>
                    <button
                      type="button"
                      class="rounded-lg border border-slate-200 bg-slate-50 hover:bg-purple-50 hover:text-purple-700 hover:border-purple-200 px-2.5 py-1 text-[11px] font-semibold text-slate-600 transition cursor-pointer"
                      @click="applyPreset('standard')"
                    >
                      20 câu chuẩn
                    </button>
                    <button
                      type="button"
                      class="rounded-lg border border-slate-200 bg-slate-50 hover:bg-purple-50 hover:text-purple-700 hover:border-purple-200 px-2.5 py-1 text-[11px] font-semibold text-slate-600 transition cursor-pointer"
                      @click="applyPreset('advanced')"
                    >
                      25 câu nâng cao
                    </button>
                  </div>
                </div>

                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-purple-700 bg-purple-50 border border-purple-200/80 px-2.5 py-1 rounded-lg"
                  >Tổng chọn: {{ totalMatrixQuestions }} câu</span
                >
              </div>

              <div class="grid md:grid-cols-3 gap-4">
                <!-- Dễ Card -->
                <div
                  class="rounded-xl border p-4 sm:p-5 grid gap-3 transition duration-150 shadow-2xs"
                  :class="
                    quizForm.easy_count > poolStats.easy
                      ? 'border-rose-300 bg-rose-50/60'
                      : 'border-emerald-200/90 bg-emerald-50/30 hover:bg-emerald-50/60 hover:border-emerald-300'
                  "
                >
                  <div class="flex items-center justify-between gap-2">
                    <span
                      class="inline-flex items-center gap-1.5 rounded-md bg-emerald-100/90 px-2 py-0.5 text-xs font-bold text-emerald-800"
                    >
                      <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                      <span>Dễ (Nhận biết)</span>
                    </span>
                    <span class="text-xs font-semibold text-slate-500"
                      >Kho: {{ poolStats.easy }} câu</span
                    >
                  </div>

                  <label class="grid gap-1.5 text-xs font-medium text-slate-600">
                    <span>Số lượng câu hỏi</span>
                    <input
                      v-model.number="quizForm.easy_count"
                      type="number"
                      min="0"
                      :max="poolStats.easy"
                      class="w-full rounded-lg border border-emerald-200 bg-white px-3 py-2 text-center text-lg font-bold text-slate-900 outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15 shadow-2xs"
                      :class="{
                        '!border-rose-400 !text-rose-600':
                          quizForm.easy_count > poolStats.easy,
                      }"
                    />
                  </label>
                </div>

                <!-- Vừa Card -->
                <div
                  class="rounded-xl border p-4 sm:p-5 grid gap-3 transition duration-150 shadow-2xs"
                  :class="
                    quizForm.medium_count > poolStats.medium
                      ? 'border-rose-300 bg-rose-50/60'
                      : 'border-amber-200/90 bg-amber-50/30 hover:bg-amber-50/60 hover:border-amber-300'
                  "
                >
                  <div class="flex items-center justify-between gap-2">
                    <span
                      class="inline-flex items-center gap-1.5 rounded-md bg-amber-100/90 px-2 py-0.5 text-xs font-bold text-amber-800"
                    >
                      <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                      <span>Vừa (Thông hiểu)</span>
                    </span>
                    <span class="text-xs font-semibold text-slate-500"
                      >Kho: {{ poolStats.medium }} câu</span
                    >
                  </div>

                  <label class="grid gap-1.5 text-xs font-medium text-slate-600">
                    <span>Số lượng câu hỏi</span>
                    <input
                      v-model.number="quizForm.medium_count"
                      type="number"
                      min="0"
                      :max="poolStats.medium"
                      class="w-full rounded-lg border border-amber-200 bg-white px-3 py-2 text-center text-lg font-bold text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/15 shadow-2xs"
                      :class="{
                        '!border-rose-400 !text-rose-600':
                          quizForm.medium_count > poolStats.medium,
                      }"
                    />
                  </label>
                </div>

                <!-- Khó Card -->
                <div
                  class="rounded-xl border p-4 sm:p-5 grid gap-3 transition duration-150 shadow-2xs"
                  :class="
                    quizForm.hard_count > poolStats.hard
                      ? 'border-rose-300 bg-rose-50/60'
                      : 'border-rose-200/90 bg-rose-50/30 hover:bg-rose-50/60 hover:border-rose-300'
                  "
                >
                  <div class="flex items-center justify-between gap-2">
                    <span
                      class="inline-flex items-center gap-1.5 rounded-md bg-rose-100/90 px-2 py-0.5 text-xs font-bold text-rose-800"
                    >
                      <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                      <span>Khó (Vận dụng)</span>
                    </span>
                    <span class="text-xs font-semibold text-slate-500"
                      >Kho: {{ poolStats.hard }} câu</span
                    >
                  </div>

                  <label class="grid gap-1.5 text-xs font-medium text-slate-600">
                    <span>Số lượng câu hỏi</span>
                    <input
                      v-model.number="quizForm.hard_count"
                      type="number"
                      min="0"
                      :max="poolStats.hard"
                      class="w-full rounded-lg border border-rose-200 bg-white px-3 py-2 text-center text-lg font-bold text-slate-900 outline-none transition focus:border-rose-500 focus:ring-2 focus:ring-rose-500/15 shadow-2xs"
                      :class="{
                        '!border-rose-400 !text-rose-600':
                          quizForm.hard_count > poolStats.hard,
                      }"
                    />
                  </label>
                </div>
              </div>

              <!-- Matrix Warnings -->
              <div
                v-if="matrixWarnings.length > 0"
                class="rounded-xl border border-rose-200 bg-rose-50/80 p-3.5 grid gap-1.5 shadow-2xs"
              >
                <div
                  v-for="(warn, idx) in matrixWarnings"
                  :key="idx"
                  class="text-xs font-semibold text-rose-700 flex items-center gap-1.5"
                >
                  <AlertTriangle :size="13" class="shrink-0 text-rose-600" />
                  <span>{{ warn }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- B. KHỐI CHỌN THỦ CÔNG (MANUAL MODE) -->
          <div v-else class="grid gap-3">
            <!-- Selected Questions List -->
            <div v-if="selectedIds.length > 0" class="grid gap-3">
              <div
                class="flex flex-wrap items-center justify-between gap-3 text-xs font-medium"
              >
                <!-- Filter Tabs -->
                <div class="flex items-center gap-1 bg-slate-100/80 p-1 rounded-xl border border-slate-200">
                  <button
                    type="button"
                    class="px-2.5 py-1 rounded-lg text-xs font-semibold transition cursor-pointer"
                    :class="manualListFilter === 'all' ? 'bg-white text-purple-700 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                    @click="manualListFilter = 'all'"
                  >
                    Tất cả ({{ selectedIds.length }})
                  </button>
                  <button
                    v-if="myBankCount > 0"
                    type="button"
                    class="px-2.5 py-1 rounded-lg text-xs font-semibold transition cursor-pointer flex items-center gap-1"
                    :class="manualListFilter === 'my_bank' ? 'bg-white text-emerald-700 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                    @click="manualListFilter = 'my_bank'"
                  >
                    <User :size="11" />
                    <span>Kho của tôi ({{ myBankCount }})</span>
                  </button>
                  <button
                    v-if="publicBankCount > 0"
                    type="button"
                    class="px-2.5 py-1 rounded-lg text-xs font-semibold transition cursor-pointer flex items-center gap-1"
                    :class="manualListFilter === 'public_bank' ? 'bg-white text-sky-700 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                    @click="manualListFilter = 'public_bank'"
                  >
                    <Globe :size="11" />
                    <span>Ngân hàng ({{ publicBankCount }})</span>
                  </button>
                </div>

                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 rounded-xl border border-purple-200 bg-purple-50 px-3.5 py-1.5 text-xs font-bold text-purple-700 transition hover:bg-purple-100 hover:border-purple-300 cursor-pointer shadow-2xs"
                  @click="openQuestionPicker"
                >
                  <Plus :size="13" />
                  <span>Chọn thêm câu hỏi</span>
                </button>
              </div>

              <!-- List of Selected Question Items -->
              <div class="grid gap-2.5 max-h-[460px] overflow-y-auto pr-1">
                <div
                  v-for="(q, idx) in filteredSelectedQuestions"
                  :key="q.id"
                  class="flex items-center justify-between gap-3 rounded-xl border border-slate-200/90 bg-slate-50/50 p-3.5 transition hover:border-purple-300 hover:bg-white hover:shadow-xs group"
                >
                  <div class="flex items-center gap-3 min-w-0 flex-1">
                    <span
                      class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white text-xs font-bold text-purple-700 border border-slate-200 group-hover:border-purple-200 group-hover:bg-purple-50 transition shadow-2xs"
                    >
                      {{ idx + 1 }}
                    </span>

                    <div class="min-w-0 flex-1">
                      <div class="flex flex-wrap items-center gap-1.5 mb-1">
                        <span
                          class="font-mono text-xs font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded-md border border-purple-100"
                          >#{{ q.id }}</span
                        >
                        <span
                          class="rounded-md px-2 py-0.5 text-[10px] font-bold"
                          :class="getDifficultyClass(q.difficulty)"
                        >
                          {{ getDifficultyLabel(q.difficulty) }}
                        </span>
                        <span
                          class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-semibold"
                          :class="
                            q.source === 'my_bank'
                              ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                              : 'bg-sky-50 text-sky-700 border border-sky-200'
                          "
                        >
                          <component
                            :is="q.source === 'my_bank' ? User : Globe"
                            :size="11"
                          />
                          <span>{{
                            q.source === "my_bank" ? "Kho của tôi" : "Ngân hàng"
                          }}</span>
                        </span>
                        <span
                          v-if="q.subject_name"
                          class="text-[10px] text-slate-500 font-medium"
                        >
                          • {{ q.subject_name }}
                        </span>
                      </div>

                      <p
                        class="text-xs sm:text-sm font-medium text-slate-900 truncate"
                      >
                        {{ q.content || q.text || `Câu hỏi #${q.id}` }}
                      </p>
                    </div>
                  </div>

                  <button
                    type="button"
                    class="h-8 w-8 shrink-0 flex items-center justify-center rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition cursor-pointer"
                    aria-label="Bỏ câu hỏi này khỏi đề thi"
                    title="Bỏ câu hỏi này khỏi đề thi"
                    @click="removeSelectedQuestion(q.id)"
                  >
                    <X :size="14" />
                  </button>
                </div>
              </div>
            </div>

            <!-- Empty State for Manual Mode -->
            <div
              v-else
              class="rounded-xl border border-dashed border-slate-300 bg-slate-50/50 p-8 sm:p-10 text-center grid gap-3"
            >
              <div class="flex justify-center">
                <BookOpen :size="40" class="text-slate-400" />
              </div>
              <div>
                <p class="text-base font-bold text-slate-800">
                  Chưa có câu hỏi nào được chọn
                </p>
                <p
                  class="text-xs text-slate-500 mt-1.5 max-w-md mx-auto leading-relaxed"
                >
                  Hãy chọn các câu hỏi từ
                  <strong class="text-slate-700">Kho câu hỏi cá nhân</strong> hoặc
                  <strong class="text-slate-700">Ngân hàng câu hỏi công khai</strong> để đưa vào đề
                  thi.
                </p>
              </div>
              <div class="pt-2">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl bg-[#7C3AED] px-6 py-2.5 text-xs font-semibold text-white shadow-md shadow-[#7C3AED]/25 transition hover:bg-[#6D28D9] hover:shadow-lg hover:shadow-[#7C3AED]/30 cursor-pointer active:scale-[0.98]"
                  @click="openQuestionPicker"
                >
                  <Plus :size="14" />
                  <span>Chọn câu hỏi cho Quiz</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- 4. FOOTER ACTIONS BAR -->
        <div
          class="flex flex-wrap items-center justify-between gap-4 pt-6 border-t border-slate-200"
        >
          <button
            type="button"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:border-slate-300 cursor-pointer shadow-2xs"
            @click="goBack"
          >
            Hủy
          </button>

          <button
            type="submit"
            class="rounded-xl bg-[#7C3AED] px-8 py-2.5 text-sm font-semibold text-white shadow-md shadow-[#7C3AED]/25 transition hover:bg-[#6D28D9] hover:shadow-lg hover:shadow-[#7C3AED]/30 active:scale-[0.98] disabled:opacity-50 cursor-pointer flex items-center gap-2"
            :disabled="
              isSubmitting ||
              (mode === 'random' &&
                (totalMatrixQuestions === 0 ||
                  matrixWarnings.length > 0 ||
                  poolStats.total === 0)) ||
              (mode === 'manual' && selectedIds.length === 0)
            "
          >
            <span
              v-if="isSubmitting"
              class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"
            ></span>
            <span>{{ isSubmitting ? "Đang tạo Quiz..." : "Tạo Quiz" }}</span>
          </button>
        </div>
      </div>
    </form>

    <!-- RIGHT ASIDE: SOFT & ELEGANT LIVE PREVIEW -->
    <aside class="grid content-start gap-5">
      <article
        class="sticky top-6 grid gap-5 overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
      >
        <!-- Header -->
        <div
          class="flex items-center justify-between border-b border-slate-200 pb-4"
        >
          <div class="flex items-center gap-2">
            <h3 class="text-base font-bold text-slate-900">Live Preview</h3>
            <span class="inline-block h-1.5 w-1.5 rounded-full bg-purple-600"></span>
          </div>

          <span
            class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 shadow-2xs"
          >
            <span
              class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"
            ></span>
            Live
          </span>
        </div>

        <!-- Cover Banner Preview -->
        <div
          class="relative h-36 overflow-hidden rounded-xl border border-slate-200 shadow-2xs transition-all duration-300"
          :style="{ background: coverBackground }"
        >
          <div
            class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/25 to-transparent"
          ></div>
          <div class="absolute bottom-3 left-3 right-3 text-white drop-shadow">
            <span
              v-if="selectedSubjectName"
              class="inline-block rounded-md bg-black/60 text-white backdrop-blur-md px-2.5 py-0.5 text-[10px] font-bold border border-white/20"
            >
              Môn {{ selectedSubjectName }}
            </span>
            <h4 class="mt-1.5 text-sm font-bold leading-tight line-clamp-2">
              {{ quizForm.title || "Bộ đề thi chưa đặt tên" }}
            </h4>
            <p class="mt-0.5 text-[11px] font-medium text-white/80 line-clamp-1">
              {{ quizForm.topic_name || "Kho câu hỏi QuizFlex" }}
            </p>
          </div>
        </div>

        <!-- Status Pills -->
        <div class="flex flex-wrap items-center gap-2">
          <span
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-2xs"
          >
            <component :is="mode === 'random' ? Sparkles : Target" :size="12" class="text-purple-600" />
            <span>{{
              mode === "random" ? "Tự động phân bổ" : "Chọn thủ công"
            }}</span>
          </span>

          <span
            class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-bold shadow-2xs"
            :class="
              quizForm.visibility === 'public'
                ? 'border-purple-200 bg-purple-50 text-purple-700'
                : 'border-amber-200 bg-amber-50 text-amber-700'
            "
          >
            <component
              :is="quizForm.visibility === 'public' ? Globe : Lock"
              :size="12"
            />
            <span>{{
              quizForm.visibility === "public" ? "Công khai" : "Riêng tư"
            }}</span>
          </span>
        </div>

        <!-- Title & Description Box -->
        <div
          class="rounded-xl border border-slate-200/90 bg-slate-50/70 p-3.5 grid gap-1.5"
        >
          <h4 class="text-sm font-bold text-slate-900 leading-snug">
            <span v-if="quizForm.title.trim()">{{ quizForm.title }}</span>
            <span v-else class="text-slate-400 italic font-normal text-xs"
              >[Tên bộ đề sẽ hiển thị ở đây]</span
            >
          </h4>

          <p
            v-if="quizForm.description.trim()"
            class="text-xs text-slate-500 leading-relaxed line-clamp-3"
          >
            {{ quizForm.description }}
          </p>
        </div>

        <!-- Key Metrics Grid -->
        <div class="grid grid-cols-2 gap-2 text-xs">
          <div class="rounded-xl border border-purple-200/80 bg-purple-50/50 p-3 shadow-2xs">
            <span
              class="text-[10px] font-bold text-purple-600 block uppercase tracking-wider"
              >Số lượng câu</span
            >
            <span class="font-black text-base text-purple-800 mt-0.5 block">
              {{
                mode === "manual" ? selectedIds.length : totalMatrixQuestions
              }}
              <span class="text-xs font-semibold text-purple-600">câu</span>
            </span>
          </div>

          <div class="rounded-xl border border-slate-200/80 bg-slate-50 p-3 shadow-2xs">
            <span
              class="text-[10px] font-bold text-slate-500 block uppercase tracking-wider"
              >Thời gian làm</span
            >
            <span class="font-bold text-base text-slate-800 mt-0.5 block">
              {{ quizForm.time_limit_minutes }} <span class="text-xs font-medium text-slate-500">phút</span>
            </span>
          </div>
        </div>

        <!-- Shuffle Setting Row -->
        <div
          class="rounded-xl border border-slate-200/80 bg-slate-50 p-3 text-xs flex items-center justify-between font-medium text-slate-800 shadow-2xs"
        >
          <span class="text-slate-500 font-normal">Thứ tự làm bài:</span>
          <span class="inline-flex items-center gap-1.5 font-bold text-slate-800">
            <component
              :is="quizForm.shuffle_questions ? Shuffle : Lock"
              :size="13"
              class="text-purple-600"
            />
            <span>{{
              quizForm.shuffle_questions ? "Tự động trộn câu" : "Thứ tự cố định"
            }}</span>
          </span>
        </div>

        <!-- Matrix Proportions Progress Bar (Random Mode) -->
        <div
          v-if="mode === 'random'"
          class="grid gap-3 pt-3 border-t border-slate-200"
        >
          <div class="flex items-center justify-between text-xs font-medium text-slate-600">
            <span>Tỷ lệ phân bổ độ khó:</span>
            <span class="text-slate-900 font-bold"
              >{{ totalMatrixQuestions }} câu</span
            >
          </div>

          <!-- Segmented Bar -->
          <div
            v-if="totalMatrixQuestions > 0"
            class="h-2.5 w-full rounded-full bg-slate-100 p-0.5 border border-slate-200/80 overflow-hidden flex shadow-inner"
          >
            <div
              class="bg-emerald-500 h-full rounded-l-full transition-all duration-300"
              :style="{ width: easyPercent + '%' }"
              :title="`Dễ: ${quizForm.easy_count} câu (${easyPercent}%)`"
            ></div>
            <div
              class="bg-amber-500 h-full transition-all duration-300"
              :style="{ width: mediumPercent + '%' }"
              :title="`Vừa: ${quizForm.medium_count} câu (${mediumPercent}%)`"
            ></div>
            <div
              class="bg-rose-500 h-full rounded-r-full transition-all duration-300"
              :style="{ width: hardPercent + '%' }"
              :title="`Khó: ${quizForm.hard_count} câu (${hardPercent}%)`"
            ></div>
          </div>

          <!-- Detail Rows -->
          <div class="grid gap-1.5 text-xs">
            <div class="flex items-center justify-between text-slate-500">
              <span class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Dễ
                (Nhận biết)
              </span>
              <span class="font-bold text-slate-800"
                >{{ quizForm.easy_count || 0 }} câu ({{ easyPercent }}%)</span
              >
            </div>

            <div class="flex items-center justify-between text-slate-500">
              <span class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-amber-500"></span> Vừa
                (Thông hiểu)
              </span>
              <span class="font-bold text-slate-800"
                >{{ quizForm.medium_count || 0 }} câu ({{
                  mediumPercent
                }}%)</span
              >
            </div>

            <div class="flex items-center justify-between text-slate-500">
              <span class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-rose-500"></span> Khó (Vận
                dụng)
              </span>
              <span class="font-bold text-slate-800"
                >{{ quizForm.hard_count || 0 }} câu ({{ hardPercent }}%)</span
              >
            </div>
          </div>
        </div>
      </article>
    </aside>

    <!-- QUESTION BANK DRAWER -->
    <QuestionBankDrawer
      :is-open="isBankDrawerOpen"
      :initial-selected-ids="selectedIds"
      @close="isBankDrawerOpen = false"
      @confirm="onBankDrawerConfirm"
    />
  </section>

  <!-- QUESTION PICKER MODAL -->
  <QuestionPickerModal
    v-model="selectedIds"
    :is-open="isPickerModalOpen"
    :initial-filters="filters"
    @close="isPickerModalOpen = false"
    @confirm="handlePickerConfirm"
  />
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  ArrowLeft,
  Target,
  Sparkles,
  Info,
  Image as ImageIcon,
  Camera,
  Lock,
  Globe,
  Shuffle,
  User,
  Check,
  X,
  Plus,
  BookOpen,
  AlertTriangle,
  FileQuestion,
  GraduationCap,
} from "lucide-vue-next";
import QuestionPickerModal from "@/components/question/QuestionPickerModal.vue";
import {
  coverToBackground,
  myQuestionsApi,
  questionsBankApi,
  taxonomyApi,
} from "@/services/api";

const route = useRoute();
const router = useRouter();
const showToast = inject("showToast");

const mode = ref(route.query.mode === "manual" ? "manual" : "random");
const isSubmitting = ref(false);

const isPickerModalOpen = ref(false);
const isBankDrawerOpen = ref(false);
const selectedQuestionsMap = ref(new Map());
const manualListFilter = ref("all");

const coverInput = ref(null);
const formCoverFile = ref(null);
const coverPreview = ref("");

const selectedIds = ref([]);
if (route.query.ids) {
  selectedIds.value = String(route.query.ids)
    .split(",")
    .map(Number)
    .filter(Boolean);
}

const taxonomyLevels = ref([]);
const allSubjects = ref([]);
const topicsList = ref([]);

const poolStats = reactive({
  easy: 0,
  medium: 0,
  hard: 0,
  total: 0,
});

const filters = reactive({
  education_level_id: route.query.education_level_id
    ? Number(route.query.education_level_id)
    : "",
  grade_id: route.query.grade_id ? Number(route.query.grade_id) : "",
  subject_id: route.query.subject_id ? Number(route.query.subject_id) : "",
  topic_name:
    typeof route.query.topic_name === "string" ? route.query.topic_name : "",
});

const quizForm = reactive({
  title: "",
  description: "Bộ đề thi chuẩn hóa khởi tạo từ Ngân hàng câu hỏi QuizFlex",
  topic_name: filters.topic_name || "",
  easy_count: 3,
  medium_count: 5,
  hard_count: 2,
  time_limit_minutes: 15,
  shuffle_questions: true,
  visibility: mode.value === "manual" ? "private" : "public",
  cover: "",
});

watch(
  mode,
  (newMode) => {
    if (newMode === "manual") {
      quizForm.visibility = "private";
    }
  },
  { immediate: true },
);

const openCoverPicker = () => coverInput.value?.click();

const handleCoverFileChange = (e) => {
  const file = e.target.files?.[0];
  if (!file) return;
  if (coverPreview.value?.startsWith("blob:")) {
    URL.revokeObjectURL(coverPreview.value);
  }
  formCoverFile.value = file;
  coverPreview.value = URL.createObjectURL(file);
};

const removeCover = () => {
  if (coverPreview.value?.startsWith("blob:")) {
    URL.revokeObjectURL(coverPreview.value);
  }
  formCoverFile.value = null;
  coverPreview.value = "";
  quizForm.cover = "";
  if (coverInput.value) coverInput.value.value = "";
};

const coverBackground = computed(() =>
  coverToBackground(coverPreview.value || quizForm.cover),
);

const selectedCoverLabel = computed(() => {
  if (formCoverFile.value) return `Đã chọn: ${formCoverFile.value.name}`;
  if (quizForm.cover) return "Đang dùng ảnh bìa mặc định";
  return "Chưa chọn ảnh bìa (Hiển thị gradient màu sắc tự động)";
});

const selectedSubjectName = computed(() => {
  if (!filters.subject_id) return "";
  const sub = allSubjects.value.find(
    (s) => s.id === Number(filters.subject_id),
  );
  return sub ? sub.name : "";
});

const totalMatrixQuestions = computed(() => {
  return (
    (quizForm.easy_count || 0) +
    (quizForm.medium_count || 0) +
    (quizForm.hard_count || 0)
  );
});

const easyPercent = computed(() => {
  if (!totalMatrixQuestions.value) return 0;
  return Math.round(
    ((quizForm.easy_count || 0) / totalMatrixQuestions.value) * 100,
  );
});

const mediumPercent = computed(() => {
  if (!totalMatrixQuestions.value) return 0;
  return Math.round(
    ((quizForm.medium_count || 0) / totalMatrixQuestions.value) * 100,
  );
});

const hardPercent = computed(() => {
  if (!totalMatrixQuestions.value) return 0;
  return Math.round(
    ((quizForm.hard_count || 0) / totalMatrixQuestions.value) * 100,
  );
});

const matrixWarnings = computed(() => {
  const warnings = [];
  if (quizForm.easy_count > poolStats.easy) {
    warnings.push(
      `Không đủ câu hỏi Dễ. Kho hiện chỉ có ${poolStats.easy} câu.`,
    );
  }
  if (quizForm.medium_count > poolStats.medium) {
    warnings.push(
      `Không đủ câu hỏi Vừa. Kho hiện chỉ có ${poolStats.medium} câu.`,
    );
  }
  if (quizForm.hard_count > poolStats.hard) {
    warnings.push(
      `Không đủ câu hỏi Khó. Kho hiện chỉ có ${poolStats.hard} câu.`,
    );
  }
  return warnings;
});

const availableGrades = computed(() => {
  if (!filters.education_level_id) {
    return taxonomyLevels.value.flatMap((l) => l.grades || []);
  }
  const level = taxonomyLevels.value.find(
    (l) => l.id === Number(filters.education_level_id),
  );
  return level ? level.grades || [] : [];
});

const availableSubjects = computed(() => {
  if (!filters.grade_id) {
    return allSubjects.value;
  }
  const grade = availableGrades.value.find(
    (g) => g.id === Number(filters.grade_id),
  );
  return grade && grade.subjects && grade.subjects.length
    ? grade.subjects
    : allSubjects.value;
});

/* =========================================================
   PRESETS HELPER
========================================================= */
const applyPreset = (presetType) => {
  if (presetType === "basic") {
    quizForm.easy_count = Math.min(7, poolStats.easy);
    quizForm.medium_count = Math.min(3, poolStats.medium);
    quizForm.hard_count = 0;
  } else if (presetType === "standard") {
    quizForm.easy_count = Math.min(8, poolStats.easy);
    quizForm.medium_count = Math.min(8, poolStats.medium);
    quizForm.hard_count = Math.min(4, poolStats.hard);
  } else if (presetType === "advanced") {
    quizForm.easy_count = Math.min(5, poolStats.easy);
    quizForm.medium_count = Math.min(12, poolStats.medium);
    quizForm.hard_count = Math.min(8, poolStats.hard);
  }
  updateFormTitle();
};

/* =========================================================
   DIFFICULTY HELPERS
========================================================= */
const getDifficultyClass = (diff) => {
  switch (diff) {
    case "easy":
      return "border border-emerald-200 bg-emerald-50 text-emerald-700";
    case "hard":
      return "border border-rose-200 bg-rose-50 text-rose-700";
    default:
      return "border border-amber-200 bg-amber-50 text-amber-700";
  }
};

const getDifficultyLabel = (diff) => {
  switch (diff) {
    case "easy":
      return "Dễ";
    case "hard":
      return "Khó";
    default:
      return "Vừa";
  }
};

/* =========================================================
   MANUAL QUESTION PICKER LOGIC
========================================================= */
const openQuestionPicker = () => {
  isPickerModalOpen.value = true;
};

const switchToManualAndOpenPicker = () => {
  switchMode("manual");
  openQuestionPicker();
};

const handlePickerConfirm = async ({ ids, questions }) => {
  selectedIds.value = [...ids];
  if (Array.isArray(questions)) {
    questions.forEach((q) => {
      selectedQuestionsMap.value.set(q.id, q);
    });
    await ensureAnswerKeysForSelectedQuestions(questions);
  }
  updateFormTitle();
};

const onBankDrawerConfirm = async ({ ids, questions }) => {
  selectedIds.value = [...ids];
  if (Array.isArray(questions)) {
    questions.forEach((q) => {
      selectedQuestionsMap.value.set(q.id, q);
    });
    await ensureAnswerKeysForSelectedQuestions(questions);
  }
  updateFormTitle();
};

const removeSelectedQuestion = (id) => {
  selectedIds.value = selectedIds.value.filter((item) => item !== id);
  selectedQuestionsMap.value.delete(id);
  updateFormTitle();
};

const selectedQuestionsDisplayList = computed(() => {
  return selectedIds.value.map((id) => {
    return (
      selectedQuestionsMap.value.get(id) || {
        id,
        content: `Câu hỏi #${id}`,
        difficulty: "medium",
        source: "public_bank",
      }
    );
  });
});

const myBankCount = computed(() => {
  return selectedQuestionsDisplayList.value.filter(
    (q) => q.source === "my_bank",
  ).length;
});

const publicBankCount = computed(() => {
  return selectedQuestionsDisplayList.value.filter(
    (q) => q.source === "public_bank",
  ).length;
});

const filteredSelectedQuestions = computed(() => {
  if (manualListFilter.value === "all") return selectedQuestionsDisplayList.value;
  return selectedQuestionsDisplayList.value.filter(
    (q) => q.source === manualListFilter.value,
  );
});

const ensureAnswerKeysForSelectedQuestions = async (questionsList) => {
  if (!Array.isArray(questionsList) || questionsList.length === 0) return;
  const needDetailFetch = questionsList.filter((q) => {
    return (
      !q.answers ||
      q.answers.length === 0 ||
      !q.answers.some((a) => a.is_correct !== undefined)
    );
  });

  if (needDetailFetch.length === 0) return;

  await Promise.all(
    needDetailFetch.map(async (q) => {
      try {
        const detail = await questionsBankApi.getQuestion(q.id);
        if (detail && detail.answers) {
          selectedQuestionsMap.value.set(q.id, {
            ...q,
            ...detail,
            answers: detail.answers,
          });
        }
      } catch (e) {
        console.error(`Không thể tải đáp án câu hỏi #${q.id}:`, e);
      }
    }),
  );
};

const hydrateSelectedQuestions = async (ids) => {
  const missingIds = ids.filter((id) => !selectedQuestionsMap.value.has(id));
  if (missingIds.length === 0) return;
  try {
    const publicRes = await questionsBankApi.fetchBank({
      ids: missingIds,
      per_page: missingIds.length,
    });
    const publicItems =
      publicRes?.items || (Array.isArray(publicRes) ? publicRes : []);
    publicItems.forEach((q) => {
      selectedQuestionsMap.value.set(q.id, { ...q, source: "public_bank" });
    });

    const stillMissing = ids.filter(
      (id) => !selectedQuestionsMap.value.has(id),
    );
    if (stillMissing.length > 0) {
      const userRes = await myQuestionsApi.fetchBank({
        ids: stillMissing,
        per_page: stillMissing.length,
      });
      const userItems =
        userRes?.items || (Array.isArray(userRes) ? userRes : []);
      userItems.forEach((q) => {
        selectedQuestionsMap.value.set(q.id, { ...q, source: "my_bank" });
      });
    }

    const allLoaded = ids
      .map((id) => selectedQuestionsMap.value.get(id))
      .filter(Boolean);
    await ensureAnswerKeysForSelectedQuestions(allLoaded);
  } catch (e) {
    console.error("Không tải được chi tiết câu hỏi:", e);
  }
};

const getCorrectAnswerKey = (question) => {
  if (
    !question ||
    !Array.isArray(question.answers) ||
    question.answers.length === 0
  )
    return null;
  const correctAnswers = question.answers.filter((a) => Boolean(a.is_correct));
  if (correctAnswers.length === 0) return null;

  return correctAnswers
    .map((correctAns, idx) => {
      const key =
        correctAns.answer_key ||
        correctAns.key ||
        (question.type === "single_choice" || question.type === "multi_choice"
          ? String.fromCharCode(65 + idx)
          : "");
      const text = correctAns.text || correctAns.content || "";
      return key ? (text ? `${key}. ${text}` : key) : text;
    })
    .join(" | ");
};

const goBack = () => {
  if (window.history.length > 1) {
    router.back();
  } else {
    router.push("/quizzes");
  }
};

const switchMode = (newMode) => {
  mode.value = newMode;
  updateFormTitle();
};

const updateFormTitle = () => {
  if (!quizForm.title) {
    if (mode.value === "manual") {
      quizForm.title = `Quiz đóng gói từ ${selectedIds.value.length} câu đã chọn`;
      quizForm.time_limit_minutes = Math.max(selectedIds.value.length, 10);
    } else {
      quizForm.title = filters.topic_name
        ? `Quiz ôn tập tự động - Chủ đề: ${filters.topic_name}`
        : `Quiz ôn tập tự động ${totalMatrixQuestions.value} câu`;
      quizForm.time_limit_minutes = Math.max(totalMatrixQuestions.value, 10);
    }
  }
};

const fetchTaxonomy = async () => {
  try {
    const data = await taxonomyApi.tree();
    const payload = data?.education_levels ? data : (data?.data ?? data);
    if (payload) {
      taxonomyLevels.value =
        payload.education_levels || payload.educationLevels || [];
      allSubjects.value = payload.subjects || [];
    }
  } catch (e) {
    console.error("Không tải được taxonomy:", e);
  }
};

const fetchTopicsList = async () => {
  try {
    const params = {
      education_level_id: filters.education_level_id || undefined,
      grade_id: filters.grade_id || undefined,
      subject_id: filters.subject_id || undefined,
    };
    const data = await questionsBankApi.fetchTopics(params);
    topicsList.value = Array.isArray(data)
      ? data
      : data?.data && Array.isArray(data.data)
        ? data.data
        : [];
  } catch (e) {
    console.error("Không tải được danh sách Chủ đề:", e);
  }
};

const updatePoolStats = async () => {
  try {
    const params = {
      education_level_id: filters.education_level_id || undefined,
      grade_id: filters.grade_id || undefined,
      subject_id: filters.subject_id || undefined,
      topic_name: filters.topic_name || undefined,
    };
    const res = await questionsBankApi.fetchStats(params);
    const stats =
      res?.easy !== undefined
        ? res
        : (res?.data ?? { easy: 0, medium: 0, hard: 0, total: 0 });
    poolStats.easy = stats.easy || 0;
    poolStats.medium = stats.medium || 0;
    poolStats.hard = stats.hard || 0;
    poolStats.total = stats.total || 0;

    // Adjust counts safely if pool is smaller
    if (quizForm.easy_count > poolStats.easy)
      quizForm.easy_count = Math.min(3, poolStats.easy);
    if (quizForm.medium_count > poolStats.medium)
      quizForm.medium_count = Math.min(5, poolStats.medium);
    if (quizForm.hard_count > poolStats.hard)
      quizForm.hard_count = Math.min(2, poolStats.hard);

    updateFormTitle();
  } catch (e) {
    console.error("Không đếm được số câu khả dụng:", e);
  }
};

const onLevelChange = () => {
  filters.grade_id = "";
  onGradeSubjectChange();
};

const onGradeSubjectChange = () => {
  fetchTopicsList();
  updatePoolStats();
};

const onTopicSelectChange = () => {
  if (!quizForm.topic_name && filters.topic_name) {
    quizForm.topic_name = filters.topic_name;
  }
  updatePoolStats();
};

const onTopicInputChange = () => {
  const text = (quizForm.topic_name || "").trim().toLowerCase();
  if (!text) {
    filters.topic_name = "";
  } else {
    const found = topicsList.value.find(
      (t) => t.topic_name.toLowerCase() === text,
    );
    if (found) {
      filters.topic_name = found.topic_name;
    } else {
      filters.topic_name = "";
    }
  }
  updatePoolStats();
};

const handleCreateQuizSubmit = async () => {
  if (!quizForm.title.trim()) {
    if (showToast) showToast("Vui lòng nhập tên Quiz!", "error");
    else alert("Vui lòng nhập tên Quiz!");
    return;
  }
  if (!filters.subject_id) {
    if (showToast) showToast("Vui lòng chọn Bộ môn cho Quiz!", "error");
    else alert("Vui lòng chọn Bộ môn cho Quiz!");
    return;
  }
  if (mode.value === "random") {
    if (poolStats.total === 0) {
      if (showToast)
        showToast(
          "Ngân hàng câu hỏi chưa có câu hỏi cho tiêu chí này!",
          "error",
        );
      else alert("Ngân hàng câu hỏi chưa có câu hỏi cho tiêu chí này!");
      return;
    }
    if (totalMatrixQuestions.value === 0) {
      if (showToast)
        showToast("Vui lòng chọn số lượng câu hỏi phân bổ!", "error");
      else alert("Vui lòng chọn số lượng câu hỏi phân bổ!");
      return;
    }
    if (matrixWarnings.value.length > 0) {
      if (showToast)
        showToast(
          "Số lượng câu hỏi vượt quá số lượng hiện có trong kho!",
          "error",
        );
      else alert("Số lượng câu hỏi vượt quá số lượng hiện có trong kho!");
      return;
    }
  }
  if (mode.value === "manual" && selectedIds.value.length === 0) {
    if (showToast)
      showToast("Vui lòng chọn ít nhất 1 câu hỏi cho Quiz!", "error");
    else alert("Vui lòng chọn ít nhất 1 câu hỏi cho Quiz!");
    return;
  }

  isSubmitting.value = true;
  try {
    const isManualMode = mode.value === "manual";
    const isPublic = isManualMode ? false : quizForm.visibility === "public";
    const payload = {
      title: quizForm.title.trim(),
      description: quizForm.description.trim(),
      mode: isManualMode ? "manual" : "auto",
      education_level_id: filters.education_level_id || undefined,
      grade_id: filters.grade_id || undefined,
      subject_id: filters.subject_id || undefined,
      topic_name: filters.topic_name || undefined,
      quiz_topic_name:
        quizForm.topic_name.trim() || filters.topic_name || undefined,
      time_limit_minutes: quizForm.time_limit_minutes || 15,
      shuffle_questions: quizForm.shuffle_questions,
      is_public: isPublic,
      status: isPublic ? "published" : "draft",
      cover_file: formCoverFile.value || undefined,
      cover: quizForm.cover || undefined,
    };

    if (isManualMode) {
      payload.question_ids = selectedIds.value;
    } else {
      payload.easy_count = quizForm.easy_count || 0;
      payload.medium_count = quizForm.medium_count || 0;
      payload.hard_count = quizForm.hard_count || 0;
      payload.random_count = totalMatrixQuestions.value;
    }

    const createdQuiz = await questionsBankApi.createQuizFromBank(payload);
    if (showToast) showToast("Tạo Quiz thành công!", "success");

    if (createdQuiz && createdQuiz.id) {
      router.push(`/quizzes/${createdQuiz.id}`);
    } else {
      router.push("/quizzes");
    }
  } catch (e) {
    if (showToast) showToast(`Không thể tạo Quiz: ${e.message}`, "error");
    else alert(`Không thể tạo Quiz: ${e.message}`);
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(async () => {
  await fetchTaxonomy();
  await fetchTopicsList();
  await updatePoolStats();
  if (selectedIds.value.length > 0) {
    await hydrateSelectedQuestions(selectedIds.value);
  }
});
</script>
