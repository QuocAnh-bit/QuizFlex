<template>
  <section class="grid gap-6">
    <div class="flex flex-col gap-4 rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)]">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Admin user</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Chi tiết người dùng</h1>
          <p class="mt-2 text-sm leading-6 text-[var(--muted)]">Xem thông tin cá nhân, role, VIP, quiz, lượt làm bài và lịch sử thanh toán.</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <button class="btn-ghost" type="button" @click="goBack">Quay lại danh sách</button>
        </div>
      </div>

      <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">Đang tải chi tiết user...</div>
      <div v-else-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

      <div v-else class="grid gap-6">
        <div class="grid gap-5 lg:grid-cols-[280px_minmax(0,1fr)]">
          <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
            <div class="flex flex-col items-center gap-4 text-center">
              <div class="flex h-24 w-24 items-center justify-center rounded-full bg-[var(--primary)]/10 text-4xl font-black text-[var(--primary)]">
                {{ user.name?.charAt(0)?.toUpperCase() || 'U' }}
              </div>
              <div>
                <h2 class="text-2xl font-black text-[var(--text)]">{{ user.name }}</h2>
                <p class="text-sm text-[var(--muted)]">{{ user.email }}</p>
              </div>
            </div>

            <div class="mt-6 space-y-5">
              <div class="rounded-3xl bg-[var(--surface-soft)] p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--muted)]">Plan</p>
                <p class="mt-2 text-lg font-black text-[var(--text)]">{{ user.plan_label || user.plan }}</p>
              </div>
              <div class="rounded-3xl bg-[var(--surface-soft)] p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--muted)]">AI còn lại</p>
                <p class="mt-2 text-lg font-black text-[var(--text)]">{{ user.role === 'admin' ? '∞' : (user.ai_quota_remaining ?? 0) + ' lượt' }}</p>
              </div>
              <div class="rounded-3xl bg-[var(--surface-soft)] p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--muted)]">Role hiện tại</p>
                <p class="mt-2 text-lg font-black text-[var(--text)]">{{ user.role_label || user.role }}</p>
              </div>
              <div class="rounded-3xl bg-[var(--surface-soft)] p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--muted)]">Trạng thái VIP</p>
                <p class="mt-2 text-lg font-black text-[var(--text)]">{{ user.vip_status || 'Không VIP' }}</p>
                <p class="mt-1 text-sm text-[var(--muted)]">Hết hạn: {{ formatDate(user.vip_expires_at) || 'Chưa có' }}</p>
              </div>
              <div class="rounded-3xl bg-[var(--surface-soft)] p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--muted)]">Tổng số tiền đã nạp</p>
                <p class="mt-2 text-lg font-black text-[var(--text)]">{{ formatCurrency(user.total_paid || 0) }}</p>
              </div>
            </div>
          </div>

          <div class="grid gap-4">
            <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
              <h3 class="text-lg font-black text-[var(--text)]">Thông tin cơ bản</h3>
              <div class="mt-4 grid gap-3">
                <div class="grid gap-1 text-sm text-[var(--muted)]">
                  <span class="font-semibold text-[var(--text)]">Tên</span>
                  <span>{{ user.name }}</span>
                </div>
                <div class="grid gap-1 text-sm text-[var(--muted)]">
                  <span class="font-semibold text-[var(--text)]">Email</span>
                  <span>{{ user.email }}</span>
                </div>
                <div class="grid gap-1 text-sm text-[var(--muted)]">
                  <span class="font-semibold text-[var(--text)]">Role thực tế</span>
                  <span>{{ user.role_label || user.role }}</span>
                </div>
                <div class="grid gap-1 text-sm text-[var(--muted)]">
                  <span class="font-semibold text-[var(--text)]">Ngày tạo</span>
                  <span>{{ formatDate(user.created_at) }}</span>
                </div>
                <div class="grid gap-1 text-sm text-[var(--muted)]">
                  <span class="font-semibold text-[var(--text)]">Lượt làm bài</span>
                  <span>{{ user.attempts_count ?? 0 }}</span>
                </div>
                <div class="grid gap-1 text-sm text-[var(--muted)]">
                  <span class="font-semibold text-[var(--text)]">Tổng quiz tạo</span>
                  <span>{{ user.quizzes_count ?? user.quizzes?.length ?? 0 }}</span>
                </div>
              </div>
            </div>

            <div class="grid gap-4">
              <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
                <h3 class="text-lg font-black text-[var(--text)]">VIP / thanh toán</h3>
                <div class="mt-4 space-y-3 text-sm text-[var(--muted)]">
                  <div>
                    <p class="font-semibold text-[var(--text)]">User đã mua VIP tháng</p>
                    <p>{{ user.vip_months_purchased?.length ? user.vip_months_purchased.join(', ') : 'Chưa có' }}</p>
                  </div>
                  <div>
                    <p class="font-semibold text-[var(--text)]">User đã nạp tiền tháng</p>
                    <p>{{ user.payment_months?.length ? user.payment_months.join(', ') : 'Chưa có' }}</p>
                  </div>
                </div>
              </div>
              <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
                <h3 class="text-lg font-black text-[var(--text)]">Thông tin quiz</h3>
                <div class="mt-4 grid gap-3 text-sm text-[var(--muted)]">
                  <div>
                    <span class="font-semibold text-[var(--text)]">Quiz đã tạo</span>
                    <span class="block mt-1 text-[var(--text)]">{{ user.quizzes?.length ?? 0 }}</span>
                  </div>
                  <div>
                    <span class="font-semibold text-[var(--text)]">Tổng lượt làm bài của quiz</span>
                    <span class="block mt-1 text-[var(--text)]">{{ user.quizzes?.reduce((sum, quiz) => sum + (quiz.attempts_count || 0), 0) ?? 0 }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <h3 class="text-lg font-black text-[var(--text)]">⚖️ Quản lý tài khoản</h3>
              <button v-if="canManageLock" class="rounded-full border border-[var(--primary)]/25 bg-[var(--primary)]/10 px-4 py-2 text-sm font-black text-[var(--primary)]" type="button" @click="openLockDialog(user?.is_locked ? 'unlock' : 'lock')">
                {{ user?.is_locked ? 'Mở khóa tài khoản' : 'Khóa tài khoản' }}
              </button>
            </div>

            <!-- Thông tin khóa -->
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
              <div class="rounded-3xl bg-[var(--surface-soft)] p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--muted)]">Trạng thái</p>
                <p class="mt-2 text-base font-black text-[var(--text)]">{{ user?.is_locked ? '🔒 Đã khóa' : '🟢 Hoạt động' }}</p>
              </div>
              <div class="rounded-3xl bg-[var(--surface-soft)] p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--muted)]">Người khóa</p>
                <p class="mt-2 text-base font-semibold text-[var(--text)]">{{ user?.locked_by?.name || '—' }}</p>
              </div>
              <div class="rounded-3xl bg-[var(--surface-soft)] p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--muted)]">Ngày khóa</p>
                <p class="mt-2 text-base font-semibold text-[var(--text)]">{{ user?.locked_at ? formatDate(user.locked_at) : '—' }}</p>
              </div>
              <div class="rounded-3xl bg-[var(--surface-soft)] p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-[var(--muted)]">Lý do khóa</p>
                <p class="mt-2 text-base font-semibold text-[var(--text)]">{{ user?.locked_reason || '—' }}</p>
              </div>
            </div>

            <!-- Kháng cáo -->
            <div class="mt-5">
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--muted)]">Kháng cáo</p>

              <div v-if="!appeals.length" class="mt-3 rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 text-sm font-semibold text-[var(--muted)]">
                Chưa có kháng cáo
              </div>

              <div v-else class="mt-3 space-y-2" style="max-height: 360px; overflow-y: auto;">
                <div
                  v-for="appeal in appeals"
                  :key="appeal.id"
                  class="rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 space-y-3"
                >
                  <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="rounded-full border px-3 py-1 text-xs font-black" :class="appealBadgeClass(appeal.status)">
                      {{ appealStatusLabel(appeal.status) }}
                    </span>
                    <span class="text-xs text-[var(--muted)]">{{ formatDate(appeal.created_at) }}</span>
                  </div>
                  <p class="text-sm leading-7 text-[var(--text)]">{{ appeal.message }}</p>

                  <div v-if="appeal.status === 'pending'" class="flex flex-wrap gap-3 pt-1">
                    <button
                      class="rounded-full bg-[var(--primary)] px-5 py-2 text-sm font-black text-white disabled:opacity-60"
                      type="button"
                      :disabled="isAppealLoading"
                      @click="approveAppeal(appeal)"
                    >{{ isAppealLoading ? 'Đang xử lý...' : 'Duyệt mở khóa' }}</button>
                    <button
                      class="rounded-full border border-[var(--border)] bg-[var(--surface)] px-5 py-2 text-sm font-black text-[var(--text)] disabled:opacity-60"
                      type="button"
                      :disabled="isAppealLoading"
                      @click="rejectAppeal(appeal)"
                    >Từ chối</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
            <h2 class="text-xl font-black text-[var(--text)]">Danh sách quiz đã tạo</h2>
            <div class="mt-4 overflow-x-auto">
              <table class="min-w-full text-left text-sm">
                <thead class="border-b border-[var(--border)] bg-[var(--surface-soft)] text-xs uppercase tracking-[0.2em] text-[var(--muted)]">
                  <tr>
                    <th class="px-4 py-3">Tiêu đề</th>
                    <th class="px-4 py-3">Số câu hỏi</th>
                    <th class="px-4 py-3">Tổng lượt làm</th>
                    <th class="px-4 py-3">Điểm trung bình (%)</th>
                    <th class="px-4 py-3">Ngày tạo</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border)]">
                  <tr v-for="quiz in user.quizzes" :key="quiz.id">
                    <td class="px-4 py-3 font-semibold text-[var(--text)]">{{ quiz.title }}</td>
                    <td class="px-4 py-3">{{ quiz.questions_count ?? 0 }}</td>
                    <td class="px-4 py-3">{{ quiz.attempts_count ?? 0 }}</td>
                    <td class="px-4 py-3">{{ quiz.avg_score ?? 0 }}%</td>
                    <td class="px-4 py-3">{{ formatDate(quiz.created_at) }}</td>
                  </tr>
                  <tr v-if="!user.quizzes?.length">
                    <td class="px-4 py-6 text-center text-sm text-[var(--muted)]" colspan="5">Người dùng chưa tạo quiz nào.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
              <h2 class="text-xl font-black text-[var(--text)]">Lịch sử làm bài</h2>
              <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                  <thead class="border-b border-[var(--border)] bg-[var(--surface-soft)] text-xs uppercase tracking-[0.2em] text-[var(--muted)]">
                    <tr>
                      <th class="px-4 py-3">Quiz</th>
                      <th class="px-4 py-3">Điểm</th>
                      <th class="px-4 py-3">Tỷ lệ</th>
                      <th class="px-4 py-3">Trạng thái</th>
                      <th class="px-4 py-3">Ngày</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-[var(--border)]">
                    <tr v-for="attempt in user.attempt_history" :key="attempt.id">
                      <td class="px-4 py-3">{{ attempt.quiz_title || 'Quiz không rõ' }}</td>
                      <td class="px-4 py-3">{{ attempt.score }}/{{ attempt.total_points }}</td>
                      <td class="px-4 py-3">{{ attempt.percent }}%</td>
                      <td class="px-4 py-3">{{ attempt.status }}</td>
                      <td class="px-4 py-3">{{ formatDate(attempt.finished_at) }}</td>
                    </tr>
                    <tr v-if="!user.attempt_history?.length">
                      <td class="px-4 py-6 text-center text-sm text-[var(--muted)]" colspan="5">Chưa có lịch sử làm bài.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
              <h2 class="text-xl font-black text-[var(--text)]">Lịch sử thanh toán</h2>
              <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                  <thead class="border-b border-[var(--border)] bg-[var(--surface-soft)] text-xs uppercase tracking-[0.2em] text-[var(--muted)]">
                    <tr>
                      <th class="px-4 py-3">Mã đơn</th>
                      <th class="px-4 py-3">Nhà cung cấp</th>
                      <th class="px-4 py-3">Số tiền</th>
                      <th class="px-4 py-3">Trạng thái</th>
                      <th class="px-4 py-3">Ngày</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-[var(--border)]">
                    <tr v-for="payment in user.payment_history" :key="payment.id">
                      <td class="px-4 py-3">{{ payment.order_code }}</td>
                      <td class="px-4 py-3">{{ payment.provider }}</td>
                      <td class="px-4 py-3">{{ formatCurrency(payment.amount) }}</td>
                      <td class="px-4 py-3">{{ payment.status }}</td>
                      <td class="px-4 py-3">{{ formatDate(payment.paid_at) }}</td>
                    </tr>
                    <tr v-if="!user.payment_history?.length">
                      <td class="px-4 py-6 text-center text-sm text-[var(--muted)]" colspan="5">Chưa có lịch sử thanh toán.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div v-if="showLockDialog" class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-2xl">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ lockDialogMode === 'lock' ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}</p>
        <h3 class="mt-2 text-2xl font-black text-[var(--text)]">{{ lockDialogMode === 'lock' ? 'Khóa tài khoản' : 'Mở khóa tài khoản' }}</h3>
        <p class="mt-3 text-sm leading-7 text-[var(--muted)]">
          {{ lockDialogMode === 'lock' ? 'Bạn có chắc muốn khóa tài khoản này?' : 'Bạn có chắc muốn mở khóa tài khoản này?' }}
        </p>
        <div v-if="lockDialogMode === 'lock'" class="mt-4 rounded-3xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 text-sm text-[var(--muted)]">
          <p class="font-semibold text-[var(--text)]">Sau khi khóa:</p>
          <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>Không thể đăng nhập</li>
            <li>Không thể sử dụng AI</li>
            <li>Không thể OCR</li>
            <li>Không thể tạo Quiz</li>
            <li>Không thể tham gia Room</li>
          </ul>
        </div>
        <textarea v-if="lockDialogMode === 'lock'" v-model="lockReason" class="mt-4 min-h-[96px] w-full rounded-2xl border border-[var(--border)] bg-[var(--input-bg)] px-4 py-3 text-sm text-[var(--text)] outline-none" placeholder="Lý do khóa (không bắt buộc)"></textarea>
        <div class="mt-5 flex gap-3">
          <button class="flex-1 rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-2 text-sm font-black text-[var(--text)]" type="button" @click="showLockDialog = false">Hủy</button>
          <button class="flex-1 rounded-full bg-[var(--primary)] px-4 py-2 text-sm font-black text-white" type="button" :disabled="isSavingLock" @click="confirmLockAction">
            {{ isSavingLock ? 'Đang xử lý...' : (lockDialogMode === 'lock' ? 'Khóa tài khoản' : 'Mở khóa') }}
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

const ocrQuotaForPlan = (plan) => ({ ultra: '∞', pro: '50', plus: '10', free: '0' }[plan] || '0')

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
  pending: 'border-amber-500/25 bg-amber-500/10 text-amber-300',
  approved: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-300',
  rejected: 'border-rose-500/25 bg-rose-500/10 text-rose-300',
}[status] || 'border-[var(--border)] bg-[var(--surface-soft)] text-[var(--muted)]')

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
  showLockDialog.value = true
}

const confirmLockAction = async () => {
  if (!user.value) return
  isSavingLock.value = true
  errorMessage.value = ''
  try {
    if (lockDialogMode.value === 'lock') {
      await usersApi.lock(user.value.id, { reason: lockReason.value })
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
  const dd = String(date.getDate()).padStart(2, '0')
  const mm = String(date.getMonth() + 1).padStart(2, '0')
  const yyyy = date.getFullYear()
  const HH = String(date.getHours()).padStart(2, '0')
  const min = String(date.getMinutes()).padStart(2, '0')
  return `${dd}/${mm}/${yyyy} ${HH}:${min}`
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
