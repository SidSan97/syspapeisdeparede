<template>
  <BaseModal
    v-model="isVisible"
    title="Dados para faturamento"
    :show-confirm-button="false"
    :close-on-backdrop="false"
  >
    <template #body>
      <p class="text-muted mb-3">
        Informe os dados da embalagem do pedido
        <span v-if="orderLabel" class="fw-semibold">#{{ orderLabel }}</span>
        antes de enviar os cards para faturar.
      </p>

      <form @submit.prevent="submit">
        <div class="mb-3">
          <label for="packer-name" class="form-label">
            Nome do embalador
            <span class="text-danger">*</span>
          </label>
          <input
            id="packer-name"
            v-model.trim="packerName"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': errors.packer_name }"
            maxlength="255"
            autocomplete="name"
            required
          />
          <div v-if="errors.packer_name" class="invalid-feedback">
            {{ errors.packer_name }}
          </div>
        </div>

        <div class="mb-0">
          <label for="volume-quantity" class="form-label">
            Quantidade de volumes
            <span class="text-danger">*</span>
          </label>
          <input
            id="volume-quantity"
            v-model.number="volumeQuantity"
            type="number"
            class="form-control"
            :class="{ 'is-invalid': errors.quantidade_volumes }"
            min="1"
            max="9999"
            step="1"
            required
          />
          <div v-if="errors.quantidade_volumes" class="invalid-feedback">
            {{ errors.quantidade_volumes }}
          </div>
        </div>
      </form>
    </template>

    <template #footer>
      <button type="button" class="btn btn-subtle" :disabled="loading" @click="close">
        Cancelar
      </button>
      <button type="button" class="btn btn-primary" :disabled="loading" @click="submit">
        <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
        Confirmar faturamento
      </button>
    </template>
  </BaseModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import BaseModal from '@/components/common/BaseModal.vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  orderLabel: {
    type: [Number, String],
    default: '',
  },
  initialPackerName: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:modelValue', 'confirm']);

const packerName = ref('');
const volumeQuantity = ref('');
const errors = ref({});

const isVisible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

watch(
  () => props.modelValue,
  (isOpen) => {
    if (!isOpen) {
      return;
    }

    packerName.value = props.initialPackerName || '';
    volumeQuantity.value = '';
    errors.value = {};
  },
);

function close() {
  isVisible.value = false;
}

function validate() {
  const nextErrors = {};

  if (!packerName.value) {
    nextErrors.packer_name = 'Informe o nome do embalador.';
  }

  const volumes = Number(volumeQuantity.value);
  if (!Number.isInteger(volumes) || volumes < 1) {
    nextErrors.quantidade_volumes = 'Informe a quantidade de volumes (mínimo 1).';
  }

  errors.value = nextErrors;

  return Object.keys(nextErrors).length === 0;
}

function submit() {
  if (props.loading || !validate()) {
    return;
  }

  emit('confirm', {
    packer_name: packerName.value,
    quantidade_volumes: Number(volumeQuantity.value),
  });
}
</script>
