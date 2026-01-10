import { ref, shallowRef } from "vue";
import type { Ref, ShallowRef } from "vue";

export interface AsyncActionOptions<T> {
  onSuccess?: (data: T) => void;
  onError?: (error: Error) => void;
}

export interface AsyncActionReturn<T> {
  loading: Ref<boolean>;
  error: Ref<Error | null>;
  data: ShallowRef<T | null>;
  execute: () => Promise<T>;
}

/**
 * Composable pour gérer les actions asynchrones avec gestion d'état
 * @param action - La fonction asynchrone à exécuter
 * @param options - Options de callbacks onSuccess et onError
 * @returns Objet contenant loading, error, data et execute
 *
 * @example
 * ```ts
 * const { loading, error, execute } = useAsyncAction(
 *   async () => await api.fetchData(),
 *   {
 *     onSuccess: (data) => console.log('Success:', data),
 *     onError: (err) => console.error('Error:', err)
 *   }
 * );
 *
 * await execute();
 * ```
 */
export function useAsyncAction<T>(
  action: () => Promise<T>,
  options?: AsyncActionOptions<T>
): AsyncActionReturn<T> {
  const loading = ref(false);
  const error = ref<Error | null>(null);
  const data = shallowRef<T | null>(null);

  const execute = async (): Promise<T> => {
    loading.value = true;
    error.value = null;
    try {
      const result = await action();
      data.value = result;
      options?.onSuccess?.(result);
      return result;
    } catch (err) {
      const errorObj = err instanceof Error ? err : new Error(String(err));
      error.value = errorObj;
      options?.onError?.(errorObj);
      throw errorObj;
    } finally {
      loading.value = false;
    }
  };

  return { loading, error, data, execute };
}
