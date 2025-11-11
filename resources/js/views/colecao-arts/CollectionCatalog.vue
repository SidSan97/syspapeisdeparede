<template>
  <section class="content">
    <div class="container py-4">
      <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
          <h1 class="h3 mb-2 text-primary fw-semibold">Catálogo de Imagens</h1>
          <p class="text-muted mb-0">
            Gerencie as imagens associadas às coleções de arte.
          </p>
        </div>
      </header>

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
            <button
              v-for="collection in collections"
              :key="collection.id"
              type="button"
              class="btn collection-button"
              :class="{
                'btn-primary': selectedCollectionId === collection.id,
                'btn-outline-primary': selectedCollectionId !== collection.id
              }"
              :disabled="isUploading || currentLoading"
              @click="handleSelectCollection(collection)"
            >
              <div class="collection-button__title">{{ collection.name }}</div>
              <small class="collection-button__meta">
                {{ formatCount(collectionCounts[collection.id]) }}
              </small>
            </button>
          </div>
        </div>
      </div>

      <section v-if="selectedCollectionId" class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <div>
              <h5 class="mb-1">Coleção selecionada</h5>
              <p class="mb-0 text-muted">{{ selectedCollection?.name ?? '—' }}</p>
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
                <ul v-if="selectedFiles.length" class="small mt-2 mb-0">
                  <li v-for="file in selectedFiles" :key="file.name">
                    {{ file.name }}
                  </li>
                </ul>
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
              <p class="image-name" :title="image.path_name">{{ image.path_name }}</p>
            </div>
          </div>
        </div>
      </section>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { swalConfirmation, swalError, swalSuccess } from '../../../utils/alerts';

const collections = ref([]);
const collectionCounts = reactive({});
const collectionImages = reactive({});
const isLoadingImages = reactive({});
const selectedCollectionId = ref(null);
const isUploading = ref(false);
const loadingCollections = ref(false);
const deletingId = ref(null);
const fileInput = ref(null);

const selectedFiles = ref([]);

const selectedCollection = computed(() =>
  collections.value.find((collection) => collection.id === selectedCollectionId.value) ?? null
);

const currentImages = computed(() => {
  if (!selectedCollectionId.value) {
    return [];
  }

  return collectionImages[selectedCollectionId.value] ?? [];
});

const currentLoading = computed(() => {
  if (!selectedCollectionId.value) {
    return false;
  }

  return Boolean(isLoadingImages[selectedCollectionId.value]);
});

const normalizeCollectionOption = (item = {}) => ({
  id: Number(item.id ?? 0),
  name: (item.name ?? '').toString(),
  images_count: Number(item.images_count ?? item.imagesCount ?? 0),
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

    Object.keys(collectionCounts).forEach((key) => delete collectionCounts[key]);
    collections.value.forEach((collection) => {
      collectionCounts[collection.id] = collection.images_count ?? 0;
    });

    if (
      selectedCollectionId.value &&
      !collections.value.some((collection) => collection.id === selectedCollectionId.value)
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

const fetchCollectionImages = async (collectionId) => {
  if (!collectionId || isLoadingImages[collectionId]) {
    return;
  }

  isLoadingImages[collectionId] = true;
  try {
    const { data } = await axios.get(`v1/collection-arts/${collectionId}`);
    const payload = data?.data ?? data ?? {};
    const images = Array.isArray(payload.images) ? payload.images : [];
    collectionImages[collectionId] = images.map((image) => ({
      id: Number(image.id ?? 0),
      path_name: image.path_name ?? image.pathName ?? '',
      url: resolveImageUrl(image.url, image.path_name ?? image.pathName ?? ''),
    }));
    collectionCounts[collectionId] = collectionImages[collectionId].length;
  } catch (error) {
    collectionImages[collectionId] = [];
    swalError('Não foi possível carregar as imagens desta coleção.');
  } finally {
    isLoadingImages[collectionId] = false;
  }
};

const handleFileChange = (event) => {
  const files = event?.target?.files ? Array.from(event.target.files) : [];
  selectedFiles.value = files;
};

const resetForm = () => {
  selectedFiles.value = [];

  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const clearSelection = () => {
  selectedCollectionId.value = null;
  selectedFiles.value = [];

  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const handleUpload = async () => {
  if (isUploading.value || currentLoading.value) {
    return;
  }

  if (!selectedCollectionId.value) {
    swalError('Selecione uma coleção.');
    return;
  }

  if (!selectedFiles.value.length) {
    swalError('Selecione ao menos uma imagem para enviar.');
    return;
  }

  isUploading.value = true;

  try {
    const formData = new FormData();
    formData.append('collection_arts_id', selectedCollectionId.value);
    selectedFiles.value.forEach((file) => {
      formData.append('images[]', file);
    });

    const response = await axios.post('v1/collection-images', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    const savedCollections = response?.data?.data ?? [];

    if (
      Array.isArray(savedCollections) &&
      savedCollections.length &&
      savedCollections[0]?.id === selectedCollectionId.value
    ) {
      collectionImages[selectedCollectionId.value] = (savedCollections[0].images ?? []).map((image) => ({
        id: Number(image.id ?? 0),
        path_name: image.path_name ?? image.pathName ?? '',
        url: resolveImageUrl(image.url, image.path_name ?? image.pathName ?? ''),
      }));
      collectionCounts[selectedCollectionId.value] = collectionImages[selectedCollectionId.value].length;
    } else if (selectedCollectionId.value) {
      await fetchCollectionImages(selectedCollectionId.value);
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

    if (selectedCollectionId.value) {
      collectionImages[selectedCollectionId.value] = (collectionImages[selectedCollectionId.value] ?? []).filter(
        (item) => item.id !== image.id
      );
      collectionCounts[selectedCollectionId.value] = collectionImages[selectedCollectionId.value].length;
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

const handleSelectCollection = async (collection) => {
  if (!collection?.id) {
    return;
  }

  selectedCollectionId.value = collection.id;
  selectedFiles.value = [];

  if (fileInput.value) {
    fileInput.value.value = '';
  }

  await fetchCollectionImages(collection.id);
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
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
</style>

