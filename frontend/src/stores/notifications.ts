import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { notificationsApi, notificationSettingsApi } from "@/api/notifications";
import type { Notification, NotificationSettings, UpdateNotificationSettingsData } from "@/types";
import { errorHandler } from "@/utils/errorHandler";

export const useNotificationsStore = defineStore("notifications", () => {
  const notifications = ref<Notification[]>([]);
  const unreadCount = ref<number>(0);
  const settings = ref<NotificationSettings | null>(null);
  const loading = ref(false);
  const currentPage = ref(1);
  const totalPages = ref(1);

  let pollingInterval: number | null = null;

  // Computed
  const unreadNotifications = computed(() => notifications.value.filter((n) => !n.read));

  // Fetch paginated notifications
  async function fetchNotifications(page: number = 1, read?: boolean) {
    loading.value = true;
    try {
      const response = await notificationsApi.list(page, read);
      notifications.value = response.data;
      currentPage.value = response.current_page;
      totalPages.value = response.last_page;
    } catch (error) {
      errorHandler.handle(error, "Erreur lors du chargement des notifications");
    } finally {
      loading.value = false;
    }
  }

  // Fetch unread count (lightweight for polling)
  async function fetchUnreadCount() {
    try {
      unreadCount.value = await notificationsApi.unreadCount();
    } catch (error) {
      // Silent fail for polling - don't show error toast
      errorHandler.handle(error, undefined, false);
    }
  }

  // Mark notification as read
  async function markRead(id: number) {
    try {
      const updated = await notificationsApi.markRead(id);

      // Update in local state
      const index = notifications.value.findIndex((n) => n.id === id);
      if (index !== -1) {
        notifications.value[index] = updated;
      }

      // Update count
      await fetchUnreadCount();
    } catch (error) {
      errorHandler.handle(error, "Erreur lors du marquage de la notification");
    }
  }

  // Mark all as read
  async function markAllRead() {
    try {
      await notificationsApi.markAllRead();

      // Update all notifications in state
      notifications.value.forEach((n) => {
        n.read = true;
        n.readAt = new Date().toISOString();
      });

      unreadCount.value = 0;
    } catch (error) {
      errorHandler.handle(error, "Erreur lors du marquage des notifications");
    }
  }

  // Delete notification
  async function deleteNotification(id: number) {
    try {
      await notificationsApi.delete(id);

      // Remove from local state
      notifications.value = notifications.value.filter((n) => n.id !== id);

      // Update count
      await fetchUnreadCount();
    } catch (error) {
      errorHandler.handle(error, "Erreur lors de la suppression de la notification");
    }
  }

  // Clear all notifications
  async function clearAll() {
    try {
      await notificationsApi.clearAll();
      notifications.value = [];
      unreadCount.value = 0;
    } catch (error) {
      errorHandler.handle(error, "Erreur lors de la suppression des notifications");
    }
  }

  // Fetch settings
  async function fetchSettings() {
    loading.value = true;
    try {
      settings.value = await notificationSettingsApi.get();
    } catch (error) {
      errorHandler.handle(error, "Erreur lors du chargement des paramètres");
    } finally {
      loading.value = false;
    }
  }

  // Update settings
  async function updateSettings(data: UpdateNotificationSettingsData) {
    loading.value = true;
    try {
      settings.value = await notificationSettingsApi.update(data);
    } catch (error) {
      errorHandler.handle(error, "Erreur lors de la mise à jour des paramètres");
      throw error;
    } finally {
      loading.value = false;
    }
  }

  // Polling management
  function startPolling() {
    if (pollingInterval) return; // Already polling

    // Fetch immediately
    fetchUnreadCount();

    // Poll every 30 seconds
    pollingInterval = window.setInterval(() => {
      fetchUnreadCount();
    }, 30000);
  }

  function stopPolling() {
    if (pollingInterval) {
      clearInterval(pollingInterval);
      pollingInterval = null;
    }
  }

  return {
    notifications,
    unreadCount,
    unreadNotifications,
    settings,
    loading,
    currentPage,
    totalPages,
    fetchNotifications,
    fetchUnreadCount,
    markRead,
    markAllRead,
    deleteNotification,
    clearAll,
    fetchSettings,
    updateSettings,
    startPolling,
    stopPolling,
  };
});
