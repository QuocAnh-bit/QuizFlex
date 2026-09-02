const EDITOR_TO_BACKEND_TYPES = Object.freeze({
  single_choice: 'single_choice',
  multi_choice: 'multi_choice',
  fill_in: 'fill_blank',
  true_false: 'true_false',
})

const TEMP_ID_PATTERN = /^(temp|local|mock|personal|bank)[-_]/i
const QUIZ_FIELDS = [
  'title',
  'description',
  'category',
  'education_level_id',
  'grade_id',
  'subject_id',
  'topic_name',
  'tag',
  'difficulty',
  'status',
  'visibility',
  'is_public',
  'room_code',
  'time_limit_seconds',
]

const asObject = (value) => value && typeof value === 'object' && !Array.isArray(value) ? value : {}
const asArray = (value) => Array.isArray(value) ? value : []
const finiteNumber = (value, fallback) => Number.isFinite(Number(value)) ? Number(value) : fallback

export const isTemporaryId = (id) => typeof id === 'string' && TEMP_ID_PATTERN.test(id)

const backendId = (id) => {
  if (isTemporaryId(id)) return null
  if (Number.isInteger(id) && id > 0) return id
  if (typeof id === 'string' && /^\d+$/.test(id) && Number(id) > 0) return Number(id)
  return null
}

const withBackendId = (payload, id) => {
  const normalizedId = backendId(id)
  return normalizedId === null ? payload : { id: normalizedId, ...payload }
}

export const serializeAnswer = (editorAnswer, index = 0) => {
  const answer = asObject(editorAnswer)
  return withBackendId({
    content: String(answer.content ?? ''),
    is_correct: Boolean(answer.is_correct),
    order: finiteNumber(answer.order, index),
  }, answer.id)
}

export const serializeQuestion = (editorQuestion) => {
  const question = asObject(editorQuestion)
  const backendType = EDITOR_TO_BACKEND_TYPES[question.type] || 'single_choice'
  const sourceAnswers = question.type === 'fill_in'
    ? asArray(question.accepted_answers).map((answer) => ({ ...asObject(answer), is_correct: true }))
    : asArray(question.answers)

  const payload = {
    content: String(question.content ?? ''),
    image_url: typeof question.image_url === 'string' ? question.image_url : null,
    type: backendType,
    points: finiteNumber(question.points, 1),
    order: finiteNumber(question.order, 0),
    answers: sourceAnswers.map(serializeAnswer),
  }

  return withBackendId(payload, question.id)
}

export const serializeQuiz = (editorQuiz) => {
  const quiz = asObject(editorQuiz)
  const payload = {}

  QUIZ_FIELDS.forEach((field) => {
    if (Object.prototype.hasOwnProperty.call(quiz, field)) payload[field] = quiz[field]
  })

  payload.questions = asArray(quiz.questions).map(serializeQuestion)
  return payload
}

