import { defineStore } from 'pinia'
import { ref } from 'vue'
import { statsApi } from '@/api/stats'
import type { BudgetStats, CategoryStats } from '@/types'

export const useStatsStore = defineStore('stats', () => {
  const summary = ref<BudgetStats | null>(null)
  const categoryStats = ref<CategoryStats[]>([])
  const subcategoryStats = ref<CategoryStats[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchSummary(budgetId: number) {
    loading.value = true
    error.value = null
    try {
      summary.value = await statsApi.summary(budgetId)
      return summary.value
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des statistiques'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchCategoryStats(budgetId: number) {
    loading.value = true
    error.value = null
    try {
      categoryStats.value = await statsApi.byCategory(budgetId)
      return categoryStats.value
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des stats'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchSubcategoryStats(budgetId: number, categoryId?: number) {
    loading.value = true
    error.value = null
    try {
      subcategoryStats.value = await statsApi.bySubcategory(budgetId, categoryId)
      return subcategoryStats.value
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des stats'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    summary,
    categoryStats,
    subcategoryStats,
    loading,
    error,
    fetchSummary,
    fetchCategoryStats,
    fetchSubcategoryStats
  }
})
