import apiClient from "./axios";

export interface ForgotPasswordData {
  email: string;
}

export interface ResetPasswordData {
  email: string;
  token: string;
  password: string;
  passwordConfirmation: string;
}

export const passwordResetApi = {
  /**
   * Envoie un email de réinitialisation de mot de passe
   */
  async forgotPassword(data: ForgotPasswordData): Promise<{ message: string }> {
    const response = await apiClient.post("/auth/forgot-password", data);
    return response.data;
  },

  /**
   * Réinitialise le mot de passe avec le token
   */
  async resetPassword(data: ResetPasswordData): Promise<{ message: string }> {
    const response = await apiClient.post("/auth/reset-password", {
      email: data.email,
      token: data.token,
      password: data.password,
      password_confirmation: data.passwordConfirmation,
    });
    return response.data;
  },
};
