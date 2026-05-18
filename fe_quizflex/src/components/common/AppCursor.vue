<template>
  <div
    v-if="isEnabled"
    class="pointer-events-none fixed left-0 top-0 z-[9999] hidden lg:block"
  >
    <div
      class="game-cursor-shell"
      :class="{ 'game-cursor-hidden': isTextTarget }"
      :style="cursorStyle"
    >
      <div
        class="game-cursor-shape"
        :class="{
          'game-cursor-hover': isHovering,
          'game-cursor-pressed': isPressed,
        }"
      >
        <div class="cursor-core"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const isEnabled = ref(false)
const isHovering = ref(false)
const isPressed = ref(false)
const isTextTarget = ref(false)

const cursorX = ref(0)
const cursorY = ref(0)

const interactiveSelector = [
  'a',
  'button',
  'select',
  '[role="button"]',
  '.cursor-interactive',
].join(',')

const textSelector = [
  'input',
  'textarea',
  '[contenteditable="true"]',
].join(',')

const cursorStyle = computed(() => ({
  transform: `translate3d(${cursorX.value}px, ${cursorY.value}px, 0)`,
}))

const handleMouseMove = (event) => {
  cursorX.value = event.clientX
  cursorY.value = event.clientY

  const target = event.target

  isHovering.value = Boolean(target.closest(interactiveSelector))
  isTextTarget.value = Boolean(target.closest(textSelector))
}

const handleMouseDown = () => {
  isPressed.value = true
}

const handleMouseUp = () => {
  isPressed.value = false
}

onMounted(() => {
  const canUseCustomCursor = window.matchMedia('(pointer: fine)').matches

  if (!canUseCustomCursor) {
    return
  }

  isEnabled.value = true
  document.documentElement.classList.add('has-game-cursor')

  window.addEventListener('mousemove', handleMouseMove)
  window.addEventListener('mousedown', handleMouseDown)
  window.addEventListener('mouseup', handleMouseUp)
})

onBeforeUnmount(() => {
  document.documentElement.classList.remove('has-game-cursor')

  window.removeEventListener('mousemove', handleMouseMove)
  window.removeEventListener('mousedown', handleMouseDown)
  window.removeEventListener('mouseup', handleMouseUp)
})
</script>

<style scoped>
.game-cursor-shell {
  position: fixed;
  width: 26px;
  height: 32px;
  opacity: 1;
  transition: opacity 0.14s ease;
}

.game-cursor-shape {
  position: relative;
  width: 26px;
  height: 32px;
  transform-origin: 2px 2px;
  transition:
    transform 0.12s ease,
    filter 0.16s ease;
  filter:
    drop-shadow(0 5px 9px rgba(0, 0, 0, 0.38))
    drop-shadow(0 0 8px rgba(255, 255, 255, 0.18));
}

.game-cursor-shape::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #ffffff 0%, #f3f0e7 52%, #c7c2b5 100%);
  clip-path: polygon(0 0, 100% 40%, 50% 55%, 38% 100%);
}

.game-cursor-shape::after {
  content: '';
  position: absolute;
  left: 4px;
  top: 4px;
  width: 18px;
  height: 23px;
  background: #111315;
  clip-path: polygon(0 0, 100% 40%, 50% 55%, 38% 100%);
  opacity: 0.92;
}

.cursor-core {
  position: absolute;
  left: 10px;
  top: 10px;
  z-index: 2;
  width: 8px;
  height: 9px;
  background: linear-gradient(135deg, #fff7a8, #f0ce4b 48%, #7b6816);
  clip-path: polygon(0 0, 100% 50%, 0 100%);
  box-shadow: 0 0 8px rgba(255, 224, 90, 0.7);
}

.game-cursor-hover {
  transform: scale(1.08);
  filter:
    drop-shadow(0 6px 11px rgba(0, 0, 0, 0.42))
    drop-shadow(0 0 12px rgba(255, 232, 120, 0.42));
}

.game-cursor-hover::before {
  background: linear-gradient(135deg, #ffffff 0%, #fff8dc 50%, #d6cfba 100%);
}

.game-cursor-pressed {
  transform: scale(0.9);
  filter:
    drop-shadow(0 3px 6px rgba(0, 0, 0, 0.45))
    drop-shadow(0 0 8px rgba(255, 208, 80, 0.5));
}

.game-cursor-hidden {
  opacity: 0;
}

:global(html.has-game-cursor body),
:global(html.has-game-cursor a),
:global(html.has-game-cursor button),
:global(html.has-game-cursor [role='button']) {
  cursor: none;
}

:global(html.has-game-cursor input),
:global(html.has-game-cursor textarea),
:global(html.has-game-cursor select) {
  cursor: text;
}
</style>    