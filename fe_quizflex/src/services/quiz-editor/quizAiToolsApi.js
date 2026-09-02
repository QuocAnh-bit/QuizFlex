import { aiQuizApi } from '@/services/api.js'
import { normalizeQuestion } from './quizEditorNormalizer.js'

const answerKey = (index) => String.fromCharCode(65 + index)

export const toAiReviewQuestion = (question, index = 0) => {
  const sourceAnswers = question.type === 'fill_in' ? question.accepted_answers || [] : question.answers || []
  const options = question.type === 'fill_in' ? null : Object.fromEntries(sourceAnswers.map((answer, answerIndex) => [answerKey(answerIndex), answer.content || '']))
  const correctKeys = sourceAnswers.map((answer, answerIndex) => answer.is_correct ? answerKey(answerIndex) : null).filter(Boolean)
  return {
    id: question.id,
    question_number: index + 1,
    type: question.type === 'fill_in' ? 'fill_blank' : question.type,
    question: question.content || '',
    options,
    correct_answer: question.type === 'fill_in' ? sourceAnswers.map((answer) => answer.content || '') : (question.type === 'multi_choice' ? correctKeys : correctKeys[0] || null),
  }
}

const quizContext = (quiz) => ({ title: quiz.title || '', description: quiz.description || '', grade: quiz.grade_name || quiz.grade_id || '', subject: quiz.subject_name || quiz.subject_id || '', topic: quiz.topic_name || '', difficulty: quiz.difficulty || '' })

export const reviewWholeQuiz = async (quiz) => {
  const { data } = await aiQuizApi.review({ action: 'review', mode: 'full', quiz: quizContext(quiz), questions: (quiz.questions || []).map(toAiReviewQuestion) })
  return { summary: String(data?.summary || ''), topics: Array.isArray(data?.topics) ? data.topics : [], issues: Array.isArray(data?.issues) ? data.issues : [], suggestions: Array.isArray(data?.suggestions) ? data.suggestions : [] }
}

export const generateSimilarQuestions = async (quiz, selectedQuestions, count = 3) => {
  const { data } = await aiQuizApi.suggest({ action: 'similar', quiz: quizContext(quiz), selected_question_ids: selectedQuestions.map((question) => question.id), selected_questions: selectedQuestions.map(toAiReviewQuestion), options: { count, difficulty: quiz.difficulty || 'medium', scope: 'selected', keep_grade_scope: true } })
  return (Array.isArray(data?.suggestions) ? data.suggestions : []).map((item, index) => {
    const entries = Object.entries(item.options || {})
    const correct = Array.isArray(item.correct_answer) ? item.correct_answer.map(String) : [String(item.correct_answer || '')]
    return normalizeQuestion({ id: item.id || `similar-ai-${index}`, type: item.type || 'single_choice', content: item.question || '', difficulty: ['easy', 'medium', 'hard'].includes(item.difficulty) ? item.difficulty : (quiz.difficulty || 'medium'), points: 1, answers: entries.map(([key, content], answerIndex) => ({ id: `similar-ai-${index}-${answerIndex}`, content: String(content || ''), is_correct: correct.includes(String(key)), order: answerIndex })) })
  }).filter((question) => question.content && question.answers.length >= 2)
}
