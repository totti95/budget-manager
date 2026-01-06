import api from "./axios";
import type { DashboardLayout } from "@/types";

export const dashboardApi = {
  async getLayout(): Promise<DashboardLayout> {
    const response = await api.get<DashboardLayout>("/dashboard/layout");
    return response.data;
  },

  async saveLayout(
    layout: Pick<DashboardLayout, "layoutConfig" | "widgetSettings">
  ): Promise<DashboardLayout> {
    const response = await api.put<DashboardLayout>("/dashboard/layout", layout);
    return response.data;
  },

  async resetLayout(): Promise<void> {
    await api.delete("/dashboard/layout");
  },
};
