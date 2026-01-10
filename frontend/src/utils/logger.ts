/**
 * Utilitaire de logging pour l'application
 *
 * En développement: affiche les logs dans la console
 * En production: les console.* sont automatiquement supprimés par Vite (esbuild)
 *
 * Pour une solution de logging avancée en production, intégrer Sentry ou équivalent
 */

export const logger = {
  /**
   * Log d'information (développement uniquement)
   */
  log(message: string, ...args: unknown[]): void {
    if (import.meta.env.DEV) {
      console.log(`[INFO] ${message}`, ...args);
    }
  },

  /**
   * Log d'erreur (développement + production si intégration Sentry)
   */
  error(message: string, error?: Error | unknown): void {
    if (import.meta.env.DEV) {
      console.error(`[ERROR] ${message}`, error);
    } else {
      // TODO: Envoyer à Sentry ou service de monitoring en production
      // Sentry.captureException(error, { message });
    }
  },

  /**
   * Log d'avertissement (développement uniquement)
   */
  warn(message: string, ...args: unknown[]): void {
    if (import.meta.env.DEV) {
      console.warn(`[WARN] ${message}`, ...args);
    }
  },

  /**
   * Log de debug (développement uniquement)
   */
  debug(message: string, ...args: unknown[]): void {
    if (import.meta.env.DEV) {
      console.debug(`[DEBUG] ${message}`, ...args);
    }
  },

  /**
   * Grouper des logs (développement uniquement)
   */
  group(label: string, callback: () => void): void {
    if (import.meta.env.DEV) {
      console.group(label);
      callback();
      console.groupEnd();
    } else {
      callback();
    }
  },
};

export default logger;
