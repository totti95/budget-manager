import { ref } from "vue";

interface Toast {
  id: number;
  message: string;
  type: "success" | "error" | "warning" | "info";
  duration: number;
}

const toasts = ref<Toast[]>([]);
let nextId = 0;

export function useToast() {
  const addToast = (message: string, type: Toast["type"] = "info", duration = 3000) => {
    const id = nextId++;
    toasts.value.push({ id, message, type, duration });
  };

  const removeToast = (id: number) => {
    const index = toasts.value.findIndex((t) => t.id === id);
    if (index !== -1) {
      toasts.value.splice(index, 1);
    }
  };

  const success = (message: string, duration = 3000) => {
    addToast(message, "success", duration);
  };

  const error = (message: string, duration = 4000) => {
    addToast(message, "error", duration);
  };

  const warning = (message: string, duration = 3500) => {
    addToast(message, "warning", duration);
  };

  const info = (message: string, duration = 3000) => {
    addToast(message, "info", duration);
  };

  return {
    toasts,
    addToast,
    removeToast,
    success,
    error,
    warning,
    info,
  };
}
