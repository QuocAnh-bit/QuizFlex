import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  headers: {
    Accept: 'application/json',
  },
})

api.interceptors.request.use((config) => {
  let currentUserId = null

  try {
    currentUserId = JSON.parse(localStorage.getItem('quizflex_current_user') || '{}')?.id
  } catch {
    currentUserId = null
  }

  const mockUserId = currentUserId || localStorage.getItem('mock_user_id') || '3'

  config.headers = config.headers || {}
  config.headers['Accept'] = 'application/json'
  config.headers['X-Mock-User-Id'] = mockUserId

  return config
})

if (import.meta.env.DEV) {
  window.setMockUser = (id) => {
    localStorage.setItem('mock_user_id', String(id))
    console.log(`Đã đổi mock user sang ID ${id}. Reload lại trang để áp dụng.`)
  }
}

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const message = error.response?.data?.message || error.message || 'API request failed'
    const normalizedError = new Error(message)
    normalizedError.status = error.response?.status
    normalizedError.errors = error.response?.data?.errors
    return Promise.reject(normalizedError)
  },
)

const unwrap = (payload) => payload?.data ?? payload
const unwrapCollection = (payload) => {
  const body = unwrap(payload)
  if (Array.isArray(body)) return body
  if (Array.isArray(body?.data)) return body.data
  return []
}


export const currentUserStorage = {
  get() {
    try {
      const raw = localStorage.getItem('quizflex_current_user')
      return raw ? JSON.parse(raw) : null
    } catch {
      return null
    }
  },
  set(user) {
    localStorage.setItem('quizflex_current_user', JSON.stringify(user))
  },
  clear() {
    localStorage.removeItem('quizflex_current_user')
  },
}

export const authApi = {
  async login(payload) {
    const { data } = await api.post('/auth/login', payload)
    const user = unwrap(data)
    currentUserStorage.set(user)
    return user
  },

  async register(payload) {
    const { data } = await api.post('/auth/register', payload)
    const user = unwrap(data)
    currentUserStorage.set(user)
    return user
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

export const quizzesApi = {
  async list(params = {}) {
    const { data } = await api.get('/quizzes', { params })
    return unwrapCollection(data)
  },

  async get(id) {
    const { data } = await api.get(`/quizzes/${id}`)
    return unwrap(data)
  },

  async create(payload) {
    const { data } = await api.post('/quizzes', payload)
    return unwrap(data)
  },

  async update(id, payload) {
    const { data } = await api.put(`/quizzes/${id}`, payload)
    return unwrap(data)
  },

  async remove(id) {
    const { data } = await api.delete(`/quizzes/${id}`)
    return data
  },

  async startAttempt(id, payload = {}) {
    const { data } = await api.post(`/quizzes/${id}/attempts/start`)
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

export const roomsApi = {
  async list(params = {}) {
    const { data } = await api.get('/rooms', { params })
    return unwrapCollection(data)
  },

  async create(payload) {
    const { data } = await api.post('/rooms', payload)
    return unwrap(data)
  },

  async get(id) {
    const { data } = await api.get(`/rooms/${id}`)
    return unwrap(data)
  },

  async join(code) {
    const { data } = await api.post('/rooms/join', { code })
    return unwrap(data)
  },

  async members(id) {
    const { data } = await api.get(`/rooms/${id}/members`)
    return unwrapCollection(data)
  },
}

export const homeworkAssignmentsApi = {
  async list(roomId) {
    const { data } = await api.get(`/rooms/${roomId}/assignments`)
    return unwrapCollection(data)
  },

  async create(roomId, payload) {
    const { data } = await api.post(`/rooms/${roomId}/assignments`, payload)
    return unwrap(data)
  },

  async get(roomId, assignmentId) {
    const { data } = await api.get(`/rooms/${roomId}/assignments/${assignmentId}`)
    return unwrap(data)
  },

  async listSubmissions(roomId, assignmentId) {
    const { data } = await api.get(`/rooms/${roomId}/assignments/${assignmentId}/submissions`)
    return unwrapCollection(data)
  },

  async getSubmissionResult(roomId, assignmentId, submissionId) {
    const { data } = await api.get(`/rooms/${roomId}/assignments/${assignmentId}/submissions/${submissionId}`)
    return unwrap(data)
  },

  async start(roomId, assignmentId) {
    const { data } = await api.post(`/rooms/${roomId}/assignments/${assignmentId}/start`)
    return unwrap(data)
  },

  async answer(submissionId, payload) {
    const { data } = await api.post(`/assignment-submissions/${submissionId}/answer`, payload)
    return unwrap(data)
  },

  async submit(submissionId) {
    const { data } = await api.post(`/assignment-submissions/${submissionId}/submit`)
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

export const normalizeQuizCard = (quiz) => ({
  ...quiz,
  duration: `${quiz.duration_minutes || Math.ceil((quiz.time_limit_seconds || 600) / 60)} phút`,
  questions: quiz.questions_count ?? (Array.isArray(quiz.questions) ? quiz.questions.length : 0),
  attempts: quiz.attempts_count ?? 0,
  avgScore: Math.round(Number(quiz.avg_score ?? quiz.score_percent ?? 0)),
  rating: quiz.rating || '4.8',
  cover: quiz.cover || 'linear-gradient(135deg, #0f172a, #7c3aed)',
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

export const normalizeAssignment = (assignment) => ({
  ...assignment,
  quizTitle: assignment.quiz_title || assignment.quiz?.title || 'Quiz chưa có tên',
  quizDescription: assignment.quiz?.description || '',
  questionCount: assignment.quiz?.questions_count ?? assignment.quiz?.questions?.length ?? assignment.total_questions ?? 0,
  durationLabel: assignment.duration_minutes ? `${assignment.duration_minutes} phút` : 'Không giới hạn',
  maxAttemptsLabel: assignment.max_attempts ? `${assignment.max_attempts} lần` : 'Không giới hạn',
})

const HOMEWORK_PROGRESS_KEY = 'quizflex_homework_progress'

export const homeworkProgressStorage = {
  getAll() {
    try {
      return JSON.parse(localStorage.getItem(HOMEWORK_PROGRESS_KEY) || '{}')
    } catch {
      return {}
    }
  },
  get(assignmentId) {
    return this.getAll()[String(assignmentId)] || null
  },
  set(assignmentId, value) {
    const all = this.getAll()
    all[String(assignmentId)] = {
      ...(all[String(assignmentId)] || {}),
      ...value,
      updated_at: new Date().toISOString(),
    }
    localStorage.setItem(HOMEWORK_PROGRESS_KEY, JSON.stringify(all))
  },
  clear(assignmentId) {
    const all = this.getAll()
    delete all[String(assignmentId)]
    localStorage.setItem(HOMEWORK_PROGRESS_KEY, JSON.stringify(all))
  },
}

export const formatSeconds = (seconds = 0) => {
  const safeSeconds = Math.max(0, Number(seconds) || 0)
  const minutes = Math.floor(safeSeconds / 60)
  const remainingSeconds = safeSeconds % 60
  return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`
}

export default api
