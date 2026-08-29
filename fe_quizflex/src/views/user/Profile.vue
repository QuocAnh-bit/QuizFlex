<template>
  <section class="max-w-[1240px] mx-auto py-4 space-y-6">

    <!-- Header -->
    <div
      class="card p-6 sm:p-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-4"
    >
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">
          Cài đặt tài khoản
        </p>

        <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">
          Hồ sơ & Gói dịch vụ
        </h1>

        <p class="mt-1 text-sm text-slate-600">
          Quản lý thông tin cá nhân, hạn mức AI và lịch sử đăng ký dịch vụ.
        </p>
      </div>

      <!-- Tabs -->
      <div
        class="flex items-center gap-1 rounded-xl border border-slate-200 bg-slate-50 p-1 text-xs font-bold"
      >
        <button
          type="button"
          @click="switchTab('profile')"
          class="px-4 py-2 rounded-lg transition"
          :class="
            activeTab === 'profile'
              ? 'bg-white text-slate-900 shadow-sm'
              : 'text-slate-600 hover:text-slate-900'
          "
        >
          Hồ sơ cá nhân
        </button>

        <button
          type="button"
          @click="switchTab('subscription')"
          class="flex items-center gap-2 px-4 py-2 rounded-lg transition"
          :class="
            activeTab === 'subscription'
              ? 'bg-white text-[#7C3AED] shadow-sm'
              : 'text-slate-600 hover:text-slate-900'
          "
        >
          Gói dịch vụ & Hạn mức

          <span
            v-if="profile.ai_quota_remaining < 20"
            class="h-2 w-2 rounded-full bg-red-500"
          ></span>
        </button>
      </div>
    </div>

    <!-- TAB PROFILE -->
    <div
      v-if="activeTab === 'profile'"
      class="grid gap-6 lg:grid-cols-[340px_1fr]"
    >

      <!-- Overview -->
      <article class="card p-6 space-y-6">
        <div class="flex flex-col items-center text-center space-y-3">

          <UserAvatar
            :user="avatarUser"
            size-class="h-24 w-24"
            text-class="text-2xl font-bold"
          />

          <div>
            <h2 class="text-lg font-black text-slate-900">
              {{ profile.name || 'Người dùng QuizFlex' }}
            </h2>

            <p class="text-xs text-slate-500">
              {{ profile.email || 'Chưa đăng nhập' }}
            </p>
          </div>

          <span
            class="rounded-full bg-purple-50 border border-purple-200 px-3 py-1 text-xs font-bold text-[#7C3AED]"
          >
            Gói: {{ currentPlanLabel }}
          </span>
        </div>

        <div class="space-y-3 pt-3 border-t border-slate-100 text-xs">

          <div class="rounded-lg bg-slate-50 p-3">
            <span class="text-slate-400 font-bold uppercase text-[10px]">
              Email đăng nhập
            </span>

            <p class="font-bold text-slate-800 truncate mt-0.5">
              {{ profile.email || 'Chưa đăng nhập' }}
            </p>
          </div>

          <div class="rounded-lg bg-slate-50 p-3">
            <span class="text-slate-400 font-bold uppercase text-[10px]">
              Vai trò hệ thống
            </span>

            <p class="font-bold text-slate-800 uppercase mt-0.5">
              {{ profile.role_label || profile.role || 'Guest' }}
            </p>
          </div>

        </div>
      </article>

      <!-- Edit Profile -->
      <article class="card p-6 sm:p-8 space-y-6">

        <h3
          class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3"
        >
          Chỉnh sửa thông tin
        </h3>

        <div class="space-y-4">

          <!-- Avatar -->
          <div
            class="rounded-xl border border-slate-100 bg-slate-50 p-4 space-y-3"
          >
            <div
              class="flex flex-wrap items-center justify-between gap-3"
            >
              <div>
                <p class="text-xs font-bold text-slate-900">
                  Ảnh đại diện
                </p>

                <p class="text-xs text-slate-500">
                  Định dạng PNG, JPG, WEBP tối đa 2MB.
                </p>
              </div>

              <div class="flex items-center gap-2">

                <button
                  type="button"
                  class="btn-secondary text-xs px-3 py-1.5"
                  @click="openAvatarPicker"
                >
                  Tải ảnh lên
                </button>

                <button
                  v-if="avatarUser.avatar"
                  type="button"
                  class="btn-ghost text-xs text-red-600 hover:bg-red-50 px-3 py-1.5"
                  @click="removeAvatar"
                >
                  Xóa ảnh
                </button>

              </div>
            </div>

            <p
              v-if="avatarFile"
              class="text-xs font-bold text-[#7C3AED] truncate"
            >
              Đã chọn: {{ avatarFile.name }}
            </p>

            <input
              ref="avatarInput"
              class="hidden"
              type="file"
              accept="image/png,image/jpeg,image/jpg,image/webp"
              @change="handleAvatarFileChange"
            />
          </div>

          <!-- Name -->
          <label class="grid gap-1.5 text-xs font-bold text-slate-700">
            Họ và tên

            <input
              v-model="profile.name"
              class="field text-xs font-medium"
              maxlength="100"
            />

            <span
              v-if="nameError"
              class="text-xs font-semibold text-red-600"
            >
              {{ nameError }}
            </span>
          </label>

          <!-- Email -->
          <label class="grid gap-1.5 text-xs font-bold text-slate-700">
            Địa chỉ Email

            <input
              v-model="profile.email"
              class="field text-xs font-medium bg-slate-100 text-slate-500 cursor-not-allowed"
              disabled
            />
          </label>

          <!-- Success -->
          <div
            v-if="message"
            class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-bold text-emerald-700"
          >
            {{ message }}
          </div>

          <!-- Error -->
          <div
            v-if="errorMessage"
            class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700"
          >
            {{ errorMessage }}
          </div>

          <!-- Save -->
          <div class="pt-2">
            <button
              type="button"
              class="btn-primary text-xs px-6 py-2.5"
              :disabled="isSaving"
              @click="saveProfile"
            >
              {{ isSaving ? 'Đang lưu...' : 'Lưu thay đổi' }}
            </button>
          </div>

        </div>
      </article>
    </div>

    <!-- TAB SUBSCRIPTION -->
    <div
      v-else-if="activeTab === 'subscription'"
      class="grid gap-6"
    >

      <!-- Current Plan -->
      <div
        class="card p-6 sm:p-8 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6"
      >
        <div class="space-y-2 max-w-3xl">

          <div class="flex items-center gap-3 flex-wrap">

            <span
              class="text-xs font-bold uppercase tracking-wider text-slate-400"
            >
              Gói dịch vụ hiện tại
            </span>

            <span
              class="rounded-full px-3 py-0.5 text-xs font-bold"
              :class="statusBadgeInfo.class"
            >
              {{ statusBadgeInfo.text }}
            </span>

          </div>

          <h2 class="text-2xl font-black text-slate-900">
            Gói:
            <span class="text-[#7C3AED]">
              {{ currentPlanLabel }}
            </span>
          </h2>

          <p class="text-xs text-slate-600 leading-relaxed">

            <template v-if="profile.role === 'admin'">
              Tài khoản Quản trị viên có toàn quyền truy cập và sử dụng tất cả tính năng không giới hạn.
            </template>

            <template v-else-if="isTrialPlan">
              Bạn đang trong thời gian
              <b class="text-emerald-700">
                dùng thử 7 ngày gói Plus
              </b>
              kết thúc vào
              <b class="text-slate-900">
                {{ formatDate(profile.vip_expires_at || profile.plan_expires_at) }}
              </b>.
            </template>

            <template
              v-else-if="profile.vip_expires_at || profile.plan_expires_at"
            >
              Gói dịch vụ của bạn có hiệu lực đến:
              <b class="text-slate-900">
                {{ formatDate(profile.vip_expires_at || profile.plan_expires_at) }}
              </b>.
            </template>

            <template v-else>
              Bạn đang sử dụng gói Miễn phí. Hãy nâng cấp để mở khóa thêm số lượt sinh câu hỏi AI, tính năng quét OCR và tạo phòng trực tiếp.
            </template>

          </p>
        </div>

        <router-link
          to="/upgrade"
          class="btn-primary text-xs shrink-0 px-6 py-2.5"
        >
          Nâng cấp / Gia hạn gói
        </router-link>
      </div>

      <!-- AI Quota -->
      <div class="card p-6 sm:p-8 space-y-4">

        <div class="flex items-center justify-between gap-4 flex-wrap">

          <div>
            <h3 class="text-base font-bold text-slate-900">
              Hạn mức AI Generator
            </h3>

            <p class="text-xs text-slate-500">
              Số lượt sinh câu hỏi và tạo quiz bằng trí tuệ nhân tạo còn lại.
            </p>
          </div>

          <div class="text-right">
            <span class="text-2xl font-black text-slate-900">
              {{ profile.ai_quota_remaining ?? 0 }}
            </span>

            <span class="text-xs text-slate-500 font-semibold">
              / {{ maxAiQuota }} lượt
            </span>
          </div>

        </div>

        <div class="space-y-2">

          <div
            class="h-3 w-full rounded-full bg-slate-100 border border-slate-200 overflow-hidden"
          >
            <div
              class="h-full rounded-full transition-all duration-500 bg-[#7C3AED]"
              :style="{ width: `${aiQuotaPercentage}%` }"
            ></div>
          </div>

          <div
            class="flex justify-between text-xs font-semibold text-slate-500"
          >
            <span>
              Khả dụng: {{ aiQuotaPercentage }}%
            </span>

            <span
              v-if="profile.ai_quota_remaining < 20"
              class="text-red-600 font-bold"
            >
              Sắp hết lượt AI
            </span>

            <span
              v-else
              class="text-emerald-700 font-bold"
            >
              Sẵn sàng sử dụng
            </span>
          </div>

        </div>
      </div>

      <!-- Resource Limits -->
      <div class="space-y-3">

        <h3
          class="text-xs font-bold uppercase tracking-wider text-slate-400"
        >
          Hạn mức tài nguyên chi tiết
        </h3>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

          <!-- OCR -->
          <div class="card p-5 space-y-2">

            <div class="flex items-center justify-between">
              <span
                class="text-[10px] font-bold uppercase tracking-wider text-slate-400"
              >
                OCR SCAN
              </span>

              <span
                class="h-2 w-2 rounded-full"
                :class="
                  limitsInfo.ocrAllowed
                    ? 'bg-emerald-500'
                    : 'bg-red-500'
                "
              ></span>
            </div>

            <h4 class="text-sm font-bold text-slate-900">
              Quét tài liệu OCR
            </h4>

            <p class="text-xs font-medium text-slate-600">
              {{ limitsInfo.ocr }}
            </p>

          </div>

          <!-- Live Room -->
          <div class="card p-5 space-y-2">

            <div class="flex items-center justify-between">
              <span
                class="text-[10px] font-bold uppercase tracking-wider text-slate-400"
              >
                LIVE ROOM
              </span>

              <span
                class="h-2 w-2 rounded-full"
                :class="
                  limitsInfo.liveRoomAllowed
                    ? 'bg-emerald-500'
                    : 'bg-red-500'
                "
              ></span>
            </div>

            <h4 class="text-sm font-bold text-slate-900">
              Phòng thi trực tiếp
            </h4>

            <p class="text-xs font-medium text-slate-600">
              {{ limitsInfo.liveRoom }}
            </p>

          </div>

          <!-- Homework -->
          <div class="card p-5 space-y-2">

            <div class="flex items-center justify-between">
              <span
                class="text-[10px] font-bold uppercase tracking-wider text-slate-400"
              >
                HOMEWORK ROOM
              </span>

              <span
                class="h-2 w-2 rounded-full"
                :class="
                  limitsInfo.homeworkAllowed
                    ? 'bg-emerald-500'
                    : 'bg-red-500'
                "
              ></span>
            </div>

            <h4 class="text-sm font-bold text-slate-900">
              Phòng bài tập lớp học
            </h4>

            <p class="text-xs font-medium text-slate-600">
              {{ limitsInfo.homeworkRoom }}
            </p>

          </div>

          <!-- Export -->
          <div class="card p-5 space-y-2">

            <div class="flex items-center justify-between">
              <span
                class="text-[10px] font-bold uppercase tracking-wider text-slate-400"
              >
                EXPORT
              </span>

              <span
                class="h-2 w-2 rounded-full"
                :class="
                  limitsInfo.exportAllowed
                    ? 'bg-emerald-500'
                    : 'bg-red-500'
                "
              ></span>
            </div>

            <h4 class="text-sm font-bold text-slate-900">
              Xuất báo cáo PDF/Excel
            </h4>

            <p
              class="text-xs font-medium"
              :class="
                limitsInfo.exportAllowed
                  ? 'text-emerald-700'
                  : 'text-slate-500'
              "
            >
              {{
                limitsInfo.exportAllowed
                  ? 'Khả dụng'
                  : 'Khóa (Cần gói Plus)'
              }}
            </p>

          </div>

        </div>
      </div>

      <!-- Payment History -->
      <div class="card p-6 sm:p-8 space-y-4">

        <div
          class="flex items-center justify-between gap-4 flex-wrap border-b border-slate-100 pb-3"
        >
          <div>

            <h3 class="text-base font-bold text-slate-900">
              Lịch sử giao dịch
            </h3>

            <p class="text-xs text-slate-500">
              Các hóa đơn thanh toán qua MoMo hoặc PayOS của tài khoản.
            </p>

          </div>

          <button
            type="button"
            class="btn-secondary text-xs px-3.5 py-1.5"
            @click="loadPaymentHistory"
          >
            Làm mới
          </button>
        </div>

        <div class="overflow-x-auto">

          <table class="w-full text-left text-xs">

            <thead>
              <tr
                class="border-b border-slate-100 text-slate-400 font-bold uppercase text-[10px]"
              >
                <th class="pb-3 pr-4">Mã đơn</th>
                <th class="pb-3 px-4">Gói cước</th>
                <th class="pb-3 px-4">Cổng</th>
                <th class="pb-3 px-4">Số tiền</th>
                <th class="pb-3 px-4">Trạng thái</th>
                <th class="pb-3 pl-4">Thời gian</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">

              <tr
                v-for="item in historyList"
                :key="item.id"
                class="hover:bg-slate-50"
              >
                <td class="py-3 pr-4 font-mono font-bold text-slate-800">
                  {{ item.order_code }}
                </td>

                <td class="py-3 px-4 font-bold text-slate-900">
                  {{ item.plan_name || 'Gói nạp' }}
                </td>

                <td class="py-3 px-4">
                  <span
                    class="rounded bg-slate-100 px-2 py-0.5 font-bold uppercase text-[10px] text-slate-600"
                  >
                    {{ item.provider || 'Nội bộ' }}
                  </span>
                </td>

                <td class="py-3 px-4 font-bold text-slate-900">
                  {{ formatPrice(item.amount) }}
                </td>

                <td class="py-3 px-4">
                  <span
                    class="rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                    :class="getStatusBadgeClass(item.status)"
                  >
                    {{ getStatusText(item.status) }}
                  </span>
                </td>

                <td class="py-3 pl-4 text-slate-500">
                  {{ formatDate(item.created_at) }}
                </td>
              </tr>

              <tr v-if="historyList.length === 0">
                <td
                  colspan="6"
                  class="py-8 text-center text-slate-400 font-semibold"
                >
                  Chưa có lịch sử giao dịch nào.
                </td>
              </tr>

            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import {
  computed,
  onBeforeUnmount,
  onMounted,
  reactive,
  ref,
  watch
} from 'vue'

import { useRoute, useRouter } from 'vue-router'

import UserAvatar from '@/components/common/UserAvatar.vue'

import {
  authApi,
  currentUserStorage,
  tokenStorage,
  paymentsApi
} from '@/services/api'

const route = useRoute()
const router = useRouter()

const activeTab = ref('profile')

const profile = reactive({
  name: '',
  email: '',
  role: 'free',
  role_label: 'Free',
  avatar: '',
  ai_quota_remaining: 0,
  ...(currentUserStorage.get() || {})
})

const avatarInput = ref(null)
const avatarFile = ref(null)
const avatarPreview = ref('')
const removeAvatarFlag = ref(false)

const message = ref('')
const errorMessage = ref('')
const nameError = ref('')

const isSaving = ref(false)

const historyList = ref([])

const avatarUser = computed(() => ({
  ...profile,
  avatar: avatarPreview.value || profile.avatar || ''
}))

const isTrialPlan = computed(() => {
  const role = (profile.role || 'free').toLowerCase()

  if (role !== 'plus') return false
  if (!profile.trial_used_at) return false

  const expiry =
    profile.vip_expires_at ||
    profile.plan_expires_at

  return expiry
    ? new Date(expiry) > new Date()
    : false
})

const statusBadgeInfo = computed(() => {
  const role = (profile.role || 'free').toLowerCase()

  if (role === 'admin') {
    return {
      text: 'Quản trị viên',
      class:
        'bg-purple-50 text-purple-700 border border-purple-200'
    }
  }

  if (isTrialPlan.value) {
    return {
      text: 'Dùng thử Plus',
      class:
        'bg-emerald-50 text-emerald-700 border border-emerald-200'
    }
  }

  if (['ultra', 'pro', 'plus'].includes(role)) {
    return {
      text: `Gói ${role.toUpperCase()}`,
      class:
        'bg-purple-50 text-[#7C3AED] border border-purple-200'
    }
  }

  return {
    text: 'Tài khoản Miễn phí',
    class:
      'bg-slate-100 text-slate-600 border border-slate-200'
  }
})

const currentPlanLabel = computed(() => {
  const role = (profile.role || 'free').toLowerCase()

  if (role === 'admin') return 'Admin'
  if (role === 'ultra') return 'ULTRA'
  if (role === 'pro') return 'PRO'

  if (role === 'plus') {
    return isTrialPlan.value
      ? 'PLUS (Dùng thử 7 ngày)'
      : 'PLUS'
  }

  return 'FREE'
})

const maxAiQuota = computed(() => {
  const role = (profile.role || 'free').toLowerCase()

  if (role === 'admin') return 9999
  if (role === 'ultra') return 1500
  if (role === 'pro') return 350
  if (role === 'plus') return 100

  return Math.max(
    profile.ai_quota_remaining || 0,
    5
  )
})

const aiQuotaPercentage = computed(() => {
  const current =
    profile.ai_quota_remaining ?? 0

  const max = maxAiQuota.value

  if (!max || max <= 0) return 0

  return Math.min(
    100,
    Math.max(
      0,
      Math.round((current / max) * 100)
    )
  )
})

const limitsInfo = computed(() => {
  const role = (profile.role || 'free').toLowerCase()

  if (role === 'admin' || role === 'ultra') {
    return {
      ocr: 'Không giới hạn số lượt',
      ocrAllowed: true,

      liveRoom: 'Tối đa 500 người / phòng',
      liveRoomAllowed: true,

      homeworkRoom: 'Không giới hạn phòng',
      homeworkAllowed: true,

      exportAllowed: true
    }
  }

  if (role === 'pro') {
    return {
      ocr: 'Tối đa 50 lượt / tháng',
      ocrAllowed: true,

      liveRoom: 'Tối đa 100 người / phòng',
      liveRoomAllowed: true,

      homeworkRoom: 'Tối đa 20 phòng',
      homeworkAllowed: true,

      exportAllowed: true
    }
  }

  if (role === 'plus') {
    return {
      ocr: 'Tối đa 10 lượt / tháng',
      ocrAllowed: true,

      liveRoom: 'Tối đa 20 người / phòng',
      liveRoomAllowed: true,

      homeworkRoom: 'Tối đa 5 phòng',
      homeworkAllowed: true,

      exportAllowed: true
    }
  }

  return {
    ocr: 'Khóa (Cần gói Plus)',
    ocrAllowed: false,

    liveRoom: 'Khóa (Cần gói Plus)',
    liveRoomAllowed: false,

    homeworkRoom: 'Khóa (Cần gói Plus)',
    homeworkAllowed: false,

    exportAllowed: false
  }
})

const switchTab = (tabName) => {
  activeTab.value = tabName

  router.replace({
    query: {
      ...route.query,
      tab: tabName
    }
  })

  if (tabName === 'subscription') {
    loadPaymentHistory()
  }
}

/*
 * FIX:
 * Không được đóng bằng `})`.
 * Hàm phải kết thúc bằng `}`.
 */
const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A'

  try {
    return new Date(dateStr).toLocaleString(
      'vi-VN',
      {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }
    )
  } catch {
    return dateStr
  }
}

const formatPrice = (amount) => {
  if (
    amount === undefined ||
    amount === null
  ) {
    return '0đ'
  }

  return new Intl.NumberFormat(
    'vi-VN',
    {
      style: 'currency',
      currency: 'VND'
    }
  ).format(amount)
}

const getStatusBadgeClass = (status) => {
  if (status === 'success') {
    return 'bg-emerald-50 text-emerald-700'
  }

  if (status === 'pending') {
    return 'bg-amber-50 text-amber-700'
  }

  return 'bg-red-50 text-red-700'
}

const getStatusText = (status) => {
  if (status === 'success') {
    return 'Thành công'
  }

  if (status === 'pending') {
    return 'Đang xử lý'
  }

  return 'Thất bại'
}

const loadPaymentHistory = async () => {
  if (!tokenStorage.get()) return

  try {
    const res = await paymentsApi.history()

    historyList.value =
      Array.isArray(res?.data)
        ? res.data
        : []
  } catch (error) {
    console.error(
      'Failed to load history:',
      error
    )
  }
}

const openAvatarPicker = () => {
  avatarInput.value?.click()
}

const revokeAvatarPreview = () => {
  if (
    avatarPreview.value &&
    avatarPreview.value.startsWith('blob:')
  ) {
    URL.revokeObjectURL(
      avatarPreview.value
    )
  }
}

const clearAvatarInput = () => {
  if (avatarInput.value) {
    avatarInput.value.value = ''
  }
}

const removeAvatar = () => {
  revokeAvatarPreview()

  avatarPreview.value = ''
  avatarFile.value = null

  profile.avatar = ''

  removeAvatarFlag.value = true

  clearAvatarInput()

  errorMessage.value = ''
}

const ALLOWED_AVATAR_TYPES = [
  'image/png',
  'image/jpeg',
  'image/jpg',
  'image/webp'
]

const handleAvatarFileChange = (event) => {
  const [file] =
    event.target.files || []

  if (!file) return

  if (
    !ALLOWED_AVATAR_TYPES.includes(
      file.type
    )
  ) {
    errorMessage.value =
      'Avatar chỉ chấp nhận định dạng PNG, JPG hoặc WEBP.'

    event.target.value = ''

    return
  }

  if (file.size > 2 * 1024 * 1024) {
    errorMessage.value =
      'Avatar tối đa 2MB.'

    event.target.value = ''

    return
  }

  revokeAvatarPreview()

  avatarFile.value = file

  avatarPreview.value =
    URL.createObjectURL(file)

  removeAvatarFlag.value = false

  errorMessage.value = ''
}

const loadProfileFromApi = async () => {
  if (!tokenStorage.get()) return

  try {
    const user = await authApi.me()

    Object.assign(
      profile,
      user
    )
  } catch (error) {
    errorMessage.value =
      `Không tải được hồ sơ: ${error.message}`
  }
}

const saveProfile = async () => {
  if (!tokenStorage.get()) {
    errorMessage.value =
      'Bạn cần đăng nhập trước khi lưu hồ sơ.'

    return
  }

  const name = String(
    profile.name || ''
  ).trim()

  if (!name) {
    nameError.value =
      'Họ và tên không được để trống.'

    return
  }

  nameError.value = ''

  isSaving.value = true
  message.value = ''
  errorMessage.value = ''

  try {
    const saved =
      await authApi.updateProfile({
        name,
        avatar_file:
          avatarFile.value || undefined,
        remove_avatar:
          removeAvatarFlag.value
      })

    Object.assign(
      profile,
      saved
    )

    revokeAvatarPreview()

    avatarPreview.value = ''
    avatarFile.value = null
    removeAvatarFlag.value = false

    clearAvatarInput()

    message.value =
      'Đã lưu hồ sơ thành công.'

    setTimeout(() => {
      message.value = ''
    }, 2500)

  } catch (error) {
    errorMessage.value =
      `Lưu thất bại: ${error.message}`

  } finally {
    isSaving.value = false
  }
}

onMounted(() => {
  if (
    route.query.tab ===
    'subscription'
  ) {
    activeTab.value =
      'subscription'

    loadPaymentHistory()
  }

  loadProfileFromApi()
})

watch(
  () => route.query.tab,
  (newTab) => {
    if (
      newTab === 'subscription'
    ) {
      activeTab.value =
        'subscription'

      loadPaymentHistory()

    } else if (
      newTab === 'profile'
    ) {
      activeTab.value =
        'profile'
    }
  }
)

onBeforeUnmount(() => {
  revokeAvatarPreview()
})
</script>
