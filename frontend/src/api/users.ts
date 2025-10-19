import api from "./axios";
import type { User, UserWithPassword, PaginatedResponse, Role } from "@/types";

export interface UserFilters {
  search?: string;
  role?: string;
  status?: "active" | "deleted";
  page?: number;
}

export interface CreateUserData {
  name: string;
  email: string;
  roleId: number;
}

export interface UpdateUserData {
  name?: string;
  email?: string;
  roleId?: number;
}

export interface UpdatePasswordData {
  password: string;
  password_confirmation: string;
}

export const usersApi = {
  async list(filters?: UserFilters): Promise<PaginatedResponse<User>> {
    const response = await api.get<PaginatedResponse<User>>("/admin/users", {
      params: filters,
    });
    return response.data;
  },

  async create(data: CreateUserData): Promise<UserWithPassword> {
    const response = await api.post<UserWithPassword>("/admin/users", data);
    return response.data;
  },

  async update(id: number, data: UpdateUserData): Promise<User> {
    const response = await api.put<User>(`/admin/users/${id}`, data);
    return response.data;
  },

  async updatePassword(id: number, data: UpdatePasswordData): Promise<void> {
    await api.put(`/admin/users/${id}/password`, data);
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/admin/users/${id}`);
  },

  async restore(id: number): Promise<User> {
    const response = await api.put<{ user: User; message: string }>(
      `/admin/users/${id}/restore`,
    );
    return response.data.user;
  },
};

export const rolesApi = {
  async list(): Promise<Role[]> {
    // Pour l'instant, on retourne les r�les en dur
    // Dans le futur, on pourrait cr�er un endpoint backend
    return Promise.resolve([
      {
        id: 1,
        label: "user",
        description: "Utilisateur standard",
        createdAt: "",
        updatedAt: "",
      },
      {
        id: 2,
        label: "admin",
        description: "Administrateur",
        createdAt: "",
        updatedAt: "",
      },
    ]);
  },
};
