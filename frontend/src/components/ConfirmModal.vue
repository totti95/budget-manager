<template>
  <Transition name="modal">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
      @click.self="onCancel"
    >
      <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 overflow-hidden"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="title ? 'confirm-title' : undefined"
      >
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3
            id="confirm-title"
            class="text-lg font-semibold text-gray-900 dark:text-gray-100"
          >
            {{ title }}
          </h3>
        </div>

        <!-- Content -->
        <div class="px-6 py-4">
          <p class="text-gray-700 dark:text-gray-300">
            {{ message }}
          </p>
        </div>

        <!-- Actions -->
        <div
          class="px-6 py-4 bg-gray-50 dark:bg-gray-900 flex gap-3 justify-end"
        >
          <button
            type="button"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
            @click="onCancel"
            @keydown.enter="onCancel"
          >
            {{ cancelText }}
          </button>
          <button
            type="button"
            :class="[
              'px-4 py-2 text-sm font-medium text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500',
              confirmClass,
            ]"
            @click="onConfirm"
            @keydown.enter="onConfirm"
            autofocus
          >
            {{ confirmText }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';

defineProps<{
  isOpen: boolean;
  title: string;
  message: string;
  confirmText: string;
  cancelText: string;
  confirmClass: string;
}>();

const emit = defineEmits<{
  confirm: [];
  cancel: [];
}>();

const onConfirm = () => {
  emit('confirm');
};

const onCancel = () => {
  emit('cancel');
};

// Gestion du clavier
const handleKeyDown = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    onCancel();
  }
};

// Ajouter/retirer le listener au montage/démontage
onMounted(() => {
  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .bg-white,
.modal-leave-active .bg-white {
  transition: transform 0.3s ease;
}

.modal-enter-from .bg-white {
  transform: scale(0.9);
}

.modal-leave-to .bg-white {
  transform: scale(0.9);
}
</style>
