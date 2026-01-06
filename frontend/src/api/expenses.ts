import api from "./axios";
import type { Expense, PaginatedResponse } from "@/types";

export interface ExpenseFilters {
  subcatId?: number;
  tagId?: number;
  q?: string;
  from?: string;
  to?: string;
  page?: number;
}

export interface CreateExpenseData {
  budget_subcategory_id: number;
  date: string;
  label: string;
  amount_cents: number;
  payment_method?: string;
  notes?: string;
  tag_ids?: number[];
}

export const expensesApi = {
  async list(budgetId: number, filters?: ExpenseFilters): Promise<PaginatedResponse<Expense>> {
    const response = await api.get<PaginatedResponse<Expense>>(`/budgets/${budgetId}/expenses`, {
      params: filters,
    });
    return response.data;
  },

  async create(budgetId: number, data: CreateExpenseData): Promise<Expense> {
    const response = await api.post<Expense>(`/budgets/${budgetId}/expenses`, data);
    return response.data;
  },

  async update(id: number, data: Partial<CreateExpenseData>): Promise<Expense> {
    const response = await api.put<Expense>(`/expenses/${id}`, data);
    return response.data;
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/expenses/${id}`);
  },

  async importCsv(budgetId: number, file: File): Promise<{ imported: number; errors: string[] }> {
    const formData = new FormData();
    formData.append("file", file);
    const response = await api.post(`/budgets/${budgetId}/expenses/import-csv`, formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    return response.data;
  },

  async exportCsv(budgetId: number): Promise<Blob> {
    const response = await api.get(`/budgets/${budgetId}/expenses/export-csv`, {
      responseType: "blob",
    });
    return response.data;
  },
};
