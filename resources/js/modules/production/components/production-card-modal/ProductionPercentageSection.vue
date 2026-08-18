<template>
  <div v-if="card.production_column_names_id >= 2" class="production-percentage-section">
    <div v-if="!isEditing"
      class="production-percentage-section-display d-flex justify-content-between"
    >
      <div>
        <strong>
          <span>Total produzido: </span>
        </strong>
        {{ formatProductionPercentage(card.production_percentage) }}%
        <button
          class="production-percentage-section-edit-btn"
          @click="startEditing"
          title="Editar porcentagem"
        >
          <IconEdit />
        </button>
      </div>

      <div>
        <button
          class="production-percentage-section-reopen-btn btn btn-secondary btn-sm"
          :disabled="reopening || isSaving"
          @click="emit('reopen')"
          v-if="formatProductionPercentage(card.production_percentage) == 100"
        >
          {{ reopening ? 'Reabrindo...' : 'Reabrir card' }}
        </button>
      </div>
    </div>
    <div v-else class="production-percentage-section-edit">
      <div class="production-percentage-section-input-wrapper">
        <span class="production-percentage-section-label">Total produzido:</span>
        <input
          v-model.number="productionPercentageText"
          type="number"
          class="production-percentage-section-input"
          min="0"
          max="100"
          step="0.1"
          placeholder="0.0"
          @keyup.enter="save"
          @keyup.esc="cancelEditing"
        />
        <span class="production-percentage-section-input-suffix">%</span>
      </div>
      <div class="production-percentage-section-actions">
        <button class="production-percentage-section-cancel" @click="cancelEditing">
          Cancelar
        </button>
        <button class="production-percentage-section-save" @click="save" :disabled="isSaving">
          {{ isSaving ? 'Salvando...' : 'Salvar' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useToast } from '@/composables/useToast';
import { IconEdit } from '@tabler/icons-vue';
import { productionService } from '@/services/productionService';
import { applyProductionPercentageUpdate } from '@/modules/production/composables/useReopenProductionCard';

const props = defineProps({
  card: {
    type: Object,
    required: true,
  },
  reopening: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['percentage-updated', 'reopen']);

const toast = useToast();

const isEditing = ref(false);
const productionPercentageText = ref(0);
const originalProductionPercentage = ref(0);
const isSaving = ref(false);

// Inicializar quando o card mudar
watch(
  () => props.card?.production_percentage,
  (newPercentage) => {
    if (newPercentage !== undefined) {
      productionPercentageText.value = newPercentage || 0;
      originalProductionPercentage.value = newPercentage || 0;
      isEditing.value = false;
    }
  },
  { immediate: true },
);

function formatProductionPercentage(value) {
  if (value === null || value === undefined) {
    return '0.0';
  }
  const numericValue = Number(value);
  return Number.isFinite(numericValue) ? numericValue.toFixed(1) : '0.0';
}

function startEditing() {
  originalProductionPercentage.value = props.card?.production_percentage || 0;
  productionPercentageText.value = originalProductionPercentage.value;
  isEditing.value = true;
}

function cancelEditing() {
  productionPercentageText.value = originalProductionPercentage.value;
  isEditing.value = false;
}

function applyUpdatedPercentage(updated, percentage) {
  applyProductionPercentageUpdate(props.card, updated);

  originalProductionPercentage.value = props.card?.production_percentage ?? percentage;
  productionPercentageText.value = originalProductionPercentage.value;
  isEditing.value = false;

  emit('percentage-updated', percentage);
}

async function save() {
  if (!props.card?.id || isSaving.value || props.reopening) {
    return;
  }

  // Validar valor
  const percentage = Number(productionPercentageText.value);
  if (isNaN(percentage) || percentage < 0 || percentage > 100) {
    if (window.Swal) {
      window.Swal.fire('Erro!', 'Porcentagem deve estar entre 0 e 100', 'error');
    } else {
      alert('Porcentagem deve estar entre 0 e 100');
    }
    return;
  }

  const isFullyProduced =
    props.card.production_percentage === 100 || Number(props.card.production_percentage) === 100;
  const isChangingFrom100 = isFullyProduced && percentage !== 100;

  if (isChangingFrom100) {
    const result = await window.Swal.fire({
      icon: 'warning',
      title: 'Alterar porcentagem de produção?',
      text: 'Este card está 100% produzido. Você realmente deseja alterar a porcentagem de produção?',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Sim, alterar',
      cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) {
      productionPercentageText.value = originalProductionPercentage.value;
      return;
    }
  }

  isSaving.value = true;

  try {
    const updated = await productionService.updateProductionPercentage(props.card.id, percentage);
    applyUpdatedPercentage(updated, percentage);
    toast.success('Porcentagem de produção atualizada com sucesso.');
  } catch (error) {
    console.error('Erro ao atualizar porcentagem:', error);
    const errorMessage =
      error.response?.data?.message ||
      'Erro ao atualizar porcentagem de produção. Tente novamente.';

    if (window.Swal) {
      window.Swal.fire('Erro!', errorMessage, 'error');
    } else {
      alert(errorMessage);
    }
  } finally {
    isSaving.value = false;
  }
}
</script>

<style lang="scss" scoped>
.production-percentage-section {
  margin-bottom: 24px;

  &:last-child {
    margin-bottom: 0;
  }
}

.production-percentage-section-display {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.production-percentage-section-edit-btn {
  background: none;
  border: none;
  color: var(--bs-primary);
  font-size: 0.875rem;
  cursor: pointer;
  padding: 0.25rem 0.5rem;
  margin-left: 0.5rem;
  border-radius: 0.25rem;
  transition: background-color 0.2s ease;

  &:hover {
    background-color: var(--bs-secondary-bg);
  }
}

.production-percentage-section-edit {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.production-percentage-section-input-wrapper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background-color: var(--bs-body-bg);
  border: 2px solid var(--bs-border-color);
  border-radius: 0.5rem;
  transition: border-color 0.2s ease;

  &:focus-within {
    border-color: var(--bs-primary);
  }
}

.production-percentage-section-label {
  font-size: 0.875rem;
  color: var(--bs-body-color);
}

.production-percentage-section-input {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 0.875rem;
  color: var(--bs-body-color);
  outline: none;
  max-width: 80px;

  &::-webkit-inner-spin-button,
  &::-webkit-outer-spin-button {
    -webkit-appearance: none;
    appearance: none;
    margin: 0;
  }

  &[type='number'] {
    -moz-appearance: textfield;
    appearance: textfield;
  }
}

.production-percentage-section-input-suffix {
  font-size: 0.875rem;
  color: var(--bs-body-color);
}

.production-percentage-section-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

.production-percentage-section-cancel,
.production-percentage-section-save {
  padding: 0.5rem 1rem;
  border-radius: 0.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
}

.production-percentage-section-cancel {
  background-color: var(--bs-secondary-bg);
  color: var(--bs-body-color);

  &:hover {
    background-color: var(--bs-tertiary-bg);
  }
}

.production-percentage-section-save {
  background-color: var(--bs-primary);
  color: var(--bs-white);

  &:hover:not(:disabled) {
    background-color: var(--bs-primary-emphasis);
  }

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
}
</style>
