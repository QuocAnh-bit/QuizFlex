import { normalizePersonalQuestion } from './personalQuestionNormalizer.js'

export const normalizeBankQuestion = (source = {}) => {
  const normalized = normalizePersonalQuestion(source)
  return {
    ...normalized,
    meta: {
      ...normalized.meta,
      reviewStatus: source.bank_submission_status === 'approved' ? 'approved' : null,
      contributor: source.author_name || null,
      usageCount: Number.isFinite(Number(source.usage_count)) ? Number(source.usage_count) : null,
      isPublic: Boolean(source.is_public),
    },
  }
}

export const normalizeBankQuestionPage = (response = {}) => ({
  items: Array.isArray(response.items) ? response.items.map(normalizeBankQuestion) : [],
  total: Number.isFinite(Number(response.total)) ? Number(response.total) : 0,
  currentPage: Number.isFinite(Number(response.currentPage)) ? Number(response.currentPage) : 1,
  lastPage: Number.isFinite(Number(response.lastPage)) ? Number(response.lastPage) : 1,
  perPage: Number.isFinite(Number(response.perPage)) ? Number(response.perPage) : 20,
})
