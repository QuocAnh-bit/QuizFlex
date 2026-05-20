<template>
  <section class="grid gap-6 py-8 lg:grid-cols-[360px_1fr]">
    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
      <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Profile</p>
      <h2 class="mt-2 text-3xl font-black text-[var(--text)]">Hồ sơ cá nhân</h2>
      <p class="mt-2 text-sm text-[var(--muted)]">Avatar và thông tin tài khoản được lưu vào database.</p>

      <div class="mt-6 flex flex-col items-center rounded-[1.75rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-center">
        <UserAvatar :user="avatarUser" size-class="h-28 w-28" text-class="text-2xl" ring-class="ring-4 ring-white/10" shadow-class="shadow-[0_20px_40px_rgba(0,0,0,0.25)]" />
        <b class="mt-4 text-lg text-[var(--text)]">{{ profile.name || 'Người dùng QuizFlex' }}</b>
        <span class="mt-1 text-sm font-bold text-[var(--muted)]">{{ profile.email || 'Chưa đăng nhập' }}</span>
      </div>

      <div class="mt-6 grid gap-3 text-sm font-bold text-[var(--muted)]">
        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4"><span>Email</span><b class="mt-1 block text-[var(--text)]">{{ profile.email || 'Chưa đăng nhập' }}</b></div>
        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4"><span>Role</span><b class="mt-1 block text-[var(--text)]">{{ profile.role_label || profile.role || 'Guest' }}</b></div>
      </div>
    </article>

    <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)]">
      <h1 class="text-4xl font-black tracking-[-0.06em] text-[var(--text)]">Thông tin hiển thị</h1>
      <div class="mt-6 grid gap-5">
        <div class="grid gap-4 xl:grid-cols-[220px_minmax(0,1fr)]">
          <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-sm font-black text-[var(--text)]">Avatar người dùng</p>
            <p class="mt-1 text-xs font-bold leading-5 text-[var(--muted)]">Chỉ upload file ảnh. Ảnh được lưu vào backend và đường dẫn lưu trong bảng users.</p>
            <div class="mt-4 flex flex-wrap items-center gap-3">
              <button class="btn-ghost !px-4 !py-2 text-xs" type="button" @click="openAvatarPicker">Upload ảnh</button>
              <button v-if="avatarUser.avatar" class="rounded-full border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-xs font-black text-rose-300" type="button" @click="removeAvatar">Xóa ảnh</button>
            </div>
            <p v-if="avatarFile" class="mt-3 text-xs font-bold text-[var(--primary)]">Đã chọn: {{ avatarFile.name }}</p>
            <input ref="avatarInput" class="hidden" type="file" accept="image/png,image/jpeg,image/jpg,image/webp" @change="handleAvatarFileChange" />
          </div>

          <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-4 text-sm font-bold leading-6 text-[var(--muted)]">
            Lưu avatar ở DB giúp đổi trình duyệt vẫn còn ảnh. Một phát minh vĩ đại gọi là “dữ liệu nên nằm ở server”, nhân loại mất hơi lâu để đồng thuận chuyện này.
          </div>
        </div>

        <label class="grid gap-2 text-sm font-black text-[var(--text)]">
          Họ tên
          <input v-model="profile.name" class="field" />
        </label>

        <label class="grid gap-2 text-sm font-black text-[var(--text)]">
          Email
          <input v-model="profile.email" class="field" disabled />
        </label>

        <div v-if="message" class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-300">{{ message }}</div>
        <div v-if="errorMessage" class="rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">{{ errorMessage }}</div>

        <button class="btn-primary w-fit" type="button" :disabled="isSaving" @click="saveProfile">{{ isSaving ? 'Đang lưu...' : 'Lưu vào database' }}</button>
      </div>
    </article>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import UserAvatar from '@/components/common/UserAvatar.vue'
import { authApi, currentUserStorage, tokenStorage } from '@/services/api'

const profile = reactive({ name: '', email: '', role: 'guest', role_label: 'Guest', avatar: '', ...(currentUserStorage.get() || {}) })
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
    errorMessage.value = 'Avatar phải là file ảnh.'
    event.target.value = ''
    return
  }

  if (file.size > 2 * 1024 * 1024) {
    errorMessage.value = 'Avatar tối đa 2MB.'
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
    errorMessage.value = `Không tải được hồ sơ: ${error.message}`
  }
}

const saveProfile = async () => {
  if (!tokenStorage.get()) {
    errorMessage.value = 'Bạn cần đăng nhập trước khi lưu hồ sơ.'
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
    message.value = 'Đã lưu hồ sơ và avatar vào database.'
    setTimeout(() => { message.value = '' }, 2500)
  } catch (error) {
    errorMessage.value = `Lưu thất bại: ${error.message}`
  } finally {
    isSaving.value = false
  }
}

onMounted(loadProfileFromApi)
onBeforeUnmount(revokeAvatarPreview)
</script>
