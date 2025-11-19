import { defineStore } from "pinia";
import { ref } from "vue";
import { assetsApi } from "@/api/assets";
import type { Asset, AssetType } from "@/types";
import type { CreateAssetData } from "@/api/assets";

export const useAssetsStore = defineStore("assets", () => {
  const assets = ref<Asset[]>([]);
  const liabilities = ref<Asset[]>([]);
  const totalAssetsCents = ref(0);
  const totalLiabilitiesCents = ref(0);
  const netWorthCents = ref(0);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function fetchAssets(type?: AssetType) {
    loading.value = true;
    error.value = null;
    try {
      const response = await assetsApi.list(type);
      assets.value = response.assets;
      liabilities.value = response.liabilities;
      totalAssetsCents.value = response.totalAssetsCents;
      totalLiabilitiesCents.value = response.totalLiabilitiesCents;
      netWorthCents.value = response.netWorthCents;
      return response;
    } catch (err) {
      error.value = "Erreur lors du chargement des actifs";
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function createAsset(data: CreateAssetData) {
    loading.value = true;
    error.value = null;
    try {
      const asset = await assetsApi.create(data);
      if (asset.isLiability) {
        liabilities.value.unshift(asset);
        totalLiabilitiesCents.value += asset.valueCents;
      } else {
        assets.value.unshift(asset);
        totalAssetsCents.value += asset.valueCents;
      }
      netWorthCents.value = totalAssetsCents.value - totalLiabilitiesCents.value;
      return asset;
    } catch (err) {
      error.value = "Erreur lors de la création de l'actif";
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateAsset(id: number, data: Partial<CreateAssetData>) {
    loading.value = true;
    error.value = null;
    try {
      const asset = await assetsApi.update(id, data);

      // Remove from old list
      const assetIndex = assets.value.findIndex((a) => a.id === id);
      const liabilityIndex = liabilities.value.findIndex((a) => a.id === id);

      if (assetIndex !== -1) {
        totalAssetsCents.value -= assets.value[assetIndex].valueCents;
        assets.value.splice(assetIndex, 1);
      }
      if (liabilityIndex !== -1) {
        totalLiabilitiesCents.value -= liabilities.value[liabilityIndex].valueCents;
        liabilities.value.splice(liabilityIndex, 1);
      }

      // Add to new list
      if (asset.isLiability) {
        liabilities.value.unshift(asset);
        totalLiabilitiesCents.value += asset.valueCents;
      } else {
        assets.value.unshift(asset);
        totalAssetsCents.value += asset.valueCents;
      }

      netWorthCents.value = totalAssetsCents.value - totalLiabilitiesCents.value;
      return asset;
    } catch (err) {
      error.value = "Erreur lors de la mise à jour de l'actif";
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function deleteAsset(id: number) {
    loading.value = true;
    error.value = null;
    try {
      await assetsApi.delete(id);

      const asset = assets.value.find((a) => a.id === id);
      const liability = liabilities.value.find((a) => a.id === id);

      if (asset) {
        totalAssetsCents.value -= asset.valueCents;
        assets.value = assets.value.filter((a) => a.id !== id);
      }
      if (liability) {
        totalLiabilitiesCents.value -= liability.valueCents;
        liabilities.value = liabilities.value.filter((a) => a.id !== id);
      }

      netWorthCents.value = totalAssetsCents.value - totalLiabilitiesCents.value;
    } catch (err) {
      error.value = "Erreur lors de la suppression de l'actif";
      throw err;
    } finally {
      loading.value = false;
    }
  }

  return {
    assets,
    liabilities,
    totalAssetsCents,
    totalLiabilitiesCents,
    netWorthCents,
    loading,
    error,
    fetchAssets,
    createAsset,
    updateAsset,
    deleteAsset,
  };
});
