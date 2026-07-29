<template>
  <section class="grid gap-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Admin UI</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Quản lý user</h1>
        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Tạo, tìm kiếm, sửa role và xóa user, thay đổi plan người dùng</p>
      </div>
    </div>

    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <form class="grid gap-4 lg:grid-cols-[minmax(180px,1.4fr)_minmax(180px,1.4fr)_130px_130px_minmax(180px,1.4fr)_150px]" @submit.prevent="createUser">
        <input v-model="newUser.name" class="field" placeholder="Tên user" />
        <input v-model="newUser.email" class="field" type="email" placeholder="email@example.com" />
        <select v-model="newUser.role" class="field"><option value="user">User</option><option value="admin">Admin</option></select>
        <select v-model="newUser.plan" class="field"><option value="free">Free</option><option value="plus">Plus</option><option value="pro">Pro</option><option value="ultra">Ultra</option></select>
        <input v-model="newUser.password" class="field" type="password" placeholder="Mật khẩu" />
        <button class="btn-primary w-full" type="submit" :disabled="isSaving">Tạo user</button>
      </form>
    </article>

    <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">Đang tải user...</div>
    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>
    <div v-if="successMessage" class="rounded-[2rem] border border-emerald-500/30 bg-emerald-500/10 p-5 text-sm font-bold text-emerald-300">{{ successMessage }}</div>

    <article class="overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--border)] bg-[var(--surface-soft)] p-4">
        <div class="flex flex-wrap gap-2">
          <button
            class="rounded-full px-4 py-2 text-sm font-semibold transition"
            :class="viewMode === 'active' ? 'bg-[var(--primary)] text-white' : 'bg-[var(--surface)] text-[var(--muted)] hover:bg-[var(--surface-soft)]'"
            type="button"
            @click="setViewMode('active')"
          >
            Người dùng hoạt động
          </button>
          <button
            class="rounded-full px-4 py-2 text-sm font-semibold transition"
            :class="viewMode === 'trash' ? 'bg-[var(--primary)] text-white' : 'bg-[var(--surface)] text-[var(--muted)] hover:bg-[var(--surface-soft)]'"
            type="button"
            @click="setViewMode('trash')"
          >
            Thùng rác
          </button>
        </div>
        <p class="text-xs text-[var(--muted)]">Quản lý toàn bộ người dùng, tìm kiếm, lọc, xóa mềm và khôi phục.</p>
      </div>

      <div v-if="viewMode === 'active'" class="space-y-6 p-5">
        <div class="grid gap-4 lg:grid-cols-[1fr_200px_120px]">
          <div class="flex items-center gap-3 rounded-2xl border border-[var(--border)] bg-[var(--input-bg)] px-4 py-3 focus-within:border-[var(--border-strong)]">
            <span>🔍</span>
            <input v-model="search" class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]" placeholder="Tìm user theo tên hoặc email" @keyup.enter="loadUsers" />
          </div>
          <select v-model="roleFilter" class="field" @change="loadUsers">
            <option value="all">Tất cả role</option>
            <option value="admin">Admin</option>
            <option value="user">User</option>
          </select>
          <button class="btn-ghost w-full" type="button" @click="loadUsers">Tìm kiếm</button>
        </div>

        <div class="overflow-x-auto rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)]">
          <table class="min-w-full text-left">
            <thead class="border-b border-[var(--border)] bg-[var(--surface-soft)] text-xs font-black uppercase tracking-[0.2em] text-[var(--muted)]">
              <tr>
                <th class="px-6 py-4">Người dùng</th>
                <th class="px-6 py-4">Role</th>
                <th class="px-6 py-4">Plan</th>
                <th class="px-6 py-4">Quota AI</th>
                <th class="px-6 py-4">OCR</th>
                <th class="px-6 py-4">Quiz</th>
                <th class="px-6 py-4">Lượt làm</th>
                <th class="px-6 py-4">Hết hạn gói</th>
                <th class="px-6 py-4">Hành động</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--border)]">
              <tr v-for="user in users" :key="user.id" class="transition hover:bg-[var(--surface-soft)]">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[var(--primary)]/10 font-bold text-[var(--primary)]">{{ user.name.charAt(0).toUpperCase() }}</div>
                    <div>
                      <p class="font-semibold text-[var(--text)]">{{ user.name }}</p>
                      <p class="text-xs text-[var(--muted)]">{{ user.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm font-semibold">{{ user.role_label || user.role }}</td>
                <td class="px-6 py-4 text-sm font-semibold">{{ user.plan_label || user.plan }}</td>
                <td class="px-6 py-4 text-sm font-semibold">{{ user.aiQuota }}</td>
                <td class="px-6 py-4 text-sm font-semibold">{{ ocrQuotaForPlan(user.plan) }}</td>
                <td class="px-6 py-4 text-sm font-semibold">{{ user.quizzesCount }}</td>
                <td class="px-6 py-4 text-sm font-semibold">{{ user.attemptsCount }}</td>
                <td class="px-6 py-4 text-sm text-[var(--muted)]">{{ formatDate(user.plan_expires_at) || '-' }}</td>
                <td class="px-6 py-4">
                  <div class="flex flex-wrap gap-2">
                    <button class="rounded-full border border-blue-500/30 bg-blue-500/10 px-4 py-2 text-xs font-black text-blue-300" type="button" @click="selectUserForEdit(user)">Sửa</button>
                    <button class="rounded-full border border-blue-500/30 bg-blue-500/10 px-4 py-2 text-xs font-black text-blue-300" type="button" @click="viewUserDetail(user.id)">Xem</button>
                    <button class="rounded-full border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-xs font-black text-rose-300" type="button" :disabled="user.role === 'admin'" @click="deleteUser(user.id)">Xóa</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else class="space-y-6 p-5">
        <div class="grid gap-4 lg:grid-cols-[1fr_200px_120px]">
          <div class="flex items-center gap-3 rounded-2xl border border-[var(--border)] bg-[var(--input-bg)] px-4 py-3 focus-within:border-[var(--border-strong)]">
            <span>🔍</span>
            <input v-model="trashSearch" class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]" placeholder="Tìm user trong thùng rác" @keyup.enter="loadTrash" />
          </div>
          <select v-model="trashRoleFilter" class="field" @change="loadTrash">
            <option value="all">Tất cả role</option>
            <option value="ADMIN">Admin</option>
            <option value="VIP">VIP</option>
            <option value="USER">User</option>
          </select>
          <button class="btn-ghost w-full" type="button" @click="loadTrash">Tìm kiếm</button>
        </div>

        <div class="overflow-x-auto rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)]">
          <table class="min-w-full text-left">
            <thead class="border-b border-[var(--border)] bg-[var(--surface-soft)] text-xs font-black uppercase tracking-[0.2em] text-[var(--muted)]">
              <tr>
                <th class="px-6 py-4">Người dùng</th>
                <th class="px-6 py-4">Role</th>
                <th class="px-6 py-4">VIP hết hạn</th>
                <th class="px-6 py-4">Xóa vào</th>
                <th class="px-6 py-4">Hành động</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--border)]">
              <tr v-for="user in trashedUsers" :key="user.id" class="transition hover:bg-[var(--surface-soft)]">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[var(--primary)]/10 font-bold text-[var(--primary)]">{{ user.name.charAt(0).toUpperCase() }}</div>
                    <div>
                      <p class="font-semibold text-[var(--text)]">{{ user.name }}</p>
                      <p class="text-xs text-[var(--muted)]">{{ user.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm font-semibold">{{ user.role }}</td>
                <td class="px-6 py-4 text-sm text-[var(--muted)]">{{ formatDate(user.vip_expires_at) || '-' }}</td>
                <td class="px-6 py-4 text-sm">{{ formatDate(user.deleted_at) }}</td>
                <td class="px-6 py-4">
                  <div class="flex flex-wrap gap-2">
                    <button class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-4 py-2 text-xs font-black text-emerald-300" type="button" @click="restoreUser(user.id)">Khôi phục</button>
                    <button class="rounded-full border border-red-500/30 bg-red-500/10 px-4 py-2 text-xs font-black text-red-300" type="button" @click="forceDeleteUser(user.id)">Xóa vĩnh viễn</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </article>

    <!-- Confirm Modal -->
    <div v-if="confirmModal.show" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
      <div class="w-full max-w-sm rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-2xl">
        <p class="text-base font-bold text-[var(--text)]">{{ confirmModal.message }}</p>
        <div class="mt-5 flex gap-3">
          <button class="flex-1 rounded-full bg-rose-500 px-4 py-2 text-sm font-black text-white hover:bg-rose-600 transition" type="button" @click="confirmModal.onConfirm">Xác nhận</button>
          <button class="btn-ghost flex-1" type="button" @click="confirmModal.show = false">Hủy</button>
        </div>
      </div>
    </div>

    <!-- Edit User Modal -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          appear
        >
          <div class="w-full max-w-[760px] rounded-2xl border border-gray-200 bg-white shadow-2xl">

            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-100 px-8 py-5">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100">
                  <svg class="h-5 w-5 text-violet-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-lg font-bold text-gray-900">Chỉnh sửa người dùng</h2>
                  <p class="text-xs text-gray-400">Cập nhật thông tin tài khoản, quyền hạn và trạng thái VIP.</p>
                </div>
              </div>
              <button
                type="button"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                @click="closeEditModal"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Form -->
            <div class="px-8 py-6">
              <div class="grid gap-5 sm:grid-cols-2">

                <!-- Tên -->
                <div class="space-y-1.5">
                  <label class="block text-sm font-semibold text-gray-700">Tên</label>
                  <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center">
                      <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                      </svg>
                    </span>
                    <input
                      v-model="editingUser.name"
                      class="h-12 w-full rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-900 outline-none transition-all placeholder:text-gray-400 focus:border-violet-500 focus:bg-white focus:ring-4 focus:ring-violet-100"
                    />
                  </div>
                </div>

                <!-- Email -->
                <div class="space-y-1.5">
                  <label class="block text-sm font-semibold text-gray-700">Email</label>
                  <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center">
                      <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                      </svg>
                    </span>
                    <input
                      v-model="editingUser.email"
                      type="email"
                      class="h-12 w-full rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-900 outline-none transition-all placeholder:text-gray-400 focus:border-violet-500 focus:bg-white focus:ring-4 focus:ring-violet-100"
                    />
                  </div>
                </div>

                <!-- Role -->
                <div class="space-y-1.5">
                  <label class="block text-sm font-semibold text-gray-700">Role</label>
                  <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center">
                      <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                      </svg>
                    </span>
                    <select
                      v-model="editingUser.role"
                      class="h-12 w-full appearance-none rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-900 outline-none transition-all focus:border-violet-500 focus:bg-white focus:ring-4 focus:ring-violet-100 disabled:cursor-not-allowed disabled:opacity-60"
                      :disabled="!canManageRole"
                    >
                      <option value="user">User</option>
                      <option value="admin">Admin</option>
                    </select>
                  </div>
                </div>

                <!-- Plan -->
                <div class="space-y-1.5">
                  <label class="block text-sm font-semibold text-gray-700">Plan</label>
                  <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center">
                      <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                      </svg>
                    </span>
                    <select
                      v-model="editingUser.plan"
                      class="h-12 w-full appearance-none rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-900 outline-none transition-all focus:border-violet-500 focus:bg-white focus:ring-4 focus:ring-violet-100"
                    >
                      <option value="free">Free</option>
                      <option value="plus">Plus</option>
                      <option value="pro">Pro</option>
                      <option value="ultra">Ultra</option>
                    </select>
                  </div>
                </div>

                <!-- OCR quota -->
                <div class="space-y-1.5">
                  <label class="block text-sm font-semibold text-gray-700">OCR</label>
                  <div class="h-12 flex items-center rounded-xl border border-gray-200 bg-gray-100 px-4 text-sm font-semibold text-gray-700">
                    {{ ocrQuotaForPlan(editingUser.plan) }}
                  </div>
                </div>

                <!-- Mật khẩu mới -->
                <div class="space-y-1.5">
                  <label class="block text-sm font-semibold text-gray-700">Mật khẩu mới <span class="font-normal text-gray-400">(để trống nếu không đổi)</span></label>
                  <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center">
                      <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                      </svg>
                    </span>
                    <input
                      v-model="editingUser.password"
                      :type="showPassword ? 'text' : 'password'"
                      class="h-12 w-full rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-11 text-sm text-gray-900 outline-none transition-all placeholder:text-gray-400 focus:border-violet-500 focus:bg-white focus:ring-4 focus:ring-violet-100"
                      placeholder="••••••"
                    />
                    <button
                      type="button"
                      class="absolute inset-y-0 right-3.5 flex items-center text-gray-400 transition hover:text-gray-600"
                      @click="showPassword = !showPassword"
                    >
                      <svg v-if="!showPassword" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                      </svg>
                    </button>
                  </div>
                </div>

              </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-8 py-5">
              <button
                type="button"
                class="h-10 rounded-xl border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50"
                @click="closeEditModal"
              >
                Hủy
              </button>
              <button
                type="button"
                class="flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-500 px-6 text-sm font-semibold text-white shadow-md shadow-violet-200 transition hover:scale-[1.02] hover:shadow-lg hover:shadow-violet-300 disabled:opacity-60 disabled:hover:scale-100"
                :disabled="isSaving"
                @click="saveUserEdit"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                </svg>
                {{ isSaving ? 'Đang lưu...' : 'Lưu thay đổi' }}
              </button>
            </div>

          </div>
        </Transition>
      </div>
    </Transition>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { currentUserStorage, normalizeUser, usersApi } from '@/services/api'

const search = ref('')
const roleFilter = ref('all')
const users = ref([])
const trashedUsers = ref([])
const viewMode = ref('active')
const trashSearch = ref('')
const trashRoleFilter = ref('all')
const isLoading = ref(false)
const router = useRouter()
const isSaving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const newUser = reactive({ name: '', email: '', password: '', role: 'user', plan: 'free' })
const showEditModal = ref(false)
const showPassword = ref(false)
const editingUser = reactive({ id: null, name: '', email: '', role: 'user', plan: 'free', plan_started_at: null, plan_expires_at: null, password: '' })
const currentUser = ref(currentUserStorage.get())

const confirmModal = reactive({ show: false, message: '', onConfirm: () => {} })
const ocrQuotaForPlan = (plan) => ({ ultra: '∞', pro: '50', plus: '10', free: '0' }[plan] || '0')

const isMainAdminUser = (user) => {
  if (!user) return false
  const role = String(user.role || '').toLowerCase()
  const isMainAdminFlag = user.is_main_admin === true || user.is_main_admin === 1 || user.is_main_admin === '1'
  return role === 'admin' && isMainAdminFlag
}

const canManageRole = computed(() => {
  const actor = currentUser.value
  if (!actor) return false
  if (actor.role !== 'admin') return false
  if (!isMainAdminUser(actor)) return false
  if (!editingUser.id) return true
  if (Number(editingUser.id) === Number(actor.id)) return false
  return true
})

const syncCurrentUser = (event) => {
  currentUser.value = event?.detail ?? currentUserStorage.get()
}

const openConfirm = (message, onConfirm) => {
  confirmModal.message = message
  confirmModal.onConfirm = () => { confirmModal.show = false; onConfirm() }
  confirmModal.show = true
}

const loadUsers = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const data = await usersApi.list({
      search: search.value || undefined,
      role: roleFilter.value,
      per_page: 100,
    })
    users.value = data.map(normalizeUser)
  } catch (error) {
    errorMessage.value = `Không tải được user: ${error.message}`
    users.value = []
  } finally {
    isLoading.value = false
  }
}

const createUser = async () => {
  errorMessage.value = ''
  successMessage.value = ''
  if (!newUser.name.trim() || !newUser.email.trim() || !newUser.password.trim()) {
    errorMessage.value = 'Cần nhập đủ tên, email và mật khẩu.'
    return
  }

  isSaving.value = true
  try {
    await usersApi.create({ ...newUser })
    successMessage.value = 'Đã tạo user.'
    newUser.name = ''
    newUser.email = ''
    newUser.password = ''
    newUser.role = 'user'
    newUser.plan = 'free'
    await loadUsers()
  } catch (error) {
    errorMessage.value = `Tạo user thất bại: ${error.message}`
  } finally {
    isSaving.value = false
  }
}

const updateRole = async (user) => {
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const updated = await usersApi.update(user.id, { role: user.role.toUpperCase() })
    const normalized = normalizeUser(updated)
    const userIndex = users.value.findIndex((u) => u.id === user.id)
    if (userIndex > -1) {
      users.value[userIndex] = normalized
    }
    successMessage.value = 'Đã cập nhật role.'
  } catch (error) {
    errorMessage.value = `Cập nhật role thất bại: ${error.message}`
    await loadUsers()
  }
}

const selectUserForEdit = (user) => {
  // ensure we have the latest currentUser from storage
  syncCurrentUser()

  editingUser.id = user.id
  editingUser.name = user.name
  editingUser.email = user.email
  editingUser.role = user.role
  editingUser.plan = user.plan || 'free'
  editingUser.plan_started_at = user.plan_started_at ? user.plan_started_at.slice(0, 16) : null
  editingUser.plan_expires_at = user.plan_expires_at ? user.plan_expires_at.slice(0, 16) : null
  editingUser.password = ''
  showPassword.value = false
  showEditModal.value = true
}

const saveUserEdit = async () => {
  if (!editingUser.name.trim() || !editingUser.email.trim()) {
    errorMessage.value = 'Vui lòng nhập tên và email.'
    return
  }

  errorMessage.value = ''
  successMessage.value = ''
  isSaving.value = true
  try {
    const payload = {
      name: editingUser.name,
      email: editingUser.email,
      role: editingUser.role,
      plan: editingUser.plan,
      plan_started_at: editingUser.plan !== 'free' ? (editingUser.plan_started_at || null) : null,
      plan_expires_at: editingUser.plan !== 'free' ? (editingUser.plan_expires_at || null) : null,
    }
    if (editingUser.password) {
      payload.password = editingUser.password
    }
    const updated = await usersApi.update(editingUser.id, payload)
    const userIndex = users.value.findIndex((u) => u.id === editingUser.id)
    if (userIndex > -1) {
      users.value[userIndex] = normalizeUser(updated)
    }
    successMessage.value = 'Đã cập nhật thông tin user.'
    showEditModal.value = false
  } catch (error) {
    errorMessage.value = `Cập nhật user thất bại: ${error.message}`
  } finally {
    isSaving.value = false
  }
}

const closeEditModal = () => {
  showEditModal.value = false
}

const setViewMode = (mode) => {
  viewMode.value = mode
  if (mode === 'trash') {
    loadTrash()
  } else {
    loadUsers()
  }
}

const loadTrash = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const data = await usersApi.trash({
      search: trashSearch.value || undefined,
      role: trashRoleFilter.value,
      per_page: 100,
    })
    trashedUsers.value = data.map(normalizeUser)
  } catch (error) {
    errorMessage.value = `Không tải được thùng rác: ${error.message}`
    trashedUsers.value = []
  } finally {
    isLoading.value = false
  }
}

const restoreUser = (id) => {
  openConfirm('Khôi phục user này?', async () => {
  errorMessage.value = ''
  successMessage.value = ''
    try {
      await usersApi.restore(id)
      trashedUsers.value = trashedUsers.value.filter((user) => user.id !== id)
      successMessage.value = 'Đã khôi phục user.'
    } catch (error) {
      errorMessage.value = `Khôi phục thất bại: ${error.message}`
    }
  })
}

const forceDeleteUser = (id) => {
  openConfirm('Xóa vĩnh viễn user này? Không thể hoàn tác!', async () => {
  errorMessage.value = ''
  successMessage.value = ''
    try {
      await usersApi.forceDelete(id)
      trashedUsers.value = trashedUsers.value.filter((user) => user.id !== id)
      successMessage.value = 'Đã xóa vĩnh viễn user.'
    } catch (error) {
      errorMessage.value = `Xóa vĩnh viễn thất bại: ${error.message}`
    }
  })
}

const deleteUser = (id) => {
  openConfirm('Xóa user này?', async () => {
  errorMessage.value = ''
  successMessage.value = ''
    try {
      await usersApi.remove(id)
      users.value = users.value.filter((user) => user.id !== id)
      successMessage.value = 'Đã xóa user.'
    } catch (error) {
      errorMessage.value = `Xóa user thất bại: ${error.message}`
    }
  })
}

const viewUserDetail = (id) => {
  router.push({ name: 'admin-user-detail', params: { id } })
}

const formatDate = (value) => {
  if (!value) return 'Chưa rõ'
  const date = new Date(value)
  return date.toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

onMounted(async () => {
  syncCurrentUser()
  window.addEventListener('quizflex-user-updated', syncCurrentUser)
  // AuthController không trả is_main_admin → fetch lại từ UserController để có đủ field
  const actor = currentUser.value
  if (actor?.id) {
    try {
      const fresh = await usersApi.get(actor.id)
      if (fresh) currentUser.value = { ...actor, ...fresh }
    } catch { /* giữ nguyên nếu lỗi */ }
  }
  loadUsers()
})
</script>
