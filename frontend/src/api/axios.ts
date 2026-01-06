import axios from "axios";
import { errorHandler } from "@/utils/errorHandler";

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || "http://localhost:8080/api",
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  withCredentials: false, // Utiliser Bearer tokens au lieu de cookies
});

apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem("token");
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    // Gérer les erreurs d'authentification (401)
    if (error.response?.status === 401) {
      localStorage.removeItem("token");
      errorHandler.handle(error, "Session expirée. Veuillez vous reconnecter.");

      // Rediriger vers login seulement si pas déjà sur la page de login
      if (!window.location.pathname.includes("/login")) {
        setTimeout(() => {
          window.location.href = "/login";
        }, 1000);
      }

      return Promise.reject(error);
    }

    // Gérer les erreurs de validation (422)
    // Ne pas afficher de toast ici, laisser le composant gérer
    if (error.response?.status === 422) {
      const validationErrors = error.response?.data?.errors;
      if (validationErrors) {
        error.validationErrors = validationErrors;
      }
      errorHandler.warning("Erreur de validation", validationErrors);
      return Promise.reject(error);
    }

    // Autres erreurs - logger mais ne pas afficher de toast
    // (les composants décideront s'ils veulent afficher un toast)
    errorHandler.info("Erreur API", {
      status: error.response?.status,
      message: error.response?.data?.message || error.message,
    });

    return Promise.reject(error);
  }
);

export default apiClient;
