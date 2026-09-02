import api from '@/services/api.js'

const clone = (value) => JSON.parse(JSON.stringify(value))

export const buildQuestionReviewPayload = (quiz, question) => ({
  quiz_context: {
    grade: quiz.grade_name || quiz.grade?.name || String(quiz.grade_id || ''),
    subject: quiz.subject_name || quiz.subject?.name || String(quiz.subject_id || ''),
    topic: quiz.topic_name || '',
    difficulty: quiz.difficulty || '',
  },
  question: {
    id: question.id,
    type: question.type,
    content: question.content || '',
    difficulty: question.difficulty || '',
    explanation: question.explanation || null,
    answers: question.type === 'fill_in' ? [] : (question.answers || []).map((answer) => ({ content: answer.content || '', is_correct: Boolean(answer.is_correct) })),
    accepted_answers: question.type === 'fill_in' ? (question.accepted_answers || []).map((answer) => ({ content: answer.content || '' })) : [],
  },
})

const normalizeReview = (raw = {}) => ({
  overallStatus: raw.overall_status === 'good' ? 'good' : 'needs_attention',
  score: Math.max(0, Math.min(100, Number(raw.score) || 0)),
  summary: String(raw.reasoning_summary || ''),
  issues: Array.isArray(raw.issues) ? raw.issues.map((issue) => ({ category: String(issue.category || 'other'), severity: ['info', 'warning', 'error'].includes(issue.severity) ? issue.severity : 'warning', message: String(issue.message || '') })).filter((issue) => issue.message) : [],
  suggestions: Array.isArray(raw.suggestions) ? raw.suggestions.map((item) => ({ field: String(item.field || ''), reason: String(item.reason || ''), currentValue: item.current_value ?? null, suggestedValue: item.suggested_value ?? null })) : [],
  suggestedQuestion: raw.suggested_question && typeof raw.suggested_question === 'object' ? clone(raw.suggested_question) : {},
})

export const reviewQuestion = async (quiz, question) => {
  const { data } = await api.post('/ai/question-review', buildQuestionReviewPayload(quiz, question))
  return normalizeReview(data)
}
