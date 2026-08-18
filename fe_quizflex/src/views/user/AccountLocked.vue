<template>
  <div>
    <Transition name="fade">
      <div v-if="isApproved" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="card p-8 text-center max-w-md w-full space-y-4 shadow-2xl">
          <div class="h-16 w-16 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center text-3xl mx-auto">
            🎉
          </div>
          <h2 class="text-xl font-black text-slate-900">Kháng cáo thành công!</h2>
          <p class="text-xs text-slate-600">Tài khoản của bạn đã được mở khóa. Bạn có thể tiếp tục sử dụng hệ thống bình thường.</p>
          <p class="text-[11px] text-slate-400 font-medium">Tự động chuyển về trang chủ sau <b class="text-[#7C3AED]">{{ countdown }}</b> giây...</p>
          <button class="btn-primary w-full py-2.5 text-xs" type="button" @click="goHome">
            Vào trang chủ ngay
          </button>
        </div>
      </div>
    </Transition>

    <section class="max-w-2xl mx-auto px-4 py-12">
      <div class="card p-8 sm:p-10 space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-3.5 border-b border-slate-100 pb-5">
          <div class="h-12 w-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-2xl shrink-0">
            🔒
          </div>
          <div>
            <span class="text-xs font-bold uppercase tracking-wider text-red-600">Tạm ngưng tài khoản</span>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900">Tài khoản của bạn đang bị khóa</h1>
          </div>
        </div>

        <!-- Lock details -->
        <div class="grid sm:grid-cols-2 gap-3 text-xs">
          <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Lý do khóa</span>
            <b class="text-slate-900 block mt-1 leading-relaxed">{{ lockedReason || 'Vi phạm điều khoản hoặc hoạt động bất thường.' }}</b>
          </div>
          <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Thời gian khóa</span>
            <b class="text-slate-900 block mt-1 leading-relaxed">{{ lockedAt || 'Chưa ghi nhận thời gian' }}</b>
          </div>
        </div>

        <!-- Appeal Status & Banner -->
        <div class="rounded-xl border border-purple-100 bg-purple-50/50 p-4 flex items-center justify-between">
          <p class="text-xs text-slate-700 font-medium">
            Nếu bạn cho rằng đây là nhầm lẫn, hãy gửi đơn kháng cáo để quản trị viên xem xét.
          </p>
          <span
            class="shrink-0 rounded-md px-2.5 py-1 text-xs font-bold"
            :class="{
              'bg-slate-100 text-slate-600': !latestRequest,
              'bg-amber-100 text-amber-800': latestRequest?.status === 'pending',
              'bg-emerald-100 text-emerald-800': latestRequest?.status === 'approved',
              'bg-red-100 text-red-800': latestRequest?.status === 'rejected',
            }"
          >
            <span v-if="!latestRequest">Chưa gửi đơn</span>
            <span v-else-if="latestRequest.status === 'pending'">Đang chờ duyệt</span>
            <span v-else-if="latestRequest.status === 'approved'">Đã được duyệt</span>
            <span v-else-if="latestRequest.status === 'rejected'">Đã bị từ chối</span>
          </span>
        </div>

        <!-- Rejected feedback -->
        <div v-if="latestRequest?.status === 'rejected'" class="rounded-xl border border-red-200 bg-red-50 p-4 space-y-1.5 text-xs">
          <p class="font-bold text-red-700">Kháng cáo của bạn đã bị từ chối.</p>
          <p class="text-slate-600">Ghi chú từ quản trị viên: <b>{{ latestRequest.admin_note || 'Không có ghi chú thêm.' }}</b></p>
        </div>

        <!-- Appeal Form -->
        <div v-if="!latestRequest || latestRequest?.status === 'rejected' || (latestRequest?.status === 'approved' && currentUser?.is_locked)" class="space-y-3 pt-2">
          <label class="text-xs font-bold text-slate-700 block" for="appeal-message">
            Nội dung giải trình / Kháng cáo
          </label>
          <textarea
            id="appeal-message"
            v-model="message"
            rows="4"
            maxlength="1000"
            class="field text-xs resize-none"
            placeholder="Giải trình lý do bạn cho rằng tài khoản bị khóa nhầm (tối thiểu 10 ký tự)..."
          />
          <div class="flex items-center justify-between gap-3 text-xs">
            <span class="text-slate-400 text-[11px]">10 - 1000 ký tự</span>
            <button class="btn-primary text-xs px-4 py-2" type="button" :disabled="isSubmitting" @click="submitAppeal">
              {{ isSubmitting ? 'Đang gửi...' : 'Gửi đơn kháng cáo' }}
            </button>
          </div>
          <p v-if="errorMessage" class="text-xs font-bold text-red-600">{{ errorMessage }}</p>
          <p v-if="successMessage" class="text-xs font-bold text-emerald-600">{{ successMessage }}</p>
        </div>

        <!-- Pending Status Message -->
        <div v-else-if="latestRequest?.status === 'pending'" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs space-y-1">
          <p class="font-bold text-amber-800">Đơn kháng cáo của bạn đang được xử lý.</p>
          <p class="text-slate-600">Quản trị viên sẽ phản hồi sớm nhất có thể. Ngày gửi: {{ latestRequest.created_at || 'Gần đây' }}</p>
        </div>

        <!-- Contact Support -->
        <div class="pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
          <span>Cần hỗ trợ gấp?</span>
          <div class="flex items-center gap-4">
            <a href="mailto:support@quizflex.com" class="text-[#7C3AED] hover:underline font-semibold">📧 support@quizflex.com</a>
            <a href="tel:0972554428" class="text-[#7C3AED] hover:underline font-semibold">☎ 0972 554 428</a>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { authApi, unlockRequestsApi } from '@/services/api'

const route = useRoute()
const router = useRouter()
const message = ref('')
const isSubmitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const currentUser = ref(null)
const latestRequest = ref(null)
const isApproved = ref(false)
const countdown = ref(5)

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

const validateAppealMessage = (value) => {
  const trimmed = value.trim()
  if (!trimmed) return 'Vui lòng nhập nội dung kháng cáo.'
  if (trimmed.length < 10) return 'Nội dung kháng cáo tối thiểu 10 ký tự.'
  if (trimmed.length > 1000) return 'Nội dung kháng cáo tối đa 1000 ký tự.'
  return ''
}

const submitAppeal = async () => {
  errorMessage.value = ''
  successMessage.value = ''

  const validationError = validateAppealMessage(message.value)
  if (validationError) {
    errorMessage.value = validationError
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

let pollInterval = null

const handleUnlocked = () => {
  if (isApproved.value) return
  if (pollInterval) { clearInterval(pollInterval); pollInterval = null }
  isApproved.value = true
  const countdownInterval = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      clearInterval(countdownInterval)
      goHome()
    }
  }, 1000)
}

const goHome = async () => {
  await authApi.me()
  router.replace('/')
}

watch(
  () => currentUser.value?.is_locked,
  (newLocked, oldLocked) => {
    if (oldLocked === false && newLocked === true) {
      reloadUnlockRequest()
    }
  }
)

onMounted(() => {
  loadData()
  window.addEventListener('quizflex-account-unlocked', handleUnlocked)

  pollInterval = setInterval(async () => {
    if (isApproved.value) return
    try {
      const data = await unlockRequestsApi.latest()
      const req = data?.data || data || null
      if (req?.status === 'approved') {
        const user = await authApi.lockedInfo()
        if (!user?.is_locked) {
          handleUnlocked()
        }
      }
    } catch {
      // ignore
    }
  }, 3000)
})

onBeforeUnmount(() => {
  window.removeEventListener('quizflex-account-unlocked', handleUnlocked)
  if (pollInterval) clearInterval(pollInterval)
})
</script>
