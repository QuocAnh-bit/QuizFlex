import { defineStore } from 'pinia'
import { currentUserStorage } from '@/services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: currentUserStorage.get(),
  }),
  getters: {
    isAuthenticated: (state) => Boolean(state.user?.id),
    role: (state) => String(state.user?.role || 'guest').toLowerCase(),
    isAdmin: (state) => String(state.user?.role || '').toLowerCase() === 'admin',
    isVip: (state) => String(state.user?.role || '').toLowerCase() === 'vip',
  },
  actions: {
    setUser(user) {
      this.user = user
      currentUserStorage.set(user)
    },
    refresh() {
      this.user = currentUserStorage.get()
    },
    clear() {
      this.user = null
      currentUserStorage.clear()
    },
  },
})
