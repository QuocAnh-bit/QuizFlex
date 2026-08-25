import axios from "axios";

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || "/api";
const AUTH_ROLES = ["admin", "plus", "pro", "ultra", "free"];
const USER_ROLES = ["admin", "plus", "pro", "ultra", "free"];
const ADMIN_ROLES = ["admin"];

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    Accept: "application/json",
  },
});

const memoryCache = new Map();

const inflightRequests = new Map();

export const withMemoryCache = async (key, fetcher, ttlMs = 15000) => {
  const cached = memoryCache.get(key);
  const now = Date.now();
  if (cached && cached.data !== undefined && cached.data !== null && now - cached.timestamp < ttlMs) {
    return cached.data;
  }

  if (inflightRequests.has(key)) {
    return inflightRequests.get(key);
  }

  const promise = (async () => {
    try {
      const freshData = await fetcher();
      if (freshData !== undefined && freshData !== null) {
        memoryCache.set(key, { data: freshData, timestamp: Date.now() });
      } else {
        memoryCache.delete(key);
      }
      return freshData;
    } catch (error) {
      memoryCache.delete(key);
      throw error;
    } finally {
      inflightRequests.delete(key);
    }
  })();

  inflightRequests.set(key, promise);
  return promise;
};

export const clearMemoryCache = (keyPrefix = "") => {
  if (!keyPrefix) {
    memoryCache.clear();
    return;
  }
  for (const key of memoryCache.keys()) {
    if (key.startsWith(keyPrefix)) memoryCache.delete(key);
  }
};

export const importOcrQuiz = (payload) => {
  return api.post("/ocr/import-quiz", payload);
};

const unwrap = (payload) => payload?.data ?? payload;
const unwrapCollection = (payload) => {
  const body = unwrap(payload);
  if (Array.isArray(body)) return body;
  if (Array.isArray(body?.data)) return body.data;
  return [];
};

export const taxonomyApi = {
  async tree(force = false) {
    if (force) clearMemoryCache('education_taxonomy_tree');
    return withMemoryCache('education_taxonomy_tree', async () => {
      const { data } = await api.get('/taxonomies/tree');
      const payload = unwrap(data);
      return payload?.education_levels ? payload : (payload?.data ?? payload);
    }, 300000);
  }
};

export const questionsBankApi = {
  async fetchBank(params = {}) {
    const { data } = await api.get('/questions/bank', { params });
    const payload = unwrap(data);
    const items = Array.isArray(payload) ? payload : (Array.isArray(payload?.data) ? payload.data : []);
    const total = payload?.total ?? items.length;
    const currentPage = payload?.current_page ?? 1;
    const lastPage = payload?.last_page ?? 1;
    const perPage = payload?.per_page ?? 15;
    return { items, total, currentPage, lastPage, perPage };
  },
  async fetchTopics(params = {}) {
    const { data } = await api.get('/questions/topics', { params });
    return unwrapCollection(data);
  },
  async fetchStats(params = {}) {
    const { data } = await api.get('/questions/stats', { params });
    return unwrap(data);
  },
  async createQuizFromBank(payload) {
    if (payload.cover_file instanceof File) {
      const formData = new FormData();
      Object.keys(payload).forEach((key) => {
        if (payload[key] !== undefined && payload[key] !== null) {
          if (typeof payload[key] === 'boolean') {
            formData.append(key, payload[key] ? '1' : '0');
          } else if (Array.isArray(payload[key])) {
            payload[key].forEach((val, i) => {
              formData.append(`${key}[${i}]`, val);
            });
          } else {
            formData.append(key, payload[key]);
          }
        }
      });
      const { data } = await api.post('/quizzes/from-bank', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      return unwrap(data);
    }
    const { data } = await api.post('/quizzes/from-bank', payload);
    return unwrap(data);
  },
  async createQuestion(payload) {
    const { data } = await api.post('/questions', payload);
    return unwrap(data);
  },
  async getQuestion(id) {
    const { data } = await api.get(`/questions/${id}`);
    return unwrap(data);
  }
};

export const quizReviewApi = {
  async requestReview(quizId, payload = {}) {
    const { data } = await api.post(`/quizzes/${quizId}/request-review`, payload);
    return unwrap(data);
  },
  async getReviewHistory(quizId) {
    const { data } = await api.get(`/quizzes/${quizId}/review-history`);
    return unwrap(data);
  },
  async fetchAdminReviewRequests(params = {}) {
    const { data } = await api.get('/admin/quiz-review-requests', { params });
    return unwrap(data);
  },
  async getAdminReviewRequest(id) {
    const { data } = await api.get(`/admin/quiz-review-requests/${id}`);
    return unwrap(data);
  },
  async adminApprove(id) {
    const { data } = await api.post(`/admin/quiz-review-requests/${id}/approve`);
    return unwrap(data);
  },
  async adminReject(id, reason) {
    const { data } = await api.post(`/admin/quiz-review-requests/${id}/reject`, { reason, note: reason });
    return unwrap(data);
  },
  async adminBulkApprove(ids) {
    const { data } = await api.post('/admin/quiz-review-requests/bulk-approve', { ids });
    return unwrap(data);
  },
  async adminBulkReject(ids, reason) {
    const { data } = await api.post('/admin/quiz-review-requests/bulk-reject', { ids, reason, note: reason });
    return unwrap(data);
  }
};

export const myQuestionsApi = {
  async fetchBank(params = {}) {
    const { data } = await api.get('/user/my-questions', { params });
    const payload = unwrap(data);
    const items = Array.isArray(payload) ? payload : (Array.isArray(payload?.data) ? payload.data : []);
    const total = payload?.total ?? items.length;
    const currentPage = payload?.current_page ?? 1;
    const lastPage = payload?.last_page ?? 1;
    const perPage = payload?.per_page ?? 20;
    return { items, total, currentPage, lastPage, perPage };
  },
  async createQuestion(payload) {
    const { data } = await api.post('/questions', payload);
    return unwrap(data);
  },
  async trash() {
    const { data } = await api.get('/user/my-questions/trash');
    return unwrapCollection(data);
  },
  async update(id, payload) {
    const { data } = await api.put(`/user/my-questions/${id}`, payload);
    return unwrap(data);
  },
  async remove(id) {
    const { data } = await api.delete(`/user/my-questions/${id}`);
    return data;
  },
  async restore(id) {
    const { data } = await api.post(`/user/my-questions/${id}/restore`);
    return unwrap(data);
  },
  async forceDelete(id) {
    const { data } = await api.delete(`/user/my-questions/${id}/force`);
    return data;
  },
  async submitToBank(id, payload = {}) {
    const { data } = await api.post(`/user/my-questions/${id}/submit-to-bank`, payload);
    return unwrap(data);
  },
  async bulkSubmitToBank(ids) {
    const { data } = await api.post('/user/my-questions/bulk-submit-to-bank', { ids });
    return unwrap(data);
  },
  async fetchReviewHistory(id) {
    const { data } = await api.get(`/user/my-questions/${id}/review-history`);
    return unwrap(data);
  }
};

export const adminBankRequestsApi = {
  async fetchRequests(params = {}) {
    const { data } = await api.get('/admin/question-bank-requests', { params });
    const payload = unwrap(data);
    const items = Array.isArray(payload?.items) ? payload.items : (Array.isArray(payload) ? payload : []);
    return {
      items,
      total: payload?.total ?? items.length,
      currentPage: payload?.current_page ?? 1,
      lastPage: payload?.last_page ?? 1,
      perPage: payload?.per_page ?? 15,
      stats: payload?.stats ?? { pending: 0, approved: 0, rejected: 0, total: 0 }
    };
  },
  async fetchRequestDetail(id) {
    const { data } = await api.get(`/admin/question-bank-requests/${id}`);
    return unwrap(data);
  },
  async approve(id) {
    const { data } = await api.post(`/admin/question-bank-requests/${id}/approve`);
    return unwrap(data);
  },
  async reject(id, payload = {}) {
    const { data } = await api.post(`/admin/question-bank-requests/${id}/reject`, payload);
    return unwrap(data);
  },
  async bulkApprove(ids) {
    const { data } = await api.post('/admin/question-bank-requests/bulk-approve', { ids });
    return unwrap(data);
  },
  async bulkReject(ids, note) {
    const { data } = await api.post('/admin/question-bank-requests/bulk-reject', { ids, note });
    return unwrap(data);
  }
};

export const adminQuestionsApi = {
  async list(params = {}) {
    const { data } = await api.get('/admin/questions-management', { params });
    const payload = unwrap(data);
    const items = Array.isArray(payload?.items) ? payload.items : (Array.isArray(payload) ? payload : []);
    return {
      items,
      total: payload?.total ?? items.length,
      currentPage: payload?.current_page ?? 1,
      lastPage: payload?.last_page ?? 1,
      perPage: payload?.per_page ?? 15,
      stats: payload?.stats ?? { total: 0, public: 0, private: 0, reported: 0 }
    };
  },

  async get(id) {
    const { data } = await api.get(`/admin/questions/${id}`);
    return unwrap(data);
  },

  async trash(params = {}) {
    const { data } = await api.get('/admin/questions-trash', { params });
    const payload = unwrap(data);
    const items = Array.isArray(payload?.items) 
      ? payload.items 
      : (Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []));
    return {
      items,
      total: payload?.total ?? items.length,
      currentPage: payload?.current_page ?? 1,
      lastPage: payload?.last_page ?? 1,
      perPage: payload?.per_page ?? 15,
      trashCount: payload?.trash_count ?? data?.trash_count ?? 0
    };
  },

  async restore(id) {
    const { data } = await api.post(`/admin/questions/${id}/restore`);
    return unwrap(data);
  },

  async forceDelete(id) {
    const { data } = await api.delete(`/admin/questions/${id}/force-delete`);
    return data;
  },

  async bulkRestore(question_ids) {
    const { data } = await api.post('/admin/questions/bulk-restore', { question_ids });
    return unwrap(data);
  },

  async bulkForceDelete(question_ids) {
    const { data } = await api.post('/admin/questions/bulk-force-delete', { question_ids });
    return unwrap(data);
  },

  async toggleVisibility(id) {
    const { data } = await api.patch(`/admin/questions/${id}/toggle-visibility`);
    return unwrap(data);
  },

  async bulkToggleVisibility(question_ids, is_public) {
    const { data } = await api.post('/admin/questions/bulk-visibility', { question_ids, is_public });
    return unwrap(data);
  },

  async bulkDelete(question_ids) {
    const { data } = await api.post('/admin/questions/bulk-delete', { question_ids });
    return unwrap(data);
  },

  async update(id, payload) {
    const { data } = await api.put(`/admin/questions/${id}`, payload);
    return unwrap(data);
  },

  async remove(id) {
    const { data } = await api.delete(`/admin/questions/${id}`);
    return unwrap(data);
  }
};

export const adminQuizzesApi = {
  async list(params = {}) {
    const { data } = await api.get('/admin/quizzes', { params });
    const payload = unwrap(data);
    const items = Array.isArray(payload?.items) 
      ? payload.items 
      : (Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []));
    return {
      items,
      total: payload?.total ?? items.length,
      currentPage: payload?.current_page ?? 1,
      lastPage: payload?.last_page ?? 1,
      perPage: payload?.per_page ?? 15,
      stats: payload?.stats ?? { total: 0, public: 0, private: 0, pending: 0, rejected: 0 }
    };
  },

  async get(id) {
    const { data } = await api.get(`/admin/quizzes/${id}`);
    return unwrap(data);
  },

  async toggleVisibility(id) {
    const { data } = await api.patch(`/admin/quizzes/${id}/toggle-visibility`);
    return unwrap(data);
  },

  async remove(id) {
    const { data } = await api.delete(`/admin/quizzes/${id}`);
    return unwrap(data);
  }
};


export const normalizeRole = (role) => {
  const value = String(role || "guest")
    .trim()
    .toLowerCase();
  return ["admin", "plus", "pro", "ultra", "free", "guest"].includes(value)
    ? value
    : "guest";
};

export const roleLabel = (role) =>
  ({
    admin: "Admin",
    plus: "Plus",
    pro: "Pro",
    ultra: "Ultra",
    free: "Free",
    guest: "Guest",
  })[normalizeRole(role)];

export const getDefaultRouteForRole = (role) => {
  const normalizedRole = normalizeRole(role);
  if (normalizedRole === "admin") return "/admin";
  if (USER_ROLES.includes(normalizedRole)) return "/";
  return "/";
};

export const getDashboardRouteForRole = (role) => {
  const normalizedRole = normalizeRole(role);
  if (normalizedRole === "admin") return "/admin";
  if (USER_ROLES.includes(normalizedRole)) return "/dashboard";
  return "/";
};

export const getQuizWorkspaceBaseForRole = (role) => {
  const normalizedRole = normalizeRole(role);
  return normalizedRole === "admin"
    ? "/admin/questions"
    : "/dashboard/questions";
};

export const hasAnyRole = (user, roles = []) => {
  if (!user || !Array.isArray(roles) || roles.length === 0) return true;
  const normalizedRole = normalizeRole(user.role);
  return roles.map(normalizeRole).includes(normalizedRole);
};

export const canUseWorkspace = (user) => hasAnyRole(user, AUTH_ROLES);
export const canUseUserDashboard = (user) => hasAnyRole(user, USER_ROLES);
export const canUseAdminConsole = (user) => hasAnyRole(user, ADMIN_ROLES);

const resolveAvatarUrl = (avatar) => {
  if (!avatar) return ''
  const url = String(avatar).trim()
  if (!url) return ''
  // If it's already a full URL, return as-is
  if (/^https?:\/\//.test(url)) return url
  // If it's a relative path starting with /storage, keep it (Vite proxy will handle)
  if (url.startsWith('/storage')) return url
  // If it's just a filename or relative path, prepend /storage/avatars/
  if (!url.includes('/')) return `/storage/avatars/${url}`
  return url
}

const normalizeUserForStorage = (user = {}) => {
  if (!user || typeof user !== "object") return null;

  const normalizedRole = normalizeRole(user.role);
  const normalized = {
    ...user,
    role: normalizedRole,
    role_label: user.role_label || user.roleLabel || roleLabel(normalizedRole),
    avatar: resolveAvatarUrl(user.avatar),
  };

  if (!normalized.name)
    normalized.name = normalized.email
      ? normalized.email.split("@")[0]
      : "Guest";
  if (!normalized.email) normalized.email = "";

  return normalized;
};

export const tokenStorage = {
  get() {
    return localStorage.getItem("quizflex_access_token");
  },
  set(token) {
    // lưu mã token JWT vào localStorage của trình duyệt
    localStorage.setItem("quizflex_access_token", token);
  },
  clear() {
    // xóa mã token JWT khỏi localStorage
    localStorage.removeItem("quizflex_access_token");
  },
};

export const currentUserStorage = {
  get() {
    if (!tokenStorage.get()) return null;

    try {
      const raw = localStorage.getItem("quizflex_current_user");
      return raw ? normalizeUserForStorage(JSON.parse(raw)) : null;
    } catch {
      return null;
    }
  },
  set(user) {
    // lưu thông tin user dưới khóa quizflex_current_user
    const normalized = normalizeUserForStorage(user);
    if (!normalized) {
      this.clear();
      return;
    }

    localStorage.setItem("quizflex_current_user", JSON.stringify(normalized));
    if (typeof window !== "undefined") {
      window.dispatchEvent(
        new CustomEvent("quizflex-user-updated", { detail: normalized }),
      );
    }
  },
  clear() {
    localStorage.removeItem("quizflex_current_user");
    if (typeof window !== "undefined") {
      window.dispatchEvent(
        new CustomEvent("quizflex-user-updated", { detail: null }),
      );
    }
  },
};

api.interceptors.request.use((config) => {
  const token = tokenStorage.get();
  if (token) {
    // Tự động đóng dấu chứng minh thư vào mọi request gửi đi
    config.headers.Authorization = `Bearer ${token}`; // gắn token vào header Authorization của mỗi request
  }
  return config;
});

let isRefreshing = false;
let failedQueue = [];

const processQueue = (error, token = null) => {
  failedQueue.forEach((prom) => {
    if (error) prom.reject(error);
    else prom.resolve(token);
  });
  failedQueue = [];
};

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config || {};
    const isAuthEndpoint = [
      "/auth/login",
      "/auth/refresh",
      "/auth/logout",
    ].some((path) => originalRequest.url?.includes(path));

    if (
      error.response?.status === 401 &&
      !originalRequest._retry &&
      !isAuthEndpoint
    ) {
      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          failedQueue.push({ resolve, reject });
        })
          .then((token) => {
            originalRequest.headers.Authorization = `Bearer ${token}`;
            return api(originalRequest);
          })
          .catch((err) => Promise.reject(err));
      }

      originalRequest._retry = true;
      isRefreshing = true;

      try {
        const token = tokenStorage.get();
        if (!token) throw new Error("No token");

        const { data } = await axios.post(
          `${API_BASE_URL}/auth/refresh`,
          {},
          {
            headers: { Authorization: `Bearer ${token}` },
          },
        );

        const newToken = data.token;
        tokenStorage.set(newToken);
        originalRequest.headers.Authorization = `Bearer ${newToken}`;

        processQueue(null, newToken);
        return api(originalRequest);
      } catch (err) {
        processQueue(err, null);
        if (
          err.response?.status === 401 ||
          err.response?.status === 403 ||
          !tokenStorage.get()
        ) {
          authApi.clearSession();
          window.location.href = "/login";
        }
        return Promise.reject(err);
      } finally {
        isRefreshing = false;
      }
    }

    const message =
      error.response?.data?.data?.error_message ||
      error.response?.data?.error_message ||
      error.response?.data?.message ||
      error.message ||
      "API request failed";

    if (
      error.response?.status === 403 &&
      message.toLowerCase().includes("kh\u00f3a")
    ) {
      window.dispatchEvent(new CustomEvent("quizflex-account-locked"));
    }

    return Promise.reject(new Error(message));
  },
);


const normalizeRoomCode = (value) =>
  String(value || "")
    .trim()
    .toUpperCase();

export const authApi = {
  async login(payload) {
    const { data } = await api.post("/auth/login", payload);
    const user = unwrap(data);
    if (data.token) tokenStorage.set(data.token);
    currentUserStorage.set(user);
    return currentUserStorage.get() || user;
  },

  async register(payload) {
    const { data } = await api.post("/auth/register", payload);
    return unwrap(data);
  },

  async verifyOtp(payload) {
    const { data } = await api.post("/auth/verify-otp", payload);
    return data;
  },

  async resendOtp(payload) {
    const { data } = await api.post("/auth/resend-otp", payload);
    return unwrap(data);
  },

  async forgotPasswordSendOtp(payload) {
    const { data } = await api.post("/auth/forgot-password/send-otp", payload);
    return data;
  },

  async forgotPasswordReset(payload) {
    const { data } = await api.post("/auth/forgot-password/reset", payload);
    return data;
  },

  async me() {
    const { data } = await api.get("/auth/me");
    const user = unwrap(data);
    currentUserStorage.set(user);
    return currentUserStorage.get() || user;
  },

  async lockedInfo() {
    const { data } = await api.get("/auth/locked-info");
    const info = unwrap(data);
    if (info && typeof info === "object") {
      const current = currentUserStorage.get();
      if (current) {
        currentUserStorage.set({
          ...current,
          is_locked: Boolean(info.is_locked),
          locked_reason: info.locked_reason ?? current.locked_reason,
          locked_at: info.locked_at ?? current.locked_at,
        });
      }
    }
    return info;
  },

  async updateProfile(payload = {}) {
    const formData = new FormData();
    Object.entries(payload).forEach(([key, value]) => {
      if (value === undefined || value === null || value === "") return;
      formData.append(
        key,
        typeof value === "boolean" ? (value ? "1" : "0") : value,
      );
    });

    const { data } = await api.post("/auth/profile", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    const user = unwrap(data);
    currentUserStorage.set(user);
    return currentUserStorage.get() || user;
  },

  clearSession() {
    tokenStorage.clear();
    currentUserStorage.clear();
  },

  async logout() {
    const token = tokenStorage.get();

    if (token) {
      try {
        await axios.post(
          `${API_BASE_URL}/auth/logout`,
          {},
          {
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: "application/json",
            },
          },
        );
      } catch {
        // Local logout must still succeed even when the token is expired or already blacklisted.
      }
    }

    this.clearSession();
  },

  getCurrentUser() {
    return currentUserStorage.get();
  },
};

export const usersApi = {
  async list(params = {}) {
    const { data } = await api.get("/users", { params });
    return unwrapCollection(data);
  },

  async get(id) {
    const { data } = await api.get(`/users/${id}`);
    return unwrap(data);
  },

  async create(payload) {
    const { data } = await api.post("/users", payload);
    return unwrap(data);
  },

  async update(id, payload) {
    const { data } = await api.put(`/users/${id}`, payload);
    return unwrap(data);
  },

  async remove(id) {
    const { data } = await api.delete(`/users/${id}`);
    return data;
  },

  async trash(params = {}) {
    const { data } = await api.get("/users/trashed", { params });
    return unwrapCollection(data);
  },

  async restore(id) {
    const { data } = await api.patch(`/users/${id}/restore`);
    return unwrap(data);
  },

  async forceDelete(id) {
    const { data } = await api.delete(`/users/${id}/force`);
    return data;
  },

  async lock(id, payload = {}) {
    const { data } = await api.post(`/admin/users/${id}/lock`, payload);
    return unwrap(data);
  },

  async unlock(id) {
    const { data } = await api.post(`/admin/users/${id}/unlock`);
    return unwrap(data);
  },
};

const isBrowserFile = (value) =>
  typeof File !== "undefined" && value instanceof File;

const payloadHasFile = (value) => {
  if (isBrowserFile(value)) return true;
  if (Array.isArray(value)) return value.some(payloadHasFile);
  if (value && typeof value === "object")
    return Object.values(value).some(payloadHasFile);
  return false;
};

const appendFormData = (formData, key, value) => {
  if (value === undefined || value === null) return;

  if (isBrowserFile(value)) {
    formData.append(key, value);
    return;
  }

  if (Array.isArray(value)) {
    value.forEach((item, index) =>
      appendFormData(formData, `${key}[${index}]`, item),
    );
    return;
  }

  if (typeof value === "object") {
    Object.entries(value).forEach(([childKey, childValue]) =>
      appendFormData(formData, `${key}[${childKey}]`, childValue),
    );
    return;
  }

  formData.append(
    key,
    typeof value === "boolean" ? (value ? "1" : "0") : value,
  );
};

const toFormData = (payload) => {
  const formData = new FormData();
  Object.entries(payload || {}).forEach(([key, value]) =>
    appendFormData(formData, key, value),
  );
  return formData;
};

const prepareQuizPayload = (payload) =>
  payloadHasFile(payload) ? toFormData(payload) : payload;

export const quizzesApi = {
  async list(params = {}) {
    const { data } = await api.get("/quizzes", { params });
    return unwrapCollection(data);
  },

  async fetchPaginated(params = {}) {
    const { data } = await api.get("/quizzes", { params });
    const payload = unwrap(data);
    const items = Array.isArray(payload) ? payload : (Array.isArray(payload?.data) ? payload.data : (payload?.items ?? []));
    const total = payload?.total ?? items.length;
    const currentPage = payload?.current_page ?? payload?.currentPage ?? 1;
    const lastPage = payload?.last_page ?? payload?.lastPage ?? 1;
    const perPage = payload?.per_page ?? payload?.perPage ?? 12;
    const from = payload?.from ?? (total > 0 ? (currentPage - 1) * perPage + 1 : 0);
    const to = payload?.to ?? Math.min(currentPage * perPage, total);
    return { items, total, currentPage, lastPage, perPage, from, to };
  },

  async adminList(params = {}) {
    const { data } = await api.get("/admin/quizzes", { params });
    const payload = unwrap(data);
    const items = Array.isArray(payload) ? payload : (Array.isArray(payload?.data) ? payload.data : (payload?.items ?? []));
    const total = payload?.total ?? items.length;
    const currentPage = payload?.current_page ?? payload?.currentPage ?? 1;
    const lastPage = payload?.last_page ?? payload?.lastPage ?? 1;
    const perPage = payload?.per_page ?? payload?.perPage ?? 10;
    const from = payload?.from ?? (total > 0 ? (currentPage - 1) * perPage + 1 : 0);
    const to = payload?.to ?? Math.min(currentPage * perPage, total);
    return { items, total, currentPage, lastPage, perPage, from, to };
  },

  async get(id) {
    const { data } = await api.get(`/quizzes/${id}`);
    return unwrap(data);
  },

  async getForEdit(id) {
    const { data } = await api.get(`/quizzes/${id}/edit-data`);
    return unwrap(data);
  },

  async create(payload) {
    const body = prepareQuizPayload(payload);
    const { data } = await api.post("/quizzes", body);
    return unwrap(data);
  },

  async update(id, payload) {
    const body = prepareQuizPayload(payload);

    if (body instanceof FormData) {
      body.append("_method", "PUT");

      const { data } = await api.post(`/quizzes/${id}`, body);

      return unwrap(data);
    }

    const { data } = await api.put(`/quizzes/${id}`, body);

    return unwrap(data);
  },

  // USER XÓA MỀM
  async remove(id) {
    const { data } = await api.delete(`/quizzes/${id}`);
    return data;
  },

  // USER + ADMIN xem thùng rác quiz của mình
  async trash() {
    const { data } = await api.get("/quizzes/trash");

    return unwrapCollection(data);
  },

  async restore(id) {
    const { data } = await api.patch(`/quizzes/${id}/restore`);

    return unwrap(data);
  },

  // ADMIN XÓA VĨNH VIỄN
  async forceDelete(id) {
    const { data } = await api.delete(`/quizzes/${id}/force-delete`);

    return data;
  },
  // ADMIN xem thùng rác quiz admin tạo
  async adminTrash() {
    const { data } = await api.get("/admin/quizzes/trash");
    return unwrapCollection(data);
  },

  // ADMIN ẨN / HIỆN
  async toggleVisibility(id) {
    const { data } = await api.patch(`/admin/quizzes/${id}/toggle-visibility`);

    return unwrap(data);
  },

  // BẮT ĐẦU LÀM QUIZ
  async startAttempt(id, payload = {}) {
    const { data } = await api.post(`/quizzes/${id}/attempts/start`, payload);

    return unwrap(data);
  },

  // KIỂM TRA ĐÚNG / SAI
  async checkAnswer(id, payload) {
    const { data } = await api.post(
      `/quizzes/${id}/attempts/check-answer`,
      payload
    );

    return unwrap(data);
  },

  // NỘP BÀI
  async submitAttempt(id, payload) {
    const { data } = await api.post(
      `/quizzes/${id}/attempts/submit`,
      payload
    );

    return unwrap(data);
  },
};

export const attemptsApi = {
  async list(params = {}) {
    const { data } = await api.get("/quiz-attempts", { params });
    return unwrapCollection(data);
  },

  async get(id) {
    const { data } = await api.get(`/quiz-attempts/${id}`);
    return unwrap(data);
  },
};

export const unlockRequestsApi = {
  async create(payload = {}) {
    const { data } = await api.post("/unlock-requests", payload);
    return unwrap(data);
  },

  async latest() {
    const { data } = await api.get("/unlock-requests/latest");
    return unwrap(data);
  },

  async adminList(params = {}) {
    const { data } = await api.get("/admin/unlock-requests", { params });
    return unwrap(data);
  },

  async adminGet(id) {
    const { data } = await api.get(`/admin/unlock-requests/${id}`);
    return unwrap(data);
  },

  async pendingCount() {
    const { data } = await api.get("/admin/unlock-requests/pending-count");
    return unwrap(data);
  },

  async approve(id, payload = {}) {
    const { data } = await api.post(
      `/admin/unlock-requests/${id}/approve`,
      payload,
    );
    return unwrap(data);
  },

  async reject(id, payload = {}) {
    const { data } = await api.post(
      `/admin/unlock-requests/${id}/reject`,
      payload,
    );
    return unwrap(data);
  },
};

export const adminDashboardApi = {
  async overview() {
    return withMemoryCache(
      "admin_dashboard_overview",
      async () => {
        const { data } = await api.get("/admin/dashboard/overview");
        return unwrap(data);
      },
      15000,
    );
  },
};

export const adminRoomApi = {
  async getRoomStats() {
    const { data } = await api.get("/admin/rooms/stats");
    return unwrap(data);
  },

  async getHomeworkRooms(params = {}) {
    const { data } = await api.get("/admin/rooms/homework", { params });
    return unwrap(data);
  },

  async getHomeworkRoomsTrash(params = {}) {
    const { data } = await api.get("/admin/rooms/homework/trash", { params });
    return unwrap(data);
  },

  async getHomeworkRoomDetail(id) {
    const { data } = await api.get(`/admin/rooms/homework/${id}`);
    return unwrap(data);
  },

  async softDeleteHomeworkRoom(id) {
    const { data } = await api.delete(`/admin/rooms/homework/${id}`);
    return data;
  },

  async restoreHomeworkRoom(id) {
    const { data } = await api.patch(`/admin/rooms/homework/${id}/restore`);
    return unwrap(data);
  },

  async removeHomeworkRoomMember(roomId, memberId) {
    const { data } = await api.delete(
      `/admin/rooms/homework/${roomId}/members/${memberId}`,
    );
    return unwrap(data);
  },

  async getLiveRooms(params = {}) {
    const { data } = await api.get("/admin/rooms/live", { params });
    return unwrap(data);
  },

  async getLiveRoomsTrash(params = {}) {
    const { data } = await api.get("/admin/rooms/live/trash", { params });
    return unwrap(data);
  },

  async getLiveRoomDetail(id) {
    const { data } = await api.get(`/admin/rooms/live/${id}`);
    return unwrap(data);
  },

  async softDeleteLiveRoom(id) {
    const { data } = await api.delete(`/admin/rooms/live/${id}`);
    return data;
  },

  async restoreLiveRoom(id) {
    const { data } = await api.patch(`/admin/rooms/live/${id}/restore`);
    return unwrap(data);
  },

  async banHomeworkRoom(id) {
    const { data } = await api.post(`/admin/rooms/homework/${id}/ban`);
    return unwrap(data);
  },

  async unbanHomeworkRoom(id) {
    const { data } = await api.post(`/admin/rooms/homework/${id}/unban`);
    return unwrap(data);
  },

  async banLiveRoom(id) {
    const { data } = await api.post(`/admin/rooms/live/${id}/ban`);
    return unwrap(data);
  },

  async unbanLiveRoom(id) {
    const { data } = await api.post(`/admin/rooms/live/${id}/unban`);
    return unwrap(data);
  },

  async forceDeleteHomework(id) {
    const { data } = await api.delete(`/admin/rooms/homework/${id}/force`);
    return data;
  },

  async forceDeleteLiveRoom(id) {
    const { data } = await api.delete(`/admin/rooms/live/${id}/force`);
    return data;
  },
};

export const adminRoomsApi = {
  getStats: adminRoomApi.getRoomStats,
  listHomework: adminRoomApi.getHomeworkRooms,
  getHomework: adminRoomApi.getHomeworkRoomDetail,
  softDeleteHomework: adminRoomApi.softDeleteHomeworkRoom,
  removeHomeworkMember: adminRoomApi.removeHomeworkRoomMember,
  banHomework: adminRoomApi.banHomeworkRoom,
  unbanHomework: adminRoomApi.unbanHomeworkRoom,
  forceDeleteHomework: adminRoomApi.forceDeleteHomework,
  listLive: adminRoomApi.getLiveRooms,
  getLive: adminRoomApi.getLiveRoomDetail,
  softDeleteLive: adminRoomApi.softDeleteLiveRoom,
  banLive: adminRoomApi.banLiveRoom,
  unbanLive: adminRoomApi.unbanLiveRoom,
  forceDeleteLive: adminRoomApi.forceDeleteLiveRoom,
};

export const aiQuizApi = {
  suggest(payload) {
    return api.post("/orc/ai/quiz-suggestions", payload);
  },
  review(payload) {
    return api.post("/orc/ai/review", payload);
  },
};

export const homeworkApi = {
  async getHomeworkRooms(params = {}) {
    const cacheKey = `homework_rooms_${JSON.stringify(params)}`;
    return withMemoryCache(
      cacheKey,
      async () => {
        const { data } = await api.get("/rooms", { params });
        return unwrapCollection(data);
      },
      15000,
    );
  },

  async createHomeworkRoom(payload) {
    clearMemoryCache("homework_rooms");
    const { data } = await api.post("/rooms", payload);
    return unwrap(data);
  },

  async updateHomeworkRoom(roomId, payload) {
    clearMemoryCache("homework_rooms");
    const { data } = await api.patch(`/rooms/${roomId}`, payload);
    return unwrap(data);
  },

  async joinHomeworkRoom(code) {
    clearMemoryCache("homework_rooms");
    const { data } = await api.post("/rooms/join", { code });
    return unwrap(data);
  },

  async leaveHomeworkRoom(roomId) {
    clearMemoryCache("homework_rooms");
    const { data } = await api.post(`/rooms/${roomId}/leave`);
    return unwrap(data);
  },

  async getHomeworkRoom(roomId) {
    const { data } = await api.get(`/rooms/${roomId}`);
    return unwrap(data);
  },

  async getRoomMembers(roomId, params = {}) {
    const { data } = await api.get(`/rooms/${roomId}/members`, { params });
    return unwrapCollection(data);
  },

  async removeRoomMember(roomId, memberId) {
    const { data } = await api.delete(`/rooms/${roomId}/members/${memberId}`);
    return unwrap(data);
  },

  async getMemberEvaluation(roomId, userId) {
    const { data } = await api.get(
      `/homework-rooms/${roomId}/members/${userId}/evaluation`,
    );
    return unwrap(data);
  },

  async saveMemberEvaluation(roomId, userId, payload) {
    const { data } = await api.post(
      `/homework-rooms/${roomId}/members/${userId}/evaluation`,
      payload,
    );
    return unwrap(data);
  },

  async getSubmissionEvaluation(roomId, submissionId) {
    const { data } = await api.get(
      `/homework-rooms/${roomId}/submissions/${submissionId}/evaluation`,
    );
    return unwrap(data);
  },

  async saveSubmissionEvaluation(roomId, submissionId, payload) {
    const { data } = await api.post(
      `/homework-rooms/${roomId}/submissions/${submissionId}/evaluation`,
      payload,
    );
    return unwrap(data);
  },

  async getAllowedMembers(roomId) {
    const { data } = await api.get(`/homework-rooms/${roomId}/allowed-members`);
    return unwrapCollection(data);
  },

  async addAllowedMembers(roomId, emails) {
    const payload = Array.isArray(emails) ? { emails } : { email: emails };
    const { data } = await api.post(
      `/homework-rooms/${roomId}/allowed-members`,
      payload,
    );
    return unwrapCollection(data);
  },

  async removeAllowedMember(roomId, allowedMemberId) {
    const { data } = await api.delete(
      `/homework-rooms/${roomId}/allowed-members/${allowedMemberId}`,
    );
    return data;
  },

  async removeAllowedMembersBatch(roomId, ids) {
    const { data } = await api.delete(
      `/homework-rooms/${roomId}/allowed-members`,
      {
        data: { ids },
      },
    );
    return data;
  },

  async clearAllowedMembers(roomId) {
    const { data } = await api.delete(
      `/homework-rooms/${roomId}/allowed-members`,
      {
        data: { clear_all: true },
      },
    );
    return data;
  },

  async getRoomAssignments(roomId) {
    const { data } = await api.get(`/rooms/${roomId}/assignments`);
    return unwrapCollection(data);
  },

  async createRoomAssignment(roomId, payload) {
    const { data } = await api.post(`/rooms/${roomId}/assignments`, payload);
    return unwrap(data);
  },

  async getRoomAssignment(assignmentId) {
    const { data } = await api.get(`/room-assignments/${assignmentId}`);
    return unwrap(data);
  },

  async getRoomAssignmentAttempts(assignmentId) {
    const { data } = await api.get(
      `/room-assignments/${assignmentId}/attempts`,
    );
    return unwrapCollection(data);
  },

  async startAssignmentAttempt(firstArg, secondArg) {
    const assignmentId = secondArg !== undefined && secondArg !== null ? secondArg : firstArg;
    const payload = typeof secondArg === 'object' && secondArg !== null ? secondArg : {};
    const { data } = await api.post(
      `/room-assignments/${assignmentId}/attempts/start`,
      payload,
    );
    return unwrap(data);
  },

  async startRoomAssignmentAttempt(assignmentId, payload = {}) {
    return this.startAssignmentAttempt(assignmentId, payload);
  },

  async answerRoomAssignmentAttempt(assignmentId, attemptId, payload) {
    const { data } = await api.post(
      `/room-assignments/${assignmentId}/attempts/${attemptId}/answer`,
      payload,
    );
    return unwrap(data);
  },

  async submitAssignmentAttempt(firstArg, secondArg, thirdArg) {
    let assignmentId;
    let attemptId;
    let payload;

    if (thirdArg !== undefined) {
      if (typeof thirdArg === 'object' && thirdArg?.attempt_id) {
        // Form: submitAssignmentAttempt(roomId, assignmentId, { attempt_id, answers })
        assignmentId = secondArg;
        attemptId = thirdArg.attempt_id;
        payload = { answers: thirdArg.answers || {} };
      } else {
        // Form: submitAssignmentAttempt(assignmentId, attemptId, payload)
        assignmentId = firstArg;
        attemptId = secondArg;
        payload = thirdArg;
      }
    } else if (secondArg !== undefined && typeof secondArg === 'object' && secondArg?.attempt_id) {
      // Form: submitAssignmentAttempt(assignmentId, { attempt_id, answers })
      assignmentId = firstArg;
      attemptId = secondArg.attempt_id;
      payload = { answers: secondArg.answers || {} };
    } else {
      assignmentId = firstArg;
      attemptId = secondArg;
      payload = {};
    }

    const body = payload?.answers ? payload : { answers: payload };
    const { data } = await api.post(
      `/room-assignments/${assignmentId}/attempts/${attemptId}/submit`,
      body,
    );
    return unwrap(data);
  },

  async submitRoomAssignmentAttempt(assignmentId, attemptId, payload) {
    return this.submitAssignmentAttempt(assignmentId, attemptId, payload);
  },

  async resetRoomAssignmentAttempt(assignmentId, attemptId) {
    const { data } = await api.post(
      `/room-assignments/${assignmentId}/attempts/${attemptId}/reset`,
    );
    return unwrap(data);
  },

  async fetchRoomGradebook(roomId) {
    const { data } = await api.get(`/rooms/${roomId}/gradebook`);
    return unwrap(data);
  },

  async approveRoomMember(roomId, memberId) {
    const { data } = await api.post(
      `/rooms/${roomId}/members/${memberId}/approve`,
    );
    return unwrap(data);
  },

  async rejectRoomMember(roomId, memberId) {
    const { data } = await api.post(
      `/rooms/${roomId}/members/${memberId}/reject`,
    );
    return unwrap(data);
  },

  async dissolveHomeworkRoom(roomId) {
    const { data } = await api.delete(`/rooms/${roomId}/dissolve`);
    return data;
  },
};

export const liveRoomApi = {
  async createLiveRoom(payload) {
    const { data } = await api.post("/live-rooms", payload);
    return unwrap(data);
  },

  async joinLiveRoom(code) {
    const payload =
      typeof code === "object"
        ? { ...code, code: normalizeRoomCode(code.code || code.room_code) }
        : { code: normalizeRoomCode(code) };
    const { data } = await api.post("/live-rooms/join", payload);
    return unwrap(data);
  },

  async getLiveRoom(id) {
    const { data } = await api.get(`/live-rooms/${id}`);
    return unwrap(data);
  },

  async startLiveRoom(id) {
    const { data } = await api.post(`/live-rooms/${id}/start`);
    return unwrap(data);
  },

  async getLiveCurrentQuestion(id) {
    const { data } = await api.get(`/live-rooms/${id}/current-question`);
    return unwrap(data);
  },

  async answerLiveQuestion(id, answerId) {
    const payload =
      typeof answerId === "object" ? answerId : { answer_id: answerId };
    const { data } = await api.post(`/live-rooms/${id}/answer`, payload);
    return unwrap(data);
  },

  async nextLiveQuestion(id) {
    const { data } = await api.post(`/live-rooms/${id}/next-question`);
    return unwrap(data);
  },

  async finishLiveRoom(id) {
    const { data } = await api.post(`/live-rooms/${id}/finish`);
    return unwrap(data);
  },

  async getLiveLeaderboard(id) {
    const { data } = await api.get(`/live-rooms/${id}/leaderboard`);
    return unwrapCollection(data);
  },
};

export const gamificationApi = {
  async getUserStats() {
    const { data } = await api.get("/user/stats");
    return unwrap(data);
  },
  async getBadges() {
    const { data } = await api.get("/badges");
    return unwrapCollection(data);
  },
  async getLeaderboard() {
    return withMemoryCache(
      "leaderboard",
      async () => {
        const { data } = await api.get("/leaderboard");
        return unwrapCollection(data);
      },
      15000,
    );
  },
};

export const ocrApi = {
  async scan(file, mode) {
    const formData = new FormData();
    formData.append("image", file);
    formData.append("mode", mode);

    const { data } = await api.post("/ocr/scan", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });

    return data;
  },
};

export const aiApi = {
  async generate(payload) {
    const { data } = await api.post("/ai/generate", payload);
    return unwrap(data);
  },

  async getJob(jobId) {
    const { data } = await api.get(`/ai/jobs/${jobId}`);
    return unwrap(data);
  },
};

export const difficultyLabel = (value) =>
  ({
    easy: "Dễ",
    medium: "Vừa",
    hard: "Khó",
    Dễ: "Dễ",
    Vừa: "Vừa",
    Khó: "Khó",
  })[value] || "Vừa";

export const difficultyValue = (value) =>
  ({
    Dễ: "easy",
    Vừa: "medium",
    Khó: "hard",
    easy: "easy",
    medium: "medium",
    hard: "hard",
  })[value] || "medium";

export const defaultQuizCover = "linear-gradient(135deg, #0f172a, #7c3aed)";

const resolveCoverUrl = (cover) => {
  if (!cover) return ''
  const url = String(cover).trim()
  if (!url) return ''
  // If it's already a full URL, return as-is
  if (/^https?:\/\//.test(url)) return url
  // If it's a relative path starting with /storage, keep it (Vite proxy will handle)
  if (url.startsWith('/storage')) return url
  // If it's just a filename or relative path, prepend /storage/quiz-covers/
  if (!url.includes('/')) return `/storage/quiz-covers/${url}`
  return url
}

export const coverToBackground = (cover) => {
  const value = String(cover || "").trim();
  if (!value) return defaultQuizCover;
  if (/gradient\(/i.test(value)) return value;

  const resolved = resolveCoverUrl(value);
  const isImageSource = /^(https?:\/\/|\/storage\/|data:image\/|blob:)/i.test(resolved);
  if (!isImageSource) return value;

  const escaped = resolved.replace(/"/g, '\\"');
  return `linear-gradient(135deg, rgba(15,23,42,.2), rgba(124,58,237,.24)), url("${escaped}") center / cover no-repeat`;
};

export const normalizeQuizCard = (quiz) => {
  const coverUrl = resolveCoverUrl(quiz.cover);
  return {
    ...quiz,
    subject_id: quiz.subject_id ?? quiz.subject?.id ?? null,
    subject_name: quiz.subject_name ?? quiz.subject?.name ?? "",
    topic_name: quiz.topic_name ?? "",
    roomCode: quiz.room_code || "",
    duration: `${quiz.duration_minutes || Math.ceil((quiz.time_limit_seconds || 600) / 60)} phút`,
    questions:
      quiz.questions_count ??
      (Array.isArray(quiz.questions) ? quiz.questions.length : 0),
    attempts: quiz.attempts_count ?? 0,
    avgScore: Math.round(Number(quiz.avg_score ?? quiz.score_percent ?? 0)),
    rating: quiz.rating || "4.8",
    coverSource: coverUrl,
    cover: coverToBackground(coverUrl),
    icon: quiz.icon || "QZ",
    badge: quiz.badge || "QUIZ",
    author: quiz.author || quiz.user?.name || "QuizFlex",
    visibility: quiz.visibility || (quiz.is_public ? "public" : "private"),
    difficulty: difficultyLabel(quiz.difficulty_label || quiz.difficulty),
    rawDifficulty: difficultyValue(quiz.difficulty),
  };
};

const resolveUserAvatar = (avatar) => {
  if (!avatar) return ''
  const url = String(avatar).trim()
  if (!url) return ''
  if (/^https?:\/\//.test(url)) return url
  if (url.startsWith('/storage')) return url
  if (!url.includes('/')) return `/storage/avatars/${url}`
  return url
}

export const normalizeUser = (user) => {
  const normRole = normalizeRole(user?.role);
  return {
    ...user,
    avatar: resolveAvatarUrl(user?.avatar),
    role: normRole,
    roleLabel: user?.role_label || user?.roleLabel || roleLabel(normRole),
    joinedAt:
      user?.joined_at || user?.created_at
        ? new Date(user.joined_at || user.created_at).toLocaleDateString(
            "vi-VN",
          )
        : "Chưa rõ",
    aiQuota: user?.ai_quota_remaining ?? 0,
    quizzesCount: user?.quizzes_count ?? 0,
    attemptsCount: user?.attempts_count ?? 0,
    status: user?.status || "active",
  };
};

export const normalizeQuestion = (question) => ({
  id: question.id,
  question: question.text || question.content,
  category: question.category || "Quiz",
  difficulty: difficultyLabel(question.difficulty || "medium"),
  type: question.type || "single_choice",
  points: question.points ?? 10,
  correct:
    question.answers?.find((answer) => answer.is_correct)?.answer_key ||
    question.answers?.find((answer) => answer.is_correct)?.key ||
    "",
  answers: (question.answers || []).map((answer, index) => ({
    id: answer.id,
    key: answer.answer_key || answer.key || String.fromCharCode(65 + index),
    text: answer.text || answer.content,
    isCorrect: Boolean(answer.is_correct),
  })),
});

export const buildEditorDraftFromQuiz = (quiz = {}) => ({
  title: quiz.title || "",
  tag: quiz.tag || "",
  description: quiz.description || "",
  category: quiz.category || "AI",
  difficulty: difficultyValue(quiz.difficulty),
  visibility:
    quiz.visibility ||
    (quiz.is_public ? "public" : quiz.room_code ? "group" : "private"),
  roomCode: quiz.room_code || "",
  durationMinutes:
    quiz.duration_minutes || Math.ceil((quiz.time_limit_seconds || 600) / 60),
  questions: (quiz.questions || []).map((question) => {
    const normalized = normalizeQuestion(question);
    return {
      id: normalized.id,
      text: normalized.question,
      correct: normalized.correct || "A",
      points: normalized.points || 1,
      answers: normalized.answers.map((answer) => ({
        id: answer.id,
        key: answer.key,
        text: answer.text,
      })),
    };
  }),
});

export const formatSeconds = (seconds = 0) => {
  const safeSeconds = Math.max(0, Number(seconds) || 0);
  const minutes = Math.floor(safeSeconds / 60);
  const remainingSeconds = safeSeconds % 60;
  return `${String(minutes).padStart(2, "0")}:${String(remainingSeconds).padStart(2, "0")}`;
};

export const paymentsApi = {
  async create(payload) {
    const { data } = await api.post("/payments/create", payload);
    return data;
  },

  async callback(params) {
    const { data } = await api.get("/payments/callback", { params });
    return data;
  },

  async checkStatus(orderCode) {
    const { data } = await api.get(`/payments/check-status/${orderCode}`);
    return data;
  },

  async activateTrial() {
    const { data } = await api.post("/payments/activate-trial");
    return data;
  },

  async history() {
    const { data } = await api.get("/payments/history");
    return data;
  },

  async getUpgradeCosts() {
    const { data } = await api.get("/payments/upgrade-costs");
    return data;
  },
};

export const reportApi = {
  // Dành cho Client: Gửi báo cáo Question
  async createQuestionReport({ question_id, reason, description = '' }) {
    const { data } = await api.post("/report-tickets", {
      question_id,
      reason,
      description,
    });
    return unwrap(data);
  },

  // Alias create tương thích ngược
  async create(payload) {
    const { data } = await api.post("/report-tickets", payload);
    return unwrap(data);
  },

  // Dành cho Admin: Lấy danh sách audit log các báo cáo
  async listAdmin(params = {}) {
    const { data } = await api.get("/admin/report-tickets", { params });
    const payload = unwrap(data);
    const items = Array.isArray(payload) ? payload : (Array.isArray(payload?.data) ? payload.data : []);
    return {
      items,
      stats: payload?.stats ?? data?.stats ?? { total: 0, pending: 0, resolved: 0, dismissed: 0, questions_count: 0 },
      total: items.length
    };
  },

  async get(id) {
    const { data } = await api.get(`/admin/report-tickets/${id}`);
    return unwrap(data);
  },

  async updateStatus(id, payload = {}) {
    const { data } = await api.patch(`/admin/report-tickets/${id}/status`, payload);
    return unwrap(data);
  },

  async resolveQuestionReports(payload = {}) {
    const { data } = await api.post('/admin/report-tickets/resolve-question', payload);
    return unwrap(data);
  },

  async countPending() {
    const { data } = await api.get('/admin/report-tickets/count');
    return unwrap(data);
  }
};


export const notificationApi = {
  async list(params) {
    const { data } = await api.get("/notifications", { params });
    const items = unwrapCollection(data);
    const unreadCount = data?.unread_count ?? 0;

    const innerData = data?.data;
    const pagination = innerData?.current_page
      ? {
          currentPage: innerData.current_page,
          lastPage: innerData.last_page,
          total: innerData.total,
          perPage: innerData.per_page,
          nextPageUrl: innerData.next_page_url,
        }
      : null;

    return {
      items,
      unreadCount,
      pagination,
    };
  },

  async markAsRead(id) {
    const { data } = await api.put(`/notifications/${id}/read`);
    return data;
  },

  async markAllAsRead() {
    const { data } = await api.put("/notifications/read-all");
    return data;
  },

  async deleteAll() {
    const { data } = await api.delete("/notifications");
    return data;
  },
};

export const adminSubjectsApi = {
  async list(params = {}) {
    const { data } = await api.get("/admin/subjects", { params });
    return data?.data || { subjects: [], stats: { total: 0, trashed: 0 } };
  },

  async trash() {
    const { data } = await api.get("/admin/subjects/trash");
    return unwrapCollection(data);
  },

  async create(payload) {
    const { data } = await api.post("/admin/subjects", payload);
    return unwrap(data);
  },

  async get(id) {
    const { data } = await api.get(`/admin/subjects/${id}`);
    return unwrap(data);
  },

  async update(id, payload) {
    const { data } = await api.put(`/admin/subjects/${id}`, payload);
    return unwrap(data);
  },

  async softDelete(id) {
    const { data } = await api.delete(`/admin/subjects/${id}`);
    return data;
  },

  async restore(id) {
    const { data } = await api.post(`/admin/subjects/${id}/restore`);
    return unwrap(data);
  },

  async forceDelete(id) {
    const { data } = await api.delete(`/admin/subjects/${id}/force-delete`);
    return data;
  },
};

export const formatApiErrorMessage = (error, defaultMessage = "Có lỗi xảy ra, vui lòng kiểm tra lại.") => {
  if (!error) return defaultMessage;

  const data = error.response?.data;
  if (!data) {
    if (error.message && error.message.includes("Network Error")) {
      return "Không thể kết nối đến máy chủ. Vui lòng kiểm tra kết nối mạng.";
    }
    return error.message || defaultMessage;
  }

  if (data.errors && typeof data.errors === "object") {
    const messages = [];

    Object.entries(data.errors).forEach(([field, errList]) => {
      const fieldMsg = Array.isArray(errList) ? errList[0] : String(errList);

      let formattedField = field;
      const qMatch = field.match(/questions\.(\d+)\.answers\.(\d+)/);
      const qOnlyMatch = field.match(/questions\.(\d+)/);
      const aMatch = field.match(/answers\.(\d+)/);

      if (qMatch) {
        const qIndex = Number(qMatch[1]) + 1;
        const aIndex = String.fromCharCode(65 + Number(qMatch[2]));
        formattedField = `Câu ${qIndex} (Đáp án ${aIndex})`;
      } else if (aMatch) {
        const aIndex = String.fromCharCode(65 + Number(aMatch[1]));
        formattedField = `Đáp án ${aIndex}`;
      } else if (qOnlyMatch) {
        const qIndex = Number(qOnlyMatch[1]) + 1;
        formattedField = `Câu hỏi số ${qIndex}`;
      } else {
        const fieldNameMap = {
          title: "Tiêu đề bài Quiz",
          subject_id: "Bộ môn",
          education_level_id: "Cấp học",
          grade_id: "Khối lớp",
          duration_minutes: "Thời gian làm bài",
          content: "Nội dung câu hỏi",
          answers: "Danh sách đáp án",
        };
        formattedField = fieldNameMap[field] || field;
      }

      let cleanMsg = fieldMsg
        .replace(/The (.+) field is required\./gi, `Vui lòng nhập ${formattedField}.`)
        .replace(/The (.+) must be a string\./gi, `${formattedField} phải là chuỗi ký tự.`)
        .replace(/The (.+) must be an integer\./gi, `${formattedField} phải là số nguyên.`)
        .replace(/The selected (.+) is invalid\./gi, `${formattedField} không hợp lệ.`)
        .replace(/\(and \d+ more errors?\)/gi, "");

      if (cleanMsg === fieldMsg && !fieldMsg.includes("Vui lòng")) {
        cleanMsg = `${formattedField}: ${fieldMsg}`;
      }

      messages.push(cleanMsg.trim());
    });

    if (messages.length > 0) {
      return messages.join("\n");
    }
  }

  if (data.message && typeof data.message === "string") {
    let msg = data.message;
    msg = msg.replace(/The questions\.(\d+)\.answers\.(\d+)\.content field is required\./gi, (m, q, a) => {
      return `Câu ${Number(q) + 1} (Đáp án ${String.fromCharCode(65 + Number(a))}): Chưa nhập nội dung.`;
    });
    msg = msg.replace(/The answers\.(\d+)\.content field is required\./gi, (m, a) => {
      return `Đáp án ${String.fromCharCode(65 + Number(a))}: Chưa nhập nội dung.`;
    });
    msg = msg.replace(/The (.+) field is required\./gi, "Trường thông tin bị thiếu.");
    msg = msg.replace(/\(and \d+ more errors?\)/gi, "").trim();

    if (msg.toLowerCase().includes("field is required") || msg.toLowerCase().includes("errors")) {
      return "Dữ liệu nhập vào chưa đầy đủ. Vui lòng kiểm tra lại tiêu đề, nội dung câu hỏi và các đáp án.";
    }

    return msg;
  }

  return defaultMessage;
};

export default api;

