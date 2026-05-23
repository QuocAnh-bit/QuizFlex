<template>
  <section class="grid gap-6 py-8">
    <article class="relative overflow-hidden rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-soft)] backdrop-blur-2xl sm:p-8">
      <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--primary)]/15 blur-3xl"></div>
      <div class="relative z-10 flex flex-col justify-between gap-5 xl:flex-row xl:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Assignment Submissions</p>
          <h1 class="mt-2 text-4xl font-black text-[var(--text)] sm:text-5xl">
            {{ assignment?.title || `Assignment #${assignmentId}` }}
          </h1>
          <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">
            Danh sach hoc sinh da lam va ket qua bai nop trong homework nay.
          </p>
        </div>
        <div class="flex flex-wrap gap-3">
          <router-link class="btn-ghost" :to="`/rooms/${roomId}`">Ve room</router-link>
          <router-link class="btn-ghost" :to="`/rooms/${roomId}/homework`">Homework</router-link>
        </div>
      </div>
    </article>

    <div v-if="isLoading" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-10 text-center text-sm font-bold text-[var(--muted)]">
      Dang tai danh sach bai nop...
    </div>

    <div v-if="errorMessage" class="rounded-[2rem] border border-rose-500/30 bg-rose-500/10 p-5 text-sm font-bold text-rose-300">
      {{ errorMessage }}
    </div>

    <template v-if="!isLoading">
      <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_440px]">
        <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
          <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Submissions</p>
              <h2 class="mt-2 text-2xl font-black text-[var(--text)]">{{ assignment?.title || "-" }}</h2>
              <p class="mt-2 text-sm text-[var(--muted)]">{{ assignment?.description || "Khong co mo ta." }}</p>
            </div>
            <span class="rounded-full border border-[var(--border)] bg-[var(--surface-soft)] px-4 py-2 text-sm font-black text-[var(--text)]">
              {{ submissions.length }} bai nop
            </span>
          </div>

          <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <InfoBox label="Quiz" :value="assignment?.quiz?.title || assignment?.quiz_title || `Quiz #${assignment?.quiz_id || '-'}`" />
            <InfoBox label="Deadline" :value="formatDate(assignment?.deadline_at)" />
            <InfoBox label="Duration" :value="durationLabel(assignment?.duration_minutes)" />
            <InfoBox label="Max attempts" :value="assignment?.max_attempts || 1" />
          </div>

          <div class="mt-6 overflow-x-auto rounded-[1.5rem] border border-[var(--border)]">
            <div class="min-w-[760px]">
              <div class="grid grid-cols-[minmax(200px,1.4fr)_120px_120px_160px_110px] gap-3 border-b border-[var(--border)] bg-[var(--surface-soft)] px-4 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--muted)]">
                <span>Hoc sinh</span>
                <span>Trang thai</span>
                <span>Diem</span>
                <span>Nop luc</span>
                <span>Action</span>
              </div>
              <div v-if="submissions.length">
                <div
                  v-for="submission in submissions"
                  :key="submission.id"
                  class="grid grid-cols-[minmax(200px,1.4fr)_120px_120px_160px_110px] gap-3 border-b border-[var(--border)] px-4 py-3 text-sm last:border-b-0"
                  :class="selectedSubmission?.id === submission.id ? 'bg-[var(--primary)]/10' : ''"
                >
                  <div>
                    <b class="text-[var(--text)]">{{ submission.user?.name || `User #${submission.user_id}` }}</b>
                    <p class="mt-1 text-xs text-[var(--muted)]">{{ submission.user?.email || "" }}</p>
                  </div>
                  <span class="self-center rounded-full border px-3 py-1 text-xs font-black" :class="submissionStatusClass(submission.status)">
                    {{ submissionStatusLabel(submission.status) }}
                  </span>
                  <b class="self-center text-[var(--text)]">{{ scoreLabel(submission) }}</b>
                  <span class="self-center text-[var(--muted)]">{{ formatDate(submission.submitted_at) }}</span>
                  <button class="btn-ghost px-3 py-2 text-xs" type="button" @click="viewSubmission(submission)">Xem</button>
                </div>
              </div>
              <div v-else class="p-8 text-center text-sm font-bold text-[var(--muted)]">
                Chua co hoc sinh nop bai.
              </div>
            </div>
          </div>
        </article>

        <aside class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface)] p-6 shadow-[var(--shadow-card)]">
          <div v-if="isLoadingSubmissionDetail" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm font-bold text-[var(--muted)]">
            Dang tai chi tiet bai nop...
          </div>

          <div v-else-if="selectedSubmission">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--primary)]">Submission Detail</p>
            <h3 class="mt-2 text-2xl font-black text-[var(--text)]">{{ selectedSubmission.user?.name || `User #${selectedSubmission.user_id}` }}</h3>
            <p class="mt-1 text-sm text-[var(--muted)]">{{ selectedSubmission.user?.email || "" }}</p>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
              <InfoBox label="Diem" :value="scoreLabel(selectedSubmission)" />
              <InfoBox label="Dung/Sai" :value="`${selectedSubmission.correct_count ?? 0}/${selectedSubmission.wrong_count ?? 0}`" />
              <InfoBox label="Tong cau" :value="selectedSubmission.total_questions ?? '-'" />
              <InfoBox label="Nop luc" :value="formatDate(selectedSubmission.submitted_at)" />
            </div>

            <div v-if="selectedSubmission.answers?.length" class="mt-6 grid gap-3">
              <article v-for="answer in selectedSubmission.answers" :key="answer.id" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-4">
                <p class="text-sm font-black text-[var(--text)]">{{ answer.question?.content || `Question #${answer.question_id}` }}</p>
                <p class="mt-2 text-xs font-bold text-[var(--muted)]">Tra loi: {{ answerText(answer) }}</p>
                <p v-if="answer.correct_answer?.content" class="mt-1 text-xs font-bold text-emerald-300">Dap an dung: {{ answer.correct_answer.content }}</p>
                <p v-if="answer.is_correct !== undefined" class="mt-1 text-xs font-black" :class="answer.is_correct ? 'text-emerald-300' : 'text-rose-300'">
                  {{ answer.is_correct ? "Dung" : "Sai" }}
                </p>
              </article>
            </div>

            <div v-else class="mt-6 rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm font-bold text-[var(--muted)]">
              Chua co chi tiet cau tra loi.
            </div>
          </div>

          <div v-else class="rounded-2xl border border-[var(--border)] bg-[var(--surface-soft)] p-5 text-sm font-bold text-[var(--muted)]">
            Chon mot bai nop de xem chi tiet.
          </div>
        </aside>
      </section>
    </template>
  </section>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { authApi, homeworkAssignmentsApi } from "@/services/api";

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
const roomId = computed(() => route.params.roomId);
const assignmentId = computed(() => route.params.assignmentId);

const assignment = ref(null);
const submissions = ref([]);
const selectedSubmission = ref(null);
const isLoading = ref(true);
const isLoadingSubmissionDetail = ref(false);
const errorMessage = ref("");

const formatDate = (value) => (value ? new Date(value).toLocaleString("vi-VN") : "Khong gioi han");
const durationLabel = (minutes) => (minutes ? `${minutes} phut` : "Khong gioi han");
const scoreLabel = (item) => {
  if (item?.score === null || item?.score === undefined) return "-";
  return `${item.score}/${item.max_score ?? item.total_questions ?? "-"}`;
};
const submissionStatusLabel = (status) => ({ in_progress: "Dang lam", submitted: "Da nop", late: "Qua han" })[status] || status || "-";
const submissionStatusClass = (status) => {
  if (status === "submitted") return "border-emerald-500/30 bg-emerald-500/10 text-emerald-300";
  if (status === "in_progress") return "border-amber-500/30 bg-amber-500/10 text-amber-300";
  if (status === "late") return "border-rose-500/30 bg-rose-500/10 text-rose-300";
  return "border-slate-500/30 bg-slate-500/10 text-slate-300";
};

function answerText(answer) {
  if (answer.selected_answer?.content) return answer.selected_answer.content;
  if (Array.isArray(answer.selected_answers) && answer.selected_answers.length) {
    return answer.selected_answers.map((item) => item.content).join(", ");
  }
  if (Array.isArray(answer.selected_answer_ids) && answer.selected_answer_ids.length) {
    return answer.selected_answer_ids.join(", ");
  }
  return answer.answer_text || "-";
}

async function loadPage() {
  isLoading.value = true;
  errorMessage.value = "";

  try {
    if (localStorage.getItem("mock_user_id")) await authApi.me().catch(() => null);
    const [assignmentData, submissionData] = await Promise.all([
      homeworkAssignmentsApi.get(roomId.value, assignmentId.value),
      homeworkAssignmentsApi.listSubmissions(roomId.value, assignmentId.value),
    ]);

    assignment.value = assignmentData;
    submissions.value = submissionData;

    const firstSubmitted = submissions.value.find((submission) => submission.status === "submitted") || submissions.value[0];
    if (firstSubmitted) await viewSubmission(firstSubmitted);
  } catch (error) {
    if (error.status === 401) {
      errorMessage.value = "Ban can dang nhap de xem bai nop.";
      return;
    }
    if (error.status === 403) {
      errorMessage.value = "Ban khong co quyen xem bai nop cua assignment nay.";
      return;
    }
    errorMessage.value = `Khong tai duoc danh sach bai nop: ${error.message}`;
  } finally {
    isLoading.value = false;
  }
}

async function viewSubmission(submission) {
  isLoadingSubmissionDetail.value = true;
  errorMessage.value = "";

  try {
    selectedSubmission.value = await homeworkAssignmentsApi.getSubmissionResult(roomId.value, assignmentId.value, submission.id);
  } catch (error) {
    errorMessage.value = `Khong tai duoc chi tiet bai nop: ${error.message}`;
  } finally {
    isLoadingSubmissionDetail.value = false;
  }
}

onMounted(loadPage);
</script>
