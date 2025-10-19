import { ref } from "vue";

interface ConfirmOptions {
  title?: string;
  message: string;
  confirmText?: string;
  cancelText?: string;
  confirmClass?: string;
}

interface ConfirmState extends ConfirmOptions {
  isOpen: boolean;
  resolve: ((value: boolean) => void) | null;
}

const state = ref<ConfirmState>({
  isOpen: false,
  title: "",
  message: "",
  confirmText: "Confirmer",
  cancelText: "Annuler",
  confirmClass: "bg-red-600 hover:bg-red-700",
  resolve: null,
});

export function useConfirm() {
  const confirm = (options: string | ConfirmOptions): Promise<boolean> => {
    return new Promise((resolve) => {
      const opts = typeof options === "string" ? { message: options } : options;

      state.value = {
        isOpen: true,
        title: opts.title || "Confirmation",
        message: opts.message,
        confirmText: opts.confirmText || "Confirmer",
        cancelText: opts.cancelText || "Annuler",
        confirmClass: opts.confirmClass || "bg-red-600 hover:bg-red-700",
        resolve,
      };
    });
  };

  const handleConfirm = () => {
    if (state.value.resolve) {
      state.value.resolve(true);
    }
    closeModal();
  };

  const handleCancel = () => {
    if (state.value.resolve) {
      state.value.resolve(false);
    }
    closeModal();
  };

  const closeModal = () => {
    state.value.isOpen = false;
    state.value.resolve = null;
  };

  return {
    state,
    confirm,
    handleConfirm,
    handleCancel,
  };
}
