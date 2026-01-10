import api from "./axios";
import type {
  Notification,
  NotificationSettings,
  UpdateNotificationSettingsData,
  PaginatedResponse,
} from "@/types";

export const notificationsApi = {
  async list(
    page: number = 1,
    read?: boolean,
    type?: string
  ): Promise<PaginatedResponse<Notification>> {
    interface NotificationParams {
      page: number;
      read?: boolean;
      type?: string;
    }

    const params: NotificationParams = { page };
    if (read !== undefined) params.read = read;
    if (type) params.type = type;

    const response = await api.get<PaginatedResponse<Notification>>("/notifications", { params });
    return response.data;
  },

  async unreadCount(): Promise<number> {
    const response = await api.get<{ count: number }>("/notifications/unread-count");
    return response.data.count;
  },

  async markRead(id: number): Promise<Notification> {
    const response = await api.put<Notification>(`/notifications/${id}/mark-read`);
    return response.data;
  },

  async markAllRead(): Promise<void> {
    await api.put("/notifications/mark-all-read");
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/notifications/${id}`);
  },

  async clearAll(): Promise<void> {
    await api.delete("/notifications");
  },
};

export const notificationSettingsApi = {
  async get(): Promise<NotificationSettings> {
    const response = await api.get<NotificationSettings>("/notification-settings");
    return response.data;
  },

  async update(data: UpdateNotificationSettingsData): Promise<NotificationSettings> {
    const response = await api.put<NotificationSettings>("/notification-settings", data);
    return response.data;
  },
};
