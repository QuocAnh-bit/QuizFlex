<template>
  <AppLoading :show="isLoading" />
  <AppCursor />

  <component :is="layout">
    <router-view />
  </component>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import AdminLayout from '@/layouts/AdminLayout.vue'
import UserLayout from '@/layouts/UserLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'

import AppCursor from '@/components/common/AppCursor.vue'
import AppLoading from '@/components/common/AppLoading.vue'

const route = useRoute()
const router = useRouter()

const isLoading = ref(true)

let loadingTimer = null
let forceCloseTimer = null
let removeBeforeGuard = null
let removeAfterGuard = null

const MIN_LOADING_TIME = 1500

const layout = computed(() => {
  const layoutName = route.meta.layout

  const layouts = {
    admin: AdminLayout,
    user: UserLayout,
    auth: AuthLayout,
  }

  return layouts[layoutName] || UserLayout
})

const startLoading = () => {
  clearTimeout(loadingTimer)
  clearTimeout(forceCloseTimer)

  isLoading.value = true

  forceCloseTimer = setTimeout(() => {
    isLoading.value = false
  }, MIN_LOADING_TIME + 700)
}

const stopLoading = () => {
  clearTimeout(loadingTimer)
  clearTimeout(forceCloseTimer)

  loadingTimer = setTimeout(() => {
    isLoading.value = false
  }, MIN_LOADING_TIME)
}

onMounted(() => {
  removeBeforeGuard = router.beforeEach((to, from, next) => {
    if (to.fullPath !== from.fullPath) {
      startLoading()
    }

    next()
  })

  removeAfterGuard = router.afterEach(() => {
    stopLoading()
  })

  stopLoading()
})

onBeforeUnmount(() => {
  clearTimeout(loadingTimer)
  clearTimeout(forceCloseTimer)

  if (removeBeforeGuard) {
    removeBeforeGuard()
  }

  if (removeAfterGuard) {
    removeAfterGuard()
  }
})
</script>