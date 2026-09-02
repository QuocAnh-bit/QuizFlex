<template>
  <section class="max-w-5xl mx-auto py-4 space-y-6">
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
      @retry="loadRoomDetail"
    >
      <template #actions>
        <router-link class="btn-secondary text-xs" to="/homework-rooms">Quay lại danh sách</router-link>
      </template>
    </AppErrorState>

    <template v-else-if="room">
      <!-- Top Action Bar -->
      <div class="flex flex-wrap items-center justify-between gap-3">
        <router-link class="btn-secondary text-xs" to="/homework-rooms">← Quay lại danh sách</router-link>
        <div class="flex items-center gap-2">
          <button
            v-if="canManageRoom"
            @click="exportGradebookExcel"
            type="button"
            :disabled="isExportingGradebook"
            class="btn-primary text-xs flex items-center gap-1.5 px-3.5 py-1.5"
          >
            <span>📊 {{ isExportingGradebook ? 'Đang xuất...' : 'Xuất bảng điểm Excel' }}</span>
          </button>
          <button 
            v-else-if="room" 
            class="btn-danger text-xs px-3 py-1.5"
            type="button" 
            :disabled="isLeavingRoom" 
            @click="leaveRoom"
          >
            {{ isLeavingRoom ? 'Đang rời phòng...' : 'Rời phòng' }}
          </button>
        </div>
      </div>

      <!-- Ban Banners -->
      <div v-if="isBanned && isHost" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs font-bold text-amber-800 flex items-center gap-2">
        <span>⚠️</span>
        <span>Phòng này đã bị quản trị viên tạm khóa. Bạn chỉ có thể xem thông tin phòng và không thể thực hiện thao tác quản lý.</span>
      </div>
      <div v-if="isBanned && !isHost && !isAdmin" class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-700 flex items-center gap-2">
        <span>🚫</span>
        <span>Phòng đã bị quản trị viên tạm khóa và hiện không thể sử dụng.</span>
      </div>

      <!-- Room Header Card -->
      <div class="card p-6 sm:p-8 space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
          <div class="space-y-1">
            <span class="rounded-full bg-blue-50 border border-blue-200 px-3 py-0.5 text-xs font-bold text-blue-700">
              Phòng bài tập
            </span>
            <h1 class="mt-2 text-2xl sm:text-3xl font-black text-slate-900">{{ room.name || 'Phòng bài tập' }}</h1>
            <p class="text-xs text-slate-600 max-w-2xl leading-relaxed">{{ room.description || 'Chưa có mô tả phòng.' }}</p>
          </div>
          <div class="flex flex-col items-start sm:items-end gap-2">
            <span class="font-mono text-sm font-bold text-blue-700 bg-blue-50 px-3 py-1 rounded-lg border border-blue-200">
              Mã phòng: {{ room.code || 'NO CODE' }}
            </span>
            <StatusBadge :value="room.status || 'active'" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 pt-4 border-t border-slate-100 text-xs">
          <div class="rounded-xl bg-slate-50 p-3">
            <span class="text-slate-400 font-bold uppercase text-[10px] block">Chủ phòng</span>
            <b class="text-slate-900 font-bold block mt-0.5">{{ room.host?.name || '-' }}</b>
          </div>
          <div class="rounded-xl bg-slate-50 p-3">
            <span class="text-slate-400 font-bold uppercase text-[10px] block">Thành viên</span>
            <b class="text-slate-900 font-bold block mt-0.5">{{ memberCount }} người</b>
          </div>
          <div class="rounded-xl bg-slate-50 p-3">
            <span class="text-slate-400 font-bold uppercase text-[10px] block">Bài được giao</span>
            <b class="text-slate-900 font-bold block mt-0.5">{{ room.assignments_count ?? assignments.length }} bài</b>
          </div>
          <div class="rounded-xl bg-slate-50 p-3">
            <span class="text-slate-400 font-bold uppercase text-[10px] block">Ngày tạo</span>
            <b class="text-slate-900 font-bold block mt-0.5">{{ formatDate(room.created_at) }}</b>
          </div>
        </div>
      </div>

      <!-- Quản lý phòng (Chỉ cho Host/Admin) -->
      <article v-if="canManageRoom" class="card p-6 sm:p-8 space-y-6">
        <!-- Settings Tabs -->
        <div class="flex items-center gap-4 border-b border-slate-100 pb-3 overflow-x-auto text-xs font-bold">
          <button
            v-if="isHost"
            type="button"
            class="pb-1 transition border-b-2"
            :class="activeSettingsTab === 'general' ? 'border-[#7C3AED] text-[#7C3AED]' : 'border-transparent text-slate-500 hover:text-slate-900'"
            @click="activeSettingsTab = 'general'"
          >
            Cấu hình phòng
          </button>

          <button
            v-if="isHost && (settingsForm.join_policy === 'email_whitelist' || room.join_policy === 'email_whitelist')"
            type="button"
            class="pb-1 transition border-b-2 flex items-center gap-1.5"
            :class="activeSettingsTab === 'whitelist' ? 'border-[#7C3AED] text-[#7C3AED]' : 'border-transparent text-slate-500 hover:text-slate-900'"
            @click="activeSettingsTab = 'whitelist'"
          >
            Email whitelist
            <span class="rounded bg-purple-50 text-[#7C3AED] px-1.5 py-0.2 text-[10px]">
              {{ allowedMembers.length }}
            </span>
          </button>

          <button
            type="button"
            class="pb-1 transition border-b-2 flex items-center gap-1.5"
            :class="activeSettingsTab === 'members' ? 'border-[#7C3AED] text-[#7C3AED]' : 'border-transparent text-slate-500 hover:text-slate-900'"
            @click="activeSettingsTab = 'members'"
          >
            Thành viên
            <span class="rounded bg-slate-100 text-slate-600 px-1.5 py-0.2 text-[10px]">
              {{ memberTab === 'active' ? filteredMembers.length : pendingMembers.length }}
            </span>
          </button>
        </div>

        <!-- Tab 1: Cấu hình chung -->
        <div v-if="activeSettingsTab === 'general' && isHost" class="space-y-4">
          <form class="space-y-4" @submit.prevent="updateRoomSettings">
            <div class="grid gap-4 sm:grid-cols-2">
              <label class="grid gap-1 text-xs font-bold text-slate-700">
                Tên phòng học
                <input v-model.trim="settingsForm.name" class="field text-xs" maxlength="255" placeholder="Tên phòng" :disabled="isBanned" required />
              </label>

              <label class="grid gap-1 text-xs font-bold text-slate-700">
                Chính sách tham gia
                <select v-model="settingsForm.join_policy" class="field text-xs" :disabled="isBanned">
                  <option value="open">Công khai (Bất kỳ ai có mã đều vào được)</option>
                  <option value="email_whitelist">Giới hạn (Chỉ email trong Whitelist)</option>
                </select>
              </label>
            </div>

            <label class="grid gap-1 text-xs font-bold text-slate-700">
              Mô tả phòng học
              <textarea v-model.trim="settingsForm.description" class="field text-xs min-h-20 resize-y" maxlength="1000" placeholder="Mô tả ngắn của phòng" :disabled="isBanned"></textarea>
            </label>

            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
              <button
                type="button"
                class="btn-danger text-xs px-3.5 py-1.5"
                @click="showDissolveDialog = true"
              >
                🚨 Giải tán phòng
              </button>
              <button v-if="!isBanned" class="btn-primary text-xs px-4 py-2" type="submit" :disabled="isUpdatingSettings">
                {{ isUpdatingSettings ? 'Đang lưu...' : 'Lưu cấu hình' }}
              </button>
            </div>
          </form>

          <div v-if="settingsSuccessMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-bold text-emerald-700">
            {{ settingsSuccessMessage }}
          </div>
          <div v-if="settingsErrorMessage" class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700">
            {{ settingsErrorMessage }}
          </div>
        </div>

        <!-- Tab 2: Email Whitelist -->
        <div v-if="activeSettingsTab === 'whitelist' && isHost && (settingsForm.join_policy === 'email_whitelist' || room.join_policy === 'email_whitelist')" class="space-y-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h3 class="text-sm font-bold text-slate-900">Danh sách email được phép tham gia</h3>
              <p class="text-xs text-slate-500">Chỉ học viên sử dụng các email này mới có thể tham gia vào phòng.</p>
            </div>
            <div class="flex gap-2 text-xs font-bold">
              <span class="rounded bg-emerald-50 text-emerald-700 px-2.5 py-1">Đã tham gia: {{ allowedJoinedCount }}</span>
              <span class="rounded bg-slate-100 text-slate-600 px-2.5 py-1">Chưa tham gia: {{ allowedPendingCount }}</span>
            </div>
          </div>

          <!-- Toolbar -->
          <div class="grid gap-3 sm:grid-cols-[1fr_auto_auto] items-center bg-slate-50 p-3 rounded-xl border border-slate-100">
            <input
              v-model="whitelistSearchQuery"
              type="text"
              class="field text-xs bg-white"
              placeholder="Tìm kiếm email trong whitelist..."
            />

            <label v-if="!isBanned" class="btn-secondary text-xs flex items-center justify-center gap-1.5 cursor-pointer py-2">
              <span>📥 Import Excel / CSV</span>
              <input type="file" accept=".xlsx, .xls, .csv" class="hidden" @change="handleImportFile" />
            </label>

            <button
              v-if="!isBanned"
              type="button"
              class="btn-primary text-xs py-2"
              @click="isShowingManualInput = !isShowingManualInput"
            >
              {{ isShowingManualInput || allowedEmailText ? 'Đóng ô nhập' : '+ Thêm thủ công' }}
            </button>
          </div>

          <!-- Manual input -->
          <div v-if="isShowingManualInput || allowedEmailText" class="p-4 rounded-xl border border-slate-200 bg-slate-50 space-y-3">
            <span class="text-xs font-bold text-slate-700 block">Nhập danh sách email (phân cách bằng dấu phẩy hoặc xuống dòng):</span>
            <form class="grid gap-3 sm:grid-cols-[1fr_auto]" @submit.prevent="addAllowedMembers">
              <textarea
                v-model="allowedEmailText"
                class="field text-xs min-h-20 bg-white"
                placeholder="student1@gmail.com&#10;student2@gmail.com, student3@gmail.com"
              ></textarea>
              <button class="btn-primary text-xs self-start px-4 py-2" type="submit" :disabled="isSavingAllowedMembers">
                {{ isSavingAllowedMembers ? 'Đang thêm...' : 'Lưu email' }}
              </button>
            </form>
          </div>

          <div v-if="allowedMembersMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-bold text-emerald-700">
            {{ allowedMembersMessage }}
          </div>
          <div v-if="allowedMembersError" class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700">
            {{ allowedMembersError }}
          </div>

          <!-- Whitelist Items List -->
          <div v-if="filteredAllowedMembers.length" class="border border-slate-200 rounded-xl overflow-hidden">
            <div v-if="!isBanned" class="flex items-center justify-between bg-slate-50 px-4 py-2.5 border-b border-slate-200 text-xs">
              <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                <input
                  type="checkbox"
                  class="rounded text-[#7C3AED]"
                  :checked="isAllAllowedSelected"
                  @change="toggleSelectAllAllowed"
                />
                <span>Chọn tất cả ({{ filteredAllowedMembers.length }})</span>
              </label>
              <div class="flex gap-2">
                <button
                  v-if="selectedAllowedIds.length"
                  type="button"
                  class="text-red-600 font-bold hover:underline"
                  @click="removeSelectedAllowedMembers"
                >
                  Xóa mục đã chọn ({{ selectedAllowedIds.length }})
                </button>
                <button
                  type="button"
                  class="text-red-600 font-bold hover:underline"
                  @click="clearAllAllowedMembers"
                >
                  Xóa tất cả
                </button>
              </div>
            </div>

            <div class="max-h-72 overflow-y-auto divide-y divide-slate-100">
              <div
                v-for="allowedMember in filteredAllowedMembers"
                :key="allowedMember.id"
                class="flex items-center justify-between p-3 text-xs hover:bg-slate-50"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <input
                    v-if="!isBanned"
                    type="checkbox"
                    class="rounded text-[#7C3AED]"
                    :value="allowedMember.id"
                    v-model="selectedAllowedIds"
                  />
                  <div class="truncate">
                    <span class="font-bold text-slate-900 block truncate">{{ allowedMember.email }}</span>
                    <span class="text-[10px] text-slate-400">
                      {{ allowedMember.joined_at ? `Đã tham gia: ${formatDateTime(allowedMember.joined_at)}` : 'Chưa tham gia' }}
                    </span>
                  </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <span
                    class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                    :class="allowedMember.joined_at ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                  >
                    {{ allowedMember.joined_at ? 'Đã vào phòng' : 'Chưa vào' }}
                  </span>
                  <button
                    v-if="!isBanned"
                    class="text-red-500 hover:text-red-700 font-bold px-2 py-1"
                    type="button"
                    @click="removeAllowedMember(allowedMember.id)"
                  >
                    ✕
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="rounded-xl border border-slate-100 bg-slate-50 p-6 text-center text-xs text-slate-500">
            {{ whitelistSearchQuery ? 'Không tìm thấy email nào khớp với từ khóa tìm kiếm.' : 'Chưa có email nào trong danh sách Whitelist.' }}
          </div>
        </div>

        <!-- Tab 3: Thành viên (Dạng Bảng hiện đại) -->
        <div v-if="activeSettingsTab === 'members'" class="space-y-4">
          <!-- Toolbar & Tabs -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/70 p-3 rounded-2xl border border-slate-100">
            <div class="flex items-center gap-2">
              <button
                type="button"
                class="rounded-xl px-3.5 py-1.5 text-xs font-bold transition inline-flex items-center gap-1.5 cursor-pointer"
                :class="memberTab === 'active' ? 'bg-[#7C3AED] text-white shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100'"
                @click="setMemberTab('active')"
              >
                <Users class="h-3.5 w-3.5" />
                <span>Chính thức</span>
                <span class="rounded-full px-1.5 py-0.2 text-[10px]" :class="memberTab === 'active' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'">
                  {{ filteredMembers.length }}
                </span>
              </button>
              <button
                type="button"
                class="rounded-xl px-3.5 py-1.5 text-xs font-bold transition inline-flex items-center gap-1.5 cursor-pointer"
                :class="memberTab === 'pending' ? 'bg-amber-500 text-white shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100'"
                @click="setMemberTab('pending')"
              >
                <Clock class="h-3.5 w-3.5" />
                <span>Chờ duyệt</span>
                <span class="rounded-full px-1.5 py-0.2 text-[10px]" :class="memberTab === 'pending' ? 'bg-white/25 text-white font-black' : 'bg-amber-100 text-amber-800 font-bold'">
                  {{ pendingMembers.length }}
                </span>
              </button>
            </div>

            <div class="flex items-center gap-2">
              <button
                v-if="memberTab === 'pending' && isHost && !isBanned && filteredPendingMembers.length > 0"
                type="button"
                class="btn-success text-xs py-1.5 px-3 inline-flex items-center gap-1.5 font-bold cursor-pointer shadow-2xs whitespace-nowrap"
                :disabled="isApprovingAll"
                @click="approveAllPendingMembers"
              >
                <Check class="h-3.5 w-3.5 stroke-[3]" />
                <span>{{ isApprovingAll ? 'Đang duyệt tất cả...' : `Duyệt tất cả (${filteredPendingMembers.length})` }}</span>
              </button>

              <div class="relative w-full sm:w-64">
                <input
                  v-model="memberSearchQuery"
                  type="text"
                  class="field text-xs w-full bg-white pr-8"
                  placeholder="Tìm kiếm học viên / email..."
                />
                <Search class="h-3.5 w-3.5 text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
              </div>
            </div>
          </div>

          <!-- 1. Danh sách Chờ duyệt (Dạng Bảng) -->
          <div v-if="memberTab === 'pending'">
            <div v-if="filteredPendingMembers.length" class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-2xs">
              <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700">
                  <thead class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                      <th scope="col" class="py-3 px-4 font-bold">Học viên</th>
                      <th scope="col" class="py-3 px-4 font-bold hidden sm:table-cell">Email</th>
                      <th scope="col" class="py-3 px-4 font-bold hidden md:table-cell">Thời gian xin vào</th>
                      <th scope="col" class="py-3 px-4 font-bold text-center">Trạng thái</th>
                      <th scope="col" class="py-3 px-4 font-bold text-right">Thao tác</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    <tr
                      v-for="member in filteredPendingMembers"
                      :key="member.id || `${member.room_id}-${member.user_id}`"
                      class="hover:bg-amber-50/20 transition group"
                    >
                      <!-- Cột 1: Compact User Avatar + Họ Tên -->
                      <td class="py-3 px-4">
                        <div class="flex items-center gap-3">
                          <div class="h-8 w-8 shrink-0 rounded-full bg-amber-100 text-amber-800 font-bold text-xs flex items-center justify-center border border-amber-200 shadow-2xs">
                            {{ getInitials(member.user?.name || `User #${member.user_id}`) }}
                          </div>
                          <div class="min-w-0">
                            <p class="font-bold text-slate-900 truncate">{{ member.user?.name || `User #${member.user_id}` }}</p>
                            <p class="text-[11px] text-slate-400 sm:hidden truncate font-mono">{{ member.user?.email || 'Chưa có email' }}</p>
                          </div>
                        </div>
                      </td>
                      <!-- Cột 2: Email -->
                      <td class="py-3 px-4 text-slate-600 font-mono text-[11px] hidden sm:table-cell">
                        {{ member.user?.email || 'Chưa có email' }}
                      </td>
                      <!-- Cột 3: Thời gian xin vào -->
                      <td class="py-3 px-4 text-slate-500 text-[11px] hidden md:table-cell whitespace-nowrap">
                        <span class="inline-flex items-center gap-1.5">
                          <Clock class="h-3.5 w-3.5 text-slate-400" />
                          {{ formatDateTime(member.joined_at) }}
                        </span>
                      </td>
                      <!-- Cột 4: Trạng thái -->
                      <td class="py-3 px-4 text-center whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                          Chờ duyệt
                        </span>
                      </td>
                      <!-- Cột 5: Nút Duyệt / Từ chối -->
                      <td class="py-3 px-4 text-right whitespace-nowrap">
                        <div v-if="isHost && !isBanned" class="inline-flex items-center gap-1.5">
                          <button
                            type="button"
                            class="btn-success text-xs px-3 py-1.5 inline-flex items-center gap-1 font-bold cursor-pointer transition shadow-2xs"
                            :disabled="isApproving === member.id"
                            @click="approveMemberRequest(member)"
                          >
                            <Check class="h-3.5 w-3.5 stroke-[2.5]" />
                            <span>{{ isApproving === member.id ? 'Đang duyệt...' : 'Duyệt' }}</span>
                          </button>
                          <button
                            type="button"
                            class="btn-danger text-xs px-3 py-1.5 inline-flex items-center gap-1 font-bold cursor-pointer transition"
                            :disabled="isRejecting === member.id"
                            @click="rejectMemberRequest(member)"
                          >
                            <X class="h-3.5 w-3.5 stroke-[2.5]" />
                            <span>{{ isRejecting === member.id ? '...' : 'Từ chối' }}</span>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-else class="rounded-2xl border border-slate-100 bg-slate-50 p-8 text-center text-xs text-slate-500 space-y-1">
              <p class="font-bold text-slate-700 text-sm">Không có yêu cầu chờ duyệt</p>
              <p class="text-[11px] text-slate-400">
                {{ memberSearchQuery ? 'Không tìm thấy yêu cầu nào khớp với từ khóa.' : 'Hiện tại tất cả học sinh đã được phê duyệt tham gia phòng.' }}
              </p>
            </div>
          </div>

          <!-- 2. Danh sách Thành viên chính thức (Dạng Bảng) -->
          <div v-else-if="memberTab === 'active'">
            <div v-if="filteredMembers.length" class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-2xs">
              <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700">
                  <thead class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                      <th scope="col" class="py-3 px-4 font-bold">Học viên</th>
                      <th scope="col" class="py-3 px-4 font-bold hidden sm:table-cell">Email</th>
                      <th scope="col" class="py-3 px-4 font-bold hidden md:table-cell">Ngày tham gia</th>
                      <th scope="col" class="py-3 px-4 font-bold text-center">Vai trò</th>
                      <th scope="col" class="py-3 px-4 font-bold text-center">Trạng thái</th>
                      <th scope="col" class="py-3 px-4 font-bold text-right">Thao tác</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    <tr
                      v-for="member in filteredMembers"
                      :key="member.id || `${member.room_id}-${member.user_id}`"
                      class="hover:bg-purple-50/20 transition group"
                    >
                      <!-- Cột 1: Compact User Avatar + Họ Tên -->
                      <td class="py-3 px-4">
                        <div class="flex items-center gap-3">
                          <div class="h-8 w-8 shrink-0 rounded-full bg-purple-100 text-[#7C3AED] font-bold text-xs flex items-center justify-center border border-purple-200 shadow-2xs">
                            {{ getInitials(member.user?.name || `User #${member.user_id}`) }}
                          </div>
                          <div class="min-w-0">
                            <p class="font-bold text-slate-900 truncate">{{ member.user?.name || `User #${member.user_id}` }}</p>
                            <p class="text-[11px] text-slate-400 sm:hidden truncate font-mono">{{ member.user?.email || 'Chưa có email' }}</p>
                          </div>
                        </div>
                      </td>
                      <!-- Cột 2: Email -->
                      <td class="py-3 px-4 text-slate-600 font-mono text-[11px] hidden sm:table-cell">
                        {{ member.user?.email || 'Chưa có email' }}
                      </td>
                      <!-- Cột 3: Ngày tham gia -->
                      <td class="py-3 px-4 text-slate-500 text-[11px] hidden md:table-cell whitespace-nowrap">
                        {{ formatDate(member.joined_at || member.created_at) }}
                      </td>
                      <!-- Cột 4: Vai trò -->
                      <td class="py-3 px-4 text-center whitespace-nowrap">
                        <StatusBadge :value="member.role || 'member'" />
                      </td>
                      <!-- Cột 5: Trạng thái -->
                      <td class="py-3 px-4 text-center whitespace-nowrap">
                        <StatusBadge :value="member.status || 'active'" />
                      </td>
                      <!-- Cột 6: Thao tác -->
                      <td class="py-3 px-4 text-right whitespace-nowrap">
                        <div class="inline-flex items-center gap-1.5">
                          <button
                            class="btn-secondary text-xs px-2.5 py-1.5 inline-flex items-center gap-1 cursor-pointer"
                            type="button"
                            @click="openMemberDetail(member)"
                          >
                            <Eye class="h-3.5 w-3.5" />
                            <span>Chi tiết</span>
                          </button>
                          <button
                            v-if="isHost && !isBanned && member.role !== 'host'"
                            class="btn-danger text-xs px-2.5 py-1.5 inline-flex items-center gap-1 cursor-pointer"
                            type="button"
                            @click="removeMember(member)"
                          >
                            <Trash2 class="h-3.5 w-3.5" />
                            <span>Xóa</span>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-else class="rounded-2xl border border-slate-100 bg-slate-50 p-8 text-center text-xs text-slate-500 space-y-1">
              <p class="font-bold text-slate-700 text-sm">Chưa có thành viên chính thức</p>
              <p class="text-[11px] text-slate-400">
                {{ memberSearchQuery ? 'Không tìm thấy thành viên nào khớp.' : 'Chưa có thành viên chính thức nào trong phòng học này.' }}
              </p>
            </div>
          </div>
        </div>
      </article>

      <!-- Assignments Section -->
      <article class="card p-6 sm:p-8 space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
          <div>
            <h2 class="text-base font-bold text-slate-900">Danh sách bài tập được giao</h2>
            <p class="text-xs text-slate-500">Hoàn thành bài trước hạn nộp để nhận đánh giá từ giáo viên.</p>
          </div>
          <router-link v-if="isHost && !isBanned" class="btn-primary text-xs px-3.5 py-1.5" :to="`/homework-rooms/${roomId}/assignments/create`">
            + Giao quiz mới
          </router-link>
          <button
            v-else-if="currentMember"
            @click="openMemberDetail(currentMember)"
            type="button"
            class="btn-secondary text-xs px-3.5 py-1.5"
          >
            📊 Xem tiến độ cá nhân
          </button>
        </div>

        <div v-if="sortedAssignments.length" class="space-y-4">
          <article v-for="assignment in sortedAssignments" :key="assignment.id" class="rounded-xl border border-slate-200 p-5 space-y-4 hover:border-slate-300 transition">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <StatusBadge :value="assignment.status || 'published'" />
                  <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">{{ assignment.show_result_mode || 'immediately' }}</span>
                </div>
                <h3 class="text-sm font-bold text-slate-900 pt-1">{{ assignment.title || assignment.quiz?.title || 'Bài được giao' }}</h3>
                <p class="text-xs text-slate-500">{{ assignment.description || 'Chưa có mô tả.' }}</p>
              </div>
              <router-link v-if="canManageRoom" class="btn-secondary text-xs px-3.5 py-1.5 shrink-0" :to="`/homework-rooms/${roomId}/assignments/${assignment.id}/attempts`">
                Xem bài nộp →
              </router-link>
              <div v-else-if="isBanned" class="text-xs font-bold text-red-500">Phòng tạm khóa</div>
              <router-link v-else class="btn-primary text-xs px-4 py-1.5 shrink-0" :to="`/homework-rooms/${roomId}/assignments/${assignment.id}/take`">
                Làm bài ngay →
              </router-link>
            </div>

            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 pt-3 border-t border-slate-100 text-xs">
              <div class="rounded-lg bg-slate-50 p-2">
                <span class="text-slate-400 font-bold uppercase text-[10px] block">Bắt đầu</span>
                <b class="text-slate-800 font-bold block mt-0.5">{{ formatDateTime(assignment.starts_at) }}</b>
              </div>
              <div class="rounded-lg bg-slate-50 p-2">
                <span class="text-slate-400 font-bold uppercase text-[10px] block">Deadline</span>
                <b class="text-slate-800 font-bold block mt-0.5">{{ formatDateTime(assignment.deadline_at) }}</b>
              </div>
              <div class="rounded-lg bg-slate-50 p-2">
                <span class="text-slate-400 font-bold uppercase text-[10px] block">Thời lượng</span>
                <b class="text-slate-800 font-bold block mt-0.5">{{ assignment.duration_minutes ? `${assignment.duration_minutes} phút` : 'Không giới hạn' }}</b>
              </div>
              <div class="rounded-lg bg-slate-50 p-2">
                <span class="text-slate-400 font-bold uppercase text-[10px] block">Số lần làm</span>
                <b class="text-slate-800 font-bold block mt-0.5">{{ assignment.my_attempts_count ?? 0 }}/{{ assignment.max_attempts ?? 1 }}</b>
              </div>
            </div>
          </article>
        </div>

        <div v-else class="rounded-xl border border-slate-100 bg-slate-50 p-8 text-center text-slate-500 text-xs">
          <span class="text-2xl block mb-1">📚</span>
          <b class="text-slate-800 block">Chưa có bài nào được giao</b>
          <p class="mt-0.5">Khi chủ phòng giao quiz, các bài tập sẽ hiển thị tại đây.</p>
        </div>
      </article>

      <!-- Modal Chi tiết thành viên -->
      <div v-if="selectedMember" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
        <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Chi tiết thành viên</h3>
            <button @click="closeMemberDetail" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
          </div>

          <div class="space-y-4 text-xs">
            <div class="flex items-center gap-3">
              <div class="h-10 w-10 rounded-full bg-purple-50 flex items-center justify-center font-bold text-[#7C3AED] text-sm">
                {{ selectedMember.user?.name ? selectedMember.user.name.charAt(0).toUpperCase() : 'M' }}
              </div>
              <div class="min-w-0">
                <h4 class="font-bold text-slate-900 truncate">{{ selectedMember.user?.name || `User #${selectedMember.user_id}` }}</h4>
                <p class="text-slate-400 truncate">{{ selectedMember.user?.email || '-' }}</p>
              </div>
            </div>

            <!-- Stats Matrix -->
            <div class="grid grid-cols-2 gap-2 text-center">
              <div class="rounded-lg bg-slate-50 p-2.5">
                <span class="text-slate-400 uppercase font-bold text-[10px] block">Ngày tham gia</span>
                <b class="text-slate-800 font-bold block mt-0.5">{{ formatDateTime(selectedMember.joined_at) }}</b>
              </div>
              <div class="rounded-lg bg-slate-50 p-2.5">
                <span class="text-slate-400 uppercase font-bold text-[10px] block">Tỷ lệ hoàn thành</span>
                <b class="text-slate-800 font-bold block mt-0.5">{{ selectedMember.completion_rate ?? 0 }}%</b>
              </div>
              <div class="rounded-lg bg-slate-50 p-2.5 col-span-2">
                <span class="text-slate-400 uppercase font-bold text-[10px] block">Điểm trung bình</span>
                <b class="text-[#7C3AED] text-base font-black block mt-0.5">{{ selectedMember.average_score ?? 0 }}/10</b>
              </div>
            </div>

            <!-- Submission Evaluations List -->
            <div class="space-y-2 pt-2 border-t border-slate-100">
              <span class="text-xs font-bold text-slate-700 block">Lịch sử nhận xét bài nộp</span>
              <div v-if="evaluationData?.submission_evaluations && evaluationData.submission_evaluations.length" class="space-y-2 max-h-40 overflow-y-auto">
                <div v-for="subEval in evaluationData.submission_evaluations" :key="subEval.id" class="rounded-lg bg-slate-50 p-2.5 text-xs space-y-1">
                  <div class="flex justify-between font-bold">
                    <span class="text-slate-900">{{ subEval.assignment_name }}</span>
                    <span class="text-[#7C3AED]">{{ subEval.score }} điểm</span>
                  </div>
                  <p class="text-slate-600 italic">"{{ subEval.comment || 'Không có nhận xét.' }}"</p>
                </div>
              </div>
              <div v-else class="text-slate-400 text-[11px]">Chưa có bài nộp nào.</div>
            </div>

            <!-- Overall comment -->
            <div v-if="isHost && !isBanned" class="pt-2 border-t border-slate-100">
              <span class="text-xs font-bold text-slate-700 block mb-1">Nhận xét chung của chủ phòng</span>
              <textarea 
                v-model="evaluationForm.comment"
                class="field text-xs min-h-20 resize-y"
                maxlength="1000"
                placeholder="Nhập nhận xét tổng quan cho thành viên..."
              ></textarea>
            </div>
            <div v-else-if="evaluationData?.comment" class="rounded-lg bg-slate-50 p-3 italic text-slate-700">
              "{{ evaluationData.comment }}"
            </div>
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
            <button @click="closeMemberDetail" class="btn-secondary text-xs" type="button">Đóng</button>
            <button 
              v-if="isHost && !isLoadingEvaluation && !isBanned" 
              @click="saveEvaluation" 
              class="btn-primary text-xs px-4 py-2" 
              type="button"
              :disabled="isSavingEvaluation"
            >
              {{ isSavingEvaluation ? 'Đang lưu...' : 'Lưu đánh giá' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Dialog Giải tán phòng -->
      <Teleport to="body">
        <div v-if="showDissolveDialog" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showDissolveDialog = false">
          <div class="w-full max-w-md rounded-2xl border border-red-200 bg-white p-6 shadow-xl space-y-4">
            <div class="flex items-center gap-2 text-red-600">
              <span class="text-xl">🚨</span>
              <h3 class="text-base font-bold text-slate-900">Giải tán phòng học?</h3>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-xs text-slate-600 space-y-1">
              <p>Hành động này sẽ đóng phòng học đối với tất cả thành viên. Quản trị viên vẫn có thể khôi phục lại khi cần.</p>
            </div>
            <div v-if="dissolveErrorMessage" class="rounded-lg border border-red-200 bg-red-50 p-2.5 text-xs font-bold text-red-700">
              {{ dissolveErrorMessage }}
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
              <button class="btn-secondary text-xs" :disabled="isDissolvingRoom" @click="showDissolveDialog = false">Hủy</button>
              <button class="btn-danger text-xs" :disabled="isDissolvingRoom" @click="confirmDissolve">
                {{ isDissolvingRoom ? 'Đang giải tán...' : 'Xác nhận giải tán' }}
              </button>
            </div>
          </div>
        </div>
      </Teleport>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch, inject } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLoadingState from '@/components/common/AppLoadingState.vue'
import AppErrorState from '@/components/common/AppErrorState.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { currentUserStorage, homeworkApi } from '@/services/api'
import { useAppLoading } from '@/composables/useAppLoading'

const { beginTask, endTask } = useAppLoading()
import { Users, Clock, Check, X, Search, Eye, Trash2 } from 'lucide-vue-next'

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

const route = useRoute()
const router = useRouter()
const roomId = computed(() => route.params.roomId)
const currentUser = currentUserStorage.get()

const MAX_EVALUATION_COMMENT_LENGTH = 1000
const MAX_IMPORT_FILE_SIZE = 10 * 1024 * 1024
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

const selectedMember = ref(null)
const isLoadingEvaluation = ref(false)
const isSavingEvaluation = ref(false)
const evaluationData = ref(null)
const evaluationForm = ref({ comment: '' })

const settingsForm = ref({
  name: '',
  description: '',
  join_policy: 'open',
})
const isUpdatingSettings = ref(false)
const settingsSuccessMessage = ref('')
const settingsErrorMessage = ref('')

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
    if (newQuery.tab) activeSettingsTab.value = newQuery.tab
    if (newQuery.status) memberTab.value = newQuery.status
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
    if (data) evaluationForm.value.comment = data.comment || ''
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

  const trimmedComment = evaluationForm.value.comment.trim()
  if (trimmedComment.length > MAX_EVALUATION_COMMENT_LENGTH) {
    alert(`Nhận xét tối đa ${MAX_EVALUATION_COMMENT_LENGTH} ký tự.`)
    return
  }

  isSavingEvaluation.value = true
  try {
    const data = await homeworkApi.saveMemberEvaluation(roomId.value, selectedMember.value.user_id, {
      comment: trimmedComment,
    })
    evaluationData.value = data
    if (showToast) showToast('Đã lưu đánh giá thành công.', 'success')
  } catch (error) {
    if (showToast) showToast(`Không lưu được đánh giá: ${error.message}`, 'error')
  } finally {
    isSavingEvaluation.value = false
  }
}

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
    const nameA = (a.user?.name || '').trim().toLowerCase()
    const nameB = (b.user?.name || '').trim().toLowerCase()
    return nameA.localeCompare(nameB, 'vi', { sensitivity: 'base' })
  })
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

const sortedAssignments = computed(() => {
  return [...assignments.value].sort((a, b) => {
    const dateA = new Date(a.created_at || a.starts_at || 0)
    const dateB = new Date(b.created_at || b.starts_at || 0)
    return dateB - dateA
  })
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
    errorMessage.value = `Không tải được chi tiết phòng: ${error.message}`
  } finally {
    isLoading.value = false
  }
}

const validateRoomSettings = () => {
  const name = settingsForm.value.name.trim()
  if (!name) return 'Tên phòng không được để trống.'
  if (name.length > 255) return 'Tên phòng tối đa 255 ký tự.'

  const description = (settingsForm.value.description || '').trim()
  if (description.length > 1000) return 'Mô tả tối đa 1000 ký tự.'

  return ''
}

const updateRoomSettings = async () => {
  settingsErrorMessage.value = ''
  settingsSuccessMessage.value = ''

  const validationError = validateRoomSettings()
  if (validationError) {
    settingsErrorMessage.value = validationError
    return
  }

  isUpdatingSettings.value = true
  try {
    const updatedRoom = await homeworkApi.updateHomeworkRoom(roomId.value, {
      name: settingsForm.value.name.trim(),
      description: settingsForm.value.description?.trim() || null,
      join_policy: settingsForm.value.join_policy,
    })
    room.value = updatedRoom
    settingsSuccessMessage.value = 'Cập nhật cấu hình phòng thành công.'

    if (updatedRoom.join_policy === 'email_whitelist' && !allowedMembers.value.length) {
      allowedMembers.value = await homeworkApi.getAllowedMembers(roomId.value)
    }
  } catch (error) {
    settingsErrorMessage.value = `Lỗi cập nhật: ${error.message}`
  } finally {
    isUpdatingSettings.value = false
  }
}

const addAllowedMembers = async () => {
  allowedMembersError.value = ''
  allowedMembersMessage.value = ''

  const rawText = allowedEmailText.value.trim()
  if (!rawText) {
    allowedMembersError.value = 'Bạn cần nhập ít nhất một email.'
    return
  }

  const emails = parseAllowedEmails()
  if (!emails.length) {
    allowedMembersError.value = 'Không tìm thấy email hợp lệ nào. Vui lòng kiểm tra lại.'
    return
  }

  if (emails.length > 500) {
    allowedMembersError.value = 'Chỉ được thêm tối đa 500 email trong một lần.'
    return
  }

  isSavingAllowedMembers.value = true
  try {
    await homeworkApi.addAllowedMembers(roomId.value, emails)
    allowedMembers.value = await homeworkApi.getAllowedMembers(roomId.value)
    allowedEmailText.value = ''
    allowedMembersMessage.value = 'Đã cập nhật danh sách email whitelist thành công.'
  } catch (error) {
    allowedMembersError.value = `Không thêm được email: ${error.message}`
  } finally {
    isSavingAllowedMembers.value = false
  }
}

const handleImportFile = async (e) => {
  const file = e.target.files?.[0]
  if (!file) return

  if (file.size > MAX_IMPORT_FILE_SIZE) {
    alert('File vượt quá dung lượng tối đa 10MB.')
    e.target.value = ''
    return
  }

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
        const firstSheetName = workbook.SheetNames[0]
        if (!firstSheetName) {
          if (showToast) showToast('File Excel không có sheet nào.', 'warning')
          return
        }
        const worksheet = workbook.Sheets[firstSheetName]
        const rows = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' })
        
        let emailColumnIndex = -1
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
        
        if (emailColumnIndex !== -1) {
          const extracted = []
          for (let i = 0; i < rows.length; i++) {
            const row = rows[i]
            if (Array.isArray(row) && row[emailColumnIndex]) {
              const cellValue = String(row[emailColumnIndex]).trim()
              const match = cellValue.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/)
              if (match) extracted.push(match[0])
            }
          }
          extractEmailsFromList(extracted)
        } else {
          let textContent = ''
          for (const key in worksheet) {
            if (Object.prototype.hasOwnProperty.call(worksheet, key) && key[0] !== '!') {
              const cell = worksheet[key]
              if (cell && cell.v !== undefined) textContent += String(cell.v) + '\n'
            }
          }
          extractEmailsFromText(textContent)
        }
      }
      reader.readAsArrayBuffer(file)
    } catch (err) {
      if (showToast) showToast(`Không đọc được file Excel: ${err.message}`, 'error')
    }
  }
  e.target.value = ''
}

const loadXlsxLibrary = () => {
  return new Promise((resolve, reject) => {
    if (window.XLSX) return resolve(window.XLSX)
    const script = document.createElement('script')
    script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js'
    script.onload = () => {
      if (window.XLSX) resolve(window.XLSX)
      else reject(new Error('Không tải được thư viện Excel.'))
    }
    script.onerror = () => reject(new Error('Lỗi kết nối khi tải thư viện Excel.'))
    document.head.appendChild(script)
  })
}

const exportGradebookExcel = async () => {
  if (isExportingGradebook.value) return
  isExportingGradebook.value = true

  try {
    const response = await homeworkApi.fetchRoomGradebook(roomId.value)
    const XLSX = await loadXlsxLibrary()
    const { assignments: gradeAssignments, students: gradeStudents } = response
    
    const headers = ['Họ tên', 'Email']
    gradeAssignments.forEach(assignment => {
      headers.push(assignment.title || `Bài tập #${assignment.id}`)
    })
    headers.push('Điểm TB (bài đã làm)')
    headers.push('Điểm TB tích lũy (hệ 10)')
    
    const dataRows = [headers]
    gradeStudents.forEach(student => {
      const row = [student.name || '', student.email || '']
      gradeAssignments.forEach(assignment => {
        const scoreData = student.scores[assignment.id]
        if (scoreData) {
          const score10 = scoreData.total_points > 0 ? ((scoreData.score / scoreData.total_points) * 10).toFixed(1) : scoreData.score
          row.push(score10)
        } else {
          row.push('')
        }
      })
      row.push(student.average_score10 !== null ? student.average_score10 : '')
      row.push(student.average_score10_all !== null ? student.average_score10_all : '')
      dataRows.push(row)
    })
    
    const worksheet = XLSX.utils.aoa_to_sheet(dataRows)
    const workbook = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Bảng điểm')
    
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
    if (showToast) showToast('Không tìm thấy địa chỉ email nào trong tệp tin.', 'warning')
    return
  }
  const uniqueEmails = [...new Set(matchedEmails.map(email => email.trim().toLowerCase()))]
  const currentEmails = parseAllowedEmails()
  const allEmails = [...new Set([...currentEmails, ...uniqueEmails])]
  allowedEmailText.value = allEmails.join('\n')
  if (showToast) showToast(`Đã trích xuất ${uniqueEmails.length} email. Vui lòng nhấn "Lưu email" để hoàn tất.`, 'success')
}

const extractEmailsFromList = (emailList) => {
  if (!emailList.length) {
    if (showToast) showToast('Không tìm thấy địa chỉ email nào trong tệp tin.', 'warning')
    return
  }
  const uniqueEmails = [...new Set(emailList.map(email => email.trim().toLowerCase()))]
  const currentEmails = parseAllowedEmails()
  const allEmails = [...new Set([...currentEmails, ...uniqueEmails])]
  allowedEmailText.value = allEmails.join('\n')
  if (showToast) showToast(`Đã tự động dò tìm và trích xuất ${uniqueEmails.length} email học sinh.`, 'success')
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
        allowedMembersError.value = `Không xóa được: ${error.message}`
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
        allowedMembersError.value = `Không xóa được: ${error.message}`
        if (showToast) showToast(allowedMembersError.value, 'error')
      }
    }
  )
}

const clearAllAllowedMembers = async () => {
  if (!allowedMembers.value.length) return
  confirmAndExecute(
    'Xóa toàn bộ Whitelist',
    'Bạn có chắc chắn muốn xóa TOÀN BỘ email khỏi danh sách Whitelist?',
    async () => {
      try {
        await homeworkApi.clearAllowedMembers(roomId.value)
        allowedMembers.value = []
        selectedAllowedIds.value = []
        allowedMembersMessage.value = 'Đã xóa toàn bộ danh sách email.'
        if (showToast) showToast(allowedMembersMessage.value, 'success')
      } catch (error) {
        allowedMembersError.value = `Không xóa được: ${error.message}`
        if (showToast) showToast(allowedMembersError.value, 'error')
      }
    }
  )
}

const removeMember = async (member) => {
  confirmAndExecute(
    'Xóa thành viên',
    `Xóa thành viên "${member.user?.name || member.user_id}" khỏi phòng học?`,
    async () => {
      try {
        await homeworkApi.removeRoomMember(roomId.value, member.id)
        members.value = members.value.filter((m) => Number(m.id) !== Number(member.id))
        if (showToast) showToast('Đã xóa thành viên khỏi phòng.', 'success')
      } catch (error) {
        errorMessage.value = `Không xóa được: ${error.message}`
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
    console.error('Không tải được thành viên:', error)
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

const isApprovingAll = ref(false)

const getInitials = (name) => {
  if (!name) return 'U'
  const parts = name.trim().split(' ').filter(Boolean)
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase()
  return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase()
}

const approveAllPendingMembers = async () => {
  if (!pendingMembers.value.length || isApprovingAll.value) return
  isApprovingAll.value = true
  try {
    for (const member of pendingMembers.value) {
      await homeworkApi.approveRoomMember(roomId.value, member.id)
    }
    pendingMembers.value = []
    const membersData = await homeworkApi.getRoomMembers(roomId.value, { status: 'active' })
    members.value = membersData
    if (showToast) showToast('Đã phê duyệt toàn bộ thành viên thành công!', 'success')
  } catch (error) {
    if (showToast) showToast(`Lỗi khi duyệt: ${error.message}`, 'error')
  } finally {
    isApprovingAll.value = false
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
    'Bạn có chắc chắn muốn rời khỏi phòng học này?',
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
  beginTask()
  try {
    await loadRoomDetail()
    if (isAdmin.value) {
      activeSettingsTab.value = 'members'
    }
  } finally {
    endTask()
  }
})
</script>
