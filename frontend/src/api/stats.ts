import api from "./axios";
import type { BudgetStats, CategoryStats } from "@/types";

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
    const response = await api.get<BudgetStats>(
      `/budgets/${budgetId}/stats/summary`,
    );
    return response.data;
  },

  async byCategory(budgetId: number): Promise<CategoryStats[]> {
    const response = await api.get<CategoryStats[]>(
      `/budgets/${budgetId}/stats/by-category`,
    );
    return response.data;
  },

  async bySubcategory(
    budgetId: number,
    categoryId?: number,
  ): Promise<CategoryStats[]> {
    const params = categoryId ? { categoryId } : {};
    const response = await api.get<CategoryStats[]>(
      `/budgets/${budgetId}/stats/by-subcategory`,
      {
        params,
      },
    );
    return response.data;
  },

  async wealthEvolution(params?: {
    from?: string;
    to?: string;
  }): Promise<WealthEvolutionData> {
    const response = await api.get<WealthEvolutionData>(
      "/stats/wealth-evolution",
      { params },
    );
    return response.data;
  },

  async expenseDistribution(
    budgetId: number,
  ): Promise<ExpenseDistributionItem[]> {
    const response = await api.get<ExpenseDistributionItem[]>(
      `/budgets/${budgetId}/stats/expense-distribution`,
    );
    return response.data;
  },
};
