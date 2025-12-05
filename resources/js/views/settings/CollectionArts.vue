<template>
  <section class="content">
    <Page title="Coleções" subtitle="Gerencie as coleções disponíveis para os tipos de arte." back-to="/settings">
      <template #actions>
        <button type="button" class="btn btn-primary" @click="startCreating">
          Nova coleção
        </button>
      </template>

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
              <div class="col-md-6 col-lg-4">
                <label for="collectionImageCover" class="form-label">Imagem de capa</label>
                <input
                  id="collectionImageCover"
                  ref="collectionImageCoverInput"
                  type="file"
                  class="form-control"
                  accept="image/*"
                  @change="handleCollectionImageChange"
                />
                <small class="text-muted d-block mt-1">Formatos aceitos: JPG, PNG, GIF (máx. 5MB)</small>
              </div>
              <div v-if="form.imagePreview || currentCollectionImageUrl" class="col-12">
                <label class="form-label">Preview da imagem de capa</label>
                <div class="image-preview-wrapper">
                  <img
                    :src="form.imagePreview || currentCollectionImageUrl"
                    alt="Preview da imagem de capa"
                    class="image-preview"
                  />
                  <button
                    v-if="form.imagePreview || currentCollectionImageUrl"
                    type="button"
                    class="btn btn-sm btn-danger image-preview-remove"
                    @click="removeCollectionImage"
                  >
                    <i class="fa fa-times"></i> Remover
                  </button>
                </div>
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
                <div class="form-control-plaintext rounded px-3 py-2 border">
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
              <div class="col-md-6 col-lg-4">
                <label for="subcategoryImageCover" class="form-label">Imagem de capa</label>
                <input
                  id="subcategoryImageCover"
                  ref="subcategoryImageCoverInput"
                  type="file"
                  class="form-control"
                  accept="image/*"
                  @change="handleSubcategoryImageChange"
                />
                <small class="text-muted d-block mt-1">Formatos aceitos: JPG, PNG, GIF (máx. 5MB)</small>
              </div>
              <div v-if="subcategoryForm.imagePreview || currentSubcategoryImageUrl" class="col-12">
                <label class="form-label">Preview da imagem de capa</label>
                <div class="image-preview-wrapper">
                  <img
                    :src="subcategoryForm.imagePreview || currentSubcategoryImageUrl"
                    alt="Preview da imagem de capa"
                    class="image-preview"
                  />
                  <button
                    v-if="subcategoryForm.imagePreview || currentSubcategoryImageUrl"
                    type="button"
                    class="btn btn-sm btn-danger image-preview-remove"
                    @click="removeSubcategoryImage"
                  >
                    <i class="fa fa-times"></i> Remover
                  </button>
                </div>
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
                      <div class="dropdown">
                        <button
                          class="btn btn-sm btn-outline-secondary dropdown-toggle"
                          type="button"
                          :id="`collectionDropdown-${collection.id}`"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          Ações
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" :aria-labelledby="`collectionDropdown-${collection.id}`">
                          <li>
                            <button
                              type="button"
                              class="dropdown-item"
                              @click="startCreatingSubcategory(collection)"
                            >
                              <i class="fa fa-plus me-2"></i> Nova subcategoria
                            </button>
                          </li>
                          <li>
                            <button
                              type="button"
                              class="dropdown-item"
                              @click="editCollection(collection)"
                            >
                              <i class="fa fa-edit me-2"></i> Editar
                            </button>
                          </li>
                          <li><hr class="dropdown-divider"></li>
                          <li>
                            <button
                              type="button"
                              class="dropdown-item text-danger"
                              :disabled="deletingId === collection.id"
                              @click="confirmDelete(collection)"
                            >
                              <i class="fa fa-trash me-2"></i> Excluir
                            </button>
                          </li>
                        </ul>
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
                              <div class="dropdown">
                                <button
                                  class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                  type="button"
                                  :id="`subcategoryDropdown-${subcategory.id}`"
                                  data-bs-toggle="dropdown"
                                  aria-expanded="false"
                                >
                                  Ações
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" :aria-labelledby="`subcategoryDropdown-${subcategory.id}`">
                                  <li>
                                    <button
                                      type="button"
                                      class="dropdown-item"
                                      @click="editSubcategory(subcategory)"
                                    >
                                      <i class="fa fa-edit me-2"></i> Editar
                                    </button>
                                  </li>
                                  <li><hr class="dropdown-divider"></li>
                                  <li>
                                    <button
                                      type="button"
                                      class="dropdown-item text-danger"
                                      :disabled="deletingSubcategoryId === subcategory.id"
                                      @click="confirmDeleteSubcategory(subcategory)"
                                    >
                                      <i class="fa fa-trash me-2"></i> Excluir
                                    </button>
                                  </li>
                                </ul>
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
    </Page>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { swalConfirmation } from '../../../utils/alerts';
// Alerts agora usam window.Swal.fire diretamente
import Page from '@/components/page/Page.vue';

const collections = ref([]);
const isLoading = ref(false);
const isFormVisible = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const deletingId = ref(null);
const editingId = ref(null);
const collectionImageCoverInput = ref(null);
const currentCollectionImageUrl = ref(null);

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
const subcategoryImageCoverInput = ref(null);
const currentSubcategoryImageUrl = ref(null);

const initialState = () => ({
  name: '',
  imageFile: null,
  imagePreview: null,
});

const subcategoryInitialState = () => ({
  name: '',
  collection_art_id: null,
  imageFile: null,
  imagePreview: null,
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

const buildStorageUrl = (path) => {
  if (!path) return '';
  const baseUrl = window.location.origin.replace(/\/$/, '');
  return `${baseUrl}/storage/${path.replace(/^\//, '')}`;
};

const resolveImageUrl = (url, path) => {
  if (url && /^https?:\/\//i.test(url)) {
    return url;
  }
  if (url && url.startsWith('/')) {
    const baseUrl = window.location.origin.replace(/\/$/, '');
    return `${baseUrl}${url}`;
  }
  return buildStorageUrl(url || path || '');
};

const normalizeCollection = (item = {}) => ({
  id: Number(item.id ?? 0),
  name: (item.name ?? '').toString(),
  image_cover: item.image_cover ?? null,
  image_cover_url: item.image_cover_url || (item.image_cover ? resolveImageUrl(null, item.image_cover) : null),
  subcategories: item.subcategories ?? [],
});

const normalizeSubcategory = (item = {}) => ({
  id: Number(item.id ?? 0),
  name: (item.name ?? '').toString(),
  collection_art_id: Number(item.collection_art_id ?? 0),
  sub_collection_image_cover: item.sub_collection_image_cover ?? null,
  sub_collection_image_cover_url: item.sub_collection_image_cover_url || (item.sub_collection_image_cover ? resolveImageUrl(null, item.sub_collection_image_cover) : null),
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
    window.Swal.fire({
      title: 'Erro!',
      text: 'Não foi possível carregar as coleções. Tente novamente.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    collections.value = [];
  } finally {
    isLoading.value = false;
  }
};

const startCreating = () => {
  editingId.value = null;
  form.name = '';
  form.imageFile = null;
  form.imagePreview = null;
  currentCollectionImageUrl.value = null;
  if (collectionImageCoverInput.value) {
    collectionImageCoverInput.value.value = '';
  }
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
  form.imageFile = null;
  form.imagePreview = null;
  currentCollectionImageUrl.value = collection.image_cover_url || null;
  if (collectionImageCoverInput.value) {
    collectionImageCoverInput.value.value = '';
  }
};

const cancelForm = () => {
  form.name = '';
  form.imageFile = null;
  form.imagePreview = null;
  currentCollectionImageUrl.value = null;
  if (collectionImageCoverInput.value) {
    collectionImageCoverInput.value.value = '';
  }
  editingId.value = null;
  isFormVisible.value = false;
};

const handleCollectionImageChange = (event) => {
  const file = event.target.files?.[0];
  if (!file) {
    form.imageFile = null;
    form.imagePreview = null;
    return;
  }

  // Validar tamanho (5MB)
  if (file.size > 5 * 1024 * 1024) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'A imagem não pode ser maior que 5MB.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    event.target.value = '';
    form.imageFile = null;
    form.imagePreview = null;
    return;
  }

  // Validar tipo
  if (!file.type.startsWith('image/')) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Por favor, selecione um arquivo de imagem válido.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    event.target.value = '';
    form.imageFile = null;
    form.imagePreview = null;
    return;
  }

  form.imageFile = file;
  currentCollectionImageUrl.value = null;

  // Criar preview
  const reader = new FileReader();
  reader.onload = (e) => {
    form.imagePreview = e.target.result;
  };
  reader.readAsDataURL(file);
};

const removeCollectionImage = () => {
  form.imageFile = null;
  form.imagePreview = null;
  currentCollectionImageUrl.value = null;
  if (collectionImageCoverInput.value) {
    collectionImageCoverInput.value.value = '';
  }
};

const handleSubmit = async () => {
  if (isSaving.value) {
    return;
  }

  const trimmedName = form.name?.trim();
  if (!trimmedName) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Informe o nome da coleção.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    return;
  }

  isSaving.value = true;

  try {
    const formData = new FormData();
    formData.append('name', trimmedName);

    if (form.imageFile) {
      formData.append('image_cover', form.imageFile);
    }

    let response;
    if (isEditing.value && editingId.value !== null) {
      formData.append('_method', 'PUT');
      response = await axios.post(`v1/collection-arts/${editingId.value}`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
    } else {
      response = await axios.post('v1/collection-arts', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
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

    window.Swal.fire({
      title: isEditing.value ? 'Coleção atualizada!' : 'Coleção criada!',
      text: isEditing.value ? 'Coleção atualizada com sucesso.' : 'Coleção criada com sucesso.',
      confirmButtonText: 'Entendi!',
    });
    cancelForm();
  } catch (error) {
    const message =
      error?.response?.data?.message ?? 'Não foi possível salvar a coleção. Verifique os dados.';
    window.Swal.fire({
      title: 'Erro!',
      text: message,
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
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
    window.Swal.fire({
      title: 'Coleção excluída!',
      text: 'Coleção excluída com sucesso.',
      confirmButtonText: 'Entendi!',
    });

    if (isFormVisible.value && editingId.value === collection.id) {
      cancelForm();
    }
  } catch (error) {
    const message =
      error?.response?.data?.message ?? 'Não foi possível excluir a coleção. Tente novamente.';
    window.Swal.fire({
      title: 'Erro!',
      text: message,
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
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
    window.Swal.fire({
      title: 'Erro!',
      text: 'Não foi possível carregar as subcategorias.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
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
  subcategoryForm.imageFile = null;
  subcategoryForm.imagePreview = null;
  currentSubcategoryImageUrl.value = null;
  if (subcategoryImageCoverInput.value) {
    subcategoryImageCoverInput.value.value = '';
  }
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
  subcategoryForm.imageFile = null;
  subcategoryForm.imagePreview = null;
  currentSubcategoryImageUrl.value = subcategory.sub_collection_image_cover_url || null;
  if (subcategoryImageCoverInput.value) {
    subcategoryImageCoverInput.value.value = '';
  }
  subcategoryFormCollectionId.value = subcategory.collection_art_id;
};

const cancelSubcategoryForm = () => {
  subcategoryForm.name = '';
  subcategoryForm.collection_art_id = null;
  subcategoryForm.imageFile = null;
  subcategoryForm.imagePreview = null;
  currentSubcategoryImageUrl.value = null;
  if (subcategoryImageCoverInput.value) {
    subcategoryImageCoverInput.value.value = '';
  }
  editingSubcategoryId.value = null;
  subcategoryFormCollectionId.value = null;
  isSubcategoryFormVisible.value = false;
};

const handleSubcategoryImageChange = (event) => {
  const file = event.target.files?.[0];
  if (!file) {
    subcategoryForm.imageFile = null;
    subcategoryForm.imagePreview = null;
    return;
  }

  // Validar tamanho (5MB)
  if (file.size > 5 * 1024 * 1024) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'A imagem não pode ser maior que 5MB.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    event.target.value = '';
    subcategoryForm.imageFile = null;
    subcategoryForm.imagePreview = null;
    return;
  }

  // Validar tipo
  if (!file.type.startsWith('image/')) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Por favor, selecione um arquivo de imagem válido.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    event.target.value = '';
    subcategoryForm.imageFile = null;
    subcategoryForm.imagePreview = null;
    return;
  }

  subcategoryForm.imageFile = file;
  currentSubcategoryImageUrl.value = null;

  // Criar preview
  const reader = new FileReader();
  reader.onload = (e) => {
    subcategoryForm.imagePreview = e.target.result;
  };
  reader.readAsDataURL(file);
};

const removeSubcategoryImage = () => {
  subcategoryForm.imageFile = null;
  subcategoryForm.imagePreview = null;
  currentSubcategoryImageUrl.value = null;
  if (subcategoryImageCoverInput.value) {
    subcategoryImageCoverInput.value.value = '';
  }
};

const handleSubcategorySubmit = async () => {
  if (isSavingSubcategory.value) {
    return;
  }

  const trimmedName = subcategoryForm.name?.trim();
  if (!trimmedName) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Informe o nome da subcategoria.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    return;
  }

  if (!subcategoryForm.collection_art_id) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Coleção não informada.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    return;
  }

  isSavingSubcategory.value = true;

  try {
    const formData = new FormData();
    formData.append('name', trimmedName);
    formData.append('collection_art_id', subcategoryForm.collection_art_id);

    if (subcategoryForm.imageFile) {
      formData.append('sub_collection_image_cover', subcategoryForm.imageFile);
    }

    let response;
    if (isEditingSubcategory.value && editingSubcategoryId.value !== null) {
      formData.append('_method', 'PUT');
      response = await axios.post(`v1/collection-art-subcategories/${editingSubcategoryId.value}`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
    } else {
      response = await axios.post('v1/collection-art-subcategories', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
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

    window.Swal.fire({
      title: isEditingSubcategory.value ? 'Subcategoria atualizada!' : 'Subcategoria criada!',
      text: isEditingSubcategory.value ? 'Subcategoria atualizada com sucesso.' : 'Subcategoria criada com sucesso.',
      confirmButtonText: 'Entendi!',
    });
    cancelSubcategoryForm();
  } catch (error) {
    const message =
      error?.response?.data?.message ?? 'Não foi possível salvar a subcategoria. Verifique os dados.';
    window.Swal.fire({
      title: 'Erro!',
      text: message,
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
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
    window.Swal.fire({
      title: 'Subcategoria excluída!',
      text: 'Subcategoria excluída com sucesso.',
      confirmButtonText: 'Entendi!',
    });

    if (isSubcategoryFormVisible.value && editingSubcategoryId.value === subcategory.id) {
      cancelSubcategoryForm();
    }
  } catch (error) {
    const message =
      error?.response?.data?.message ?? 'Não foi possível excluir a subcategoria. Tente novamente.';
    window.Swal.fire({
      title: 'Erro!',
      text: message,
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
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

.image-preview-wrapper {
  position: relative;
  display: inline-block;
  max-width: 100%;
  margin-top: 0.5rem;
}

.image-preview {
  max-width: 300px;
  max-height: 300px;
  width: auto;
  height: auto;
  border-radius: 0.5rem;
  border: 1px solid var(--bs-border-color);
  object-fit: contain;
}

.image-preview-remove {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  z-index: 10;
}

</style>

