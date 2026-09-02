<template>
  <section class="max-w-6xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div class="flex items-start gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-purple-500/10 text-purple-500">
          <Users class="h-5 w-5" />
        </div>
        <div>
          <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Quản trị hệ thống</p>
          <h1 class="text-3xl font-black tracking-[-0.04em] text-[var(--text)]">Quản lý người dùng</h1>
          <p class="mt-1 text-sm font-medium text-[var(--muted)]">Thêm mới, tìm kiếm, phân quyền tài khoản, nâng cấp gói và xử lý vi phạm.</p>
        </div>
      </div>
    </div>

    <!-- Create User Form Card -->
    <article class="card p-5 space-y-3">
      <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
        <UserPlus class="h-3.5 w-3.5 text-[#7C3AED]" /> Thêm người dùng mới
      </h2>
      <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1.5fr_1.5fr_120px_120px_1.5fr_140px]" @submit.prevent="createUser">
        <input v-model="newUser.name" class="field text-xs" placeholder="Họ và tên" maxlength="100" required />
        <input v-model="newUser.email" class="field text-xs" type="email" placeholder="email@example.com" required />
        <select v-model="newUser.role" class="field text-xs">
          <option value="user">User</option>
          <option value="admin">Admin</option>
        </select>
        <select v-if="newUser.role !== 'admin'" v-model="newUser.plan" class="field text-xs">
          <option value="free">Free</option>
          <option value="plus">Plus</option>
          <option value="pro">Pro</option>
          <option value="ultra">Ultra</option>
        </select>
        <input v-model="newUser.password" class="field text-xs" type="password" placeholder="Mật khẩu (>= 8 ký tự)" required />
        <button class="btn-primary text-xs w-full py-2.5 inline-flex items-center justify-center gap-1.5" type="submit" :disabled="isSaving">
          <UserPlus class="h-3.5 w-3.5" />
          {{ isSaving ? 'Đang tạo...' : 'Tạo tài khoản' }}
        </button>
      </form>
    </article>

    <!-- Feedback Alerts -->
    <div v-if="isLoading" class="card p-8 text-center text-xs text-slate-400 flex items-center justify-center gap-2">
      <RefreshCw class="h-4 w-4 animate-spin text-[#7C3AED]" /> Đang tải danh sách người dùng...
    </div>
    <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700 flex items-start gap-2">
      <AlertCircle class="h-4 w-4 shrink-0 mt-0.5" /> <span>{{ errorMessage }}</span>
    </div>
    <div v-if="successMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-700 flex items-start gap-2">
      <CheckCircle2 class="h-4 w-4 shrink-0 mt-0.5" /> <span>{{ successMessage }}</span>
    </div>

    <!-- Main Table Container -->
    <article class="card overflow-hidden">
      <!-- Tabs Navigation -->
      <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50/70 p-3 px-5 text-xs font-bold overflow-x-auto">
        <button
          class="rounded-lg px-3.5 py-1.5 transition inline-flex items-center gap-1.5 whitespace-nowrap"
          :class="viewMode === 'active' ? 'bg-[#7C3AED] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200/60'"
          type="button"
          @click="setViewMode('active')"
        >
          <Users class="h-3.5 w-3.5" />
          <span>Người dùng hoạt động</span>
        </button>
        <button
          class="relative rounded-lg px-3.5 py-1.5 transition inline-flex items-center gap-1.5 whitespace-nowrap"
          :class="viewMode === 'locked' ? 'bg-[#7C3AED] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200/60'"
          type="button"
          @click="setViewMode('locked')"
        >
          <Lock class="h-3.5 w-3.5" />
          <span>Tài khoản bị khóa</span>
          <span v-if="lockedCount > 0" class="rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold text-white leading-none">{{ lockedCount }}</span>
        </button>
        <button
          class="rounded-lg px-3.5 py-1.5 transition inline-flex items-center gap-1.5 whitespace-nowrap"
          :class="viewMode === 'trash' ? 'bg-[#7C3AED] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200/60'"
          type="button"
          @click="setViewMode('trash')"
        >
          <Trash2 class="h-3.5 w-3.5" />
          <span>Thùng rác</span>
        </button>
      </div>

      <!-- Tab 1: Active Users -->
      <div v-if="viewMode === 'active'" class="p-5 space-y-4">
        <div class="grid gap-3 sm:grid-cols-[1fr_160px_100px]">
          <div class="relative">
            <Search class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
            <input v-model="search" class="field text-xs pl-8 w-full" placeholder="Tìm theo tên hoặc email..." @keyup.enter="loadUsers(1)" />
          </div>
          <select v-model="roleFilter" class="field text-xs" @change="loadUsers(1)">
            <option value="all">Tất cả role</option>
            <option value="admin">Admin</option>
            <option value="user">User</option>
          </select>
          <button class="btn-secondary text-xs inline-flex items-center justify-center gap-1.5" type="button" @click="loadUsers(1)">
            <Search class="h-3.5 w-3.5" /> Tìm kiếm
          </button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-100">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50 text-slate-400 font-bold uppercase text-[10px]">
                <th class="py-3 px-4">Người dùng</th>
                <th class="py-3 px-4">Role</th>
                <th class="py-3 px-4">Gói VIP</th>
                <th class="py-3 px-4">AI / OCR</th>
                <th class="py-3 px-4">Quiz / Lượt</th>
                <th class="py-3 px-4">Hạn gói</th>
                <th class="py-3 px-4 text-right">Thao tác</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-if="!isLoading && users.length === 0">
                <td colspan="7" class="py-10 text-center text-slate-400">
                  <Inbox class="mx-auto mb-2 h-6 w-6 opacity-40" />
                  Không tìm thấy người dùng nào.
                </td>
              </tr>
              <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50/70 transition-colors">
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-2.5">
                    <div class="h-8 w-8 rounded-full bg-purple-500/10 text-[#7C3AED] font-bold flex items-center justify-center text-xs shrink-0">
                      {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <p class="font-bold text-slate-900 leading-tight">{{ user.name }}</p>
                      <p class="text-[11px] text-slate-400">{{ user.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="py-3.5 px-4">
                  <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold" :class="user.role === 'admin' ? 'bg-purple-100 text-[#7C3AED]' : 'bg-slate-100 text-slate-600'">
                    <ShieldCheck v-if="user.role === 'admin'" class="h-3 w-3" />
                    {{ user.role_label || user.role }}
                  </span>
                </td>
                <td class="py-3.5 px-4 font-bold text-slate-700 uppercase text-[10px]">
                  {{ user.plan_label || user.plan }}
                </td>
                <td class="py-3.5 px-4 text-slate-600">
                  {{ user.role === 'admin' ? '∞' : `${user.aiQuota} / ${ocrQuotaForPlan(user.plan)}` }}
                </td>
                <td class="py-3.5 px-4 text-slate-600">
                  {{ user.quizzesCount }} / {{ user.attemptsCount }}
                </td>
                <td class="py-3.5 px-4 text-slate-400 text-[11px]">
                  {{ formatDate(user.plan_expires_at) || '-' }}
                </td>
                <td class="py-3.5 px-4 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button v-if="canEditRow(user)" class="btn-secondary text-[11px] px-2.5 py-1 inline-flex items-center gap-1" type="button" @click="selectUserForEdit(user)">
                      <Pencil class="h-3 w-3" /> Sửa
                    </button>
                    <button class="btn-secondary text-[11px] px-2.5 py-1 inline-flex items-center gap-1" type="button" @click="viewUserDetail(user.id)">
                      <Eye class="h-3 w-3" /> Xem
                    </button>
                    <button v-if="canDeleteRow(user)" class="rounded-lg border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 text-[11px] px-2.5 py-1 font-bold inline-flex items-center gap-1 transition" type="button" @click="deleteUser(user.id)">
                      <Trash2 class="h-3 w-3" /> Xóa
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination for Active Users -->
        <div class="pt-3">
          <AppPagination
            :current-page="activePagination.currentPage"
            :last-page="activePagination.lastPage"
            :total="activePagination.total"
            :per-page="activePagination.perPage"
            :show-always="true"
            item-label="người dùng"
            @change="loadUsers"
          />
        </div>
      </div>

      <!-- Tab 2: Locked Users -->
      <div v-else-if="viewMode === 'locked'" class="p-5 space-y-4">
        <div class="grid gap-3 sm:grid-cols-[1fr_100px]">
          <div class="relative">
            <Search class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
            <input v-model="lockedSearch" class="field text-xs pl-8 w-full" placeholder="Tìm tài khoản bị khóa..." @keyup.enter="loadLockedUsers(1)" />
          </div>
          <button class="btn-secondary text-xs inline-flex items-center justify-center gap-1.5" type="button" @click="loadLockedUsers(1)">
            <Search class="h-3.5 w-3.5" /> Tìm kiếm
          </button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-100">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50 text-slate-400 font-bold uppercase text-[10px]">
                <th class="py-3 px-4">Người dùng</th>
                <th class="py-3 px-4">Role / Plan</th>
                <th class="py-3 px-4">Ngày khóa</th>
                <th class="py-3 px-4">Kháng cáo</th>
                <th class="py-3 px-4 text-right">Thao tác</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-if="!isLoading && lockedUsers.length === 0">
                <td colspan="5" class="py-10 text-center text-slate-400">
                  <Inbox class="mx-auto mb-2 h-6 w-6 opacity-40" />
                  Không có tài khoản nào bị khóa.
                </td>
              </tr>
              <tr v-for="user in lockedUsers" :key="user.id" class="hover:bg-slate-50/70 transition-colors">
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-2.5">
                    <div class="h-8 w-8 rounded-full bg-red-500/10 text-red-600 font-bold flex items-center justify-center text-xs shrink-0">
                      {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <p class="font-bold text-slate-900 leading-tight">{{ user.name }}</p>
                      <p class="text-[11px] text-slate-400">{{ user.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="py-3.5 px-4">
                  <span class="font-bold text-slate-700">{{ user.role_label || user.role }}</span>
                  <span class="text-slate-400 text-[10px] block uppercase">{{ user.plan_label || user.plan }}</span>
                </td>
                <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                  {{ formatDate(user.locked_at) }}
                </td>
                <td class="py-3.5 px-4">
                  <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold" :class="appealBadgeClass(lockedAppealMap[user.id])">
                    {{ appealLabel(lockedAppealMap[user.id]) }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      v-if="lockedAppealMap[user.id] === 'pending'"
                      class="rounded-lg border border-amber-200 bg-amber-50 text-amber-800 text-[11px] px-2.5 py-1 font-bold hover:bg-amber-100 inline-flex items-center gap-1 transition"
                      type="button"
                      @click="openAppealModal(user)"
                    >
                      <MessageSquareWarning class="h-3 w-3" /> Xem kháng cáo
                    </button>
                    <button class="btn-primary text-[11px] px-3 py-1 inline-flex items-center gap-1" type="button" @click="unlockUser(user.id)">
                      <Unlock class="h-3 w-3" /> Mở khóa
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination for locked users -->
        <div class="pt-3">
          <AppPagination
            :current-page="lockedPagination.currentPage"
            :last-page="lockedPagination.lastPage"
            :total="lockedPagination.total"
            :per-page="lockedPagination.perPage"
            :show-always="true"
            item-label="tài khoản bị khóa"
            @change="loadLockedUsers"
          />
        </div>
      </div>

      <!-- Tab 3: Trash -->
      <div v-else-if="viewMode === 'trash'" class="p-5 space-y-4">
        <div class="grid gap-3 sm:grid-cols-[1fr_160px_100px]">
          <div class="relative">
            <Search class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
            <input v-model="trashSearch" class="field text-xs pl-8 w-full" placeholder="Tìm trong thùng rác..." @keyup.enter="loadTrash(1)" />
          </div>
          <select v-model="trashRoleFilter" class="field text-xs" @change="loadTrash(1)">
            <option value="all">Tất cả role</option>
            <option value="ADMIN">Admin</option>
            <option value="VIP">VIP</option>
            <option value="USER">User</option>
          </select>
          <button class="btn-secondary text-xs inline-flex items-center justify-center gap-1.5" type="button" @click="loadTrash(1)">
            <Search class="h-3.5 w-3.5" /> Tìm kiếm
          </button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-100">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="border-b border-slate-100 bg-slate-50 text-slate-400 font-bold uppercase text-[10px]">
                <th class="py-3 px-4">Người dùng</th>
                <th class="py-3 px-4">Role</th>
                <th class="py-3 px-4">Ngày xóa</th>
                <th class="py-3 px-4 text-right">Thao tác</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-if="!isLoading && trashedUsers.length === 0">
                <td colspan="4" class="py-10 text-center text-slate-400">
                  <Inbox class="mx-auto mb-2 h-6 w-6 opacity-40" />
                  Thùng rác trống.
                </td>
              </tr>
              <tr v-for="user in trashedUsers" :key="user.id" class="hover:bg-slate-50/70 transition-colors">
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-2.5">
                    <div class="h-8 w-8 rounded-full bg-slate-200 text-slate-500 font-bold flex items-center justify-center text-xs shrink-0">
                      {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <p class="font-bold text-slate-900 leading-tight">{{ user.name }}</p>
                      <p class="text-[11px] text-slate-400">{{ user.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="py-3.5 px-4 font-bold text-slate-700">{{ user.role }}</td>
                <td class="py-3.5 px-4 text-slate-400 text-[11px]">{{ formatDate(user.deleted_at) }}</td>
                <td class="py-3.5 px-4 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button class="btn-secondary text-[11px] px-2.5 py-1 text-emerald-700 font-bold inline-flex items-center gap-1" type="button" @click="restoreUser(user.id)">
                      <RotateCcw class="h-3 w-3" /> Khôi phục
                    </button>
                    <button class="rounded-lg border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 text-[11px] px-2.5 py-1 font-bold inline-flex items-center gap-1 transition" type="button" @click="forceDeleteUser(user.id)">
                      <Trash2 class="h-3 w-3" /> Xóa vĩnh viễn
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination for Trashed Users -->
        <div class="pt-3">
          <AppPagination
            :current-page="trashPagination.currentPage"
            :last-page="trashPagination.lastPage"
            :total="trashPagination.total"
            :per-page="trashPagination.perPage"
            :show-always="true"
            item-label="người dùng đã xóa"
            @change="loadTrash"
          />
        </div>
      </div>
    </article>

    <!-- Appeal Review Modal -->
    <div v-if="showAppealModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm" @click.self="showAppealModal = false">
      <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-3">
            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-amber-100 text-amber-700">
              <MessageSquareWarning class="h-5 w-5" />
            </div>
            <div>
              <span class="text-[10px] font-bold uppercase text-[#7C3AED]">Kháng cáo khóa tài khoản</span>
              <h3 class="text-base font-bold text-slate-900">{{ selectedLockedUser?.name }}</h3>
            </div>
          </div>
          <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition" @click="showAppealModal = false">
            <X class="h-4 w-4" />
          </button>
        </div>

        <div v-if="isLoadingAppeal" class="py-6 text-center text-xs text-slate-400 flex items-center justify-center gap-2">
          <RefreshCw class="h-4 w-4 animate-spin text-[#7C3AED]" /> Đang tải nội dung...
        </div>
        <div v-else-if="modalAppealRequest" class="space-y-4 text-xs">
          <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 space-y-1">
            <span class="text-[10px] font-bold uppercase text-slate-400">Nội dung kháng cáo</span>
            <p class="text-slate-800 leading-relaxed text-xs">{{ modalAppealRequest.message }}</p>
            <span class="text-[10px] text-slate-400 block pt-1">Gửi lúc: {{ formatDate(modalAppealRequest.created_at) }}</span>
          </div>

          <div class="space-y-1.5">
            <label class="font-bold text-slate-700 block">Ghi chú của Admin</label>
            <textarea v-model="adminNote" rows="3" maxlength="1000" class="field text-xs resize-none" placeholder="Nhập lý do hoặc phản hồi (bắt buộc khi từ chối)..."></textarea>
            <span v-if="adminNoteError" class="text-xs font-bold text-red-600 flex items-center gap-1"><AlertCircle class="h-3 w-3" /> {{ adminNoteError }}</span>
          </div>

          <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
            <button class="btn-secondary text-xs px-3.5 py-1.5 text-red-700 font-bold inline-flex items-center gap-1.5" type="button" :disabled="isActionLoading" @click="rejectRequest">
              <X class="h-3.5 w-3.5" /> Từ chối kháng cáo
            </button>
            <button class="btn-primary text-xs px-4 py-1.5 inline-flex items-center gap-1.5" type="button" :disabled="isActionLoading" @click="approveRequest">
              <Check class="h-3.5 w-3.5" /> Duyệt & Mở khóa
            </button>
          </div>
        </div>
        <div v-else class="py-6 text-center text-xs text-slate-400">Không có kháng cáo đang chờ xử lý.</div>
      </div>
    </div>

    <!-- Edit User Modal -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
      <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-3">
            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-purple-100 text-[#7C3AED]">
              <Pencil class="h-5 w-5" />
            </div>
            <div>
              <span class="text-[10px] font-bold uppercase text-[#7C3AED]">Cập nhật thông tin</span>
              <h3 class="text-base font-bold text-slate-900">Chỉnh sửa người dùng</h3>
            </div>
          </div>
          <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition" @click="closeEditModal">
            <X class="h-4 w-4" />
          </button>
        </div>

        <form class="space-y-3 text-xs" @submit.prevent="saveUserEdit">
          <div class="space-y-1">
            <label class="font-bold text-slate-700 block">Họ và tên</label>
            <input v-model="editingUser.name" maxlength="100" class="field text-xs" required />
          </div>

          <div class="space-y-1">
            <label class="font-bold text-slate-700 block">Email</label>
            <input v-model="editingUser.email" type="email" class="field text-xs" required />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div v-if="!isSubAdmin" class="space-y-1">
              <label class="font-bold text-slate-700 block">Role</label>
              <select v-model="editingUser.role" class="field text-xs" :disabled="!canManageRole || isSelf">
                <option value="user">User</option>
                <option value="admin">Admin</option>
              </select>
            </div>

            <div v-if="editingUser.role !== 'admin'" class="space-y-1">
              <label class="font-bold text-slate-700 block">Gói Plan</label>
              <select v-model="editingUser.plan" class="field text-xs">
                <option value="free">Free</option>
                <option value="plus">Plus</option>
                <option value="pro">Pro</option>
                <option value="ultra">Ultra</option>
              </select>
            </div>
          </div>

          <div class="space-y-1">
            <label class="font-bold text-slate-700 block">Mật khẩu mới (để trống nếu không đổi)</label>
            <input v-model="editingUser.password" type="password" class="field text-xs" placeholder="••••••••" />
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button class="btn-secondary text-xs px-3.5 py-1.5" type="button" @click="closeEditModal">Hủy</button>
            <button class="btn-primary text-xs px-4 py-1.5 inline-flex items-center gap-1.5" type="submit" :disabled="isSaving">
              <RefreshCw v-if="isSaving" class="h-3.5 w-3.5 animate-spin" />
              <Check v-else class="h-3.5 w-3.5" />
              {{ isSaving ? 'Đang lưu...' : 'Lưu thay đổi' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, inject } from 'vue'
import { useRouter } from 'vue-router'
import { currentUserStorage, normalizeUser, unlockRequestsApi, usersApi } from '@/services/api'
import { useAppLoading } from '@/composables/useAppLoading'
import AppPagination from '@/components/common/AppPagination.vue'

const { beginTask, endTask } = useAppLoading()
import {
  Users,
  UserPlus,
  Lock,
  Unlock,
  Trash2,
  RotateCcw,
  Search,
  Pencil,
  Eye,
  ShieldCheck,
  Check,
  X,
  AlertCircle,
  CheckCircle2,
  RefreshCw,
  Inbox,
  MessageSquareWarning,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'

const search = ref('')
const roleFilter = ref('all')
const users = ref([])
const trashedUsers = ref([])
const lockedUsers = ref([])
const lockedCount = ref(0)
const viewMode = ref('active')
const trashSearch = ref('')
const trashRoleFilter = ref('all')
const isLoading = ref(false)

const activePagination = reactive({
  currentPage: 1,
  lastPage: 1,
  total: 0,
  perPage: 10,
})

const trashPagination = reactive({
  currentPage: 1,
  lastPage: 1,
  total: 0,
  perPage: 10,
})

// Locked tab state
const lockedPagination = reactive({
  currentPage: 1,
  lastPage: 1,
  total: 0,
  perPage: 10,
})
const lockedSearch = ref('')
const lockedAppealMap = ref({})
const selectedLockedUser = ref(null)
const modalAppealRequest = ref(null)
const adminNote = ref('')
const isActionLoading = ref(false)
const showAppealModal = ref(false)
const isLoadingAppeal = ref(false)
const adminNoteError = ref('')
const router = useRouter()
const isSaving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const newUser = reactive({ name: '', email: '', password: '', role: 'user', plan: 'free' })
const showEditModal = ref(false)
const editingUser = reactive({ id: null, name: '', email: '', role: 'user', plan: 'free', plan_started_at: null, plan_expires_at: null, password: '' })
const currentUser = ref(currentUserStorage.get())
const showConfirm = inject('showConfirm')

const confirmModal = reactive({ show: false, message: '', onConfirm: () => {} })
const ocrQuotaForPlan = (plan) => ({ ultra: '∞', pro: '50', plus: '10', free: '0' }[plan] || '0')

const isMainAdminUser = (user) => {
  if (!user) return false
  const role = String(user.role || '').toLowerCase()
  const email = String(user.email || '').toLowerCase().trim()
  return role === 'admin' && (
    email === 'vip@gmail.com' ||
    user.is_main_admin === true || user.is_main_admin === 1 || user.is_main_admin === '1'
  )
}

const isSelf = computed(() => {
  const actor = currentUser.value
  return actor && editingUser.id && Number(editingUser.id) === Number(actor.id)
})

const canManageRole = computed(() => {
  const actor = currentUser.value
  if (!actor) return false
  if (actor.role !== 'admin') return false
  if (!isMainAdminUser(actor)) return false
  if (!editingUser.id) return true
  if (isSelf.value) return false
  return true
})

const isSubAdmin = computed(() => {
  const actor = currentUser.value
  if (!actor) return false
  return actor.role === 'admin' && !isMainAdminUser(actor)
})

const canEditRow = (user) => {
  if (isSubAdmin.value && user.role === 'admin') return false
  return true
}

const canDeleteRow = (user) => {
  const actor = currentUser.value
  if (!actor) return false
  if (Number(user.id) === Number(actor.id)) return false
  if (isSubAdmin.value && user.role === 'admin') return false
  return true
}

const syncCurrentUser = (event) => {
  currentUser.value = event?.detail ?? currentUserStorage.get()
}

const openConfirm = (message, onConfirm) => {
  if (showConfirm) {
    showConfirm('Xác nhận', message, onConfirm)
  } else {
    confirmModal.message = message
    confirmModal.onConfirm = () => { confirmModal.show = false; onConfirm() }
    confirmModal.show = true
  }
}

const loadUsers = async (page = 1) => {
  isLoading.value = true
  errorMessage.value = ''
  activePagination.currentPage = page
  try {
    const res = await usersApi.list({
      search: search.value || undefined,
      role: roleFilter.value,
      page,
      per_page: activePagination.perPage,
    })
    const list = res?.items || res?.data || (Array.isArray(res) ? res : [])
    users.value = list.map(normalizeUser)
    activePagination.total = res?.total ?? users.value.length
    activePagination.currentPage = res?.currentPage ?? page
    activePagination.lastPage = res?.lastPage ?? 1
  } catch (error) {
    errorMessage.value = `Không tải được user: ${error.message}`
    users.value = []
  } finally {
    isLoading.value = false
  }
}

const validateName = (value) => {
  const trimmed = (value || '').trim()
  if (!trimmed) return 'Vui lòng nhập tên.'
  if (trimmed.length > 100) return 'Tên tối đa 100 ký tự.'
  return ''
}

const validateEmail = (value) => {
  const trimmed = (value || '').trim()
  if (!trimmed) return 'Email không được để trống.'
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed)) return 'Email chưa đúng định dạng.'
  return ''
}

const validatePassword = (value, required) => {
  if (!value) return required ? 'Vui lòng nhập mật khẩu.' : ''
  if (value.length < 8) return 'Mật khẩu tối thiểu 8 ký tự.'
  return ''
}

const createUser = async () => {
  errorMessage.value = ''
  successMessage.value = ''

  const nameError = validateName(newUser.name)
  const emailError = validateEmail(newUser.email)
  const passwordError = validatePassword(newUser.password, true)

  if (nameError || emailError || passwordError) {
    errorMessage.value = nameError || emailError || passwordError
    return
  }

  isSaving.value = true
  try {
    await usersApi.create({
      ...newUser,
      name: newUser.name.trim(),
      email: newUser.email.trim(),
    })
    successMessage.value = 'Đã tạo user thành công.'
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

const selectUserForEdit = (user) => {
  syncCurrentUser()
  editingUser.id = user.id
  editingUser.name = user.name
  editingUser.email = user.email
  editingUser.role = user.role
  editingUser.plan = user.plan || 'free'
  editingUser.plan_started_at = user.plan_started_at ? user.plan_started_at.slice(0, 16) : null
  editingUser.plan_expires_at = user.plan_expires_at ? user.plan_expires_at.slice(0, 16) : null
  editingUser.password = ''
  showEditModal.value = true
}

const saveUserEdit = async () => {
  errorMessage.value = ''
  successMessage.value = ''

  const nameError = validateName(editingUser.name)
  const emailError = validateEmail(editingUser.email)
  const passwordError = validatePassword(editingUser.password, false)

  if (nameError || emailError || passwordError) {
    errorMessage.value = nameError || emailError || passwordError
    return
  }

  isSaving.value = true
  try {
    const payload = {
      name: editingUser.name.trim(),
      email: editingUser.email.trim(),
      plan: editingUser.plan,
    }
    if (!isSubAdmin.value) {
      payload.role = editingUser.role
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
    loadTrash(1)
  } else if (mode === 'locked') {
    loadLockedUsers(1)
  } else {
    loadUsers(1)
  }
}

const loadLockedUsers = async (page = 1) => {
  isLoading.value = true
  errorMessage.value = ''
  lockedPagination.currentPage = page
  try {
    const res = await usersApi.list({
      is_locked: 1,
      search: lockedSearch.value ? lockedSearch.value.trim() : undefined,
      page,
      per_page: lockedPagination.perPage,
    })
    const list = res?.items || res?.data || (Array.isArray(res) ? res : [])
    lockedUsers.value = list.map(normalizeUser)
    lockedPagination.total = res?.total ?? lockedUsers.value.length
    lockedPagination.currentPage = res?.currentPage ?? page
    lockedPagination.lastPage = res?.lastPage ?? 1
    lockedCount.value = lockedPagination.total
    await loadAppealMap()
  } catch (error) {
    errorMessage.value = `Không tải được danh sách bị khóa: ${error.message}`
    lockedUsers.value = []
  } finally {
    isLoading.value = false
  }
}

const loadLockedCount = async () => {
  try {
    const res = await usersApi.list({ is_locked: 1, per_page: 1 })
    lockedCount.value = res?.total ?? 0
  } catch {
    lockedCount.value = 0
  }
}

const unlockUser = (id) => {
  openConfirm('Mở khóa tài khoản này?', async () => {
    errorMessage.value = ''
    successMessage.value = ''
    try {
      await usersApi.unlock(id)
      if (selectedLockedUser.value?.id === id) selectedLockedUser.value = null
      successMessage.value = 'Đã mở khóa tài khoản thành công.'
      await loadLockedUsers(lockedPagination.currentPage)
      await loadLockedCount()
    } catch (error) {
      errorMessage.value = `Mở khóa thất bại: ${error.message}`
    }
  })
}

const appealLabel = (status) => ({ pending: 'Đang chờ', approved: 'Đã duyệt', rejected: 'Đã từ chối' }[status] || 'Chưa gửi')

const appealBadgeClass = (status) => ({
  pending: 'bg-amber-100 text-amber-800',
  approved: 'bg-emerald-100 text-emerald-800',
  rejected: 'bg-red-100 text-red-800',
}[status] || 'bg-slate-100 text-slate-500')

const loadAppealMap = async () => {
  try {
    const payload = await unlockRequestsApi.adminList({ per_page: 500 })
    const list = payload?.data || payload || []
    const map = {}
    list.forEach((r) => {
      const uid = r.user_id || r.user?.id
      if (!uid) return
      const priority = { pending: 3, rejected: 2, approved: 1 }
      if (!map[uid] || (priority[r.status] || 0) > (priority[map[uid]] || 0)) {
        map[uid] = r.status
      }
    })
    lockedAppealMap.value = map
  } catch {
    lockedAppealMap.value = {}
  }
}

const openAppealModal = async (user) => {
  selectedLockedUser.value = user
  modalAppealRequest.value = null
  adminNote.value = ''
  adminNoteError.value = ''
  showAppealModal.value = true
  isLoadingAppeal.value = true
  try {
    const payload = await unlockRequestsApi.adminList({ user_id: user.id, status: 'pending' })
    const list = payload?.data || payload || []
    modalAppealRequest.value = list[0] || null
  } catch {
    modalAppealRequest.value = null
  } finally {
    isLoadingAppeal.value = false
  }
}

const approveRequest = async () => {
  if (!modalAppealRequest.value) return

  const trimmedNote = adminNote.value.trim()
  if (trimmedNote.length > 1000) {
    adminNoteError.value = 'Ghi chú tối đa 1000 ký tự.'
    return
  }
  adminNoteError.value = ''

  isActionLoading.value = true
  try {
    await unlockRequestsApi.approve(modalAppealRequest.value.id, { admin_note: trimmedNote })
    showAppealModal.value = false
    await loadLockedUsers(lockedPagination.currentPage)
    await loadLockedCount()
  } catch (error) {
    errorMessage.value = error.message || 'Không thể duyệt kháng cáo.'
  } finally {
    isActionLoading.value = false
  }
}

const rejectRequest = async () => {
  if (!modalAppealRequest.value) return

  const trimmedNote = adminNote.value.trim()
  if (!trimmedNote) {
    adminNoteError.value = 'Vui lòng nhập lý do từ chối để người dùng biết.'
    return
  }
  if (trimmedNote.length > 1000) {
    adminNoteError.value = 'Ghi chú tối đa 1000 ký tự.'
    return
  }
  adminNoteError.value = ''

  isActionLoading.value = true
  try {
    await unlockRequestsApi.reject(modalAppealRequest.value.id, { admin_note: trimmedNote })
    showAppealModal.value = false
    await loadAppealMap()
  } catch (error) {
    errorMessage.value = error.message || 'Không thể từ chối kháng cáo.'
  } finally {
    isActionLoading.value = false
  }
}

const loadTrash = async (page = 1) => {
  isLoading.value = true
  errorMessage.value = ''
  trashPagination.currentPage = page
  try {
    const res = await usersApi.trash({
      search: trashSearch.value || undefined,
      role: trashRoleFilter.value,
      page,
      per_page: trashPagination.perPage,
    })
    const list = res?.items || res?.data || (Array.isArray(res) ? res : [])
    trashedUsers.value = list.map(normalizeUser)
    trashPagination.total = res?.total ?? trashedUsers.value.length
    trashPagination.currentPage = res?.currentPage ?? page
    trashPagination.lastPage = res?.lastPage ?? 1
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
      successMessage.value = 'Đã khôi phục user.'
      await loadTrash(trashPagination.currentPage)
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
      successMessage.value = 'Đã xóa vĩnh viễn user.'
      await loadTrash(trashPagination.currentPage)
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
      successMessage.value = 'Đã xóa user.'
      await loadUsers(activePagination.currentPage)
    } catch (error) {
      errorMessage.value = `Xóa user thất bại: ${error.message}`
    }
  })
}

const viewUserDetail = (id) => {
  router.push({ name: 'admin-user-detail', params: { id } })
}

const formatDate = (value) => {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('vi-VN')
}

onMounted(async () => {
  beginTask()
  try {
    syncCurrentUser()
    window.addEventListener('quizflex-user-updated', syncCurrentUser)
    await Promise.all([loadUsers(), loadLockedCount(), loadAppealMap()])
  } finally {
    endTask()
  }
})
</script>