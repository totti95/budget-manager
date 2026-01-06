import api from "./axios";
import type { SavingsPlan } from "@/types";

export interface UpdateSavingsPlanData {
  plannedCents: number;
}

export const savingsPlansApi = {
  async list(month?: string): Promise<SavingsPlan[]> {
    const params = month ? { month } : {};
    const response = await api.get("/savings", { params });
    return response.data;
  },

  async get(id: number): Promise<SavingsPlan> {
    const response = await api.get(`/savings/${id}`);
    return response.data;
  },

  async update(id: number, data: UpdateSavingsPlanData): Promise<SavingsPlan> {
    const response = await api.put(`/savings/${id}`, {
      planned_cents: data.plannedCents,
    });
    return response.data;
  },
};
