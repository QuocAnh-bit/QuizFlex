<template>
  <section class="grid gap-6 py-8">
    <!-- 1. LOADING STATE -->
    <AppLoadingState 
      v-if="isLoading" 
      title="Đang tải chi tiết phòng học..." 
      message="Vui lòng chờ trong giây lát để hệ thống tải dữ liệu phòng và danh sách bài tập."
      icon="🏫"
    />

    <!-- 2. ERROR STATE -->
    <AppErrorState 
      v-else-if="errorMessage" 
      title="Không thể tải chi tiết phòng học"
      :message="errorMessage" 
      @retry="loadRoom"
    >
      <template #actions>
        <router-link class="btn-ghost text-xs" to="/homework-rooms">Quay lại danh sách</router-link>
      </template>
    </AppErrorState>

    <template v-else-if="room">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <router-link class="btn-ghost" to="/homework-rooms">Quay lại danh sách</router-link>
        <div class="flex items-center gap-2">
          <button
            v-if="canManageRoom"
            @click="exportGradebookExcel"
            type="button"
            :disabled="isExportingGradebook"
            class="btn-primary flex items-center gap-2"
          >
            <span>📊 {{ isExportingGradebook ? 'Đang xuất...' : 'Xuất bảng điểm Excel' }}</span>
          </button>
          <button 
            v-else-if="room" 
            class="btn-ghost text-rose-400 hover:bg-rose-500/10 hover:text-rose-300"
            type="button" 
            :disabled="isLeavingRoom" 
            @click="leaveRoom"
          >
            {{ isLeavingRoom ? 'Đang rời phòng...' : 'Rời phòng' }}
          </button>
        </div>
      </div>

      <!-- Banner Banned cho Host -->
      <div v-if="isBanned && isHost" class="rounded-[2.5rem] border border-amber-500/30 bg-amber-500/10 p-6 text-sm font-bold text-amber-300 flex items-center gap-3">
        <span>⚠️</span>
        <span>Phòng này đã bị quản trị viên khóa. Bạn chỉ có thể xem thông tin phòng và không thể thực hiện bất kỳ thao tác quản lý nào.</span>
      </div>

      <!-- Banner Banned cho Member -->
      <div v-if="isBanned && !isHost && !isAdmin" class="rounded-[2.5rem] border border-rose-500/30 bg-rose-500/10 p-6 text-sm font-bold text-rose-300 flex items-center gap-3">
        <span>🚫</span>
        <span>Phòng đã bị quản trị viên khóa và hiện không thể sử dụng.</span>
      </div>
      <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl">
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
        <div class="relative z-10 flex flex-col justify-between gap-6 xl:flex-row xl:items-end">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Room Homework</p>
            <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)]">{{ room.name || 'Room Homework' }}</h1>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-[var(--muted)]">{{ room.description || 'Chưa có mô tả.' }}</p>
          </div>
          <div class="grid gap-2 text-right">
            <span class="rounded-full bg-[var(--chip-active)] px-4 py-2 text-sm font-black text-[var(--primary)]">{{ room.code || 'NO CODE' }}</span>
            <StatusBadge :value="room.status || 'active'" />
          </div>
        </div>

        <div class="relative z-10 mt-6 grid gap-3 md:grid-cols-4">
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Host</p>
            <p class="mt-1 text-lg font-black text-[var(--text)]">{{ room.host?.name || '-' }}</p>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Thành viên</p>
            <p class="mt-1 text-lg font-black text-[var(--text)]">{{ memberCount }}</p>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Bài được giao</p>
            <p class="mt-1 text-lg font-black text-[var(--text)]">{{ room.assignments_count ?? assignments.length }}</p>
          </div>
          <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
            <p class="text-xs font-bold text-[var(--muted)]">Ngày tạo</p>
            <p class="mt-1 text-lg font-black text-[var(--text)]">{{ formatDate(room.created_at) }}</p>
          </div>
        </div>
      </article>

      <!-- Card Quản lý phòng (Chỉ dành cho Host/Admin) -->
      <article v-if="canManageRoom" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
        <!-- Tab Bar -->
        <div class="flex border-b border-[var(--border)] pb-px overflow-x-auto scrollbar-none gap-6 mb-6">
          <button
            v-if="isHost"
            type="button"
            class="whitespace-nowrap pb-4 text-sm font-black transition relative"
            :class="activeSettingsTab === 'general' ? 'text-[var(--primary)]' : 'text-[var(--muted)] hover:text-[var(--text)]'"
            @click="activeSettingsTab = 'general'"
          >
            Cấu hình chung
            <div v-if="activeSettingsTab === 'general'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-[var(--primary)] rounded-full"></div>
          </button>

          <button
            v-if="isHost && (settingsForm.join_policy === 'email_whitelist' || room.join_policy === 'email_whitelist')"
            type="button"
            class="whitespace-nowrap pb-4 text-sm font-black transition relative flex items-center gap-2"
            :class="activeSettingsTab === 'whitelist' ? 'text-[var(--primary)]' : 'text-[var(--muted)] hover:text-[var(--text)]'"
            @click="activeSettingsTab = 'whitelist'"
          >
            Email whitelist
            <span class="rounded-full bg-[var(--primary)]/10 px-2 py-0.5 text-xs text-[var(--primary)]">
              {{ allowedMembers.length }}
            </span>
            <div v-if="activeSettingsTab === 'whitelist'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-[var(--primary)] rounded-full"></div>
          </button>

          <button
            type="button"
            class="whitespace-nowrap pb-4 text-sm font-black transition relative flex items-center gap-2"
            :class="activeSettingsTab === 'members' ? 'text-[var(--primary)]' : 'text-[var(--muted)] hover:text-[var(--text)]'"
            @click="activeSettingsTab = 'members'"
          >
            Thành viên
            <span class="rounded-full bg-[var(--surface-soft)] px-2 py-0.5 text-xs text-[var(--muted)] border border-[var(--border)]">
              {{ memberTab === 'active' ? filteredMembers.length : pendingMembers.length }}
            </span>
            <div v-if="activeSettingsTab === 'members'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-[var(--primary)] rounded-full"></div>
          </button>
        </div>

        <!-- Tab 1: Cấu hình chung -->
        <div v-if="activeSettingsTab === 'general' && isHost" class="space-y-4">
          <div>
            <h2 class="text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Cấu hình phòng</h2>
            <p class="mt-1 text-sm font-bold text-[var(--muted)]">Chỉnh sửa thông tin phòng và chính sách tham gia.</p>
          </div>

          <form class="mt-6 grid gap-5" @submit.prevent="updateRoomSettings">
            <div class="grid gap-5 md:grid-cols-2">
              <label class="grid gap-2">
                <span class="text-sm font-black text-[var(--text)]">Tên room</span>
                <input v-model.trim="settingsForm.name" class="field" maxlength="255" placeholder="Tên room" :disabled="isBanned" />
              </label>

              <label class="grid gap-2">
                <span class="text-sm font-black text-[var(--text)]">Quyền tham gia</span>
                <select v-model="settingsForm.join_policy" class="field" :disabled="isBanned">
                  <option value="open">Công khai (Ai có mã phòng đều có thể tham gia)</option>
                  <option value="email_whitelist">Giới hạn (Chỉ email trong danh sách mới được tham gia)</option>
                </select>
              </label>
            </div>

            <label class="grid gap-2">
              <span class="text-sm font-black text-[var(--text)]">Mô tả</span>
              <textarea v-model.trim="settingsForm.description" class="field min-h-20 resize-y" placeholder="Mô tả ngắn của phòng" :disabled="isBanned"></textarea>
            </label>

            <div class="flex justify-end gap-3">
            <button
                type="button"
                class="btn-danger shrink-0"
                @click="showDissolveDialog = true"
              >
                🚨 Giải tán phòng
              </button>
              <button v-if="!isBanned" class="btn-primary" type="submit" :disabled="isUpdatingSettings">
                {{ isUpdatingSettings ? 'Đang lưu...' : 'Lưu cấu hình' }}
              </button>
            </div>
          </form>

          <div v-if="settingsSuccessMessage" class="mt-4 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-300">
            {{ settingsSuccessMessage }}
          </div>
          <div v-if="settingsErrorMessage" class="mt-4 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">
            {{ settingsErrorMessage }}
          </div>

          
        </div>

        <!-- Tab 2: Email Whitelist -->
        <div v-if="activeSettingsTab === 'whitelist' && isHost && (settingsForm.join_policy === 'email_whitelist' || room.join_policy === 'email_whitelist')" class="space-y-6">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 class="text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Danh sách email whitelist</h2>
              <p class="mt-1 text-sm font-bold text-[var(--muted)]">Chỉ các email trong danh sách này mới được phép tham gia phòng học.</p>
            </div>
            <!-- Tách rõ số liệu đã tham gia / chưa tham gia -->
            <div class="flex gap-2 text-xs font-black">
              <span class="rounded-full bg-emerald-500/10 border border-emerald-500/20 px-3 py-1 text-emerald-400">
                Đã tham gia: {{ allowedJoinedCount }}
              </span>
              <span class="rounded-full bg-[var(--surface-soft)] border border-[var(--border)] px-3 py-1 text-[var(--muted)]">
                Chưa tham gia: {{ allowedPendingCount }}
              </span>
            </div>
          </div>

          <!-- Thanh công cụ hàng ngang: Tìm kiếm -> Import Excel -> Thêm thủ công -->
          <div class="grid gap-4 md:grid-cols-[2fr_1.2fr_1fr] items-center bg-[var(--surface-soft)] p-4 rounded-3xl border border-[var(--border)]">
            <!-- 1. Tìm kiếm -->
            <div class="relative w-full" :class="isBanned ? 'col-span-3' : ''">
              <input
                v-model="whitelistSearchQuery"
                type="text"
                class="field pr-8 text-sm w-full bg-[var(--surface)]"
                placeholder="Tìm kiếm email whitelist..."
              />
              <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--muted)] text-xs font-bold pointer-events-none">🔍</span>
            </div>

            <!-- 2. Import Excel/CSV -->
            <label v-if="!isBanned" class="btn-ghost w-full justify-center flex items-center gap-2 cursor-pointer text-xs font-black text-[var(--primary)] hover:bg-[var(--primary)]/10 h-10 rounded-2xl border border-[var(--border)] bg-[var(--surface)] shadow-sm">
              <span>📥 Import Excel / CSV</span>
              <input
                type="file"
                accept=".xlsx, .xls, .csv"
                class="hidden"
                @change="handleImportFile"
              />
            </label>

            <!-- 3. Thêm thủ công (Toggle button) -->
            <button
              v-if="!isBanned"
              type="button"
              class="btn-primary w-full justify-center flex items-center gap-2 text-xs font-black h-10 rounded-2xl shadow-sm"
              @click="isShowingManualInput = !isShowingManualInput"
            >
              <span>✍️ {{ isShowingManualInput || allowedEmailText ? 'Đóng ô nhập' : 'Thêm thủ công' }}</span>
            </button>
          </div>

          <!-- Khung nhập email thủ công (Chỉ hiển thị khi bấm "Thêm thủ công" hoặc có email chờ lưu từ file import) -->
          <div v-if="isShowingManualInput || allowedEmailText" class="p-5 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-xs font-black text-[var(--text)] uppercase tracking-wider">Nhập danh sách email</span>
              <span class="text-xs text-[var(--muted)]">Các email phân cách nhau bằng dấu phẩy hoặc xuống dòng</span>
            </div>
            <form class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto]" @submit.prevent="addAllowedMembers">
              <textarea
                v-model="allowedEmailText"
                class="field min-h-24 resize-y bg-[var(--surface)]"
                placeholder="student1@example.com&#10;student2@example.com, student3@example.com"
              ></textarea>
              <button class="btn-primary self-start" type="submit" :disabled="isSavingAllowedMembers">
                {{ isSavingAllowedMembers ? 'Đang thêm...' : 'Thêm email' }}
              </button>
            </form>
          </div>

          <div v-if="allowedMembersMessage" class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-300">
            {{ allowedMembersMessage }}
          </div>
          <div v-if="allowedMembersError" class="rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm font-bold text-rose-300">
            {{ allowedMembersError }}
          </div>

          <!-- Bảng quản lý Whitelist -->
          <div class="space-y-4">

            <div v-if="filteredAllowedMembers.length" class="border border-[var(--border)] bg-[var(--surface-soft)] rounded-[1.5rem] p-4">
              <!-- Thanh công cụ quản lý xóa nhiều / xóa tất cả -->
              <div v-if="!isBanned" class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--border)] pb-3 mb-3">
                <label class="flex items-center gap-2 cursor-pointer text-sm font-bold text-[var(--text)]">
                  <input
                    type="checkbox"
                    class="rounded border-[var(--border)] bg-transparent text-[var(--primary)] focus:ring-[var(--primary)]"
                    :checked="isAllAllowedSelected"
                    @change="toggleSelectAllAllowed"
                  />
                  <span>Chọn tất cả ({{ filteredAllowedMembers.length }})</span>
                </label>
                
                <div class="flex gap-2">
                  <button
                    v-if="selectedAllowedIds.length"
                    type="button"
                    class="btn-ghost px-3 py-1.5 text-xs text-rose-400 hover:bg-rose-500/10"
                    @click="removeSelectedAllowedMembers"
                  >
                    Xóa các email đã chọn ({{ selectedAllowedIds.length }})
                  </button>
                  <button
                    type="button"
                    class="btn-ghost px-3 py-1.5 text-xs text-rose-500 font-bold hover:bg-rose-600/10"
                    @click="clearAllAllowedMembers"
                  >
                    Xóa tất cả
                  </button>
                </div>
              </div>

              <!-- Danh sách cuộn -->
              <div class="max-h-[350px] overflow-y-auto pr-1 space-y-2">
                <article
                  v-for="allowedMember in filteredAllowedMembers"
                  :key="allowedMember.id"
                  class="flex items-center justify-between gap-3 rounded-xl border border-[var(--border)] bg-[var(--surface)] p-3 hover:border-[var(--primary)]/30 transition-all"
                >
                  <div class="flex items-center gap-3 min-w-0">
                    <input
                      v-if="!isBanned"
                      type="checkbox"
                      class="rounded border-[var(--border)] bg-transparent text-[var(--primary)] focus:ring-[var(--primary)]"
                      :value="allowedMember.id"
                      v-model="selectedAllowedIds"
                    />
                    <div class="min-w-0">
                      <h3 class="break-words text-sm font-black text-[var(--text)] truncate">{{ allowedMember.email }}</h3>
                      <p class="mt-0.5 text-xs font-bold text-[var(--muted)]">
                        {{ allowedMember.joined_at ? `Đã tham gia: ${formatDateTime(allowedMember.joined_at)}` : 'Chưa tham gia' }}
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center gap-3 shrink-0">
                    <span
                      class="rounded-full px-2.5 py-0.5 text-[10px] font-black border uppercase tracking-wider"
                      :class="allowedMember.joined_at ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-[var(--surface-soft)] text-[var(--muted)] border-[var(--border)]'"
                    >
                      {{ allowedMember.joined_at ? 'Đã tham gia' : 'Chưa tham gia' }}
                    </span>
                    <button
                      v-if="!isBanned"
                      class="btn-ghost px-2.5 py-1.5 text-xs text-rose-400 hover:bg-rose-500/10"
                      type="button"
                      @click="removeAllowedMember(allowedMember.id)"
                    >
                      Xóa
                    </button>
                  </div>
                </article>
              </div>
            </div>

            <div v-else class="mt-5 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm font-bold text-[var(--muted)]">
              {{ whitelistSearchQuery ? 'Không tìm thấy email nào khớp với từ khóa tìm kiếm.' : 'Chưa có email nào trong danh sách.' }}
            </div>
          </div>
        </div>

        <!-- Tab 3: Quản lý thành viên (Chỉ dành cho Host/Admin) -->
        <div v-if="activeSettingsTab === 'members'" class="space-y-6">
          <div class="flex items-center justify-between gap-3 border-b border-[var(--border)] pb-3">
            <div>
              <h2 class="text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Quản lý thành viên</h2>
              <p class="mt-1 text-sm font-bold text-[var(--muted)]">Xem danh sách thành viên chính thức và phê duyệt yêu cầu tham gia.</p>
            </div>
            <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-3 py-1 text-xs font-black text-[var(--muted)]">
              {{ memberTab === 'active' ? filteredMembers.length : pendingMembers.length }}
            </span>
          </div>

          <!-- Tabs chọn thành viên: Chỉ hiển thị cho Host -->
          <div class="flex gap-2">
            <button
              type="button"
              class="rounded-full px-4 py-1.5 text-xs font-black transition border"
              :class="memberTab === 'active' ? 'bg-[var(--chip-active)] text-[var(--primary)] border-[var(--border-strong)]' : 'text-[var(--muted)] hover:text-[var(--text)] border-transparent'"
              @click="setMemberTab('active')"
            >
              Chính thức ({{ filteredMembers.length }})
            </button>
            <button
              type="button"
              class="rounded-full px-4 py-1.5 text-xs font-black transition border"
              :class="memberTab === 'pending' ? 'bg-[var(--chip-active)] text-[var(--primary)] border-[var(--border-strong)]' : 'text-[var(--muted)] hover:text-[var(--text)] border-transparent'"
              @click="setMemberTab('pending')"
            >
              Chờ duyệt ({{ pendingMembers.length }})
            </button>
          </div>

          <!-- Ô tìm kiếm thành viên -->
          <div class="relative w-full sm:w-80">
            <input
              v-model="memberSearchQuery"
              type="text"
              class="field pr-8 text-sm bg-[var(--surface-soft)]"
              placeholder="Tìm kiếm thành viên theo tên hoặc email..."
            />
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--muted)] text-xs font-bold pointer-events-none">🔍</span>
          </div>

          <!-- Nội dung Tab Thành viên chính thức -->
          <div v-if="memberTab === 'active'">
            <div v-if="filteredMembers.length" class="grid gap-3 max-h-[480px] overflow-y-auto pr-1">
              <article v-for="member in filteredMembers" :key="member.id || `${member.room_id}-${member.user_id}`" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <h3 class="font-black text-[var(--text)] truncate">{{ member.user?.name || `User #${member.user_id}` }}</h3>
                    <p class="mt-1 text-xs font-bold text-[var(--muted)] truncate">{{ member.user?.email || 'Chưa có email' }}</p>
                  </div>
                  <StatusBadge :value="member.role || 'member'" />
                </div>
                <div class="mt-3 flex items-center justify-between">
                  <StatusBadge :value="member.status || 'active'" />
                  <div class="flex gap-2">
                    <button
                      class="btn-ghost px-3 py-1.5 text-xs text-[var(--primary)] hover:bg-[var(--primary)]/10"
                      type="button"
                      @click="openMemberDetail(member)"
                    >
                      Chi tiết
                    </button>
                    <button
                      v-if="isHost && !isBanned"
                      class="btn-ghost px-3 py-1.5 text-xs text-rose-400 hover:bg-rose-500/10"
                      type="button"
                      @click="removeMember(member)"
                    >
                      Xóa
                    </button>
                  </div>
                </div>
              </article>
            </div>
            <div v-else class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm font-bold text-[var(--muted)]">
              {{ memberSearchQuery ? 'Không tìm thấy thành viên nào khớp với từ khóa tìm kiếm.' : 'Chưa có thành viên chính thức.' }}
            </div>
          </div>

          <!-- Nội dung Tab Thành viên chờ duyệt -->
          <div v-else-if="memberTab === 'pending'">
            <div v-if="filteredPendingMembers.length" class="grid gap-3 max-h-[480px] overflow-y-auto pr-1">
              <article v-for="member in filteredPendingMembers" :key="member.id || `${member.room_id}-${member.user_id}`" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <h3 class="font-black text-[var(--text)] truncate">{{ member.user?.name || `User #${member.user_id}` }}</h3>
                    <p class="mt-1 text-xs font-bold text-[var(--muted)] truncate">{{ member.user?.email || 'Chưa có email' }}</p>
                  </div>
                  <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-black text-amber-400 border border-amber-500/20">Chờ duyệt</span>
                </div>
                <div class="mt-3 flex items-center justify-between">
                  <span class="text-xs text-[var(--muted)]">Đăng ký: {{ formatDate(member.joined_at) }}</span>
                  <div class="flex gap-2">
                    <button
                      v-if="isHost && !isBanned"
                      class="btn-ghost px-3 py-1.5 text-xs text-emerald-400 hover:bg-emerald-500/10"
                      type="button"
                      :disabled="isApproving === member.id"
                      @click="approveMemberRequest(member)"
                    >
                      {{ isApproving === member.id ? 'Đang duyệt...' : 'Duyệt' }}
                    </button>
                    <button
                      v-if="isHost && !isBanned"
                      class="btn-ghost px-3 py-1.5 text-xs text-rose-400 hover:bg-rose-500/10"
                      type="button"
                      :disabled="isRejecting === member.id"
                      @click="rejectMemberRequest(member)"
                    >
                      {{ isRejecting === member.id ? 'Đang từ chối...' : 'Từ chối' }}
                    </button>
                  </div>
                </div>
              </article>
            </div>
            <div v-else class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm font-bold text-[var(--muted)]">
              {{ memberSearchQuery ? 'Không tìm thấy yêu cầu tham gia nào khớp với từ khóa tìm kiếm.' : 'Chưa có yêu cầu tham gia.' }}
            </div>
          </div>
        </div>
      </article>

      <div class="w-full">
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Assignments</p>
              <h2 class="mt-1 text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Bài được giao</h2>
            </div>
            <router-link v-if="isHost && !isBanned" class="btn-ghost" :to="`/homework-rooms/${roomId}/assignments/create`">Giao quiz</router-link>
            <button
              v-else-if="currentMember"
              @click="openMemberDetail(currentMember)"
              type="button"
              class="btn-ghost shrink-0 text-xs font-black px-4 py-2 border border-[var(--border)] rounded-full text-[var(--primary)] hover:bg-[var(--primary)]/10 transition"
            >
              Chi tiết thành viên
            </button>
          </div>

          <div v-if="sortedAssignments.length" class="mt-5 grid gap-4 max-h-[520px] overflow-y-auto pr-1">
            <article v-for="assignment in sortedAssignments" :key="assignment.id" class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5">
              <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                <div>
                  <div class="flex flex-wrap items-center gap-2">
                    <StatusBadge :value="assignment.status || 'published'" />
                    <span class="rounded-full border border-[var(--border)] px-3 py-1 text-xs font-black text-[var(--muted)]">{{ assignment.show_result_mode || 'immediately' }}</span>
                  </div>
                  <h3 class="mt-3 text-xl font-black text-[var(--text)]">{{ assignment.title || assignment.quiz?.title || 'Bài được giao' }}</h3>
                  <p class="mt-2 text-sm leading-6 text-[var(--muted)]">{{ assignment.description || 'Chưa có mô tả.' }}</p>
                  <p class="mt-3 text-sm font-bold text-[var(--muted)]">Quiz: <span class="text-[var(--text)]">{{ assignment.quiz?.title || `#${assignment.quiz_id}` }}</span></p>
                </div>
                <router-link v-if="canManageRoom" class="btn-ghost whitespace-nowrap" :to="`/homework-rooms/${roomId}/assignments/${assignment.id}/attempts`">Xem bài nộp</router-link>
                <div v-else-if="isBanned" class="text-xs font-black text-rose-400 py-2">Phòng bị khóa</div>
                <router-link v-else class="btn-ghost whitespace-nowrap" :to="`/homework-rooms/${roomId}/assignments/${assignment.id}/take`">Làm bài</router-link>
              </div>

              <div class="mt-5 grid gap-3 md:grid-cols-4">
                <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-3">
                  <p class="text-xs font-bold text-[var(--muted)]">Bắt đầu</p>
                  <p class="mt-1 text-sm font-black text-[var(--text)]">{{ formatDateTime(assignment.starts_at) }}</p>
                </div>
                <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-3">
                  <p class="text-xs font-bold text-[var(--muted)]">Deadline</p>
                  <p class="mt-1 text-sm font-black text-[var(--text)]">{{ formatDateTime(assignment.deadline_at) }}</p>
                </div>
                <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-3">
                  <p class="text-xs font-bold text-[var(--muted)]">Thời lượng</p>
                  <p class="mt-1 text-sm font-black text-[var(--text)]">{{ assignment.duration_minutes ? `${assignment.duration_minutes} phút` : 'Không giới hạn' }}</p>
                </div>
                <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-3">
                  <p class="text-xs font-bold text-[var(--muted)]">Số lần</p>
                  <p class="mt-1 text-sm font-black text-[var(--text)]">{{ assignment.my_attempts_count ?? 0 }}/{{ assignment.max_attempts ?? 1 }}</p>
                </div>
              </div>
            </article>
          </div>

          <div v-else class="mt-5 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-10 text-center">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Empty</p>
            <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-[var(--text)]">Chưa có bài nào được giao</h3>
            <p class="mt-3 text-sm leading-7 text-[var(--muted)]">Khi chủ room giao quiz, bài sẽ xuất hiện tại đây.</p>
          </div>
        </article>
      </div>

      <!-- Modal Chi tiết thành viên -->
      <div v-if="selectedMember" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity">
        <div class="relative w-full max-w-lg rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)] transition-all">
          <div class="flex items-center justify-between pb-4 border-b border-[var(--border)]">
            <h3 class="text-xl font-black text-[var(--text)]">Chi tiết thành viên</h3>
            <button @click="closeMemberDetail" class="text-[var(--muted)] hover:text-[var(--text)] text-2xl font-bold">&times;</button>
          </div>

          <div class="mt-5 space-y-6 max-h-[70vh] overflow-y-auto pr-1">
            <div class="flex items-center gap-3">
              <div class="h-12 w-12 rounded-full bg-[var(--primary)]/10 flex items-center justify-center font-black text-[var(--primary)] text-lg">
                {{ selectedMember.user?.name ? selectedMember.user.name.charAt(0).toUpperCase() : 'M' }}
              </div>
              <div class="min-w-0">
                <h4 class="font-black text-[var(--text)] truncate">{{ selectedMember.user?.name || `User #${selectedMember.user_id}` }}</h4>
                <p class="text-xs font-bold text-[var(--muted)] truncate">{{ selectedMember.user?.email || 'Chưa có email' }}</p>
              </div>
            </div>

            <div>
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)] mb-3">Thông tin học tập</p>
              <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3">
                  <p class="text-[10px] font-bold text-[var(--muted)] uppercase">Ngày tham gia</p>
                  <p class="mt-1 text-sm font-black text-[var(--text)]">{{ formatDateTime(selectedMember.joined_at) }}</p>
                </div>
                <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3">
                  <p class="text-[10px] font-bold text-[var(--muted)] uppercase">Bài được giao</p>
                  <p class="mt-1 text-sm font-black text-[var(--text)]">{{ selectedMember.assigned ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3">
                  <p class="text-[10px] font-bold text-[var(--muted)] uppercase">Đã hoàn thành</p>
                  <p class="mt-1 text-sm font-black text-[var(--text)]">{{ selectedMember.completed ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3">
                  <p class="text-[10px] font-bold text-[var(--muted)] uppercase">Tỷ lệ hoàn thành</p>
                  <p class="mt-1 text-sm font-black text-[var(--text)]">{{ selectedMember.completion_rate ?? 0 }}%</p>
                </div>
                <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 col-span-2">
                  <p class="text-[10px] font-bold text-[var(--muted)] uppercase">Điểm trung bình</p>
                  <p class="mt-1 text-lg font-black text-[var(--primary)]">{{ selectedMember.average_score ?? 0 }}/10</p>
                </div>
              </div>
            </div>

            <div>
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)] mb-3">Đánh giá thành viên</p>
              
              <div v-if="isLoadingEvaluation" class="py-4 text-center text-xs font-bold text-[var(--muted)]">
                Đang tải đánh giá...
              </div>

              <div v-else>
                <!-- Nhận xét theo từng bài (Lịch sử nhận xét) -->
                <div class="border-b border-[var(--border)] pb-4 mb-4">
                  <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)] mb-3">Lịch sử nhận xét bài nộp</p>
                  <div 
                    v-if="evaluationData?.submission_evaluations && evaluationData.submission_evaluations.length"
                    class="space-y-3 max-h-48 overflow-y-auto pr-1"
                  >
                    <div 
                      v-for="subEval in evaluationData.submission_evaluations" 
                      :key="subEval.id"
                      class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-xs"
                    >
                      <div class="flex items-start justify-between gap-2">
                        <span class="font-black text-[var(--text)] truncate max-w-[200px]" :title="subEval.assignment_name">
                          {{ subEval.assignment_name }}
                        </span>
                        <span class="shrink-0 font-bold text-[var(--muted)]">
                          Điểm: <strong class="text-[var(--text)]">{{ subEval.score }}</strong>
                        </span>
                      </div>
                      <p class="mt-1 text-[10px] text-[var(--muted)]">{{ formatDateTime(subEval.submitted_at) }}</p>
                      <p class="mt-2 font-medium leading-relaxed" :class="subEval.comment ? 'text-[var(--text)] italic' : 'text-[var(--muted)]'">
                        {{ subEval.comment ? `"${subEval.comment}"` : 'Chưa có nhận xét bài nộp.' }}
                      </p>
                    </div>
                  </div>
                  <div v-else class="rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-3 text-center text-xs font-bold text-[var(--muted)]">
                    Chưa có bài nộp nào trong room này.
                  </div>
                </div>

                <div v-if="isHost && !isBanned" class="space-y-4">
                  <div>
                    <label class="block text-xs font-bold text-[var(--muted)] mb-1 uppercase">Nhận xét của chủ phòng</label>
                    <textarea 
                      v-model="evaluationForm.comment"
                      class="field min-h-20 w-full resize-y text-sm"
                      placeholder="Nhập nhận xét thành viên (Ví dụ: Làm bài đầy đủ và nghiêm túc...)"
                    ></textarea>
                  </div>
                </div>

                <div v-else class="space-y-3 rounded-xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
                  <div>
                    <p class="text-[10px] font-bold text-[var(--muted)] uppercase">Nhận xét</p>
                    <p class="mt-1 text-sm font-bold leading-relaxed text-[var(--text)] italic">
                      "{{ evaluationData?.comment || 'Chưa có nhận xét nào.' }}"
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-[var(--border)]">
            <button @click="closeMemberDetail" class="btn-ghost" type="button">Đóng</button>
            <button 
              v-if="isHost && !isLoadingEvaluation && !isBanned" 
              @click="saveEvaluation" 
              class="btn-primary" 
              type="button"
              :disabled="isSavingEvaluation"
            >
              {{ isSavingEvaluation ? 'Đang lưu...' : (evaluationData ? 'Cập nhật đánh giá' : 'Lưu đánh giá') }}
            </button>
          </div>
        </div>
      </div>
    </template>
  </section>

  <!-- Dialog xác nhận giải tán phòng -->
  <Teleport to="body">
    <div
      v-if="showDissolveDialog"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
      @click.self="showDissolveDialog = false"
    >
      <!-- Overlay -->
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showDissolveDialog = false"></div>

      <!-- Dialog card -->
      <div class="relative z-10 w-full max-w-lg rounded-[2rem] border border-rose-500/30 bg-[var(--surface)] p-8 shadow-2xl">
        <!-- Icon -->
        <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-500/15 text-2xl">🚨</div>

        <h3 class="text-xl font-black tracking-tight text-[var(--text)]">Giải tán phòng?</h3>

        <div class="mt-4 space-y-2 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 text-sm leading-7 text-[var(--muted)]">
          <p>Sau khi giải tán:</p>
          <ul class="ml-4 list-disc space-y-1">
            <li>Phòng sẽ không còn hiển thị trong danh sách của bạn.</li>
            <li>Thành viên sẽ không thể tiếp tục truy cập phòng.</li>
            <li>Toàn bộ dữ liệu phòng vẫn được lưu trong hệ thống.</li>
            <li>Quản trị viên có thể khôi phục phòng nếu cần.</li>
          </ul>
        </div>

        <div v-if="dissolveErrorMessage" class="mt-4 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-3 text-sm font-bold text-rose-300">
          {{ dissolveErrorMessage }}
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
          <button
            type="button"
            class="btn-ghost"
            :disabled="isDissolvingRoom"
            @click="showDissolveDialog = false"
          >
            Hủy
          </button>
          <button
            type="button"
            class="btn-danger"
            :disabled="isDissolvingRoom"
            @click="confirmDissolve"
          >
            {{ isDissolvingRoom ? 'Đang giải tán...' : 'Giải tán phòng' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>


<script setup>
import { computed, onMounted, ref, watch, inject } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLoadingState from '@/components/common/AppLoadingState.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'

const showConfirm = inject('showConfirm')
const showToast = inject('showToast')

const confirmAndExecute = (title, message, action) => {
  if (showConfirm) {
    showConfirm(title, message, action)
  } else {
    if (window.confirm(message)) {
      action()
    }
  }
}
import StatusBadge from '@/components/common/StatusBadge.vue'
import { currentUserStorage, homeworkApi } from '@/services/api'

const route = useRoute()
const router = useRouter()
const roomId = computed(() => route.params.roomId)
const currentUser = currentUserStorage.get()

const room = ref(null)
const isLeavingRoom = ref(false)
const members = ref([])
const pendingMembers = ref([])
const memberTab = ref('active')
const isApproving = ref(null)
const isRejecting = ref(null)
const assignments = ref([])
const allowedMembers = ref([])
const selectedAllowedIds = ref([])
const allowedEmailText = ref('')
const isLoading = ref(false)
const isSavingAllowedMembers = ref(false)
const errorMessage = ref('')
const allowedMembersError = ref('')
const allowedMembersMessage = ref('')

const isShowingManualInput = ref(false)
const allowedJoinedCount = computed(() => allowedMembers.value.filter(m => m.user_id).length)
const allowedPendingCount = computed(() => allowedMembers.value.filter(m => !m.user_id).length)
const memberCount = computed(() => filteredMembers.value.length)

// Evaluation state variables
const selectedMember = ref(null)
const isLoadingEvaluation = ref(false)
const isSavingEvaluation = ref(false)
const evaluationData = ref(null)
const evaluationForm = ref({
  comment: '',
})

// Room settings state variables
const settingsForm = ref({
  name: '',
  description: '',
  join_policy: 'open',
})
const isUpdatingSettings = ref(false)
const settingsSuccessMessage = ref('')
const settingsErrorMessage = ref('')

// Dissolve room state
const showDissolveDialog = ref(false)
const isDissolvingRoom = ref(false)
const dissolveErrorMessage = ref('')

const isHost = computed(() => Number(room.value?.host_id) === Number(currentUser?.id))
const isAdmin = computed(() => currentUser?.role === 'admin')
const isBanned = computed(() => room.value?.status === 'banned')
const canManageRoom = computed(() => isAdmin.value || isHost.value)

const activeSettingsTab = ref('general')
const whitelistSearchQuery = ref('')
const isExportingGradebook = ref(false)
const memberSearchQuery = ref('')

watch(
  () => route.query,
  (newQuery) => {
    if (newQuery.tab) {
      activeSettingsTab.value = newQuery.tab
    }
    if (newQuery.status) {
      memberTab.value = newQuery.status
    }
  },
  { immediate: true }
)

const filteredAllowedMembers = computed(() => {
  const query = whitelistSearchQuery.value.trim().toLowerCase()
  if (!query) return allowedMembers.value
  return allowedMembers.value.filter((m) => String(m.email).toLowerCase().includes(query))
})

const openMemberDetail = async (member) => {
  selectedMember.value = member
  isLoadingEvaluation.value = true
  evaluationData.value = null
  evaluationForm.value.comment = ''

  try {
    const data = await homeworkApi.getMemberEvaluation(roomId.value, member.user_id)
    evaluationData.value = data
    if (data) {
      evaluationForm.value.comment = data.comment || ''
    }
  } catch (error) {
    console.error('Không tải được đánh giá:', error)
  } finally {
    isLoadingEvaluation.value = false
  }
}

const closeMemberDetail = () => {
  selectedMember.value = null
}

const saveEvaluation = async () => {
  if (!selectedMember.value) return

  isSavingEvaluation.value = true
  try {
    const data = await homeworkApi.saveMemberEvaluation(roomId.value, selectedMember.value.user_id, {
      comment: evaluationForm.value.comment,
    })
    evaluationData.value = data
    
    if (showToast) {
      showToast('Đã lưu đánh giá thành công.', 'success')
    }
  } catch (error) {
    if (showToast) {
      showToast(`Không lưu được đánh giá: ${error.message}`, 'error')
    }
  } finally {
    isSavingEvaluation.value = false
  }
}
const canManageAllowedMembers = computed(() => room.value?.type === 'homework' && Number(room.value?.host_id) === Number(currentUser?.id))
const shouldShowAllowedMembers = computed(() => canManageAllowedMembers.value && room.value?.join_policy === 'email_whitelist')

const filteredMembers = computed(() => {
  let list = members.value.filter((member) => Number(member.user_id) !== Number(room.value?.host_id))
  
  const query = memberSearchQuery.value.trim().toLowerCase()
  if (query) {
    list = list.filter((member) => {
      const name = (member.user?.name || '').toLowerCase()
      const email = (member.user?.email || '').toLowerCase()
      return name.includes(query) || email.includes(query)
    })
  }

  return list.sort((a, b) => {
    const nameA = (a.user?.name || '').trim().toLowerCase();
    const nameB = (b.user?.name || '').trim().toLowerCase();
    return nameA.localeCompare(nameB, 'vi', { sensitivity: 'base' });
  });
})

const filteredPendingMembers = computed(() => {
  let list = pendingMembers.value
  
  const query = memberSearchQuery.value.trim().toLowerCase()
  if (query) {
    list = list.filter((member) => {
      const name = (member.user?.name || '').toLowerCase()
      const email = (member.user?.email || '').toLowerCase()
      return name.includes(query) || email.includes(query)
    })
  }
  
  return list
})

const currentMember = computed(() => {
  return members.value.find((m) => Number(m.user_id) === Number(currentUser?.id))
})

watch(
  () => [route.query.open_member_evaluation, currentMember.value],
  ([openEval, member]) => {
    if (openEval === '1' && member) {
      openMemberDetail(member)
    }
  },
  { immediate: true }
)

const sortedAssignments = computed(() => {
  return [...assignments.value].sort((a, b) => {
    const dateA = new Date(a.created_at || a.starts_at || 0);
    const dateB = new Date(b.created_at || b.starts_at || 0);
    return dateB - dateA;
  });
})

const formatDate = (value) => {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('vi-VN')
}

const formatDateTime = (value) => {
  if (!value) return '-'
  const date = new Date(value)
  return `${date.toLocaleDateString('vi-VN')} ${date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })}`
}

const loadRoomDetail = async () => {
  isLoading.value = true
  errorMessage.value = ''
  allowedMembersError.value = ''

  try {
    const [roomData, membersData, assignmentsData] = await Promise.all([
      homeworkApi.getHomeworkRoom(roomId.value),
      homeworkApi.getRoomMembers(roomId.value),
      homeworkApi.getRoomAssignments(roomId.value),
    ])

    room.value = roomData
    settingsForm.value.name = roomData.name || ''
    settingsForm.value.description = roomData.description || ''
    settingsForm.value.join_policy = roomData.join_policy || 'open'

    members.value = membersData
    assignments.value = assignmentsData
    allowedMembers.value = roomData?.type === 'homework' && Number(roomData.host_id) === Number(currentUser?.id)
      ? await homeworkApi.getAllowedMembers(roomId.value)
      : []

    if (currentUser?.role === 'admin' || Number(roomData.host_id) === Number(currentUser?.id)) {
      const pendingData = await homeworkApi.getRoomMembers(roomId.value, { status: 'pending' })
      pendingMembers.value = pendingData
    }
  } catch (error) {
    errorMessage.value = `Không tải được chi tiết room: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

const updateRoomSettings = async () => {
  settingsErrorMessage.value = ''
  settingsSuccessMessage.value = ''

  if (!settingsForm.value.name) {
    settingsErrorMessage.value = 'Tên room không được để trống.'
    return
  }

  isUpdatingSettings.value = true
  try {
    const updatedRoom = await homeworkApi.updateHomeworkRoom(roomId.value, {
      name: settingsForm.value.name,
      description: settingsForm.value.description || null,
      join_policy: settingsForm.value.join_policy,
    })
    room.value = updatedRoom
    settingsSuccessMessage.value = 'Cập nhật cấu hình phòng thành công.'
    
    if (updatedRoom.join_policy === 'email_whitelist' && !allowedMembers.value.length) {
      allowedMembers.value = await homeworkApi.getAllowedMembers(roomId.value)
    }
  } catch (error) {
    settingsErrorMessage.value = `Lỗi cập nhật cấu hình: ${error.message}`
  } finally {
    isUpdatingSettings.value = false
  }
}

const addAllowedMembers = async () => {
  allowedMembersError.value = ''
  allowedMembersMessage.value = ''

  const emails = parseAllowedEmails()
  if (!emails.length) {
    allowedMembersError.value = 'Bạn cần nhập ít nhất một email.'
    return
  }

  isSavingAllowedMembers.value = true
  try {
    await homeworkApi.addAllowedMembers(roomId.value, emails)
    allowedMembers.value = await homeworkApi.getAllowedMembers(roomId.value)
    allowedEmailText.value = ''
    allowedMembersMessage.value = 'Đã cập nhật danh sách email.'
  } catch (error) {
    allowedMembersError.value = `Không thêm được email: ${error.message}`
  } finally {
    isSavingAllowedMembers.value = false
  }
}

const handleImportFile = async (e) => {
  const file = e.target.files?.[0]
  if (!file) return

  const fileType = file.name.split('.').pop().toLowerCase()
  
  if (fileType === 'csv') {
    const reader = new FileReader()
    reader.onload = (event) => {
      const text = event.target.result
      extractEmailsFromText(text)
    }
    reader.readAsText(file)
  } else if (['xlsx', 'xls'].includes(fileType)) {
    try {
      const XLSX = await loadXlsxLibrary()
      const reader = new FileReader()
      reader.onload = (event) => {
        const data = new Uint8Array(event.target.result)
        const workbook = XLSX.read(data, { type: 'array' })
        
        // CHỈ đọc Sheet đầu tiên
        const firstSheetName = workbook.SheetNames[0]
        if (!firstSheetName) {
          if (showToast) showToast('File Excel không có sheet nào.', 'warning')
          return
        }
        const worksheet = workbook.Sheets[firstSheetName]
        const rows = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' })
        
        let emailColumnIndex = -1
        // Quét 10 dòng đầu để dò tìm cột chứa email
        for (let i = 0; i < Math.min(rows.length, 10); i++) {
          const row = rows[i]
          if (Array.isArray(row)) {
            for (let j = 0; j < row.length; j++) {
              const cellValue = String(row[j] || '').trim()
              if (/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/.test(cellValue)) {
                emailColumnIndex = j
                break
              }
            }
          }
          if (emailColumnIndex !== -1) break
        }
        
        // Nếu dò thấy cột email -> Chỉ lấy email ở cột đó
        if (emailColumnIndex !== -1) {
          const extracted = []
          for (let i = 0; i < rows.length; i++) {
            const row = rows[i]
            if (Array.isArray(row) && row[emailColumnIndex]) {
              const cellValue = String(row[emailColumnIndex]).trim()
              const match = cellValue.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/)
              if (match) {
                extracted.push(match[0])
              }
            }
          }
          extractEmailsFromList(extracted)
        } else {
          // Fallback: Quét tự do tất cả các ô trong Sheet đầu tiên
          let textContent = ''
          for (const key in worksheet) {
            if (Object.prototype.hasOwnProperty.call(worksheet, key) && key[0] !== '!') {
              const cell = worksheet[key]
              if (cell && cell.v !== undefined) {
                textContent += String(cell.v) + '\n'
              }
            }
          }
          extractEmailsFromText(textContent)
        }
      }
      reader.readAsArrayBuffer(file)
    } catch (err) {
      if (showToast) showToast(`Không đọc được file Excel: ${err.message}`, 'error')
    }
  } else {
    if (showToast) showToast('Định dạng tệp không được hỗ trợ. Vui lòng tải lên file Excel (.xlsx, .xls) hoặc CSV (.csv)', 'warning')
  }
  
  e.target.value = ''
}

const loadXlsxLibrary = () => {
  return new Promise((resolve, reject) => {
    if (window.XLSX) {
      resolve(window.XLSX)
      return
    }
    const script = document.createElement('script')
    script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js'
    script.onload = () => {
      if (window.XLSX) {
        resolve(window.XLSX)
      } else {
        reject(new Error('Không tải được thư viện xử lý Excel.'))
      }
    }
    script.onerror = () => reject(new Error('Lỗi kết nối khi tải thư viện xử lý Excel.'))
    document.head.appendChild(script)
  })
}

const exportGradebookExcel = async () => {
  if (isExportingGradebook.value) return
  isExportingGradebook.value = true

  try {
    const response = await homeworkApi.fetchRoomGradebook(roomId.value)
    
    // Tải thư viện Excel
    const XLSX = await loadXlsxLibrary()
    
    const { assignments: gradeAssignments, students: gradeStudents } = response
    
    // 1. Tạo Header Row
    // ["Họ tên", "Email", ...assignment titles, "Điểm TB (bài đã làm)", "Điểm TB tích lũy (hệ 10)"]
    const headers = ['Họ tên', 'Email']
    gradeAssignments.forEach(assignment => {
      headers.push(assignment.title || `Bài tập #${assignment.id}`)
    })
    headers.push('Điểm TB (bài đã làm)')
    headers.push('Điểm TB tích lũy (hệ 10)')
    
    const dataRows = [headers]
    
    // 2. Điền dữ liệu cho từng học sinh
    gradeStudents.forEach(student => {
      const row = [student.name || '', student.email || '']
      
      gradeAssignments.forEach(assignment => {
        const scoreData = student.scores[assignment.id]
        if (scoreData) {
          row.push(`${scoreData.score}/${scoreData.total_points}`)
        } else {
          row.push('') // Trống nếu chưa làm
        }
      })
      
      // Điểm trung bình bài đã làm
      row.push(student.average_score10 !== null ? student.average_score10 : '')
      // Điểm trung bình tích lũy (bài chưa làm = 0)
      row.push(student.average_score10_all !== null ? student.average_score10_all : '')
      
      dataRows.push(row)
    })
    
    // 3. Tạo Workbook & Sheet
    const worksheet = XLSX.utils.aoa_to_sheet(dataRows)
    const workbook = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Bảng điểm')
    
    // Thiết lập độ rộng cột cơ bản để hiển thị đẹp
    const wscols = [
      { wch: 25 }, // Họ tên
      { wch: 30 }, // Email
    ]
    gradeAssignments.forEach(() => {
      wscols.push({ wch: 20 }) // Các cột điểm
    })
    wscols.push({ wch: 22 }) // Điểm TB bài đã làm
    wscols.push({ wch: 25 }) // Điểm TB tích lũy
    worksheet['!cols'] = wscols
    
    // Xuất file
    const roomNameClean = (room.value?.name || 'phong-hoc').replace(/[^a-zA-Z0-9 Vietnamese_]/g, '-').trim()
    XLSX.writeFile(workbook, `bang-diem-${roomNameClean}.xlsx`)
  } catch (error) {
    if (showToast) showToast(`Không xuất được bảng điểm: ${error.message}`, 'error')
  } finally {
    isExportingGradebook.value = false
  }
}

const parseAllowedEmails = () => {
  const text = allowedEmailText.value || ''
  const rawEmails = text.split(/[\s,;\n]+/)
  const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/
  const validEmails = rawEmails
    .map(email => email.trim().toLowerCase())
    .filter(email => email && emailRegex.test(email))
  return [...new Set(validEmails)]
}

const extractEmailsFromText = (text) => {
  const emailRegex = /[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g
  const matchedEmails = text.match(emailRegex) || []
  
  if (!matchedEmails.length) {
    if (showToast) showToast('Không tìm thấy bất kỳ địa chỉ email nào trong tệp tin vừa tải lên.', 'warning')
    return
  }
  
  const uniqueEmails = [...new Set(matchedEmails.map(email => email.trim().toLowerCase()))]
  const currentEmails = parseAllowedEmails()
  const allEmails = [...new Set([...currentEmails, ...uniqueEmails])]
  
  allowedEmailText.value = allEmails.join('\n')
  if (showToast) showToast(`Đã trích xuất và thêm ${uniqueEmails.length} email mới vào danh sách nhập ở trên. Vui lòng kiểm tra lại và nhấn "Thêm email" để lưu.`, 'success')
}

const extractEmailsFromList = (emailList) => {
  if (!emailList.length) {
    if (showToast) showToast('Không tìm thấy bất kỳ địa chỉ email nào trong tệp tin vừa tải lên.', 'warning')
    return
  }
  
  const uniqueEmails = [...new Set(emailList.map(email => email.trim().toLowerCase()))]
  const currentEmails = parseAllowedEmails()
  const allEmails = [...new Set([...currentEmails, ...uniqueEmails])]
  
  allowedEmailText.value = allEmails.join('\n')
  if (showToast) showToast(`Đã tự động dò tìm cột email và trích xuất thành công ${uniqueEmails.length} email học sinh. Vui lòng kiểm tra lại danh sách ở trên và nhấn "Thêm email" để lưu.`, 'success')
}

const removeAllowedMember = async (allowedMemberId) => {
  allowedMembersError.value = ''
  allowedMembersMessage.value = ''

  confirmAndExecute(
    'Xóa email khỏi danh sách',
    'Xóa email này khỏi danh sách được phép tham gia?',
    async () => {
      try {
        await homeworkApi.removeAllowedMember(roomId.value, allowedMemberId)
        allowedMembers.value = allowedMembers.value.filter((member) => Number(member.id) !== Number(allowedMemberId))
        selectedAllowedIds.value = selectedAllowedIds.value.filter((id) => Number(id) !== Number(allowedMemberId))
        allowedMembersMessage.value = 'Đã xóa email khỏi danh sách.'
        if (showToast) showToast(allowedMembersMessage.value, 'success')
      } catch (error) {
        allowedMembersError.value = `Không xóa được email: ${error.message}`
        if (showToast) showToast(allowedMembersError.value, 'error')
      }
    }
  )
}

const isAllAllowedSelected = computed(() => {
  return allowedMembers.value.length > 0 && selectedAllowedIds.value.length === allowedMembers.value.length
})

const toggleSelectAllAllowed = (e) => {
  if (e.target.checked) {
    selectedAllowedIds.value = allowedMembers.value.map((m) => m.id)
  } else {
    selectedAllowedIds.value = []
  }
}

const removeSelectedAllowedMembers = async () => {
  if (!selectedAllowedIds.value.length) return
  confirmAndExecute(
    'Xóa các email đã chọn',
    `Xóa ${selectedAllowedIds.value.length} email đã chọn khỏi Whitelist?`,
    async () => {
      try {
        await homeworkApi.removeAllowedMembersBatch(roomId.value, selectedAllowedIds.value)
        allowedMembers.value = allowedMembers.value.filter((m) => !selectedAllowedIds.value.includes(m.id))
        selectedAllowedIds.value = []
        allowedMembersMessage.value = 'Đã xóa các email được chọn.'
        if (showToast) showToast(allowedMembersMessage.value, 'success')
      } catch (error) {
        allowedMembersError.value = `Không xóa được các email: ${error.message}`
        if (showToast) showToast(allowedMembersError.value, 'error')
      }
    }
  )
}

const clearAllAllowedMembers = async () => {
  if (!allowedMembers.value.length) return
  confirmAndExecute(
    'Xóa toàn bộ Whitelist',
    'CẢNH BÁO: Bạn có chắc chắn muốn xóa TOÀN BỘ email khỏi danh sách Whitelist? Hành động này không thể hoàn tác.',
    async () => {
      try {
        await homeworkApi.clearAllowedMembers(roomId.value)
        allowedMembers.value = []
        selectedAllowedIds.value = []
        allowedMembersMessage.value = 'Đã xóa toàn bộ danh sách email.'
        if (showToast) showToast(allowedMembersMessage.value, 'success')
      } catch (error) {
        allowedMembersError.value = `Không xóa được danh sách email: ${error.message}`
        if (showToast) showToast(allowedMembersError.value, 'error')
      }
    }
  )
}

const removeMember = async (member) => {
  confirmAndExecute(
    'Xóa thành viên',
    `Xóa thành viên "${member.user?.name || member.user_id}" khỏi Homework room này?`,
    async () => {
      try {
        await homeworkApi.removeRoomMember(roomId.value, member.id)
        members.value = members.value.filter((m) => Number(m.id) !== Number(member.id))
        if (showToast) showToast('Đã xóa thành viên khỏi room.', 'success')
      } catch (error) {
        errorMessage.value = `Không xóa được thành viên: ${error.message}`
        if (showToast) showToast(errorMessage.value, 'error')
      }
    }
  )
}

const setMemberTab = (tab) => {
  memberTab.value = tab
  if (tab === 'pending') {
    loadPendingMembers()
  } else {
    loadActiveMembers()
  }
}

const loadActiveMembers = async () => {
  try {
    const data = await homeworkApi.getRoomMembers(roomId.value, { status: 'active' })
    members.value = data
  } catch (error) {
    console.error('Không tải được danh sách thành viên:', error)
  }
}

const loadPendingMembers = async () => {
  try {
    const data = await homeworkApi.getRoomMembers(roomId.value, { status: 'pending' })
    pendingMembers.value = data
  } catch (error) {
    console.error('Không tải được danh sách chờ duyệt:', error)
  }
}

const approveMemberRequest = async (member) => {
  isApproving.value = member.id
  try {
    await homeworkApi.approveRoomMember(roomId.value, member.id)
    pendingMembers.value = pendingMembers.value.filter((m) => Number(m.id) !== Number(member.id))
    const membersData = await homeworkApi.getRoomMembers(roomId.value, { status: 'active' })
    members.value = membersData
    if (showToast) showToast('Đã duyệt thành viên thành công.', 'success')
  } catch (error) {
    if (showToast) showToast(`Không phê duyệt được: ${error.message}`, 'error')
  } finally {
    isApproving.value = null
  }
}

const rejectMemberRequest = async (member) => {
  confirmAndExecute(
    'Từ chối yêu cầu',
    `Từ chối yêu cầu tham gia của "${member.user?.name || member.user_id}"?`,
    async () => {
      isRejecting.value = member.id
      try {
        await homeworkApi.rejectRoomMember(roomId.value, member.id)
        pendingMembers.value = pendingMembers.value.filter((m) => Number(m.id) !== Number(member.id))
        if (showToast) showToast('Đã từ chối yêu cầu tham gia.', 'success')
      } catch (error) {
        if (showToast) showToast(`Lỗi khi từ chối: ${error.message}`, 'error')
      } finally {
        isRejecting.value = null
      }
    }
  )
}

const leaveRoom = async () => {
  confirmAndExecute(
    'Rời phòng',
    'Bạn có chắc chắn muốn rời khỏi phòng Homework này?',
    async () => {
      isLeavingRoom.value = true
      try {
        await homeworkApi.leaveHomeworkRoom(roomId.value)
        if (showToast) showToast('Đã rời phòng thành công.', 'success')
        router.push('/homework-rooms')
      } catch (error) {
        if (showToast) showToast(`Không rời được phòng: ${error.message}`, 'error')
      } finally {
        isLeavingRoom.value = false
      }
    }
  )
}

const confirmDissolve = async () => {
  dissolveErrorMessage.value = ''
  isDissolvingRoom.value = true
  try {
    await homeworkApi.dissolveHomeworkRoom(roomId.value)
    showDissolveDialog.value = false
    router.push('/homework-rooms')
  } catch (error) {
    dissolveErrorMessage.value = error.message || 'Không thể giải tán phòng lúc này.'
  } finally {
    isDissolvingRoom.value = false
  }
}

onMounted(async () => {
  await loadRoomDetail()
  if (isAdmin.value) {
    activeSettingsTab.value = 'members'
  }
})
</script>
