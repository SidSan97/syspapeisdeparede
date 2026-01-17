<template>
  <div class="description-section">
    <h3 class="description-section-title">
      <i class="fa fa-align-left"></i> Descrição
    </h3>
    <div v-if="!isEditing" class="description-section-display" :class="{ 'is-empty': !description }" @click="startEdit">
      {{ description || 'Adicione uma descrição mais detalhada...' }}
    </div>
    <div v-else class="d-flex flex-column gap-3">
      <textarea
        v-model="descriptionText"
        class="form-control"
        maxlength="500"
        rows="4"
        placeholder="Adicione uma descrição mais detalhada..."
      ></textarea>
      <div class="d-flex justify-content-between align-items-center">
        <span class="text-muted small">{{ descriptionText.length }}/500</span>
        <div class="d-flex gap-1">
          <button class="btn btn-subtle" @click="cancelEdit">
            Cancelar
          </button>
          <button class="btn btn-primary" @click="save" :disabled="isSaving">
            {{ isSaving ? 'Salvando...' : 'Salvar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useDescriptionService } from '@/views/layouts/services/descriptionService';

const props = defineProps({
  card: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['description-updated']);

const descriptionService = useDescriptionService();

const isEditing = ref(false);
const descriptionText = ref('');
const originalDescription = ref('');
const isSaving = ref(false);

const description = computed(() => {
  return props.card?.description || '';
});

// Inicializar descrição quando o card mudar
watch(() => props.card?.id, (newCardId) => {
  if (newCardId) {
    descriptionText.value = props.card?.description || '';
    originalDescription.value = props.card?.description || '';
    isEditing.value = false;
  }
}, { immediate: true });

function startEdit() {
  originalDescription.value = props.card?.description || '';
  descriptionText.value = originalDescription.value;
  isEditing.value = true;
}

function cancelEdit() {
  descriptionText.value = originalDescription.value;
  isEditing.value = false;
}

async function save() {
  if (!props.card?.id) {
    return;
  }

  isSaving.value = true;

  try {
    const response = await descriptionService.updateDescription(props.card.id, descriptionText.value);

    // Atualizar o card localmente
    if (props.card) {
      props.card.description = descriptionText.value;
    }

    originalDescription.value = descriptionText.value;
    isEditing.value = false;

    if (window.Toast) {
      window.Toast.fire({
        icon: 'success',
        title: response.message || 'Descrição salva com sucesso',
      });
    }

    emit('description-updated', descriptionText.value);
  } catch (error) {
    console.error('Erro ao salvar descrição:', error);
    const errorMessage = error.response?.data?.message || 'Erro ao salvar descrição. Tente novamente.';

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
.description-section {
  margin-bottom: 24px;

  &:last-child {
    margin-bottom: 0;
  }
}

.description-section-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--bs-body-color);
  margin-bottom: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.description-section-display {
  padding: 1rem;
  border-radius: 0.5rem;
  min-height: 80px;
  color: var(--bs-body-color);
  line-height: 1.5;
  cursor: pointer;
  transition: background-color 0.2s ease;
  border: 1px solid var(--bs-border-color);

  &:hover {
    background-color: var(--bs-tertiary-bg);
  }

  &.is-empty {
    color: var(--bs-secondary);
  }
}
</style>

