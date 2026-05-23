<template>
  <section class="grid gap-6 py-8">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl sm:p-8">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Room Detail</p>
          <h1 class="mt-2 text-4xl font-black tracking-[-0.06em] text-[var(--text)] sm:text-5xl">
            {{ room?.name || `Room #${route.params.roomId}` }}
          </h1>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">
            {{ room?.description || (isLiveRoom ? "Xem tong quan, thanh vien va vao live quiz." : "Xem tong quan, homework va thanh vien trong room.") }}
          </p>
        </div>
        <div class="flex flex-wrap gap-3">
          <router-link class="btn-ghost" to="/rooms">My Rooms</router-link>
          <router-link v-if="isLiveRoom" class="btn-primary" :to="`/rooms/${route.params.roomId}/live`">Live Quiz</router-link>
        </div>
      </div>
    </article>

    <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">
      Dang tai thong tin room...
    </div>

    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">
      {{ errorMessage }}
    </div>

    <template v-if="!isLoading">
      <div class="flex gap-2 overflow-x-auto rounded-full border border-[var(--border)] bg-[var(--surface)] p-1 shadow-[var(--shadow-card)]">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          type="button"
          class="shrink-0 rounded-full px-4 py-2.5 text-sm font-black transition"
          :class="activeTab === tab.id ? 'bg-[var(--chip-active)] text-[var(--primary)] shadow-[0_10px_28px_rgba(155,44,255,0.16)]' : 'text-[var(--muted)] hover:text-[var(--text)]'"
          @click="activeTab = tab.id"
        >
          {{ tab.label }}
        </button>
      </div>

      <section v-if="activeTab === 'overview'" class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
          <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Tong quan</p>
              <h2 class="mt-2 text-2xl font-black text-[var(--text)]">{{ room?.name || "-" }}</h2>
              <p class="mt-3 text-sm leading-7 text-[var(--muted)]">{{ room?.description || "Room chua co mo ta." }}</p>
            </div>
            <span class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-3 text-sm font-black text-[var(--primary)]">
              {{ room?.code || "-" }}
            </span>
          </div>

          <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <InfoBox label="Ma room" :value="room?.code || '-'" />
            <InfoBox label="Loai room" :value="room?.type || 'homework'" />
            <InfoBox label="Vai tro cua ban" :value="roomRoleLabel" />
            <InfoBox label="Thanh vien" :value="visibleMembers.length" />
            <InfoBox v-if="isHomeworkRoom" label="Homework" :value="assignments.length" />
            <InfoBox label="Host" :value="room?.host?.name || `User #${room?.host_id || '-'}`" />
            <InfoBox label="Trang thai" :value="room?.status || 'waiting'" />
            <InfoBox v-if="isLiveRoom" label="Live" :value="activeLiveSession ? activeLiveSession.status : 'not_created'" />
          </div>

          <div v-if="isLiveRoom && activeLiveSession" class="mt-6 rounded-2xl border border-[var(--primary)]/40 bg-[var(--primary)]/10 p-5">
            <p class="text-xs font-black uppercase tracking-widest text-[var(--primary)]">Live Quiz dang dien ra</p>
            <h3 class="mt-2 text-xl font-black text-[var(--text)]">{{ activeLiveSession.code || `Live #${activeLiveSession.id}` }}</h3>
            <p class="mt-2 text-sm font-bold text-[var(--muted)]">Status: {{ activeLiveSession.status }}</p>
            <router-link :to="`/rooms/${room.id}/live`" class="btn-primary mt-4 inline-flex">Vao xem Live</router-link>
          </div>
        </article>

        <aside class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Actions</p>
          <div class="mt-5 grid gap-3">
            <form v-if="canManageRoom" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4" @submit.prevent="updateRoomCode">
              <label class="grid gap-2 text-sm font-black text-[var(--text)]">
                Ma room
                <input v-model="roomCodeForm.code" class="field uppercase" maxlength="12" placeholder="VD: MATH10A" />
              </label>
              <button class="btn-primary mt-3 w-full disabled:cursor-not-allowed disabled:opacity-60" type="submit" :disabled="isUpdatingRoomCode">
                {{ isUpdatingRoomCode ? "Dang doi..." : "Doi ma room" }}
              </button>
              <p v-if="roomCodeMessage" class="mt-3 text-xs font-bold text-emerald-300">{{ roomCodeMessage }}</p>
              <p v-if="roomCodeError" class="mt-3 text-xs font-bold text-rose-300">{{ roomCodeError }}</p>
            </form>
            <button class="btn-ghost w-full" type="button" @click="activeTab = 'members'">Xem thanh vien</button>
            <button v-if="isHomeworkRoom" class="btn-ghost w-full" type="button" @click="activeTab = 'homework'">Xem Homework</button>
            <router-link v-if="isLiveRoom" class="btn-ghost w-full text-center" :to="`/rooms/${route.params.roomId}/live`">Vao Live Quiz</router-link>
            <button v-if="canManageRoom && isHomeworkRoom" class="btn-primary w-full" type="button" @click="openAssignmentForm">Giao Homework</button>
          </div>
        </aside>
      </section>

      <section v-if="activeTab === 'homework' && isHomeworkRoom" class="grid gap-5">
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
          <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Homework</p>
              <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Bai da giao</h2>
              <p class="mt-2 text-sm text-[var(--muted)]">VIP/chu room xem bai nop va ket qua cua hoc sinh tai day.</p>
            </div>
            <button v-if="canManageRoom" class="btn-primary" type="button" @click="isAssignmentFormOpen = !isAssignmentFormOpen">
              {{ isAssignmentFormOpen ? "Dong form" : "Giao bai moi" }}
            </button>
          </div>

          <form v-if="canManageRoom && isAssignmentFormOpen" class="mt-6 rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-soft)] p-5" @submit.prevent="createAssignment">
            <div class="grid gap-4 lg:grid-cols-2">
              <label class="grid gap-2 text-sm font-black text-[var(--text)]">
                Quiz
                <select v-model="assignmentForm.quiz_id" class="field">
                  <option value="">Chon quiz</option>
                  <option v-for="quiz in quizzes" :key="quiz.id" :value="quiz.id">{{ quiz.title }}</option>
                </select>
              </label>
              <label class="grid gap-2 text-sm font-black text-[var(--text)]">
                Title
                <input v-model="assignmentForm.title" class="field" placeholder="Vi du: Bai tap tuan 3" />
              </label>
              <label class="grid gap-2 text-sm font-black text-[var(--text)] lg:col-span-2">
                Description
                <textarea v-model="assignmentForm.description" class="field min-h-24" placeholder="Ghi chu cho hoc sinh"></textarea>
              </label>
              <label class="grid gap-2 text-sm font-black text-[var(--text)]">
                Starts at
                <input v-model="assignmentForm.starts_at" class="field" type="datetime-local" />
              </label>
              <label class="grid gap-2 text-sm font-black text-[var(--text)]">
                Deadline
                <input v-model="assignmentForm.deadline_at" class="field" type="datetime-local" />
              </label>
              <label class="grid gap-2 text-sm font-black text-[var(--text)]">
                Duration minutes
                <input v-model.number="assignmentForm.duration_minutes" class="field" min="1" type="number" />
              </label>
              <label class="grid gap-2 text-sm font-black text-[var(--text)]">
                Max attempts
                <input v-model.number="assignmentForm.max_attempts" class="field" min="1" type="number" />
              </label>
              <label class="grid gap-2 text-sm font-black text-[var(--text)]">
                Show result mode
                <select v-model="assignmentForm.show_result_mode" class="field">
                  <option value="immediately">Immediately</option>
                  <option value="after_submit">After submit</option>
                  <option value="after_deadline">After deadline</option>
                  <option value="never">Never</option>
                </select>
              </label>
            </div>
            <div class="mt-5 flex flex-wrap items-center gap-3">
              <button class="btn-primary disabled:cursor-not-allowed disabled:opacity-60" type="submit" :disabled="isSavingAssignment">
                {{ isSavingAssignment ? "Dang luu..." : "Publish assignment" }}
              </button>
              <p v-if="assignmentMessage" class="text-sm font-bold text-emerald-300">{{ assignmentMessage }}</p>
            </div>
          </form>
        </article>

        <div v-if="assignments.length" class="grid gap-4">
          <article v-for="assignment in assignments" :key="assignment.id" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
              <div>
                <span class="rounded-full border px-3 py-1 text-xs font-black" :class="assignmentStatusClass(assignment)">
                  {{ assignmentStatusLabel(assignment) }}
                </span>
                <h3 class="mt-3 text-2xl font-black text-[var(--text)]">{{ assignment.title }}</h3>
                <p class="mt-2 text-sm leading-6 text-[var(--muted)]">{{ assignment.description || "Khong co mo ta." }}</p>
              </div>
              <div class="flex flex-wrap gap-2">
                <router-link v-if="!canManageRoom" class="btn-ghost px-4 py-2 text-xs" :to="assignmentDetailPath(assignment)">Chi tiet</router-link>
                <router-link v-if="!canManageRoom && canDoAssignment(assignment)" class="btn-primary px-4 py-2 text-xs" :to="assignmentPlayPath(assignment)">Lam bai</router-link>
                <router-link v-if="!canManageRoom && isSubmittedAssignment(assignment)" class="btn-primary px-4 py-2 text-xs" :to="assignmentResultPath(assignment)">Xem ket qua</router-link>
                <router-link v-if="canManageRoom" class="btn-primary px-4 py-2 text-xs" :to="assignmentSubmissionsPath(assignment)">Xem bai nop</router-link>
              </div>
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
              <InfoBox label="Quiz" :value="assignment.quizTitle || assignment.quiz?.title || `Quiz #${assignment.quiz_id}`" />
              <InfoBox label="Deadline" :value="formatDate(assignment.deadline_at)" />
              <InfoBox label="Duration" :value="assignment.durationLabel || durationLabel(assignment.duration_minutes)" />
              <InfoBox label="Max attempts" :value="assignment.max_attempts || 1" />
            </div>
          </article>
        </div>

        <div v-else class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">
          Room nay chua co homework.
        </div>

      </section>

      <section v-if="activeTab === 'members'" class="grid gap-5">
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
          <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Members</p>
              <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Thanh vien trong room</h2>
              <p class="mt-2 text-sm text-[var(--muted)]">Danh sach nay khong tinh chu phong.</p>
            </div>
            <InfoBox label="Tong thanh vien" :value="visibleMembers.length" />
          </div>
        </article>

        <div v-if="visibleMembers.length" class="grid gap-4 lg:grid-cols-2">
          <article v-for="member in visibleMembers" :key="member.id || member.user_id" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 class="text-xl font-black text-[var(--text)]">{{ member.user?.name || member.name || `User #${member.user_id}` }}</h3>
                <p class="mt-1 text-sm text-[var(--muted)]">{{ member.user?.email || member.email || "Chua co email" }}</p>
              </div>
              <span class="rounded-full border border-[var(--border-strong)] bg-[var(--chip-active)] px-3 py-1 text-xs font-black text-[var(--primary)]">
                {{ member.room_role || member.role || "member" }}
              </span>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
              <InfoBox label="Trang thai" :value="member.status || 'active'" />
              <InfoBox label="Tham gia" :value="formatDate(member.joined_at || member.created_at)" />
            </div>
          </article>
        </div>

        <div v-else class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">
          Chua co thanh vien nao ngoai chu phong.
        </div>
      </section>
    </template>
  </section>
</template>

<script setup>
import { computed, defineComponent, h, onBeforeUnmount, onMounted, reactive, ref } from "vue";
import { useRoute } from "vue-router";
import {
  authApi,
  currentUserStorage,
  homeworkAssignmentsApi,
  homeworkProgressStorage,
  liveSessionsApi,
  normalizeAssignment,
  quizzesApi,
  roomsApi,
} from "@/services/api";

const InfoBox = defineComponent({
  props: {
    label: { type: String, required: true },
    value: { type: [String, Number], default: "-" },
  },
  setup(props) {
    return () =>
      h("div", { class: "rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4" }, [
        h("p", { class: "text-xs font-bold text-[var(--muted)]" }, props.label),
        h("b", { class: "mt-1 block text-sm text-[var(--text)]" }, props.value ?? "-"),
      ]);
  },
});

const route = useRoute();
const activeTab = ref(route.path.endsWith("/homework") ? "homework" : "overview");
const room = ref(null);
const members = ref([]);
const assignments = ref([]);
const quizzes = ref([]);
const progressMap = ref({});
const activeLiveSession = ref(null);
const isLoading = ref(true);
const errorMessage = ref("");
const isUpdatingRoomCode = ref(false);
const roomCodeMessage = ref("");
const roomCodeError = ref("");
const isAssignmentFormOpen = ref(false);
const isSavingAssignment = ref(false);
const assignmentMessage = ref("");
let livePollTimer = null;

const assignmentForm = reactive({
  quiz_id: "",
  title: "",
  description: "",
  starts_at: "",
  deadline_at: "",
  duration_minutes: null,
  max_attempts: 1,
  show_result_mode: "after_submit",
});
const roomCodeForm = reactive({
  code: "",
});

const currentUser = computed(() => currentUserStorage.get() || {});
const isLiveRoom = computed(() => String(room.value?.type || "homework").toLowerCase() === "live");
const isHomeworkRoom = computed(() => !isLiveRoom.value);
const tabs = computed(() => [
  { id: "overview", label: "Tong quan" },
  ...(isHomeworkRoom.value ? [{ id: "homework", label: "Homework" }] : []),
  { id: "members", label: "Thanh vien" },
]);
const currentMember = computed(() => members.value.find((member) => Number(member.user_id) === Number(currentUser.value.id)) || null);
const roomRoleLabel = computed(
  () =>
    currentMember.value?.room_role ||
    currentMember.value?.role ||
    (Number(room.value?.host_id) === Number(currentUser.value.id) ? "owner" : "member"),
);
const visibleMembers = computed(() =>
  members.value.filter((member) => {
    const role = String(member.room_role || member.role || "").toLowerCase();
    return Number(member.user_id) !== Number(room.value?.host_id) && role !== "owner";
  }),
);
const canManageRoom = computed(() => {
  if (String(currentUser.value.role || "").toLowerCase() === "admin") return true;
  if (Number(room.value?.host_id) === Number(currentUser.value.id)) return true;
  return ["owner", "teacher"].includes(String(roomRoleLabel.value || "").toLowerCase());
});

const nowMs = () => Date.now();
const dateMs = (value) => (value ? new Date(value).getTime() : null);
const isBeforeStart = (assignment) => {
  const startsAt = dateMs(assignment.starts_at);
  return startsAt && startsAt > nowMs();
};
const isPastDeadline = (assignment) => {
  const deadlineAt = dateMs(assignment.deadline_at);
  return deadlineAt && deadlineAt < nowMs();
};
const progressFor = (assignmentId) => progressMap.value?.[assignmentId] || null;
const formatDate = (value) => (value ? new Date(value).toLocaleString("vi-VN") : "Khong gioi han");
const durationLabel = (minutes) => (minutes ? `${minutes} phut` : "Khong gioi han");
const scoreLabel = (item) => {
  if (item?.score === null || item?.score === undefined) return "-";
  return `${item.score}/${item.max_score ?? item.total_questions ?? "-"}`;
};
const assignmentStatusLabel = (assignment) => {
  const progress = progressFor(assignment.id);
  if (progress?.status === "submitted") return "Da nop";
  if (progress?.status === "late" || isPastDeadline(assignment)) return "Qua han";
  if (isBeforeStart(assignment)) return "Chua den gio";
  if (progress?.status === "in_progress") return "Dang lam";
  if (assignment.status !== "published") return "Chua mo";
  return "Chua lam";
};
const assignmentStatusClass = (assignment) => {
  const label = assignmentStatusLabel(assignment);
  if (label === "Da nop") return "border-emerald-500/30 bg-emerald-500/10 text-emerald-300";
  if (label === "Dang lam") return "border-amber-500/30 bg-amber-500/10 text-amber-300";
  if (label === "Qua han") return "border-rose-500/30 bg-rose-500/10 text-rose-300";
  return "border-slate-500/30 bg-slate-500/10 text-slate-300";
};
const canDoAssignment = (assignment) => {
  const progress = progressFor(assignment.id);
  if (progress?.status === "submitted" || progress?.status === "late") return false;
  return assignment.status === "published" && !isBeforeStart(assignment) && !isPastDeadline(assignment);
};
const isSubmittedAssignment = (assignment) => progressFor(assignment.id)?.status === "submitted";
const assignmentDetailPath = (assignment) => `/rooms/${route.params.roomId}/assignments/${assignment.id}`;
const assignmentPlayPath = (assignment) => `/rooms/${route.params.roomId}/assignments/${assignment.id}/do`;
const assignmentResultPath = (assignment) => `/rooms/${route.params.roomId}/assignments/${assignment.id}/result`;
const assignmentSubmissionsPath = (assignment) => `/rooms/${route.params.roomId}/assignments/${assignment.id}/submissions`;
const emptyToNull = (value) => (value === "" || value === undefined ? null : value);

function openAssignmentForm() {
  if (!isHomeworkRoom.value) return;
  activeTab.value = "homework";
  isAssignmentFormOpen.value = true;
}

async function syncActiveLiveSession() {
  if (!isLiveRoom.value) {
    activeLiveSession.value = null;
    return;
  }

  try {
    const data = await liveSessionsApi.currentForRoom(route.params.roomId);
    activeLiveSession.value = data?.session || data || null;
  } catch {
    activeLiveSession.value = null;
  }
}

async function updateRoomCode() {
  if (!canManageRoom.value) return;

  const code = roomCodeForm.code.trim().toUpperCase();
  roomCodeMessage.value = "";
  roomCodeError.value = "";

  if (!/^[A-Z0-9]{4,12}$/.test(code)) {
    roomCodeError.value = "Ma room chi gom chu/số, tu 4 den 12 ky tu.";
    return;
  }

  isUpdatingRoomCode.value = true;

  try {
    const updatedRoom = await roomsApi.updateCode(route.params.roomId, code);
    room.value = {
      ...(room.value || {}),
      ...updatedRoom,
    };
    roomCodeForm.code = updatedRoom.code || code;
    roomCodeMessage.value = "Da doi ma room.";
  } catch (error) {
    roomCodeError.value = error.message;
  } finally {
    isUpdatingRoomCode.value = false;
  }
}

async function loadRoom() {
  isLoading.value = true;
  errorMessage.value = "";
  progressMap.value = homeworkProgressStorage.getAll();

  try {
    if (localStorage.getItem("mock_user_id")) await authApi.me().catch(() => null);

    room.value = await roomsApi.get(route.params.roomId);
    roomCodeForm.code = room.value?.code || "";
    if (activeTab.value === "homework" && isLiveRoom.value) {
      activeTab.value = "overview";
    }

    const [assignmentResult, memberResult, quizResult] = await Promise.allSettled([
      isHomeworkRoom.value ? homeworkAssignmentsApi.list(route.params.roomId) : Promise.resolve([]),
      roomsApi.members(route.params.roomId),
      isHomeworkRoom.value ? quizzesApi.list({ per_page: 100 }) : Promise.resolve([]),
    ]);

    assignments.value = assignmentResult.status === "fulfilled" ? assignmentResult.value.map(normalizeAssignment) : [];
    members.value = memberResult.status === "fulfilled" ? memberResult.value : [];
    quizzes.value = quizResult.status === "fulfilled" ? quizResult.value : [];
    if (isLiveRoom.value) {
      await syncActiveLiveSession();
      livePollTimer = setInterval(syncActiveLiveSession, 10000);
    } else {
      activeLiveSession.value = null;
    }
  } catch (error) {
    if (error.status === 401) {
      errorMessage.value = "Ban can dang nhap de xem room.";
      return;
    }
    if (error.status === 403) {
      errorMessage.value = "Ban khong co quyen truy cap room nay.";
      return;
    }
    errorMessage.value = `Khong tai duoc room: ${error.message}`;
  } finally {
    isLoading.value = false;
  }
}

async function createAssignment() {
  if (!canManageRoom.value) return;
  if (!assignmentForm.quiz_id || !assignmentForm.title.trim()) {
    errorMessage.value = "Ban can chon quiz va nhap title assignment.";
    return;
  }

  isSavingAssignment.value = true;
  errorMessage.value = "";
  assignmentMessage.value = "";

  try {
    await homeworkAssignmentsApi.create(route.params.roomId, {
      quiz_id: assignmentForm.quiz_id,
      title: assignmentForm.title.trim(),
      description: emptyToNull(assignmentForm.description.trim()),
      starts_at: emptyToNull(assignmentForm.starts_at),
      deadline_at: emptyToNull(assignmentForm.deadline_at),
      duration_minutes: emptyToNull(assignmentForm.duration_minutes),
      max_attempts: assignmentForm.max_attempts || 1,
      show_result_mode: assignmentForm.show_result_mode,
      status: "published",
    });

    assignmentMessage.value = "Giao bai thanh cong.";
    assignmentForm.quiz_id = "";
    assignmentForm.title = "";
    assignmentForm.description = "";
    assignmentForm.starts_at = "";
    assignmentForm.deadline_at = "";
    assignmentForm.duration_minutes = null;
    assignmentForm.max_attempts = 1;
    assignmentForm.show_result_mode = "after_submit";
    isAssignmentFormOpen.value = false;

    const assignmentData = await homeworkAssignmentsApi.list(route.params.roomId);
    assignments.value = assignmentData.map(normalizeAssignment);
  } catch (error) {
    errorMessage.value = `Khong giao duoc bai: ${error.message}`;
  } finally {
    isSavingAssignment.value = false;
  }
}

onMounted(loadRoom);

onBeforeUnmount(() => {
  if (livePollTimer) clearInterval(livePollTimer);
});
</script>
