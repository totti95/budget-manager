import api from "./axios";
import type {
  SavingsGoal,
  SavingsGoalContribution,
  CreateSavingsGoalData,
  UpdateSavingsGoalData,
  CreateContributionData,
} from "@/types";

export const savingsGoalsApi = {
  async list(): Promise<SavingsGoal[]> {
    const response = await api.get("/savings-goals");
    return response.data;
  },

  async get(id: number): Promise<SavingsGoal> {
    const response = await api.get(`/savings-goals/${id}`);
    return response.data;
  },

  async create(data: CreateSavingsGoalData): Promise<SavingsGoal> {
    const response = await api.post("/savings-goals", data);
    return response.data;
  },

  async update(id: number, data: UpdateSavingsGoalData): Promise<SavingsGoal> {
    const response = await api.put(`/savings-goals/${id}`, data);
    return response.data;
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/savings-goals/${id}`);
  },

  async syncAsset(id: number): Promise<SavingsGoal> {
    const response = await api.patch(`/savings-goals/${id}/sync-asset`);
    return response.data;
  },

  // Contributions
  async listContributions(goalId: number): Promise<SavingsGoalContribution[]> {
    const response = await api.get(`/savings-goals/${goalId}/contributions`);
    return response.data;
  },

  async addContribution(
    goalId: number,
    data: CreateContributionData
  ): Promise<SavingsGoalContribution> {
    const response = await api.post(`/savings-goals/${goalId}/contributions`, data);
    return response.data;
  },

  async deleteContribution(goalId: number, contributionId: number): Promise<void> {
    await api.delete(`/savings-goals/${goalId}/contributions/${contributionId}`);
  },
};
