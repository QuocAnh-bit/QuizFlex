import { aiApi } from '@/services/api.js'
import { normalizeQuestion } from './quizEditorNormalizer.js'

export const startAiQuestionGeneration = (payload) => aiApi.generate({
  ...payload,
  output_mode: 'questions_only',
  visibility: 'private',
})

export const getAiQuestionJob = (jobId) => aiApi.getJob(jobId)

export const normalizeAiQuestions = (job = {}) => {
  const result = job.generated_result || {}
  const difficulty = ['easy', 'medium', 'hard'].includes(job.difficulty) ? job.difficulty : 'medium'
  return (Array.isArray(result.questions) ? result.questions : []).map((question, index) => normalizeQuestion({
    ...question,
    id: `ai-result-${index}`,
    order: index + 1,
    type: 'single_choice',
    difficulty,
    points: 1,
  }))
}
