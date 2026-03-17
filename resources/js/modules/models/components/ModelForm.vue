<template>
  <section v-if="isVisible" class="form-container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h5 class="mb-0">
        {{ isEditing ? 'Editar modelo' : 'Novo modelo' }}
      </h5>
      <button
        type="button"
        class="btn btn-outline-secondary"
        @click="handleReset"
      >
        Limpar
      </button>
    </div>
    <form @submit.prevent="handleSubmit">
      <div class="row">
        <div class="col-md-6 mb-2">
          <label for="modelName" class="form-label">Nome</label>
          <input
            id="modelName"
            v-model.trim="form.name"
            type="text"
            class="form-control"
            placeholder="Ex: Painel fotográfico"
            required
          />
        </div>
        <div class="col-md-6 mb-2">
          <label for="modelValue" class="form-label">Valor</label>
          <div class="input-group">
            <span class="input-group-text">R$</span>
            <money
              id="modelValue"
              v-model.number="form.value"
              v-bind="moneyConfig"
              class="form-control"
              required
            />
          </div>
        </div>
        <div class="col-md-6 mb-2">
          <label for="modelDeadline" class="form-label">Prazo (dias)</label>
          <input
            id="modelDeadline"
            v-model.number="form.deadline"
            type="number"
            min="0"
            class="form-control"
            required
          />
        </div>
      </div>

      <div class="mt-4">
        <h6 class="fw-semibold mb-3">Solicitações adicionais</h6>
        <div class="row g-3">
          <div class="col-md-3">
            <div class="form-check form-switch">
              <input
                id="requiresLink"
                v-model="form.requests.link"
                class="form-check-input"
                type="checkbox"
              />
              <label class="form-check-label" for="requiresLink">
                Solicita link?
              </label>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-check form-switch">
              <input
                id="requiresComment"
                v-model="form.requests.comment"
                class="form-check-input"
                type="checkbox"
              />
              <label class="form-check-label" for="requiresComment">
                Solicita comentário?
              </label>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-check form-switch">
              <input
                id="requiresFile"
                v-model="form.requests.file"
                class="form-check-input"
                type="checkbox"
              />
              <label class="form-check-label" for="requiresFile">
                Solicita arquivo?
              </label>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-check form-switch">
              <input
                id="requiresCollection"
                v-model="form.requests.collection"
                class="form-check-input"
                type="checkbox"
              />
              <label class="form-check-label" for="requiresCollection">
                Escolher da coleção?
              </label>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-5 d-flex justify-content-end gap-3">
        <button
          type="button"
          class="btn btn-subtle"
          @click="handleCancel"
        >
          Cancelar
        </button>
        <button
          type="submit"
          class="btn btn-primary"
          :disabled="isSaving"
        >
          {{ isEditing ? 'Salvar alterações' : 'Salvar modelo' }}
        </button>
      </div>
    </form>
  </section>
</template>

<script setup>
defineProps({
  isVisible: {
    type: Boolean,
    required: true,
  },
  isEditing: {
    type: Boolean,
    required: true,
  },
  isSaving: {
    type: Boolean,
    default: false,
  },
  form: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['submit', 'cancel', 'reset']);

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

function handleSubmit() {
  emit('submit');
}

function handleCancel() {
  emit('cancel');
}

function handleReset() {
  emit('reset');
}
</script>

<style scoped>
.form-container {
  background: var(--bs-body-bg);
  border: 1px solid var(--bs-border-color);
  border-radius: 0.75rem;
  padding: 2.5rem 2rem;
  box-shadow: 0 1rem 2.5rem rgba(15, 15, 15, 0.08);
}
</style>

