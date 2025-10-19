<template>
  <ConfirmModal
    :is-open="state.isOpen"
    :title="state.title || ''"
    :message="state.message"
    :confirm-text="state.confirmText || 'Confirmer'"
    :cancel-text="state.cancelText || 'Annuler'"
    :confirm-class="state.confirmClass || 'bg-red-600 hover:bg-red-700'"
    @confirm="handleConfirm"
    @cancel="handleCancel"
  />
</template>

<script setup lang="ts">
import { useConfirm } from "@/composables/useConfirm";
import ConfirmModal from "./ConfirmModal.vue";

const { state, handleConfirm, handleCancel } = useConfirm();
</script>
