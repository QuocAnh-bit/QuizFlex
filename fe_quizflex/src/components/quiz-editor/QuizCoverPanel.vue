<template>
  <section class="cover-panel border-b border-slate-200 bg-white px-4 py-4 sm:px-6">
    <div class="mx-auto grid max-w-6xl gap-4 lg:grid-cols-[minmax(260px,380px)_1fr]">
      <div>
        <div class="mb-2 flex items-center justify-between gap-3">
          <div><p class="text-[11px] font-black uppercase tracking-[.14em] text-violet-600">Ảnh bìa Quiz</p><p class="mt-0.5 text-xs text-slate-500">Hiển thị ở danh sách và trang chi tiết Quiz.</p></div>
          <button type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-black text-slate-500 hover:bg-slate-100" @click="$emit('close')">Đóng</button>
        </div>
        <div class="group relative aspect-[16/7] overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-inner" :style="{ background: previewBackground }">
          <div v-if="!hasCover" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-slate-800 to-violet-700 text-center text-white"><div><ImageIcon class="mx-auto h-7 w-7 opacity-80" /><p class="mt-2 text-xs font-black">Chưa có ảnh bìa</p></div></div>
          <div v-if="hasCover" class="absolute inset-x-0 bottom-0 flex justify-end gap-2 bg-gradient-to-t from-slate-950/70 to-transparent p-3 pt-8">
            <button type="button" class="cover-action" @click="openFilePicker"><Upload class="h-3.5 w-3.5" /> Đổi ảnh</button>
            <button type="button" class="cover-action text-rose-600" @click="$emit('remove')"><Trash2 class="h-3.5 w-3.5" /> Xóa</button>
          </div>
        </div>
        <input ref="fileInput" class="hidden" type="file" accept="image/png,image/jpeg,image/webp,image/gif" @change="handleUpload" />
        <button type="button" class="mt-2.5 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-violet-300 bg-violet-50 px-4 py-2.5 text-xs font-black text-violet-700 transition hover:bg-violet-100" @click="openFilePicker"><Upload class="h-4 w-4" /> Tải ảnh của bạn lên</button>
        <p v-if="error" class="mt-2 text-xs font-semibold text-rose-600">{{ error }}</p>
      </div>

      <div class="min-w-0">
        <div class="mb-2 flex items-center justify-between"><p class="text-xs font-black text-slate-700">Chọn nền có sẵn</p><span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ presets.length }} nền</span></div>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
          <button v-for="(preset, index) in presets" :key="preset" type="button" class="preset-tile" :class="{ 'preset-tile-selected': !uploadedFile && selectedCover === preset }" :style="{ background: preset }" :aria-label="`Chọn nền ${index + 1}`" @click="$emit('select', preset)">
            <span class="text-xs font-black text-white drop-shadow">Nền {{ index + 1 }}</span>
            <span v-if="!uploadedFile && selectedCover === preset" class="absolute right-2 top-2 grid h-6 w-6 place-items-center rounded-full bg-white text-violet-700 shadow"><Check class="h-4 w-4 stroke-[3]" /></span>
          </button>
        </div>
      </div>
    </div>
  </section>

  <Teleport to="body">
    <div v-if="cropSource" class="fixed inset-0 z-[100] grid place-items-center bg-slate-950/70 p-4" @click.self="cancelCrop">
      <div class="w-full max-w-3xl rounded-2xl bg-white p-4 shadow-2xl sm:p-5">
        <div class="mb-3 flex items-start justify-between gap-4">
          <div><p class="text-sm font-black text-slate-900">Cắt ảnh bìa</p><p class="mt-0.5 text-xs text-slate-500">Khung ảnh được cố định theo tỉ lệ 16:7.</p></div>
          <button type="button" class="rounded-lg px-2 py-1 text-sm font-black text-slate-500 hover:bg-slate-100" @click="cancelCrop">✕</button>
        </div>
        <div class="overflow-hidden rounded-xl bg-slate-900 shadow-inner">
          <canvas ref="cropCanvas" class="block aspect-[16/7] w-full" width="1280" height="560"></canvas>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-3">
          <label class="crop-control"><span>Phóng to</span><input v-model.number="cropZoom" type="range" min="1" max="3" step="0.01" /></label>
          <label class="crop-control"><span>Trái / phải</span><input v-model.number="cropX" type="range" min="-100" max="100" step="1" /></label>
          <label class="crop-control"><span>Lên / xuống</span><input v-model.number="cropY" type="range" min="-100" max="100" step="1" /></label>
        </div>
        <div class="mt-4 flex justify-end gap-2">
          <button type="button" class="rounded-xl px-4 py-2.5 text-xs font-black text-slate-600 hover:bg-slate-100" @click="cancelCrop">Hủy</button>
          <button type="button" class="rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-black text-white shadow-sm hover:bg-violet-700" @click="confirmCrop">Dùng ảnh đã cắt</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { Check, ImageIcon, Trash2, Upload } from 'lucide-vue-next'
import { coverToBackground } from '@/services/api'

const props = defineProps({ selectedCover: { type: String, default: '' }, uploadedFile: { type: Object, default: null } })
const emit = defineEmits(['select', 'upload', 'remove', 'close'])
const fileInput = ref(null)
const error = ref('')
const uploadPreview = ref('')
const cropCanvas = ref(null)
const cropSource = ref('')
const cropImage = ref(null)
const cropFile = ref(null)
const cropZoom = ref(1)
const cropX = ref(0)
const cropY = ref(0)
const presets = [
  'linear-gradient(135deg, #0f172a 0%, #7c3aed 100%)',
  'linear-gradient(135deg, #1d4ed8 0%, #06b6d4 100%)',
  'linear-gradient(135deg, #047857 0%, #34d399 100%)',
  'linear-gradient(135deg, #9f1239 0%, #fb7185 100%)',
  'linear-gradient(135deg, #7c2d12 0%, #f59e0b 100%)',
  'linear-gradient(135deg, #312e81 0%, #c026d3 100%)',
  'linear-gradient(135deg, #111827 0%, #475569 100%)',
  'linear-gradient(135deg, #0f766e 0%, #84cc16 100%)',
]
const hasCover = computed(() => Boolean(uploadPreview.value || props.selectedCover))
const previewBackground = computed(() => coverToBackground(uploadPreview.value || props.selectedCover))
const revokePreview = () => { if (uploadPreview.value.startsWith('blob:')) URL.revokeObjectURL(uploadPreview.value); uploadPreview.value = '' }
watch(() => props.uploadedFile, (file) => { revokePreview(); if (file instanceof File) uploadPreview.value = URL.createObjectURL(file) }, { immediate: true })
const revokeCropSource = () => { if (cropSource.value.startsWith('blob:')) URL.revokeObjectURL(cropSource.value); cropSource.value = '' }
onBeforeUnmount(() => { revokePreview(); revokeCropSource() })
const openFilePicker = () => { error.value = ''; fileInput.value?.click() }
const drawCrop = () => {
  const canvas = cropCanvas.value
  const image = cropImage.value
  if (!canvas || !image) return
  const context = canvas.getContext('2d')
  const baseScale = Math.max(canvas.width / image.naturalWidth, canvas.height / image.naturalHeight)
  const scale = baseScale * cropZoom.value
  const width = image.naturalWidth * scale
  const height = image.naturalHeight * scale
  const maxX = Math.max(0, (width - canvas.width) / 2)
  const maxY = Math.max(0, (height - canvas.height) / 2)
  const left = (canvas.width - width) / 2 + (cropX.value / 100) * maxX
  const top = (canvas.height - height) / 2 + (cropY.value / 100) * maxY
  context.clearRect(0, 0, canvas.width, canvas.height)
  context.drawImage(image, left, top, width, height)
}
watch([cropZoom, cropX, cropY], drawCrop)
const cancelCrop = () => {
  revokeCropSource()
  cropImage.value = null
  cropFile.value = null
}
const startCrop = async (file) => {
  cancelCrop()
  cropFile.value = file
  cropZoom.value = 1
  cropX.value = 0
  cropY.value = 0
  cropSource.value = URL.createObjectURL(file)
  const image = new Image()
  image.onload = async () => { cropImage.value = image; await nextTick(); drawCrop() }
  image.onerror = () => { error.value = 'Không thể đọc ảnh đã chọn.'; cancelCrop() }
  image.src = cropSource.value
}
const confirmCrop = () => {
  const canvas = cropCanvas.value
  const originalFile = cropFile.value
  if (!canvas || !originalFile) return
  canvas.toBlob((blob) => {
    if (!blob) { error.value = 'Không thể cắt ảnh. Vui lòng thử lại.'; return }
    const baseName = originalFile.name.replace(/\.[^.]+$/, '') || 'quiz-cover'
    const croppedFile = new File([blob], `${baseName}-cropped.jpg`, { type: 'image/jpeg', lastModified: Date.now() })
    cancelCrop()
    emit('upload', croppedFile)
  }, 'image/jpeg', 0.9)
}
const handleUpload = (event) => {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file) return
  if (!['image/png', 'image/jpeg', 'image/webp', 'image/gif'].includes(file.type)) { error.value = 'Chỉ hỗ trợ PNG, JPG, WEBP hoặc GIF.'; return }
  if (file.size > 4 * 1024 * 1024) { error.value = 'Ảnh bìa không được vượt quá 4MB.'; return }
  error.value = ''
  startCrop(file)
}
</script>

<style scoped>
.cover-panel { max-height: min(390px, 48dvh); overflow-y: auto; }
.cover-action { @apply inline-flex items-center gap-1.5 rounded-lg bg-white/95 px-2.5 py-1.5 text-[11px] font-black text-slate-700 shadow-sm hover:bg-white; }
.preset-tile { @apply relative aspect-[16/9] overflow-hidden rounded-xl border-2 border-transparent p-2 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md; }
.preset-tile-selected { @apply border-violet-500 ring-2 ring-violet-200; }
.crop-control { @apply grid gap-1.5 text-[11px] font-black text-slate-600; }
.crop-control input { @apply w-full accent-violet-600; }
</style>
