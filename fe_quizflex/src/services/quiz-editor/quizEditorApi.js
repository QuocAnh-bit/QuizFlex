import api, { quizzesApi } from '../api.js'

const unwrapResponse = (response) => response?.data?.data ?? response?.data

export const getQuiz = (id) => quizzesApi.getForEdit(id)

export const updateQuiz = async (id, payload) => {
  const response = await api.put(`/quizzes/${id}`, payload, { timeout: 15000 })
  return unwrapResponse(response)
}

export const getQuizQuestions = async (quizId) => {
  const response = await api.get(`/quizzes/${quizId}/questions`)
  return unwrapResponse(response)
}

export const createQuestion = async (quizId, payload) => {
  const response = await api.post(`/quizzes/${quizId}/questions`, payload)
  return unwrapResponse(response)
}

export const updateQuestion = async (questionId, payload) => {
  const response = await api.put(`/questions/${questionId}`, payload)
  return unwrapResponse(response)
}

export const deleteQuestion = async (questionId) => {
  const response = await api.delete(`/questions/${questionId}`)
  return response.data
}
