import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  headers: {
    Accept: 'application/json',
  },
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const message = error.response?.data?.message || error.message || 'API request failed'
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
  roomCode: quiz.room_code || '',
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

export const formatSeconds = (seconds = 0) => {
  const safeSeconds = Math.max(0, Number(seconds) || 0)
  const minutes = Math.floor(safeSeconds / 60)
  const remainingSeconds = safeSeconds % 60
  return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`
}

export default api
