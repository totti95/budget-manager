import apiClient from "./axios";
import type { BudgetTemplate } from "@/types";

export const templatesApi = {
  // Récupérer tous les templates de l'utilisateur
  async getAll(): Promise<BudgetTemplate[]> {
    const response = await apiClient.get("/templates");
    return response.data;
  },

  // Récupérer un template par ID
  async getById(id: number): Promise<BudgetTemplate> {
    const response = await apiClient.get(`/templates/${id}`);
    return response.data;
  },

  // Créer un nouveau template
  async create(data: {
    name: string;
    isDefault?: boolean;
    revenueCents?: number | null;
    categories?: Array<{
      name: string;
      plannedAmountCents: number;
      sortOrder?: number;
      subcategories?: Array<{
        name: string;
        plannedAmountCents: number;
        sortOrder?: number;
      }>;
    }>;
  }): Promise<BudgetTemplate> {
    const response = await apiClient.post("/templates", {
      name: data.name,
      isDefault: data.isDefault,
      revenueCents: data.revenueCents,
      categories: data.categories?.map((cat, catIndex) => ({
        name: cat.name,
        plannedAmountCents: cat.plannedAmountCents,
        sortOrder: cat.sortOrder ?? catIndex,
        subcategories: cat.subcategories?.map((sub, subIndex) => ({
          name: sub.name,
          plannedAmountCents: sub.plannedAmountCents,
          sortOrder: sub.sortOrder ?? subIndex,
        })),
      })),
    });
    return response.data;
  },

  // Mettre à jour un template (nom, défaut ET catégories)
  async update(
    id: number,
    data: {
      name?: string;
      isDefault?: boolean;
      revenueCents?: number | null;
      categories?: Array<{
        id?: number;
        name: string;
        plannedAmountCents: number;
        sortOrder?: number;
        subcategories?: Array<{
          id?: number;
          name: string;
          plannedAmountCents: number;
          sortOrder?: number;
        }>;
      }>;
    }
  ): Promise<BudgetTemplate> {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const payload: any = {};

    if (data.name !== undefined) payload.name = data.name;
    if (data.isDefault !== undefined) payload.isDefault = data.isDefault;
    if (data.revenueCents !== undefined) payload.revenueCents = data.revenueCents;

    if (data.categories) {
      payload.categories = data.categories.map((cat, catIndex) => ({
        ...(cat.id && { id: cat.id }),
        name: cat.name,
        plannedAmountCents: cat.plannedAmountCents,
        sortOrder: cat.sortOrder ?? catIndex,
        subcategories: cat.subcategories?.map((sub, subIndex) => ({
          ...(sub.id && { id: sub.id }),
          name: sub.name,
          plannedAmountCents: sub.plannedAmountCents,
          sortOrder: sub.sortOrder ?? subIndex,
        })),
      }));
    }

    const response = await apiClient.put(`/templates/${id}`, payload);
    return response.data;
  },

  // Supprimer un template
  async delete(id: number): Promise<void> {
    await apiClient.delete(`/templates/${id}`);
  },

  // Définir comme template par défaut
  async setDefault(id: number): Promise<BudgetTemplate> {
    const response = await apiClient.post(`/templates/${id}/set-default`);
    return response.data;
  },
};
