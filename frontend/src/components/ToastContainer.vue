<template>
  <div class="fixed top-4 right-4 z-[9999] space-y-2">
    <Toast
      v-for="toast in toasts"
      :key="toast.id"
      :message="toast.message"
      :type="toast.type"
      :duration="toast.duration"
      @close="removeToast(toast.id)"
    />
  </div>
</template>

<script setup lang="ts">
import { useToast } from "@/composables/useToast";
import Toast from "./Toast.vue";

const { toasts, removeToast } = useToast();
</script>
