import api from "./axios";
import type { Asset, AssetType } from "@/types";

export interface CreateAssetData {
  type: AssetType;
  is_liability?: boolean;
  label: string;
  institution?: string;
  value_cents: number;
  notes?: string;
}

export const assetsApi = {
  async getTypes(): Promise<string[]> {
    const response = await api.get<{ types: string[] }>("/assets/types");
    return response.data.types;
  },

  async list(type?: AssetType): Promise<{
    assets: Asset[];
    liabilities: Asset[];
    totalAssetsCents: number;
    totalLiabilitiesCents: number;
    netWorthCents: number;
  }> {
    const params = type ? { type } : {};
    const response = await api.get("/assets", { params });
    return response.data;
  },

  async get(id: number): Promise<Asset> {
    const response = await api.get(`/assets/${id}`);
    return response.data;
  },

  async create(data: CreateAssetData): Promise<Asset> {
    const response = await api.post("/assets", data);
    return response.data;
  },

  async update(id: number, data: Partial<CreateAssetData>): Promise<Asset> {
    const response = await api.put(`/assets/${id}`, data);
    return response.data;
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/assets/${id}`);
  },
};
