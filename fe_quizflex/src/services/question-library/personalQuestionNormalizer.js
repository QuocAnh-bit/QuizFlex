const TYPE_MAP = Object.freeze({
  single_choice: 'single_choice',
  multi_choice: 'multi_choice',
  multiple_choice: 'multi_choice',
  fill_blank: 'fill_in',
  fill_in: 'fill_in',
  true_false: 'true_false',
})

const asArray = (value) => Array.isArray(value) ? value : []
const asNumber = (value, fallback) => Number.isFinite(Number(value)) ? Number(value) : fallback

export const normalizePersonalQuestion = (source = {}) => {
  const type = TYPE_MAP[source.type] || 'single_choice'
  const normalizedAnswers = asArray(source.answers).map((answer, index) => ({
    id: answer.id ?? `source-answer-${source.id}-${index}`,
    content: String(answer.content ?? answer.text ?? ''),
    is_correct: Boolean(answer.is_correct),
    order: asNumber(answer.order, index),
  }))

  return {
    question: {
      id: source.id,
      content: String(source.content ?? source.text ?? ''),
      image_url: typeof source.image_url === 'string' ? source.image_url : null,
      type,
      points: asNumber(source.points, 1),
      difficulty: ['easy', 'medium', 'hard'].includes(source.difficulty) ? source.difficulty : 'medium',
      answers: type === 'fill_in' ? [] : normalizedAnswers,
      accepted_answers: type === 'fill_in'
        ? normalizedAnswers.map((answer) => ({ id: answer.id, content: answer.content }))
        : [],
    },
    meta: {
      sourceQuestionId: source.id,
      subjectId: source.subject_id ?? null,
      subject: source.subject_name || 'Chưa phân môn',
      gradeId: source.grade_id ?? null,
      grade: source.grade_name || 'Chưa phân lớp',
      topic: source.topic_name || 'Chưa có chủ đề',
      difficulty: ['easy', 'medium', 'hard'].includes(source.difficulty) ? source.difficulty : 'medium',
      ownerId: source.user_id ?? null,
    },
  }
}

export const normalizePersonalQuestionPage = (response = {}) => ({
  items: asArray(response.items).map(normalizePersonalQuestion),
  total: asNumber(response.total, 0),
  currentPage: asNumber(response.currentPage, 1),
  lastPage: asNumber(response.lastPage, 1),
  perPage: asNumber(response.perPage, 20),
})
