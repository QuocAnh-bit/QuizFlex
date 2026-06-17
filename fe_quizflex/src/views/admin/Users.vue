<template>
  <section class="grid gap-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Admin UI</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Quản lý user</h1>
        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Tạo, tìm kiếm, sửa role và xóa user bằng API backend.</p>
      </div>
    </div>

    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <form class="grid gap-4 lg:grid-cols-[1fr_1fr_150px_160px_auto]" @submit.prevent="createUser">
        <input v-model="newUser.name" class="field" placeholder="Tên user" />
        <input v-model="newUser.email" class="field" type="email" placeholder="email@example.com" />
        <select v-model="newUser.role" class="field"><option value="FREE">Free</option><option value="PLUS">Plus</option><option value="PRO">Pro</option><option value="ULTRA">Ultra</option></select>
        <input v-model="newUser.password" class="field" type="password" placeholder="Mật khẩu" />
        <button class="btn-primary" type="submit" :disabled="isSaving">Tạo user</button>
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
            <option value="ADMIN">Admin</option>
            <option value="VIP">VIP</option>
            <option value="USER">User</option>
          </select>
          <button class="btn-ghost w-full" type="button" @click="loadUsers">Tìm kiếm</button>
        </div>

        <div class="overflow-x-auto rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)]">
          <table class="min-w-full text-left">
            <thead class="border-b border-[var(--border)] bg-[var(--surface-soft)] text-xs font-black uppercase tracking-[0.2em] text-[var(--muted)]">
              <tr>
                <th class="px-6 py-4">Người dùng</th>
                <th class="px-6 py-4">Role</th>
                <th class="px-6 py-4">Quota AI</th>
                <th class="px-6 py-4">Quiz</th>
                <th class="px-6 py-4">Lượt làm</th>
                <th class="px-6 py-4">VIP hết hạn</th>
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
                <td class="px-6 py-4">
                  <select v-model="user.role" class="field text-sm" :disabled="user.role === 'admin'" @change="updateRole(user)">
                    <option v-if="user.role === 'admin'" value="admin">Admin</option>
                    <option value="ultra">Ultra</option>
                    <option value="pro">Pro</option>
                    <option value="plus">Plus</option>
                    <option value="free">Free</option>
                  </select>
                </td>
                <td class="px-6 py-4 text-sm font-semibold">{{ user.aiQuota }}</td>
                <td class="px-6 py-4 text-sm font-semibold">{{ user.quizzesCount }}</td>
                <td class="px-6 py-4 text-sm font-semibold">{{ user.attemptsCount }}</td>
                <td class="px-6 py-4 text-sm text-[var(--muted)]">{{ formatDate(user.vip_expires_at) || '-' }}</td>
                <td class="px-6 py-4">
                  <div class="flex flex-wrap gap-2">
                    <button class="rounded-full border border-blue-500/30 bg-blue-500/10 px-4 py-2 text-xs font-black text-blue-300" type="button" @click="selectUserForEdit(user)">Sửa</button>
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

    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-2xl">
        <h2 class="text-xl font-bold text-[var(--text)]">Chỉnh sửa user</h2>
        <p class="mb-4 text-sm text-[var(--muted)]">Cho phép sửa thông tin user, role, quota AI và VIP hết hạn. Role admin sẽ bị khóa.</p>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-[var(--text)] mb-2">Tên</label>
            <input v-model="editingUser.name" class="field w-full" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-[var(--text)] mb-2">Email</label>
            <input v-model="editingUser.email" type="email" class="field w-full" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-[var(--text)] mb-2">Role</label>
            <select v-model="editingUser.role" class="field w-full" :disabled="editingUser.role === 'admin'">
              <option v-if="editingUser.role === 'admin'" value="admin">Admin</option>
              <option value="ultra">Ultra</option>
              <option value="pro">Pro</option>
              <option value="plus">Plus</option>
              <option value="free">Free</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold text-[var(--text)] mb-2">Quota AI</label>
            <input v-model.number="editingUser.ai_quota_remaining" type="number" min="0" class="field w-full" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-[var(--text)] mb-2">VIP hết hạn</label>
            <input v-model="editingUser.vip_expires_at" type="datetime-local" class="field w-full" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-[var(--text)] mb-2">Mật khẩu mới (để trống nếu không đổi)</label>
            <input v-model="editingUser.password" type="password" class="field w-full" placeholder="••••••" />
          </div>
        </div>
        <div class="mt-6 flex gap-3">
          <button class="btn-primary flex-1" type="button" :disabled="isSaving" @click="saveUserEdit">{{ isSaving ? 'Đang lưu...' : 'Lưu' }}</button>
          <button class="btn-ghost flex-1" type="button" @click="closeEditModal">Hủy</button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { normalizeUser, usersApi } from '@/services/api'

const search = ref('')
const roleFilter = ref('all')
const users = ref([])
const trashedUsers = ref([])
const viewMode = ref('active')
const trashSearch = ref('')
const trashRoleFilter = ref('all')
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const newUser = reactive({ name: '', email: '', password: '', role: 'FREE' })
const showEditModal = ref(false)
const editingUser = reactive({ id: null, name: '', email: '', role: 'free', ai_quota_remaining: 0, vip_expires_at: null, password: '' })
const confirmModal = reactive({ show: false, message: '', onConfirm: () => {} })

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
    newUser.role = 'FREE'
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
  editingUser.id = user.id
  editingUser.name = user.name
  editingUser.email = user.email
  editingUser.role = user.role
  editingUser.ai_quota_remaining = user.aiQuota ?? 0
  editingUser.vip_expires_at = user.vip_expires_at || null
  editingUser.password = ''
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
      role: editingUser.role.toUpperCase(),
      ai_quota_remaining: editingUser.ai_quota_remaining,
      vip_expires_at: editingUser.vip_expires_at || null,
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

const formatDate = (value) => {
  if (!value) return 'Chưa rõ'
  const date = new Date(value)
  return date.toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

onMounted(loadUsers)
</script>
