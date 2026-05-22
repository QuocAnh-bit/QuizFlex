import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api'
const AUTH_ROLES = ['admin', 'vip', 'user']
const USER_ROLES = ['vip', 'user']
const ADMIN_ROLES = ['admin']

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    Accept: 'application/json',
  },
})

export const normalizeRole = (role) => {
  const value = String(role || 'guest').trim().toLowerCase()
  return ['admin', 'vip', 'user', 'guest'].includes(value) ? value : 'guest'
}

export const roleLabel = (role) => ({
  admin: 'Admin',
  vip: 'VIP',
  user: 'Thường',
  guest: 'Guest',
}[normalizeRole(role)])

export const getDefaultRouteForRole = (role) => {
  const normalizedRole = normalizeRole(role)
  if (normalizedRole === 'admin') return '/admin'
  if (USER_ROLES.includes(normalizedRole)) return '/'
  return '/'
}

export const getDashboardRouteForRole = (role) => {
  const normalizedRole = normalizeRole(role)
  if (normalizedRole === 'admin') return '/admin'
  if (USER_ROLES.includes(normalizedRole)) return '/dashboard'
  return '/'
}

export const getQuizWorkspaceBaseForRole = (role) => {
  const normalizedRole = normalizeRole(role)
  return normalizedRole === 'admin' ? '/admin/questions' : '/dashboard/questions'
}

export const hasAnyRole = (user, roles = []) => {
  if (!user || !Array.isArray(roles) || roles.length === 0) return true
  const normalizedRole = normalizeRole(user.role)
  return roles.map(normalizeRole).includes(normalizedRole)
}

export const canUseWorkspace = (user) => hasAnyRole(user, AUTH_ROLES)
export const canUseUserDashboard = (user) => hasAnyRole(user, USER_ROLES)
export const canUseAdminConsole = (user) => hasAnyRole(user, ADMIN_ROLES)

const normalizeUserForStorage = (user = {}) => {
  if (!user || typeof user !== 'object') return null

  const normalizedRole = normalizeRole(user.role)
  const normalized = {
    ...user,
    role: normalizedRole,
    role_label: user.role_label || user.roleLabel || roleLabel(normalizedRole),
  }

  if (!normalized.name) normalized.name = normalized.email ? normalized.email.split('@')[0] : 'Guest'
  if (!normalized.email) normalized.email = ''

  return normalized
}

export const tokenStorage = {
  get() {
    return localStorage.getItem('quizflex_access_token')
  },
  set(token) {
    if (!token) return
    localStorage.setItem('quizflex_access_token', token)
  },
  clear() {
    localStorage.removeItem('quizflex_access_token')
  },
}

export const currentUserStorage = {
  get() {
    if (!tokenStorage.get()) return null

    try {
      const raw = localStorage.getItem('quizflex_current_user')
      return raw ? normalizeUserForStorage(JSON.parse(raw)) : null
    } catch {
      return null
    }
  },
  set(user) {
    const normalized = normalizeUserForStorage(user)
    if (!normalized) {
      this.clear()
      return
    }

    localStorage.setItem('quizflex_current_user', JSON.stringify(normalized))
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('quizflex-user-updated', { detail: normalized }))
    }
  },
  clear() {
    localStorage.removeItem('quizflex_current_user')
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('quizflex-user-updated', { detail: null }))
    }
  },
}

api.interceptors.request.use((config) => {
  const token = tokenStorage.get()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

let isRefreshing = false
let failedQueue = []

const processQueue = (error, token = null) => {
  failedQueue.forEach((prom) => {
    if (error) prom.reject(error)
    else prom.resolve(token)
  })
  failedQueue = []
}

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config || {}
    const isAuthEndpoint = ['/auth/login', '/auth/refresh', '/auth/logout'].some((path) => originalRequest.url?.includes(path))

    if (error.response?.status === 401 && !originalRequest._retry && !isAuthEndpoint) {
      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          failedQueue.push({ resolve, reject })
        }).then((token) => {
          originalRequest.headers.Authorization = `Bearer ${token}`
          return api(originalRequest)
        }).catch((err) => Promise.reject(err))
      }

      originalRequest._retry = true
      isRefreshing = true

      try {
        const token = tokenStorage.get()
        if (!token) throw new Error('No token')

        const { data } = await axios.post(`${API_BASE_URL}/auth/refresh`, {}, {
          headers: { Authorization: `Bearer ${token}` },
        })

        const newToken = data.token
        tokenStorage.set(newToken)
        originalRequest.headers.Authorization = `Bearer ${newToken}`

        processQueue(null, newToken)
        return api(originalRequest)
      } catch (err) {
        processQueue(err, null)
        authApi.clearSession()
        window.location.href = '/login'
        return Promise.reject(err)
      } finally {
        isRefreshing = false
      }
    }

    const message = error.response?.data?.data?.error_message || error.response?.data?.error_message || error.response?.data?.message || error.message || 'API request failed'
    return Promise.reject(new Error(message))
  },
)

const unwrap = (payload) => payload?.data ?? payload
const unwrapCollection = (payload) => {
  const body = unwrap(payload)
  if (Array.isArray(body)) return body
  if (Array.isArray(body?.data)) return body.data
  return []
}

export const authApi = {
  async login(payload) {
    const { data } = await api.post('/auth/login', payload)
    const user = unwrap(data)
    if (data.token) tokenStorage.set(data.token)
    currentUserStorage.set(user)
    return currentUserStorage.get() || user
  },

  async register(payload) {
    const { data } = await api.post('/auth/register', payload)
    return unwrap(data)
  },

  async me() {
    const { data } = await api.get('/auth/me')
    const user = unwrap(data)
    currentUserStorage.set(user)
    return currentUserStorage.get() || user
  },

  async updateProfile(payload = {}) {
    const formData = new FormData()
    Object.entries(payload).forEach(([key, value]) => {
      if (value === undefined || value === null || value === '') return
      formData.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : value)
    })

    const { data } = await api.post('/auth/profile', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    const user = unwrap(data)
    currentUserStorage.set(user)
    return currentUserStorage.get() || user
  },

  clearSession() {
    tokenStorage.clear()
    currentUserStorage.clear()
  },

  async logout() {
    const token = tokenStorage.get()

    if (token) {
      try {
        await axios.post(`${API_BASE_URL}/auth/logout`, {}, {
          headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
        })
      } catch {
        // Local logout must still succeed even when the token is expired or already blacklisted.
      }
    }

    this.clearSession()
  },
}

export const usersApi = {
  async list(params = {}) {
    const { data } = await api.get('/users', { params })
    return unwrapCollection(data)
  },

  async get(id) {
    const { data } = await api.get(`/users/${id}`)
    return unwrap(data)
  },

  async create(payload) {
    const { data } = await api.post('/users', payload)
    return unwrap(data)
  },

  async update(id, payload) {
    const { data } = await api.put(`/users/${id}`, payload)
    return unwrap(data)
  },

  async remove(id) {
    const { data } = await api.delete(`/users/${id}`)
    return data
  },
}

const isBrowserFile = (value) => typeof File !== 'undefined' && value instanceof File

const payloadHasFile = (value) => {
  if (isBrowserFile(value)) return true
  if (Array.isArray(value)) return value.some(payloadHasFile)
  if (value && typeof value === 'object') return Object.values(value).some(payloadHasFile)
  return false
}

const appendFormData = (formData, key, value) => {
  if (value === undefined || value === null) return

  if (isBrowserFile(value)) {
    formData.append(key, value)
    return
  }

  if (Array.isArray(value)) {
    value.forEach((item, index) => appendFormData(formData, `${key}[${index}]`, item))
    return
  }

  if (typeof value === 'object') {
    Object.entries(value).forEach(([childKey, childValue]) => appendFormData(formData, `${key}[${childKey}]`, childValue))
    return
  }

  formData.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : value)
}

const toFormData = (payload) => {
  const formData = new FormData()
  Object.entries(payload || {}).forEach(([key, value]) => appendFormData(formData, key, value))
  return formData
}

const prepareQuizPayload = (payload) => payloadHasFile(payload) ? toFormData(payload) : payload

export const quizzesApi = {
  async list(params = {}) {
    const { data } = await api.get('/quizzes', { params })
    return unwrapCollection(data)
  },

  async get(id) {
    const { data } = await api.get(`/quizzes/${id}`)
    return unwrap(data)
  },

  async getForEdit(id) {
    const { data } = await api.get(`/quizzes/${id}/edit-data`)
    return unwrap(data)
  },

  async create(payload) {
    const body = prepareQuizPayload(payload)
    const { data } = await api.post('/quizzes', body)
    return unwrap(data)
  },

  async update(id, payload) {
    const body = prepareQuizPayload(payload)

    if (body instanceof FormData) {
      body.append('_method', 'PUT')
      const { data } = await api.post(`/quizzes/${id}`, body)
      return unwrap(data)
    }

    const { data } = await api.put(`/quizzes/${id}`, body)
    return unwrap(data)
  },

  async remove(id) {
    const { data } = await api.delete(`/quizzes/${id}`)
    return data
  },

  async startAttempt(id, payload = {}) {
    const { data } = await api.post(`/quizzes/${id}/attempts/start`, payload)
    return unwrap(data)
  },

  async submitAttempt(id, payload) {
    const { data } = await api.post(`/quizzes/${id}/attempts/submit`, payload)
    return unwrap(data)
  },
}

export const attemptsApi = {
  async list(params = {}) {
    const { data } = await api.get('/quiz-attempts', { params })
    return unwrapCollection(data)
  },

  async get(id) {
    const { data } = await api.get(`/quiz-attempts/${id}`)
    return unwrap(data)
  },
}

export const ocrApi = {
  async scan(file) {
    const formData = new FormData()
    formData.append('image', file)

    const { data } = await api.post('/ocr/scan', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    return data
  },
}

export const aiApi = {
  async generate(payload) {
    const { data } = await api.post('/ai/generate', payload)
    return unwrap(data)
  },

  async getJob(jobId) {
    const { data } = await api.get(`/ai/jobs/${jobId}`)
    return unwrap(data)
  },
}

export const difficultyLabel = (value) => ({
  easy: 'Dễ',
  medium: 'Vừa',
  hard: 'Khó',
  Dễ: 'Dễ',
  Vừa: 'Vừa',
  Khó: 'Khó',
}[value] || 'Vừa')

export const difficultyValue = (value) => ({
  Dễ: 'easy',
  Vừa: 'medium',
  Khó: 'hard',
  easy: 'easy',
  medium: 'medium',
  hard: 'hard',
}[value] || 'medium')

export const defaultQuizCover = 'linear-gradient(135deg, #0f172a, #7c3aed)'

export const coverToBackground = (cover) => {
  const value = String(cover || '').trim()
  if (!value) return defaultQuizCover
  if (/gradient\(/i.test(value)) return value

  const isImageSource = /^(https?:\/\/|\/|data:image\/|blob:)/i.test(value)
  if (!isImageSource) return value

  const escaped = value.replace(/"/g, '\\"')
  return `linear-gradient(135deg, rgba(15,23,42,.2), rgba(124,58,237,.24)), url("${escaped}") center / cover no-repeat`
}

export const normalizeQuizCard = (quiz) => ({
  ...quiz,
  roomCode: quiz.room_code || '',
  duration: `${quiz.duration_minutes || Math.ceil((quiz.time_limit_seconds || 600) / 60)} phút`,
  questions: quiz.questions_count ?? (Array.isArray(quiz.questions) ? quiz.questions.length : 0),
  attempts: quiz.attempts_count ?? 0,
  avgScore: Math.round(Number(quiz.avg_score ?? quiz.score_percent ?? 0)),
  rating: quiz.rating || '4.8',
  coverSource: quiz.cover || '',
  cover: coverToBackground(quiz.cover),
  icon: quiz.icon || 'QZ',
  badge: quiz.badge || 'QUIZ',
  author: quiz.author || quiz.user?.name || 'QuizFlex',
  visibility: quiz.visibility || (quiz.is_public ? 'public' : 'private'),
  difficulty: difficultyLabel(quiz.difficulty_label || quiz.difficulty),
  rawDifficulty: difficultyValue(quiz.difficulty),
})


export const normalizeUser = (user) => ({
  ...user,
  role: String(user.role || 'user').toLowerCase(),
  roleLabel: user.role_label || ({ admin: 'Admin', vip: 'VIP', user: 'Thường', guest: 'Guest' }[String(user.role || 'user').toLowerCase()] || user.role),
  joinedAt: user.joined_at || user.created_at ? new Date(user.joined_at || user.created_at).toLocaleDateString('vi-VN') : 'Chưa rõ',
  aiQuota: user.ai_quota_remaining ?? 0,
  quizzesCount: user.quizzes_count ?? 0,
  attemptsCount: user.attempts_count ?? 0,
  status: user.status || 'active',
})

export const normalizeQuestion = (question) => ({
  id: question.id,
  question: question.text || question.content,
  category: question.category || 'Quiz',
  difficulty: difficultyLabel(question.difficulty || 'medium'),
  type: question.type || 'single_choice',
  points: question.points ?? 10,
  correct: question.answers?.find((answer) => answer.is_correct)?.answer_key || question.answers?.find((answer) => answer.is_correct)?.key || '',
  answers: (question.answers || []).map((answer, index) => ({
    id: answer.id,
    key: answer.answer_key || answer.key || String.fromCharCode(65 + index),
    text: answer.text || answer.content,
    isCorrect: Boolean(answer.is_correct),
  })),
})

export const buildEditorDraftFromQuiz = (quiz = {}) => ({
  title: quiz.title || '',
  tag: quiz.tag || '',
  description: quiz.description || '',
  category: quiz.category || 'AI',
  difficulty: difficultyValue(quiz.difficulty),
  visibility: quiz.visibility || (quiz.is_public ? 'public' : (quiz.room_code ? 'group' : 'private')),
  roomCode: quiz.room_code || '',
  durationMinutes: quiz.duration_minutes || Math.ceil((quiz.time_limit_seconds || 600) / 60),
  questions: (quiz.questions || []).map((question) => {
    const normalized = normalizeQuestion(question)
    return {
      id: normalized.id,
      text: normalized.question,
      correct: normalized.correct || 'A',
      points: normalized.points || 1,
      answers: normalized.answers.map((answer) => ({
        id: answer.id,
        key: answer.key,
        text: answer.text,
      })),
    }
  }),
})

export const formatSeconds = (seconds = 0) => {
  const safeSeconds = Math.max(0, Number(seconds) || 0)
  const minutes = Math.floor(safeSeconds / 60)
  const remainingSeconds = safeSeconds % 60
  return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`
}

export const paymentsApi = {
  async create(payload) {
    const { data } = await api.post('/payments/create', payload)
    return data
  },

  async callback(params) {
    const { data } = await api.get('/payments/callback', { params })
    return data
  },

  async history() {
    const { data } = await api.get('/payments/history')
    return data
  }
}

export default api
