<template>
  <div class="relative">
    <!-- Bell Icon Button -->
    <button
      @click.stop="toggleDropdown"
      class="relative p-2 text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
      aria-label="Notifications"
    >
      <svg
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
          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
        />
      </svg>

      <!-- Badge -->
      <span
        v-if="notificationsStore.unreadCount > 0"
        class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full"
      >
        {{ notificationsStore.unreadCount > 99 ? "99+" : notificationsStore.unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <Transition name="dropdown">
      <div
        v-if="isOpen"
        v-click-outside="closeDropdown"
        class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
      >
        <!-- Header -->
        <div
          class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center"
        >
          <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Notifications</h3>
          <button
            v-if="notificationsStore.unreadCount > 0"
            @click="handleMarkAllRead"
            class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
          >
            Tout marquer comme lu
          </button>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
          <div
            v-if="notificationsStore.unreadNotifications.length === 0"
            class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400"
          >
            Aucune nouvelle notification
          </div>

          <div v-else>
            <NotificationItem
              v-for="notification in notificationsStore.unreadNotifications.slice(0, 5)"
              :key="notification.id"
              :notification="notification"
              @click="handleNotificationClick(notification)"
              @mark-read="notificationsStore.markRead(notification.id)"
              @delete="notificationsStore.deleteNotification(notification.id)"
              compact
            />
          </div>
        </div>

        <!-- Footer -->
        <div
          v-if="notificationsStore.unreadNotifications.length > 0"
          class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 text-center"
        >
          <router-link
            to="/notifications"
            @click="closeDropdown"
            class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
          >
            Voir toutes les notifications
          </router-link>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";
import { useNotificationsStore } from "@/stores/notifications";
import NotificationItem from "./NotificationItem.vue";
import type { Notification } from "@/types";

const router = useRouter();
const notificationsStore = useNotificationsStore();
const isOpen = ref(false);

// Custom directive for click outside
interface HTMLElementWithClickOutside extends HTMLElement {
  clickOutsideEvent?: (event: Event) => void;
}

const vClickOutside = {
  mounted(el: HTMLElementWithClickOutside, binding: any) {
    el.clickOutsideEvent = (event: Event) => {
      if (!(el === event.target || el.contains(event.target as Node))) {
        binding.value(event);
      }
    };
    document.addEventListener("click", el.clickOutsideEvent);
  },
  unmounted(el: HTMLElementWithClickOutside) {
    if (el.clickOutsideEvent) {
      document.removeEventListener("click", el.clickOutsideEvent);
    }
  },
};

function toggleDropdown() {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    // Fetch latest unread notifications when opening
    notificationsStore.fetchNotifications(1, false);
  }
}

function closeDropdown() {
  isOpen.value = false;
}

async function handleMarkAllRead() {
  await notificationsStore.markAllRead();
  closeDropdown();
}

function handleNotificationClick(notification: Notification) {
  // Mark as read
  notificationsStore.markRead(notification.id);

  // Navigate to budget if data available
  if (notification.data?.budgetMonth) {
    router.push(`/budgets/${notification.data.budgetMonth}`);
    closeDropdown();
  }
}

// Start polling on mount
onMounted(() => {
  notificationsStore.startPolling();
});

// Stop polling on unmount
onUnmounted(() => {
  notificationsStore.stopPolling();
});
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
