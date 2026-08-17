<template>
  <section class="grid gap-6 min-w-0 mx-auto w-full max-w-[1400px] xl:grid-cols-[minmax(0,1fr)_380px]">
    <!-- MAIN FORM COLUMN -->
    <form
      class="relative min-w-0 overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 sm:p-8 shadow-[var(--shadow-soft)] backdrop-blur-2xl grid gap-6"
      @submit.prevent="handleCreateQuizSubmit"
    >
      <!-- Subtle Background Glows -->
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/12 blur-3xl"></div>
      <div class="pointer-events-none absolute -left-24 top-1/2 h-64 w-64 rounded-full bg-[var(--accent)]/10 blur-3xl"></div>

      <div class="relative z-10 grid gap-6">
        <!-- Top Navigation & Title -->
        <div>
          <button 
            type="button"
            class="inline-flex items-center gap-2 text-xs font-bold text-[var(--muted)] hover:text-[var(--primary)] transition duration-200 mb-3"
            @click="goBack"
          >
            <span>← Quay lại kho câu hỏi</span>
          </button>

          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h1 class="text-3xl sm:text-4xl font-black tracking-[-0.06em] text-[var(--text)] flex items-center gap-3">
                <span>Tạo bộ đề</span>
                <span class="inline-block h-2.5 w-2.5 rounded-full bg-[var(--primary)] shadow-[0_0_12px_var(--primary)]"></span>
              </h1>
              <p class="mt-2 text-sm leading-6 text-[var(--muted)] max-w-xl">
                Tùy chỉnh phân bổ độ khó, phạm vi kiến thức hoặc đóng gói bộ đề thi từ ngân hàng câu hỏi.
              </p>
            </div>

            <!-- Mode Selector -->
            <div class="inline-flex rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-1 shrink-0 self-start sm:self-auto">
              <button 
                type="button" 
                class="flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition duration-200"
                :class="mode === 'random' ? 'bg-[var(--primary)] text-white font-black shadow-sm' : 'text-[var(--muted)] hover:text-[var(--text)]'"
                @click="switchMode('random')"
              >
                <span>Tự động phân bổ độ khó</span>
              </button>
              <button 
                type="button" 
                class="flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition duration-200"
                :class="mode === 'manual' ? 'bg-[var(--primary)] text-white font-black shadow-sm' : 'text-[var(--muted)] hover:text-[var(--text)]'"
                @click="switchMode('manual')"
              >
                <span>Chọn thủ công ({{ selectedIds.length }})</span>
              </button>
            </div>
          </div>
        </div>

        <!-- 1. Thông tin bộ đề thi -->
        <div class="grid gap-3 pt-2">
          <label for="exam-title-input" class="text-lg font-black tracking-[-0.04em] text-[var(--text)] flex items-center">
            <span class="h-4 w-1 rounded-full bg-[var(--primary)] inline-block mr-2.5 shadow-[0_0_8px_var(--primary)]"></span>
            <span>1. Thông tin bộ đề</span>
          </label>

          <div class="grid gap-4">
            <div class="grid md:grid-cols-2 gap-4">
              <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
                Tên bộ đề *
                <input 
                  id="exam-title-input"
                  v-model="quizForm.title" 
                  required 
                  class="field text-sm font-bold" 
                  placeholder="VD: Đề thi kiểm tra 1 tiết Toán 10 - Ôn tập Hàm Số" 
                />
              </label>

              <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
                Chủ đề hiển thị
                <input 
                  v-model="quizForm.topic_name" 
                  class="field text-sm font-bold" 
                  placeholder="VD: Đại số & Hàm số, Ôn tập chương 1..." 
                  @input="onTopicInputChange"
                />
              </label>
            </div>

            <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
              Mô tả ngắn
              <textarea 
                v-model="quizForm.description" 
                class="field min-h-24 text-xs leading-relaxed" 
                placeholder="Nhập ghi chú hoặc hướng dẫn làm bài..."
              ></textarea>
            </label>
          </div>
        </div>

        <!-- 2. Cấu hình & Phạm vi hiển thị -->
        <div class="grid gap-4 pt-5 border-t border-[var(--border)]">
          <h2 class="text-lg font-black tracking-[-0.04em] text-[var(--text)] flex items-center">
            <span class="h-4 w-1 rounded-full bg-[var(--primary)] inline-block mr-2.5 shadow-[0_0_8px_var(--primary)]"></span>
            <span>2. Cấu hình & Phạm vi hiển thị</span>
          </h2>

          <div class="grid md:grid-cols-2 gap-4">
            <label 
              class="flex items-start gap-3 rounded-2xl border p-4 cursor-pointer transition duration-200"
              :class="quizForm.visibility === 'private' ? 'border-amber-500/60 bg-amber-500/10 text-[var(--text)] shadow-sm' : 'border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)] hover:border-[var(--border-strong)]'"
            >
              <input type="radio" v-model="quizForm.visibility" value="private" class="mt-1 accent-amber-500 h-4 w-4" />
              <div class="grid gap-1">
                <span class="font-black text-sm text-[var(--text)] flex items-center gap-1.5">
                  <span>🔒</span> Riêng tư
                </span>
                <span class="text-xs leading-relaxed text-[var(--muted)]">Chỉ lưu vào kho cá nhân của bạn.</span>
              </div>
            </label>

            <label 
              class="flex items-start gap-3 rounded-2xl border p-4 cursor-pointer transition duration-200"
              :class="quizForm.visibility === 'public' ? 'border-[var(--primary)] bg-[var(--primary)]/10 text-[var(--text)] shadow-sm' : 'border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)] hover:border-[var(--border-strong)]'"
            >
              <input type="radio" v-model="quizForm.visibility" value="public" class="mt-1 accent-[var(--primary)] h-4 w-4" />
              <div class="grid gap-1">
                <span class="font-black text-sm text-[var(--text)] flex items-center gap-1.5">
                  <span>🌐</span> Công khai
                </span>
                <span class="text-xs leading-relaxed text-[var(--muted)]">Chia sẻ cho mọi người cùng làm bài.</span>
              </div>
            </label>
          </div>

          <div class="grid md:grid-cols-2 gap-4 pt-2">
            <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
              Thời gian làm bài (phút)
              <div class="flex items-center gap-3">
                <input 
                  v-model.number="quizForm.time_limit_minutes" 
                  type="number" 
                  min="1" 
                  max="180" 
                  class="field text-center font-bold text-sm max-w-[120px]" 
                />
                <span class="text-xs text-[var(--muted)]">phút</span>
              </div>
            </label>

            <div class="flex items-center pt-3 sm:pt-4">
              <label class="flex items-center gap-3 cursor-pointer text-xs font-bold text-[var(--text)] select-none">
                <input 
                  type="checkbox" 
                  v-model="quizForm.shuffle_questions" 
                  class="h-5 w-5 rounded accent-[var(--primary)] cursor-pointer" 
                />
                <span>Trộn thứ tự câu hỏi khi làm bài</span>
              </label>
            </div>
          </div>
        </div>

        <!-- 3. Phân loại & Cấu hình câu hỏi (Dùng chung cho cả Tự động & Thủ công) -->
        <div class="grid gap-4 pt-5 border-t border-[var(--border)]">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-black tracking-[-0.04em] text-[var(--text)] flex items-center">
              <span class="h-4 w-1 rounded-full bg-[var(--primary)] inline-block mr-2.5 shadow-[0_0_8px_var(--primary)]"></span>
              <span>3. {{ mode === 'random' ? 'Phân loại & Phân bổ độ khó' : 'Phân loại & Danh sách câu hỏi' }}</span>
            </h2>

            <span v-if="mode === 'random'" class="text-xs font-bold text-[var(--muted)]">
              Tổng kho: <strong class="text-[var(--text)]">{{ poolStats.total }}</strong> câu
            </span>
          </div>

          <!-- Bộ chọn Taxonomy & Chủ đề (Cả Random & Manual) -->
          <div class="grid gap-4 md:grid-cols-2">
            <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
              Cấp học
              <select v-model="filters.education_level_id" class="field" @change="onLevelChange">
                <option value="">Tất cả cấp học</option>
                <option v-for="level in taxonomyLevels" :key="level.id" :value="level.id">{{ level.name }}</option>
              </select>
            </label>

            <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
              Khối lớp
              <select v-model="filters.grade_id" class="field" @change="onGradeSubjectChange">
                <option value="">Tất cả khối lớp</option>
                <option v-for="grade in availableGrades" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
              </select>
            </label>

            <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
              Bộ môn *
              <select v-model="filters.subject_id" class="field" @change="onGradeSubjectChange">
                <option value="">Chọn bộ môn *</option>
                <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
              </select>
            </label>

            <label class="grid gap-1.5 text-xs font-black text-[var(--text)]">
              Chủ đề từ Ngân hàng câu hỏi
              <select v-model="filters.topic_name" class="field cursor-pointer" @change="onTopicSelectChange">
                <option value="">Chủ đề từ kho (Tất cả)</option>
                <option v-for="top in topicsList" :key="top.topic_name" :value="top.topic_name">
                  {{ top.topic_name }} ({{ top.total_questions }} câu)
                </option>
              </select>
            </label>
          </div>

          <!-- DÙNG CHO MODE RANDOM: Phân bổ ma trận độ khó 3 Cards -->
          <div v-if="mode === 'random'" class="grid gap-3 pt-3">
            <div class="flex items-center justify-between">
              <p class="text-xs font-bold text-[var(--muted)]">Phân bổ ma trận độ khó:</p>
              <span class="text-xs font-black text-[var(--primary)]">Tổng chọn: {{ totalMatrixQuestions }} câu</span>
            </div>

            <div class="grid md:grid-cols-3 gap-4">
              <!-- Dễ Card -->
              <div 
                class="rounded-2xl border p-4 sm:p-5 grid gap-3 transition duration-200"
                :class="quizForm.easy_count > poolStats.easy ? 'border-rose-500/50 bg-rose-500/10' : 'border-emerald-500/30 bg-emerald-500/5 hover:border-emerald-500/50'"
              >
                <div class="flex items-center justify-between gap-2">
                  <span class="text-xs font-black text-emerald-400 flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    <span>Dễ (Nhận biết)</span>
                  </span>
                  <span class="text-xs font-semibold text-[var(--muted)]">Kho: {{ poolStats.easy }} câu</span>
                </div>

                <label class="grid gap-1.5 text-xs font-bold text-[var(--muted)]">
                  Số lượng câu hỏi
                  <input 
                    v-model.number="quizForm.easy_count" 
                    type="number" 
                    min="0" 
                    :max="poolStats.easy" 
                    class="field text-center text-lg font-black !py-2 text-[var(--text)]" 
                    :class="{ '!border-rose-500 !text-rose-400': quizForm.easy_count > poolStats.easy }"
                  />
                </label>
              </div>

              <!-- Vừa Card -->
              <div 
                class="rounded-2xl border p-4 sm:p-5 grid gap-3 transition duration-200"
                :class="quizForm.medium_count > poolStats.medium ? 'border-rose-500/50 bg-rose-500/10' : 'border-amber-500/30 bg-amber-500/5 hover:border-amber-500/50'"
              >
                <div class="flex items-center justify-between gap-2">
                  <span class="text-xs font-black text-amber-400 flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                    <span>Vừa (Thông hiểu)</span>
                  </span>
                  <span class="text-xs font-semibold text-[var(--muted)]">Kho: {{ poolStats.medium }} câu</span>
                </div>

                <label class="grid gap-1.5 text-xs font-bold text-[var(--muted)]">
                  Số lượng câu hỏi
                  <input 
                    v-model.number="quizForm.medium_count" 
                    type="number" 
                    min="0" 
                    :max="poolStats.medium" 
                    class="field text-center text-lg font-black !py-2 text-[var(--text)]" 
                    :class="{ '!border-rose-500 !text-rose-400': quizForm.medium_count > poolStats.medium }"
                  />
                </label>
              </div>

              <!-- Khó Card -->
              <div 
                class="rounded-2xl border p-4 sm:p-5 grid gap-3 transition duration-200"
                :class="quizForm.hard_count > poolStats.hard ? 'border-rose-500/50 bg-rose-500/10' : 'border-rose-500/30 bg-rose-500/5 hover:border-rose-500/50'"
              >
                <div class="flex items-center justify-between gap-2">
                  <span class="text-xs font-black text-rose-400 flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                    <span>Khó (Vận dụng)</span>
                  </span>
                  <span class="text-xs font-semibold text-[var(--muted)]">Kho: {{ poolStats.hard }} câu</span>
                </div>

                <label class="grid gap-1.5 text-xs font-bold text-[var(--muted)]">
                  Số lượng câu hỏi
                  <input 
                    v-model.number="quizForm.hard_count" 
                    type="number" 
                    min="0" 
                    :max="poolStats.hard" 
                    class="field text-center text-lg font-black !py-2 text-[var(--text)]" 
                    :class="{ '!border-rose-500 !text-rose-400': quizForm.hard_count > poolStats.hard }"
                  />
                </label>
              </div>
            </div>

            <!-- Warnings -->
            <div v-if="matrixWarnings.length > 0" class="rounded-2xl border border-rose-500/40 bg-rose-500/10 p-3.5 grid gap-1.5">
              <div v-for="(warn, idx) in matrixWarnings" :key="idx" class="text-xs font-bold text-rose-400 flex items-center gap-1.5">
                <span>⚠️ {{ warn }}</span>
              </div>
            </div>
          </div>

          <!-- DÙNG CHO MODE MANUAL: Danh sách câu hỏi đã chọn -->
          <div v-else class="grid gap-3 pt-3">
            <div v-if="selectedIds.length > 0" class="grid gap-3">
              <div class="flex items-center justify-between text-xs font-bold text-[var(--muted)]">
                <span>Đã chọn {{ selectedIds.length }} câu hỏi từ ngân hàng.</span>
                <button type="button" class="text-[var(--primary)] hover:underline font-bold cursor-pointer" @click="saveDraftAndGoToBank">
                  + Chọn thêm câu hỏi khác
                </button>
              </div>

              <div class="flex flex-wrap gap-2">
                <span 
                  v-for="id in selectedIds" 
                  :key="id" 
                  class="inline-flex items-center gap-1.5 rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-bold text-[var(--text)]"
                >
                  <span>#{{ id }}</span>
                  <button 
                    type="button" 
                    class="text-[var(--muted)] hover:text-rose-400 font-black transition cursor-pointer"
                    title="Bỏ chọn câu hỏi này"
                    @click="removeSelectedId(id)"
                  >
                    ✕
                  </button>
                </span>
              </div>
            </div>

            <div v-else class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-5 text-center grid gap-3">
              <p class="text-xs font-bold text-amber-300">
                Bạn chưa chọn câu hỏi nào từ Ngân hàng câu hỏi.
              </p>
              <div>
                <button type="button" class="btn-primary inline-flex items-center gap-2 text-xs !py-2 cursor-pointer" @click="saveDraftAndGoToBank">
                  <span>📚 Chọn câu hỏi từ Ngân hàng</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Actions Bar -->
        <div class="flex flex-wrap items-center justify-between gap-4 pt-6 border-t border-[var(--border)]">
          <button 
            type="button" 
            class="btn-ghost text-xs font-bold text-[var(--muted)] hover:text-[var(--text)]" 
            @click="goBack"
          >
            Hủy
          </button>

          <button 
            type="submit" 
            class="btn-primary !px-8 !py-3 text-xs font-black shadow-lg shadow-[var(--primary)]/25 hover:shadow-[var(--primary)]/40 transition duration-200 hover:-translate-y-0.5" 
            :disabled="isSubmitting || (mode === 'random' && (totalMatrixQuestions === 0 || matrixWarnings.length > 0)) || (mode === 'manual' && selectedIds.length === 0)"
          >
            {{ isSubmitting ? "Đang tạo bộ đề..." : "Tạo bộ đề" }}
          </button>
        </div>
      </div>
    </form>

    <!-- RIGHT ASIDE: SOFT & ELEGANT LIVE PREVIEW -->
    <aside class="grid content-start gap-5">
      <article class="overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] backdrop-blur-2xl grid gap-5 sticky top-6">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-[var(--border)] pb-3 font-black text-[var(--text)]">
          <h3 class="text-xl tracking-[-0.04em]">
            Live preview
          </h3>

          <span class="flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[10px] font-bold text-emerald-400">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            LIVE
          </span>
        </div>

        <!-- Status Pills -->
        <div class="flex flex-wrap items-center gap-2">
          <span class="rounded-full bg-[var(--surface-soft)] px-3 py-1 text-xs font-semibold text-[var(--muted)]">
            {{ mode === 'random' ? '🎲 Tự động phân bổ' : '🎯 Đóng gói thủ công' }}
          </span>

          <span 
            class="rounded-full px-3 py-1 text-xs font-bold"
            :class="quizForm.visibility === 'public' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-300'"
          >
            {{ quizForm.visibility === 'public' ? '🌐 Công khai' : '🔒 Riêng tư' }}
          </span>
        </div>

        <!-- Title & Description Box -->
        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 grid gap-2">
          <h4 class="text-sm font-black text-[var(--text)] leading-snug">
            <span v-if="quizForm.title.trim()">{{ quizForm.title }}</span>
            <span v-else class="text-[var(--muted)] italic font-normal text-xs">[Tên bộ đề sẽ hiển thị ở đây]</span>
          </h4>

          <p v-if="quizForm.description.trim()" class="text-xs text-[var(--muted)] leading-relaxed line-clamp-3">
            {{ quizForm.description }}
          </p>
        </div>

        <!-- Key Metrics Grid -->
        <div class="grid grid-cols-2 gap-2 text-xs">
          <div class="rounded-xl bg-[var(--surface-soft)] p-3">
            <span class="text-[10px] font-semibold text-[var(--muted)] block uppercase">Số lượng câu:</span>
            <span class="font-black text-sm text-[var(--primary)]">
              {{ mode === 'manual' ? selectedIds.length : totalMatrixQuestions }} câu
            </span>
          </div>

          <div class="rounded-xl bg-[var(--surface-soft)] p-3">
            <span class="text-[10px] font-semibold text-[var(--muted)] block uppercase">Thời gian làm:</span>
            <span class="font-black text-sm text-[var(--text)]">
              {{ quizForm.time_limit_minutes }} phút
            </span>
          </div>
        </div>

        <!-- Shuffle Setting Row -->
        <div class="rounded-xl bg-[var(--surface-soft)] p-3 text-xs flex items-center justify-between font-bold text-[var(--text)]">
          <span class="text-[var(--muted)] font-normal">Thứ tự làm bài:</span>
          <span>{{ quizForm.shuffle_questions ? '🔀 Tự động trộn câu' : '🔒 Thứ tự cố định' }}</span>
        </div>

        <!-- Matrix Proportions Progress Bar (Random Mode) -->
        <div v-if="mode === 'random'" class="grid gap-3 pt-3 border-t border-[var(--border)]">
          <div class="flex items-center justify-between text-xs font-bold">
            <span class="text-[var(--muted)]">Tỷ lệ phân bổ độ khó:</span>
            <span class="text-[var(--text)] font-black">{{ totalMatrixQuestions }} câu</span>
          </div>

          <!-- Segmented Bar -->
          <div v-if="totalMatrixQuestions > 0" class="h-2 w-full rounded-full bg-[var(--surface-soft)] overflow-hidden flex">
            <div 
              class="bg-emerald-400 h-full transition-all duration-300" 
              :style="{ width: easyPercent + '%' }"
              :title="`Dễ: ${quizForm.easy_count} câu (${easyPercent}%)`"
            ></div>
            <div 
              class="bg-amber-400 h-full transition-all duration-300" 
              :style="{ width: mediumPercent + '%' }"
              :title="`Vừa: ${quizForm.medium_count} câu (${mediumPercent}%)`"
            ></div>
            <div 
              class="bg-rose-400 h-full transition-all duration-300" 
              :style="{ width: hardPercent + '%' }"
              :title="`Khó: ${quizForm.hard_count} câu (${hardPercent}%)`"
            ></div>
          </div>

          <!-- Detail Rows -->
          <div class="grid gap-1.5 text-xs">
            <div class="flex items-center justify-between text-[var(--muted)]">
              <span class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span> Dễ (Nhận biết)
              </span>
              <span class="font-bold text-[var(--text)]">{{ quizForm.easy_count || 0 }} câu ({{ easyPercent }}%)</span>
            </div>

            <div class="flex items-center justify-between text-[var(--muted)]">
              <span class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-amber-400"></span> Vừa (Thông hiểu)
              </span>
              <span class="font-bold text-[var(--text)]">{{ quizForm.medium_count || 0 }} câu ({{ mediumPercent }}%)</span>
            </div>

            <div class="flex items-center justify-between text-[var(--muted)]">
              <span class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-rose-400"></span> Khó (Vận dụng)
              </span>
              <span class="font-bold text-[var(--text)]">{{ quizForm.hard_count || 0 }} câu ({{ hardPercent }}%)</span>
            </div>
          </div>
        </div>
      </article>
    </aside>
  </section>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { questionsBankApi, taxonomyApi } from '@/services/api'

const route = useRoute()
const router = useRouter()
const showToast = inject('showToast')

const mode = ref(route.query.mode === 'manual' ? 'manual' : 'random')
const isSubmitting = ref(false)

const selectedIds = ref([])
if (route.query.ids) {
  selectedIds.value = String(route.query.ids).split(',').map(Number).filter(Boolean)
}

const taxonomyLevels = ref([])
const allSubjects = ref([])
const topicsList = ref([])

const poolStats = reactive({
  easy: 0,
  medium: 0,
  hard: 0,
  total: 0,
})

const filters = reactive({
  education_level_id: route.query.education_level_id ? Number(route.query.education_level_id) : '',
  grade_id: route.query.grade_id ? Number(route.query.grade_id) : '',
  subject_id: route.query.subject_id ? Number(route.query.subject_id) : '',
  topic_name: typeof route.query.topic_name === 'string' ? route.query.topic_name : '',
})

const quizForm = reactive({
  title: '',
  description: 'Bộ đề thi chuẩn hóa khởi tạo từ Ngân hàng câu hỏi QuizFlex',
  topic_name: filters.topic_name || '',
  easy_count: 3,
  medium_count: 5,
  hard_count: 2,
  time_limit_minutes: 15,
  shuffle_questions: true,
  visibility: 'public',
})

const totalMatrixQuestions = computed(() => {
  return (quizForm.easy_count || 0) + (quizForm.medium_count || 0) + (quizForm.hard_count || 0)
})

const easyPercent = computed(() => {
  if (!totalMatrixQuestions.value) return 0
  return Math.round(((quizForm.easy_count || 0) / totalMatrixQuestions.value) * 100)
})

const mediumPercent = computed(() => {
  if (!totalMatrixQuestions.value) return 0
  return Math.round(((quizForm.medium_count || 0) / totalMatrixQuestions.value) * 100)
})

const hardPercent = computed(() => {
  if (!totalMatrixQuestions.value) return 0
  return Math.round(((quizForm.hard_count || 0) / totalMatrixQuestions.value) * 100)
})

const matrixWarnings = computed(() => {
  const warnings = []
  if (quizForm.easy_count > poolStats.easy) {
    warnings.push(`Không đủ câu hỏi Dễ. Kho hiện chỉ có ${poolStats.easy} câu.`)
  }
  if (quizForm.medium_count > poolStats.medium) {
    warnings.push(`Không đủ câu hỏi Vừa. Kho hiện chỉ có ${poolStats.medium} câu.`)
  }
  if (quizForm.hard_count > poolStats.hard) {
    warnings.push(`Không đủ câu hỏi Khó. Kho hiện chỉ có ${poolStats.hard} câu.`)
  }
  return warnings
})

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

const removeSelectedId = (id) => {
  selectedIds.value = selectedIds.value.filter(item => item !== id)
}

const saveDraftAndGoToBank = () => {
  const draftData = {
    title: quizForm.title,
    description: quizForm.description,
    time_limit_minutes: quizForm.time_limit_minutes,
    shuffle_questions: quizForm.shuffle_questions,
    visibility: quizForm.visibility,
    topic_name: quizForm.topic_name,
    easy_count: quizForm.easy_count,
    medium_count: quizForm.medium_count,
    hard_count: quizForm.hard_count,
    mode: mode.value,
  }
  try {
    sessionStorage.setItem('quizflex_create_exam_draft', JSON.stringify(draftData))
  } catch (e) {
    console.error('Failed to save exam draft:', e)
  }

  const query = {
    ids: selectedIds.value.length ? selectedIds.value.join(',') : undefined,
    education_level_id: filters.education_level_id || undefined,
    grade_id: filters.grade_id || undefined,
    subject_id: filters.subject_id || undefined,
    topic_name: filters.topic_name || undefined,
  }
  router.push({ path: '/question-bank', query })
}

const restoreDraft = () => {
  try {
    const raw = sessionStorage.getItem('quizflex_create_exam_draft')
    if (raw) {
      const draft = JSON.parse(raw)
      if (draft.title) quizForm.title = draft.title
      if (draft.description !== undefined) quizForm.description = draft.description
      if (draft.time_limit_minutes) quizForm.time_limit_minutes = draft.time_limit_minutes
      if (draft.shuffle_questions !== undefined) quizForm.shuffle_questions = draft.shuffle_questions
      if (draft.visibility) quizForm.visibility = draft.visibility
      if (draft.topic_name) quizForm.topic_name = draft.topic_name
      if (draft.easy_count !== undefined) quizForm.easy_count = draft.easy_count
      if (draft.medium_count !== undefined) quizForm.medium_count = draft.medium_count
      if (draft.hard_count !== undefined) quizForm.hard_count = draft.hard_count
      if (draft.mode) mode.value = draft.mode
      sessionStorage.removeItem('quizflex_create_exam_draft')
    }
  } catch (e) {
    console.error('Failed to restore exam draft:', e)
  }
}

const goBack = () => {
  router.back()
}

const switchMode = (newMode) => {
  mode.value = newMode
  updateFormTitle()
}

const updateFormTitle = () => {
  if (!quizForm.title) {
    if (mode.value === 'manual') {
      quizForm.title = `Bộ đề thi đóng gói từ ${selectedIds.value.length} câu đã chọn`
      quizForm.time_limit_minutes = Math.max(selectedIds.value.length, 10)
    } else {
      quizForm.title = filters.topic_name 
        ? `Bộ đề thi tự động - Chủ đề: ${filters.topic_name}`
        : `Bộ đề ôn tập tự động ${totalMatrixQuestions.value} câu`
      quizForm.time_limit_minutes = Math.max(totalMatrixQuestions.value, 10)
    }
  }
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
    const params = {
      education_level_id: filters.education_level_id || undefined,
      grade_id: filters.grade_id || undefined,
      subject_id: filters.subject_id || undefined,
    }
    const data = await questionsBankApi.fetchTopics(params)
    topicsList.value = Array.isArray(data) ? data : (data?.data && Array.isArray(data.data) ? data.data : [])
  } catch (e) {
    console.error('Không tải được danh sách Chủ đề:', e)
  }
}

const updatePoolStats = async () => {
  try {
    const params = {
      education_level_id: filters.education_level_id || undefined,
      grade_id: filters.grade_id || undefined,
      subject_id: filters.subject_id || undefined,
      topic_name: filters.topic_name || undefined,
    }
    const res = await questionsBankApi.fetchStats(params)
    const stats = res?.easy !== undefined ? res : (res?.data ?? { easy: 0, medium: 0, hard: 0, total: 0 })
    poolStats.easy = stats.easy || 0
    poolStats.medium = stats.medium || 0
    poolStats.hard = stats.hard || 0
    poolStats.total = stats.total || 0

    // Adjust counts safely if pool is smaller
    if (quizForm.easy_count > poolStats.easy) quizForm.easy_count = Math.min(3, poolStats.easy)
    if (quizForm.medium_count > poolStats.medium) quizForm.medium_count = Math.min(5, poolStats.medium)
    if (quizForm.hard_count > poolStats.hard) quizForm.hard_count = Math.min(2, poolStats.hard)

    updateFormTitle()
  } catch (e) {
    console.error('Không đếm được số câu khả dụng:', e)
  }
}

const onLevelChange = () => {
  filters.grade_id = ''
  onGradeSubjectChange()
}

const onGradeSubjectChange = () => {
  fetchTopicsList()
  updatePoolStats()
}

const onTopicSelectChange = () => {
  if (!quizForm.topic_name && filters.topic_name) {
    quizForm.topic_name = filters.topic_name
  }
  updatePoolStats()
}

const onTopicInputChange = () => {
  const text = (quizForm.topic_name || '').trim().toLowerCase()
  if (!text) {
    filters.topic_name = ''
  } else {
    const found = topicsList.value.find(t => t.topic_name.toLowerCase() === text)
    if (found) {
      filters.topic_name = found.topic_name
    } else {
      filters.topic_name = ''
    }
  }
  updatePoolStats()
}

const handleCreateQuizSubmit = async () => {
  if (!quizForm.title.trim()) return
  if (!filters.subject_id) {
    if (showToast) showToast('Vui lòng chọn Bộ môn cho bộ đề thi!', 'error')
    else alert('Vui lòng chọn Bộ môn cho bộ đề thi!')
    return
  }
  if (mode.value === 'random' && matrixWarnings.value.length > 0) return
  if (mode.value === 'manual' && selectedIds.value.length === 0) return

  isSubmitting.value = true
  try {
    const payload = {
      title: quizForm.title.trim(),
      description: quizForm.description.trim(),
      education_level_id: filters.education_level_id || undefined,
      grade_id: filters.grade_id || undefined,
      subject_id: filters.subject_id || undefined,
      topic_name: filters.topic_name || undefined,
      quiz_topic_name: quizForm.topic_name.trim() || filters.topic_name || undefined,
      time_limit_minutes: quizForm.time_limit_minutes || 15,
      shuffle_questions: quizForm.shuffle_questions,
      is_public: quizForm.visibility === 'public',
      status: quizForm.visibility === 'public' ? 'published' : 'draft',
    }

    if (mode.value === 'manual') {
      payload.question_ids = selectedIds.value
    } else {
      payload.easy_count = quizForm.easy_count || 0
      payload.medium_count = quizForm.medium_count || 0
      payload.hard_count = quizForm.hard_count || 0
      payload.random_count = totalMatrixQuestions.value
    }

    const createdQuiz = await questionsBankApi.createQuizFromBank(payload)
    if (showToast) showToast('Tạo bộ đề thi thành công!', 'success')
    
    if (createdQuiz && createdQuiz.id) {
      router.push(`/quizzes/${createdQuiz.id}`)
    } else {
      router.push('/question-bank')
    }
  } catch (e) {
    if (showToast) showToast(`Không thể tạo Quiz: ${e.message}`, 'error')
    else alert(`Không thể tạo Quiz: ${e.message}`)
  } finally {
    isSubmitting.value = false
  }
}

onMounted(async () => {
  restoreDraft()
  await fetchTaxonomy()
  await fetchTopicsList()
  await updatePoolStats()
})
</script>
