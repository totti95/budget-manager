import type { Ref } from "vue";
import { useToast } from "./useToast";

// Type guard pour les erreurs Axios
interface AxiosError {
  response?: {
    data?: {
      message?: string;
    };
  };
}

function isAxiosError(err: unknown): err is AxiosError {
  return typeof err === "object" && err !== null && "response" in err;
}

export interface StoreActionOptions {
  successMessage?: string;
  errorMessage?: string;
  onSuccess?: () => void;
  onError?: (error: Error) => void;
}

/**
 * Wrapper pour actions asynchrones dans les stores Pinia
 * Gère automatiquement loading, error, et toasts
 *
 * @param action - La fonction asynchrone à exécuter
 * @param loadingRef - Référence au state loading du store
 * @param errorRef - Référence au state error du store
 * @param options - Options pour messages toast et callbacks
 * @returns Le résultat de l'action
 *
 * @example
 * ```ts
 * // Dans un store Pinia
 * const loading = ref(false);
 * const error = ref<string | null>(null);
 *
 * async function createBudget(data: CreateBudgetData) {
 *   return await executeStoreAction(
 *     async () => {
 *       const budget = await budgetsApi.create(data);
 *       budgets.value.push(budget);
 *       return budget;
 *     },
 *     loading,
 *     error,
 *     { successMessage: "Budget créé avec succès" }
 *   );
 * }
 * ```
 */
export async function executeStoreAction<T>(
  action: () => Promise<T>,
  loadingRef: Ref<boolean>,
  errorRef: Ref<string | null>,
  options: StoreActionOptions = {}
): Promise<T> {
  const { success, error: errorToast } = useToast();

  loadingRef.value = true;
  errorRef.value = null;

  try {
    const result = await action();

    if (options.successMessage) {
      success(options.successMessage);
    }

    options.onSuccess?.();

    return result;
  } catch (err: unknown) {
    const errorMessage =
      (isAxiosError(err) && err.response?.data?.message) ||
      options.errorMessage ||
      "Une erreur est survenue";

    errorRef.value = errorMessage;
    errorToast(errorMessage);

    const errorObj = err instanceof Error ? err : new Error(String(err));
    options.onError?.(errorObj);

    throw errorObj;
  } finally {
    loadingRef.value = false;
  }
}
