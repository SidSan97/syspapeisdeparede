<template>
  <section class="content">
    <div class="container py-4">
      <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
          <h1 class="h3 mb-2 text-primary fw-semibold">Coleções</h1>
          <p class="text-muted mb-0">
            Gerencie as coleções disponíveis para os tipos de arte.
          </p>
        </div>
        <button type="button" class="btn btn-primary" @click="startCreating">
          <i class="fa fa-plus-circle me-2"></i>
          Nova coleção
        </button>
      </header>

      <section v-if="isFormVisible" class="card mb-4 shadow-sm">
        <div class="card-body">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <h5 class="mb-0">
              {{ isEditing ? 'Editar coleção' : 'Nova coleção' }}
            </h5>
          </div>

          <form @submit.prevent="handleSubmit">
            <div class="row g-3">
              <div class="col-md-6 col-lg-4">
                <label for="collectionName" class="form-label">Nome da coleção</label>
                <input
                  id="collectionName"
                  v-model.trim="form.name"
                  type="text"
                  class="form-control"
                  placeholder="Ex: Linha Clássica"
                  required
                />
              </div>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-3 align-items-center justify-content-end justify-content-md-start">
              <button type="submit" class="btn btn-primary" :disabled="isSaving">
                {{ isEditing ? 'Salvar alterações' : 'Salvar coleção' }}
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
            Carregando coleções...
          </div>
          <div v-else-if="!hasCollections" class="text-center text-muted py-5">
            Nenhuma coleção cadastrada ainda.
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
                <tr v-for="collection in collections" :key="collection.id">
                  <td>{{ collection.name }}</td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm" role="group">
                      <button type="button" class="btn btn-outline-primary" @click="editCollection(collection)">
                        <i class="fa fa-edit me-1"></i>
                        Editar
                      </button>
                      <button
                        type="button"
                        class="btn btn-outline-danger"
                        :disabled="deletingId === collection.id"
                        @click="confirmDelete(collection)"
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

const collections = ref([]);
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

const hasCollections = computed(() => collections.value.length > 0);

const normalizeCollection = (item = {}) => ({
  id: Number(item.id ?? 0),
  name: (item.name ?? '').toString(),
});

const sortCollections = (items = []) =>
  [...items].sort((a, b) => a.name.localeCompare(b.name, 'pt-BR', { sensitivity: 'base' }));

const fetchCollections = async () => {
  isLoading.value = true;
  try {
    const { data } = await axios.get('v1/collection-arts', {
      params: { per_page: 100 },
    });

    const payload = data?.data ?? data ?? {};
    const items = payload.items ?? payload ?? [];

    const list = Array.isArray(items) ? items.map(normalizeCollection) : [];
    collections.value = sortCollections(list);
  } catch (error) {
    swalError('Não foi possível carregar as coleções. Tente novamente.');
    collections.value = [];
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

const editCollection = (collection) => {
  if (!collection) {
    return;
  }

  isFormVisible.value = true;
  isEditing.value = true;
  editingId.value = collection.id;
  form.name = collection.name;
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
    swalError('Informe o nome da coleção.');
    return;
  }

  isSaving.value = true;

  try {
    let response;
    if (isEditing.value && editingId.value !== null) {
      response = await axios.put(`v1/collection-arts/${editingId.value}`, {
        name: trimmedName,
      });
    } else {
      response = await axios.post('v1/collection-arts', {
        name: trimmedName,
      });
    }

    const saved = normalizeCollection(response?.data?.data ?? response?.data ?? {});

    if (!saved.id) {
      await fetchCollections();
    } else {
      const index = collections.value.findIndex((item) => item.id === saved.id);
      if (index !== -1) {
        const updated = [...collections.value];
        updated.splice(index, 1, saved);
        collections.value = sortCollections(updated);
      } else {
        collections.value = sortCollections([saved, ...collections.value]);
      }
    }

    swalSuccess(isEditing.value ? 'Coleção atualizada com sucesso.' : 'Coleção criada com sucesso.');
    cancelForm();
  } catch (error) {
    const message =
      error?.response?.data?.message ?? 'Não foi possível salvar a coleção. Verifique os dados.';
    swalError(message);
  } finally {
    isSaving.value = false;
  }
};

const confirmDelete = async (collection) => {
  if (!collection?.id || deletingId.value !== null) {
    return;
  }

  const result = await swalConfirmation(
    'Excluir coleção?',
    'Essa ação é <strong>irreversível!</strong>',
    'warning',
    'Excluir',
    'Cancelar'
  );

  if (result.isConfirmed) {
    await destroyCollection(collection);
  }
};

const destroyCollection = async (collection) => {
  if (!collection?.id) {
    return;
  }

  deletingId.value = collection.id;

  try {
    await axios.delete(`v1/collection-arts/${collection.id}`);
    collections.value = collections.value.filter((item) => item.id !== collection.id);
    swalSuccess('Coleção excluída com sucesso.');

    if (isFormVisible.value && editingId.value === collection.id) {
      cancelForm();
    }
  } catch (error) {
    const message =
      error?.response?.data?.message ?? 'Não foi possível excluir a coleção. Tente novamente.';
    swalError(message);
  } finally {
    deletingId.value = null;
  }
};

onMounted(() => {
  document.title = 'Coleções';
  fetchCollections();
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

