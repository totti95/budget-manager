<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-8">
      <h1 class="text-3xl font-bold">Notifications</h1>

      <div class="flex gap-2">
        <button
          v-if="notificationsStore.unreadCount > 0"
          @click="handleMarkAllRead"
          class="btn btn-secondary text-sm"
        >
          Tout marquer comme lu
        </button>
        <button
          v-if="notificationsStore.notifications.length > 0"
          @click="handleClearAll"
          class="btn btn-secondary text-sm text-red-600 hover:text-red-700"
        >
          Tout effacer
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex gap-4 mb-6">
      <button
        @click="selectedFilter = 'all'"
        :class="[
          'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
          selectedFilter === 'all'
            ? 'bg-primary-600 text-white'
            : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600',
        ]"
      >
        Toutes ({{ notificationsStore.notifications.length }})
      </button>
      <button
        @click="selectedFilter = 'unread'"
        :class="[
          'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
          selectedFilter === 'unread'
            ? 'bg-primary-600 text-white'
            : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600',
        ]"
      >
        Non lues ({{ notificationsStore.unreadCount }})
      </button>
      <button
        @click="selectedFilter = 'read'"
        :class="[
          'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
          selectedFilter === 'read'
            ? 'bg-primary-600 text-white'
            : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600',
        ]"
      >
        Lues
      </button>
    </div>

    <!-- Loading State -->
    <div
      v-if="notificationsStore.loading"
      class="text-center py-12"
    >
      <div
        class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"
      ></div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="notificationsStore.notifications.length === 0"
      class="text-center py-12"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-16 w-16 mx-auto text-gray-400 mb-4"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
        />
      </svg>
      <p class="text-gray-500 dark:text-gray-400">Aucune notification</p>
    </div>

    <!-- Notifications List -->
    <div
      v-else
      class="space-y-0 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden"
    >
      <NotificationItem
        v-for="notification in notificationsStore.notifications"
        :key="notification.id"
        :notification="notification"
        @click="handleNotificationClick(notification)"
        @mark-read="notificationsStore.markRead(notification.id)"
        @delete="handleDelete(notification.id)"
      />
    </div>

    <!-- Pagination -->
    <div
      v-if="notificationsStore.totalPages > 1"
      class="flex justify-center gap-2 mt-6"
    >
      <button
        @click="changePage(notificationsStore.currentPage - 1)"
        :disabled="notificationsStore.currentPage === 1"
        class="btn btn-secondary text-sm"
        :class="{
          'opacity-50 cursor-not-allowed':
            notificationsStore.currentPage === 1,
        }"
      >
        Précédent
      </button>

      <span class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
        Page {{ notificationsStore.currentPage }} /
        {{ notificationsStore.totalPages }}
      </span>

      <button
        @click="changePage(notificationsStore.currentPage + 1)"
        :disabled="
          notificationsStore.currentPage === notificationsStore.totalPages
        "
        class="btn btn-secondary text-sm"
        :class="{
          'opacity-50 cursor-not-allowed':
            notificationsStore.currentPage === notificationsStore.totalPages,
        }"
      >
        Suivant
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useNotificationsStore } from "@/stores/notifications";
import { useToast } from "@/composables/useToast";
import NotificationItem from "@/components/NotificationItem.vue";
import type { Notification } from "@/types";

const router = useRouter();
const notificationsStore = useNotificationsStore();
const toast = useToast();

const selectedFilter = ref<"all" | "unread" | "read">("all");

// Fetch notifications based on filter
async function fetchWithFilter() {
  const readFilter =
    selectedFilter.value === "unread"
      ? false
      : selectedFilter.value === "read"
      ? true
      : undefined;

  await notificationsStore.fetchNotifications(1, readFilter);
}

// Watch filter changes
watch(selectedFilter, () => {
  fetchWithFilter();
});

// Change page
function changePage(page: number) {
  const readFilter =
    selectedFilter.value === "unread"
      ? false
      : selectedFilter.value === "read"
      ? true
      : undefined;

  notificationsStore.fetchNotifications(page, readFilter);
}

// Mark all as read
async function handleMarkAllRead() {
  await notificationsStore.markAllRead();
  toast.success("Toutes les notifications ont été marquées comme lues");
  fetchWithFilter();
}

// Clear all
async function handleClearAll() {
  if (
    confirm(
      "Êtes-vous sûr de vouloir supprimer toutes les notifications ? Cette action est irréversible."
    )
  ) {
    await notificationsStore.clearAll();
    toast.success("Toutes les notifications ont été supprimées");
  }
}

// Delete single notification
async function handleDelete(id: number) {
  if (confirm("Voulez-vous supprimer cette notification ?")) {
    await notificationsStore.deleteNotification(id);
    toast.success("Notification supprimée");
  }
}

// Handle notification click
function handleNotificationClick(notification: Notification) {
  // Mark as read
  if (!notification.read) {
    notificationsStore.markRead(notification.id);
  }

  // Navigate to budget if data available
  if (notification.data?.budgetMonth) {
    router.push(`/budgets/${notification.data.budgetMonth}`);
  }
}

// Load on mount
onMounted(() => {
  fetchWithFilter();
});
</script>
