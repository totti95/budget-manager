import apiClient from './axios'
import type { BudgetTemplate } from '@/types'

export const templatesApi = {
  // Récupérer tous les templates de l'utilisateur
  async getAll(): Promise<BudgetTemplate[]> {
    const response = await apiClient.get('/templates')
    return response.data
  },

  // Récupérer un template par ID
  async getById(id: number): Promise<BudgetTemplate> {
    const response = await apiClient.get(`/templates/${id}`)
    return response.data
  },

  // Créer un nouveau template
  async create(data: {
    name: string
    isDefault?: boolean
    categories?: Array<{
      name: string
      plannedAmountCents: number
      sortOrder?: number
      subcategories?: Array<{
        name: string
        plannedAmountCents: number
        sortOrder?: number
      }>
    }>
  }): Promise<BudgetTemplate> {
    const response = await apiClient.post('/templates', {
      name: data.name,
      is_default: data.isDefault,
      categories: data.categories?.map((cat, catIndex) => ({
        name: cat.name,
        planned_amount_cents: cat.plannedAmountCents,
        sort_order: cat.sortOrder ?? catIndex,
        subcategories: cat.subcategories?.map((sub, subIndex) => ({
          name: sub.name,
          planned_amount_cents: sub.plannedAmountCents,
          sort_order: sub.sortOrder ?? subIndex
        }))
      }))
    })
    return response.data
  },

  // Mettre à jour un template (nom, défaut ET catégories)
  async update(
    id: number,
    data: {
      name?: string
      isDefault?: boolean
      categories?: Array<{
        id?: number
        name: string
        plannedAmountCents: number
        sortOrder?: number
        subcategories?: Array<{
          id?: number
          name: string
          plannedAmountCents: number
          sortOrder?: number
        }>
      }>
    }
  ): Promise<BudgetTemplate> {
    const payload: any = {}

    if (data.name !== undefined) payload.name = data.name
    if (data.isDefault !== undefined) payload.is_default = data.isDefault

    if (data.categories) {
      payload.categories = data.categories.map((cat, catIndex) => ({
        ...(cat.id && { id: cat.id }),
        name: cat.name,
        planned_amount_cents: cat.plannedAmountCents,
        sort_order: cat.sortOrder ?? catIndex,
        subcategories: cat.subcategories?.map((sub, subIndex) => ({
          ...(sub.id && { id: sub.id }),
          name: sub.name,
          planned_amount_cents: sub.plannedAmountCents,
          sort_order: sub.sortOrder ?? subIndex
        }))
      }))
    }

    const response = await apiClient.put(`/templates/${id}`, payload)
    return response.data
  },

  // Supprimer un template
  async delete(id: number): Promise<void> {
    await apiClient.delete(`/templates/${id}`)
  },

  // Définir comme template par défaut
  async setDefault(id: number): Promise<BudgetTemplate> {
    const response = await apiClient.post(`/templates/${id}/set-default`)
    return response.data
  }
}
