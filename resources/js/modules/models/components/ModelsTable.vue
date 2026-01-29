<template>
  <section class="models-table mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
    </div>

    <div v-if="isLoading" class="border rounded p-4 text-center text-muted">
      Carregando modelos...
    </div>
    <div v-else-if="!hasModels" class="border rounded p-4 text-center text-muted">
      Nenhum modelo cadastrado ainda.
    </div>

    <div v-else class="table-responsive shadow-sm rounded">
      <table class="table align-middle mb-0">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Valor</th>
            <th>Prazo (dias)</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="model in models" :key="model.id">
            <td class="text-nowrap">{{ model.name }}</td>
            <td class="text-nowrap">R$ {{ formatValue(model.value) }}</td>
            <td>{{ model.deadline }}</td>
            <td class="text-end">
              <div class="dropdown">
                <button
                  class="btn btn-subtle btn-sm"
                  type="button"
                  data-bs-toggle="dropdown"
                  aria-expanded="false"
                >
                  <i class="fa fa-ellipsis-h"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <button
                      class="dropdown-item"
                      type="button"
                      @click="handleEdit(model)"
                    >
                      Editar
                    </button>
                  </li>
                  <li>
                    <hr class="dropdown-divider" />
                  </li>
                  <li>
                    <button
                      class="dropdown-item text-danger"
                      type="button"
                      :disabled="deletingId === model.id"
                      @click="handleDelete(model)"
                    >
                      Excluir
                    </button>
                  </li>
                </ul>
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
  deletingId: {
    type: [Number, String],
    default: null,
  },
});

const emit = defineEmits(['edit', 'delete']);

const valueFormatter = new Intl.NumberFormat('pt-BR', {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});

function formatValue(value) {
  const n = Number(value);
  return Number.isFinite(n) ? valueFormatter.format(n) : '0,00';
}

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
</style>

