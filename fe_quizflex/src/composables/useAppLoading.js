import { ref } from 'vue'

const isLoading = ref(true)
const progress = ref(0)
let pendingTasks = 0
let safetyTimer = null
const SAFETY_TIMEOUT = 8000

function startPageLoading() {
  isLoading.value = true
  progress.value = 0
  pendingTasks = 0
  clearTimeout(safetyTimer)
  safetyTimer = setTimeout(() => {
    if (isLoading.value) {
      console.warn('[useAppLoading] Loading treo quá lâu, tự tắt.')
      isLoading.value = false
    }
  }, SAFETY_TIMEOUT)
}

function beginTask() {
  pendingTasks++
  progress.value = Math.max(progress.value, 10)
}

function endTask() {
  pendingTasks = Math.max(0, pendingTasks - 1)
  if (pendingTasks === 0) {
    progress.value = 100
    clearTimeout(safetyTimer)
    setTimeout(() => { isLoading.value = false }, 200)
  } else {
    progress.value = Math.min(90, progress.value + 25)
  }
}

export function useAppLoading() {
  return { isLoading, progress, startPageLoading, beginTask, endTask }
}
