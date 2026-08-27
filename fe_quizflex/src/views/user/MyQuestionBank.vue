<template>
  <section id="my-question-top" class="grid gap-6 py-8">
    <!-- Header Banner -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs">
      <div class="flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div>
          <h1 class="text-3xl font-black tracking-tight text-slate-900">Kho câu hỏi của tôi</h1>
          <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-500">Quản lý độc lập tất cả câu hỏi trắc nghiệm do bạn tạo ra. Chỉnh sửa nội dung, sửa đáp án đúng hoặc chuyển câu hỏi lỗi vào thùng rác an toàn.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50/80 px-4 py-2.5 text-xs font-bold text-rose-500 hover:bg-rose-100 hover:border-rose-300 transition shadow-2xs cursor-pointer active:scale-95"
            @click="openTrashModal"
          >
            <Trash2 :size="14" />
            <span>Thùng rác câu hỏi</span>
            <span v-if="trashCount > 0" class="rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] leading-none text-white font-bold">{{ trashCount }}</span>
          </button>

          <router-link
            to="/dashboard/my-questions/create"
            class="btn-primary inline-flex items-center gap-2 bg-[#7C3AED] hover:bg-[#6D28D9] text-white px-5 py-2.5 rounded-xl font-bold text-xs shadow-md shadow-purple-500/20"
          >
            <Plus :size="14" />
            <span>Tạo câu hỏi</span>
          </router-link>
        </div>
      </div>
    </div>

    <!-- Taxonomy & Search Filter Bar -->
    <article class="rounded-2xl border border-slate-200/80 bg-white p-5 grid gap-4 shadow-2xs">
      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <!-- 1. Cấp học -->
        <div class="relative">
          <GraduationCap :size="16" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-700" />
          <select v-model="filters.education_level_id" class="field !pl-10 text-xs font-medium text-slate-700" @change="onLevelChange">
            <option value="">Tất cả Cấp học</option>
            <option v-for="level in taxonomyLevels" :key="level.id" :value="level.id">{{ level.name }}</option>
          </select>
        </div>

        <!-- 2. Khối lớp -->
        <div class="relative">
          <Users :size="16" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-700" />
          <select v-model="filters.grade_id" class="field !pl-10 text-xs font-medium text-slate-700" @change="onGradeSubjectChange">
            <option value="">Tất cả Khối lớp</option>
            <option v-for="grade in availableGrades" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
          </select>
        </div>

        <!-- 3. Bộ môn -->
        <div class="relative">
          <BookOpen :size="16" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-700" />
          <select v-model="filters.subject_id" class="field !pl-10 text-xs font-medium text-slate-700" @change="onGradeSubjectChange">
            <option value="">Tất cả Bộ môn</option>
            <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
          </select>
        </div>

        <!-- 4. Độ khó -->
        <div class="relative">
          <BarChart2 :size="16" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-700" />
          <select v-model="filters.difficulty" class="field !pl-10 text-xs font-medium text-slate-700" @change="onFilterSubmit">
            <option value="">Tất cả độ khó</option>
            <option value="easy">Dễ (Nhận biết)</option>
            <option value="medium">Vừa (Thông hiểu)</option>
            <option value="hard">Khó (Vận dụng)</option>
          </select>
        </div>
      </div>

      <div class="grid gap-3 xl:grid-cols-[1fr_260px_auto_auto]">
        <div class="relative">
          <Search :size="15" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
          <input v-model="filters.search" class="field !pl-10 text-xs" placeholder="Tìm kiếm theo nội dung câu hỏi..." @keyup.enter="onFilterSubmit" />
        </div>

        <div class="relative">
          <Tag :size="15" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-700" />
          <select v-model="filters.topic_name" class="field !pl-10 text-xs font-medium text-slate-700" @change="onFilterSubmit">
            <option value="">Tất cả Chủ đề</option>
            <option v-for="top in topicsList" :key="top.topic_name" :value="top.topic_name">
              {{ top.topic_name }}
            </option>
          </select>
        </div>

        <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#7C3AED] hover:bg-[#6D28D9] px-5 py-2.5 text-xs font-bold text-white shadow-sm shadow-purple-500/20 transition cursor-pointer active:scale-95" type="button" @click="onFilterSubmit">
          <Filter :size="14" />
          <span>Tìm kiếm & Lọc</span>
        </button>
        <button v-if="hasActiveFilters" class="btn-ghost inline-flex items-center gap-1.5 text-xs text-rose-500 font-bold cursor-pointer" type="button" @click="resetFilters">
          <RotateCcw :size="13" />
          <span>Đặt lại</span>
        </button>
      </div>
    </article>

    <!-- Stats Bar -->
    <div class="flex items-center justify-between text-xs font-semibold text-slate-500 px-1">
      <span>Hiển thị {{ pageStartItem }} - {{ pageEndItem }} / Tổng {{ pagination.total }} câu hỏi sở hữu</span>
    </div>

    <!-- Loading State -->
    <AppLoadingState v-if="isLoading" title="Đang tải kho câu hỏi cá nhân..." message="Vui lòng chờ trong giây lát..." icon="📚" />

    <!-- Error State -->
    <AppErrorState v-else-if="errorMessage" title="Không thể tải kho câu hỏi" :message="errorMessage" @retry="loadQuestions" />

    <!-- Loaded Questions List -->
    <template v-else>
      <!-- Focused Question Banner -->
      <div v-if="focusedQuestionId" class="mb-2">
        <!-- SUCCESS BANNER (When question was just updated) -->
        <div v-if="highlightedUpdatedQuestionId === focusedQuestionId" class="rounded-2xl border border-[#FDE68A] bg-[#FFFBEB] p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-2xs">
          <div class="flex items-start sm:items-center gap-3.5">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#F59E0B] text-white shadow-xs">
              <Check :size="18" class="stroke-[3]" />
            </div>
            <div>
              <h4 class="font-bold text-[#D97706] text-sm sm:text-base">
                Đã lưu thay đổi cho câu hỏi #{{ focusedQuestionId }}
              </h4>
              <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                Nội dung đã được cập nhật thành công. Câu hỏi <strong>chưa được gửi lại cho Admin</strong>. Vui lòng bấm <strong>“Gửi duyệt”</strong> ở thẻ bên dưới để gửi yêu cầu kiểm duyệt lại.
              </p>
            </div>
          </div>
          <button
            type="button"
            class="shrink-0 inline-flex items-center gap-1.5 rounded-xl border border-[#FDE68A] bg-[#FEF3C7]/70 px-4 py-2 text-xs font-bold text-[#D97706] hover:bg-[#FEF3C7] transition active:scale-95 cursor-pointer shadow-2xs"
            @click="clearQuestionFocus"
          >
            <Eye :size="14" class="text-[#D97706]" />
            <span>Xem tất cả câu hỏi trong kho</span>
          </button>
        </div>

        <!-- WARNING BANNER (When user needs to edit reported question) -->
        <div v-else class="rounded-2xl border border-rose-200 bg-rose-50/80 p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-2xs">
          <div class="flex items-start sm:items-center gap-3.5">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-rose-500 text-white shadow-xs">
              <AlertTriangle :size="18" />
            </div>
            <div>
              <h4 class="font-bold text-rose-700 text-sm sm:text-base flex items-center gap-2">
                <span>Đang tập trung xử lý câu hỏi</span>
                <span class="rounded-md bg-rose-200 px-2 py-0.5 text-xs font-black text-rose-900">#{{ focusedQuestionId }}</span>
                <span v-if="focusedQuestionItem?.is_locked_by_admin" class="text-xs text-rose-600 font-semibold">(Admin đã khóa: "{{ focusedQuestionItem.report_reason || 'Vi phạm quy định' }}")</span>
              </h4>
              <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                Vui lòng nhấp nút <strong class="font-bold text-slate-800">"Sửa câu hỏi"</strong> ở thẻ bên dưới để đính chính đáp án hoặc nội dung.
              </p>
            </div>
          </div>
          <button
            type="button"
            class="shrink-0 inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-white px-4 py-2 text-xs font-bold text-rose-700 shadow-2xs hover:bg-rose-50 hover:border-rose-300 transition active:scale-95 cursor-pointer"
            @click="clearQuestionFocus"
          >
            <Eye :size="14" />
            <span>Xem tất cả câu hỏi trong kho</span>
          </button>
        </div>
      </div>

      <!-- SELECTION & BULK ACTION BAR -->
      <div v-if="questions.length > 0" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200/80 bg-white p-3 shadow-2xs">
        <div class="flex items-center gap-3">
          <label class="flex items-center gap-2 text-xs font-semibold text-slate-800 cursor-pointer select-none">
            <input
              type="checkbox"
              :checked="isCurrentPageAllSelected"
              class="h-4 w-4 rounded accent-[#7C3AED] cursor-pointer"
              @change="toggleSelectAllOnPage"
            />
            <span>Chọn tất cả trên trang này</span>
          </label>
          <span v-if="selectedQuestionIds.length > 0" class="rounded-lg bg-purple-100 border border-purple-200 px-2.5 py-0.5 text-xs font-bold text-purple-800">
            Đã chọn {{ selectedQuestionIds.length }} câu hỏi
          </span>
        </div>

        <div v-if="selectedQuestionIds.length > 0" class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-xl bg-[#7C3AED] px-4 py-2 text-xs font-bold text-white shadow-sm shadow-purple-600/25 hover:bg-purple-700 transition disabled:opacity-50 cursor-pointer active:scale-95"
            :disabled="isSubmittingToBank"
            @click="bulkSubmitQuestionsToBank"
          >
            <Send :size="13" />
            <span>Gửi duyệt vào Ngân hàng ({{ selectedQuestionIds.length }} câu)</span>
          </button>
        </div>
      </div>

      <div class="grid gap-3">
        <article
          v-for="q in questions"
          :key="q.id"
          :id="`question-card-${q.id}`"
          class="rounded-2xl border p-5 transition-all duration-200 relative shadow-2xs"
          :class="[
            highlightedUpdatedQuestionId === q.id
              ? 'border border-[#A7F3D0] bg-white'
              : (highlightedQuestionId === q.id
                  ? 'border border-rose-300 bg-white'
                  : 'border border-slate-200 bg-white hover:border-slate-300'),
            q.is_locked_by_admin && highlightedUpdatedQuestionId !== q.id ? 'border-rose-400 bg-rose-50/20' : ''
          ]"
        >
          <!-- Success Banner if updated (Green Bar) -->
          <div v-if="highlightedUpdatedQuestionId === q.id" class="mb-4 flex items-center justify-between rounded-xl bg-[#ECFDF5] px-4 py-3 text-xs text-[#065F46]">
            <div class="flex items-center gap-2.5">
              <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#10B981] text-white">
                <Check :size="13" class="stroke-[3]" />
              </div>
              <span class="font-medium">
                Đã lưu thay đổi nội dung câu hỏi thành công! Vui lòng bấm <strong>“Gửi duyệt”</strong> bên dưới để gửi yêu cầu kiểm duyệt lại cho Admin.
              </span>
            </div>
            <button type="button" class="text-[#10B981] hover:text-[#065F46] rounded-md p-1 transition ml-2 cursor-pointer" @click="clearQuestionFocus" title="Đóng thông báo">
              <X :size="14" />
            </button>
          </div>

          <div class="flex flex-col xl:flex-row xl:items-start justify-between gap-4">
            <div class="flex items-start gap-3 flex-1 min-w-0">
              <!-- Selection checkbox -->
              <input
                type="checkbox"
                :checked="selectedQuestionIds.includes(q.id)"
                class="h-4 w-4 rounded accent-[#7C3AED] mt-1 shrink-0 cursor-pointer"
                @change="toggleSelectQuestion(q.id)"
              />

              <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-1.5 mb-3">
                  <span class="rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-700">#{{ q.id }}</span>
                  <span
                    class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-0.5 text-[11px] font-bold border"
                    :class="{
                      'bg-emerald-50 text-emerald-700 border-emerald-200': q.difficulty === 'easy',
                      'bg-amber-50 text-amber-600 border-amber-200': q.difficulty === 'medium',
                      'bg-rose-50 text-rose-700 border-rose-200': q.difficulty === 'hard'
                    }"
                  >
                    <span
                      class="h-1.5 w-1.5 rounded-full"
                      :class="{
                        'bg-emerald-500': q.difficulty === 'easy',
                        'bg-amber-500': q.difficulty === 'medium',
                        'bg-rose-500': q.difficulty === 'hard'
                      }"
                    ></span>
                    {{ difficultyText(q.difficulty) }}
                  </span>

                  <!-- Status badge for bank submission -->
                  <span v-if="q.bank_submission_status === 'pending'" class="inline-flex items-center gap-1 rounded-md bg-amber-50 text-amber-800 border border-amber-200 px-2.5 py-0.5 text-[11px] font-bold">
                    <Clock :size="12" class="text-amber-600" />
                    <span>Đang chờ duyệt</span>
                  </span>
                  <span v-else-if="q.bank_submission_status === 'approved' || q.is_public" class="inline-flex items-center gap-1 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200 px-2.5 py-0.5 text-[11px] font-bold">
                    <Globe :size="12" class="text-emerald-600" />
                    <span>Đã vào Ngân hàng</span>
                  </span>
                  <span v-else-if="q.bank_submission_status === 'rejected'" class="inline-flex items-center gap-1 rounded-md bg-rose-50 text-rose-800 border border-rose-200 px-2.5 py-0.5 text-[11px] font-bold" :title="q.bank_submission_note ? `Lý do từ chối: ${q.bank_submission_note}` : 'Bị từ chối'">
                    <XCircle :size="12" class="text-rose-600" />
                    <span>Bị từ chối {{ q.bank_submission_note ? `— Lý do: ${q.bank_submission_note}` : '' }}</span>
                  </span>
                  <span v-else class="inline-flex items-center gap-1 rounded-md bg-slate-100 text-slate-600 border border-slate-200 px-2.5 py-0.5 text-[11px] font-medium">
                    <Lock :size="12" class="text-slate-500" />
                    <span>Riêng tư</span>
                  </span>

                  <!-- Brownish Orange badge: Đã lưu chỉnh sửa (Chưa gửi duyệt) -->
                  <span v-if="highlightedUpdatedQuestionId === q.id" class="inline-flex items-center gap-1.5 rounded-md bg-[#9A3412] text-white px-2.5 py-0.5 text-[11px] font-bold shadow-xs">
                    <Clock :size="12" />
                    <span>Đã lưu chỉnh sửa (Chưa gửi duyệt)</span>
                  </span>
                  <span v-else-if="q.is_locked_by_admin" class="inline-flex items-center gap-1 rounded-md bg-rose-100 text-rose-900 border border-rose-300 px-2.5 py-0.5 text-[11px] font-bold" :title="q.report_reason ? `Lý do: ${q.report_reason}` : ''">
                    <Lock :size="12" class="text-rose-700" />
                    <span>Đã bị Admin khóa / Gỡ công khai {{ q.report_reason ? `(Lý do: ${q.report_reason})` : '' }}</span>
                  </span>
                  <span v-else-if="q.has_report" class="inline-flex items-center gap-1 rounded-md bg-amber-50 text-amber-800 border border-amber-200 px-2.5 py-0.5 text-[11px] font-bold">
                    <Flag :size="12" class="text-amber-600" />
                    <span>Có báo cáo vi phạm</span>
                  </span>

                  <span v-if="q.grade_name" class="rounded-md bg-indigo-50 border border-indigo-200 text-indigo-700 px-2 py-0.5 text-[11px] font-bold">{{ q.grade_name }}</span>
                  <span v-if="q.subject_name" class="rounded-md bg-[#D1FAE5] text-[#065F46] px-2.5 py-0.5 text-[11px] font-bold">{{ q.subject_name }}</span>
                  <span v-if="q.topic_name" class="rounded-md bg-purple-50 border border-purple-200 text-purple-700 px-2 py-0.5 text-[11px] font-bold">Chủ đề: {{ q.topic_name }}</span>
                  <span v-if="q.quiz_title" class="rounded-md bg-slate-100 border border-slate-200 text-slate-700 px-2 py-0.5 text-[11px] font-bold truncate max-w-[200px]">Quiz: {{ q.quiz_title }}</span>
                </div>

                <h3 class="text-base font-bold text-slate-900 leading-snug mt-1">{{ q.content || q.text }}</h3>

                <!-- Answers Grid -->
                <div v-if="q.answers && q.answers.length > 0" class="mt-4 grid gap-3 md:grid-cols-2">
                  <div
                    v-for="ans in q.answers"
                    :key="ans.id"
                    class="flex items-center gap-2.5 rounded-xl border px-3.5 py-2 text-xs transition"
                    :class="ans.is_correct ? 'border-emerald-300 bg-white text-slate-900 font-medium' : 'border-slate-200 bg-white text-slate-700'"
                  >
                    <span
                      class="grid h-6 w-6 place-items-center rounded-md font-bold text-xs shrink-0"
                      :class="ans.is_correct ? 'bg-[#10B981] text-white' : 'bg-slate-100 text-slate-600'"
                    >
                      {{ ans.key }}
                    </span>
                    <span class="truncate">{{ ans.text || ans.content }}</span>
                    <span v-if="ans.is_correct" class="ml-auto inline-flex items-center gap-1 text-[#10B981] text-xs font-bold shrink-0">
                      <Check :size="14" class="stroke-[3]" />
                      <span>Đúng</span>
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Action buttons (Gửi duyệt, Sửa câu hỏi, Xóa) -->
            <div class="flex items-center gap-2 shrink-0 self-end xl:self-start pt-2 xl:pt-0">
              <!-- Nút Gửi duyệt vào Ngân hàng -->
              <button
                v-if="q.bank_submission_status === 'none' || q.bank_submission_status === 'rejected' || q.bank_submission_status === 'pending'"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl border border-purple-200 bg-purple-50 px-3.5 py-2 text-xs font-bold text-purple-700 hover:bg-purple-100 transition cursor-pointer active:scale-95"
                :title="q.bank_submission_status === 'pending' ? 'Gửi duyệt lại nội dung mới đã chỉnh sửa' : 'Gửi yêu cầu đưa câu hỏi này vào Ngân hàng dùng chung'"
                @click="submitQuestionToBank(q.id)"
              >
                <Send :size="13" />
                <span>{{ q.bank_submission_status === 'pending' ? 'Gửi duyệt lại' : 'Gửi duyệt' }}</span>
              </button>

              <router-link
                :to="`/dashboard/my-questions/${q.id}/edit`"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer active:scale-95"
              >
                <Pencil :size="13" />
                <span>Sửa câu hỏi</span>
              </router-link>

              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2 text-xs font-bold text-rose-600 hover:bg-rose-100 transition cursor-pointer active:scale-95"
                @click="deleteQuestion(q.id)"
              >
                <Trash2 :size="13" />
                <span>Xóa</span>
              </button>
            </div>
          </div>
        </article>
      </div>

      <div v-if="questions.length === 0" class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-10 text-center">
        <div class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-xl bg-[var(--chip-active)]">
          <Search :size="24" class="text-[var(--muted)]" />
        </div>
        <h3 class="text-xl font-black text-[var(--text)]">Chưa có câu hỏi nào</h3>
        <p class="mt-2 text-sm text-[var(--muted)]">Bạn có thể tạo Quiz mới để khởi tạo các câu hỏi trắc nghiệm của riêng mình.</p>
        <button v-if="hasActiveFilters" class="btn-ghost mt-4 text-xs font-black text-[var(--primary)]" @click="resetFilters">Đặt lại bộ lọc</button>
      </div>

      <!-- Pagination Bar -->
      <div v-if="pagination.last_page > 1" class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-[var(--border)] bg-[var(--surface)] p-4">
        <div class="flex items-center gap-2 text-xs font-bold text-[var(--muted)]">
          <span>Trang {{ pagination.current_page }} / {{ pagination.last_page }} (Hiển thị {{ pageStartItem }} - {{ pageEndItem }} / {{ pagination.total }} câu)</span>
        </div>

        <div class="flex items-center gap-1.5">
          <button
            class="btn-ghost !px-3 !py-1.5 inline-flex items-center gap-1 text-xs font-bold"
            :disabled="pagination.current_page <= 1"
            @click="changePage(pagination.current_page - 1)"
          >
            <ChevronLeft :size="14" />
            <span>Trang trước</span>
          </button>

          <button
            v-for="p in visiblePages"
            :key="p"
            class="h-8 w-8 rounded-lg text-xs font-black transition duration-150"
            :class="p === pagination.current_page ? 'bg-[var(--primary)] text-white' : 'bg-[var(--surface-soft)] text-[var(--text)] hover:bg-[var(--primary)]/15'"
            @click="changePage(p)"
          >
            {{ p }}
          </button>

          <button
            class="btn-ghost !px-3 !py-1.5 inline-flex items-center gap-1 text-xs font-bold"
            :disabled="pagination.current_page >= pagination.last_page"
            @click="changePage(pagination.current_page + 1)"
          >
            <span>Trang sau</span>
            <ChevronRight :size="14" />
          </button>
        </div>
      </div>
    </template>

    <!-- MODAL 1: SỬA NHANH CÂU HỎI (QUICK EDIT MODAL) -->
    <div v-if="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4" @click.self="isEditModalOpen = false">
      <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-200 pb-4 mb-5">
          <div>
            <h3 class="text-lg font-black text-slate-900">Chỉnh sửa câu hỏi #{{ editForm.id }}</h3>
            <p class="text-xs text-slate-500 mt-0.5">Cập nhật nội dung câu hỏi, đáp án đúng và mức độ khó</p>
          </div>
          <button class="text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg p-1.5 transition cursor-pointer" @click="isEditModalOpen = false">
            <X :size="18" />
          </button>
        </div>

        <form @submit.prevent="saveEditQuestion" class="grid gap-4">
          <label class="grid gap-1.5 text-xs font-black text-slate-800">
            Nội dung câu hỏi *
            <textarea v-model="editForm.content" required class="field min-h-24 text-sm" placeholder="Nhập câu hỏi..."></textarea>
          </label>

          <div class="grid grid-cols-2 gap-3">
            <label class="grid gap-1.5 text-xs font-black text-slate-800">
              Mức độ khó
              <select v-model="editForm.difficulty" class="field text-xs">
                <option value="easy">Dễ (Nhận biết)</option>
                <option value="medium">Vừa (Thông hiểu)</option>
                <option value="hard">Khó (Vận dụng)</option>
              </select>
            </label>

            <label class="grid gap-1.5 text-xs font-black text-slate-800">
              Chủ đề
              <input v-model="editForm.topic_name" class="field text-xs" placeholder="VD: Văn học, Hàm số..." />
            </label>
          </div>

          <!-- ANSWERS EDIT SECTION -->
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 grid gap-3">
            <div class="flex items-center justify-between">
              <span class="text-xs font-black uppercase text-purple-700">Danh sách Đáp án (Tick chọn đáp án đúng)</span>
            </div>

            <div v-for="(ans, idx) in editForm.answers" :key="idx" class="flex items-center gap-2.5">
              <span class="grid h-8 w-8 place-items-center rounded-xl bg-slate-200 font-black text-xs shrink-0 text-slate-800">{{ ans.key }}</span>

              <input v-model="ans.content" required class="field text-xs flex-1" :placeholder="`Nội dung đáp án ${ans.key}`" />

              <label class="inline-flex items-center gap-1.5 text-xs font-bold shrink-0 cursor-pointer px-3.5 py-2 rounded-xl border transition" :class="ans.is_correct ? 'border-emerald-300 bg-emerald-50 text-emerald-800 shadow-xs' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'">
                <input type="radio" name="correct_answer" :checked="ans.is_correct" @change="setCorrectAnswer(idx)" class="accent-emerald-600" />
                <span class="inline-flex items-center gap-1">
                  <Check v-if="ans.is_correct" :size="13" class="text-emerald-700" />
                  <span>{{ ans.is_correct ? 'Đúng' : 'Sai' }}</span>
                </span>
              </label>
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-2 pt-4 border-t border-slate-200">
            <button class="btn-ghost text-xs cursor-pointer" type="button" @click="isEditModalOpen = false">Hủy</button>
            <button class="btn-primary text-xs inline-flex items-center gap-1.5 cursor-pointer" type="submit" :disabled="isSavingEdit">
              <span>{{ isSavingEdit ? 'Đang lưu...' : 'Lưu thay đổi' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 2: THÙNG RÁC CÂU HỎI (TRASH MODAL) -->
    <div v-if="isTrashModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4" @click.self="isTrashModalOpen = false">
      <div class="w-full max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-200 pb-4 mb-5">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600 shadow-inner">
              <Trash2 :size="20" />
            </div>
            <div>
              <h3 class="text-lg font-black text-slate-900">Thùng rác câu hỏi đã xóa</h3>
              <p class="text-xs text-slate-500 mt-0.5">Các câu hỏi đã bị xóa mềm. Bạn có thể khôi phục lại hoặc xóa vĩnh viễn.</p>
            </div>
          </div>
          <button class="text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg p-1.5 transition cursor-pointer" @click="isTrashModalOpen = false">
            <X :size="18" />
          </button>
        </div>

        <div v-if="isLoadingTrash" class="py-10 text-center text-xs font-bold text-slate-500">
          Đang tải Thùng rác...
        </div>

        <div v-else-if="trashQuestions.length === 0" class="py-14 text-center text-sm font-bold text-slate-500">
          Thùng rác trống. Không có câu hỏi nào bị xóa.
        </div>

        <div v-else class="grid gap-3">
          <article v-for="tq in trashQuestions" :key="tq.id" class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 hover:bg-slate-50 hover:border-slate-300 transition shadow-xs">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                <span class="text-xs font-black text-rose-700 bg-rose-100/80 px-2 py-0.5 rounded border border-rose-200">#{{ tq.id }}</span>
                <span v-if="tq.topic_name" class="text-xs font-semibold text-slate-500">• {{ tq.topic_name }}</span>
                <span v-if="tq.deleted_at || tq.updated_at" class="inline-flex items-center gap-1 text-[11px] font-medium text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                  <Clock :size="12" class="text-rose-500" />
                  <span>Xóa lúc: {{ formatDateTime(tq.deleted_at || tq.updated_at) }}</span>
                </span>
              </div>
              <p class="text-sm font-bold text-slate-900 truncate">{{ tq.content || tq.text }}</p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 hover:border-emerald-300 shadow-xs cursor-pointer active:scale-95"
                @click="restoreQuestion(tq.id)"
              >
                <RotateCcw :size="13" />
                <span>Khôi phục</span>
              </button>

              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100 hover:border-rose-300 shadow-xs cursor-pointer active:scale-95"
                @click="forceDeleteQuestion(tq.id)"
              >
                <Trash2 :size="13" />
                <span>Xóa vĩnh viễn</span>
              </button>
            </div>
          </article>
        </div>
      </div>
    </div>

  </section>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLoadingState from '@/components/common/AppLoadingState.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import { myQuestionsApi, questionsBankApi, taxonomyApi } from '@/services/api'
import {
  Trash2,
  Plus,
  Search,
  RotateCcw,
  CheckCircle2,
  AlertTriangle,
  Eye,
  X,
  Lock,
  Flag,
  Check,
  Pencil,
  ChevronLeft,
  ChevronRight,
  Send,
  Clock,
  Globe,
  XCircle,
  GraduationCap,
  Users,
  BookOpen,
  BarChart2,
  Tag,
  Filter,
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const highlightedQuestionId = ref(null)
const highlightedUpdatedQuestionId = ref(null)
const hasAutoOpenedModal = ref(false)

const focusedQuestionId = computed(() => {
  return (route.query.question_id || route.query.id) ? Number(route.query.question_id || route.query.id) : null
})

const focusedQuestionItem = computed(() => {
  if (!focusedQuestionId.value) return null
  return questions.value.find(q => q.id === focusedQuestionId.value) || null
})

const isCreateModalOpen = ref(false)

const showConfirm = inject('showConfirm')
const showToast = inject('showToast')

const questions = ref([])
const topicsList = ref([])
const selectedQuestionIds = ref([])
const isSubmittingToBank = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')

const taxonomyLevels = ref([])
const allSubjects = ref([])

const filters = reactive({
  search: '',
  education_level_id: '',
  grade_id: '',
  subject_id: '',
  topic_name: '',
  difficulty: '',
})

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
})

// MODAL EDIT STATE
const isEditModalOpen = ref(false)
const isSavingEdit = ref(false)
const editForm = reactive({
  id: null,
  content: '',
  difficulty: 'medium',
  topic_name: '',
  answers: [],
})

// TRASH STATE
const isTrashModalOpen = ref(false)
const isLoadingTrash = ref(false)
const trashQuestions = ref([])
const trashCount = computed(() => trashQuestions.value.length)

const pageStartItem = computed(() => {
  if (pagination.total === 0) return 0
  return (pagination.current_page - 1) * pagination.per_page + 1
})

const pageEndItem = computed(() => {
  return Math.min(pagination.current_page * pagination.per_page, pagination.total)
})

const visiblePages = computed(() => {
  const pages = []
  const maxPages = 5
  let start = Math.max(1, pagination.current_page - 2)
  let end = Math.min(pagination.last_page, start + maxPages - 1)
  if (end - start + 1 < maxPages) {
    start = Math.max(1, end - maxPages + 1)
  }
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

const isCurrentPageAllSelected = computed(() => {
  if (questions.value.length === 0) return false
  return questions.value.every(q => selectedQuestionIds.value.includes(q.id))
})

const toggleSelectQuestion = (id) => {
  const index = selectedQuestionIds.value.indexOf(id)
  if (index > -1) {
    selectedQuestionIds.value.splice(index, 1)
  } else {
    selectedQuestionIds.value.push(id)
  }
}

const toggleSelectAllOnPage = () => {
  const pageIds = questions.value.map(q => q.id)
  if (isCurrentPageAllSelected.value) {
    selectedQuestionIds.value = selectedQuestionIds.value.filter(id => !pageIds.includes(id))
  } else {
    pageIds.forEach(id => {
      if (!selectedQuestionIds.value.includes(id)) {
        selectedQuestionIds.value.push(id)
      }
    })
  }
}

const submitQuestionToBank = async (id) => {
  try {
    const res = await myQuestionsApi.submitToBank(id)
    if (showToast) {
      showToast(res?.message || 'Đã gửi yêu cầu duyệt câu hỏi cho Admin thành công!', 'success')
    }
    await loadQuestions()
  } catch (e) {
    if (showToast) {
      showToast(`Không thể gửi duyệt: ${e.message}`, 'error')
    }
  }
}

const bulkSubmitQuestionsToBank = async () => {
  if (selectedQuestionIds.value.length === 0) return
  isSubmittingToBank.value = true
  try {
    const res = await myQuestionsApi.bulkSubmitToBank(selectedQuestionIds.value)
    if (showToast) {
      showToast(res?.message || 'Đã gửi yêu cầu duyệt câu hỏi cho Admin thành công!', 'success')
    }
    selectedQuestionIds.value = []
    await loadQuestions()
  } catch (e) {
    if (showToast) {
      showToast(`Không thể gửi duyệt: ${e.message}`, 'error')
    }
  } finally {
    isSubmittingToBank.value = false
  }
}

const hasActiveFilters = computed(() => {
  return Boolean(filters.search || filters.education_level_id || filters.grade_id || filters.subject_id || filters.topic_name || filters.difficulty)
})

const difficultyText = (diff) => {
  switch (diff) {
    case 'easy': return 'Dễ'
    case 'hard': return 'Khó'
    default: return 'Vừa'
  }
}

const availableGrades = computed(() => {
  if (!filters.education_level_id) {
    return taxonomyLevels.value.flatMap(l => l.grades || [])
  }
  const level = taxonomyLevels.value.find(l => l.id === Number(filters.education_level_id))
  return level ? level.grades || [] : []
})

const availableSubjects = computed(() => {
  if (!filters.grade_id) {
    return allSubjects.value
  }
  const grade = availableGrades.value.find(g => g.id === Number(filters.grade_id))
  return grade && grade.subjects && grade.subjects.length ? grade.subjects : allSubjects.value
})

const onLevelChange = () => {
  filters.grade_id = ''
  onGradeSubjectChange()
}

const onGradeSubjectChange = () => {
  fetchTopicsList()
  onFilterSubmit()
}

const onFilterSubmit = () => {
  pagination.current_page = 1
  loadQuestions()
}

const resetFilters = () => {
  filters.search = ''
  filters.education_level_id = ''
  filters.grade_id = ''
  filters.subject_id = ''
  filters.topic_name = ''
  filters.difficulty = ''
  pagination.current_page = 1
  loadQuestions()
}

const changePage = (page) => {
  if (page < 1 || page > pagination.last_page || page === pagination.current_page) return
  pagination.current_page = page
  loadQuestions()
  const el = document.getElementById('my-question-top')
  if (el) el.scrollIntoView({ behavior: 'smooth' })
}

const fetchTaxonomy = async () => {
  try {
    const data = await taxonomyApi.tree()
    const payload = data?.education_levels ? data : (data?.data ?? data)
    if (payload) {
      taxonomyLevels.value = payload.education_levels || payload.educationLevels || []
      allSubjects.value = payload.subjects || []
    }
  } catch (e) {
    console.error('Không tải được taxonomy:', e)
  }
}

const fetchTopicsList = async () => {
  try {
    const data = await questionsBankApi.fetchTopics({
      education_level_id: filters.education_level_id || undefined,
      grade_id: filters.grade_id || undefined,
      subject_id: filters.subject_id || undefined,
    })
    topicsList.value = Array.isArray(data) ? data : (data?.data && Array.isArray(data.data) ? data.data : [])
  } catch (e) {
    console.error('Không tải được danh sách Chủ đề:', e)
  }
}

const loadQuestions = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const targetQId = route.query.question_id || route.query.id || undefined

    const res = await myQuestionsApi.fetchBank({
      search: filters.search || undefined,
      education_level_id: filters.education_level_id || undefined,
      grade_id: filters.grade_id || undefined,
      subject_id: filters.subject_id || undefined,
      topic_name: filters.topic_name || undefined,
      difficulty: filters.difficulty || undefined,
      question_id: targetQId,
      page: pagination.current_page,
      per_page: pagination.per_page,
    })

    questions.value = res.items || []
    pagination.total = res.total ?? 0
    pagination.current_page = res.currentPage ?? 1
    pagination.last_page = res.lastPage ?? 1

    handleHighlightFromQuery()
  } catch (err) {
    errorMessage.value = `Không tải được kho câu hỏi: ${err.message}`
  } finally {
    isLoading.value = false
  }
}

const openEditModal = (q) => {
  editForm.id = q.id
  editForm.content = q.content || q.text || ''
  editForm.difficulty = q.difficulty || 'medium'
  editForm.topic_name = q.topic_name || ''
  editForm.answers = (q.answers || []).map((ans, idx) => ({
    id: ans.id,
    key: ans.key || chrKey(idx),
    content: ans.content || ans.text || '',
    is_correct: Boolean(ans.is_correct),
  }))

  if (editForm.answers.length === 0) {
    editForm.answers = [
      { key: 'A', content: '', is_correct: true },
      { key: 'B', content: '', is_correct: false },
      { key: 'C', content: '', is_correct: false },
      { key: 'D', content: '', is_correct: false },
    ]
  }

  isEditModalOpen.value = true
}

const chrKey = (idx) => String.fromCharCode(65 + idx)

const setCorrectAnswer = (targetIdx) => {
  editForm.answers.forEach((ans, idx) => {
    ans.is_correct = idx === targetIdx
  })
}

const saveEditQuestion = async () => {
  if (!editForm.content.trim()) return
  isSavingEdit.value = true

  try {
    const payload = {
      content: editForm.content.trim(),
      difficulty: editForm.difficulty,
      topic_name: editForm.topic_name.trim(),
      answers: editForm.answers.map(ans => ({
        id: ans.id,
        content: ans.content.trim(),
        key: ans.key,
        is_correct: ans.is_correct,
      })),
    }

    const updated = await myQuestionsApi.update(editForm.id, payload)
    isEditModalOpen.value = false
    hasAutoOpenedModal.value = true
    if (showToast) showToast('Đã lưu thay đổi thành công! Vui lòng bấm "Gửi duyệt" để gửi yêu cầu kiểm duyệt lại cho Admin.', 'success')
    loadQuestions()
  } catch (err) {
    if (showToast) showToast(`Cập nhật thất bại: ${err.message}`, 'error')
  } finally {
    isSavingEdit.value = false
  }
}

const deleteQuestion = async (id) => {
  const msg = 'Chuyển câu hỏi này vào Thùng rác?'
  const action = async () => {
    try {
      await myQuestionsApi.remove(id)
      if (showToast) showToast('Đã chuyển câu hỏi vào Thùng rác.', 'success')
      loadQuestions()
      loadTrash()
    } catch (e) {
      if (showToast) showToast(`Xóa thất bại: ${e.message}`, 'error')
    }
  }

  if (showConfirm) showConfirm('Xóa câu hỏi', msg, action)
  else if (confirm(msg)) action()
}

const openTrashModal = async () => {
  isTrashModalOpen.value = true
  await loadTrash()
}

const loadTrash = async () => {
  isLoadingTrash.value = true
  try {
    trashQuestions.value = await myQuestionsApi.trash()
  } catch (e) {
    console.error('Không tải được thùng rác:', e)
  } finally {
    isLoadingTrash.value = false
  }
}

const restoreQuestion = async (id) => {
  try {
    await myQuestionsApi.restore(id)
    if (showToast) showToast('Khôi phục câu hỏi thành công!', 'success')
    loadTrash()
    loadQuestions()
  } catch (e) {
    if (showToast) showToast(`Khôi phục thất bại: ${e.message}`, 'error')
  }
}

const forceDeleteQuestion = async (id) => {
  const msg = 'Xóa vĩnh viễn câu hỏi này? Dữ liệu không thể khôi phục.'
  const action = async () => {
    try {
      await myQuestionsApi.forceDelete(id)
      if (showToast) showToast('Đã xóa vĩnh viễn câu hỏi.', 'success')
      loadTrash()
    } catch (e) {
      if (showToast) showToast(`Xóa thất bại: ${e.message}`, 'error')
    }
  }

  if (showConfirm) showConfirm('Xóa vĩnh viễn', msg, action)
  else if (confirm(msg)) action()
}

const formatDateTime = (dateStr) => {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  if (isNaN(d.getTime())) return '—'
  return d.toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const clearQuestionFocus = () => {
  hasAutoOpenedModal.value = false
  highlightedQuestionId.value = null
  highlightedUpdatedQuestionId.value = null
  router.push({ path: '/dashboard/my-questions' })
}

const handleHighlightFromQuery = () => {
  const targetId = route.query.question_id || route.query.id
  const isUpdated = route.query.updated === '1' || route.query.updated === 'true'

  if (targetId) {
    const qId = Number(targetId)
    if (isUpdated) {
      highlightedUpdatedQuestionId.value = qId
      highlightedQuestionId.value = null
    } else {
      highlightedQuestionId.value = qId
      highlightedUpdatedQuestionId.value = null
    }

    const matched = questions.value.find(q => q.id === qId)
    if (matched) {
      setTimeout(() => {
        const cardEl = document.getElementById(`question-card-${qId}`)
        if (cardEl) {
          cardEl.scrollIntoView({ behavior: 'smooth', block: 'center' })
        }
      }, 250)
    }
  } else {
    highlightedQuestionId.value = null
    highlightedUpdatedQuestionId.value = null
    hasAutoOpenedModal.value = false
  }
}

watch(
  () => route.query.question_id,
  () => {
    loadQuestions()
  }
)

onMounted(() => {
  fetchTaxonomy() 
  fetchTopicsList()
  loadQuestions()
  loadTrash()
})
</script>