<template>
  <section class="grid gap-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ $t('admin_views.Users.badge') }}</p>
        <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ $t('admin_views.Users.title') }}</h1>
        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">{{ $t('admin_views.Users.description') }}</p>
      </div>
    </div>

    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <form class="grid gap-4 lg:grid-cols-[1fr_1fr_150px_160px_auto]" @submit.prevent="createUser">
        <input v-model="newUser.name" class="field" :placeholder="$t('admin_views.Users.name_placeholder')" />
        <input v-model="newUser.email" class="field" type="email" placeholder="email@example.com" />
        <select v-model="newUser.role" class="field"><option value="USER">{{ $t('admin_views.Users.role_user_en') }}</option><option value="VIP">{{ $t('admin_views.Users.role_vip') }}</option><option value="ADMIN">{{ $t('admin_views.Users.role_admin') }}</option></select>
        <input v-model="newUser.password" class="field" type="password" :placeholder="$t('admin_views.Users.password_placeholder')" />
        <button class="btn-primary" type="submit" :disabled="isSaving">{{ $t('admin_views.Users.create_button') }}</button>
      </form>
    </article>

    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <div class="grid gap-4 lg:grid-cols-[1fr_180px_auto]">
        <div class="flex items-center gap-3 rounded-2xl border border-[var(--border)] bg-[var(--input-bg)] px-4 py-3 focus-within:border-[var(--border-strong)]"><span>🔍</span><input v-model="search" class="w-full bg-transparent text-sm font-semibold text-[var(--text)] outline-none placeholder:text-[var(--muted)]" :placeholder="$t('admin_views.Users.search_placeholder')" @keyup.enter="loadUsers" /></div>
        <select v-model="roleFilter" class="field" @change="loadUsers"><option value="all">{{ $t('admin_views.Users.role_all') }}</option><option value="ADMIN">{{ $t('admin_views.Users.role_admin') }}</option><option value="VIP">{{ $t('admin_views.Users.role_vip') }}</option><option value="USER">{{ $t('admin_views.Users.role_user') }}</option><option value="GUEST">{{ $t('admin_views.Users.role_guest') }}</option></select>
        <button class="btn-ghost" type="button" @click="loadUsers">{{ $t('admin_views.Users.search_button') }}</button>
      </div>
    </article>

    <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">{{ $t('admin_views.Users.loading') }}</div>
    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">{{ errorMessage }}</div>
    <div v-if="successMessage" class="rounded-[2rem] border border-emerald-500/30 bg-emerald-500/10 p-5 text-sm font-bold text-emerald-300">{{ successMessage }}</div>

    <article class="overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-card)] backdrop-blur-2xl">
      <div class="hidden grid-cols-[1.35fr_0.6fr_0.6fr_0.6fr_0.75fr] gap-4 border-b border-[var(--border)] bg-[var(--surface-soft)] px-5 py-4 text-xs font-black uppercase tracking-[0.15em] text-[var(--muted)] lg:grid">
        <span>{{ $t('admin_views.Users.table.user') }}</span><span>{{ $t('admin_views.Users.table.role') }}</span><span>{{ $t('admin_views.Users.table.quiz') }}</span><span>{{ $t('admin_views.Users.table.attempts') }}</span><span>{{ $t('admin_views.Users.table.action') }}</span>
      </div>
      <div class="grid divide-y divide-[var(--border)]">
        <div v-for="user in users" :key="user.id" class="grid gap-4 p-5 transition hover:bg-[var(--surface-soft)] lg:grid-cols-[1.35fr_0.6fr_0.6fr_0.6fr_0.75fr] lg:items-center">
          <div><b class="text-[var(--text)]">{{ user.name }}</b><p class="mt-1 text-sm text-[var(--muted)]">{{ $t('admin_views.Users.joined_meta', { email: user.email, joinedAt: user.joinedAt }) }}</p></div>
          <select v-model="user.role" class="field" @change="updateRole(user)"><option value="admin">{{ $t('admin_views.Users.role_admin') }}</option><option value="vip">{{ $t('admin_views.Users.role_vip') }}</option><option value="user">{{ $t('admin_views.Users.role_user') }}</option><option value="guest">{{ $t('admin_views.Users.role_guest') }}</option></select>
          <b class="text-[var(--text)]">{{ user.quizzesCount }}</b>
          <b class="text-[var(--text)]">{{ user.attemptsCount }}</b>
          <div class="flex flex-wrap gap-2"><button class="rounded-full border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-xs font-black text-rose-300" type="button" @click="deleteUser(user.id)">{{ $t('admin_views.Users.delete_button') }}</button></div>
        </div>
      </div>
    </article>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { normalizeUser, usersApi } from '@/services/api'

const { t } = useI18n()
const search = ref('')
const roleFilter = ref('all')
const users = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const newUser = reactive({ name: '', email: '', password: '', role: 'USER' })

const loadUsers = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const data = await usersApi.list({
      search: search.value || undefined,
      role: roleFilter.value,
      per_page: 100,
    })
    users.value = data.map(normalizeUser)
  } catch (error) {
    errorMessage.value = t('admin_views.Users.errors.load_failed', { message: error.message })
    users.value = []
  } finally {
    isLoading.value = false
  }
}

const createUser = async () => {
  errorMessage.value = ''
  successMessage.value = ''
  if (!newUser.name.trim() || !newUser.email.trim() || !newUser.password.trim()) {
    errorMessage.value = t('admin_views.Users.errors.required')
    return
  }

  isSaving.value = true
  try {
    await usersApi.create({ ...newUser })
    successMessage.value = t('admin_views.Users.success.created')
    newUser.name = ''
    newUser.email = ''
    newUser.password = ''
    newUser.role = 'USER'
    await loadUsers()
  } catch (error) {
    errorMessage.value = t('admin_views.Users.errors.create_failed', { message: error.message })
  } finally {
    isSaving.value = false
  }
}

const updateRole = async (user) => {
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const updated = await usersApi.update(user.id, { role: user.role.toUpperCase() })
    Object.assign(user, normalizeUser(updated))
    successMessage.value = t('admin_views.Users.success.role_updated')
  } catch (error) {
    errorMessage.value = t('admin_views.Users.errors.role_update_failed', { message: error.message })
    await loadUsers()
  }
}

const deleteUser = async (id) => {
  if (!window.confirm(t('admin_views.Users.confirm_delete'))) return
  errorMessage.value = ''
  successMessage.value = ''
  try {
    await usersApi.remove(id)
    users.value = users.value.filter((user) => user.id !== id)
    successMessage.value = t('admin_views.Users.success.deleted')
  } catch (error) {
    errorMessage.value = t('admin_views.Users.errors.delete_failed', { message: error.message })
  }
}

onMounted(loadUsers)
</script>
