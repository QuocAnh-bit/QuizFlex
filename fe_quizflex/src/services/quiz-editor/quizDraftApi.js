import { quizzesApi } from '@/services/api.js'

const metadataPayload = (form) => ({
  title: String(form.title || '').trim(),
  description: String(form.description || '').trim() || null,
  education_level_id: Number(form.education_level_id),
  grade_id: Number(form.grade_id),
  subject_id: Number(form.subject_id),
  topic_name: String(form.topic_name || '').trim() || null,
  difficulty: form.difficulty || 'medium',
})

export const createEmptyQuizDraft = (form) => quizzesApi.create({
  ...metadataPayload(form),
  status: 'draft',
  is_public: false,
  questions: [],
})

export const getRecentEmptyQuizDrafts = async (limit = 3) => {
  const quizzes = await quizzesApi.list({ mine: 1, status: 'draft', per_page: 20 })
  return (Array.isArray(quizzes) ? quizzes : [])
    .filter((quiz) => Number(quiz.questions_count || 0) === 0)
    .slice(0, limit)
}

export const updateQuizMetadata = (quizId, form) => quizzesApi.update(quizId, metadataPayload(form))
