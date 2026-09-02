import { myQuestionsApi } from '@/services/api.js'
import { normalizePersonalQuestionPage } from './personalQuestionNormalizer.js'

export const getPersonalQuestions = async (params = {}) => {
  const response = await myQuestionsApi.fetchBank({
    page: params.page,
    per_page: params.perPage,
    search: params.search || undefined,
    subject_id: params.subjectId || undefined,
    grade_id: params.gradeId || undefined,
    topic_name: params.topic || undefined,
    difficulty: params.difficulty || undefined,
  })
  return normalizePersonalQuestionPage(response)
}
