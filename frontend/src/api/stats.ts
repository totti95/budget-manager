import api from "./axios";
import type { BudgetStats, CategoryStats } from "@/types";

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
};
