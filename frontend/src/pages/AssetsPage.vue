<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold mb-6">Patrimoine</h1>

    <div class="mb-6 card">
      <div class="text-center">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Patrimoine total</p>
        <p class="text-4xl font-bold text-primary-600">
          <MoneyDisplay :cents="assetsStore.totalValue" />
        </p>
      </div>
    </div>

    <div class="card">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Actifs</h2>
        <button class="btn btn-primary" @click="openCreateModal">
          Ajouter un actif
        </button>
      </div>

      <div v-if="assetsStore.loading" class="text-center py-8">
        <p>Chargement...</p>
      </div>

      <div v-else-if="assetsStore.error" class="text-center py-8 text-red-600">
        <p>{{ assetsStore.error }}</p>
      </div>

      <div v-else-if="assetsStore.assets.length === 0" class="text-center py-8 text-gray-600">
        <p>Aucun actif enregistré</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="table">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th>Type</th>
              <th>Libellé</th>
              <th>Institution</th>
              <th>Valeur</th>
              <th>Notes</th>
              <th>Mise à jour</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="asset in assetsStore.assets" :key="asset.id">
              <td>
                <span class="px-2 py-1 text-xs rounded-full bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                  {{ formatAssetType(asset.type) }}
                </span>
              </td>
              <td class="font-medium">{{ asset.label }}</td>
              <td>{{ asset.institution || '-' }}</td>
              <td><MoneyDisplay :cents="asset.valueCents" /></td>
              <td class="max-w-xs truncate">{{ asset.notes || '-' }}</td>
              <td>{{ new Date(asset.updatedAt).toLocaleDateString('fr-FR') }}</td>
              <td>
                <div class="flex gap-2">
                  <button
                    @click="openEditModal(asset)"
                    class="text-blue-600 hover:text-blue-700 dark:text-blue-400 text-sm"
                  >
                    Modifier
                  </button>
                  <button
                    @click="handleDelete(asset.id)"
                    class="text-red-600 hover:text-red-700 dark:text-red-400 text-sm"
                  >
                    Supprimer
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <AssetFormModal
      :is-open="isModalOpen"
      :asset="selectedAsset"
      @close="closeModal"
      @submit="handleSubmit"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAssetsStore } from '@/stores/assets'
import MoneyDisplay from '@/components/MoneyDisplay.vue'
import AssetFormModal from '@/components/AssetFormModal.vue'
import type { Asset } from '@/types'

const assetsStore = useAssetsStore()
const isModalOpen = ref(false)
const selectedAsset = ref<Asset | null>(null)

onMounted(() => {
  assetsStore.fetchAssets()
})

function openCreateModal() {
  selectedAsset.value = null
  isModalOpen.value = true
}

function openEditModal(asset: Asset) {
  selectedAsset.value = asset
  isModalOpen.value = true
}

function closeModal() {
  isModalOpen.value = false
  selectedAsset.value = null
}

async function handleSubmit(values: any) {
  try {
    if (selectedAsset.value) {
      await assetsStore.updateAsset(selectedAsset.value.id, values)
    } else {
      await assetsStore.createAsset(values)
    }
    closeModal()
  } catch (error) {
    // Error handled in store
  }
}

async function handleDelete(id: number) {
  if (confirm('Êtes-vous sûr de vouloir supprimer cet actif ?')) {
    try {
      await assetsStore.deleteAsset(id)
    } catch (error) {
      // Error handled in store
    }
  }
}

function formatAssetType(type: string): string {
  return type.charAt(0).toUpperCase() + type.slice(1)
}
</script>
