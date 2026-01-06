import api from "./axios";
import type { LoginCredentials, RegisterData, AuthResponse, User } from "@/types";

export const authApi = {
  async register(data: RegisterData): Promise<AuthResponse> {
    const response = await api.post<AuthResponse>("/auth/register", data);
    return response.data;
  },

  async login(credentials: LoginCredentials): Promise<AuthResponse> {
    const response = await api.post<AuthResponse>("/auth/login", credentials);
    return response.data;
  },

  async me(): Promise<User> {
    const response = await api.get<User>("/auth/me");
    return response.data;
  },

  async logout(): Promise<void> {
    await api.post("/auth/logout");
  },

  async updatePassword(data: {
    currentPassword: string;
    newPassword: string;
    newPasswordConfirmation: string;
  }): Promise<{ message: string }> {
    const response = await api.put<{ message: string }>("/auth/password", data);
    return response.data;
  },
};
