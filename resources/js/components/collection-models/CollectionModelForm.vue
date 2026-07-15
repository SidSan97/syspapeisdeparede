<template>
  <div>
    <BaseInput
      v-model="form.name"
      label="Nome"
      name="name"
      type="text"
      required
      :disabled="loading"
      form-group-class="mb-3"
      :class="{ 'is-invalid': form.errors.has('name') }"
    >
      <template #bottom>
        <has-error :form="form" field="name"></has-error>
      </template>
    </BaseInput>
    <div class="form-group mb-3">
      <BaseInputLabel for="value">Valor</BaseInputLabel>
      <money
        id="value"
        v-model.number="form.value"
        v-bind="moneyConfig"
        class="form-control"
        required
      />
      <has-error :form="form" field="role"></has-error>
    </div>
    <BaseInput
      v-model="form.deadline"
      form-group-class="mb-3"
      label="Prazo (dias)"
      name="deadline"
      type="number"
      required
      :min="0"
      :disabled="loading"
      :class="{ 'is-invalid': form.errors.has('deadline') }"
    >
      <template #bottom>
        <has-error :form="form" field="deadline"></has-error>
      </template>
    </BaseInput>
    <h3 class="h5 my-4">Solicitações adicionais</h3>
    <div class="form-check form-switch mb-3">
      <input
        id="requiresLink"
        v-model="form.requests.link"
        class="form-check-input"
        type="checkbox"
      />
      <label class="form-check-label" for="requiresLink"> Solicita link? </label>
    </div>
    <div class="form-check form-switch mb-3">
      <input
        id="requiresComment"
        v-model="form.requests.comment"
        class="form-check-input"
        type="checkbox"
      />
      <label class="form-check-label" for="requiresComment"> Solicita comentário? </label>
    </div>
    <div class="form-check form-switch mb-3">
      <input
        id="requiresFile"
        v-model="form.requests.file"
        class="form-check-input"
        type="checkbox"
      />
      <label class="form-check-label" for="requiresFile"> Solicita arquivo? </label>
    </div>
    <div class="form-check form-switch mb-3">
      <input
        id="requiresCollection"
        v-model="form.requests.collection"
        class="form-check-input"
        type="checkbox"
      />
      <label class="form-check-label" for="requiresCollection"> Escolher da coleção? </label>
    </div>
    <div class="form-check form-switch mb-3">
      <input
        id="requiresLayout"
        v-model="form.requests.layout"
        class="form-check-input"
        type="checkbox"
      />
      <label class="form-check-label" for="requiresLayout">
        Solicitar Layout ao Aprovar Pedido?
      </label>
    </div>
    <div class="form-check form-switch mb-3">
      <input
        id="requiresArtOnPayment"
        v-model="form.requests.artOnPayment"
        class="form-check-input"
        type="checkbox"
      />
      <label class="form-check-label" for="requiresArtOnPayment">
        Solicitar Arte ao pagar pedido
      </label>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue';
import BaseInput from '@/components/common/BaseInput.vue';
import BaseInputLabel from '@/components/common/BaseInputLabel.vue';

defineProps({
  isSaving: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const form = reactive(
  new Form({
    id: '',
    name: '',
    value: 0,
    deadline: null,
    requests: {
      link: false,
      comment: false,
      file: false,
      collection: false,
      layout: false,
      artOnPayment: false,
    },
  }),
);

const moneyConfig = {
  decimal: ',',
  thousands: '.',
  precision: 2,
  prefix: '',
  allowBlank: false,
  min: 0,
  max: null,
  disableNegative: true,
  minimumNumberOfCharacters: 0,
};

defineExpose({ form });
</script>
