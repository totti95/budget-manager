import { defineStore } from 'pinia'
import { ref } from 'vue'
import { assetsApi } from '@/api/assets'
import type { Asset, AssetType } from '@/types'
import type { CreateAssetData } from '@/api/assets'

export const useAssetsStore = defineStore('assets', () => {
  const assets = ref<Asset[]>([])
  const totalValue = ref(0)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchAssets(type?: AssetType) {
    loading.value = true
    error.value = null
    try {
      const response = await assetsApi.list(type)
      assets.value = response.assets
      totalValue.value = response.totalValueCents
      return response
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des actifs'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createAsset(data: CreateAssetData) {
    loading.value = true
    error.value = null
    try {
      const asset = await assetsApi.create(data)
      assets.value.unshift(asset)
      totalValue.value += asset.valueCents
      return asset
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erreur lors de la création de l\'actif'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateAsset(id: number, data: Partial<CreateAssetData>) {
    loading.value = true
    error.value = null
    try {
      const asset = await assetsApi.update(id, data)
      const index = assets.value.findIndex((a) => a.id === id)
      if (index !== -1) {
        const oldValue = assets.value[index].valueCents
        assets.value[index] = asset
        totalValue.value = totalValue.value - oldValue + asset.valueCents
      }
      return asset
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erreur lors de la mise à jour de l\'actif'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteAsset(id: number) {
    loading.value = true
    error.value = null
    try {
      await assetsApi.delete(id)
      const asset = assets.value.find((a) => a.id === id)
      if (asset) {
        totalValue.value -= asset.valueCents
      }
      assets.value = assets.value.filter((a) => a.id !== id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erreur lors de la suppression de l\'actif'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    assets,
    totalValue,
    loading,
    error,
    fetchAssets,
    createAsset,
    updateAsset,
    deleteAsset
  }
})
