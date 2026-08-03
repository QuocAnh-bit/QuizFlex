<template>
  <section class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-8 shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <div class="flex items-center gap-3">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-500/15 text-2xl">🔒</div>
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-rose-400">Tài khoản bị khóa</p>
          <h1 class="mt-1 text-3xl font-black tracking-[-0.05em] text-[var(--text)]">Tài khoản đã bị khóa</h1>
        </div>
      </div>

      <p class="mt-6 text-base leading-8 text-[var(--muted)]">
        Tài khoản của bạn hiện không thể sử dụng.
      </p>

      <div class="mt-8 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5">
        <h2 class="text-lg font-black text-[var(--text)]">Lý do khóa</h2>
        <p class="mt-3 text-sm leading-7 text-[var(--muted)]">{{ lockedReason || 'Không có thông tin lý do khóa.' }}</p>
      </div>

      <div class="mt-5 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5">
        <h2 class="text-lg font-black text-[var(--text)]">Ngày khóa</h2>
        <p class="mt-3 text-sm leading-7 text-[var(--muted)]">{{ lockedAt || 'Không có thông tin ngày khóa.' }}</p>
      </div>

      <div class="mt-8 rounded-[1.5rem] border border-dashed border-[var(--border-strong)] bg-[var(--chip-active)] p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <p class="text-sm font-bold leading-7 text-[var(--muted)]">
            Nếu bạn cho rằng đây là nhầm lẫn, hãy gửi kháng cáo để quản trị viên xem xét.
          </p>
          <span
            class="shrink-0 rounded-full border px-3 py-1 text-xs font-black"
            :class="{
              'border-zinc-500/25 bg-zinc-500/10 text-zinc-400': !latestRequest,
              'border-amber-500/25 bg-amber-500/10 text-amber-300': latestRequest?.status === 'pending',
              'border-emerald-500/25 bg-emerald-500/10 text-emerald-300': latestRequest?.status === 'approved',
              'border-rose-500/25 bg-rose-500/10 text-rose-300': latestRequest?.status === 'rejected',
            }"
          >
            <span v-if="!latestRequest">— Chưa gửi</span>
            <span v-else-if="latestRequest.status === 'pending'">🟡 Đang chờ</span>
            <span v-else-if="latestRequest.status === 'approved'">🟢 Đã duyệt</span>
            <span v-else-if="latestRequest.status === 'rejected'">🔴 Đã từ chối</span>
          </span>
        </div>
      </div>

      <div v-if="latestRequest?.status === 'rejected'" class="mt-6 rounded-[1.5rem] border border-rose-500/25 bg-rose-500/10 p-5">
        <p class="text-lg font-black text-rose-300">🔴 Kháng cáo của bạn đã bị từ chối.</p>
        <p class="mt-3 text-sm leading-7 text-[var(--muted)]">Ghi chú từ quản trị viên:</p>
        <p class="mt-2 text-sm font-semibold text-[var(--text)]">{{ latestRequest.admin_note || 'Không có ghi chú.' }}</p>
      </div>

      <div v-if="latestRequest?.status === 'rejected'" class="mt-8 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5">
        <label class="text-sm font-black text-[var(--text)]" for="appeal-message">Nội dung kháng cáo</label>
        <textarea
          id="appeal-message"
          v-model="message"
          rows="6"
          class="mt-3 w-full rounded-[1.25rem] border border-[var(--border)] bg-[var(--surface)] px-4 py-3 text-sm font-medium text-[var(--text)] outline-none transition focus:border-[var(--primary)]"
          placeholder="Tôi nghĩ tài khoản của tôi bị khóa nhầm..."
        />
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
          <p class="text-xs font-semibold text-[var(--muted)]">Tối thiểu 10 ký tự, tối đa 1000 ký tự.</p>
          <button class="btn-primary" type="button" :disabled="isSubmitting" @click="submitAppeal">
            {{ isSubmitting ? 'Đang gửi...' : 'Gửi kháng cáo' }}
          </button>
        </div>
        <p v-if="errorMessage" class="mt-3 text-sm font-semibold text-rose-400">{{ errorMessage }}</p>
        <p v-if="successMessage" class="mt-3 text-sm font-semibold text-emerald-400">{{ successMessage }}</p>
      </div>

      <div v-else-if="latestRequest?.status === 'pending'" class="mt-6 rounded-[1.5rem] border border-amber-500/25 bg-amber-500/10 p-5">
        <p class="text-lg font-black text-amber-300">🟡 Bạn đã gửi kháng cáo.</p>
        <p class="mt-3 text-sm leading-7 text-[var(--muted)]">Quản trị viên sẽ xem xét trong thời gian sớm nhất.</p>
        <p class="mt-2 text-sm font-semibold text-[var(--text)]">Ngày gửi: {{ latestRequest.created_at || 'Không rõ' }}</p>
      </div>

      <div v-else-if="latestRequest?.status === 'approved' && !currentUser?.is_locked" class="mt-6 rounded-[1.5rem] border border-emerald-500/25 bg-emerald-500/10 p-5">
        <p class="text-lg font-black text-emerald-300">🟢 Kháng cáo của bạn đã được duyệt.</p>
        <p class="mt-3 text-sm leading-7 text-[var(--muted)]">Tài khoản của bạn đã được mở khóa.</p>
      </div>

      <!-- Nếu approved nhưng account bị khóa lại (admin khóa lại sau khi duyệt) -->
      <div v-else-if="latestRequest?.status === 'approved' && currentUser?.is_locked" class="mt-8 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5">
        <p class="mb-4 text-sm font-semibold text-amber-400">⚠️ Tài khoản của bạn đã bị khóa lại. Vui lòng gửi kháng cáo mới.</p>
        <label class="text-sm font-black text-[var(--text)]" for="appeal-message">Nội dung kháng cáo</label>
        <textarea
          id="appeal-message"
          v-model="message"
          rows="6"
          class="mt-3 w-full rounded-[1.25rem] border border-[var(--border)] bg-[var(--surface)] px-4 py-3 text-sm font-medium text-[var(--text)] outline-none transition focus:border-[var(--primary)]"
          placeholder="Tôi nghĩ tài khoản của tôi bị khóa nhầm..."
        />
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
          <p class="text-xs font-semibold text-[var(--muted)]">Tối thiểu 10 ký tự, tối đa 1000 ký tự.</p>
          <button class="btn-primary" type="button" :disabled="isSubmitting" @click="submitAppeal">
            {{ isSubmitting ? 'Đang gửi...' : 'Gửi kháng cáo' }}
          </button>
        </div>
        <p v-if="errorMessage" class="mt-3 text-sm font-semibold text-rose-400">{{ errorMessage }}</p>
        <p v-if="successMessage" class="mt-3 text-sm font-semibold text-emerald-400">{{ successMessage }}</p>
      </div>

      <div v-else class="mt-8 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5">
        <label class="text-sm font-black text-[var(--text)]" for="appeal-message">Nội dung kháng cáo</label>
        <textarea
          id="appeal-message"
          v-model="message"
          rows="6"
          class="mt-3 w-full rounded-[1.25rem] border border-[var(--border)] bg-[var(--surface)] px-4 py-3 text-sm font-medium text-[var(--text)] outline-none transition focus:border-[var(--primary)]"
          placeholder="Tôi nghĩ tài khoản của tôi bị khóa nhầm..."
        />
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
          <p class="text-xs font-semibold text-[var(--muted)]">Tối thiểu 10 ký tự, tối đa 1000 ký tự.</p>
          <button class="btn-primary" type="button" :disabled="isSubmitting" @click="submitAppeal">
            {{ isSubmitting ? 'Đang gửi...' : 'Gửi kháng cáo' }}
          </button>
        </div>
        <p v-if="errorMessage" class="mt-3 text-sm font-semibold text-rose-400">{{ errorMessage }}</p>
        <p v-if="successMessage" class="mt-3 text-sm font-semibold text-emerald-400">{{ successMessage }}</p>
      </div>

      <div class="mt-8 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5">
        <h2 class="text-lg font-black text-[var(--text)]">Hoặc liên hệ quản trị viên</h2>
        <div class="mt-4 flex flex-col gap-2 text-sm font-semibold text-[var(--muted)]">
          <a class="hover:text-[var(--primary)]" href="mailto:support@quizflex.com">📧 quizflex@gmail.com</a>
          <a class="hover:text-[var(--primary)]" href="tel:0123456789">☎ 0972554428</a>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { authApi, unlockRequestsApi } from '@/services/api'

const route = useRoute()
const message = ref('')
const isSubmitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const currentUser = ref(null)
const latestRequest = ref(null)

const lockedReason = computed(() => currentUser.value?.locked_reason || route.query.reason || '')
const lockedAt = computed(() => currentUser.value?.locked_at || route.query.locked_at || '')

const loadData = async () => {
  try {
    const user = await authApi.lockedInfo()
    currentUser.value = user
  } catch {
    try {
      const fallback = await authApi.me()
      currentUser.value = fallback
    } catch {
      currentUser.value = null
    }
  }

  try {
    const data = await unlockRequestsApi.latest()
    latestRequest.value = data?.data || data || null
  } catch {
    latestRequest.value = null
  }
}

const reloadUnlockRequest = async () => {
  try {
    const data = await unlockRequestsApi.latest()
    latestRequest.value = data?.data || data || null
  } catch {
    latestRequest.value = null
  }
}

const submitAppeal = async () => {
  errorMessage.value = ''
  successMessage.value = ''

  if (!message.value.trim()) {
    errorMessage.value = 'Vui lòng nhập nội dung kháng cáo.'
    return
  }

  if (message.value.trim().length < 10) {
    errorMessage.value = 'Nội dung kháng cáo tối thiểu 10 ký tự.'
    return
  }

  isSubmitting.value = true
  try {
    await unlockRequestsApi.create({ message: message.value.trim() })
    successMessage.value = 'Đã gửi kháng cáo thành công.'
    message.value = ''
    await loadData()
  } catch (error) {
    errorMessage.value = error.message || 'Không thể gửi kháng cáo.'
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  loadData()
  
  // Setup polling to refresh data every 5 seconds
  // This ensures we detect when account gets locked again or appeal status changes
  const pollInterval = setInterval(async () => {
    await loadData()
  }, 5000)

  // Cleanup interval on unmount
  return () => clearInterval(pollInterval)
})

// Watch for changes in user locked status
// Useful when account status updates during polling
watch(
  () => currentUser.value?.is_locked,
  (newLocked, oldLocked) => {
    if (oldLocked === false && newLocked === true) {
      // Account got locked again, immediately refresh unlock request data
      reloadUnlockRequest()
    }
  }
)
</script>
