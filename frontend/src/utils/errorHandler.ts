import { useToast } from "@/composables/useToast";
import type { AxiosError } from "axios";

export interface ApiError {
  message: string;
  code?: string;
  statusCode?: number;
  validationErrors?: Record<string, string[]>;
  timestamp: string;
}

export interface LogEntry {
  level: "error" | "warning" | "info";
  message: string;
  details?: unknown;
  timestamp: string;
  userAgent?: string;
  url?: string;
}

class ErrorHandler {
  private logs: LogEntry[] = [];
  private maxLogs = 100;
  private toast = useToast();

  /**
   * Log une erreur dans la console et la stocke pour debugging
   */
  private log(level: LogEntry["level"], message: string, details?: unknown) {
    const entry: LogEntry = {
      level,
      message,
      details,
      timestamp: new Date().toISOString(),
      userAgent: navigator.userAgent,
      url: window.location.href,
    };

    this.logs.push(entry);

    // Garder seulement les N derniers logs
    if (this.logs.length > this.maxLogs) {
      this.logs.shift();
    }

    // Logger dans la console
    const consoleMethod = level === "error" ? console.error : level === "warning" ? console.warn : console.info;
    consoleMethod(`[${level.toUpperCase()}] ${message}`, details || "");
  }

  /**
   * Récupère les logs pour debugging
   */
  getLogs(): LogEntry[] {
    return [...this.logs];
  }

  /**
   * Exporte les logs en JSON pour envoi au support
   */
  exportLogs(): string {
    return JSON.stringify(this.logs, null, 2);
  }

  /**
   * Vide les logs
   */
  clearLogs() {
    this.logs = [];
  }

  /**
   * Parse une erreur API et extrait les informations pertinentes
   */
  private parseApiError(error: unknown): ApiError {
    const axiosError = error as AxiosError<{
      message?: string;
      errors?: Record<string, string[]>;
    }>;

    const statusCode = axiosError.response?.status;
    const message = axiosError.response?.data?.message || axiosError.message || "Une erreur est survenue";
    const validationErrors = axiosError.response?.data?.errors;

    return {
      message,
      statusCode,
      validationErrors,
      timestamp: new Date().toISOString(),
    };
  }

  /**
   * Génère un message d'erreur lisible pour l'utilisateur
   */
  private getUserFriendlyMessage(apiError: ApiError): string {
    const { statusCode, message, validationErrors } = apiError;

    // Erreurs de validation
    if (statusCode === 422 && validationErrors) {
      const firstErrorKey = Object.keys(validationErrors)[0];
      const firstError = validationErrors[firstErrorKey]?.[0];
      return firstError || message;
    }

    // Erreurs HTTP standard
    switch (statusCode) {
      case 400:
        return "Requête invalide. Veuillez vérifier les données saisies.";
      case 401:
        return "Session expirée. Veuillez vous reconnecter.";
      case 403:
        return "Vous n'avez pas l'autorisation d'effectuer cette action.";
      case 404:
        return "La ressource demandée est introuvable.";
      case 409:
        return "Cette ressource existe déjà ou un conflit a été détecté.";
      case 429:
        return "Trop de requêtes. Veuillez patienter avant de réessayer.";
      case 500:
      case 502:
      case 503:
        return "Erreur serveur. Veuillez réessayer dans quelques instants.";
      default:
        return message;
    }
  }

  /**
   * Gère une erreur et affiche un toast approprié
   * @param error L'erreur à gérer
   * @param customMessage Message personnalisé optionnel
   * @param showToast Si false, ne pas afficher de toast (pour gestion manuelle)
   * @returns L'objet ApiError parsé
   */
  handle(error: unknown, customMessage?: string, showToast = true): ApiError {
    const apiError = this.parseApiError(error);
    const userMessage = customMessage || this.getUserFriendlyMessage(apiError);

    // Log l'erreur
    this.log("error", userMessage, {
      originalError: error,
      apiError,
    });

    // Afficher le toast si demandé
    if (showToast) {
      this.toast.error(userMessage);
    }

    return apiError;
  }

  /**
   * Gère une erreur avec les erreurs de validation
   * @param error L'erreur à gérer
   * @returns Les erreurs de validation ou null
   */
  handleValidation(error: unknown): Record<string, string> | null {
    const apiError = this.parseApiError(error);

    if (apiError.statusCode === 422 && apiError.validationErrors) {
      // Convertir les erreurs de validation en format simple
      const errors: Record<string, string> = {};
      Object.entries(apiError.validationErrors).forEach(([key, messages]) => {
        errors[key] = messages[0]; // Prendre le premier message
      });

      this.log("warning", "Erreurs de validation", errors);
      return errors;
    }

    // Si ce n'est pas une erreur de validation, gérer comme erreur normale
    this.handle(error);
    return null;
  }

  /**
   * Log un warning
   */
  warning(message: string, details?: unknown) {
    this.log("warning", message, details);
  }

  /**
   * Log une info
   */
  info(message: string, details?: unknown) {
    this.log("info", message, details);
  }

  /**
   * Gère les erreurs globales non capturées
   */
  setupGlobalHandlers() {
    // Erreurs non capturées
    window.addEventListener("error", (event) => {
      this.log("error", "Erreur non capturée", {
        message: event.message,
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno,
        error: event.error,
      });
    });

    // Promesses rejetées non gérées
    window.addEventListener("unhandledrejection", (event) => {
      this.log("error", "Promise rejetée non gérée", {
        reason: event.reason,
      });
    });
  }
}

// Singleton
export const errorHandler = new ErrorHandler();

// Setup automatique au démarrage
if (typeof window !== "undefined") {
  errorHandler.setupGlobalHandlers();
}
