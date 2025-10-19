import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { authApi } from "@/api/auth";
import type { User, LoginCredentials, RegisterData } from "@/types";

export const useAuthStore = defineStore("auth", () => {
  const user = ref<User | null>(null);
  const token = ref<string | null>(localStorage.getItem("token"));
  const loading = ref(false);
  const error = ref<string | null>(null);

  const isAuthenticated = computed(() => !!token.value && !!user.value);

  async function register(data: RegisterData) {
    loading.value = true;
    error.value = null;
    try {
      const response = await authApi.register(data);
      token.value = response.token;
      user.value = response.user;
      localStorage.setItem("token", response.token);
      return response;
    } catch (err) {
      error.value = "Erreur lors de l'inscription";
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function login(credentials: LoginCredentials) {
    loading.value = true;
    error.value = null;
    try {
      const response = await authApi.login(credentials);
      token.value = response.token;
      user.value = response.user;
      localStorage.setItem("token", response.token);
      return response;
    } catch (err) {
      error.value = "Erreur lors de la connexion";
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchUser() {
    if (!token.value) return;
    loading.value = true;
    try {
      user.value = await authApi.me();
    } catch (err) {
      logout();
    } finally {
      loading.value = false;
    }
  }

  async function logout() {
    try {
      if (token.value) {
        await authApi.logout();
      }
    } finally {
      token.value = null;
      user.value = null;
      localStorage.removeItem("token");
    }
  }

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    register,
    login,
    fetchUser,
    logout,
  };
});
