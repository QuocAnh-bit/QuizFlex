import { ocrApi } from '@/services/api.js'
import { normalizeQuestion } from './quizEditorNormalizer.js'

const TYPE_MAP = { single_choice: 'single_choice', multiple_choice: 'multi_choice', multi_choice: 'multi_choice', fill_blank: 'fill_in', fill_in: 'fill_in', true_false: 'true_false' }
const optionEntries = (options) => {
  if (Array.isArray(options)) return options.map((value, index) => [String.fromCharCode(65 + index), value])
  if (options && typeof options === 'object') return Object.entries(options)
  return []
}

export const scanOcrDocument = async (file) => {
  const response = await ocrApi.scan(file, 'math')
  const body = response?.data || response
  return body?.quizOrc || body?.data || body
}

export const normalizeOcrQuestions = (payload = {}, filename = '') => {
  const rawQuestions = Array.isArray(payload?.questions) ? payload.questions : []
  return rawQuestions.map((raw, index) => {
    const entries = optionEntries(raw.options).filter(([, value]) => String(value ?? '').trim())
    const correctKeys = Array.isArray(raw.correct_answer) ? raw.correct_answer.map(String) : raw.correct_answer ? [String(raw.correct_answer)] : []
    const explicitType = TYPE_MAP[raw.type]
    const type = explicitType || (correctKeys.length > 1 ? 'multi_choice' : 'single_choice')
    const answers = entries.map(([key, content], answerIndex) => ({ id: `ocr-result-${index}-answer-${answerIndex}`, content: String(content), is_correct: correctKeys.includes(String(key)), order: answerIndex }))
    const question = normalizeQuestion({ id: `ocr-result-${index}`, order: index + 1, type, content: raw.question ?? raw.content ?? '', image_url: raw.image_url ?? raw.images?.[0]?.url ?? null, difficulty: raw.difficulty || 'medium', points: raw.points || 1, answers })
    let invalidReason = ''
    if (!question.content.trim()) invalidReason = 'Không nhận diện được nội dung câu hỏi.'
    else if (type === 'fill_in') invalidReason = 'Pipeline OCR hiện chưa trả đáp án điền khuyết theo schema EditorV2.'
    else if (answers.length < 2) invalidReason = 'Câu hỏi chưa nhận diện đủ lựa chọn.'
    else if (!answers.some((answer) => answer.is_correct)) invalidReason = 'Chưa nhận diện được đáp án đúng.'
    return { question: { ...question, source_type: 'ocr', source_filename: filename }, invalidReason }
  })
}
