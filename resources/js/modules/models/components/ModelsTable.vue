<template>
  <section class="models-table mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Modelos cadastrados</h5>
      <span class="badge bg-secondary rounded-pill">
        {{ totalModels }} {{ totalModels === 1 ? 'modelo' : 'modelos' }}
      </span>
    </div>

    <div v-if="isLoading" class="border rounded p-4 text-center text-muted">
      Carregando modelos...
    </div>
    <div v-else-if="!hasModels" class="border rounded p-4 text-center text-muted">
      Nenhum modelo cadastrado ainda.
    </div>

    <div v-else class="table-responsive shadow-sm rounded">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Nome</th>
            <th>Valor</th>
            <th>Prazo (dias)</th>
            <th>Solicita link?</th>
            <th>Solicita comentário?</th>
            <th>Solicita arquivo?</th>
            <th>Escolher da coleção?</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="model in models" :key="model.id">
            <td>{{ model.name }}</td>
            <td>R$ {{ model.value.toFixed(2) }}</td>
            <td>{{ model.deadline }}</td>
            <td>
              <i
                class="fa"
                :class="model.requests.link ? 'fa-check text-success' : 'fa-times text-muted'"
              />
            </td>
            <td>
              <i
                class="fa"
                :class="model.requests.comment ? 'fa-check text-success' : 'fa-times text-muted'"
              />
            </td>
            <td>
              <i
                class="fa"
                :class="model.requests.file ? 'fa-check text-success' : 'fa-times text-muted'"
              />
            </td>
            <td>
              <i
                class="fa"
                :class="model.requests.collection ? 'fa-check text-success' : 'fa-times text-muted'"
              />
            </td>
            <td class="text-end">
              <div class="btn-group btn-group-sm" role="group">
                <button
                  type="button"
                  class="btn btn-outline-primary edit-button"
                  @click="handleEdit(model)"
                >
                  Editar
                </button>
                <button
                  type="button"
                  class="btn btn-outline-danger"
                  :disabled="deletingId === model.id"
                  @click="handleDelete(model)"
                >
                  Excluir
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
defineProps({
  models: {
    type: Array,
    required: true,
    default: () => [],
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  hasModels: {
    type: Boolean,
    required: true,
  },
  totalModels: {
    type: Number,
    required: true,
  },
  deletingId: {
    type: [Number, String],
    default: null,
  },
});

const emit = defineEmits(['edit', 'delete']);

function handleEdit(model) {
  emit('edit', model);
}

function handleDelete(model) {
  emit('delete', model);
}
</script>

<style scoped>
.models-table .table {
  background: var(--bs-body-bg);
}

.table td,
.table th {
  vertical-align: middle;
}

.edit-button {
  margin-right: 10px;
}
</style>

