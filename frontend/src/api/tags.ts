import api from "./axios";
import type { Tag } from "@/types";

export interface CreateTagData {
  name: string;
  color?: string;
}

export interface UpdateTagData {
  name?: string;
  color?: string;
}

export const tagsApi = {
  async list(): Promise<Tag[]> {
    const response = await api.get<Tag[]>("/tags");
    return response.data;
  },

  async create(data: CreateTagData): Promise<Tag> {
    const response = await api.post<Tag>("/tags", data);
    return response.data;
  },

  async update(id: number, data: UpdateTagData): Promise<Tag> {
    const response = await api.put<Tag>(`/tags/${id}`, data);
    return response.data;
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/tags/${id}`);
  },
};
