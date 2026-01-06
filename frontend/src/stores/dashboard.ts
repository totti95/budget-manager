import { defineStore } from "pinia";
import { ref } from "vue";
import { dashboardApi } from "@/api/dashboard";
import type { DashboardLayout, WidgetLayoutItem, WidgetSettings } from "@/types";

export const useDashboardStore = defineStore("dashboard", () => {
  const layout = ref<WidgetLayoutItem[]>([]);
  const widgetSettings = ref<WidgetSettings>({});
  const loading = ref(false);
  const error = ref<string | null>(null);
  const hasUnsavedChanges = ref(false);

  async function fetchLayout() {
    loading.value = true;
    error.value = null;
    try {
      const data = await dashboardApi.getLayout();
      layout.value = data.layoutConfig;
      widgetSettings.value = data.widgetSettings;
      hasUnsavedChanges.value = false;
    } catch (err) {
      error.value = "Erreur lors du chargement de la disposition";
      console.error(err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function saveLayout() {
    loading.value = true;
    error.value = null;
    try {
      const data = await dashboardApi.saveLayout({
        layoutConfig: layout.value,
        widgetSettings: widgetSettings.value,
      });
      layout.value = data.layoutConfig;
      widgetSettings.value = data.widgetSettings;
      hasUnsavedChanges.value = false;
    } catch (err) {
      error.value = "Erreur lors de la sauvegarde";
      console.error(err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function resetLayout() {
    loading.value = true;
    error.value = null;
    try {
      await dashboardApi.resetLayout();
      await fetchLayout();
      hasUnsavedChanges.value = false;
    } catch (err) {
      error.value = "Erreur lors de la réinitialisation";
      console.error(err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  function updateLayout(newLayout: WidgetLayoutItem[]) {
    layout.value = newLayout;
    hasUnsavedChanges.value = true;
  }

  function updateWidgetSettings(
    widgetId: string,
    settings: Record<string, any>,
  ) {
    widgetSettings.value[widgetId] = {
      ...widgetSettings.value[widgetId],
      ...settings,
    };
    hasUnsavedChanges.value = true;
  }

  return {
    layout,
    widgetSettings,
    loading,
    error,
    hasUnsavedChanges,
    fetchLayout,
    saveLayout,
    resetLayout,
    updateLayout,
    updateWidgetSettings,
  };
});
