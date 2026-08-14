<template>
  <section class="max-w-6xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Quản trị hệ thống</p>
        <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Hồ sơ người dùng</h1>
        <p class="mt-1 text-sm text-slate-600">Xem thông tin cá nhân, gói dịch vụ, lịch sử quiz và giao dịch.</p>
      </div>
      <button class="btn-secondary text-xs px-3.5 py-1.5" type="button" @click="goBack">
        ← Quay lại danh sách
      </button>
    </div>

    <div v-if="isLoading" class="card p-10 text-center text-xs text-slate-400">Đang tải thông tin...</div>
    <div v-else-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700">{{ errorMessage }}</div>

    <div v-else class="grid gap-6">
      <div class="grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
        <!-- Sidebar Profile Card -->
        <div class="card p-6 text-center space-y-4">
          <div class="h-20 w-20 rounded-full bg-purple-100 text-[#7C3AED] font-black text-2xl flex items-center justify-center mx-auto shadow-sm">
            {{ user.name?.charAt(0)?.toUpperCase() || 'U' }}
          </div>
          <div>
            <h2 class="text-lg font-bold text-slate-900">{{ user.name }}</h2>
            <p class="text-xs text-slate-400">{{ user.email }}</p>
          </div>

          <div class="pt-3 border-t border-slate-100 space-y-2 text-left text-xs">
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
              <span class="text-[10px] font-bold text-slate-400 uppercase block">Gói Plan</span>
              <b class="text-slate-900 font-bold uppercase">{{ user.plan_label || user.plan }}</b>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
              <span class="text-[10px] font-bold text-slate-400 uppercase block">AI Quota còn lại</span>
              <b class="text-[#7C3AED] font-bold">{{ user.role === 'admin' ? '∞ Không giới hạn' : (user.ai_quota_remaining ?? 0) + ' lượt' }}</b>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
              <span class="text-[10px] font-bold text-slate-400 uppercase block">Vai trò (Role)</span>
              <b class="text-slate-900 font-bold">{{ user.role_label || user.role }}</b>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
              <span class="text-[10px] font-bold text-slate-400 uppercase block">Tổng tiền đã nạp</span>
              <b class="text-emerald-700 font-bold">{{ formatCurrency(user.total_paid || 0) }}</b>
            </div>
          </div>
        </div>

        <!-- Account Info & VIP -->
        <div class="grid gap-5">
          <div class="card p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Thông tin chi tiết</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
              <div>
                <span class="text-slate-400 block font-semibold text-[11px]">Ngày tạo tài khoản</span>
                <span class="text-slate-800 font-bold block mt-0.5">{{ formatDate(user.created_at) }}</span>
              </div>
              <div>
                <span class="text-slate-400 block font-semibold text-[11px]">Tổng số quiz đã tạo</span>
                <span class="text-slate-800 font-bold block mt-0.5">{{ user.quizzes_count ?? user.quizzes?.length ?? 0 }} quiz</span>
              </div>
              <div>
                <span class="text-slate-400 block font-semibold text-[11px]">Tổng lượt đã làm bài</span>
                <span class="text-slate-800 font-bold block mt-0.5">{{ user.attempts_count ?? 0 }} lượt</span>
              </div>
              <div>
                <span class="text-slate-400 block font-semibold text-[11px]">VIP hết hạn</span>
                <span class="text-slate-800 font-bold block mt-0.5">{{ formatDate(user.vip_expires_at) || 'Chưa đăng ký' }}</span>
              </div>
            </div>
          </div>

          <!-- Account Lock Management -->
          <div class="card p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <h3 class="text-sm font-bold text-slate-900">Trạng thái khóa tài khoản</h3>
              <button 
                v-if="canManageLock" 
                class="text-xs font-bold px-3 py-1.5 rounded-lg border transition"
                :class="user?.is_locked ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'"
                type="button" 
                @click="openLockDialog(user?.is_locked ? 'unlock' : 'lock')"
              >
                {{ user?.is_locked ? 'Mở khóa tài khoản' : 'Khóa tài khoản này' }}
              </button>
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs">
              <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                <span class="text-[10px] font-bold text-slate-400 uppercase block">Trạng thái</span>
                <b class="inline-flex items-center gap-1.5 font-bold" :class="user?.is_locked ? 'text-red-600' : 'text-emerald-600'">
                  <Lock v-if="user?.is_locked" class="h-3.5 w-3.5" />
                  <CheckCircle2 v-else class="h-3.5 w-3.5" />
                  <span>{{ user?.is_locked ? 'Đang bị khóa' : 'Đang hoạt động' }}</span>
                </b>
              </div>
              <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                <span class="text-[10px] font-bold text-slate-400 uppercase block">Lý do khóa</span>
                <b class="text-slate-800 font-bold">{{ user?.locked_reason || '—' }}</b>
              </div>
            </div>

            <!-- Appeals Section -->
            <div v-if="appeals.length" class="space-y-2 pt-2 border-t border-slate-100">
              <span class="text-xs font-bold text-slate-700 block">Lịch sử kháng cáo:</span>
              <div v-for="appeal in appeals" :key="appeal.id" class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-xs space-y-2">
                <div class="flex items-center justify-between">
                  <span class="rounded px-2 py-0.5 text-[10px] font-bold" :class="appealBadgeClass(appeal.status)">
                    {{ appealStatusLabel(appeal.status) }}
                  </span>
                  <span class="text-slate-400 text-[10px]">{{ formatDate(appeal.created_at) }}</span>
                </div>
                <p class="text-slate-700 leading-relaxed">{{ appeal.message }}</p>
                <div v-if="appeal.status === 'pending'" class="flex items-center gap-2 pt-1">
                  <button class="btn-primary text-[11px] px-3 py-1" type="button" :disabled="isAppealLoading" @click="approveAppeal(appeal)">Duyệt mở khóa</button>
                  <button class="btn-secondary text-[11px] px-3 py-1 text-red-700 font-bold" type="button" :disabled="isAppealLoading" @click="rejectAppeal(appeal)">Từ chối</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Created Quizzes & History -->
      <div class="grid gap-6 lg:grid-cols-2">
        <!-- Created Quizzes -->
        <div class="card p-6 space-y-3">
          <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Quiz đã tạo ({{ user.quizzes?.length ?? 0 }})</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="border-b border-slate-100 text-slate-400 uppercase text-[10px] font-bold">
                  <th class="py-2.5 px-3">Tiêu đề</th>
                  <th class="py-2.5 px-3">Câu</th>
                  <th class="py-2.5 px-3">Lượt làm</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="quiz in user.quizzes" :key="quiz.id">
                  <td class="py-2.5 px-3 font-semibold text-slate-900 truncate max-w-xs">{{ quiz.title }}</td>
                  <td class="py-2.5 px-3 text-slate-600">{{ quiz.questions_count ?? 0 }}</td>
                  <td class="py-2.5 px-3 text-[#7C3AED] font-bold">{{ quiz.attempts_count ?? 0 }}</td>
                </tr>
                <tr v-if="!user.quizzes?.length">
                  <td colspan="3" class="py-6 text-center text-slate-400">Chưa tạo quiz nào.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Payment History -->
        <div class="card p-6 space-y-3">
          <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Lịch sử nạp VIP ({{ user.payment_history?.length ?? 0 }})</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="border-b border-slate-100 text-slate-400 uppercase text-[10px] font-bold">
                  <th class="py-2.5 px-3">Mã đơn</th>
                  <th class="py-2.5 px-3">Số tiền</th>
                  <th class="py-2.5 px-3">Trạng thái</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="payment in user.payment_history" :key="payment.id">
                  <td class="py-2.5 px-3 font-mono text-[11px] text-slate-700">{{ payment.order_code }}</td>
                  <td class="py-2.5 px-3 font-bold text-slate-900">{{ formatCurrency(payment.amount) }}</td>
                  <td class="py-2.5 px-3">
                    <span class="rounded px-2 py-0.5 text-[10px] font-bold" :class="payment.status === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'">
                      {{ payment.status }}
                    </span>
                  </td>
                </tr>
                <tr v-if="!user.payment_history?.length">
                  <td colspan="3" class="py-6 text-center text-slate-400">Chưa có giao dịch nạp tiền.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Lock / Unlock Confirm Dialog -->
    <div v-if="showLockDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl space-y-4">
        <h3 class="text-base font-bold text-slate-900">
          {{ lockDialogMode === 'lock' ? 'Xác nhận khóa tài khoản' : 'Mở khóa tài khoản' }}
        </h3>
        <p class="text-xs text-slate-600 leading-relaxed">
          {{ lockDialogMode === 'lock' ? 'Người dùng bị khóa sẽ không thể đăng nhập, thi đấu hoặc sử dụng tính năng AI.' : 'Tài khoản sẽ được kích hoạt lại ngay lập tức.' }}
        </p>

        <div v-if="lockDialogMode === 'lock'" class="space-y-1">
          <label class="text-xs font-bold text-slate-700 block">Lý do khóa</label>
          <textarea v-model="lockReason" maxlength="500" class="field text-xs resize-none" rows="3" placeholder="Nhập lý do khóa tài khoản..."></textarea>
          <span v-if="lockReasonError" class="text-xs font-bold text-red-600 block">{{ lockReasonError }}</span>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button class="btn-secondary text-xs px-3.5 py-1.5" type="button" @click="showLockDialog = false">Hủy</button>
          <button 
            class="text-xs px-4 py-1.5 rounded-lg font-bold transition text-white" 
            :class="lockDialogMode === 'lock' ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700'"
            type="button" 
            :disabled="isSavingLock" 
            @click="confirmLockAction"
          >
            {{ isSavingLock ? 'Đang xử lý...' : (lockDialogMode === 'lock' ? 'Khóa ngay' : 'Mở khóa') }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { currentUserStorage, unlockRequestsApi, usersApi } from '@/services/api'

const route = useRoute()
const router = useRouter()
const user = ref(null)
const currentUser = ref(currentUserStorage.get())
const isLoading = ref(true)
const errorMessage = ref('')
const showLockDialog = ref(false)
const lockDialogMode = ref('lock')
const lockReason = ref('')
const isSavingLock = ref(false)
const lockReasonError = ref('')
const appeals = ref([])
const isAppealLoading = ref(false)

const isMainAdminUser = (actor) => {
  if (!actor) return false
  const role = String(actor.role || '').toLowerCase()
  const email = String(actor.email || '').toLowerCase().trim()
  return role === 'admin' && (
    email === 'vip@gmail.com' ||
    actor.is_main_admin === true || actor.is_main_admin === 1 || actor.is_main_admin === '1'
  )
}

const canManageLock = computed(() => {
  if (!currentUser.value || !user.value) return false
  if (String(currentUser.value.role || '').toLowerCase() !== 'admin') return false
  if (Number(currentUser.value.id) === Number(user.value.id)) return false
  if (isMainAdminUser(currentUser.value)) {
    return !isMainAdminUser(user.value)
  }
  return String(user.value.role || '').toLowerCase() !== 'admin' && !isMainAdminUser(user.value)
})

const loadActorProfile = async () => {
  const actor = currentUserStorage.get()
  if (!actor?.id || String(actor.role || '').toLowerCase() !== 'admin') {
    currentUser.value = actor
    return
  }

  try {
    const fresh = await usersApi.get(actor.id)
    currentUser.value = { ...actor, ...fresh }
    currentUserStorage.set(currentUser.value)
  } catch {
    currentUser.value = actor
  }
}

const loadUser = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const data = await usersApi.get(route.params.id)
    user.value = data
    await loadLatestAppeal()
  } catch (error) {
    errorMessage.value = `Không tải được chi tiết user: ${error.message}`
    user.value = null
  } finally {
    isLoading.value = false
  }
}

const loadLatestAppeal = async () => {
  if (!user.value?.id) return
  try {
    const payload = await unlockRequestsApi.adminList({ user_id: user.value.id, per_page: 50 })
    const list = payload?.data || payload || []
    appeals.value = Array.isArray(list) ? list : []
  } catch {
    appeals.value = []
  }
}

const appealStatusLabel = (status) => ({ pending: 'Đang chờ', approved: 'Đã duyệt', rejected: 'Đã từ chối' }[status] || status)

const appealBadgeClass = (status) => ({
  pending: 'bg-amber-100 text-amber-800',
  approved: 'bg-emerald-100 text-emerald-800',
  rejected: 'bg-red-100 text-red-800',
}[status] || 'bg-slate-100 text-slate-600')

const approveAppeal = async (appeal) => {
  if (!appeal) return
  isAppealLoading.value = true
  try {
    await unlockRequestsApi.approve(appeal.id, {})
    await loadUser()
  } catch (error) {
    errorMessage.value = error.message || 'Không thể duyệt kháng cáo.'
  } finally {
    isAppealLoading.value = false
  }
}

const rejectAppeal = async (appeal) => {
  if (!appeal) return
  isAppealLoading.value = true
  try {
    await unlockRequestsApi.reject(appeal.id, {})
    await loadLatestAppeal()
  } catch (error) {
    errorMessage.value = error.message || 'Không thể từ chối kháng cáo.'
  } finally {
    isAppealLoading.value = false
  }
}

const goBack = () => router.push({ name: 'admin-users' })

const openLockDialog = (mode) => {
  lockDialogMode.value = mode
  lockReason.value = ''
  lockReasonError.value = ''
  showLockDialog.value = true
}

const confirmLockAction = async () => {
  if (!user.value) return

  if (lockDialogMode.value === 'lock' && lockReason.value.trim().length > 500) {
    lockReasonError.value = 'Lý do khóa tối đa 500 ký tự.'
    return
  }
  lockReasonError.value = ''

  isSavingLock.value = true
  errorMessage.value = ''
  try {
    if (lockDialogMode.value === 'lock') {
      await usersApi.lock(user.value.id, { reason: lockReason.value.trim() })
    } else {
      await usersApi.unlock(user.value.id)
    }
    showLockDialog.value = false
    await loadUser()
    await loadActorProfile()
  } catch (error) {
    errorMessage.value = error.message || 'Không thể thực hiện thao tác.'
  } finally {
    isSavingLock.value = false
  }
}

const formatDate = (value) => {
  if (!value) return 'Chưa rõ'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Chưa rõ'
  return date.toLocaleDateString('vi-VN')
}

const formatCurrency = (value) => {
  const amount = Number(value ?? 0)
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    maximumFractionDigits: 0,
  }).format(amount)
}

onMounted(async () => {
  await loadActorProfile()
  await loadUser()
})
</script>
