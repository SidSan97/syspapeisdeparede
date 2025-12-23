<template>
  <section class="content">
    <Page title="Gerenciar Coleções" subtitle="Gerencie coleções, subcategorias e imagens." back-to="/settings">
      <template #actions>
        <button type="button" class="btn btn-primary" @click="startCreatingCollection">
          <i class="fa fa-plus me-2"></i> Nova coleção
        </button>
      </template>

      <!-- Formulário de Coleção -->
      <div v-if="isCollectionFormVisible" class="card mb-4 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">
              {{ isEditingCollection ? 'Editar coleção' : 'Nova coleção' }}
            </h5>
            <button type="button" class="btn-close" @click="cancelCollectionForm"></button>
          </div>

          <form @submit.prevent="handleCollectionSubmit">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="collectionName" class="form-label">Nome da coleção</label>
                <input
                  id="collectionName"
                  v-model.trim="collectionForm.name"
                  type="text"
                  class="form-control"
                  placeholder="Ex: Linha Clássica"
                  required
                />
              </div>
              <div class="col-md-6">
                <label for="collectionImageCover" class="form-label">Imagem de capa</label>
                <input
                  id="collectionImageCover"
                  ref="collectionImageCoverInput"
                  type="file"
                  class="form-control"
                  accept="image/*"
                  @change="handleCollectionImageChange"
                />
                <small class="text-muted">Formatos: JPG, PNG, GIF (máx. 5MB)</small>
              </div>
              <div v-if="collectionForm.imagePreview || currentCollectionImageUrl" class="col-12">
                <label class="form-label">Preview</label>
                <div class="position-relative d-inline-block">
                  <img
                    :src="collectionForm.imagePreview || currentCollectionImageUrl"
                    alt="Preview"
                    class="img-thumbnail"
                    style="max-width: 300px; max-height: 300px; object-fit: contain;"
                  />
                  <button
                    type="button"
                    class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2"
                    @click="removeCollectionImage"
                  >
                    <i class="fa fa-times"></i>
                  </button>
                </div>
              </div>
            </div>

            <div class="mt-4 d-flex gap-2">
              <button type="submit" class="btn btn-primary" :disabled="isSavingCollection">
                <span
                  v-if="isSavingCollection"
                  class="spinner-border spinner-border-sm me-2"
                ></span>
                {{ isEditingCollection ? 'Salvar alterações' : 'Salvar coleção' }}
              </button>
              <button type="button" class="btn btn-outline-secondary" @click="cancelCollectionForm">
                Cancelar
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Lista de Coleções -->
      <div class="card shadow-sm">
        <div class="card-body">
          <div v-if="isLoadingCollections" class="text-center text-muted py-5">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Carregando...</span>
            </div>
          </div>

          <div v-else-if="!collections.length" class="text-center text-muted py-5">
            <p class="mb-3">Nenhuma coleção cadastrada.</p>
            <button type="button" class="btn btn-primary" @click="startCreatingCollection">
              Criar primeira coleção
            </button>
          </div>

          <div v-else class="accordion" id="collectionsAccordion">
            <div
              v-for="collection in collections"
              :key="collection.id"
              class="accordion-item"
            >
              <h2 class="accordion-header">
                <button
                  class="accordion-button"
                  :class="{ collapsed: !expandedCollections.has(collection.id) }"
                  type="button"
                  data-bs-toggle="collapse"
                  :data-bs-target="`#collection-${collection.id}`"
                  @click="toggleCollection(collection.id)"
                >
                  <div class="d-flex justify-content-between align-items-center w-100 me-3">
                    <span class="fw-semibold">{{ collection.name }}</span>
                    <div class="dropdown" @click.stop>
                      <button
                        class="btn btn-sm btn-outline-secondary dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                      >
                        Ações
                      </button>
                      <ul class="dropdown-menu">
                        <li>
                          <button
                            class="dropdown-item"
                            type="button"
                            @click="startCreatingSubcategory(collection)"
                          >
                            <i class="fa fa-plus me-2"></i> Nova subcategoria
                          </button>
                        </li>
                        <li>
                          <button
                            class="dropdown-item"
                            type="button"
                            @click="editCollection(collection)"
                          >
                            <i class="fa fa-edit me-2"></i> Editar
                          </button>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                          <button
                            class="dropdown-item text-danger"
                            type="button"
                            :disabled="deletingCollectionId === collection.id"
                            @click="confirmDeleteCollection(collection)"
                          >
                            <i class="fa fa-trash me-2"></i> Excluir
                          </button>
                        </li>
                      </ul>
                    </div>
                  </div>
                </button>
              </h2>
              <div
                :id="`collection-${collection.id}`"
                class="accordion-collapse collapse"
                :class="{ show: expandedCollections.has(collection.id) }"
                data-bs-parent="#collectionsAccordion"
              >
                <div class="accordion-body">
                  <!-- Subcategorias da Coleção -->
                  <div v-if="loadingSubcategories[collection.id]" class="text-center text-muted py-3">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                  </div>
                  <div v-else-if="!collectionSubcategories[collection.id]?.length" class="text-center text-muted py-3">
                    Nenhuma subcategoria cadastrada.
                  </div>
                  <div v-else class="list-group">
                    <div
                      v-for="subcategory in collectionSubcategories[collection.id]"
                      :key="subcategory.id"
                      class="list-group-item"
                      :class="{ 'active': selectedSubcategoryId === subcategory.id }"
                      style="cursor: pointer;"
                      @click="selectSubcategory(subcategory)"
                    >
                      <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                          <h6 class="mb-1">{{ subcategory.name }}</h6>
                          <small class="text-muted">{{ formatCount(subcategory.images_count) }}</small>
                        </div>
                        <button
                          class="btn btn-sm btn-outline-danger"
                          type="button"
                          :disabled="deletingSubcategoryId === subcategory.id"
                          @click.stop="confirmDeleteSubcategory(subcategory)"
                          title="Excluir subcategoria"
                        >
                          <i class="fa fa-trash"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Painel de Gerenciamento de Subcategoria -->
      <div v-if="selectedSubcategory || isCreatingNewSubcategory" class="card mt-4 shadow-sm">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-0">
                {{ isCreatingNewSubcategory ? 'Nova subcategoria' : `Gerenciar: ${selectedSubcategory?.name}` }}
              </h5>
              <small v-if="!isCreatingNewSubcategory" class="text-muted">
                Coleção: {{ getCollectionName(selectedSubcategory?.parent_id) }}
              </small>
              <small v-else class="text-muted">
                Coleção: {{ getCollectionName(subcategoryForm.parent_id) }}
              </small>
            </div>
            <button type="button" class="btn-close" @click="clearSubcategorySelection"></button>
          </div>
        </div>
        <div class="card-body">
          <ul v-if="!isCreatingNewSubcategory" class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
              <button
                class="nav-link"
                :class="{ active: activeTab === 'subcategory' }"
                type="button"
                @click="activeTab = 'subcategory'"
              >
                <i class="fa fa-edit me-2"></i> Editar Subcategoria
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button
                class="nav-link"
                :class="{ active: activeTab === 'upload' }"
                type="button"
                @click="activeTab = 'upload'"
              >
                <i class="fa fa-upload me-2"></i> Upload de Imagens
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button
                class="nav-link"
                :class="{ active: activeTab === 'images' }"
                type="button"
                @click="activeTab = 'images'"
              >
                <i class="fa fa-images me-2"></i> Imagens ({{ currentImages.length }})
              </button>
            </li>
          </ul>

          <div class="tab-content">
            <!-- Tab: Editar Subcategoria -->
            <div v-show="activeTab === 'subcategory'" class="tab-pane fade" :class="{ 'show active': activeTab === 'subcategory' }">
              <form @submit.prevent="handleSubcategorySubmit">
                <div class="row g-3">
                  <div class="col-md-6">
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
                  <div class="col-md-6">
                    <label for="subcategoryImageCover" class="form-label">Imagem de capa</label>
                    <input
                      id="subcategoryImageCover"
                      ref="subcategoryImageCoverInput"
                      type="file"
                      class="form-control"
                      accept="image/*"
                      @change="handleSubcategoryImageChange"
                    />
                    <small class="text-muted">Formatos: JPG, PNG, GIF (máx. 5MB)</small>
                  </div>
                  <div v-if="subcategoryForm.imagePreview || currentSubcategoryImageUrl" class="col-12">
                    <label class="form-label">Preview</label>
                    <div class="position-relative d-inline-block">
                      <img
                        :src="subcategoryForm.imagePreview || currentSubcategoryImageUrl"
                        alt="Preview"
                        class="img-thumbnail"
                        style="max-width: 300px; max-height: 300px; object-fit: contain;"
                      />
                      <button
                        type="button"
                        class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2"
                        @click="removeSubcategoryImage"
                      >
                        <i class="fa fa-times"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                  <button type="submit" class="btn btn-primary" :disabled="isSavingSubcategory">
                    <span
                      v-if="isSavingSubcategory"
                      class="spinner-border spinner-border-sm me-2"
                    ></span>
                    Salvar alterações
                  </button>
                  <button type="button" class="btn btn-outline-secondary" @click="cancelSubcategoryForm">
                    Cancelar
                  </button>
                </div>
              </form>
            </div>

            <!-- Tab: Upload de Imagens -->
            <div v-show="activeTab === 'upload'" class="tab-pane fade" :class="{ 'show active': activeTab === 'upload' }">
              <form @submit.prevent="handleUpload">
                <div class="mb-3">
                  <label for="catalogFiles" class="form-label">Selecione as imagens</label>
                  <input
                    id="catalogFiles"
                    ref="fileInput"
                    class="form-control"
                    type="file"
                    accept="image/*"
                    multiple
                    :disabled="isUploading"
                    @change="handleFileChange"
                  />
                  <small class="text-muted">Formatos: JPG, PNG, WEBP (máx. 5MB cada)</small>
                </div>

                <div v-if="selectedFiles.length" class="mb-4">
                  <hr>
                  <h6 class="mb-3">Nome das imagens</h6>
                  <div class="row g-3">
                    <div
                      v-for="fileItem in selectedFiles"
                      :key="fileItem.id"
                      class="col-12 col-md-6"
                    >
                      <label :for="`imageName_${fileItem.id}`" class="form-label">
                        Nome para: <small class="text-muted">{{ fileItem.file.name }}</small>
                      </label>
                      <input
                        :id="`imageName_${fileItem.id}`"
                        v-model.trim="fileItem.name"
                        type="text"
                        class="form-control"
                        placeholder="Digite o nome da imagem"
                        maxlength="100"
                        :disabled="isUploading"
                      />
                    </div>
                  </div>
                </div>

                <div class="d-flex gap-2">
                  <button
                    type="button"
                    class="btn btn-outline-secondary"
                    :disabled="isUploading"
                    @click="resetForm"
                  >
                    Limpar
                  </button>
                  <button
                    type="submit"
                    class="btn btn-primary"
                    :disabled="isUploading || !selectedFiles.length"
                  >
                    <span
                      v-if="isUploading"
                      class="spinner-border spinner-border-sm me-2"
                    ></span>
                    Enviar imagens
                  </button>
                </div>
              </form>
            </div>

            <!-- Tab: Visualizar Imagens -->
            <div v-show="activeTab === 'images'" class="tab-pane fade" :class="{ 'show active': activeTab === 'images' }">
              <div v-if="currentLoading" class="text-center text-muted py-4">
                <div class="spinner-border" role="status"></div>
              </div>
              <div v-else-if="!currentImages.length" class="text-center text-muted py-4">
                Nenhuma imagem cadastrada nesta subcategoria.
              </div>
              <div v-else class="row g-3">
                <div
                  v-for="image in currentImages"
                  :key="image.id"
                  class="col-6 col-md-4 col-lg-3"
                >
                  <div class="card">
                    <div class="position-relative">
                      <img
                        :src="image.url"
                        :alt="image.path_name"
                        class="card-img-top"
                        style="height: 200px; object-fit: cover;"
                      />
                      <button
                        type="button"
                        class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2"
                        :disabled="deletingImageId === image.id"
                        @click="confirmDeleteImage(image)"
                        title="Remover imagem"
                      >
                        <i class="fa fa-trash"></i>
                      </button>
                    </div>
                    <div class="card-body">
                      <p class="card-text small text-truncate mb-0">{{ image.name }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Page>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { swalConfirmation, swalSuccess, swalError } from '../../../utils/alerts';
import Page from '@/components/page/Page.vue';

// Collections
const collections = ref([]);
const isLoadingCollections = ref(false);
const isCollectionFormVisible = ref(false);
const isEditingCollection = ref(false);
const isSavingCollection = ref(false);
const deletingCollectionId = ref(null);
const editingCollectionId = ref(null);
const collectionImageCoverInput = ref(null);
const currentCollectionImageUrl = ref(null);

// Subcategories
const collectionSubcategories = reactive({});
const expandedCollections = ref(new Set());
const loadingSubcategories = reactive({});
const isSubcategoryFormVisible = ref(false);
const isEditingSubcategory = ref(false);
const isSavingSubcategory = ref(false);
const deletingSubcategoryId = ref(null);
const editingSubcategoryId = ref(null);
const subcategoryImageCoverInput = ref(null);
const currentSubcategoryImageUrl = ref(null);
const isCreatingNewSubcategory = ref(false);

// Images
const collectionImages = reactive({});
const isLoadingImages = reactive({});
const selectedSubcategoryId = ref(null);
const activeTab = ref('subcategory');
const isUploading = ref(false);
const deletingImageId = ref(null);
const fileInput = ref(null);
const selectedFiles = ref([]);
let fileIdCounter = 0;

// Forms
const collectionForm = reactive({
  name: '',
  imageFile: null,
  imagePreview: null,
});

const subcategoryForm = reactive({
  name: '',
  parent_id: null,
  imageFile: null,
  imagePreview: null,
});

// Computed
const selectedSubcategory = computed(() => {
  if (!selectedSubcategoryId.value) return null;
  for (const collection of collections.value) {
    const subcategories = collectionSubcategories[collection.id] || [];
    const found = subcategories.find((s) => s.id === selectedSubcategoryId.value);
    if (found) return found;
  }
  return null;
});

const currentImages = computed(() => {
  if (!selectedSubcategoryId.value) return [];
  return collectionImages[selectedSubcategoryId.value] ?? [];
});

const currentLoading = computed(() => {
  if (!selectedSubcategoryId.value) return false;
  return Boolean(isLoadingImages[selectedSubcategoryId.value]);
});

// Utility functions
const buildStorageUrl = (path) => {
  if (!path) return '';
  const baseUrl = window.location.origin.replace(/\/$/, '');
  return `${baseUrl}/storage/${path.replace(/^\//, '')}`;
};

const resolveImageUrl = (url, path) => {
  if (url && /^https?:\/\//i.test(url)) return url;
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
  children: item.children ?? [],
});

const normalizeSubcategory = (item = {}) => ({
  id: Number(item.id ?? 0),
  name: (item.name ?? '').toString(),
  parent_id: Number(item.parent_id ?? 0),
  image_cover: item.image_cover ?? null,
  image_cover_url: item.image_cover_url || (item.image_cover ? resolveImageUrl(null, item.image_cover) : null),
  images_count: Number(item.images_count ?? 0),
});

const sortCollections = (items = []) =>
  [...items].sort((a, b) => a.name.localeCompare(b.name, 'pt-BR', { sensitivity: 'base' }));

const formatCount = (count) => {
  const total = Number(count ?? 0);
  return total === 1 ? '1 imagem' : `${total} imagens`;
};

const getCollectionName = (collectionId) => {
  const collection = collections.value.find((c) => c.id === collectionId);
  return collection?.name ?? '—';
};

// Fetch functions
const fetchCollections = async () => {
  isLoadingCollections.value = true;
  try {
    const { data } = await axios.get('v1/collection-categories', {
      params: { tree: true, per_page: 100 },
    });

    const payload = data?.data ?? data ?? {};
    const items = Array.isArray(payload) ? payload : (payload.items ?? []);
    const rootCategories = items.filter(item => !item.parent_id);
    const list = Array.isArray(rootCategories) ? rootCategories.map(normalizeCollection) : [];
    collections.value = sortCollections(list);

    collections.value.forEach((collection) => {
      if (collection.children && Array.isArray(collection.children)) {
        collectionSubcategories[collection.id] = collection.children.map(normalizeSubcategory);
      }
    });
  } catch (error) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Não foi possível carregar as coleções.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    collections.value = [];
  } finally {
    isLoadingCollections.value = false;
  }
};

const fetchSubcategories = async (categoryId) => {
  if (loadingSubcategories[categoryId]) return;
  loadingSubcategories[categoryId] = true;
  try {
    const { data } = await axios.get(`v1/collection-categories/children/${categoryId}`);
    const payload = data?.data ?? data ?? [];
    const items = Array.isArray(payload) ? payload : [];
    collectionSubcategories[categoryId] = items.map(normalizeSubcategory);
  } catch (error) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Não foi possível carregar as subcategorias.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    collectionSubcategories[categoryId] = [];
  } finally {
    loadingSubcategories[categoryId] = false;
  }
};

const fetchSubcategoryImages = async (categoryId) => {
  if (!categoryId || isLoadingImages[categoryId]) return;
  isLoadingImages[categoryId] = true;
  try {
    const { data } = await axios.get(`v1/collection-categories/${categoryId}`);
    const payload = data?.data ?? data ?? {};
    const images = Array.isArray(payload.images) ? payload.images : [];
    collectionImages[categoryId] = images.map((image) => ({
      id: Number(image.id ?? 0),
      name: image.name ?? '',
      path_name: image.path_name ?? image.pathName ?? '',
      url: resolveImageUrl(image.url, image.path_name ?? image.pathName ?? ''),
    }));
  } catch (error) {
    collectionImages[categoryId] = [];
    window.Swal.fire({
      title: 'Erro!',
      text: 'Não foi possível carregar as imagens.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
  } finally {
    isLoadingImages[categoryId] = false;
  }
};

// Collection functions
const toggleCollection = async (collectionId) => {
  if (expandedCollections.value.has(collectionId)) {
    expandedCollections.value.delete(collectionId);
  } else {
    expandedCollections.value.add(collectionId);
    if (!collectionSubcategories[collectionId]) {
      await fetchSubcategories(collectionId);
    }
  }
};

const startCreatingCollection = () => {
  editingCollectionId.value = null;
  collectionForm.name = '';
  collectionForm.imageFile = null;
  collectionForm.imagePreview = null;
  currentCollectionImageUrl.value = null;
  if (collectionImageCoverInput.value) {
    collectionImageCoverInput.value.value = '';
  }
  isEditingCollection.value = false;
  isCollectionFormVisible.value = true;
};

const editCollection = (collection) => {
  if (!collection) return;
  isCollectionFormVisible.value = true;
  isEditingCollection.value = true;
  editingCollectionId.value = collection.id;
  collectionForm.name = collection.name;
  collectionForm.imageFile = null;
  collectionForm.imagePreview = null;
  currentCollectionImageUrl.value = collection.image_cover_url || null;
  if (collectionImageCoverInput.value) {
    collectionImageCoverInput.value.value = '';
  }
};

const cancelCollectionForm = () => {
  collectionForm.name = '';
  collectionForm.imageFile = null;
  collectionForm.imagePreview = null;
  currentCollectionImageUrl.value = null;
  if (collectionImageCoverInput.value) {
    collectionImageCoverInput.value.value = '';
  }
  editingCollectionId.value = null;
  isCollectionFormVisible.value = false;
};

const handleCollectionImageChange = (event) => {
  const file = event.target.files?.[0];
  if (!file) {
    collectionForm.imageFile = null;
    collectionForm.imagePreview = null;
    return;
  }

  if (file.size > 5 * 1024 * 1024) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'A imagem não pode ser maior que 5MB.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    event.target.value = '';
    collectionForm.imageFile = null;
    collectionForm.imagePreview = null;
    return;
  }

  if (!file.type.startsWith('image/')) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Por favor, selecione um arquivo de imagem válido.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    event.target.value = '';
    collectionForm.imageFile = null;
    collectionForm.imagePreview = null;
    return;
  }

  collectionForm.imageFile = file;
  currentCollectionImageUrl.value = null;

  const reader = new FileReader();
  reader.onload = (e) => {
    collectionForm.imagePreview = e.target.result;
  };
  reader.readAsDataURL(file);
};

const removeCollectionImage = () => {
  collectionForm.imageFile = null;
  collectionForm.imagePreview = null;
  currentCollectionImageUrl.value = null;
  if (collectionImageCoverInput.value) {
    collectionImageCoverInput.value.value = '';
  }
};

const handleCollectionSubmit = async () => {
  if (isSavingCollection.value) return;

  const trimmedName = collectionForm.name?.trim();
  if (!trimmedName) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Informe o nome da coleção.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    return;
  }

  isSavingCollection.value = true;

  try {
    const formData = new FormData();
    formData.append('name', trimmedName);

    if (collectionForm.imageFile) {
      formData.append('image_cover', collectionForm.imageFile);
    }

    let response;
    if (isEditingCollection.value && editingCollectionId.value !== null) {
      formData.append('_method', 'PUT');
      response = await axios.post(`v1/collection-categories/${editingCollectionId.value}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    } else {
      formData.append('parent_id', '');
      response = await axios.post('v1/collection-categories', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
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
      title: isEditingCollection.value ? 'Coleção atualizada!' : 'Coleção criada!',
      text: isEditingCollection.value ? 'Coleção atualizada com sucesso.' : 'Coleção criada com sucesso.',
      icon: 'success',
      confirmButtonText: 'Entendi!',
    });
    cancelCollectionForm();
  } catch (error) {
    const message = error?.response?.data?.message ?? 'Não foi possível salvar a coleção.';
    window.Swal.fire({
      title: 'Erro!',
      text: message,
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
  } finally {
    isSavingCollection.value = false;
  }
};

const confirmDeleteCollection = async (collection) => {
  if (!collection?.id || deletingCollectionId.value !== null) return;

  const result = await swalConfirmation(
    'Excluir coleção?',
    'Essa ação é <strong>irreversível!</strong>',
    'warning',
    'Excluir',
    'Cancelar'
  );

  if (result.isConfirmed) {
    deletingCollectionId.value = collection.id;
    try {
      await axios.delete(`v1/collection-categories/${collection.id}`);
      collections.value = collections.value.filter((item) => item.id !== collection.id);
      swalSuccess('Coleção excluída com sucesso.', 'Coleção excluída!');
      if (isCollectionFormVisible.value && editingCollectionId.value === collection.id) {
        cancelCollectionForm();
      }
    } catch (error) {
      const message = error?.response?.data?.message ?? 'Não foi possível excluir a coleção.';
      swalError(message);
    } finally {
      deletingCollectionId.value = null;
    }
  }
};

// Subcategory functions
const startCreatingSubcategory = async (collection) => {
  if (!collection?.id) return;

  // Expand collection if not expanded
  if (!expandedCollections.value.has(collection.id)) {
    expandedCollections.value.add(collection.id);
    if (!collectionSubcategories[collection.id]) {
      await fetchSubcategories(collection.id);
    }
  }

  editingSubcategoryId.value = null;
  subcategoryForm.name = '';
  subcategoryForm.parent_id = collection.id;
  subcategoryForm.imageFile = null;
  subcategoryForm.imagePreview = null;
  currentSubcategoryImageUrl.value = null;
  if (subcategoryImageCoverInput.value) {
    subcategoryImageCoverInput.value.value = '';
  }
  isEditingSubcategory.value = false;
  isCreatingNewSubcategory.value = true;
  selectedSubcategoryId.value = null;
  activeTab.value = 'subcategory';
};

const selectSubcategory = async (subcategory) => {
  if (!subcategory?.id) return;
  selectedSubcategoryId.value = subcategory.id;
  activeTab.value = 'subcategory';

  // Load subcategory data for editing
  subcategoryForm.name = subcategory.name;
  subcategoryForm.parent_id = subcategory.parent_id;
  subcategoryForm.imageFile = null;
  subcategoryForm.imagePreview = null;
  currentSubcategoryImageUrl.value = subcategory.image_cover_url || null;
  isEditingSubcategory.value = true;
  editingSubcategoryId.value = subcategory.id;

  await fetchSubcategoryImages(subcategory.id);
};

const editSubcategory = (subcategory) => {
  selectSubcategory(subcategory);
};

const clearSubcategorySelection = () => {
  selectedSubcategoryId.value = null;
  isCreatingNewSubcategory.value = false;
  activeTab.value = 'subcategory';
  cancelSubcategoryForm();
};

const cancelSubcategoryForm = () => {
  subcategoryForm.name = '';
  subcategoryForm.parent_id = null;
  subcategoryForm.imageFile = null;
  subcategoryForm.imagePreview = null;
  currentSubcategoryImageUrl.value = null;
  if (subcategoryImageCoverInput.value) {
    subcategoryImageCoverInput.value.value = '';
  }
  editingSubcategoryId.value = null;
  isEditingSubcategory.value = false;
  isCreatingNewSubcategory.value = false;
};

const handleSubcategoryImageChange = (event) => {
  const file = event.target.files?.[0];
  if (!file) {
    subcategoryForm.imageFile = null;
    subcategoryForm.imagePreview = null;
    return;
  }

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
  if (isSavingSubcategory.value) return;

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

  if (!subcategoryForm.parent_id) {
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
    formData.append('parent_id', subcategoryForm.parent_id);

    if (subcategoryForm.imageFile) {
      formData.append('image_cover', subcategoryForm.imageFile);
    }

    let response;
    if (isEditingSubcategory.value && editingSubcategoryId.value !== null) {
      formData.append('_method', 'PUT');
      response = await axios.post(`v1/collection-categories/${editingSubcategoryId.value}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    } else {
      response = await axios.post('v1/collection-categories', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    }

    const saved = normalizeSubcategory(response?.data?.data ?? response?.data ?? {});
    const collectionId = saved.parent_id;

    if (!collectionSubcategories[collectionId]) {
      collectionSubcategories[collectionId] = [];
    }

    const index = collectionSubcategories[collectionId].findIndex((item) => item.id === saved.id);
    if (index !== -1) {
      collectionSubcategories[collectionId].splice(index, 1, saved);
    } else {
      collectionSubcategories[collectionId].push(saved);
    }

    // If creating new, select it and load images
    if (isCreatingNewSubcategory.value) {
      selectedSubcategoryId.value = saved.id;
      isCreatingNewSubcategory.value = false;
      await fetchSubcategoryImages(saved.id);
    } else if (selectedSubcategoryId.value === saved.id) {
      // Update selected subcategory if it's the one being edited
      selectedSubcategoryId.value = saved.id;
    }

    window.Swal.fire({
      title: isEditingSubcategory.value ? 'Subcategoria atualizada!' : 'Subcategoria criada!',
      text: isEditingSubcategory.value ? 'Subcategoria atualizada com sucesso.' : 'Subcategoria criada com sucesso.',
      icon: 'success',
      confirmButtonText: 'Entendi!',
    });
  } catch (error) {
    const message = error?.response?.data?.message ?? 'Não foi possível salvar a subcategoria.';
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
  if (!subcategory?.id || deletingSubcategoryId.value !== null) return;

  const result = await swalConfirmation(
    'Excluir subcategoria?',
    'Essa ação é <strong>irreversível!</strong> Todas as imagens associadas serão removidas.',
    'warning',
    'Excluir',
    'Cancelar'
  );

  if (result.isConfirmed) {
    deletingSubcategoryId.value = subcategory.id;
    try {
      await axios.delete(`v1/collection-categories/${subcategory.id}`);
      const collectionId = subcategory.parent_id;
      if (collectionSubcategories[collectionId]) {
        collectionSubcategories[collectionId] = collectionSubcategories[collectionId].filter(
          (item) => item.id !== subcategory.id
        );
      }
      if (selectedSubcategoryId.value === subcategory.id) {
        clearSubcategorySelection();
      }
      swalSuccess('Subcategoria excluída com sucesso.', 'Subcategoria excluída!');
    } catch (error) {
      const message = error?.response?.data?.message ?? 'Não foi possível excluir a subcategoria.';
      swalError(message);
    } finally {
      deletingSubcategoryId.value = null;
    }
  }
};

// Image functions
const handleFileChange = (event) => {
  const files = event?.target?.files ? Array.from(event.target.files) : [];
  selectedFiles.value = files.map((file) => {
    const fileName = file.name;
    const lastDotIndex = fileName.lastIndexOf('.');
    const nameWithoutExtension = lastDotIndex > 0
      ? fileName.substring(0, lastDotIndex)
      : fileName;

    return {
      id: ++fileIdCounter,
      file: file,
      name: nameWithoutExtension || '',
    };
  });
};

const resetForm = () => {
  selectedFiles.value = [];
  fileIdCounter = 0;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const handleUpload = async () => {
  if (isUploading.value || currentLoading.value) return;

  if (!selectedSubcategoryId.value) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Selecione uma subcategoria.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    return;
  }

  if (!selectedFiles.value.length) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Selecione ao menos uma imagem para enviar.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    return;
  }

  const filesWithoutName = selectedFiles.value.filter((item) => !item.name?.trim());
  if (filesWithoutName.length > 0) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Por favor, preencha o nome para todas as imagens.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    return;
  }

  isUploading.value = true;

  try {
    const formData = new FormData();
    formData.append('collection_category_id', selectedSubcategoryId.value);

    selectedFiles.value.forEach((fileItem, index) => {
      formData.append('images[]', fileItem.file);
      formData.append(`names[${index}]`, fileItem.name.trim());
    });

    const response = await axios.post('v1/collection-images', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    const savedCollections = response?.data?.data ?? [];

    if (
      Array.isArray(savedCollections) &&
      savedCollections.length &&
      savedCollections[0]?.images
    ) {
      collectionImages[selectedSubcategoryId.value] = savedCollections[0].images.map((image) => ({
        id: Number(image.id ?? 0),
        name: image.name ?? '',
        path_name: image.path_name ?? image.pathName ?? '',
        url: resolveImageUrl(image.url, image.path_name ?? image.pathName ?? ''),
      }));

      const subcategory = selectedSubcategory.value;
      if (subcategory) {
        subcategory.images_count = collectionImages[selectedSubcategoryId.value].length;
      }
    } else if (selectedSubcategoryId.value) {
      await fetchSubcategoryImages(selectedSubcategoryId.value);
    }

    window.Swal.fire({
      title: 'Imagens adicionadas!',
      text: 'Imagens adicionadas com sucesso.',
      icon: 'success',
      confirmButtonText: 'Entendi!',
    });
    resetForm();
    activeTab.value = 'images';
  } catch (error) {
    const message =
      error?.response?.data?.message ??
      error?.response?.data?.errors?.images?.[0] ??
      'Não foi possível enviar as imagens.';
    window.Swal.fire({
      title: 'Erro!',
      text: message,
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
  } finally {
    isUploading.value = false;
  }
};

const confirmDeleteImage = async (image) => {
  if (!image?.id || deletingImageId.value !== null) return;

  const result = await swalConfirmation(
    'Remover imagem?',
    'Essa ação é <strong>irreversível!</strong>',
    'warning',
    'Remover',
    'Cancelar'
  );

  if (result.isConfirmed) {
    deletingImageId.value = image.id;
    try {
      await axios.delete(`v1/collection-images/${image.id}`);

      if (selectedSubcategoryId.value) {
        collectionImages[selectedSubcategoryId.value] = (collectionImages[selectedSubcategoryId.value] ?? []).filter(
          (item) => item.id !== image.id
        );

        const subcategory = selectedSubcategory.value;
        if (subcategory) {
          subcategory.images_count = collectionImages[selectedSubcategoryId.value].length;
        }
      }

      swalSuccess('Imagem removida com sucesso.', 'Imagem removida!');
    } catch (error) {
      const message = error?.response?.data?.message ?? 'Não foi possível remover a imagem.';
      swalError(message);
    } finally {
      deletingImageId.value = null;
    }
  }
};

onMounted(() => {
  document.title = 'Gerenciar Coleções';
  fetchCollections();
});
</script>

<style scoped>
</style>

