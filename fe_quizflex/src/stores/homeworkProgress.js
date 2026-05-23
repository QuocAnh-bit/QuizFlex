import { defineStore } from 'pinia'
import { homeworkProgressStorage } from '@/services/api'

export const useHomeworkProgressStore = defineStore('homeworkProgress', {
  state: () => ({
    progressByAssignment: homeworkProgressStorage.getAll(),
  }),
  actions: {
    refresh() {
      this.progressByAssignment = homeworkProgressStorage.getAll()
    },
    setProgress(assignmentId, value) {
      homeworkProgressStorage.set(assignmentId, value)
      this.refresh()
    },
    clearProgress(assignmentId) {
      homeworkProgressStorage.clear(assignmentId)
      this.refresh()
    },
  },
})
