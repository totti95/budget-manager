import api from "./axios";
import type { Budget, PaginatedResponse, BudgetComparison } from "@/types";

export const budgetsApi = {
  async list(month?: string): Promise<PaginatedResponse<Budget>> {
    const params = month ? { month } : {};
    const response = await api.get<PaginatedResponse<Budget>>("/budgets", {
      params,
    });
    return response.data;
  },

  async get(id: number): Promise<Budget> {
    const response = await api.get<Budget>(`/budgets/${id}`);
    return response.data;
  },

  async generate(month: string): Promise<Budget> {
    const response = await api.post<Budget>("/budgets/generate", { month });
    return response.data;
  },

  async update(id: number, data: { name?: string; revenueCents?: number }): Promise<Budget> {
    const response = await api.put<Budget>(`/budgets/${id}`, data);
    return response.data;
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/budgets/${id}`);
  },

  async compare(months: string[]): Promise<BudgetComparison> {
    const response = await api.get<BudgetComparison>("/budgets/compare", {
      params: { months },
    });
    return response.data;
  },

  async exportPdf(id: number): Promise<{ blob: Blob; filename: string }> {
    const response = await api.get(`/budgets/${id}/export-pdf`, {
      responseType: "blob",
    });

    // Extract filename from Content-Disposition header
    const contentDisposition = response.headers["content-disposition"] || "";
    let filename = `budget-${id}.pdf`; // Fallback

    console.log("Content-Disposition header:", contentDisposition);
    console.log("All headers:", response.headers);

    // Try multiple patterns to extract filename
    const match = contentDisposition.match(/filename[*]?=["']?([^"';]+)["']?/i);
    if (match && match[1]) {
      filename = match[1].trim();
      console.log("Extracted filename:", filename);
    } else {
      console.log("Using fallback filename:", filename);
    }

    return {
      blob: response.data,
      filename: filename,
    };
  },
};
