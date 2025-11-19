import axios from "./axios";
import type { WealthHistory } from "@/types";

export const wealthHistoryApi = {
  async list(params?: { from?: string; to?: string }): Promise<WealthHistory[]> {
    const response = await axios.get("/wealth-history", { params });
    return response.data;
  },

  async record(recordedAt: string): Promise<WealthHistory> {
    const response = await axios.post("/wealth-history/record", { recordedAt });
    return response.data;
  },

  async delete(id: number): Promise<void> {
    await axios.delete(`/wealth-history/${id}`);
  },
};
