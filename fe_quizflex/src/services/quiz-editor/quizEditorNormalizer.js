const BACKEND_TO_EDITOR_TYPES = Object.freeze({
  single_choice: 'single_choice',
  multi_choice: 'multi_choice',
  multiple_choice: 'multi_choice',
  fill_blank: 'fill_in',
  true_false: 'true_false',
})

const SUPPORTED_DIFFICULTIES = new Set(['easy', 'medium', 'hard'])

const asObject = (value) => value && typeof value === 'object' && !Array.isArray(value) ? value : {}
const asArray = (value) => Array.isArray(value) ? value : []
const asNumber = (value, fallback) => Number.isFinite(Number(value)) ? Number(value) : fallback

export const normalizeQuestionType = (type) => BACKEND_TO_EDITOR_TYPES[type] || 'single_choice'

export const normalizeAnswer = (rawAnswer) => {
  const answer = asObject(rawAnswer)
  return {
    id: answer.id ?? null,
    content: String(answer.content ?? answer.text ?? ''),
    is_correct: Boolean(answer.is_correct),
    order: asNumber(answer.order, 0),
  }
}

export const normalizeQuestion = (rawQuestion) => {
  const question = asObject(rawQuestion)
  const type = normalizeQuestionType(question.type)
  const answers = asArray(question.answers).map(normalizeAnswer)

  return {
    id: question.id ?? null,
    order: asNumber(question.order, 0),
    type,
    content: String(question.content ?? question.text ?? ''),
    image_url: typeof question.image_url === 'string' ? question.image_url : null,
    difficulty: SUPPORTED_DIFFICULTIES.has(question.difficulty) ? question.difficulty : 'medium',
    points: asNumber(question.points, 1),
    // A question copied from the approved bank remains an immutable snapshot
    // inside a quiz. This value comes from the API, not from a UI-only flag.
    origin_question_id: question.origin_question_id ?? null,
    answers,
    accepted_answers: type === 'fill_in'
      ? answers.map((answer) => ({ id: answer.id, content: answer.content }))
      : [],
  }
}

export const normalizeQuiz = (rawQuiz) => {
  const quiz = asObject(rawQuiz)
  const questions = asArray(quiz.questions)
    .map((question, responseIndex) => ({ question, responseIndex }))
    .sort((left, right) => {
      const leftOrder = Number(left.question?.order)
      const rightOrder = Number(right.question?.order)
      const leftHasOrder = left.question?.order !== null && left.question?.order !== undefined && left.question?.order !== '' && Number.isFinite(leftOrder)
      const rightHasOrder = right.question?.order !== null && right.question?.order !== undefined && right.question?.order !== '' && Number.isFinite(rightOrder)
      if (leftHasOrder && rightHasOrder && leftOrder !== rightOrder) return leftOrder - rightOrder
      if (leftHasOrder !== rightHasOrder) return leftHasOrder ? -1 : 1
      return left.responseIndex - right.responseIndex
    })
    .map(({ question }) => normalizeQuestion(question))

  return {
    id: quiz.id ?? null,
    title: String(quiz.title ?? ''),
    description: String(quiz.description ?? ''),
    category: quiz.category ?? null,
    education_level_id: quiz.education_level_id ?? null,
    education_level_name: String(quiz.education_level_name ?? quiz.education_level?.name ?? ''),
    grade_id: quiz.grade_id ?? null,
    grade_name: String(quiz.grade_name ?? quiz.grade?.name ?? ''),
    subject_id: quiz.subject_id ?? null,
    subject_name: String(quiz.subject_name ?? quiz.subject?.name ?? ''),
    topic_name: quiz.topic_name ?? null,
    curriculum_unit_ids: Array.isArray(quiz.curriculum_unit_ids) ? quiz.curriculum_unit_ids.map(Number).filter(Number.isInteger) : [],
    tag: quiz.tag ?? null,
    difficulty: SUPPORTED_DIFFICULTIES.has(quiz.difficulty) ? quiz.difficulty : 'medium',
    status: quiz.status ?? 'draft',
    visibility: quiz.visibility ?? 'private',
    is_public: Boolean(quiz.is_public),
    room_code: quiz.room_code ?? null,
    time_limit_seconds: asNumber(quiz.time_limit_seconds, 600),
    questions,
  }
}
