<template>
  <section id="my-question-top" class="grid gap-6 py-8">
    <!-- Header Banner -->
    <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-6">
      <div class="flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div>
          <p class="text-xs font-bold uppercase tracking-[0.14em] text-[var(--primary)]">My Personal Question Repository</p>
          <h1 class="mt-2 text-3xl font-black tracking-[-0.02em] text-[var(--text)]">Kho câu hỏi của tôi</h1>
          <p class="mt-2 max-w-3xl text-sm leading-6 text-[var(--muted)]">Quản lý độc lập tất cả câu hỏi trắc nghiệm do bạn tạo ra. Chỉnh sửa nội dung, sửa đáp án đúng hoặc chuyển câu hỏi lỗi vào thùng rác an toàn.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-xs font-bold text-rose-300 transition hover:bg-rose-500/15"
            @click="openTrashModal"
          >
            <Trash2 :size="14" />
            <span>Thùng rác câu hỏi</span>
            <span v-if="trashCount > 0" class="rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] leading-none text-white font-bold">{{ trashCount }}</span>
          </button>

          <router-link
            to="/question-bank/create-question"
            class="btn-primary inline-flex items-center gap-2"
          >
            <Plus :size="14" />
            <span>Tạo câu hỏi</span>
          </router-link>
        </div>
      </div>
    </div>

    <!-- Taxonomy & Search Filter Bar -->
    <article class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-5 grid gap-4">
      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <!-- 1. Cấp học -->
        <select v-model="filters.education_level_id" class="field" @change="onLevelChange">
          <option value="">Tất cả Cấp học</option>
          <option v-for="level in taxonomyLevels" :key="level.id" :value="level.id">{{ level.name }}</option>
        </select>

        <!-- 2. Khối lớp -->
        <select v-model="filters.grade_id" class="field" @change="onGradeSubjectChange">
          <option value="">Tất cả Khối lớp</option>
          <option v-for="grade in availableGrades" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
        </select>

        <!-- 3. Bộ môn -->
        <select v-model="filters.subject_id" class="field" @change="onGradeSubjectChange">
          <option value="">Tất cả Bộ môn</option>
          <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
        </select>

        <!-- 4. Độ khó -->
        <select v-model="filters.difficulty" class="field" @change="onFilterSubmit">
          <option value="">Tất cả độ khó</option>
          <option value="easy">Dễ (Nhận biết)</option>
          <option value="medium">Vừa (Thông hiểu)</option>
          <option value="hard">Khó (Vận dụng)</option>
        </select>
      </div>

      <div class="grid gap-3 xl:grid-cols-[1fr_260px_auto_auto]">
        <div class="relative">
          <Search :size="15" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[var(--muted)]" />
          <input v-model="filters.search" class="field !pl-9" placeholder="Tìm kiếm theo nội dung câu hỏi..." @keyup.enter="onFilterSubmit" />
        </div>

        <select v-model="filters.topic_name" class="field" @change="onFilterSubmit">
          <option value="">Tất cả Chủ đề</option>
          <option v-for="top in topicsList" :key="top.topic_name" :value="top.topic_name">
            {{ top.topic_name }}
          </option>
        </select>

        <button class="btn-primary" type="button" @click="onFilterSubmit">Tìm kiếm & Lọc</button>
        <button v-if="hasActiveFilters" class="btn-ghost inline-flex items-center gap-1.5 text-xs text-rose-500 font-bold" type="button" @click="resetFilters">
          <RotateCcw :size="13" />
          <span>Đặt lại</span>
        </button>
      </div>
    </article>

    <!-- Stats Bar -->
    <div class="flex items-center justify-between text-xs font-bold text-[var(--muted)] px-1">
      <span>Hiển thị {{ pageStartItem }} - {{ pageEndItem }} / Tổng {{ pagination.total }} câu hỏi sở hữu</span>
    </div>

    <!-- Loading State -->
    <AppLoadingState v-if="isLoading" title="Đang tải kho câu hỏi cá nhân..." message="Vui lòng chờ trong giây lát..." icon="📚" />

    <!-- Error State -->
    <AppErrorState v-else-if="errorMessage" title="Không thể tải kho câu hỏi" :message="errorMessage" @retry="loadQuestions" />

    <!-- Loaded Questions List -->
    <template v-else>
      <!-- Focused Question Banner -->
      <div v-if="focusedQuestionId" class="mb-1">
        <!-- SUCCESS BANNER (When question was just updated) -->
        <div v-if="highlightedUpdatedQuestionId === focusedQuestionId" class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <CheckCircle2 :size="20" class="shrink-0 text-emerald-400" />
            <div>
              <h4 class="font-black text-emerald-700 dark:text-emerald-300 text-sm">
                Đã hoàn tất đính chính câu hỏi #{{ focusedQuestionId }}
              </h4>
              <p class="text-xs text-slate-700 dark:text-slate-300 mt-0.5 font-semibold">Nội dung đã được lưu thành công và đã tự động chuyển sang trạng thái <strong>Chờ Admin duyệt lại</strong>.</p>
            </div>
          </div>
          <button
            type="button"
            class="shrink-0 inline-flex items-center gap-1.5 rounded-lg border border-emerald-500/30 bg-emerald-500/15 px-3 py-1.5 text-xs font-bold text-emerald-800 dark:text-emerald-200 hover:bg-emerald-500/25 transition"
            @click="clearQuestionFocus"
          >
            <Eye :size="13" />
            <span>Xem tất cả câu hỏi trong kho</span>
          </button>
        </div>

        <!-- WARNING BANNER (When user needs to edit reported question) -->
        <div v-else class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <AlertTriangle :size="20" class="shrink-0 text-rose-400" />
            <div>
              <h4 class="font-black text-rose-300 text-sm">
                Đang tập trung xử lý câu hỏi #{{ focusedQuestionId }}
                <span v-if="focusedQuestionItem?.is_locked_by_admin" class="text-amber-300 ml-1">(Admin đã khóa: "{{ focusedQuestionItem.report_reason || 'Vi phạm quy định' }}")</span>
              </h4>
              <p class="text-xs text-[var(--muted)] mt-0.5">Vui lòng nhấp nút <strong>"Sửa câu hỏi"</strong> ở thẻ bên dưới để đính chính đáp án/nội dung.</p>
            </div>
          </div>
          <button
            type="button"
            class="shrink-0 inline-flex items-center gap-1.5 rounded-lg border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1.5 text-xs font-bold text-[var(--text)] hover:bg-[var(--chip-active)] transition"
            @click="clearQuestionFocus"
          >
            <Eye :size="13" />
            <span>Xem tất cả câu hỏi trong kho</span>
          </button>
        </div>
      </div>

      <!-- SELECTION & BULK ACTION BAR -->
      <div v-if="questions.length > 0" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3">
        <div class="flex items-center gap-3">
          <label class="flex items-center gap-2 text-xs font-bold text-[var(--text)] cursor-pointer select-none">
            <input
              type="checkbox"
              :checked="isCurrentPageAllSelected"
              class="h-4 w-4 rounded accent-[var(--primary)] cursor-pointer"
              @change="toggleSelectAllOnPage"
            />
            <span>Chọn tất cả trên trang này</span>
          </label>
          <span v-if="selectedQuestionIds.length > 0" class="rounded-lg bg-[var(--primary)]/10 px-2 py-0.5 text-xs font-bold text-[var(--primary)]">
            Đã chọn {{ selectedQuestionIds.length }} câu hỏi
          </span>
        </div>

        <div v-if="selectedQuestionIds.length > 0" class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg bg-[var(--primary)] px-3.5 py-1.5 text-xs font-bold text-white shadow-md shadow-[var(--primary)]/25 hover:opacity-90 transition disabled:opacity-50 cursor-pointer"
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
          class="rounded-xl border p-5 transition-colors duration-200 relative"
          :class="[
            highlightedUpdatedQuestionId === q.id
              ? 'border-emerald-500 bg-emerald-500/5'
              : (highlightedQuestionId === q.id
                  ? 'border-rose-500 bg-rose-500/5'
                  : 'border-[var(--border)] bg-[var(--surface)] hover:border-[var(--border-strong)]'),
            q.is_locked_by_admin && highlightedUpdatedQuestionId !== q.id ? 'border-rose-500/40 bg-rose-500/5' : ''
          ]"
        >
          <!-- Success Banner if updated -->
          <div v-if="highlightedUpdatedQuestionId === q.id" class="mb-4 flex items-center justify-between rounded-lg border border-emerald-500/30 bg-emerald-500/15 p-3 text-xs font-bold text-emerald-950 dark:text-emerald-100">
            <div class="flex items-center gap-2">
              <CheckCircle2 :size="16" />
              <span>Đã cập nhật đính chính nội dung câu hỏi thành công! Hệ thống đã tự động chuyển câu hỏi sang trạng thái Chờ duyệt lại.</span>
            </div>
            <button type="button" class="text-emerald-900 dark:text-emerald-200 hover:text-black dark:hover:text-white ml-2" @click="clearQuestionFocus">
              <X :size="14" />
            </button>
          </div>

          <div class="flex flex-col xl:flex-row xl:items-start justify-between gap-4">
            <div class="flex items-start gap-3 flex-1 min-w-0">
              <!-- Selection checkbox -->
              <input
                type="checkbox"
                :checked="selectedQuestionIds.includes(q.id)"
                class="h-4 w-4 rounded accent-[var(--primary)] mt-1 shrink-0 cursor-pointer"
                @change="toggleSelectQuestion(q.id)"
              />

              <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-1.5 mb-3">
                  <span class="rounded-md border border-[var(--border)] bg-[var(--surface-soft)] px-2 py-0.5 text-[11px] font-bold text-[var(--muted)]">#{{ q.id }}</span>
                  <span
                    class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-[11px] font-bold"
                    :class="{
                      'bg-emerald-500/10 text-emerald-400': q.difficulty === 'easy',
                      'bg-amber-500/10 text-amber-400': q.difficulty === 'medium',
                      'bg-rose-500/10 text-rose-400': q.difficulty === 'hard'
                    }"
                  >
                    <span
                      class="h-1.5 w-1.5 rounded-full"
                      :class="{
                        'bg-emerald-400': q.difficulty === 'easy',
                        'bg-amber-400': q.difficulty === 'medium',
                        'bg-rose-400': q.difficulty === 'hard'
                      }"
                    ></span>
                    {{ difficultyText(q.difficulty) }}
                  </span>

                  <!-- Status badge for bank submission -->
                  <span v-if="q.bank_submission_status === 'pending'" class="inline-flex items-center gap-1 rounded-md bg-amber-500/15 text-amber-400 border border-amber-500/30 px-2 py-0.5 text-[11px] font-bold">
                    <Clock :size="12" />
                    <span>Đang chờ duyệt</span>
                  </span>
                  <span v-else-if="q.bank_submission_status === 'approved' || q.is_public" class="inline-flex items-center gap-1 rounded-md bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 text-[11px] font-bold">
                    <Globe :size="12" />
                    <span>Đã vào Ngân hàng</span>
                  </span>
                  <span v-else-if="q.bank_submission_status === 'rejected'" class="inline-flex items-center gap-1 rounded-md bg-rose-500/15 text-rose-300 border border-rose-500/30 px-2 py-0.5 text-[11px] font-bold" :title="q.bank_submission_note ? `Lý do từ chối: ${q.bank_submission_note}` : 'Bị từ chối'">
                    <XCircle :size="12" />
                    <span>Bị từ chối {{ q.bank_submission_note ? `— Lý do: ${q.bank_submission_note}` : '' }}</span>
                  </span>
                  <span v-else class="inline-flex items-center gap-1 rounded-md bg-slate-500/15 text-slate-400 border border-slate-500/30 px-2 py-0.5 text-[11px] font-bold">
                    <Lock :size="12" />
                    <span>Riêng tư</span>
                  </span>

                  <span v-if="highlightedUpdatedQuestionId === q.id" class="inline-flex items-center gap-1 rounded-md bg-emerald-600 text-white dark:bg-emerald-800 dark:text-emerald-100 px-2 py-0.5 text-[11px] font-bold">
                    <CheckCircle2 :size="12" />
                    <span>Đã cập nhật &amp; Chờ duyệt lại</span>
                  </span>
                  <span v-else-if="q.is_locked_by_admin" class="inline-flex items-center gap-1 rounded-md bg-rose-500/10 text-rose-300 border border-rose-500/30 px-2 py-0.5 text-[11px] font-bold" :title="q.report_reason ? `Lý do: ${q.report_reason}` : ''">
                    <Lock :size="12" />
                    <span>Đã bị Admin khóa / Gỡ công khai {{ q.report_reason ? `(Lý do: ${q.report_reason})` : '' }}</span>
                  </span>
                  <span v-else-if="q.has_report" class="inline-flex items-center gap-1 rounded-md bg-amber-500/10 text-amber-300 border border-amber-500/30 px-2 py-0.5 text-[11px] font-bold">
                    <Flag :size="12" />
                    <span>Có báo cáo vi phạm</span>
                  </span>

                  <span v-if="q.grade_name" class="rounded-md bg-indigo-600/15 text-indigo-400 px-2 py-0.5 text-[11px] font-bold">{{ q.grade_name }}</span>
                  <span v-if="q.subject_name" class="rounded-md bg-emerald-600/15 text-emerald-400 px-2 py-0.5 text-[11px] font-bold">{{ q.subject_name }}</span>
                  <span v-if="q.topic_name" class="rounded-md bg-purple-600/15 text-purple-300 px-2 py-0.5 text-[11px] font-bold">Chủ đề: {{ q.topic_name }}</span>
                  <span v-if="q.quiz_title" class="rounded-md bg-slate-700/25 text-slate-300 px-2 py-0.5 text-[11px] font-bold truncate max-w-[200px]">Quiz: {{ q.quiz_title }}</span>
                </div>

                <h3 class="text-base font-black text-[var(--text)] leading-snug">{{ q.content || q.text }}</h3>

                <!-- Answers Grid -->
                <div v-if="q.answers && q.answers.length > 0" class="mt-4 grid gap-2 md:grid-cols-2">
                  <div
                    v-for="ans in q.answers"
                    :key="ans.id"
                    class="flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-semibold"
                    :class="ans.is_correct ? 'border-emerald-500/30 bg-emerald-500/5 text-emerald-300 font-bold' : 'border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)]'"
                  >
                    <span class="grid h-5 w-5 place-items-center rounded-md bg-black/25 font-black text-[10px] shrink-0">{{ ans.key }}</span>
                    <span class="truncate">{{ ans.text || ans.content }}</span>
                    <span v-if="ans.is_correct" class="ml-auto inline-flex items-center gap-1 text-emerald-400 text-xs shrink-0">
                      <Check :size="13" />
                      <span>Đúng</span>
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-2 shrink-0 self-end xl:self-start pt-2 xl:pt-0">
              <!-- Nút Gửi duyệt vào Ngân hàng -->
              <button
                v-if="q.bank_submission_status === 'none' || q.bank_submission_status === 'rejected'"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg border border-purple-500/40 bg-purple-500/10 px-3.5 py-2 text-xs font-bold text-purple-400 hover:bg-purple-500/20 transition cursor-pointer"
                title="Gửi yêu cầu đưa câu hỏi này vào Ngân hàng dùng chung"
                @click="submitQuestionToBank(q.id)"
              >
                <Send :size="13" />
                <span>Gửi duyệt</span>
              </button>

              <router-link
                :to="`/dashboard/my-questions/${q.id}/edit`"
                class="inline-flex items-center gap-1.5 rounded-lg border border-[var(--border)] bg-[var(--surface-soft)] px-3.5 py-2 text-xs font-bold text-[var(--text)] transition hover:border-[var(--border-strong)] hover:bg-[var(--chip-active)]"
              >
                <Pencil :size="13" />
                <span>Sửa câu hỏi</span>
              </router-link>

              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg border border-rose-500/30 bg-rose-500/10 px-3.5 py-2 text-xs font-bold text-rose-300 transition hover:bg-rose-500/15"
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
    <div v-if="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4" @click.self="isEditModalOpen = false">
      <div class="w-full max-w-2xl rounded-2xl border border-[var(--border-strong)] bg-[var(--surface)] p-6 shadow-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-[var(--border)] pb-4 mb-4">
          <div>
            <h3 class="text-lg font-black text-[var(--text)]">Chỉnh sửa câu hỏi #{{ editForm.id }}</h3>
            <p class="text-xs text-[var(--muted)]">Cập nhật nội dung câu hỏi, đáp án đúng và mức độ khó</p>
          </div>
          <button class="text-[var(--muted)] hover:text-rose-500" @click="isEditModalOpen = false">
            <X :size="18" />
          </button>
        </div>

        <form @submit.prevent="saveEditQuestion" class="grid gap-4">
          <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
            Nội dung câu hỏi *
            <textarea v-model="editForm.content" required class="field min-h-24 text-sm" placeholder="Nhập câu hỏi..."></textarea>
          </label>

          <div class="grid grid-cols-2 gap-3">
            <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
              Mức độ khó
              <select v-model="editForm.difficulty" class="field text-xs">
                <option value="easy">Dễ (Nhận biết)</option>
                <option value="medium">Vừa (Thông hiểu)</option>
                <option value="hard">Khó (Vận dụng)</option>
              </select>
            </label>

            <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
              Chủ đề
              <input v-model="editForm.topic_name" class="field text-xs" placeholder="VD: Văn học, Hàm số..." />
            </label>
          </div>

          <!-- ANSWERS EDIT SECTION -->
          <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 grid gap-3">
            <div class="flex items-center justify-between">
              <span class="text-xs font-black uppercase text-[var(--primary)]">Danh sách Đáp án (Tick chọn đáp án đúng)</span>
            </div>

            <div v-for="(ans, idx) in editForm.answers" :key="idx" class="flex items-center gap-2">
              <span class="grid h-7 w-7 place-items-center rounded-lg bg-black/30 font-black text-xs shrink-0 text-[var(--text)]">{{ ans.key }}</span>

              <input v-model="ans.content" required class="field text-xs flex-1" :placeholder="`Nội dung đáp án ${ans.key}`" />

              <label class="inline-flex items-center gap-1.5 text-xs font-bold shrink-0 cursor-pointer px-3 py-2 rounded-lg border transition" :class="ans.is_correct ? 'border-emerald-500/60 bg-emerald-500/15 text-emerald-300' : 'border-[var(--border)] text-[var(--muted)]'">
                <input type="radio" name="correct_answer" :checked="ans.is_correct" @change="setCorrectAnswer(idx)" class="accent-emerald-500" />
                <span class="inline-flex items-center gap-1">
                  <Check v-if="ans.is_correct" :size="12" />
                  <span>{{ ans.is_correct ? 'Đúng' : 'Sai' }}</span>
                </span>
              </label>
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-2 pt-4 border-t border-[var(--border)]">
            <button class="btn-ghost text-xs" type="button" @click="isEditModalOpen = false">Hủy</button>
            <button class="btn-primary text-xs inline-flex items-center gap-1.5" type="submit" :disabled="isSavingEdit">
              <span>{{ isSavingEdit ? 'Đang lưu...' : 'Lưu thay đổi' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 2: THÙNG RÁC CÂU HỎI (TRASH MODAL) -->
    <div v-if="isTrashModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4" @click.self="isTrashModalOpen = false">
      <div class="w-full max-w-3xl rounded-2xl border border-rose-500/25 bg-[var(--surface)] p-6 shadow-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-[var(--border)] pb-4 mb-4">
          <div class="flex items-center gap-2">
            <Trash2 :size="18" class="text-rose-400" />
            <div>
              <h3 class="text-lg font-black text-rose-400">Thùng rác câu hỏi đã xóa</h3>
              <p class="text-xs text-[var(--muted)]">Các câu hỏi đã bị xóa mềm. Bạn có thể khôi phục lại hoặc xóa vĩnh viễn.</p>
            </div>
          </div>
          <button class="text-[var(--muted)] hover:text-rose-500" @click="isTrashModalOpen = false">
            <X :size="18" />
          </button>
        </div>

        <div v-if="isLoadingTrash" class="py-8 text-center text-xs font-bold text-[var(--muted)]">
          Đang tải Thùng rác...
        </div>

        <div v-else-if="trashQuestions.length === 0" class="py-12 text-center text-sm font-bold text-[var(--muted)]">
          Thùng rác trống. Không có câu hỏi nào bị xóa.
        </div>

        <div v-else class="grid gap-2.5">
          <article v-for="tq in trashQuestions" :key="tq.id" class="rounded-xl border border-rose-500/15 bg-rose-500/5 p-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-black text-rose-400">#{{ tq.id }}</span>
                <span v-if="tq.topic_name" class="text-xs text-[var(--muted)]">• {{ tq.topic_name }}</span>
              </div>
              <p class="text-sm font-bold text-[var(--text)] truncate">{{ tq.content || tq.text }}</p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 text-xs font-bold text-emerald-300 transition hover:bg-emerald-500/15"
                @click="restoreQuestion(tq.id)"
              >
                <RotateCcw :size="13" />
                <span>Khôi phục</span>
              </button>

              <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 py-1.5 text-xs font-bold text-rose-300 transition hover:bg-rose-500/15"
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
      showToast(res?.message || 'Đã gửi yêu cầu duyệt câu hỏi vào Ngân hàng!', 'success')
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
      showToast(res?.message || 'Đã gửi yêu cầu duyệt câu hỏi vào Ngân hàng!', 'success')
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
    if (showToast) showToast('Cập nhật đính chính thành công! Đã gửi thông báo cho Admin kiểm duyệt.', 'success')
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