<template>
  <section class="content">
    <Page title="Catálogo de Imagens" subtitle="Gerencie as imagens associadas às coleções de arte." back-to="/settings">

      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <h5 class="mb-3">Selecione uma coleção</h5>
          <div v-if="loadingCollections" class="text-center text-muted py-3">
            Carregando coleções...
          </div>
          <div v-else-if="!collections.length" class="text-center text-muted py-3">
            Nenhuma coleção cadastrada. Cadastre uma coleção antes de enviar imagens.
          </div>
          <div v-else class="collection-list">
            <div
              v-for="collection in collections"
              :key="collection.id"
              class="collection-group"
            >
              <div class="collection-group__header">
                <strong
                  class="collection-name-clickable"
                  @click="toggleCollection(collection.id)"
                  :title="expandedCollections.has(collection.id) ? 'Ocultar subcategorias' : 'Mostrar subcategorias'"
                >
                  {{ collection.name }}
                </strong>
                <button
                  type="button"
                  class="btn btn-link btn-sm p-0"
                  @click="toggleCollection(collection.id)"
                  :title="expandedCollections.has(collection.id) ? 'Ocultar subcategorias' : 'Mostrar subcategorias'"
                >
                  <i :class="['fa', expandedCollections.has(collection.id) ? 'fa-chevron-down' : 'fa-chevron-right']"></i>
                </button>
              </div>
              <div v-if="expandedCollections.has(collection.id)" class="collection-group__subcategories">
                <div v-if="loadingSubcategories[collection.id]" class="text-center text-muted py-2">
                  Carregando subcategorias...
                </div>
                <div v-else-if="!collectionSubcategories[collection.id]?.length" class="text-center text-muted py-2">
                  Nenhuma subcategoria cadastrada.
                </div>
                <div v-else class="subcategories-list">
                  <button
                    v-for="subcategory in collectionSubcategories[collection.id]"
                    :key="subcategory.id"
                    type="button"
                    class="btn collection-button"
                    :class="{
                      'btn-primary': selectedSubcategoryId === subcategory.id,
                      'btn-outline-primary': selectedSubcategoryId !== subcategory.id
                    }"
                    :disabled="isUploading || currentLoading"
                    @click="handleSelectSubcategory(subcategory)"
                  >
                    <div class="collection-button__title">{{ subcategory.name }}</div>
                    <small class="collection-button__meta">
                      {{ formatCount(subcategory.images_count) }}
                    </small>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <section v-if="selectedSubcategoryId" ref="imagesSection" class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <div>
              <h5 class="mb-1">Subcategoria selecionada</h5>
              <strong>
                <p class="mb-0 text-success">{{ selectedSubcategory?.name ?? '—' }}</p>
              </strong>
              <small class="text-muted" v-if="selectedSubcategory?.collection_art">
                Coleção: {{ selectedSubcategory.collection_art.name }}
              </small>
            </div>
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm"
              :disabled="isUploading || currentLoading"
              @click="clearSelection"
            >
              Limpar seleção
            </button>
          </div>

          <form @submit.prevent="handleUpload">
            <div class="row g-3">
              <div class="col-12">
                <label for="catalogFiles" class="form-label">Imagens</label>
                <input
                  id="catalogFiles"
                  ref="fileInput"
                  class="form-control"
                  type="file"
                  accept="image/*"
                  multiple
                  :disabled="isUploading || currentLoading"
                  @change="handleFileChange"
                />
                <small class="text-muted">
                  Selecione uma ou mais imagens (formatos JPG, PNG, WEBP, máximo 5 MB cada).
                </small>
              </div>

              <div v-if="selectedFiles.length" class="col-12">
                <hr class="my-3">
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
                      :disabled="isUploading || currentLoading"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-3 justify-content-end">
              <button
                type="button"
                class="btn btn-outline-secondary"
                :disabled="isUploading || currentLoading"
                @click="resetForm"
              >
                Limpar
              </button>
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="isUploading || currentLoading || !selectedFiles.length"
              >
                <span
                  v-if="isUploading"
                  class="spinner-border spinner-border-sm me-2"
                  role="status"
                  aria-hidden="true"
                ></span>
                Enviar imagens
              </button>
            </div>
          </form>

          <hr class="my-4">

          <div v-if="currentLoading" class="text-center text-muted py-4">
            Carregando imagens da coleção...
          </div>
          <div v-else-if="!currentImages.length" class="text-center text-muted py-4">
            Nenhuma imagem cadastrada nesta coleção.
          </div>
          <div v-else class="image-grid">
            <div v-for="image in currentImages" :key="image.id" class="image-grid__item">
              <div class="image-wrapper">
                <img :src="image.url" :alt="image.path_name" />
                <button
                  type="button"
                  class="btn btn-danger btn-sm image-delete"
                  :disabled="deletingId === image.id"
                  @click="confirmDelete(image)"
                  title="Remover imagem"
                >
                  <i class="fa fa-trash"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </Page>
  </section>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { swalConfirmation, swalError, swalSuccess } from '../../../utils/alerts';
import Page from '@/components/page/Page.vue';

const collections = ref([]);
const collectionSubcategories = reactive({});
const expandedCollections = ref(new Set());
const loadingSubcategories = reactive({});
const collectionImages = reactive({});
const isLoadingImages = reactive({});
const selectedSubcategoryId = ref(null);
const isUploading = ref(false);
const loadingCollections = ref(false);
const deletingId = ref(null);
const fileInput = ref(null);
const imagesSection = ref(null);

const selectedFiles = ref([]);
let fileIdCounter = 0;

const selectedSubcategory = computed(() => {
  if (!selectedSubcategoryId.value) {
    return null;
  }

  for (const collection of collections.value) {
    const subcategories = collectionSubcategories[collection.id] || [];
    const found = subcategories.find((s) => s.id === selectedSubcategoryId.value);
    if (found) {
      return found;
    }
  }
  return null;
});

const currentImages = computed(() => {
  if (!selectedSubcategoryId.value) {
    return [];
  }

  return collectionImages[selectedSubcategoryId.value] ?? [];
});

const currentLoading = computed(() => {
  if (!selectedSubcategoryId.value) {
    return false;
  }

  return Boolean(isLoadingImages[selectedSubcategoryId.value]);
});

const normalizeCollectionOption = (item = {}) => ({
  id: Number(item.id ?? 0),
  name: (item.name ?? '').toString(),
  subcategories: item.subcategories ?? [],
});

const normalizeSubcategory = (item = {}) => ({
  id: Number(item.id ?? 0),
  name: (item.name ?? '').toString(),
  collection_art_id: Number(item.collection_art_id ?? 0),
  images_count: Number(item.images_count ?? 0),
  collection_art: item.collection_art ? {
    id: Number(item.collection_art.id ?? 0),
    name: (item.collection_art.name ?? '').toString(),
  } : null,
});

const buildStorageUrl = (path) => {
  if (!path) {
    return '#';
  }

  if (/^https?:\/\//i.test(path)) {
    return path;
  }

  const baseUrl = window.location.origin.replace(/\/$/, '');
  return `${baseUrl}/storage/${String(path).replace(/^storage\//, '')}`;
};

const resolveImageUrl = (url, pathName) => {
  if (url && /^https?:\/\//i.test(url)) {
    return url;
  }

  if (url && url.startsWith('/')) {
    const baseUrl = window.location.origin.replace(/\/$/, '');
    return `${baseUrl}${url}`;
  }

  return buildStorageUrl(url || pathName || '');
};

const fetchCollections = async () => {
  loadingCollections.value = true;
  try {
    const { data } = await axios.get('v1/collection-arts', {
      params: { per_page: 100 },
    });
    const payload = data?.data ?? data ?? {};
    const items = payload.items ?? payload ?? [];
    collections.value = Array.isArray(items) ? items.map(normalizeCollectionOption) : [];

    // Inicializar subcategorias
    collections.value.forEach((collection) => {
      if (collection.subcategories && Array.isArray(collection.subcategories)) {
        collectionSubcategories[collection.id] = collection.subcategories.map(normalizeSubcategory);
      }
    });

    if (
      selectedSubcategoryId.value &&
      !collections.value.some((collection) => {
        const subcategories = collectionSubcategories[collection.id] || [];
        return subcategories.some((s) => s.id === selectedSubcategoryId.value);
      })
    ) {
      clearSelection();
    }
  } catch (error) {
    collections.value = [];
    swalError('Não foi possível carregar as coleções. Atualize a página e tente novamente.');
  } finally {
    loadingCollections.value = false;
  }
};

const fetchSubcategoryImages = async (subcategoryId) => {
  if (!subcategoryId || isLoadingImages[subcategoryId]) {
    return;
  }

  isLoadingImages[subcategoryId] = true;
  try {
    const { data } = await axios.get(`v1/collection-art-subcategories/${subcategoryId}`);
    const payload = data?.data ?? data ?? {};
    const images = Array.isArray(payload.images) ? payload.images : [];
    collectionImages[subcategoryId] = images.map((image) => ({
      id: Number(image.id ?? 0),
      name: image.name ?? '',
      path_name: image.path_name ?? image.pathName ?? '',
      url: resolveImageUrl(image.url, image.path_name ?? image.pathName ?? ''),
    }));
  } catch (error) {
    collectionImages[subcategoryId] = [];
    swalError('Não foi possível carregar as imagens desta subcategoria.');
  } finally {
    isLoadingImages[subcategoryId] = false;
  }
};

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

const handleFileChange = (event) => {
  const files = event?.target?.files ? Array.from(event.target.files) : [];
  selectedFiles.value = files.map((file) => {
    // Extrair o nome do arquivo sem a extensão
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
  if (isUploading.value || currentLoading.value) {
    return;
  }

  if (!selectedSubcategoryId.value) {
    swalError('Selecione uma subcategoria.');
    return;
  }

  if (!selectedFiles.value.length) {
    swalError('Selecione ao menos uma imagem para enviar.');
    return;
  }

  // Validar se todos os nomes foram preenchidos
  const filesWithoutName = selectedFiles.value.filter((item) => !item.name?.trim());
  if (filesWithoutName.length > 0) {
    swalError('Por favor, preencha o nome para todas as imagens.');
    return;
  }

  isUploading.value = true;

  try {
    const formData = new FormData();
    formData.append('collection_arts_id', selectedSubcategoryId.value);

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

      // Atualizar contagem na subcategoria
      const subcategory = selectedSubcategory.value;
      if (subcategory) {
        subcategory.images_count = collectionImages[selectedSubcategoryId.value].length;
      }
    } else if (selectedSubcategoryId.value) {
      await fetchSubcategoryImages(selectedSubcategoryId.value);
    }

    swalSuccess('Imagens adicionadas com sucesso.');
    resetForm();
  } catch (error) {
    const message =
      error?.response?.data?.message ??
      error?.response?.data?.errors?.images?.[0] ??
      'Não foi possível enviar as imagens. Tente novamente.';
    swalError(message);
  } finally {
    isUploading.value = false;
  }
};

const confirmDelete = async (image) => {
  if (!image?.id || deletingId.value !== null) {
    return;
  }

  const result = await swalConfirmation(
    'Remover imagem?',
    'Essa ação é <strong>irreversível!</strong>',
    'warning',
    'Remover',
    'Cancelar'
  );

  if (result.isConfirmed) {
    await destroyImage(image);
  }
};

const destroyImage = async (image) => {
  if (!image?.id) {
    return;
  }

  deletingId.value = image.id;

  try {
    await axios.delete(`v1/collection-images/${image.id}`);

    if (selectedSubcategoryId.value) {
      collectionImages[selectedSubcategoryId.value] = (collectionImages[selectedSubcategoryId.value] ?? []).filter(
        (item) => item.id !== image.id
      );

      // Atualizar contagem na subcategoria
      const subcategory = selectedSubcategory.value;
      if (subcategory) {
        subcategory.images_count = collectionImages[selectedSubcategoryId.value].length;
      }
    }

    swalSuccess('Imagem removida com sucesso.');
  } catch (error) {
    const message =
      error?.response?.data?.message ??
      'Não foi possível remover a imagem. Tente novamente.';
    swalError(message);
  } finally {
    deletingId.value = null;
  }
};

const handleSelectSubcategory = async (subcategory) => {
  if (!subcategory?.id) {
    return;
  }

  selectedSubcategoryId.value = subcategory.id;
  selectedFiles.value = [];

  if (fileInput.value) {
    fileInput.value.value = '';
  }

  await fetchSubcategoryImages(subcategory.id);

  // Aguardar o DOM atualizar e então fazer scroll para a seção de imagens
  await nextTick();
  if (imagesSection.value) {
    imagesSection.value.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
};

const clearSelection = () => {
  selectedSubcategoryId.value = null;
  selectedFiles.value = [];
  fileIdCounter = 0;

  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const formatCount = (value) => {
  const count = Number(value ?? 0);
  if (count === 1) {
    return '1 imagem';
  }
  return `${count} imagens`;
};

onMounted(() => {
  fetchCollections();
});
</script>

<style scoped>
.collection-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.collection-group {
  border: 1px solid var(--bs-border-color);
  border-radius: 0.75rem;
  padding: 1rem;
  background: var(--bs-body-bg);
}

.collection-group__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.collection-group__subcategories {
  margin-top: 0.75rem;
}

.subcategories-list {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

.collection-button {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.35rem;
  padding: 1rem 1.25rem;
  border-radius: 0.75rem;
  text-align: left;
}

.collection-button__title {
  font-weight: 600;
  font-size: 1rem;
}

.collection-button__meta {
  font-size: 0.85rem;
  color: inherit;
}

.image-grid {
  display: grid;
  gap: 1rem;
}

@media (min-width: 576px) {
  .image-grid {
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  }
}

.image-grid__item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.image-wrapper {
  position: relative;
  overflow: hidden;
  border-radius: 0.75rem;
  border: 1px solid var(--bs-border-color);
  background: var(--bs-secondary-bg);
}

.image-wrapper img {
  width: 100%;
  height: 150px;
  object-fit: cover;
  display: block;
}

.image-delete {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  border-radius: 999px;
}

.image-name {
  margin: 0;
  font-size: 0.85rem;
  color: var(--bs-secondary-color);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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
</style>

