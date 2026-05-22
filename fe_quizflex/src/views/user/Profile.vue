<template>
  <section class="grid gap-6 py-8 lg:grid-cols-[360px_1fr]">
    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
      <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">{{ $t('user_views.Profile.badge') }}</p>
      <h2 class="mt-2 text-3xl font-black text-[var(--text)]">{{ $t('user_views.Profile.title') }}</h2>
      <p class="mt-2 text-sm text-[var(--muted)]">{{ $t('user_views.Profile.description') }}</p>

      <div class="mt-6 flex flex-col items-center rounded-[1.75rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-center">
        <UserAvatar :user="avatarUser" size-class="h-28 w-28" text-class="text-2xl" ring-class="ring-4 ring-white/10" shadow-class="shadow-[0_20px_40px_rgba(0,0,0,0.25)]" />
        <b class="mt-4 text-lg text-[var(--text)]">{{ profile.name || $t('user_views.Profile.default_name') }}</b>
        <span class="mt-1 text-sm font-bold text-[var(--muted)]">{{ profile.email || $t('user_views.Profile.not_logged_in') }}</span>
      </div>

      <div class="mt-6 grid gap-3 text-sm font-bold text-[var(--muted)]">
        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4"><span>{{ $t('user_views.Profile.email_label') }}</span><b class="mt-1 block text-[var(--text)]">{{ profile.email || $t('user_views.Profile.not_logged_in') }}</b></div>
        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4"><span>{{ $t('user_views.Profile.role_label') }}</span><b class="mt-1 block text-[var(--text)]">{{ profile.role_label || profile.role || $t('user_views.Profile.guest_role') }}</b></div>
      </div>
    </article>

    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)]">
      <h1 class="text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ $t('user_views.Profile.display_info_title') }}</h1>
      <div class="mt-6 grid gap-5">
        <div class="grid gap-4 xl:grid-cols-[220px_minmax(0,1fr)]">
          <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-sm font-black text-[var(--text)]">{{ $t('user_views.Profile.avatar_title') }}</p>
            <p class="mt-1 text-xs font-bold leading-5 text-[var(--muted)]">{{ $t('user_views.Profile.avatar_description') }}</p>
            <div class="mt-4 flex flex-wrap items-center gap-3">
              <button class="btn-ghost !px-4 !py-2 text-xs" type="button" @click="openAvatarPicker">{{ $t('user_views.Profile.upload_avatar_button') }}</button>
              <button v-if="avatarUser.avatar" class="rounded-full border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-xs font-black text-rose-300" type="button" @click="removeAvatar">{{ $t('user_views.Profile.remove_avatar_button') }}</button>
            </div>
            <p v-if="avatarFile" class="mt-3 text-xs font-bold text-[var(--primary)]">{{ $t('user_views.Profile.selected_file', { name: avatarFile.name }) }}</p>
            <input ref="avatarInput" class="hidden" type="file" accept="image/png,image/jpeg,image/jpg,image/webp" @change="handleAvatarFileChange" />
          </div>

          <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4 text-sm font-bold leading-6 text-[var(--muted)]">
            {{ $t('user_views.Profile.avatar_note') }}
          </div>
        </div>

        <label class="grid gap-2 text-sm font-black text-[var(--text)]">
          {{ $t('user_views.Profile.full_name_label') }}
          <input v-model="profile.name" class="field" />
        </label>

        <label class="grid gap-2 text-sm font-black text-[var(--text)]">
          {{ $t('user_views.Profile.email_label') }}
          <input v-model="profile.email" class="field" disabled />
        </label>

        <div v-if="message" class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-300">{{ message }}</div>
        <div v-if="errorMessage" class="rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

        <button class="btn-primary w-fit" type="button" :disabled="isSaving" @click="saveProfile">{{ isSaving ? $t('user_views.Profile.saving_button') : $t('user_views.Profile.save_button') }}</button>
      </div>
    </article>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import UserAvatar from '@/components/common/UserAvatar.vue'
import { authApi, currentUserStorage, tokenStorage } from '@/services/api'

const { t } = useI18n()
const profile = reactive({ name: '', email: '', role: 'guest', role_label: t('user_views.Profile.guest_role'), avatar: '', ...(currentUserStorage.get() || {}) })
const avatarInput = ref(null)
const avatarFile = ref(null)
const avatarPreview = ref('')
const removeAvatarFlag = ref(false)
const message = ref('')
const errorMessage = ref('')
const isSaving = ref(false)

const avatarUser = computed(() => ({ ...profile, avatar: avatarPreview.value || profile.avatar || '' }))

const openAvatarPicker = () => avatarInput.value?.click()

const revokeAvatarPreview = () => {
  if (avatarPreview.value?.startsWith('blob:')) URL.revokeObjectURL(avatarPreview.value)
}

const clearAvatarInput = () => {
  if (avatarInput.value) avatarInput.value.value = ''
  errorMessage.value = ''
}

const removeAvatar = () => {
  revokeAvatarPreview()
  avatarPreview.value = ''
  avatarFile.value = null
  profile.avatar = ''
  removeAvatarFlag.value = true
  clearAvatarInput()
}

const handleAvatarFileChange = (event) => {
  const [file] = event.target.files || []
  if (!file) return

  if (!file.type.startsWith('image/')) {
    errorMessage.value = t('user_views.Profile.errors.avatar_image_only')
    event.target.value = ''
    return
  }

  if (file.size > 2 * 1024 * 1024) {
    errorMessage.value = t('user_views.Profile.errors.avatar_max_size')
    event.target.value = ''
    return
  }

  revokeAvatarPreview()
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
  removeAvatarFlag.value = false
  errorMessage.value = ''
}

const loadProfileFromApi = async () => {
  if (!tokenStorage.get()) return

  try {
    const user = await authApi.me()
    Object.assign(profile, user)
  } catch (error) {
    errorMessage.value = t('user_views.Profile.errors.load_failed', { message: error.message })
  }
}

const saveProfile = async () => {
  if (!tokenStorage.get()) {
    errorMessage.value = t('user_views.Profile.errors.login_required')
    return
  }

  isSaving.value = true
  message.value = ''
  errorMessage.value = ''

  try {
    const saved = await authApi.updateProfile({
      name: profile.name,
      avatar_file: avatarFile.value || undefined,
      remove_avatar: removeAvatarFlag.value,
    })

    Object.assign(profile, saved)
    revokeAvatarPreview()
    avatarPreview.value = ''
    avatarFile.value = null
    removeAvatarFlag.value = false
    clearAvatarInput()
    message.value = t('user_views.Profile.success_saved')
    setTimeout(() => { message.value = '' }, 2500)
  } catch (error) {
    errorMessage.value = t('user_views.Profile.errors.save_failed', { message: error.message })
  } finally {
    isSaving.value = false
  }
}

onMounted(loadProfileFromApi)
onBeforeUnmount(revokeAvatarPreview)
</script>
