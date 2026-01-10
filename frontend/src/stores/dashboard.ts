import { defineStore } from "pinia";
import { ref } from "vue";
import { dashboardApi } from "@/api/dashboard";
import { executeStoreAction } from "@/composables/useStoreAction";
import type { WidgetLayoutItem, WidgetSettings, WidgetConfig } from "@/types";

export const useDashboardStore = defineStore("dashboard", () => {
  const layout = ref<WidgetLayoutItem[]>([]);
  const widgetSettings = ref<WidgetSettings>({});
  const loading = ref(false);
  const error = ref<string | null>(null);
  const hasUnsavedChanges = ref(false);

  async function fetchLayout() {
    return await executeStoreAction(
      async () => {
        const data = await dashboardApi.getLayout();
        layout.value = data.layoutConfig;
        widgetSettings.value = data.widgetSettings;
        hasUnsavedChanges.value = false;
      },
      loading,
      error,
      { errorMessage: "Erreur lors du chargement de la disposition" }
    );
  }

  async function saveLayout() {
    return await executeStoreAction(
      async () => {
        const data = await dashboardApi.saveLayout({
          layoutConfig: layout.value,
          widgetSettings: widgetSettings.value,
        });
        layout.value = data.layoutConfig;
        widgetSettings.value = data.widgetSettings;
        hasUnsavedChanges.value = false;
      },
      loading,
      error,
      { errorMessage: "Erreur lors de la sauvegarde" }
    );
  }

  async function resetLayout() {
    return await executeStoreAction(
      async () => {
        await dashboardApi.resetLayout();
        await fetchLayout();
        hasUnsavedChanges.value = false;
      },
      loading,
      error,
      { errorMessage: "Erreur lors de la réinitialisation" }
    );
  }

  function updateLayout(newLayout: WidgetLayoutItem[]) {
    layout.value = newLayout;
    hasUnsavedChanges.value = true;
  }

  function updateWidgetSettings(widgetId: string, settings: Partial<WidgetConfig>) {
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
