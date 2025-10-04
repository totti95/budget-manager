<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center">
    <!-- Backdrop -->
    <div
      class="absolute inset-0 bg-black bg-opacity-50"
      @click="close"
    />

    <!-- Modal -->
    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-bold">
          {{ asset ? 'Modifier le patrimoine' : 'Ajouter un patrimoine' }}
        </h3>
        <button
          type="button"
          @click="close"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form @submit="onSubmit" class="space-y-4">
        <div>
          <label for="type" class="label">Type</label>
          <TypeInput
            :model-value="type || ''"
            @update:model-value="(value: string) => type = value"
            :suggestions="availableTypes"
            :input-class="errors.type ? 'border-red-500' : ''"
          />
          <p v-if="errors.type" class="mt-1 text-sm text-red-600">{{ errors.type }}</p>
          <p class="mt-1 text-xs text-gray-500">Tapez pour créer un nouveau type ou choisissez dans la liste</p>
        </div>

        <div>
          <label for="label" class="label">Libellé</label>
          <input
            id="label"
            v-model="label"
            v-bind="labelAttrs"
            type="text"
            class="input"
            :class="{ 'border-red-500': errors.label }"
            placeholder="Ex: Résidence principale, Livret A..."
          />
          <p v-if="errors.label" class="mt-1 text-sm text-red-600">{{ errors.label }}</p>
        </div>

        <div>
          <label for="institution" class="label">Institution (optionnel)</label>
          <input
            id="institution"
            v-model="institution"
            v-bind="institutionAttrs"
            type="text"
            class="input"
            :class="{ 'border-red-500': errors.institution }"
            placeholder="Ex: Banque Populaire, Crédit Agricole..."
          />
          <p v-if="errors.institution" class="mt-1 text-sm text-red-600">{{ errors.institution }}</p>
        </div>

        <div>
          <label for="value" class="label">Valeur (€)</label>
          <input
            id="value"
            v-model="value"
            v-bind="valueAttrs"
            type="number"
            step="0.01"
            min="0"
            class="input"
            :class="{ 'border-red-500': errors.value_cents }"
            placeholder="0.00"
          />
          <p v-if="errors.value_cents" class="mt-1 text-sm text-red-600">{{ errors.value_cents }}</p>
        </div>

        <div>
          <label for="notes" class="label">Notes (optionnel)</label>
          <textarea
            id="notes"
            v-model="notes"
            v-bind="notesAttrs"
            rows="3"
            class="input"
            :class="{ 'border-red-500': errors.notes }"
            placeholder="Notes supplémentaires..."
          />
          <p v-if="errors.notes" class="mt-1 text-sm text-red-600">{{ errors.notes }}</p>
        </div>

        <div class="flex gap-2 pt-2">
          <button
            type="submit"
            :disabled="isSubmitting"
            class="flex-1 btn btn-primary"
          >
            {{ isSubmitting ? 'Enregistrement...' : (asset ? 'Modifier' : 'Ajouter') }}
          </button>
          <button
            type="button"
            @click="close"
            class="flex-1 btn btn-secondary"
          >
            Annuler
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, watch, ref, onMounted } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { assetSchema } from '@/schemas/asset'
import { assetsApi } from '@/api/assets'
import TypeInput from './TypeInput.vue'
import type { Asset } from '@/types'

interface Props {
  isOpen: boolean
  asset?: Asset | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  submit: [values: {
    type: string
    label: string
    institution?: string
    value_cents: number
    notes?: string
  }]
}>()

const availableTypes = ref<string[]>([])

// Convert euros to cents for validation
const valueToCents = (value: number) => Math.round(value * 100)
const centsToValue = (cents: number) => cents / 100

const { errors, defineField, handleSubmit, isSubmitting, resetForm } = useForm({
  validationSchema: toTypedSchema(assetSchema),
  initialValues: {
    type: '',
    label: '',
    institution: '',
    value_cents: 0,
    notes: ''
  }
})

// Load available types
async function loadTypes() {
  try {
    availableTypes.value = await assetsApi.getTypes()
  } catch (error) {
    console.error('Failed to load asset types:', error)
    availableTypes.value = []
  }
}

// Load types on mount
onMounted(() => {
  loadTypes()
})

// Watch for modal opening to refresh types
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    loadTypes()
  }
})

// Watch for asset changes to update form
watch(() => props.asset, (newAsset) => {
  if (newAsset) {
    resetForm({
      values: {
        type: newAsset.type,
        label: newAsset.label,
        institution: newAsset.institution || '',
        value_cents: newAsset.valueCents,
        notes: newAsset.notes || ''
      }
    })
  } else {
    resetForm()
  }
}, { immediate: true })

const [type, typeAttrs] = defineField('type')
const [label, labelAttrs] = defineField('label')
const [institution, institutionAttrs] = defineField('institution')
const [notes, notesAttrs] = defineField('notes')

// Handle value separately to convert between euros and cents
const [valueCents, valueAttrs] = defineField('value_cents')
const value = computed({
  get: () => valueCents.value ? centsToValue(valueCents.value as number) : 0,
  set: (val) => {
    valueCents.value = valueToCents(Number(val))
  }
})

const onSubmit = handleSubmit((values) => {
  emit('submit', values)
  resetForm()
})

function close() {
  emit('close')
  resetForm()
}
</script>
