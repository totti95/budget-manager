import { defineStore } from "pinia";
import { ref } from "vue";
import { statsApi } from "@/api/stats";
import type { BudgetStats, CategoryStats, TopCategoryStats, SavingsRateDataPoint } from "@/types";

export const useStatsStore = defineStore("stats", () => {
  const summary = ref<BudgetStats | null>(null);
  const categoryStats = ref<CategoryStats[]>([]);
  const subcategoryStats = ref<CategoryStats[]>([]);
  const topCategories = ref<TopCategoryStats[]>([]);
  const savingsRateData = ref<SavingsRateDataPoint[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function fetchSummary(budgetId: number) {
    loading.value = true;
    error.value = null;
    try {
      summary.value = await statsApi.summary(budgetId);
      return summary.value;
    } catch (err) {
      error.value = "Erreur lors du chargement des statistiques";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchCategoryStats(budgetId: number) {
    loading.value = true;
    error.value = null;
    try {
      categoryStats.value = await statsApi.byCategory(budgetId);
      return categoryStats.value;
    } catch (err) {
      error.value = "Erreur lors du chargement des stats";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchSubcategoryStats(budgetId: number, categoryId?: number) {
    loading.value = true;
    error.value = null;
    try {
      subcategoryStats.value = await statsApi.bySubcategory(budgetId, categoryId);
      return subcategoryStats.value;
    } catch (err) {
      error.value = "Erreur lors du chargement des stats";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchTopCategories(budgetId: number, limit: number = 5) {
    loading.value = true;
    error.value = null;
    try {
      topCategories.value = await statsApi.topCategories(budgetId, limit);
      return topCategories.value;
    } catch (err) {
      error.value = "Erreur lors du chargement des top catégories";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchSavingsRateEvolution(params?: {
    from?: string;
    to?: string;
    months?: number;
  }) {
    loading.value = true;
    error.value = null;
    try {
      savingsRateData.value = await statsApi.savingsRateEvolution(params);
      return savingsRateData.value;
    } catch (err) {
      error.value = "Erreur lors du chargement du taux d'épargne";
      console.error(err as Error);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  return {
    summary,
    categoryStats,
    subcategoryStats,
    topCategories,
    savingsRateData,
    loading,
    error,
    fetchSummary,
    fetchCategoryStats,
    fetchSubcategoryStats,
    fetchTopCategories,
    fetchSavingsRateEvolution,
  };
});
