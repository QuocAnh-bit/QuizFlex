<template>
  <section class="grid gap-6 text-[var(--text)]" @click="closeDropdown">
    <!-- Top Breadcrumb & Header Banner -->
    <div class="space-y-4">
      <div class="flex items-center gap-2 text-xs font-bold text-[var(--muted)]">
        <router-link to="/admin" class="hover:text-[var(--primary)] transition">Dashboard</router-link>
        <span>&rsaquo;</span>
        <span class="text-[var(--text)]">Quản lý Bộ môn</span>
      </div>

      <div class="relative overflow-hidden rounded-[1.8rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
        <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-center">
          <div class="flex items-center gap-4 min-w-0">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
              </svg>
            </div>
            <div class="min-w-0">
              <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-[var(--text)] truncate">
                Quản lý Bộ môn & Danh mục
              </h1>
              <p class="mt-1 text-xs sm:text-sm font-medium text-[var(--muted)] leading-relaxed truncate">
                Danh sách bộ môn hệ thống, cấu hình mã môn, phân nhóm và hỗ trợ xóa mềm an toàn.
              </p>
            </div>
          </div>

          <div class="flex shrink-0 items-center gap-3">
            <button
              type="button"
              class="rounded-xl border px-4 py-2.5 text-xs font-bold transition flex items-center gap-2"
              :class="activeTab === 'trash' ? 'border-purple-500/40 bg-purple-500/10 text-purple-300' : 'border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)] hover:text-[var(--text)]'"
              @click="toggleTab(activeTab === 'trash' ? 'active' : 'trash')"
            >
              <span>{{ activeTab === 'trash' ? '🌐 Danh sách chính' : '🗑️ Thùng rác môn học' }}</span>
              <span v-if="stats.trashed > 0" class="rounded-full bg-rose-500/20 text-rose-300 px-2 py-0.5 text-[10px] font-black border border-rose-500/30">
                {{ stats.trashed }}
              </span>
            </button>

            <button
              type="button"
              class="btn-primary flex items-center gap-2 px-5 py-2.5 text-xs font-bold shadow-lg transition hover:scale-105"
              @click="openCreateModal"
            >
              <span class="text-base font-black">+</span>
              <span>Thêm bộ môn mới</span>
            </button>
          </div>
        </div>

        <!-- 4 KPI Stat Cards Row (Minimal SaaS Styling) -->
        <div class="mt-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
          <!-- Card 1: Tổng bộ môn -->
          <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 transition hover:border-[var(--border-strong)]">
            <div class="flex items-center gap-3.5">
              <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-purple-500/10 text-purple-400 border border-purple-500/20">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <span class="text-xs font-medium text-[var(--muted)] block truncate">Tổng bộ môn</span>
                <p class="text-2xl font-bold text-[var(--text)] tracking-tight mt-0.5">{{ stats.total || 0 }}</p>
              </div>
            </div>
          </div>

          <!-- Card 2: Nhóm Tự nhiên -->
          <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 transition hover:border-emerald-500/30">
            <div class="flex items-center gap-3.5">
              <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="2"/>
                  <path d="M12 2a10 10 0 0 0-10 10c0 5.523 4.477 10 10 10s10-4.477 10-10A10 10 0 0 0 12 2z"/>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <span class="text-xs font-medium text-[var(--muted)] block truncate">Khoa học Tự nhiên</span>
                <p class="text-2xl font-bold text-[var(--text)] tracking-tight mt-0.5">{{ naturalGroupCount }}</p>
              </div>
            </div>
          </div>

          <!-- Card 3: Nhóm Xã hội & Ngôn ngữ -->
          <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 transition hover:border-amber-500/30">
            <div class="flex items-center gap-3.5">
              <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <span class="text-xs font-medium text-[var(--muted)] block truncate">Xã hội & Ngoại ngữ</span>
                <p class="text-2xl font-bold text-[var(--text)] tracking-tight mt-0.5">{{ socialGroupCount }}</p>
              </div>
            </div>
          </div>

          <!-- Card 4: Thùng rác -->
          <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 transition hover:border-rose-500/30">
            <div class="flex items-center gap-3.5">
              <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <span class="text-xs font-medium text-[var(--muted)] block truncate">Thùng rác</span>
                <p class="text-2xl font-bold text-[var(--text)] tracking-tight mt-0.5">{{ stats.trashed || 0 }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between rounded-xl border border-[var(--border)] bg-[var(--surface)] p-3.5 shadow-sm backdrop-blur-xl">
      <!-- Search Input -->
      <div class="relative flex-1 min-w-[240px]">
        <input
          v-model="filters.search"
          type="text"
          class="w-full rounded-lg border border-[var(--border)] bg-[var(--surface-soft)] py-2 pl-9 pr-3 text-xs sm:text-sm font-medium text-[var(--text)] placeholder-[var(--muted)] outline-none transition focus:border-[var(--primary)]"
          placeholder="🔎 Tìm tên môn hoặc mã môn..."
          @input="debounceSearch"
        />
      </div>

      <!-- Filter Dropdowns -->
      <div class="flex flex-wrap items-center gap-2.5">
        <select
          v-model="filters.category_group"
          class="rounded-lg border border-[var(--border)] bg-[var(--surface-soft)] px-3.5 py-2 text-xs sm:text-sm font-medium text-[var(--text)] outline-none cursor-pointer transition focus:border-[var(--primary)]"
          @change="fetchSubjects"
        >
          <option value="">Tất cả nhóm môn ▾</option>
          <option value="natural">Khoa học Tự nhiên</option>
          <option value="social">Khoa học Xã hội</option>
          <option value="foreign_language">Ngoại ngữ</option>
          <option value="technology">Tin học & Công nghệ</option>
          <option value="other">Năng khiếu & Khác</option>
        </select>

        <button
          v-if="filters.search || filters.category_group"
          type="button"
          class="btn-ghost !px-3 !py-2 text-xs font-bold text-rose-400 hover:bg-rose-500/10"
          @click="resetFilters"
        >
          ✕ Bỏ lọc
        </button>
      </div>
    </div>

    <!-- Main Content: HIGH PRECISION COMPACT ADMIN TABLE -->
    <div v-if="isLoading" class="py-16 text-center text-xs font-bold text-[var(--muted)] flex flex-col items-center justify-center gap-2">
      <div class="h-7 w-7 animate-spin rounded-full border-3 border-[var(--primary)] border-t-transparent"></div>
      <span>Đang tải danh sách bộ môn...</span>
    </div>

    <div v-else-if="displayedSubjects.length === 0" class="rounded-xl border border-[var(--border)] bg-[var(--surface)] p-12 text-center shadow-sm">
      <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-xl bg-[var(--surface-soft)] text-xl text-[var(--muted)]">
        📁
      </div>
      <h3 class="text-base font-bold text-[var(--text)]">
        {{ activeTab === 'trash' ? 'Thùng rác trống' : 'Không tìm thấy bộ môn nào' }}
      </h3>
      <p class="mt-1 text-xs text-[var(--muted)]">
        {{ activeTab === 'trash' ? 'Chưa có môn học nào bị xóa mềm.' : 'Thử thay đổi từ khóa tìm kiếm hoặc bấm thêm bộ môn mới.' }}
      </p>
    </div>

    <!-- Ultra-Clean Balanced Admin Table (Tỉ lệ cột 36% - 29% - 11% - 11% - 13%) -->
    <div v-else class="overflow-hidden rounded-xl border border-[var(--border)] bg-[var(--surface)] shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left text-sm text-[var(--text)]">
          <thead>
            <tr class="border-b border-[var(--border)] bg-[var(--surface-soft)] text-xs font-semibold uppercase tracking-wider text-[var(--muted)] h-11">
              <th class="py-3 pl-6 pr-3 w-[36%] min-w-[210px] align-middle">Môn học</th>
              <th class="py-3 px-3 w-[28%] min-w-[170px] align-middle">Nhóm môn</th>
              <th class="py-3 px-2 w-[11%] text-center min-w-[90px] align-middle">Số Quiz</th>
              <th class="py-3 px-2 w-[11%] text-center min-w-[100px] align-middle">Số câu hỏi</th>
              <th class="py-3 pl-2 pr-7 w-[14%] text-right min-w-[130px] align-middle">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--border)]">
            <tr 
              v-for="subject in displayedSubjects" 
              :key="subject.id"
              class="h-[70px] transition duration-150 hover:bg-[var(--surface-soft)]/60 align-middle"
            >
              <!-- 1. MÔN HỌC ([Icon 40x40] Tên môn 16px font-700 + #code 12-13px font-bold) -->
              <td class="py-3 pl-6 pr-3 align-middle">
                <div class="flex items-center gap-3.5 min-w-0">
                  <!-- 40x40 Uniform SVG Container -->
                  <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400">
                    <component :is="renderSubjectIcon(subject.code, subject.name)" />
                  </div>

                  <div class="min-w-0 flex-1">
                    <h4 class="font-bold text-base text-[var(--text)] leading-snug truncate">
                      {{ subject.name }}
                    </h4>
                    <div class="mt-0.5">
                      <span class="font-mono text-xs font-bold text-purple-400 tracking-wide">
                        #{{ subject.code }}
                      </span>
                    </div>
                  </div>
                </div>
              </td>

              <!-- 2. NHÓM MÔN (Badge 13px font-semibold) -->
              <td class="py-3 px-3 align-middle">
                <span
                  class="inline-flex items-center gap-1.5 rounded-md px-3 py-1 text-[13px] font-semibold border"
                  :class="getCategoryGroupClass(subject.category_group)"
                >
                  <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                  <span>{{ getCategoryGroupLabel(subject.category_group) }}</span>
                </span>
              </td>

              <!-- 3. SỐ QUIZ (Thẳng hàng tuyệt đối theo Tabular Nums) -->
              <td class="py-3 px-2 text-center align-middle">
                <div class="inline-flex items-baseline gap-1.5 justify-center font-mono">
                  <strong class="w-7 text-right tabular-nums text-[16px] font-black text-white leading-none tracking-tight shrink-0">{{ subject.quizzes_count || 0 }}</strong>
                  <span class="font-sans text-xs font-semibold text-[var(--muted)] opacity-75">Quiz</span>
                </div>
              </td>

              <!-- 4. SỐ CÂU HỎI (Thẳng hàng tuyệt đối theo Tabular Nums) -->
              <td class="py-3 px-2 text-center align-middle">
                <div class="inline-flex items-baseline gap-1.5 justify-center font-mono">
                  <strong class="w-8 text-right tabular-nums text-[16px] font-black text-white leading-none tracking-tight shrink-0">{{ subject.questions_count || 0 }}</strong>
                  <span class="font-sans text-xs font-semibold text-[var(--muted)] opacity-75">Câu hỏi</span>
                </div>
              </td>

              <!-- 5. THAO TÁC (Thoáng lề phải pr-7 với Nút Sửa 13px + Dropdown menu ⋮) -->
              <td class="py-3 pl-2 pr-7 text-right align-middle">
                <div class="relative flex items-center justify-end gap-1.5">
                  <template v-if="activeTab === 'active'">
                    <button
                      type="button"
                      class="rounded-lg border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1.5 text-[13px] font-bold text-[var(--text)] hover:border-[var(--primary)] hover:text-[var(--primary)] transition"
                      title="Chỉnh sửa môn học"
                      @click.stop="openEditModal(subject)"
                    >
                      Sửa
                    </button>

                    <!-- Dropdown Menu Trigger ⋮ -->
                    <button
                      type="button"
                      class="h-8 w-8 rounded-lg border border-[var(--border)] bg-[var(--surface-soft)] text-xs font-bold text-[var(--muted)] hover:text-[var(--text)] hover:border-[var(--border-strong)] transition flex items-center justify-center"
                      title="Thao tác khác"
                      @click.stop="toggleDropdown(subject.id)"
                    >
                      ⋮
                    </button>

                    <!-- Dropdown Content -->
                    <div
                      v-if="openMenuId === subject.id"
                      class="absolute right-0 top-10 z-50 min-w-[160px] rounded-xl border border-[var(--border)] bg-[var(--surface)] p-1.5 shadow-xl backdrop-blur-xl text-left grid gap-1"
                      @click.stop
                    >
                      <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-[var(--text)] hover:bg-[var(--surface-soft)] transition"
                        @click="openEditModal(subject); closeDropdown()"
                      >
                        <span>✏️</span>
                        <span>Chỉnh sửa</span>
                      </button>

                      <router-link
                        :to="`/admin/question-bank?subject_id=${subject.id}`"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-[var(--text)] hover:bg-[var(--surface-soft)] transition"
                        @click="closeDropdown"
                      >
                        <span>❓</span>
                        <span>Xem câu hỏi</span>
                      </router-link>

                      <router-link
                        :to="`/admin/quizzes?subject_id=${subject.id}`"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-[var(--text)] hover:bg-[var(--surface-soft)] transition"
                        @click="closeDropdown"
                      >
                        <span>📝</span>
                        <span>Xem Quiz</span>
                      </router-link>

                      <div class="border-t border-[var(--border)] my-0.5"></div>

                      <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-rose-400 hover:bg-rose-500/10 transition"
                        @click="confirmSoftDelete(subject); closeDropdown()"
                      >
                        <span>🗑️</span>
                        <span>Xóa môn học</span>
                      </button>
                    </div>
                  </template>

                  <template v-else>
                    <button
                      type="button"
                      class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 text-[13px] font-bold text-emerald-400 hover:bg-emerald-500/20 transition"
                      title="Khôi phục môn học"
                      @click="handleRestore(subject)"
                    >
                      Khôi phục
                    </button>

                    <button
                      type="button"
                      class="rounded-lg border border-rose-500/30 bg-rose-500/10 px-2.5 py-1.5 text-[13px] font-bold text-rose-400 hover:bg-rose-500/20 transition"
                      title="Xóa vĩnh viễn khỏi CSDL"
                      @click="confirmForceDelete(subject)"
                    >
                      Xóa vĩnh viễn
                    </button>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL THÊM / SỬA BỘ MÔN (PREMIUM SAAS MODAL) -->
    <div
      v-if="isModalOpen"
      class="fixed inset-0 z-[100] flex items-center justify-center bg-black/75 p-4 sm:p-6 backdrop-blur-md overflow-y-auto"
      @click.self="closeModal"
    >
      <div class="relative w-full max-w-lg my-auto rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-6 sm:p-7 shadow-2xl backdrop-blur-2xl grid gap-5">
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-[var(--border)] pb-4">
          <div class="flex items-center gap-3">
            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
              <component :is="renderSubjectIcon(modalForm.code, modalForm.name)" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-[var(--text)]">
                {{ isEditing ? 'Chỉnh sửa Bộ môn' : 'Thêm Bộ môn mới' }}
              </h3>
              <p class="text-xs text-[var(--muted)]">
                Thiết lập thông tin bộ môn chuẩn hóa toàn hệ thống
              </p>
            </div>
          </div>

          <button
            type="button"
            class="grid h-8 w-8 place-items-center rounded-lg bg-[var(--surface-soft)] text-xs font-bold text-[var(--muted)] hover:text-[var(--text)] transition"
            @click="closeModal"
          >
            ✕
          </button>
        </div>

        <!-- Modal Form Body -->
        <form class="grid gap-4" @submit.prevent="saveSubject">
          
          <!-- Tên môn & Mã môn Row -->
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-[var(--muted)]">
                Tên Bộ môn <span class="text-rose-400">*</span>
              </label>
              <input
                v-model="modalForm.name"
                type="text"
                required
                class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-xs sm:text-sm font-semibold text-[var(--text)] outline-none transition focus:border-[var(--primary)]"
                placeholder="VD: Toán học, Tin học..."
                @input="autoGenerateCode"
              />
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-[var(--muted)]">
                Mã Bộ môn (Code) <span class="text-rose-400">*</span>
              </label>
              <input
                v-model="modalForm.code"
                type="text"
                required
                class="w-full font-mono rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-xs sm:text-sm font-semibold text-purple-400 outline-none transition focus:border-[var(--primary)]"
                placeholder="VD: math, informatics"
              />
            </div>
          </div>

          <!-- Nhóm danh mục & Thứ tự Row -->
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-[var(--muted)]">
                Nhóm bộ môn
              </label>
              <select
                v-model="modalForm.category_group"
                class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-xs sm:text-sm font-semibold text-[var(--text)] outline-none cursor-pointer focus:border-[var(--primary)]"
              >
                <option value="natural">Khoa học Tự nhiên</option>
                <option value="social">Khoa học Xã hội</option>
                <option value="foreign_language">Ngoại ngữ</option>
                <option value="technology">Tin học & Công nghệ</option>
                <option value="other">Năng khiếu & Khác</option>
              </select>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-[var(--muted)]">
                Thứ tự hiển thị (Order)
              </label>
              <input
                v-model.number="modalForm.order"
                type="number"
                min="0"
                class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-xs sm:text-sm font-semibold text-[var(--text)] outline-none focus:border-[var(--primary)]"
                placeholder="0"
              />
            </div>
          </div>

          <!-- Modal Footer Actions -->
          <div class="flex items-center justify-end gap-3 border-t border-[var(--border)] pt-4 mt-2">
            <button
              type="button"
              class="btn-ghost !px-4 !py-2 text-xs font-bold"
              @click="closeModal"
            >
              Hủy
            </button>

            <button
              type="submit"
              class="btn-primary !px-6 !py-2 text-xs font-bold shadow-md"
              :disabled="isSubmitting"
            >
              {{ isSubmitting ? 'Đang lưu...' : (isEditing ? 'Lưu thay đổi' : 'Tạo môn học ngay') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject, h } from 'vue'
import { adminSubjectsApi } from '@/services/api'

const showToast = inject('showToast', (msg) => alert(msg))

const isLoading = ref(true)
const isSubmitting = ref(false)
const activeTab = ref('active') // 'active' | 'trash'
const openMenuId = ref(null)

const subjects = ref([])
const trashedSubjects = ref([])
const stats = reactive({ total: 0, trashed: 0 })

const filters = reactive({
  search: '',
  category_group: '',
})

let searchTimeout = null

// Modal State
const isModalOpen = ref(false)
const isEditing = ref(false)
const currentSubjectId = ref(null)

const modalForm = reactive({
  name: '',
  code: '',
  category_group: 'natural',
  order: 0,
})

// Lucide SVG Icon Renderer Component Generator
const renderSubjectIcon = (code, name) => {
  const key = ((code || '') + ' ' + (name || '')).toLowerCase()

  // Calculator (Toán học)
  if (key.includes('math') || key.includes('toan')) {
    return h('svg', { class: 'h-5 w-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
      h('rect', { x: '4', y: '2', width: '16', height: '20', rx: '2' }),
      h('line', { x1: '8', y1: '6', x2: '16', y2: '6' }),
      h('line', { x1: '16', y1: '14', x2: '16', y2: '18' }),
      h('path', { d: 'M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M8 18h.01M12 18h.01' })
    ])
  }
  // BookOpen (Ngữ văn / Tiếng Việt)
  if (key.includes('lit') || key.includes('van') || key.includes('viet')) {
    return h('svg', { class: 'h-5 w-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
      h('path', { d: 'M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z' }),
      h('path', { d: 'M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z' })
    ])
  }
  // Languages (Tiếng Anh / Ngoại ngữ)
  if (key.includes('eng') || key.includes('anh') || key.includes('foreign') || key.includes('lang')) {
    return h('svg', { class: 'h-5 w-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
      h('path', { d: 'm5 8 6 6' }),
      h('path', { d: 'm4 14 6-6 2-3' }),
      h('path', { d: 'M2 5h12' }),
      h('path', { d: 'M7 2h1' }),
      h('path', { d: 'm22 22-5-10-5 10' }),
      h('path', { d: 'M14 18h6' })
    ])
  }
  // Atom (Vật lý)
  if (key.includes('phys') || key.includes('ly')) {
    return h('svg', { class: 'h-5 w-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
      h('circle', { cx: '12', cy: '12', r: '2' }),
      h('path', { d: 'M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41' })
    ])
  }
  // FlaskConical (Hóa học)
  if (key.includes('chem') || key.includes('hoa')) {
    return h('svg', { class: 'h-5 w-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
      h('path', { d: 'M10 2v7.527a2 2 0 0 1-.211.896L4.72 20.55a1 1 0 0 0 .9 1.45h12.76a1 1 0 0 0 .9-1.45l-5.069-10.127A2 2 0 0 1 14 9.527V2' }),
      h('line', { x1: '8.5', y1: '2', x2: '15.5', y2: '2' }),
      h('line', { x1: '7', y1: '16', x2: '17', y2: '16' })
    ])
  }
  // Dna (Sinh học)
  if (key.includes('bio') || key.includes('sinh')) {
    return h('svg', { class: 'h-5 w-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
      h('path', { d: 'M2 15c6.667-6 13.333 0 20-6' }),
      h('path', { d: 'M9 22c1.798-1.998 2.518-3.995 2.807-5.993' }),
      h('path', { d: 'M15 2c-1.798 1.998-2.518 3.995-2.807 5.993' }),
      h('path', { d: 'm17 6-2.5 2.5' }),
      h('path', { d: 'm7 18 2.5-2.5' })
    ])
  }
  // Landmark (Lịch sử)
  if (key.includes('hist') || key.includes('su')) {
    return h('svg', { class: 'h-5 w-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
      h('line', { x1: '3', y1: '22', x2: '21', y2: '22' }),
      h('line', { x1: '6', y1: '18', x2: '6', y2: '11' }),
      h('line', { x1: '10', y1: '18', x2: '10', y2: '11' }),
      h('line', { x1: '14', y1: '18', x2: '14', y2: '11' }),
      h('line', { x1: '18', y1: '18', x2: '18', y2: '11' }),
      h('polygon', { points: '12 2 20 7 4 7 12 2' })
    ])
  }
  // Globe (Địa lý)
  if (key.includes('geo') || key.includes('dia')) {
    return h('svg', { class: 'h-5 w-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
      h('circle', { cx: '12', cy: '12', r: '10' }),
      h('line', { x1: '2', y1: '12', x2: '22', y2: '12' }),
      h('path', { d: 'M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z' })
    ])
  }
  // Scale (GDCD / Pháp luật)
  if (key.includes('civic') || key.includes('gdcd') || key.includes('phap')) {
    return h('svg', { class: 'h-5 w-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
      h('path', { d: 'm16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1z' }),
      h('path', { d: 'm2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1z' }),
      h('path', { d: 'M7 21h10' }),
      h('path', { d: 'M12 3v18' }),
      h('path', { d: 'M3 7h18' })
    ])
  }
  // Monitor (Tin học / Công nghệ)
  if (key.includes('info') || key.includes('tech') || key.includes('tin') || key.includes('cong')) {
    return h('svg', { class: 'h-5 w-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
      h('rect', { x: '2', y: '3', width: '20', height: '14', rx: '2' }),
      h('line', { x1: '8', y1: '21', x2: '16', y2: '21' }),
      h('line', { x1: '12', y1: '17', x2: '12', y2: '21' })
    ])
  }

  // Fallback: Book Icon
  return h('svg', { class: 'h-5 w-5', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('path', { d: 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20' }),
    h('path', { d: 'M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z' })
  ])
}

const fetchSubjects = async () => {
  isLoading.value = true
  try {
    if (activeTab.value === 'active') {
      const data = await adminSubjectsApi.list(filters)
      subjects.value = data.subjects || []
      stats.total = data.stats?.total || 0
      stats.trashed = data.stats?.trashed || 0
    } else {
      const data = await adminSubjectsApi.trash()
      trashedSubjects.value = Array.isArray(data) ? data : []
    }
  } catch (e) {
    console.error('Lỗi khi tải danh sách môn học:', e)
    showToast('Không thể tải danh sách môn học.', 'error')
  } finally {
    isLoading.value = false
  }
}

const toggleTab = (tab) => {
  activeTab.value = tab
  fetchSubjects()
}

const toggleDropdown = (id) => {
  if (openMenuId.value === id) {
    openMenuId.value = null
  } else {
    openMenuId.value = id
  }
}

const closeDropdown = () => {
  openMenuId.value = null
}

const debounceSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchSubjects()
  }, 300)
}

const resetFilters = () => {
  filters.search = ''
  filters.category_group = ''
  fetchSubjects()
}

const displayedSubjects = computed(() => {
  return activeTab.value === 'active' ? subjects.value : trashedSubjects.value
})

const naturalGroupCount = computed(() => {
  return subjects.value.filter(s => s.category_group === 'natural' || s.category_group === 'technology').length
})

const socialGroupCount = computed(() => {
  return subjects.value.filter(s => s.category_group === 'social' || s.category_group === 'foreign_language').length
})

// Muted Premium Category Group Labels & Styling
const getCategoryGroupLabel = (group) => {
  switch (group) {
    case 'natural': return 'Khoa học Tự nhiên'
    case 'social': return 'Khoa học Xã hội'
    case 'foreign_language': return 'Ngoại ngữ'
    case 'technology': return 'Tin học & Công nghệ'
    default: return 'Năng khiếu / Khác'
  }
}

const getCategoryGroupClass = (group) => {
  switch (group) {
    case 'natural': return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400'
    case 'social': return 'border-amber-500/20 bg-amber-500/10 text-amber-400'
    case 'foreign_language': return 'border-sky-500/20 bg-sky-500/10 text-sky-400'
    case 'technology': return 'border-purple-500/20 bg-purple-500/10 text-purple-400'
    default: return 'border-slate-500/20 bg-slate-500/10 text-slate-300'
  }
}

// Auto generate code from Name
const autoGenerateCode = () => {
  if (!isEditing.value && modalForm.name) {
    modalForm.code = modalForm.name
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/đ/g, 'd')
      .replace(/[^a-z0-9]/g, '_')
      .replace(/_+/g, '_')
      .replace(/^_+|_+$/g, '')
  }
}

// Open Modal
const openCreateModal = () => {
  isEditing.value = false
  currentSubjectId.value = null
  modalForm.name = ''
  modalForm.code = ''
  modalForm.category_group = 'natural'
  modalForm.order = 0
  isModalOpen.value = true
}

const openEditModal = (subject) => {
  isEditing.value = true
  currentSubjectId.value = subject.id
  modalForm.name = subject.name
  modalForm.code = subject.code
  modalForm.category_group = subject.category_group || 'natural'
  modalForm.order = subject.order || 0
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
}

// Submit Form
const saveSubject = async () => {
  if (!modalForm.name.trim() || !modalForm.code.trim()) {
    showToast('Vui lòng nhập tên và mã bộ môn.', 'error')
    return
  }

  isSubmitting.value = true
  try {
    if (isEditing.value) {
      await adminSubjectsApi.update(currentSubjectId.value, modalForm)
      showToast(`Đã cập nhật môn '${modalForm.name}' thành công.`, 'success')
    } else {
      await adminSubjectsApi.create(modalForm)
      showToast(`Đã tạo môn '${modalForm.name}' thành công.`, 'success')
    }

    closeModal()
    fetchSubjects()
  } catch (e) {
    console.error('Lỗi khi lưu bộ môn:', e)
    const errMessage = e.response?.data?.message || 'Có lỗi xảy ra khi lưu môn học.'
    showToast(errMessage, 'error')
  } finally {
    isSubmitting.value = false
  }
}

// Actions: Soft Delete, Restore, Force Delete
const confirmSoftDelete = async (subject) => {
  if (!confirm(`Bạn có chắc chắn muốn chuyển môn học '${subject.name}' vào Thùng rác không?`)) return

  try {
    await adminSubjectsApi.softDelete(subject.id)
    showToast(`Đã chuyển môn '${subject.name}' vào Thùng rác.`, 'success')
    fetchSubjects()
  } catch (e) {
    console.error('Lỗi khi xóa mềm môn học:', e)
    showToast('Không thể xóa mềm môn học này.', 'error')
  }
}

const handleRestore = async (subject) => {
  try {
    await adminSubjectsApi.restore(subject.id)
    showToast(`Đã khôi phục môn '${subject.name}'.`, 'success')
    fetchSubjects()
  } catch (e) {
    console.error('Lỗi khi khôi phục môn học:', e)
    showToast('Không thể khôi phục môn học.', 'error')
  }
}

const confirmForceDelete = async (subject) => {
  if (!confirm(`HÀNH ĐỘNG NGUY HIỂM: Bạn có chắc muốn XÓA VĨNH VIỄN môn '${subject.name}'? Hành động này không thể hoàn tác!`)) return

  try {
    const res = await adminSubjectsApi.forceDelete(subject.id)
    if (res.success) {
      showToast(res.message || 'Đã xóa vĩnh viễn môn học.', 'success')
      fetchSubjects()
    } else {
      showToast(res.message || 'Không thể xóa vĩnh viễn môn học.', 'error')
    }
  } catch (e) {
    console.error('Lỗi khi xóa vĩnh viễn môn học:', e)
    const msg = e.response?.data?.message || 'Không thể xóa vĩnh viễn môn học này.'
    showToast(msg, 'error')
  }
}

onMounted(() => {
  fetchSubjects()
})
</script>
