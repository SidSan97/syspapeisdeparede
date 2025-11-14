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

      <section v-if="isSubcategoryFormVisible" class="card mb-4 shadow-sm">
        <div class="card-body">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <h5 class="mb-0">
              {{ isEditingSubcategory ? 'Editar subcategoria' : 'Nova subcategoria' }}
            </h5>
          </div>

          <form @submit.prevent="handleSubcategorySubmit">
            <div class="row g-3">
              <div class="col-md-6 col-lg-4">
                <label for="subcategoryCollection" class="form-label">Coleção</label>
                <div class="form-control-plaintext bg-light rounded px-3 py-2 border">
                  <strong>{{ selectedCollectionForSubcategory?.name ?? '—' }}</strong>
                </div>
                <small class="text-muted">A subcategoria será adicionada a esta coleção</small>
              </div>
              <div class="col-md-6 col-lg-4">
                <label for="subcategoryName" class="form-label">Nome da subcategoria</label>
                <input
                  id="subcategoryName"
                  v-model.trim="subcategoryForm.name"
                  type="text"
                  class="form-control"
                  placeholder="Ex: Moderno"
                  maxlength="50"
                  required
                />
              </div>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-3 align-items-center justify-content-end justify-content-md-start">
              <button type="submit" class="btn btn-primary" :disabled="isSavingSubcategory">
                {{ isEditingSubcategory ? 'Salvar alterações' : 'Salvar subcategoria' }}
              </button>
              <button type="button" class="btn btn-subtle text-danger" @click="cancelSubcategoryForm">
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
                <template v-for="collection in collections" :key="collection.id">
                  <tr>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <span
                          class="collection-name-clickable"
                          @click="toggleSubcategories(collection.id)"
                          :title="expandedCollections.has(collection.id) ? 'Ocultar subcategorias' : 'Mostrar subcategorias'"
                        >
                          {{ collection.name }}
                        </span>
                        <button
                          type="button"
                          class="btn btn-link btn-sm p-0 text-muted"
                          @click="toggleSubcategories(collection.id)"
                          :title="expandedCollections.has(collection.id) ? 'Ocultar subcategorias' : 'Mostrar subcategorias'"
                        >
                          <i :class="['fa', expandedCollections.has(collection.id) ? 'fa-chevron-down' : 'fa-chevron-right']"></i>
                        </button>
                      </div>
                    </td>
                    <td class="text-end">
                      <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-success" @click="startCreatingSubcategory(collection)">
                          <i class="fa fa-plus me-1"></i>
                          Subcategoria
                        </button>
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
                  <tr v-if="expandedCollections.has(collection.id)">
                    <td colspan="2" class="p-0">
                      <div class="subcategories-container">
                        <div v-if="loadingSubcategories[collection.id]" class="text-center text-muted py-3">
                          Carregando subcategorias...
                        </div>
                        <div v-else-if="!collectionSubcategories[collection.id]?.length" class="text-center text-muted py-3">
                          Nenhuma subcategoria cadastrada.
                        </div>
                        <div v-else class="subcategories-list">
                          <div
                            v-for="subcategory in collectionSubcategories[collection.id]"
                            :key="subcategory.id"
                            class="subcategory-item"
                          >
                            <div class="d-flex justify-content-between align-items-center">
                              <div>
                                <strong>{{ subcategory.name }}</strong>
                                <small class="text-muted ms-2">{{ formatCount(subcategory.images_count) }}</small>
                              </div>
                              <div class="btn-group btn-group-sm">
                                <button
                                  type="button"
                                  class="btn btn-outline-primary btn-sm"
                                  @click="editSubcategory(subcategory)"
                                >
                                  <i class="fa fa-edit"></i>
                                </button>
                                <button
                                  type="button"
                                  class="btn btn-outline-danger btn-sm"
                                  :disabled="deletingSubcategoryId === subcategory.id"
                                  @click="confirmDeleteSubcategory(subcategory)"
                                >
                                  <i class="fa fa-trash"></i>
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </td>
                  </tr>
                </template>
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

// Subcategorias
const collectionSubcategories = reactive({});
const expandedCollections = ref(new Set());
const loadingSubcategories = reactive({});
const isSubcategoryFormVisible = ref(false);
const isEditingSubcategory = ref(false);
const isSavingSubcategory = ref(false);
const deletingSubcategoryId = ref(null);
const editingSubcategoryId = ref(null);
const subcategoryFormCollectionId = ref(null);

const initialState = () => ({
  name: '',
});

const subcategoryInitialState = () => ({
  name: '',
  collection_art_id: null,
});

const form = reactive(initialState());
const subcategoryForm = reactive(subcategoryInitialState());

const hasCollections = computed(() => collections.value.length > 0);

const selectedCollectionForSubcategory = computed(() => {
  if (!subcategoryFormCollectionId.value) {
    return null;
  }
  return collections.value.find((c) => c.id === subcategoryFormCollectionId.value) ?? null;
});

const normalizeCollection = (item = {}) => ({
  id: Number(item.id ?? 0),
  name: (item.name ?? '').toString(),
  subcategories: item.subcategories ?? [],
});

const normalizeSubcategory = (item = {}) => ({
  id: Number(item.id ?? 0),
  name: (item.name ?? '').toString(),
  collection_art_id: Number(item.collection_art_id ?? 0),
  images_count: Number(item.images_count ?? 0),
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

    // Inicializar subcategorias
    collections.value.forEach((collection) => {
      if (collection.subcategories && Array.isArray(collection.subcategories)) {
        collectionSubcategories[collection.id] = collection.subcategories.map(normalizeSubcategory);
      }
    });
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

// Funções de subcategorias
const toggleSubcategories = async (collectionId) => {
  if (expandedCollections.value.has(collectionId)) {
    expandedCollections.value.delete(collectionId);
  } else {
    expandedCollections.value.add(collectionId);
    if (!collectionSubcategories[collectionId]) {
      await fetchSubcategories(collectionId);
    }
  }
};

const fetchSubcategories = async (collectionId) => {
  if (loadingSubcategories[collectionId]) {
    return;
  }

  loadingSubcategories[collectionId] = true;
  try {
    const { data } = await axios.get('v1/collection-art-subcategories', {
      params: { collection_art_id: collectionId },
    });

    const payload = data?.data ?? data ?? {};
    const items = Array.isArray(payload) ? payload : [];

    collectionSubcategories[collectionId] = items.map(normalizeSubcategory);
  } catch (error) {
    swalError('Não foi possível carregar as subcategorias.');
    collectionSubcategories[collectionId] = [];
  } finally {
    loadingSubcategories[collectionId] = false;
  }
};

const startCreatingSubcategory = (collection) => {
  if (!collection?.id) {
    return;
  }

  editingSubcategoryId.value = null;
  subcategoryForm.name = '';
  subcategoryForm.collection_art_id = collection.id;
  subcategoryFormCollectionId.value = collection.id;
  isEditingSubcategory.value = false;
  isSubcategoryFormVisible.value = true;
};

const editSubcategory = (subcategory) => {
  if (!subcategory) {
    return;
  }

  isSubcategoryFormVisible.value = true;
  isEditingSubcategory.value = true;
  editingSubcategoryId.value = subcategory.id;
  subcategoryForm.name = subcategory.name;
  subcategoryForm.collection_art_id = subcategory.collection_art_id;
  subcategoryFormCollectionId.value = subcategory.collection_art_id;
};

const cancelSubcategoryForm = () => {
  subcategoryForm.name = '';
  subcategoryForm.collection_art_id = null;
  editingSubcategoryId.value = null;
  subcategoryFormCollectionId.value = null;
  isSubcategoryFormVisible.value = false;
};

const handleSubcategorySubmit = async () => {
  if (isSavingSubcategory.value) {
    return;
  }

  const trimmedName = subcategoryForm.name?.trim();
  if (!trimmedName) {
    swalError('Informe o nome da subcategoria.');
    return;
  }

  if (!subcategoryForm.collection_art_id) {
    swalError('Coleção não informada.');
    return;
  }

  isSavingSubcategory.value = true;

  try {
    let response;
    if (isEditingSubcategory.value && editingSubcategoryId.value !== null) {
      response = await axios.put(`v1/collection-art-subcategories/${editingSubcategoryId.value}`, {
        name: trimmedName,
        collection_art_id: subcategoryForm.collection_art_id,
      });
    } else {
      response = await axios.post('v1/collection-art-subcategories', {
        name: trimmedName,
        collection_art_id: subcategoryForm.collection_art_id,
      });
    }

    const saved = normalizeSubcategory(response?.data?.data ?? response?.data ?? {});
    const collectionId = saved.collection_art_id;

    if (!collectionSubcategories[collectionId]) {
      collectionSubcategories[collectionId] = [];
    }

    const index = collectionSubcategories[collectionId].findIndex((item) => item.id === saved.id);
    if (index !== -1) {
      collectionSubcategories[collectionId].splice(index, 1, saved);
    } else {
      collectionSubcategories[collectionId].push(saved);
    }

    swalSuccess(isEditingSubcategory.value ? 'Subcategoria atualizada com sucesso.' : 'Subcategoria criada com sucesso.');
    cancelSubcategoryForm();
  } catch (error) {
    const message =
      error?.response?.data?.message ?? 'Não foi possível salvar a subcategoria. Verifique os dados.';
    swalError(message);
  } finally {
    isSavingSubcategory.value = false;
  }
};

const confirmDeleteSubcategory = async (subcategory) => {
  if (!subcategory?.id || deletingSubcategoryId.value !== null) {
    return;
  }

  const result = await swalConfirmation(
    'Excluir subcategoria?',
    'Essa ação é <strong>irreversível!</strong> Todas as imagens associadas serão removidas.',
    'warning',
    'Excluir',
    'Cancelar'
  );

  if (result.isConfirmed) {
    await destroySubcategory(subcategory);
  }
};

const destroySubcategory = async (subcategory) => {
  if (!subcategory?.id) {
    return;
  }

  deletingSubcategoryId.value = subcategory.id;

  try {
    await axios.delete(`v1/collection-art-subcategories/${subcategory.id}`);
    const collectionId = subcategory.collection_art_id;
    if (collectionSubcategories[collectionId]) {
      collectionSubcategories[collectionId] = collectionSubcategories[collectionId].filter(
        (item) => item.id !== subcategory.id
      );
    }
    swalSuccess('Subcategoria excluída com sucesso.');

    if (isSubcategoryFormVisible.value && editingSubcategoryId.value === subcategory.id) {
      cancelSubcategoryForm();
    }
  } catch (error) {
    const message =
      error?.response?.data?.message ?? 'Não foi possível excluir a subcategoria. Tente novamente.';
    swalError(message);
  } finally {
    deletingSubcategoryId.value = null;
  }
};

const formatCount = (count) => {
  const total = Number(count ?? 0);
  return total === 1 ? '1 imagem' : `${total} imagens`;
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

.subcategories-container {
  background: var(--bs-secondary-bg);
  padding: 1rem;
  border-top: 1px solid var(--bs-border-color);
}

.subcategories-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.subcategory-item {
  padding: 0.75rem;
  background: var(--bs-body-bg);
  border: 1px solid var(--bs-border-color);
  border-radius: 0.5rem;
}

.collection-name-clickable {
  cursor: pointer;
  user-select: none;
  transition: color 0.2s ease;
  font-weight: 500;
}

.collection-name-clickable:hover {
  color: var(--bs-primary);
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

