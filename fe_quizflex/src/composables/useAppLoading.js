import { ref } from 'vue'

const isLoading = ref(false)
const progress = ref(0)

export function useAppLoading() {
  return {
    isLoading,
    progress,
    startPageLoading: () => {},
    finishPageLoading: () => {},
    beginTask: () => {},
    endTask: () => {}
  }
}
