<template>
  <section class="max-w-6xl mx-auto py-4 space-y-6">
    <!-- Header -->
    <div class="card p-6 sm:p-8 flex flex-col justify-between gap-4 md:flex-row md:items-center">
      <div class="flex items-start gap-3.5 min-w-0 flex-1">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-purple-500/10 text-[#7C3AED]">
          <SlidersHorizontal class="h-5 w-5" />
        </div>
        <div class="min-w-0">
          <p class="text-xs font-bold uppercase tracking-wider text-[#7C3AED]">Quản trị hệ thống</p>
          <h1 class="text-2xl sm:text-3xl font-black tracking-[-0.04em] text-[var(--text)]">Cài đặt hệ thống</h1>
          <p class="mt-1 text-sm font-medium text-[var(--muted)]">
            Cấu hình định mức tài nguyên AI, OCR theo phân quyền và tùy chọn hiển thị mặc định.
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2.5 shrink-0 self-start md:self-center">
        <button
          class="btn-secondary text-xs px-3.5 py-2 inline-flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
          type="button"
          :disabled="isSaving || isLoading"
          @click="openResetConfirm"
        >
          <RotateCcw class="h-3.5 w-3.5" />
          <span>Khôi phục mặc định</span>
        </button>
        <button
          class="btn-primary text-xs px-4 py-2 inline-flex items-center gap-1.5 cursor-pointer shadow-sm whitespace-nowrap"
          type="button"
          :disabled="isSaving || isLoading"
          @click="saveSettings"
        >
          <RefreshCw v-if="isSaving" class="h-3.5 w-3.5 animate-spin" />
          <Save v-else class="h-3.5 w-3.5" />
          <span>{{ isSaving ? 'Đang lưu...' : 'Lưu cài đặt' }}</span>
        </button>
      </div>
    </div>

    <!-- Alert Notifications -->
    <div
      v-if="isLoading"
      class="card p-6 text-center text-xs text-slate-500 flex items-center justify-center gap-2"
    >
      <RefreshCw class="h-4 w-4 animate-spin text-[#7C3AED]" />
      <span>Đang tải cài đặt từ Database MySQL...</span>
    </div>

    <div
      v-if="successMessage"
      class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-700 flex items-center justify-between shadow-2xs"
    >
      <div class="flex items-center gap-2.5">
        <CheckCircle2 class="h-4 w-4 shrink-0 text-emerald-600" />
        <span>{{ successMessage }}</span>
      </div>
      <button type="button" class="text-emerald-500 hover:text-emerald-700 cursor-pointer" @click="successMessage = ''">
        <X class="h-4 w-4" />
      </button>
    </div>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700 flex items-center justify-between shadow-2xs"
    >
      <div class="flex items-center gap-2.5">
        <AlertCircle class="h-4 w-4 shrink-0 text-red-600" />
        <span>{{ errorMessage }}</span>
      </div>
      <button type="button" class="text-red-500 hover:text-red-700 cursor-pointer" @click="errorMessage = ''">
        <X class="h-4 w-4" />
      </button>
    </div>

    <!-- Main Content: 2-Column Grid -->
    <div v-if="!isLoading" class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
      <!-- Left Column: AI & OCR Limits (Chỉnh sửa và lưu vào MySQL) -->
      <article class="card p-6 sm:p-7 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
          <div class="flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-[#7C3AED]">
              <Cpu class="h-4 w-4" />
            </div>
            <div>
              <h2 class="text-sm font-bold text-slate-900">Định mức tài nguyên (AI & OCR)</h2>
              <p class="text-[11px] text-slate-500">Chỉnh sửa định mức hàng tháng lưu trực tiếp vào Server</p>
            </div>
          </div>
          <span class="text-[11px] font-semibold text-slate-400">5 Phân quyền</span>
        </div>

        <div class="space-y-3 pt-1">
          <div
            v-for="row in limits"
            :key="row.roleKey"
            class="rounded-xl border border-slate-200/90 bg-slate-50/50 p-4 transition hover:border-purple-200 hover:bg-white space-y-3"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span
                  class="px-2.5 py-0.5 rounded-md text-[11px] font-bold tracking-wide uppercase"
                  :class="getRoleBadgeClass(row.roleKey)"
                >
                  {{ row.role }}
                </span>
                <span class="text-xs text-slate-500 font-medium">{{ row.desc }}</span>
              </div>
              <span v-if="row.roleKey === 'admin'" class="text-[11px] text-slate-400 font-semibold flex items-center gap-1">
                <Lock class="h-3 w-3 text-slate-400" /> Cố định hệ thống
              </span>
              <span v-else class="text-[11px] text-slate-400 font-medium">Hạn mức tháng</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
              <div>
                <label class="text-[11px] font-semibold text-slate-600 block mb-1">
                  AI Quota
                </label>
                <input
                  v-model="row.ai"
                  class="field text-xs transition"
                  :class="row.roleKey === 'admin' ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200 font-medium' : 'bg-white focus:border-[#7C3AED]'"
                  :disabled="row.roleKey === 'admin'"
                  placeholder="VD: 350 lượt/tháng"
                />
              </div>
              <div>
                <label class="text-[11px] font-semibold text-slate-600 block mb-1">
                  OCR Quota
                </label>
                <input
                  v-model="row.ocr"
                  class="field text-xs transition"
                  :class="row.roleKey === 'admin' ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200 font-medium' : 'bg-white focus:border-[#7C3AED]'"
                  :disabled="row.roleKey === 'admin'"
                  placeholder="VD: 50 lượt/tháng"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-xl bg-purple-50/40 border border-purple-100 p-3.5 text-xs text-slate-600 flex items-start gap-2.5">
          <Info class="h-4 w-4 text-[#7C3AED] shrink-0 mt-0.5" />
          <span class="text-[11px] leading-relaxed">
            Dữ liệu định mức sau khi bấm lưu sẽ được cập nhật trực tiếp vào bảng <code>system_settings</code> trong MySQL Server.
          </span>
        </div>
      </article>

      <!-- Right Column: Visibility & System Status -->
      <div class="space-y-6 flex flex-col justify-between">
        <!-- 1. Default Visibility Setting -->
        <article class="card p-6 sm:p-7 space-y-4">
          <div class="flex items-center gap-2.5 border-b border-slate-100 pb-3.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
              <Eye class="h-4 w-4" />
            </div>
            <div>
              <h2 class="text-sm font-bold text-slate-900">Hiển thị mặc định khi tạo Quiz</h2>
              <p class="text-[11px] text-slate-500">Quyền riêng tư mặc định khi người dùng tạo đề thi mới</p>
            </div>
          </div>

          <div class="space-y-2 pt-1">
            <label
              v-for="item in visibilityOptions"
              :key="item.value"
              class="flex items-center justify-between rounded-xl border p-3.5 text-xs font-semibold cursor-pointer transition"
              :class="
                selectedVisibility === item.value
                  ? 'border-[#7C3AED] bg-purple-50/40 text-[#7C3AED] shadow-2xs'
                  : 'border-slate-200/90 bg-slate-50/50 text-slate-700 hover:border-slate-300 hover:bg-white'
              "
            >
              <div class="flex items-center gap-2.5">
                <component :is="item.icon" class="h-4 w-4" :class="selectedVisibility === item.value ? 'text-[#7C3AED]' : 'text-slate-400'" />
                <div>
                  <span class="block font-bold">{{ item.label }}</span>
                  <span class="text-[11px] font-normal text-slate-500">{{ item.desc }}</span>
                </div>
              </div>
              <input
                v-model="selectedVisibility"
                type="radio"
                name="visibility"
                :value="item.value"
                class="h-4 w-4 accent-[#7C3AED]"
              />
            </label>
          </div>
        </article>

        <!-- 2. System Status & Technical Specs -->
        <article class="card p-6 sm:p-7 space-y-4 flex-1 flex flex-col justify-between">
          <div class="space-y-3.5">
            <div class="flex items-center gap-2.5 border-b border-slate-100 pb-3.5">
              <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                <ShieldCheck class="h-4 w-4" />
              </div>
              <div>
                <h2 class="text-sm font-bold text-slate-900">Thông tin hệ thống & Môi trường</h2>
                <p class="text-[11px] text-slate-500">Môi trường vận hành của nền tảng QuizFlex</p>
              </div>
            </div>

            <div class="space-y-2.5 pt-1 text-xs">
              <div
                v-for="spec in systemSpecs"
                :key="spec.label"
                class="flex items-center justify-between p-3 rounded-xl border border-slate-200/80 bg-slate-50/50"
              >
                <span class="text-[11px] font-medium text-slate-600">{{ spec.label }}</span>
                <span class="font-bold inline-flex items-center gap-1.5" :class="spec.statusColor || 'text-slate-900'">
                  <span v-if="spec.isDot" class="h-2 w-2 rounded-full bg-emerald-500 inline-block"></span>
                  {{ spec.value }}
                </span>
              </div>
            </div>
          </div>

          <div class="pt-4 border-t border-slate-100">
            <button
              class="btn-primary w-full py-2.5 text-xs inline-flex items-center justify-center gap-2 cursor-pointer shadow-sm"
              type="button"
              :disabled="isSaving || isLoading"
              @click="saveSettings"
            >
              <RefreshCw v-if="isSaving" class="h-3.5 w-3.5 animate-spin" />
              <Save v-else class="h-3.5 w-3.5" />
              <span>{{ isSaving ? 'Đang lưu vào DB...' : 'Lưu toàn bộ cài đặt' }}</span>
            </button>
          </div>
        </article>
      </div>
    </div>

    <!-- Reset Confirmation Modal -->
    <div
      v-if="showResetModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs"
      @click.self="showResetModal = false"
    >
      <div class="card max-w-md w-full p-6 space-y-4 shadow-xl">
        <div class="flex items-center gap-3 text-amber-600">
          <div class="h-10 w-10 rounded-2xl bg-amber-100 flex items-center justify-center shrink-0">
            <RotateCcw class="h-5 w-5" />
          </div>
          <div>
            <h3 class="text-base font-black text-slate-900">Khôi phục định mức mặc định?</h3>
            <p class="text-xs text-slate-500 mt-0.5">Tất cả định mức AI và OCR sẽ được lưu lại theo quy chuẩn ban đầu vào Database.</p>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
          <button
            type="button"
            class="btn-secondary text-xs px-4 py-2"
            @click="showResetModal = false"
          >
            Hủy bỏ
          </button>
          <button
            type="button"
            class="btn-primary text-xs px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white"
            @click="confirmResetDefaults"
          >
            Đồng ý khôi phục
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import {
  SlidersHorizontal,
  Cpu,
  Eye,
  Globe,
  Lock,
  Users,
  ShieldCheck,
  Info,
  Save,
  RotateCcw,
  RefreshCw,
  CheckCircle2,
  AlertCircle,
  X,
} from 'lucide-vue-next'
import { adminSettingsApi } from '@/services/api'

const STORAGE_KEY = 'quizflex_admin_system_settings'

const isLoading = ref(false)
const isSaving = ref(false)
const successMessage = ref('')
const errorMessage = ref('')
const showResetModal = ref(false)

const getDefaultLimits = () => [
  { roleKey: 'admin', role: 'Admin', desc: 'Quản trị viên toàn quyền', ai: 'Unlimited', ocr: 'Unlimited' },
  { roleKey: 'ultra', role: 'Ultra VIP', desc: 'Giáo viên & Luyện thi chuyên sâu', ai: '1500 lượt/tháng', ocr: 'Không giới hạn' },
  { roleKey: 'pro', role: 'Pro VIP', desc: 'Học sinh ôn thi THPT & Đại học', ai: '350 lượt/tháng', ocr: '50 lượt/tháng' },
  { roleKey: 'plus', role: 'Plus VIP', desc: 'Học sinh luyện đề tiêu chuẩn', ai: '100 lượt/tháng', ocr: '10 lượt/tháng' },
  { roleKey: 'free', role: 'Free', desc: 'Tài khoản thành viên thường', ai: '10 lượt/tháng', ocr: 'Yêu cầu gói Plus' },
]

const limits = reactive(getDefaultLimits())

const visibilityOptions = [
  { value: 'Private', label: 'Private (Riêng tư - Mặc định)', desc: 'Chỉ tác giả mới có quyền xem và chỉnh sửa', icon: Lock },
  { value: 'Public', label: 'Public (Công khai)', desc: 'Mọi người dùng trên QuizFlex đều có thể làm bài', icon: Globe },
  { value: 'Group', label: 'Group (Nhóm / Lớp học)', desc: 'Chỉ thành viên trong lớp học được làm bài', icon: Users },
]

const selectedVisibility = ref('Private')

const systemSpecs = [
  { label: 'Phiên bản nền tảng', value: 'QuizFlex v1.2 Pro', isDot: false, statusColor: 'text-purple-700 font-bold' },
  { label: 'Cơ sở dữ liệu cài đặt', value: 'MySQL (system_settings)', isDot: true, statusColor: 'text-emerald-700' },
  { label: 'AI Generator Engine', value: 'Gemini 1.5 Flash API', isDot: true, statusColor: 'text-emerald-700' },
  { label: 'OCR Recognition Engine', value: 'Tesseract + AI Vision', isDot: true, statusColor: 'text-emerald-700' },
  { label: 'Cổng thanh toán trực tuyến', value: 'PayOS & MoMo Online', isDot: true, statusColor: 'text-emerald-700' },
  { label: 'Realtime Server (WebSocket)', value: 'Laravel Reverb (Active)', isDot: true, statusColor: 'text-emerald-700' },
]

const getRoleBadgeClass = (roleKey) => {
  switch (roleKey) {
    case 'admin':
      return 'bg-rose-50 text-rose-700 border border-rose-200'
    case 'ultra':
      return 'bg-amber-50 text-amber-800 border border-amber-200'
    case 'pro':
      return 'bg-purple-50 text-purple-700 border border-purple-200'
    case 'plus':
      return 'bg-blue-50 text-blue-700 border border-blue-200'
    default:
      return 'bg-slate-100 text-slate-700 border border-slate-200'
  }
}

const loadSettings = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const res = await adminSettingsApi.getSettings()
    const payload = res?.data || res

    if (Array.isArray(payload?.limits) && payload.limits.length > 0) {
      limits.splice(0, limits.length, ...payload.limits)
    }

    if (payload?.selectedVisibility) {
      selectedVisibility.value = payload.selectedVisibility
    }
  } catch (err) {
    console.warn('Không thể tải cài đặt từ Database, sử dụng cache/fallback:', err)
    // Fallback sang localStorage nếu API có sự cố mạng
    try {
      const raw = localStorage.getItem(STORAGE_KEY)
      if (raw) {
        const data = JSON.parse(raw)
        if (Array.isArray(data.limits) && data.limits.length > 0) {
          limits.splice(0, limits.length, ...data.limits)
        }
        if (data.selectedVisibility) {
          selectedVisibility.value = data.selectedVisibility
        }
      }
    } catch (e) {
      console.error(e)
    }
  } finally {
    isLoading.value = false
  }
}

const saveSettings = async () => {
  isSaving.value = true
  errorMessage.value = ''
  successMessage.value = ''

  const payload = {
    limits,
    selectedVisibility: selectedVisibility.value,
  }

  try {
    // 1. Gửi lưu trực tiếp vào Database MySQL qua Backend API
    const res = await adminSettingsApi.updateSettings(payload)
    
    // 2. Đồng bộ luôn vào localStorage để backup
    localStorage.setItem(STORAGE_KEY, JSON.stringify(payload))
    
    successMessage.value = res?.message || 'Đã lưu cấu hình cài đặt vào cơ sở dữ liệu MySQL thành công!'
    setTimeout(() => {
      if (successMessage.value) successMessage.value = ''
    }, 3500)
  } catch (err) {
    errorMessage.value = err?.response?.data?.message || `Lỗi lưu cài đặt: ${err.message}`
  } finally {
    isSaving.value = false
  }
}

const openResetConfirm = () => {
  showResetModal.value = true
}

const confirmResetDefaults = async () => {
  const defaultList = getDefaultLimits()
  limits.splice(0, limits.length, ...defaultList)
  selectedVisibility.value = 'Private'
  showResetModal.value = false

  try {
    await adminSettingsApi.updateSettings({
      limits: defaultList,
      selectedVisibility: 'Private',
    })
    localStorage.removeItem(STORAGE_KEY)
    successMessage.value = 'Đã khôi phục toàn bộ định mức về giá trị mặc định trong Database!'
    setTimeout(() => {
      if (successMessage.value) successMessage.value = ''
    }, 3500)
  } catch (err) {
    errorMessage.value = `Lỗi khôi phục: ${err.message}`
  }
}

onMounted(() => {
  loadSettings()
})
</script>
