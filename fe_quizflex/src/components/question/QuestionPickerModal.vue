<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-2 sm:p-4 md:p-6 backdrop-blur-md animate-fade-in"
    @click.self="handleCancel"
  >
    <div
      class="relative flex h-[92vh] max-h-[880px] w-full max-w-[1360px] flex-col overflow-hidden rounded-[1.75rem] border border-slate-200/90 bg-white shadow-2xl"
    >
      <!-- =========================================================
           1. HEADER ZONE
      ========================================================= -->
      <div
        class="border-b border-slate-200/80 px-6 py-3.5 bg-white flex items-center justify-between gap-4 shrink-0"
      >
        <div class="flex items-center gap-3">
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-purple-500/10 text-purple-500"
          >
            <Layers :size="20" />
          </div>

          <div>
            <div class="flex items-center gap-2">
              <h3
                class="text-base sm:text-lg font-black tracking-tight text-slate-900"
              >
                Chọn câu hỏi cho bộ đề
              </h3>
              <span
                class="inline-block h-2 w-2 rounded-full bg-[#7c3aed]"
              ></span>
            </div>
            <p class="text-xs text-slate-500 font-medium hidden sm:block">
              Tra cứu, lọc và chọn câu hỏi từ Kho cá nhân hoặc Ngân hàng dùng
              chung chèn trực tiếp vào Bài Quiz.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2.5">
          <!-- + Tạo câu hỏi mới button -->
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 shadow-xs transition active:scale-[0.98] cursor-pointer"
            @click="openCreateQuestionModal"
          >
            <Plus :size="14" class="text-[#7c3aed]" :stroke-width="2.5" />
            <span>Tạo câu hỏi mới</span>
          </button>

          <!-- Close button X -->
          <button
            type="button"
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
            title="Đóng modal"
            @click="handleCancel"
          >
            <X :size="18" />
          </button>
        </div>
      </div>

      <!-- =========================================================
           2. SPLIT BODY ZONE (SIDEBAR + MAIN CONTENT)
      ========================================================= -->
      <div class="flex-1 flex overflow-hidden min-h-0">
        <!-- -------------------------------------------------------
             LEFT SIDEBAR (Fixed width, independent scroll)
        ------------------------------------------------------- -->
        <aside
          class="w-72 sm:w-80 shrink-0 border-r border-slate-200 bg-slate-50/50 p-4 sm:p-5 flex flex-col justify-between overflow-y-auto space-y-6"
        >
          <div class="space-y-6">
            <!-- NGUỒN CÂU HỎI -->
            <div class="space-y-2.5">
              <h4
                class="text-[11px] font-bold tracking-wider text-slate-400 uppercase"
              >
                Nguồn câu hỏi
              </h4>

              <!-- Option 1: Kho câu hỏi của tôi -->
              <div
                class="flex items-center justify-between gap-2.5 rounded-xl border p-3 cursor-pointer transition-all duration-150 select-none"
                :class="
                  activeTab === 'my_bank'
                    ? 'border-amber-300 bg-amber-50/80 text-slate-900 shadow-xs'
                    : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50/80'
                "
                @click="switchTab('my_bank')"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                    :class="
                      activeTab === 'my_bank'
                        ? 'bg-amber-500 text-white'
                        : 'bg-amber-100 text-amber-600'
                    "
                  >
                    <Folder :size="16" />
                  </div>
                  <span class="text-xs font-bold truncate"
                    >Kho câu hỏi của tôi</span
                  >
                </div>

                <div class="flex items-center gap-1.5 shrink-0">
                  <span
                    v-if="myBankSelectedCount > 0"
                    class="rounded-full bg-[#7c3aed] px-1.5 py-0.5 text-[10px] font-black text-white"
                  >
                    {{ myBankSelectedCount }}
                  </span>
                </div>
              </div>

              <!-- Option 2: Ngân hàng cộng đồng -->
              <div
                class="flex items-center justify-between gap-2.5 rounded-xl border p-3 cursor-pointer transition-all duration-150 select-none"
                :class="
                  activeTab === 'public_bank'
                    ? 'border-sky-300 bg-sky-50/80 text-slate-900 shadow-xs'
                    : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50/80'
                "
                @click="switchTab('public_bank')"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                    :class="
                      activeTab === 'public_bank'
                        ? 'bg-sky-500 text-white'
                        : 'bg-sky-100 text-sky-600'
                    "
                  >
                    <Globe :size="16" />
                  </div>
                  <span class="text-xs font-bold truncate"
                    >Ngân hàng câu hỏi</span
                  >
                </div>

                <div class="flex items-center gap-1.5 shrink-0">
                  <span
                    v-if="publicBankSelectedCount > 0"
                    class="rounded-full bg-[#7c3aed] px-1.5 py-0.5 text-[10px] font-black text-white"
                  >
                    {{ publicBankSelectedCount }}
                  </span>
                </div>
              </div>
            </div>

            <!-- BỘ LỌC PHÂN LOẠI -->
            <div class="space-y-3.5 pt-2 border-t border-slate-200/80">
              <div class="flex items-center justify-between">
                <h4
                  class="text-[11px] font-bold tracking-wider text-slate-400 uppercase"
                >
                  Bộ lọc phân loại
                </h4>
                <button
                  v-if="hasActiveFilters"
                  type="button"
                  class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-500 hover:underline cursor-pointer"
                  @click="resetFilters"
                >
                  <RotateCcw :size="11" />
                  <span>Đặt lại</span>
                </button>
              </div>

              <!-- Cấp học -->
              <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600 block"
                  >Cấp học</label
                >
                <select
                  v-model="filters.education_level_id"
                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:border-[#7c3aed] focus:ring-1 focus:ring-[#7c3aed] cursor-pointer"
                  @change="onLevelChange"
                >
                  <option value="">Tất cả Cấp học</option>
                  <option
                    v-for="level in taxonomyLevels"
                    :key="level.id"
                    :value="level.id"
                  >
                    {{ level.name }}
                  </option>
                </select>
              </div>

              <!-- Khối lớp -->
              <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600 block"
                  >Khối lớp</label
                >
                <select
                  v-model="filters.grade_id"
                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:border-[#7c3aed] focus:ring-1 focus:ring-[#7c3aed] cursor-pointer"
                  @change="onGradeSubjectChange"
                >
                  <option value="">Tất cả Khối lớp</option>
                  <option
                    v-for="grade in availableGrades"
                    :key="grade.id"
                    :value="grade.id"
                  >
                    {{ grade.name }}
                  </option>
                </select>
              </div>

              <!-- Bộ môn -->
              <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600 block"
                  >Bộ môn</label
                >
                <select
                  v-model="filters.subject_id"
                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:border-[#7c3aed] focus:ring-1 focus:ring-[#7c3aed] cursor-pointer"
                  @change="onGradeSubjectChange"
                >
                  <option value="">Tất cả Bộ môn</option>
                  <option
                    v-for="subject in availableSubjects"
                    :key="subject.id"
                    :value="subject.id"
                  >
                    {{ subject.name }}
                  </option>
                </select>
              </div>

              <!-- Độ khó -->
              <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600 block"
                  >Độ khó</label
                >
                <select
                  v-model="filters.difficulty"
                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:border-[#7c3aed] focus:ring-1 focus:ring-[#7c3aed] cursor-pointer"
                  @change="applyFilter"
                >
                  <option value="">Tất cả Độ khó</option>
                  <option value="easy">Dễ (Nhận biết)</option>
                  <option value="medium">Vừa (Thông hiểu)</option>
                  <option value="hard">Khó (Vận dụng)</option>
                </select>
              </div>

              <!-- Chủ đề (nếu có topics) -->
              <div v-if="topicsList.length > 0" class="space-y-1">
                <label class="text-xs font-bold text-slate-600 block"
                  >Chủ đề</label
                >
                <select
                  v-model="filters.topic_name"
                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:border-[#7c3aed] focus:ring-1 focus:ring-[#7c3aed] cursor-pointer"
                  @change="applyFilter"
                >
                  <option value="">Tất cả Chủ đề</option>
                  <option
                    v-for="top in topicsList"
                    :key="top.topic_name"
                    :value="top.topic_name"
                  >
                    {{ top.topic_name }} ({{ top.total_questions }} câu)
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- KHU VỰC THỐNG KÊ CÂU ĐÃ CHỌN (Bottom of Sidebar) -->
          <div
            class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm space-y-3"
          >
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-700"
                >Đã chọn cho bài Quiz:</span
              >
              <span
                class="rounded-full bg-[#7c3aed] px-2.5 py-0.5 text-xs font-black text-white shadow-xs"
              >
                {{ localSelectedIds.length }} câu
              </span>
            </div>

            <div
              v-if="localSelectedIds.length > 0"
              class="flex items-center justify-between text-[11px] text-slate-500 font-medium px-0.5"
            >
              <span
                >Cá nhân:
                <strong class="text-slate-800">{{
                  myBankSelectedCount
                }}</strong></span
              >
              <span>•</span>
              <span
                >Dùng chung:
                <strong class="text-slate-800">{{
                  publicBankSelectedCount
                }}</strong></span
              >
            </div>

            <button
              type="button"
              class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#7c3aed] hover:bg-[#6d28d9] px-4 py-3 text-xs font-bold text-white shadow-md shadow-violet-500/20 transition active:scale-[0.98] disabled:opacity-40 disabled:pointer-events-none cursor-pointer"
              :disabled="localSelectedIds.length === 0"
              @click="handleConfirm"
            >
              <Check :size="14" :stroke-width="2.5" />
              <span>Thêm {{ localSelectedIds.length }} câu vào bài Quiz</span>
            </button>
          </div>
        </aside>

        <!-- -------------------------------------------------------
             RIGHT MAIN CONTENT AREA (Scrollable)
        ------------------------------------------------------- -->
        <main
          class="flex-1 flex flex-col min-w-0 bg-slate-50/40 overflow-hidden"
        >
          <!-- TOP SEARCH & INFO BAR -->
          <div
            class="p-3.5 sm:px-6 sm:py-3.5 border-b border-slate-200/80 bg-white flex flex-wrap items-center justify-between gap-3 shrink-0"
          >
            <!-- Search input -->
            <div class="relative flex-1 min-w-[240px] max-w-xl">
              <Search
                :size="15"
                class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"
              />
              <input
                v-model="filters.search"
                type="text"
                class="w-full rounded-xl border border-slate-200 bg-slate-50/60 pl-10 pr-4 py-2 text-xs font-medium text-slate-800 placeholder-slate-400 outline-none focus:border-[#7c3aed] focus:bg-white transition"
                placeholder="Nhập từ khóa tìm kiếm nội dung câu hỏi..."
                @input="onSearchInput"
                @keyup.enter="applyFilter"
              />
            </div>

            <!-- Stats & Action buttons -->
            <div class="flex items-center gap-3">
              <span class="text-xs text-slate-500 font-medium">
                Tìm thấy:
                <strong class="text-slate-900 font-bold">{{
                  pagination.total
                }}</strong>
                câu hỏi
              </span>

              <button
                type="button"
                class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-700 shadow-xs transition active:scale-[0.98] cursor-pointer"
                title="Làm mới danh sách"
                @click="loadQuestions"
              >
                <RotateCcw :size="12" :class="{ 'animate-spin': isLoading }" />
                <span>Làm mới</span>
              </button>

              <button
                v-if="selectableQuestions.length > 0"
                type="button"
                class="text-[#7c3aed] hover:underline font-bold text-xs inline-flex items-center gap-1 cursor-pointer hidden md:inline-flex"
                @click="toggleSelectAllCurrentPage"
              >
                <component
                  :is="isCurrentPageAllSelected ? X : Check"
                  :size="13"
                />
                <span>{{
                  isCurrentPageAllSelected ? "Bỏ chọn trang" : "Chọn trang này"
                }}</span>
              </button>
            </div>
          </div>

          <!-- QUESTION CARDS LIST -->
          <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-3.5">
            <!-- Loading State -->
            <div
              v-if="isLoading"
              class="flex flex-col items-center justify-center gap-3 py-20 text-xs font-bold text-slate-400"
            >
              <div
                class="h-8 w-8 animate-spin rounded-full border-2 border-[#7c3aed] border-t-transparent"
              ></div>
              <span>Đang tải danh sách câu hỏi...</span>
            </div>

            <!-- Empty State -->
            <div
              v-else-if="questions.length === 0"
              class="flex flex-col items-center justify-center py-20 text-center text-xs text-slate-500"
            >
              <Search :size="40" class="text-slate-300 mb-2" />
              <p class="font-bold text-sm text-slate-800">
                Không tìm thấy câu hỏi phù hợp
              </p>
              <p class="mt-1 text-slate-400">
                Hãy thử điều chỉnh lại bộ lọc hoặc từ khóa tìm kiếm.
              </p>
            </div>

            <!-- Question Cards -->
            <template v-else>
              <div
                v-for="q in questions"
                :key="q.id"
                class="rounded-2xl border bg-white p-4 sm:p-5 transition-all duration-150 relative select-none"
                :class="[
                  isQuestionDisabled(q.id)
                    ? 'opacity-65 border-slate-200 bg-slate-50/80 cursor-not-allowed'
                    : localSelectedIds.includes(q.id)
                      ? 'border-violet-400/80 bg-violet-50/25 ring-1 ring-violet-400/30 shadow-sm cursor-pointer'
                      : 'border-slate-200/90 hover:border-slate-300 hover:shadow-xs cursor-pointer',
                ]"
                @click="toggleSelectQuestion(q)"
              >
                <!-- CARD HEADER: Checkbox + Badges + Right Action -->
                <div class="flex items-center justify-between gap-3 mb-2.5">
                  <div class="flex flex-wrap items-center gap-2 min-w-0">
                    <!-- Checkbox Box -->
                    <div
                      class="flex h-5 w-5 shrink-0 items-center justify-center rounded-lg border transition-all duration-150"
                      :class="[
                        isQuestionDisabled(q.id)
                          ? 'border-slate-300 bg-slate-200 text-slate-400'
                          : localSelectedIds.includes(q.id)
                            ? 'border-[#7c3aed] bg-[#7c3aed] text-white shadow-xs'
                            : 'border-slate-300 bg-white hover:border-[#7c3aed]',
                      ]"
                    >
                      <Check
                        v-if="
                          isQuestionDisabled(q.id) ||
                          localSelectedIds.includes(q.id)
                        "
                        :size="13"
                        :stroke-width="3"
                      />
                    </div>

                    <!-- Question ID -->
                    <span class="font-mono text-xs font-bold text-slate-700"
                      >#{{ q.id }}</span
                    >

                    <!-- Privacy Badge -->
                    <span
                      class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-bold"
                      :class="
                        activeTab === 'my_bank'
                          ? 'bg-amber-100/80 text-amber-800 border border-amber-200'
                          : 'bg-indigo-100/80 text-indigo-800 border border-indigo-200'
                      "
                    >
                      <component
                        :is="activeTab === 'my_bank' ? Lock : Globe"
                        :size="11"
                      />
                      <span>{{
                        activeTab === "my_bank" ? "Riêng tư" : "Công khai"
                      }}</span>
                    </span>

                    <!-- Difficulty Badge -->
                    <span
                      class="rounded-md px-2 py-0.5 text-[10px] font-bold border"
                      :class="getDifficultyClass(q.difficulty)"
                    >
                      {{ getDifficultyLabel(q.difficulty) }}
                    </span>

                    <!-- Subject / Grade -->
                    <span
                      v-if="q.subject_name || q.grade_name"
                      class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700 border border-slate-200 truncate max-w-[180px]"
                    >
                      {{
                        [q.subject_name, q.grade_name]
                          .filter(Boolean)
                          .join(" • ")
                      }}
                    </span>

                    <!-- Topic -->
                    <span
                      v-if="q.topic_name"
                      class="rounded-md bg-purple-50 text-purple-700 border border-purple-200/80 px-2 py-0.5 text-[10px] font-bold truncate max-w-[160px]"
                    >
                      #{{ q.topic_name }}
                    </span>

                    <!-- Has Image Badge -->
                    <span
                      v-if="q.image_url"
                      class="rounded-md bg-sky-50 text-sky-700 border border-sky-200/80 px-2 py-0.5 text-[10px] font-bold inline-flex items-center gap-1"
                    >
                      🖼️ Có ảnh
                    </span>
                  </div>

                  <!-- Right Action Button / Status -->
                  <div class="shrink-0">
                    <span
                      v-if="isQuestionDisabled(q.id)"
                      class="inline-flex items-center gap-1 rounded-lg bg-slate-200/80 text-slate-500 px-2.5 py-1 text-xs font-bold border border-slate-300"
                    >
                      <Check :size="12" />
                      <span>Đã có trong đề</span>
                    </span>

                    <button
                      v-else-if="localSelectedIds.includes(q.id)"
                      type="button"
                      class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 text-emerald-800 px-2.5 py-1 text-xs font-bold border border-emerald-200 transition hover:bg-rose-100 hover:text-rose-700 hover:border-rose-200 cursor-pointer"
                      @click.stop="toggleSelectQuestion(q)"
                    >
                      <Check :size="13" :stroke-width="2.5" />
                      <span>Đã chọn</span>
                    </button>
                  </div>
                </div>

                <!-- CARD QUESTION CONTENT -->
                <div
                  class="text-sm font-semibold text-slate-900 leading-relaxed my-2"
                >
                  {{ q.content || q.text }}
                </div>

                <!-- ANSWERS AREA (COLLAPSIBLE / EXPANDABLE) -->
                <div
                  v-if="revealedQuestionIds.has(q.id)"
                  class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-3 pt-3 border-t border-slate-100 animate-fade-in"
                >
                  <div
                    v-for="(ans, aIdx) in getQuestionAnswers(q)"
                    :key="ans.id || aIdx"
                    class="flex items-center gap-2.5 rounded-xl border px-3 py-2 text-xs transition"
                    :class="
                      isAnswerCorrect(q, ans, aIdx)
                        ? 'border-emerald-500 bg-emerald-50 text-emerald-950 font-bold shadow-xs'
                        : 'border-slate-200 bg-slate-50/50 text-slate-700'
                    "
                  >
                    <span
                      class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-xs font-bold transition"
                      :class="
                        isAnswerCorrect(q, ans, aIdx)
                          ? 'bg-emerald-600 text-white'
                          : 'bg-white text-slate-700 border border-slate-200'
                      "
                    >
                      {{
                        ans.answer_key ||
                        ans.key ||
                        String.fromCharCode(65 + aIdx)
                      }}
                    </span>
                    <span class="flex-1 break-words">{{
                      ans.content || ans.text
                    }}</span>
                    <Check
                      v-if="isAnswerCorrect(q, ans, aIdx)"
                      :size="14"
                      class="text-emerald-600 shrink-0"
                      :stroke-width="2.5"
                    />
                  </div>
                </div>

                <!-- Toggle Reveal Button -->
                <div class="flex items-center justify-end mt-2 pt-1">
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-[#7c3aed] transition cursor-pointer py-1 px-2.5 rounded-lg hover:bg-slate-100"
                    @click.stop="toggleRevealAnswer(q)"
                  >
                    <Loader2
                      v-if="loadingAnswerId === q.id"
                      :size="13"
                      class="animate-spin text-[#7c3aed]"
                    />
                    <EyeOff
                      v-else-if="revealedQuestionIds.has(q.id)"
                      :size="13"
                      class="text-emerald-600"
                    />
                    <Eye v-else :size="13" class="text-slate-400" />
                    <span>{{
                      revealedQuestionIds.has(q.id) ? "Ẩn đáp án" : "Xem đáp án"
                    }}</span>
                  </button>
                </div>
              </div>

              <!-- PAGINATION CONTROLS -->
              <div
                v-if="pagination.last_page > 1"
                class="flex items-center justify-between border-t border-slate-200 pt-4 pb-2 text-xs font-bold text-slate-600"
              >
                <button
                  type="button"
                  class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-3.5 py-2 disabled:opacity-40 disabled:pointer-events-none transition cursor-pointer"
                  :disabled="pagination.current_page <= 1"
                  @click="changePage(pagination.current_page - 1)"
                >
                  <ChevronLeft :size="14" />
                  <span>Trang trước</span>
                </button>

                <span class="text-slate-800 font-bold">
                  Trang {{ pagination.current_page }} /
                  {{ pagination.last_page }}
                </span>

                <button
                  type="button"
                  class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-3.5 py-2 disabled:opacity-40 disabled:pointer-events-none transition cursor-pointer"
                  :disabled="pagination.current_page >= pagination.last_page"
                  @click="changePage(pagination.current_page + 1)"
                >
                  <span>Trang sau</span>
                  <ChevronRight :size="14" />
                </button>
              </div>
            </template>
          </div>
        </main>
      </div>
    </div>

    <!-- =========================================================
         3. CREATE QUESTION SUB-MODAL (Matching CreateQuestionView)
    ========================================================= -->
    <div
      v-if="isCreateQuestionModalOpen"
      class="fixed inset-0 z-60 flex items-center justify-center bg-slate-900/60 p-3 sm:p-6 backdrop-blur-md animate-fade-in"
      @click.self="isCreateQuestionModalOpen = false"
    >
      <div
        class="relative flex h-[90vh] max-h-[820px] w-full max-w-3xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl"
      >
        <!-- Modal Header -->
        <div
          class="flex items-center justify-between border-b border-slate-200 px-6 py-4 bg-slate-50/50 shrink-0"
        >
          <div class="flex items-center gap-2.5">
            <h3 class="text-lg font-bold text-slate-900">Tạo câu hỏi mới</h3>
            <span class="inline-block h-2 w-2 rounded-full bg-[#7c3aed]"></span>
          </div>

          <button
            type="button"
            class="flex h-8 w-8 items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
            @click="isCreateQuestionModalOpen = false"
          >
            <X :size="18" />
          </button>
        </div>

        <!-- Modal Form Body -->
        <form
          class="flex-1 overflow-y-auto p-6 space-y-6"
          @submit.prevent="submitCreateQuestion"
        >
          <!-- 1. Nội dung câu hỏi -->
          <div class="space-y-2">
            <label
              class="flex items-center gap-2 text-sm font-bold text-slate-900"
            >
              <span class="h-3.5 w-1 rounded-full bg-[#7c3aed]"></span>
              1. Nội dung câu hỏi
            </label>
            <textarea
              v-model="createForm.content"
              required
              rows="4"
              class="w-full resize-none rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs sm:text-sm font-medium text-slate-900 outline-none transition focus:border-[#7c3aed] focus:ring-1 focus:ring-[#7c3aed]"
              placeholder="Nhập nội dung câu hỏi tại đây..."
            ></textarea>

            <!-- Upload hình ảnh câu hỏi -->
            <QuestionImageUploader
              v-model="createForm.image_url"
              label="Hình ảnh minh họa cho câu hỏi (Tùy chọn)"
            />
          </div>

          <!-- 2. Phân loại -->
          <div class="space-y-3 pt-2 border-t border-slate-100">
            <label
              class="flex items-center gap-2 text-sm font-bold text-slate-900"
            >
              <span class="h-3.5 w-1 rounded-full bg-[#7c3aed]"></span>
              2. Phân loại câu hỏi
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600 block"
                  >Cấp học</label
                >
                <select
                  v-model="createForm.education_level_id"
                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:border-[#7c3aed]"
                  @change="createForm.grade_id = ''"
                >
                  <option value="">Chọn Cấp học</option>
                  <option
                    v-for="level in taxonomyLevels"
                    :key="level.id"
                    :value="level.id"
                  >
                    {{ level.name }}
                  </option>
                </select>
              </div>

              <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600 block"
                  >Khối lớp</label
                >
                <select
                  v-model="createForm.grade_id"
                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:border-[#7c3aed]"
                >
                  <option value="">Chọn Khối lớp</option>
                  <option
                    v-for="grade in createAvailableGrades"
                    :key="grade.id"
                    :value="grade.id"
                  >
                    {{ grade.name }}
                  </option>
                </select>
              </div>

              <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600 block"
                  >Bộ môn</label
                >
                <select
                  v-model="createForm.subject_id"
                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:border-[#7c3aed]"
                >
                  <option value="">Chọn Bộ môn</option>
                  <option
                    v-for="subject in createAvailableSubjects"
                    :key="subject.id"
                    :value="subject.id"
                  >
                    {{ subject.name }}
                  </option>
                </select>
              </div>

              <div class="space-y-1">
                <label class="text-xs font-bold text-slate-600 block"
                  >Độ khó</label
                >
                <select
                  v-model="createForm.difficulty"
                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:border-[#7c3aed]"
                >
                  <option value="easy">Dễ (Nhận biết)</option>
                  <option value="medium">Vừa (Thông hiểu)</option>
                  <option value="hard">Khó (Vận dụng)</option>
                </select>
              </div>
            </div>

            <div class="space-y-1">
              <label class="text-xs font-bold text-slate-600 block"
                >Tên chủ đề (tùy chọn)</label
              >
              <input
                v-model="createForm.topic_name"
                type="text"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:border-[#7c3aed]"
                placeholder="VD: Hàm số, Hình học không gian..."
              />
            </div>
          </div>

          <!-- 3. Loại câu hỏi & Đáp án -->
          <div class="space-y-3 pt-2 border-t border-slate-100">
            <div class="flex items-center justify-between">
              <label
                class="flex items-center gap-2 text-sm font-bold text-slate-900"
              >
                <span class="h-3.5 w-1 rounded-full bg-[#7c3aed]"></span>
                3. Các phương án đáp án
              </label>

              <button
                v-if="createForm.answers.length < 6"
                type="button"
                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-[#7c3aed] hover:bg-violet-50 transition cursor-pointer"
                @click="addCreateAnswerChoice"
              >
                <Plus :size="13" />
                <span>Thêm đáp án</span>
              </button>
            </div>

            <div class="space-y-2.5">
              <div
                v-for="(ans, idx) in createForm.answers"
                :key="idx"
                class="flex items-center gap-2.5 rounded-xl border p-2.5 transition"
                :class="
                  ans.is_correct
                    ? 'border-emerald-300 bg-emerald-50/70'
                    : 'border-slate-200 bg-slate-50/50'
                "
              >
                <span
                  class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
                  :class="
                    ans.is_correct
                      ? 'bg-emerald-500 text-white'
                      : 'bg-white border border-slate-200 text-slate-700'
                  "
                >
                  {{ ans.key }}
                </span>

                <input
                  v-model="ans.content"
                  required
                  class="flex-1 min-w-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 outline-none focus:border-[#7c3aed]"
                  :placeholder="`Nội dung đáp án ${ans.key}...`"
                />

                <label
                  class="flex shrink-0 items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-bold cursor-pointer select-none transition"
                  :class="
                    ans.is_correct
                      ? 'border-emerald-300 bg-emerald-100 text-emerald-800'
                      : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300'
                  "
                >
                  <input
                    type="radio"
                    name="create_correct_ans"
                    :checked="ans.is_correct"
                    class="accent-emerald-600 cursor-pointer"
                    @change="setCreateCorrectAnswer(idx)"
                  />
                  <span>{{
                    ans.is_correct ? "Đáp án đúng" : "Đánh dấu đúng"
                  }}</span>
                </label>

                <button
                  v-if="createForm.answers.length > 2"
                  type="button"
                  class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                  title="Xóa đáp án này"
                  @click="removeCreateAnswerChoice(idx)"
                >
                  <X :size="14" />
                </button>
              </div>
            </div>
          </div>

          <!-- 4. Banner Lưu trữ -->
          <div
            class="rounded-xl border border-amber-200 bg-amber-50/70 p-3.5 flex items-start gap-2.5"
          >
            <Lock :size="16" class="text-amber-600 shrink-0 mt-0.5" />
            <div class="text-xs text-slate-600 leading-relaxed">
              Câu hỏi mới sẽ được lưu vào
              <strong>Kho câu hỏi cá nhân</strong> của bạn và tự động chọn vào
              đề thi này.
            </div>
          </div>

          <!-- Actions -->
          <div
            class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200"
          >
            <button
              type="button"
              class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer"
              @click="isCreateQuestionModalOpen = false"
            >
              Hủy
            </button>

            <button
              type="submit"
              class="rounded-xl bg-[#7c3aed] hover:bg-[#6d28d9] px-6 py-2.5 text-xs font-bold text-white shadow-md shadow-violet-500/20 transition active:scale-[0.98] disabled:opacity-50 cursor-pointer"
              :disabled="isSubmittingCreate"
            >
              {{
                isSubmittingCreate ? "Đang lưu..." : "Lưu câu hỏi & chọn luôn"
              }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref, watch } from "vue";
import {
  X,
  Globe,
  Search,
  RotateCcw,
  Check,
  ChevronLeft,
  ChevronRight,
  Eye,
  EyeOff,
  Loader2,
  Plus,
  Folder,
  Lock,
  Layers,
} from "lucide-vue-next";
import { myQuestionsApi, questionsBankApi, taxonomyApi } from "@/services/api";
import QuestionImage from "@/components/question/QuestionImage.vue";
import QuestionImageUploader from "@/components/question/QuestionImageUploader.vue";

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  isOpen: {
    type: Boolean,
    default: false,
  },
  mode: {
    type: String,
    default: "manual",
  },
  initialFilters: {
    type: Object,
    default: () => ({}),
  },
  disabledIds: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["update:modelValue", "close", "confirm"]);
const showToast = inject("showToast", null);

/* =========================================================
   STATE
========================================================= */
const activeTab = ref("my_bank"); // 'my_bank' | 'public_bank'
const localSelectedIds = ref([]);
const selectedQuestionCache = ref(new Map());

const revealedQuestionIds = ref(new Set());
const revealedAnswersMap = ref(new Map());
const loadingAnswerId = ref(null);

const questions = ref([]);
const isLoading = ref(false);

const taxonomyLevels = ref([]);
const allSubjects = ref([]);
const topicsList = ref([]);

const filters = reactive({
  search: "",
  education_level_id: "",
  grade_id: "",
  subject_id: "",
  topic_name: "",
  difficulty: "",
});

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 12,
  total: 0,
});

/* =========================================================
   CREATE QUESTION SUB-MODAL STATE
========================================================= */
const isCreateQuestionModalOpen = ref(false);
const isSubmittingCreate = ref(false);
const createForm = reactive({
  content: "",
  image_url: "",
  education_level_id: "",
  grade_id: "",
  subject_id: "",
  topic_name: "",
  difficulty: "medium",
  type: "single_choice",
  is_public: false,
  answers: [
    { key: "A", content: "", is_correct: true },
    { key: "B", content: "", is_correct: false },
    { key: "C", content: "", is_correct: false },
    { key: "D", content: "", is_correct: false },
  ],
});

/* =========================================================
   COMPUTED COUNTS & STATS
========================================================= */
const myBankSelectedCount = computed(() => {
  return localSelectedIds.value.filter((id) => {
    const item = selectedQuestionCache.value.get(id);
    return item?.source === "my_bank";
  }).length;
});

const publicBankSelectedCount = computed(() => {
  return localSelectedIds.value.filter((id) => {
    const item = selectedQuestionCache.value.get(id);
    return item?.source === "public_bank";
  }).length;
});

const hasActiveFilters = computed(() => {
  return Boolean(
    filters.search ||
    filters.education_level_id ||
    filters.grade_id ||
    filters.subject_id ||
    filters.topic_name ||
    filters.difficulty,
  );
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

const createAvailableGrades = computed(() => {
  if (!createForm.education_level_id) {
    return taxonomyLevels.value.flatMap((l) => l.grades || []);
  }
  const level = taxonomyLevels.value.find(
    (l) => l.id === Number(createForm.education_level_id),
  );
  return level ? level.grades || [] : [];
});

const createAvailableSubjects = computed(() => {
  if (!createForm.grade_id) {
    return allSubjects.value;
  }
  const grade = createAvailableGrades.value.find(
    (g) => g.id === Number(createForm.grade_id),
  );
  return grade && grade.subjects && grade.subjects.length
    ? grade.subjects
    : allSubjects.value;
});

const isQuestionDisabled = (questionId) => {
  return (
    Array.isArray(props.disabledIds) && props.disabledIds.includes(questionId)
  );
};

const selectableQuestions = computed(() => {
  return questions.value.filter((q) => !isQuestionDisabled(q.id));
});

const isCurrentPageAllSelected = computed(() => {
  if (selectableQuestions.value.length === 0) return false;
  return selectableQuestions.value.every((q) =>
    localSelectedIds.value.includes(q.id),
  );
});

/* =========================================================
   DIFFICULTY HELPERS
========================================================= */
const getDifficultyClass = (diff) => {
  switch (diff) {
    case "easy":
      return "bg-emerald-50 text-emerald-700 border-emerald-200";
    case "hard":
      return "bg-rose-50 text-rose-700 border-rose-200";
    default:
      return "bg-amber-50 text-amber-700 border-amber-200";
  }
};

const getDifficultyLabel = (diff) => {
  switch (diff) {
    case "easy":
      return "Dễ (Nhận biết)";
    case "hard":
      return "Khó (Vận dụng)";
    default:
      return "Vừa (Thông hiểu)";
  }
};

/* =========================================================
   DATA FETCHING
========================================================= */
const fetchTaxonomy = async () => {
  if (taxonomyLevels.value.length > 0) return;
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

const loadQuestions = async () => {
  isLoading.value = true;
  try {
    const params = {
      search: filters.search ? filters.search.trim() : undefined,
      education_level_id: filters.education_level_id || undefined,
      grade_id: filters.grade_id || undefined,
      subject_id: filters.subject_id || undefined,
      topic_name: filters.topic_name || undefined,
      difficulty: filters.difficulty || undefined,
      page: pagination.current_page,
      per_page: pagination.per_page,
    };

    const apiCall =
      activeTab.value === "my_bank"
        ? myQuestionsApi.fetchBank(params)
        : questionsBankApi.fetchBank(params);

    const res = await apiCall;
    if (res && Array.isArray(res.items)) {
      questions.value = res.items;
      pagination.total = res.total ?? res.items.length;
      pagination.current_page = res.currentPage ?? 1;
      pagination.last_page = res.lastPage ?? 1;
      pagination.per_page = res.perPage ?? pagination.per_page;
    } else if (Array.isArray(res)) {
      questions.value = res;
      pagination.total = res.length;
      pagination.current_page = 1;
      pagination.last_page = 1;
    } else {
      questions.value = [];
      pagination.total = 0;
      pagination.current_page = 1;
      pagination.last_page = 1;
    }

    // Cache current question items
    questions.value.forEach((q) => {
      if (
        localSelectedIds.value.includes(q.id) &&
        !selectedQuestionCache.value.has(q.id)
      ) {
        selectedQuestionCache.value.set(q.id, {
          ...q,
          source: activeTab.value,
        });
      }
    });
  } catch (e) {
    console.error("Không tải được danh sách câu hỏi:", e);
    questions.value = [];
  } finally {
    isLoading.value = false;
  }
};

/* =========================================================
   HYDRATE MISSING SELECTED QUESTIONS CACHE
========================================================= */
const hydrateSelectedCache = async (ids) => {
  const missingIds = ids.filter((id) => !selectedQuestionCache.value.has(id));
  if (missingIds.length === 0) return;

  try {
    const publicRes = await questionsBankApi.fetchBank({
      ids: missingIds,
      per_page: missingIds.length,
    });
    const publicItems =
      publicRes?.items || (Array.isArray(publicRes) ? publicRes : []);
    publicItems.forEach((q) => {
      selectedQuestionCache.value.set(q.id, { ...q, source: "public_bank" });
    });

    const stillMissing = ids.filter(
      (id) => !selectedQuestionCache.value.has(id),
    );
    if (stillMissing.length > 0) {
      const userRes = await myQuestionsApi.fetchBank({
        ids: stillMissing,
        per_page: stillMissing.length,
      });
      const userItems =
        userRes?.items || (Array.isArray(userRes) ? userRes : []);
      userItems.forEach((q) => {
        selectedQuestionCache.value.set(q.id, { ...q, source: "my_bank" });
      });
    }
  } catch (e) {
    console.error("Không thể nạp thông tin chi tiết câu hỏi đã chọn:", e);
  }
};

/* =========================================================
   TAB SWITCHING & FILTERS
========================================================= */
const switchTab = (tab) => {
  if (activeTab.value === tab) return;
  activeTab.value = tab;
  pagination.current_page = 1;
  loadQuestions();
};

const onLevelChange = () => {
  filters.grade_id = "";
  onGradeSubjectChange();
};

const onGradeSubjectChange = () => {
  fetchTopicsList();
  applyFilter();
};

let searchDebounce = null;
const onSearchInput = () => {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => {
    applyFilter();
  }, 400);
};

const applyFilter = () => {
  pagination.current_page = 1;
  loadQuestions();
};

const resetFilters = () => {
  filters.search = "";
  filters.education_level_id = "";
  filters.grade_id = "";
  filters.subject_id = "";
  filters.topic_name = "";
  filters.difficulty = "";
  pagination.current_page = 1;
  fetchTopicsList();
  loadQuestions();
};

const changePage = (page) => {
  if (
    page < 1 ||
    page > pagination.last_page ||
    page === pagination.current_page
  )
    return;
  pagination.current_page = page;
  loadQuestions();
};

/* =========================================================
   SELECTION HANDLING
========================================================= */
const toggleSelectQuestion = (question) => {
  if (isQuestionDisabled(question.id)) return;
  const index = localSelectedIds.value.indexOf(question.id);
  if (index > -1) {
    localSelectedIds.value.splice(index, 1);
  } else {
    localSelectedIds.value.push(question.id);
    selectedQuestionCache.value.set(question.id, {
      ...question,
      source: activeTab.value,
    });
  }
};

const toggleSelectAllCurrentPage = () => {
  const selectable = selectableQuestions.value;
  if (selectable.length === 0) return;
  const selectableIds = selectable.map((q) => q.id);
  if (isCurrentPageAllSelected.value) {
    localSelectedIds.value = localSelectedIds.value.filter(
      (id) => !selectableIds.includes(id),
    );
  } else {
    selectable.forEach((q) => {
      if (!localSelectedIds.value.includes(q.id)) {
        localSelectedIds.value.push(q.id);
        selectedQuestionCache.value.set(q.id, {
          ...q,
          source: activeTab.value,
        });
      }
    });
  }
};

/* =========================================================
   REVEAL ANSWER DETAIL LOGIC & CACHING
========================================================= */
const toggleRevealAnswer = async (question) => {
  const qId = question.id;
  if (revealedQuestionIds.value.has(qId)) {
    revealedQuestionIds.value.delete(qId);
    return;
  }

  const hasDirectAnswersWithCorrect =
    Array.isArray(question.answers) &&
    question.answers.length > 0 &&
    question.answers.some(
      (a) => a.is_correct !== undefined && a.is_correct !== null,
    );

  if (hasDirectAnswersWithCorrect) {
    revealedQuestionIds.value.add(qId);
    return;
  }

  if (!revealedAnswersMap.value.has(qId)) {
    loadingAnswerId.value = qId;
    try {
      const detail = await questionsBankApi.getQuestion(qId);
      if (detail) {
        revealedAnswersMap.value.set(qId, detail);
      }
    } catch (e) {
      console.error("Không thể tải đáp án câu hỏi:", e);
    } finally {
      loadingAnswerId.value = null;
    }
  }

  revealedQuestionIds.value.add(qId);
};

const getQuestionAnswers = (question) => {
  const cached = revealedAnswersMap.value.get(question.id);
  if (cached && Array.isArray(cached.answers) && cached.answers.length > 0) {
    return cached.answers;
  }
  return question.answers || [];
};

const isAnswerCorrect = (question, ans, aIdx) => {
  if (ans.is_correct !== undefined && ans.is_correct !== null) {
    return Boolean(ans.is_correct);
  }

  const detail = revealedAnswersMap.value.get(question.id);
  if (!detail || !Array.isArray(detail.answers)) return false;

  const key = ans.answer_key || ans.key || String.fromCharCode(65 + aIdx);
  const matched = detail.answers.find(
    (a) => (ans.id && a.id === ans.id) || a.key === key || a.answer_key === key,
  );
  return Boolean(matched?.is_correct);
};

/* =========================================================
   CREATE QUESTION SUB-MODAL LOGIC
========================================================= */
const openCreateQuestionModal = () => {
  createForm.content = "";
  createForm.education_level_id = filters.education_level_id || "";
  createForm.grade_id = filters.grade_id || "";
  createForm.subject_id = filters.subject_id || "";
  createForm.topic_name = filters.topic_name || "";
  createForm.difficulty = filters.difficulty || "medium";
  createForm.type = "single_choice";
  createForm.answers = [
    { key: "A", content: "", is_correct: true },
    { key: "B", content: "", is_correct: false },
    { key: "C", content: "", is_correct: false },
    { key: "D", content: "", is_correct: false },
  ];
  isCreateQuestionModalOpen.value = true;
};

const addCreateAnswerChoice = () => {
  if (createForm.answers.length >= 6) return;
  const nextKey = String.fromCharCode(65 + createForm.answers.length);
  createForm.answers.push({ key: nextKey, content: "", is_correct: false });
};

const removeCreateAnswerChoice = (index) => {
  if (createForm.answers.length <= 2) return;
  const removedWasCorrect = createForm.answers[index]?.is_correct;
  createForm.answers.splice(index, 1);
  createForm.answers.forEach((ans, i) => {
    ans.key = String.fromCharCode(65 + i);
  });
  if (removedWasCorrect && createForm.answers.length > 0) {
    createForm.answers[0].is_correct = true;
  }
};

const setCreateCorrectAnswer = (index) => {
  createForm.answers.forEach((ans, i) => {
    ans.is_correct = i === index;
  });
};

const submitCreateQuestion = async () => {
  if (!createForm.content.trim()) {
    if (showToast) showToast("Vui lòng nhập nội dung câu hỏi.", "error");
    return;
  }
  for (let i = 0; i < createForm.answers.length; i++) {
    if (!createForm.answers[i].content.trim()) {
      if (showToast)
        showToast(
          `Vui lòng nhập nội dung cho Đáp án ${createForm.answers[i].key}.`,
          "error",
        );
      return;
    }
  }
  if (!createForm.answers.some((a) => a.is_correct)) {
    if (showToast) showToast("Vui lòng đánh dấu chọn 1 đáp án đúng.", "error");
    return;
  }

  isSubmittingCreate.value = true;
  try {
    const payload = {
      content: createForm.content.trim(),
      image_url: createForm.image_url ? createForm.image_url.trim() : null,
      difficulty: createForm.difficulty,
      education_level_id: createForm.education_level_id || null,
      grade_id: createForm.grade_id || null,
      subject_id: createForm.subject_id || null,
      topic_name: createForm.topic_name ? createForm.topic_name.trim() : null,
      is_public: false,
      answers: createForm.answers.map((a) => ({
        content: a.content.trim(),
        key: a.key,
        is_correct: a.is_correct,
      })),
    };

    const created = await myQuestionsApi.createQuestion(payload);
    if (showToast) showToast("Tạo câu hỏi mới thành công!", "success");

    // Switch to my_bank if not already
    activeTab.value = "my_bank";
    await loadQuestions();

    // Auto-select the newly created question
    const createdId = created?.id || created?.data?.id;
    if (createdId) {
      if (!localSelectedIds.value.includes(createdId)) {
        localSelectedIds.value.push(createdId);
      }
      selectedQuestionCache.value.set(createdId, {
        id: createdId,
        content: payload.content,
        image_url: payload.image_url,
        difficulty: payload.difficulty,
        subject_id: payload.subject_id,
        source: "my_bank",
        answers: payload.answers,
      });
    }

    isCreateQuestionModalOpen.value = false;
  } catch (err) {
    const msg =
      err.response?.data?.message ||
      err.message ||
      "Tạo câu hỏi thất bại. Vui lòng kiểm tra lại.";
    if (showToast) showToast(msg, "error");
  } finally {
    isSubmittingCreate.value = false;
  }
};

/* =========================================================
   ACTIONS (CANCEL / CONFIRM)
========================================================= */
const handleCancel = () => {
  emit("close");
};

const handleConfirm = () => {
  const selectedList = localSelectedIds.value.map((id) => {
    const cached = selectedQuestionCache.value.get(id);
    return (
      cached || {
        id,
        content: `Câu hỏi #${id}`,
        difficulty: "medium",
        source: "public_bank",
      }
    );
  });

  emit("update:modelValue", [...localSelectedIds.value]);
  emit("confirm", {
    ids: [...localSelectedIds.value],
    questions: selectedList,
    myBankCount: myBankSelectedCount.value,
    publicBankCount: publicBankSelectedCount.value,
  });
  emit("close");
};

const applyInitialFilters = () => {
  if (props.initialFilters && typeof props.initialFilters === "object") {
    if (props.initialFilters.education_level_id !== undefined) {
      filters.education_level_id =
        props.initialFilters.education_level_id || "";
    }
    if (props.initialFilters.grade_id !== undefined) {
      filters.grade_id = props.initialFilters.grade_id || "";
    }
    if (props.initialFilters.subject_id !== undefined) {
      filters.subject_id = props.initialFilters.subject_id || "";
    }
    if (props.initialFilters.topic_name !== undefined) {
      filters.topic_name = props.initialFilters.topic_name || "";
    }
    if (props.initialFilters.difficulty !== undefined) {
      filters.difficulty = props.initialFilters.difficulty || "";
    }
    if (props.initialFilters.search !== undefined) {
      filters.search = props.initialFilters.search || "";
    }
  }
};

/* =========================================================
   WATCHERS & LIFECYCLE
========================================================= */
watch(
  () => props.isOpen,
  async (isOpen) => {
    if (isOpen) {
      localSelectedIds.value = Array.isArray(props.modelValue)
        ? [...props.modelValue]
        : [];
      applyInitialFilters();
      await fetchTaxonomy();
      await fetchTopicsList();
      await hydrateSelectedCache(localSelectedIds.value);
      await loadQuestions();
    }
  },
  { immediate: true },
);

onMounted(async () => {
  if (props.isOpen) {
    localSelectedIds.value = Array.isArray(props.modelValue)
      ? [...props.modelValue]
      : [];
    applyInitialFilters();
    await fetchTaxonomy();
    await fetchTopicsList();
    await hydrateSelectedCache(localSelectedIds.value);
    await loadQuestions();
  }
});
</script>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(0.985);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-fade-in {
  animation: fadeIn 0.18s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
