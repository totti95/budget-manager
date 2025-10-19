import axios from "axios";

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
    // Standardiser le format d'erreur
    const errorMessage =
      error.response?.data?.message ||
      error.message ||
      "Une erreur est survenue";

    // Gérer les erreurs d'authentification
    if (error.response?.status === 401) {
      localStorage.removeItem("token");
      window.location.href = "/login";
      return Promise.reject(
        new Error("Session expirée. Veuillez vous reconnecter."),
      );
    }

    // Gérer les erreurs de validation (422)
    if (error.response?.status === 422) {
      const validationErrors = error.response?.data?.errors;
      if (validationErrors) {
        error.validationErrors = validationErrors;
      }
      return Promise.reject(error);
    }

    // Gérer les erreurs de rate limiting
    if (error.response?.status === 429) {
      return Promise.reject(
        new Error("Trop de requêtes. Veuillez patienter avant de réessayer."),
      );
    }

    // Gérer les erreurs serveur
    if (error.response?.status >= 500) {
      return Promise.reject(
        new Error("Erreur serveur. Veuillez réessayer plus tard."),
      );
    }

    // Gérer les erreurs réseau
    if (!error.response) {
      return Promise.reject(
        new Error("Erreur de connexion. Vérifiez votre connexion internet."),
      );
    }

    // Retourner l'erreur avec message standardisé
    error.message = errorMessage;
    return Promise.reject(error);
  },
);

export default apiClient;
