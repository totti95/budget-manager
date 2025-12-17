<template>
  <div
    :class="[
      'px-4 py-3 cursor-pointer transition-colors border-b border-gray-100 dark:border-gray-700',
      !notification.read
        ? 'bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30'
        : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700',
      compact ? 'text-sm' : '',
    ]"
    @click="$emit('click')"
  >
    <div class="flex items-start gap-3">
      <!-- Icon -->
      <div
        :class="[
          'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center',
          notification.type === 'budget_exceeded'
            ? 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400'
            : 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
        ]"
      >
        <svg
          v-if="notification.type === 'budget_exceeded'"
          xmlns="http://www.w3.org/2000/svg"
          class="h-6 w-6"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
        <svg
          v-else
          xmlns="http://www.w3.org/2000/svg"
          class="h-6 w-6"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between gap-2">
          <div class="flex-1">
            <p
              :class="[
                'font-medium',
                !notification.read
                  ? 'text-gray-900 dark:text-white'
                  : 'text-gray-700 dark:text-gray-300',
              ]"
            >
              {{ notification.title }}
            </p>
            <p
              :class="[
                'mt-1',
                compact ? 'text-xs' : 'text-sm',
                'text-gray-600 dark:text-gray-400',
              ]"
            >
              {{ notification.message }}
            </p>

            <!-- Metadata -->
            <div
              class="flex items-center gap-2 mt-2 text-xs text-gray-500 dark:text-gray-500"
            >
              <span>{{ formatDate(notification.createdAt) }}</span>
              <span v-if="notification.data?.categoryName">•</span>
              <span v-if="notification.data?.categoryName">
                {{ notification.data.categoryName }}
              </span>
            </div>
          </div>

          <!-- Unread dot -->
          <div v-if="!notification.read" class="flex-shrink-0">
            <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
          </div>
        </div>

        <!-- Actions (only if not compact) -->
        <div v-if="!compact" class="flex gap-2 mt-3">
          <button
            v-if="!notification.read"
            @click.stop="$emit('mark-read')"
            class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
          >
            Marquer comme lu
          </button>
          <button
            @click.stop="$emit('delete')"
            class="text-xs text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
          >
            Supprimer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Notification } from "@/types";

interface Props {
  notification: Notification;
  compact?: boolean;
}

withDefaults(defineProps<Props>(), {
  compact: false,
});

defineEmits<{
  click: [];
  "mark-read": [];
  delete: [];
}>();

// Format relative date
function formatDate(dateString: string): string {
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);

  if (diffMins < 1) return "À l'instant";
  if (diffMins < 60) return `Il y a ${diffMins} min`;
  if (diffHours < 24) return `Il y a ${diffHours}h`;
  if (diffDays < 7) return `Il y a ${diffDays}j`;

  return date.toLocaleDateString("fr-FR", {
    day: "numeric",
    month: "short",
  });
}
</script>
