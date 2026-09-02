import { questionsBankApi } from '@/services/api.js'
import { normalizeBankQuestionPage } from './bankQuestionNormalizer.js'

const requestBankQuestions = async (params = {}) => normalizeBankQuestionPage(await questionsBankApi.fetchBank(params))

export const getBankQuestions = (params = {}) => requestBankQuestions({
  page: params.page,
  per_page: params.perPage,
  search: params.search || undefined,
  subject_id: params.subjectId || undefined,
  grade_id: params.gradeId || undefined,
  topic_name: params.topic || undefined,
  difficulty: params.difficulty || undefined,
})

export const getBankQuestionsByIds = (ids = []) => requestBankQuestions({
  ids,
  per_page: Math.min(Math.max(ids.length, 1), 100),
})
