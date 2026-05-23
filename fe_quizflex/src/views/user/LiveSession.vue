<template>
  <section class="grid gap-6 py-8">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl sm:p-8">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Live Quiz</p>
          <h1 class="mt-2 text-4xl font-black text-[var(--text)] sm:text-5xl">
            {{ room?.name || `Room #${roomId}` }}
          </h1>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">
            {{ liveSession?.code ? `Session code: ${liveSession.code}` : "Tao va theo doi Live Quiz trong room." }}
          </p>
        </div>
        <div class="flex flex-wrap gap-3">
          <router-link class="btn-ghost" :to="`/rooms/${roomId}`">Ve room</router-link>
          <button class="btn-ghost" type="button" @click="syncLiveState">Dong bo</button>
        </div>
      </div>
    </article>

    <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">
      Dang tai live quiz...
    </div>

    <div v-if="errorMsg" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">
      {{ errorMsg }}
    </div>

    <div v-if="successMsg" class="rounded-[2rem] border border-emerald-500/30 bg-emerald-500/10 p-5 text-sm font-bold text-emerald-300">
      {{ successMsg }}
    </div>

    <template v-if="!isLoading">
      <section v-if="!liveSession" class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Waiting</p>
          <h2 class="mt-2 text-2xl font-black text-[var(--text)]">Room chua co live session dang mo</h2>
          <p class="mt-3 text-sm leading-7 text-[var(--muted)]">
            Host tao Live Quiz moi, thanh vien trong room se tu dong vao man hinh lam bai khi live bat dau.
          </p>
        </article>

        <form
          v-if="isRoomHost"
          class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]"
          @submit.prevent="createLiveSession"
        >
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Create Live</p>
          <label class="mt-5 grid gap-2 text-sm font-black text-[var(--text)]">
            Quiz
            <select v-model="liveForm.quiz_id" class="field">
              <option value="">Chon quiz</option>
              <option v-for="quiz in quizzes" :key="quiz.id" :value="quiz.id">{{ quiz.title }}</option>
            </select>
          </label>
          <label class="mt-4 grid gap-2 text-sm font-black text-[var(--text)]">
            Thoi gian goi y moi cau
            <input v-model.number="liveForm.question_duration_sec" class="field" min="5" max="300" type="number" />
          </label>
          <button class="btn-primary mt-5 w-full disabled:cursor-not-allowed disabled:opacity-60" type="submit" :disabled="isCreatingLive">
            {{ isCreatingLive ? "Dang tao..." : "Tao Live Quiz" }}
          </button>
        </form>
      </section>

      <section v-else-if="liveStatus === 'waiting'" class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_380px]">
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Waiting Room</p>
          <h2 class="mt-2 text-3xl font-black text-[var(--text)]">Cho host bat dau</h2>
          <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <InfoBox label="Session code" :value="liveSession.code || '-'" />
            <InfoBox label="Participants" :value="liveParticipants.length" />
            <InfoBox label="Questions" :value="totalQuestions" />
          </div>
          <div class="mt-6 flex flex-wrap gap-3">
            <div v-if="!isHost" class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-5 py-3 text-sm font-black text-[var(--text)]">
              {{ hasJoined ? "Da tu dong vao Live" : isJoiningLive ? "Dang ket noi vao Live..." : "Dang cho dong bo..." }}
            </div>
            <button v-if="isHost" class="btn-primary disabled:cursor-not-allowed disabled:opacity-60" type="button" :disabled="isStartingLive" @click="startLiveSession">
              {{ isStartingLive ? "Dang bat dau..." : "Bat dau Live" }}
            </button>
          </div>
        </article>

        <LeaderboardPanel :items="sortedLeaderboard" :participants="liveParticipants" :total-questions="totalQuestions" :current-user-id="currentUser?.id" />
      </section>

      <section v-else-if="liveStatus === 'playing'" class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
        <article v-if="isFinished && !isHost" class="rounded-[2rem] border border-emerald-500/30 bg-emerald-500/10 p-8 text-center shadow-[var(--shadow-card)]">
          <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-300">Completed</p>
          <h2 class="mt-2 text-4xl font-black text-[var(--text)]">Ban da hoan thanh Live Quiz</h2>
          <p class="mt-3 text-sm font-bold text-[var(--muted)]">
            Diem hien tai: {{ myParticipant?.score ?? 0 }} | Dung: {{ myParticipant?.correct_count ?? 0 }} | Sai: {{ myParticipant?.wrong_count ?? 0 }}
          </p>
          <p class="mt-2 text-sm text-[var(--muted)]">Dang cho cac thanh vien con lai hoan thanh.</p>
        </article>

        <article v-else class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
          <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">
                Cau {{ displayQuestionNumber }}/{{ totalQuestions || "?" }}
              </p>
              <h2 class="mt-2 text-3xl font-black text-[var(--text)]">{{ currentQuestion?.content || "Dang cho cau hoi..." }}</h2>
            </div>
            <div class="min-w-36 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4 text-center">
              <p class="text-xs font-black uppercase text-[var(--muted)]">Tien do</p>
              <b class="mt-1 block text-2xl font-black text-[var(--primary)]">{{ Math.min(currentQuestionIndex + 1, totalQuestions || 1) }}/{{ totalQuestions || "?" }}</b>
            </div>
          </div>

          <div class="mt-6 overflow-hidden rounded-full border border-[var(--border)] bg-[var(--surface-soft)] p-1">
            <div class="h-2 rounded-full bg-[var(--primary)] transition-all duration-300" :style="{ width: progressPercent + '%' }"></div>
          </div>

          <img v-if="currentQuestion?.image_url" :src="currentQuestion.image_url" alt="" class="mt-6 max-h-80 w-full rounded-2xl object-contain" />

          <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <button
              v-for="answer in answerOptions"
              :key="answer.id"
              type="button"
              class="min-h-24 rounded-2xl border p-5 text-left text-sm font-black transition disabled:cursor-not-allowed"
              :class="answerClass(answer.id)"
              :disabled="isHost || hasAnswered || isSendingAnswer || Boolean(lastAnswerResult)"
              @click="toggleAnswer(answer.id)"
            >
              {{ answer.content }}
            </button>
          </div>

          <div v-if="lastAnswerResult" class="mt-5 rounded-2xl border p-4 text-sm font-black" :class="lastAnswerResult.is_correct ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-rose-500/30 bg-rose-500/10 text-rose-300'">
            {{ lastAnswerResult.is_correct ? "Dung dap an." : "Chua dung. Dap an dung da duoc hien thi." }}
          </div>

          <div class="mt-6 flex flex-wrap items-center gap-3">
            <button
              v-if="!isHost"
              class="btn-primary disabled:cursor-not-allowed disabled:opacity-60"
              type="button"
              :disabled="!hasJoined || hasAnswered || isSendingAnswer || !selectedAnswerIds.length || !currentQuestion"
              @click="submitAnswer"
            >
              {{ isSendingAnswer ? "Dang gui..." : "Gui dap an" }}
            </button>
            <button v-if="isHost" class="btn-primary disabled:cursor-not-allowed disabled:opacity-60" type="button" :disabled="isNextQuestionLoading" @click="forceNextQuestion">
              {{ isNextQuestionLoading ? "Dang chuyen..." : "Host skip cau" }}
            </button>
          </div>
        </article>

        <LeaderboardPanel :items="sortedLeaderboard" :participants="liveParticipants" :total-questions="totalQuestions" :current-user-id="currentUser?.id" />
      </section>

      <section v-else class="grid gap-5">
        <article class="relative overflow-hidden rounded-[2rem] border border-[var(--primary)]/40 bg-[var(--primary)]/10 p-8 text-center shadow-[var(--shadow-card)]">
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Finished</p>
          <h2 class="mt-2 text-4xl font-black text-[var(--text)]">Live Quiz da ket thuc</h2>
          <p class="mt-3 text-sm text-[var(--muted)]">Leaderboard cuoi cung da duoc cap nhat.</p>
        </article>

        <div class="grid gap-4 md:grid-cols-3">
          <article
            v-for="item in sortedLeaderboard.slice(0, 3)"
            :key="item.participant_id || item.user_id"
            class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 text-center shadow-[var(--shadow-card)] transition hover:-translate-y-1"
          >
            <p class="text-4xl font-black text-[var(--primary)]">#{{ item.rank }}</p>
            <h3 class="mt-3 text-xl font-black text-[var(--text)]">{{ item.display_name || `User #${item.user_id}` }}</h3>
            <p class="mt-2 text-sm font-bold text-[var(--muted)]">{{ item.correct_count ?? 0 }} cau dung</p>
            <b class="mt-3 block text-2xl text-[var(--text)]">{{ item.score ?? 0 }} diem</b>
          </article>
        </div>

        <LeaderboardPanel :items="sortedLeaderboard" :participants="liveParticipants" :total-questions="totalQuestions" :current-user-id="currentUser?.id" />
      </section>
    </template>
  </section>
</template>

<script setup>
import { computed, defineComponent, h, onBeforeUnmount, onMounted, reactive, ref } from "vue";
import { useRoute } from "vue-router";
import { authApi, currentUserStorage, liveSessionsApi, quizzesApi, roomsApi } from "@/services/api";

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

const LeaderboardPanel = defineComponent({
  props: {
    items: { type: Array, default: () => [] },
    participants: { type: Array, default: () => [] },
    totalQuestions: { type: Number, default: 0 },
    currentUserId: { type: [String, Number], default: null },
  },
  setup(props) {
    const progressLabel = (item) => {
      const current = Number(item.current_question_index || 0);
      const total = Number(props.totalQuestions || 0);
      if (item.is_finished) return "Da hoan thanh";
      if (!total) return "Dang lam";
      return `Cau ${Math.min(current + 1, total)}/${total}`;
    };

    return () =>
      h("aside", { class: "rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]" }, [
        h("p", { class: "text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]" }, "Leaderboard"),
        props.items.length
          ? h(
              "div",
              { class: "mt-5 max-h-[520px] overflow-y-auto pr-1" },
              props.items.map((item) =>
                h(
                  "div",
                  {
                    key: item.participant_id || item.user_id,
                    class: [
                      "mb-3 flex items-center justify-between rounded-2xl border p-4 transition",
                      Number(item.user_id) === Number(props.currentUserId)
                        ? "border-[var(--primary)] bg-[var(--primary)]/10"
                        : "border-[var(--border)] bg-[var(--surface-soft)]",
                    ],
                  },
                  [
                    h("div", { class: "flex items-center gap-3" }, [
                      h("span", { class: "grid h-10 w-10 place-items-center rounded-full bg-[var(--primary)]/15 text-sm font-black text-[var(--primary)]" }, `#${item.rank || "-"}`),
                      h("div", [
                        h("b", { class: "text-sm text-[var(--text)]" }, item.display_name || `User #${item.user_id}`),
                        h("p", { class: "mt-1 text-xs text-[var(--muted)]" }, `${item.correct_count ?? 0} dung / ${item.wrong_count ?? 0} sai - ${progressLabel(item)}`),
                      ]),
                    ]),
                    h("span", { class: "text-lg font-black text-[var(--primary)]" }, item.score ?? 0),
                  ],
                ),
              ),
            )
          : h("p", { class: "mt-5 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm font-bold text-[var(--muted)]" }, "Chua co diem."),
        props.participants.length
          ? h("div", { class: "mt-5 border-t border-[var(--border)] pt-5" }, [
              h("p", { class: "text-xs font-black uppercase tracking-[0.14em] text-[var(--muted)]" }, "Participants"),
              ...props.participants.map((participant) =>
                h("p", { key: participant.id, class: "mt-2 text-sm font-bold text-[var(--text)]" }, participant.display_name || participant.user?.name || `User #${participant.user_id}`),
              ),
            ])
          : null,
      ]);
  },
});

const route = useRoute();
const roomId = computed(() => route.params.roomId);

const room = ref(null);
const liveSession = ref(null);
const currentQuestion = ref(null);
const liveLeaderboard = ref([]);
const liveParticipants = ref([]);
const myParticipant = ref(null);
const selectedAnswerIds = ref([]);
const hasAnswered = ref(false);
const isFinished = ref(false);
const lastAnswerResult = ref(null);
const currentQuestionIndex = ref(0);
const totalQuestionsState = ref(0);
const questionShownAt = ref(Date.now());
const isLoading = ref(true);
const isCreatingLive = ref(false);
const isJoiningLive = ref(false);
const isStartingLive = ref(false);
const isNextQuestionLoading = ref(false);
const isSendingAnswer = ref(false);
const errorMsg = ref("");
const successMsg = ref("");
const quizzes = ref([]);
const liveForm = reactive({
  quiz_id: "",
  question_duration_sec: 20,
});

let echoClient = null;
let liveChannelName = "";
let roomChannelName = "";
let syncInterval = null;

const currentUser = computed(() => currentUserStorage.get() || {});
const isHost = computed(() => Number(liveSession.value?.host_id) === Number(currentUser.value?.id));
const isRoomHost = computed(() => Number(room.value?.host_id) === Number(currentUser.value?.id) || String(currentUser.value?.role || "").toLowerCase() === "admin");
const liveStatus = computed(() => liveSession.value?.status ?? "waiting");
const totalQuestions = computed(() => Number(totalQuestionsState.value || liveSession.value?.total_questions || 0));
const answerOptions = computed(() => currentQuestion.value?.answers || currentQuestion.value?.options || []);
const displayQuestionNumber = computed(() => Math.min(Number(currentQuestionIndex.value || 0) + 1, totalQuestions.value || 1));
const progressPercent = computed(() => {
  if (!totalQuestions.value) return 0;
  const answered = isFinished.value ? totalQuestions.value : currentQuestionIndex.value;
  return Math.max(0, Math.min(100, (answered / totalQuestions.value) * 100));
});
const sortedLeaderboard = computed(() =>
  [...liveLeaderboard.value]
    .sort((a, b) => {
      const scoreDiff = Number(b.score || 0) - Number(a.score || 0);
      if (scoreDiff) return scoreDiff;
      const correctDiff = Number(b.correct_count || 0) - Number(a.correct_count || 0);
      if (correctDiff) return correctDiff;
      return String(a.finished_at || "9999").localeCompare(String(b.finished_at || "9999"));
    })
    .map((item, index) => ({ ...item, rank: index + 1 })),
);
const hasJoined = computed(() => liveParticipants.value.some((participant) => Number(participant.user_id) === Number(currentUser.value?.id)));

async function ensureEcho() {
  if (echoClient) return echoClient;
  try {
    echoClient = (await import("@/services/echo")).default;
    return echoClient;
  } catch {
    errorMsg.value = "Chua ket noi duoc realtime client. Dang dung polling fallback.";
    return null;
  }
}

function setQuestion(question, index = currentQuestionIndex.value) {
  currentQuestion.value = question || null;
  currentQuestionIndex.value = Number(index || 0);
  selectedAnswerIds.value = [];
  hasAnswered.value = false;
  lastAnswerResult.value = null;
  questionShownAt.value = Date.now();
}

function setLiveData(payload, { acceptQuestion = true } = {}) {
  if (payload === null) {
    liveSession.value = null;
    currentQuestion.value = null;
    liveLeaderboard.value = [];
    liveParticipants.value = [];
    return;
  }

  if (!payload || Array.isArray(payload)) return;

  if (payload.session) liveSession.value = { ...(liveSession.value || {}), ...payload.session };
  else if (payload.id) liveSession.value = { ...(liveSession.value || {}), ...payload };

  totalQuestionsState.value = Number(payload.total_questions || payload.session?.total_questions || totalQuestionsState.value || 0);

  if (payload.leaderboard) liveLeaderboard.value = payload.leaderboard;
  if (payload.participants) liveParticipants.value = payload.participants;

  if (payload.participant) {
    myParticipant.value = payload.participant;
    isFinished.value = Boolean(payload.participant.is_finished || payload.is_finished);
    currentQuestionIndex.value = Number(payload.participant.current_question_index ?? currentQuestionIndex.value);

    const exists = liveParticipants.value.some((participant) => Number(participant.id) === Number(payload.participant.id));
    liveParticipants.value = exists
      ? liveParticipants.value.map((participant) => (Number(participant.id) === Number(payload.participant.id) ? payload.participant : participant))
      : [...liveParticipants.value, payload.participant];
  }

  if (typeof payload.is_finished === "boolean") {
    isFinished.value = payload.is_finished;
  }

  if (payload.question && acceptQuestion) {
    setQuestion(payload.question, payload.current_question_index ?? payload.session?.current_question_index ?? currentQuestionIndex.value);
  }

  if (liveSession.value?.status === "ended") {
    isFinished.value = true;
  }
}

async function subscribeRoomChannel() {
  const echo = await ensureEcho();
  if (!echo) return;

  const channel = `room.${roomId.value}`;
  if (roomChannelName === channel) return;
  if (roomChannelName) echo.leave(roomChannelName);

  roomChannelName = channel;
  echo.channel(channel).listen(".live.created", async (event) => {
    setLiveData(event, { acceptQuestion: false });
    if (event?.session?.id) await subscribeLiveSession(event.session.id);
    await autoJoinLiveIfNeeded();
    successMsg.value = "Live session moi da duoc tao.";
  });
}

async function subscribeLiveSession(sessionId) {
  const echo = await ensureEcho();
  if (!echo || !sessionId) return;

  const channel = `live-session.${sessionId}`;
  if (liveChannelName === channel) return;
  if (liveChannelName) echo.leave(liveChannelName);

  liveChannelName = channel;
  echo
    .channel(channel)
    .listen(".leaderboard.updated", (event) => {
      liveLeaderboard.value = event.leaderboard || [];
    })
    .listen(".session.ended", (event) => {
      setLiveData(event, { acceptQuestion: false });
      successMsg.value = "Live Quiz da ket thuc.";
    })
    .listen(".session.started", async (event) => {
      setLiveData(event, { acceptQuestion: isHost.value });
      successMsg.value = "Live Quiz da bat dau.";
      await autoJoinLiveIfNeeded();
      if (!isHost.value) await initLivePlay();
    })
    .listen(".question.changed", (event) => {
      if (isHost.value) {
        setLiveData(event, { acceptQuestion: true });
        successMsg.value = "Host da chuyen cau.";
      }
    })
    .listen(".participant.joined", (event) => {
      setLiveData(event, { acceptQuestion: false });
    });
}

async function syncLiveState() {
  try {
    const data = await liveSessionsApi.currentForRoom(roomId.value);
    const hostFromPayload = Number(data?.session?.host_id) === Number(currentUser.value?.id);
    setLiveData(data, { acceptQuestion: hostFromPayload });
    if (liveSession.value?.id) await subscribeLiveSession(liveSession.value.id);
    await autoJoinLiveIfNeeded();
    if (liveSession.value?.status === "playing" && !isHost.value && !currentQuestion.value && !lastAnswerResult.value) {
      await initLivePlay();
    }
  } catch (error) {
    errorMsg.value = error.message;
  }
}

function startPolling() {
  if (syncInterval) clearInterval(syncInterval);
  syncInterval = setInterval(() => {
    syncLiveState();
  }, 3500);
}

async function createLiveSession() {
  if (!liveForm.quiz_id) {
    errorMsg.value = "Ban can chon quiz.";
    return;
  }

  isCreatingLive.value = true;
  errorMsg.value = "";
  successMsg.value = "";

  try {
    const data = await liveSessionsApi.create(roomId.value, {
      quiz_id: liveForm.quiz_id,
      question_duration_sec: liveForm.question_duration_sec || 20,
    });
    setLiveData(data, { acceptQuestion: false });
    await subscribeLiveSession(liveSession.value?.id || data.id);
    successMsg.value = "Tao Live Quiz thanh cong.";
  } catch (error) {
    errorMsg.value = error.message;
  } finally {
    isCreatingLive.value = false;
  }
}

async function autoJoinLiveIfNeeded() {
  if (!liveSession.value?.id || isHost.value || hasJoined.value || isJoiningLive.value) return;
  if (!["waiting", "playing"].includes(liveSession.value.status)) return;

  await joinLiveSession();
}

async function joinLiveSession() {
  if (!liveSession.value?.id) return;

  isJoiningLive.value = true;
  errorMsg.value = "";

  try {
    const data = await liveSessionsApi.join(liveSession.value.id);
    setLiveData(data, { acceptQuestion: false });
    if (liveSession.value?.status === "playing") {
      await initLivePlay();
    }
  } catch (error) {
    errorMsg.value = error.message;
  } finally {
    isJoiningLive.value = false;
  }
}

async function initLivePlay() {
  if (!liveSession.value?.id || isHost.value || lastAnswerResult.value) return;

  try {
    const data = await liveSessionsApi.currentQuestion(liveSession.value.id);
    setLiveData(data, { acceptQuestion: true });
  } catch (error) {
    errorMsg.value = error.message;
  }
}

async function startLiveSession() {
  if (!liveSession.value?.id) return;

  isStartingLive.value = true;
  errorMsg.value = "";
  successMsg.value = "";

  try {
    const data = await liveSessionsApi.start(liveSession.value.id);
    setLiveData(data, { acceptQuestion: true });
    successMsg.value = "Live Quiz da bat dau.";
  } catch (error) {
    errorMsg.value = error.message;
  } finally {
    isStartingLive.value = false;
  }
}

function toggleAnswer(answerId) {
  if (hasAnswered.value || isHost.value || lastAnswerResult.value) return;

  if (currentQuestion.value?.type === "multiple_choice") {
    selectedAnswerIds.value = selectedAnswerIds.value.includes(answerId)
      ? selectedAnswerIds.value.filter((id) => id !== answerId)
      : [...selectedAnswerIds.value, answerId];
    return;
  }

  selectedAnswerIds.value = [answerId];
}

function answerClass(answerId) {
  if (lastAnswerResult.value) {
    if (lastAnswerResult.value.correct_ids.includes(answerId)) {
      return "border-emerald-500/60 bg-emerald-500/15 text-emerald-200";
    }

    if (lastAnswerResult.value.selected_ids.includes(answerId)) {
      return "border-rose-500/60 bg-rose-500/15 text-rose-200";
    }
  }

  return selectedAnswerIds.value.includes(answerId)
    ? "border-[var(--primary)] bg-[var(--primary)]/15 text-[var(--text)]"
    : "border-[var(--border)] bg-[var(--surface-soft)] text-[var(--text)] hover:-translate-y-0.5";
}

async function submitAnswer() {
  if (hasAnswered.value || !selectedAnswerIds.value.length || !liveSession.value?.id || !currentQuestion.value) return;

  isSendingAnswer.value = true;
  errorMsg.value = "";
  successMsg.value = "";

  try {
    const selectedIds = [...selectedAnswerIds.value];
    const displayIndexBeforeSubmit = currentQuestionIndex.value;
    const data = await liveSessionsApi.answer(liveSession.value.id, {
      selected_answer_ids: selectedIds,
      response_time_ms: Math.max(0, Date.now() - questionShownAt.value),
    });

    hasAnswered.value = true;
    setLiveData(data, { acceptQuestion: false });
    currentQuestionIndex.value = displayIndexBeforeSubmit;
    lastAnswerResult.value = {
      is_correct: Boolean(data?.is_correct),
      correct_ids: data?.correct_ids || [],
      selected_ids: selectedIds,
    };

    await refreshLeaderboard();

    window.setTimeout(() => {
      if (data?.is_finished) {
        isFinished.value = true;
        currentQuestion.value = null;
        currentQuestionIndex.value = totalQuestions.value;
        selectedAnswerIds.value = [];
        hasAnswered.value = false;
        lastAnswerResult.value = null;
        successMsg.value = "Ban da hoan thanh Live Quiz.";
        return;
      }

      setQuestion(data?.next_question || null, data?.participant?.current_question_index ?? currentQuestionIndex.value + 1);
    }, 1500);
  } catch (error) {
    errorMsg.value = error.message;
  } finally {
    isSendingAnswer.value = false;
  }
}

async function forceNextQuestion() {
  if (!liveSession.value?.id || !isHost.value) return;

  isNextQuestionLoading.value = true;
  errorMsg.value = "";
  successMsg.value = "";

  try {
    const data = await liveSessionsApi.nextQuestion(liveSession.value.id);
    setLiveData(data, { acceptQuestion: true });
    successMsg.value = data?.session?.status === "ended" ? "Live Quiz da ket thuc." : "Host da skip cau.";
  } catch (error) {
    errorMsg.value = error.message;
  } finally {
    isNextQuestionLoading.value = false;
  }
}

async function refreshLeaderboard() {
  if (!liveSession.value?.id) return;
  try {
    liveLeaderboard.value = await liveSessionsApi.leaderboard(liveSession.value.id);
  } catch {
    // Optional refresh.
  }
}

onMounted(async () => {
  isLoading.value = true;
  try {
    if (localStorage.getItem("mock_user_id")) await authApi.me().catch(() => null);
    const [roomData, quizData] = await Promise.all([
      roomsApi.get(roomId.value),
      quizzesApi.list({ per_page: 100 }).catch(() => []),
    ]);
    room.value = roomData;
    quizzes.value = quizData;
    await subscribeRoomChannel();
    await syncLiveState();
    startPolling();
  } finally {
    isLoading.value = false;
  }
});

onBeforeUnmount(() => {
  if (syncInterval) clearInterval(syncInterval);
  if (echoClient && liveChannelName) echoClient.leave(liveChannelName);
  if (echoClient && roomChannelName) echoClient.leave(roomChannelName);
});
</script>
