<template>
  <div
    v-if="resolvedSrc && !hasError"
    class="question-image-wrapper group relative inline-flex flex-col items-center justify-center max-w-full overflow-hidden transition-all duration-300 select-none"
    :class="[
      rounded,
      containerBorderClass,
      containerBgClass,
      containerClass
    ]"
  >
    <!-- SKELETON LOADER WHILE IMAGE LOADS -->
    <div
      v-if="isLoading"
      class="absolute inset-0 flex items-center justify-center bg-slate-100/90 backdrop-blur-2xs animate-pulse z-10 min-h-[120px] min-w-[160px]"
    >
      <div class="flex items-center gap-2 text-xs font-semibold text-slate-400">
        <Loader2 class="h-4 w-4 animate-spin text-purple-600" />
        <span>Đang tải ảnh...</span>
      </div>
    </div>

    <!-- MAIN IMAGE CONTAINER WITH CARD PADDING -->
    <div
      class="relative overflow-hidden flex items-center justify-center max-w-full p-1.5 sm:p-2"
      :class="[allowZoom ? 'cursor-zoom-in' : '']"
      @click="handleImageClick"
    >
      <img
        :src="resolvedSrc"
        :alt="alt || 'Hình ảnh câu hỏi'"
        loading="lazy"
        class="w-auto h-auto rounded-xl object-contain transition-all duration-300 group-hover:scale-[1.015]"
        :class="[
          computedHeightClass,
          imgClass
        ]"
        @load="onImageLoad"
        @error="onImageError"
      />

      <!-- SLEEK ZOOM BADGE -->
      <div
        v-if="allowZoom && !isLoading"
        class="absolute bottom-3.5 right-3.5 flex items-center gap-1 rounded-md bg-slate-950/65 group-hover:bg-[#7C3AED]/90 px-2 py-1 text-[10px] font-medium text-white backdrop-blur-xs transition-all duration-200 shadow-sm pointer-events-none"
      >
        <ZoomIn :size="11" />
        <span>Phóng to</span>
      </div>
    </div>

    <!-- LIGHTBOX FULLSCREEN ZOOM MODAL -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="isLightboxOpen"
          class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6 bg-slate-950/85 backdrop-blur-md"
          role="dialog"
          aria-modal="true"
          @click.self="closeLightbox"
        >
          <!-- TOP ACTION BAR -->
          <div class="absolute top-4 right-4 sm:top-6 sm:right-6 flex items-center gap-3 z-20">
            <a
              :href="resolvedSrc"
              target="_blank"
              rel="noopener noreferrer"
              class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-white backdrop-blur-md transition hover:bg-white/20 hover:text-white"
              title="Mở ảnh trong tab mới"
            >
              <ExternalLink :size="16" />
            </a>

            <button
              type="button"
              class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-white backdrop-blur-md transition hover:bg-rose-600 hover:text-white cursor-pointer"
              title="Đóng (ESC)"
              @click="closeLightbox"
            >
              <X :size="18" :stroke-width="2.5" />
            </button>
          </div>

          <!-- FULL-SIZE IMAGE CONTAINER -->
          <div class="relative max-h-[92vh] max-w-[95vw] flex items-center justify-center select-none" @click.self="closeLightbox">
            <img
              :src="resolvedSrc"
              :alt="alt || 'Hình ảnh câu hỏi (phóng to)'"
              class="max-h-[90vh] max-w-[92vw] rounded-2xl object-contain shadow-2xl border border-white/10 transition-transform duration-200"
            />
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { ZoomIn, X, ExternalLink, Loader2 } from 'lucide-vue-next'

const props = defineProps({
  src: {
    type: String,
    default: '',
  },
  alt: {
    type: String,
    default: 'Hình ảnh câu hỏi',
  },
  size: {
    type: String,
    default: 'normal', // 'compact' | 'medium' | 'normal' | 'large' | 'full'
  },
  maxHeight: {
    type: String,
    default: '',
  },
  allowZoom: {
    type: Boolean,
    default: true,
  },
  rounded: {
    type: String,
    default: 'rounded-2xl',
  },
  containerClass: {
    type: String,
    default: '',
  },
  containerBgClass: {
    type: String,
    default: 'bg-slate-50/70 hover:bg-slate-50/90',
  },
  containerBorderClass: {
    type: String,
    default: 'border border-slate-200/90 shadow-2xs hover:border-purple-300 hover:shadow-xs ring-1 ring-slate-900/5',
  },
  imgClass: {
    type: String,
    default: '',
  },
})

const isLoading = ref(true)
const hasError = ref(false)
const isLightboxOpen = ref(false)

const resolvedSrc = computed(() => {
  if (!props.src) return ''
  const trimmed = props.src.trim()
  if (trimmed.startsWith('/storage/')) {
    return trimmed
  }
  return trimmed
})

watch(() => props.src, (newVal) => {
  if (newVal) {
    isLoading.value = true
    hasError.value = false
  }
})

const computedHeightClass = computed(() => {
  if (props.maxHeight) return props.maxHeight

  switch (props.size) {
    case 'compact':
      return 'max-h-24 sm:max-h-32 max-w-[260px]'
    case 'medium':
      return 'max-h-40 sm:max-h-48 max-w-[420px]'
    case 'large':
      return 'max-h-60 sm:max-h-72 max-w-[580px]'
    case 'full':
      return 'max-h-[460px] max-w-full'
    case 'normal':
    default:
      return 'max-h-44 sm:max-h-52 md:max-h-56 max-w-[450px] sm:max-w-[480px]'
  }
})

const onImageLoad = () => {
  isLoading.value = false
  hasError.value = false
}

const onImageError = () => {
  isLoading.value = false
  hasError.value = true
}

const handleImageClick = () => {
  if (props.allowZoom && resolvedSrc.value) {
    isLightboxOpen.value = true
  }
}

const closeLightbox = () => {
  isLightboxOpen.value = false
}

const handleKeyDown = (e) => {
  if (e.key === 'Escape' && isLightboxOpen.value) {
    closeLightbox()
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
})
</script>
