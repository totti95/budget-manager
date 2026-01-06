import api from "./axios";
import type {
  BudgetStats,
  CategoryStats,
  TagStats,
  TopCategoryStats,
  SavingsRateDataPoint,
} from "@/types";

export interface WealthEvolutionData {
  labels: string[];
  datasets: {
    label: string;
    data: number[];
  }[];
}

export interface ExpenseDistributionItem {
  label: string;
  value: number;
}

export const statsApi = {
  async summary(budgetId: number): Promise<BudgetStats> {
    const response = await api.get<BudgetStats>(`/budgets/${budgetId}/stats/summary`);
    return response.data;
  },

  async byCategory(budgetId: number): Promise<CategoryStats[]> {
    const response = await api.get<CategoryStats[]>(`/budgets/${budgetId}/stats/by-category`);
    return response.data;
  },

  async bySubcategory(budgetId: number, categoryId?: number): Promise<CategoryStats[]> {
    const params = categoryId ? { categoryId } : {};
    const response = await api.get<CategoryStats[]>(`/budgets/${budgetId}/stats/by-subcategory`, {
      params,
    });
    return response.data;
  },

  async wealthEvolution(params?: { from?: string; to?: string }): Promise<WealthEvolutionData> {
    const response = await api.get<WealthEvolutionData>("/stats/wealth-evolution", { params });
    return response.data;
  },

  async expenseDistribution(budgetId: number): Promise<ExpenseDistributionItem[]> {
    const response = await api.get<ExpenseDistributionItem[]>(
      `/budgets/${budgetId}/stats/expense-distribution`
    );
    return response.data;
  },

  async byTag(budgetId: number): Promise<TagStats[]> {
    const response = await api.get<TagStats[]>(`/budgets/${budgetId}/stats/by-tag`);
    return response.data;
  },

  async topCategories(budgetId: number, limit: number = 5): Promise<TopCategoryStats[]> {
    const response = await api.get<TopCategoryStats[]>(
      `/budgets/${budgetId}/stats/top-categories`,
      { params: { limit } }
    );
    return response.data;
  },

  async savingsRateEvolution(params?: {
    from?: string;
    to?: string;
    months?: number;
  }): Promise<SavingsRateDataPoint[]> {
    const response = await api.get<SavingsRateDataPoint[]>("/stats/savings-rate-evolution", {
      params,
    });
    return response.data;
  },
};
