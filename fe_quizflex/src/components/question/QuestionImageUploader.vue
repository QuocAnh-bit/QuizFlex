<template>
  <div class="space-y-2 select-none">
    <!-- LABEL & ACTIONS -->
    <div class="flex items-center justify-between">
      <label class="flex items-center gap-1.5 text-xs font-bold text-slate-700">
        <ImageIcon :size="14" class="text-purple-600" />
        <span>{{ label || 'Hình ảnh câu hỏi (Tùy chọn)' }}</span>
      </label>

      <span v-if="modelValue" class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1">
        <Check :size="12" />
        <span>Đã có ảnh</span>
      </span>
    </div>

    <!-- 1. EMPTY STATE: UPLOAD DROPZONE -->
    <div
      v-if="!modelValue"
      class="relative flex flex-col items-center justify-center rounded-2xl border-2 border-dashed p-5 text-center transition-all duration-200 cursor-pointer"
      :class="[
        isDragging
          ? 'border-purple-500 bg-purple-50/60 shadow-sm'
          : 'border-slate-200 bg-slate-50/60 hover:border-purple-300 hover:bg-purple-50/30',
        disabled || isUploading ? 'pointer-events-none opacity-60' : ''
      ]"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="handleFileDrop"
      @click="triggerFileInput"
    >
      <input
        ref="fileInputRef"
        type="file"
        class="hidden"
        accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml,image/gif"
        :disabled="disabled || isUploading"
        @change="handleFileSelect"
      />

      <div v-if="isUploading" class="flex flex-col items-center gap-2 py-2">
        <Loader2 class="h-7 w-7 animate-spin text-purple-600" />
        <span class="text-xs font-bold text-purple-700">Đang tải ảnh lên máy chủ...</span>
        <span class="text-[11px] text-slate-400">Vui lòng chờ trong giây lát</span>
      </div>

      <div v-else class="flex flex-col items-center gap-2 py-1">
        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-purple-100 text-purple-600 shadow-2xs">
          <UploadCloud :size="20" />
        </div>

        <div class="space-y-0.5">
          <p class="text-xs font-bold text-slate-800">
            <span class="text-purple-600 hover:underline">Nhấn để chọn ảnh</span> hoặc kéo thả vào đây
          </p>
          <p class="text-[11px] text-slate-500 font-medium">
            Hỗ trợ PNG, JPG, JPEG, WEBP, SVG, GIF (Tối đa 5MB)
          </p>
        </div>
      </div>
    </div>

    <!-- 2. IMAGE PREVIEW STATE -->
    <div
      v-else
      class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3.5 rounded-2xl border border-slate-200 bg-slate-50/70 p-3.5 shadow-2xs"
    >
      <input
        ref="fileInputRef"
        type="file"
        class="hidden"
        accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml,image/gif"
        :disabled="disabled || isUploading"
        @change="handleFileSelect"
      />

      <!-- IMAGE THUMBNAIL & LIGHTBOX PREVIEW -->
      <div class="flex items-center gap-3 min-w-0">
        <div class="relative h-16 w-24 sm:h-20 sm:w-32 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xs flex items-center justify-center">
          <img
            :src="modelValue"
            alt="Preview ảnh câu hỏi"
            class="h-full w-full object-contain p-1"
          />
        </div>

        <div class="min-w-0 flex-1 space-y-1">
          <div class="flex items-center gap-1.5">
            <span class="rounded bg-purple-100 px-1.5 py-0.5 text-[10px] font-bold text-purple-700">Ảnh câu hỏi</span>
            <span class="text-[11px] font-bold text-slate-700 truncate max-w-[180px] sm:max-w-xs">
              {{ fileName || 'Ảnh đã lưu' }}
            </span>
          </div>
          <p class="text-[11px] text-slate-500 truncate max-w-[200px] sm:max-w-sm">
            {{ modelValue }}
          </p>
        </div>
      </div>

      <!-- ACTION BUTTONS: CHANGE & DELETE -->
      <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:border-purple-300 hover:bg-purple-50 hover:text-purple-700 cursor-pointer disabled:opacity-40"
          :disabled="disabled || isUploading"
          title="Chọn ảnh khác từ máy tính"
          @click="triggerFileInput"
        >
          <RefreshCw v-if="!isUploading" :size="13" />
          <Loader2 v-else :size="13" class="animate-spin text-purple-600" />
          <span>{{ isUploading ? 'Đang tải...' : 'Đổi ảnh' }}</span>
        </button>

        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-xl border border-rose-200 bg-white px-2.5 py-1.5 text-xs font-bold text-rose-600 shadow-2xs transition hover:bg-rose-50 hover:border-rose-300 cursor-pointer disabled:opacity-40"
          :disabled="disabled || isUploading"
          title="Xóa ảnh này khỏi câu hỏi"
          @click="removeImage"
        >
          <Trash2 :size="13" />
          <span>Xóa</span>
        </button>
      </div>
    </div>

    <!-- ERROR NOTICE -->
    <div
      v-if="errorMessage"
      class="flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 p-2.5 text-xs font-semibold text-rose-700 animate-fadeIn"
    >
      <AlertTriangle :size="14" class="shrink-0 text-rose-600" />
      <span>{{ errorMessage }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, inject } from 'vue'
import {
  ImageIcon,
  UploadCloud,
  Check,
  RefreshCw,
  Trash2,
  Loader2,
  AlertTriangle
} from 'lucide-vue-next'
import { questionsBankApi } from '@/services/api'

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'upload-success', 'upload-error'])

const showToast = inject('showToast', null)

const fileInputRef = ref(null)
const isDragging = ref(false)
const isUploading = ref(false)
const errorMessage = ref('')
const fileName = ref('')

const triggerFileInput = () => {
  if (props.disabled || isUploading.value) return
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
    fileInputRef.value.click()
  }
}

const handleFileSelect = (e) => {
  const files = e.target?.files
  if (files && files.length > 0) {
    uploadFile(files[0])
  }
}

const handleFileDrop = (e) => {
  isDragging.value = false
  if (props.disabled || isUploading.value) return
  const files = e.dataTransfer?.files
  if (files && files.length > 0) {
    uploadFile(files[0])
  }
}

const uploadFile = async (file) => {
  errorMessage.value = ''

  // Validate format
  const allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/svg+xml', 'image/gif']
  if (!allowedMimeTypes.includes(file.type.toLowerCase())) {
    errorMessage.value = 'Chỉ chấp nhận file định dạng ảnh (PNG, JPG, JPEG, WEBP, SVG, GIF).'
    if (showToast) showToast(errorMessage.value, 'error')
    return
  }

  // Validate size (max 5MB = 5 * 1024 * 1024 bytes)
  const maxSize = 5 * 1024 * 1024
  if (file.size > maxSize) {
    errorMessage.value = 'Dung lượng file ảnh quá lớn (tối đa 5MB).'
    if (showToast) showToast(errorMessage.value, 'error')
    return
  }

  isUploading.value = true
  fileName.value = file.name

  try {
    const res = await questionsBankApi.uploadImage(file)
    const url = res?.url || res?.data?.url || res?.path
    if (url) {
      emit('update:modelValue', url)
      emit('upload-success', url)
      if (showToast) showToast('Tải ảnh câu hỏi thành công!', 'success')
    } else {
      throw new Error('Máy chủ không trả về đường dẫn hình ảnh.')
    }
  } catch (err) {
    console.error('Error uploading question image:', err)
    errorMessage.value = err?.response?.data?.message || err?.message || 'Có lỗi khi tải ảnh lên máy chủ.'
    emit('upload-error', errorMessage.value)
    if (showToast) showToast(errorMessage.value, 'error')
  } finally {
    isUploading.value = false
  }
}

const removeImage = () => {
  emit('update:modelValue', '')
  fileName.value = ''
  errorMessage.value = ''
}
</script>
