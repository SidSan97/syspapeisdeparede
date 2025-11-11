<template>
  <section class="content">
    <div class="container py-4">
      <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
          <h1 class="h3 mb-2 text-primary fw-semibold">Tipos de Arte</h1>
          <p class="text-muted mb-0">
            Gerencie os tipos de arte disponíveis para associação aos modelos.
          </p>
        </div>
        <button type="button" class="btn btn-primary" @click="startCreating">
          <i class="fa fa-plus-circle me-2"></i>
          Nova arte
        </button>
      </header>

      <section v-if="isFormVisible" class="card mb-4 shadow-sm">
        <div class="card-body">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <h5 class="mb-0">
              {{ isEditing ? 'Editar arte' : 'Nova arte' }}
            </h5>
          </div>

          <form @submit.prevent="handleSubmit">
            <div class="row g-3">
              <div class="col-md-6 col-lg-4">
                <label for="artName" class="form-label">Nome da arte</label>
                <input
                  id="artName"
                  v-model.trim="form.name"
                  type="text"
                  class="form-control"
                  placeholder="Ex: Painel fotográfico"
                  required
                />
              </div>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-3 align-items-center justify-content-end justify-content-md-start">
              <button type="submit" class="btn btn-primary" :disabled="isSaving">
                {{ isEditing ? 'Salvar alterações' : 'Salvar arte' }}
              </button>
              <button type="button" class="btn btn-subtle text-danger" @click="cancelForm">
                Cancelar
              </button>
            </div>
          </form>
        </div>
      </section>

      <div class="card shadow-sm">
        <div class="card-body">
          <div v-if="isLoading" class="text-center text-muted py-5">
            Carregando tipos de arte...
          </div>
          <div v-else-if="!hasTypes" class="text-center text-muted py-5">
            Nenhum tipo de arte cadastrado ainda.
          </div>
          <div v-else class="table-responsive">
            <table class="table align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Nome</th>
                  <th class="text-end">Ações</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="type in types" :key="type.id">
                  <td>{{ type.name }}</td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm" role="group">
                      <button type="button" class="btn btn-outline-primary" @click="editType(type)">
                        <i class="fa fa-edit me-1"></i>
                        Editar
                      </button>
                      <button
                        type="button"
                        class="btn btn-outline-danger"
                        :disabled="deletingId === type.id"
                        @click="confirmDelete(type)"
                      >
                        <i class="fa fa-trash me-1"></i>
                        Excluir
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { swalConfirmation, swalError, swalSuccess } from '../../../utils/alerts';

const types = ref([]);
const isLoading = ref(false);
const isFormVisible = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const deletingId = ref(null);
const editingId = ref(null);

const initialState = () => ({
  name: '',
});

const form = reactive(initialState());

const hasTypes = computed(() => types.value.length > 0);

const normalizeType = (type = {}) => ({
  id: Number(type.id ?? type.type_id ?? 0),
  name: (type.name ?? '').toString(),
});

const sortTypes = (items = []) =>
  [...items].sort((a, b) => a.name.localeCompare(b.name, 'pt-BR', { sensitivity: 'base' }));

const fetchTypes = async () => {
  isLoading.value = true;
  try {
    const { data } = await axios.get('v1/model-types');
    const payload = data?.data ?? data ?? [];
    const list = Array.isArray(payload) ? payload.map(normalizeType) : [];
    types.value = sortTypes(list);
  } catch (error) {
    swalError('Não foi possível carregar os tipos de arte. Tente novamente.');
    types.value = [];
  } finally {
    isLoading.value = false;
  }
};

const startCreating = () => {
  editingId.value = null;
  form.name = '';
  isEditing.value = false;
  isFormVisible.value = true;
};

const editType = (type) => {
  if (!type) {
    return;
  }

  isFormVisible.value = true;
  isEditing.value = true;
  editingId.value = type.id;
  form.name = type.name;
};

const cancelForm = () => {
  form.name = '';
  editingId.value = null;
  isFormVisible.value = false;
};

const handleSubmit = async () => {
  if (isSaving.value) {
    return;
  }

  const trimmedName = form.name?.trim();
  if (!trimmedName) {
    swalError('Informe o nome da arte.');
    return;
  }

  isSaving.value = true;

  try {
    let response;
    if (isEditing.value && editingId.value !== null) {
      response = await axios.put(`v1/model-types/${editingId.value}`, {
        name: trimmedName,
      });
    } else {
      response = await axios.post('v1/model-types', {
        name: trimmedName,
      });
    }

    const saved = normalizeType(response?.data?.data ?? response?.data ?? {});

    if (!saved.id) {
      await fetchTypes();
    } else {
      const index = types.value.findIndex((item) => item.id === saved.id);
      if (index !== -1) {
        const updated = [...types.value];
        updated.splice(index, 1, saved);
        types.value = sortTypes(updated);
      } else {
        types.value = sortTypes([saved, ...types.value]);
      }
    }

    swalSuccess(isEditing.value ? 'Arte atualizada com sucesso.' : 'Arte criada com sucesso.');
    cancelForm();
  } catch (error) {
    const message =
      error?.response?.data?.message ?? 'Não foi possível salvar a arte. Verifique os dados.';
    swalError(message);
  } finally {
    isSaving.value = false;
  }
};

const confirmDelete = async (type) => {
  if (!type?.id || deletingId.value !== null) {
    return;
  }

  const result = await swalConfirmation(
    'Excluir arte?',
    'Essa ação é <strong>irreversível!</strong>',
    'warning',
    'Excluir',
    'Cancelar'
  );

  if (result.isConfirmed) {
    await destroyType(type);
  }
};

const destroyType = async (type) => {
  if (!type?.id) {
    return;
  }

  deletingId.value = type.id;

  try {
    await axios.delete(`v1/model-types/${type.id}`);
    types.value = types.value.filter((item) => item.id !== type.id);
    swalSuccess('Arte excluída com sucesso.');

    if (isFormVisible.value && editingId.value === type.id) {
      cancelForm();
    }
  } catch (error) {
    const message =
      error?.response?.data?.message ?? 'Não foi possível excluir a arte. Tente novamente.';
    swalError(message);
  } finally {
    deletingId.value = null;
  }
};

onMounted(() => {
  document.title = 'Tipos de Arte';
  fetchTypes();
});
</script>

<style scoped>
.card {
  border: 1px solid var(--bs-border-color);
  border-radius: 1rem;
}

.btn-group .btn + .btn {
  margin-left: 0.35rem;
}

.btn-subtle {
  background-color: transparent;
  border: none;
  color: var(--bs-danger);
  padding: 0.375rem 0.75rem;
  transition: color 0.2s ease-in-out, text-decoration 0.2s ease-in-out;
}

.btn-subtle:hover,
.btn-subtle:focus {
  background-color: transparent;
  color: var(--bs-danger-hover, #bb2d3b);
  text-decoration: underline;
}

@media (max-width: 575.98px) {
  header > div {
    text-align: center;
  }

  header {
    align-items: stretch !important;
  }

  header .btn {
    width: 100%;
  }
}
</style>


