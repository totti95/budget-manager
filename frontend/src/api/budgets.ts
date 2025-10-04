import api from './axios'
import type { Budget, PaginatedResponse } from '@/types'

export const budgetsApi = {
  async list(month?: string): Promise<PaginatedResponse<Budget>> {
    const params = month ? { month } : {}
    const response = await api.get<PaginatedResponse<Budget>>('/budgets', { params })
    return response.data
  },

  async get(id: number): Promise<Budget> {
    const response = await api.get<Budget>(`/budgets/${id}`)
    return response.data
  },

  async generate(month: string): Promise<Budget> {
    const response = await api.post<Budget>('/budgets/generate', { month })
    return response.data
  },

  async update(id: number, data: { name: string }): Promise<Budget> {
    const response = await api.put<Budget>(`/budgets/${id}`, data)
    return response.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/budgets/${id}`)
  }
}
