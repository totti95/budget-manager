import api from './axios'
import type { Asset, AssetType } from '@/types'

export interface CreateAssetData {
  type: AssetType
  label: string
  institution?: string
  value_cents: number
  notes?: string
}

// Transform snake_case to camelCase
function transformAsset(data: any): Asset {
  return {
    id: data.id,
    userId: data.user_id,
    type: data.type,
    label: data.label,
    institution: data.institution,
    valueCents: data.value_cents,
    notes: data.notes,
    createdAt: data.created_at,
    updatedAt: data.updated_at
  }
}

export const assetsApi = {
  async getTypes(): Promise<string[]> {
    const response = await api.get<{ types: string[] }>('/assets/types')
    return response.data.types
  },

  async list(type?: AssetType): Promise<{ assets: Asset[]; totalValueCents: number }> {
    const params = type ? { type } : {}
    const response = await api.get('/assets', { params })
    return {
      assets: response.data.assets.map(transformAsset),
      totalValueCents: response.data.totalValueCents
    }
  },

  async get(id: number): Promise<Asset> {
    const response = await api.get(`/assets/${id}`)
    return transformAsset(response.data)
  },

  async create(data: CreateAssetData): Promise<Asset> {
    const response = await api.post('/assets', data)
    return transformAsset(response.data)
  },

  async update(id: number, data: Partial<CreateAssetData>): Promise<Asset> {
    const response = await api.put(`/assets/${id}`, data)
    return transformAsset(response.data)
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/assets/${id}`)
  }
}
