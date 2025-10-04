import { defineStore } from 'pinia'
import { ref } from 'vue'
import { templatesApi } from '@/api/templates'
import type { BudgetTemplate } from '@/types'

export const useTemplateStore = defineStore('template', () => {
  const templates = ref<BudgetTemplate[]>([])
  const currentTemplate = ref<BudgetTemplate | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchTemplates() {
    loading.value = true
    error.value = null
    try {
      templates.value = await templatesApi.getAll()
      return templates.value
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des templates'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchTemplate(id: number) {
    loading.value = true
    error.value = null
    try {
      currentTemplate.value = await templatesApi.getById(id)
      return currentTemplate.value
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement du template'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createTemplate(data: {
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
  }) {
    loading.value = true
    error.value = null
    try {
      const template = await templatesApi.create(data)
      templates.value.push(template)
      return template
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erreur lors de la création du template'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateTemplate(
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
  ) {
    loading.value = true
    error.value = null
    try {
      const template = await templatesApi.update(id, data)
      const index = templates.value.findIndex((t) => t.id === id)
      if (index !== -1) {
        templates.value[index] = template
      }
      if (currentTemplate.value?.id === id) {
        currentTemplate.value = template
      }
      return template
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erreur lors de la mise à jour du template'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteTemplate(id: number) {
    loading.value = true
    error.value = null
    try {
      await templatesApi.delete(id)
      templates.value = templates.value.filter((t) => t.id !== id)
      if (currentTemplate.value?.id === id) {
        currentTemplate.value = null
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erreur lors de la suppression du template'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function setDefaultTemplate(id: number) {
    loading.value = true
    error.value = null
    try {
      const template = await templatesApi.setDefault(id)

      // Mettre à jour tous les templates : désactiver les autres
      templates.value = templates.value.map((t) => ({
        ...t,
        isDefault: t.id === id
      }))

      return template
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erreur lors de la définition du template par défaut'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    templates,
    currentTemplate,
    loading,
    error,
    fetchTemplates,
    fetchTemplate,
    createTemplate,
    updateTemplate,
    deleteTemplate,
    setDefaultTemplate
  }
})
