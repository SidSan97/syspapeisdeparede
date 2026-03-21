<template>
  <div v-if="model && hasRequirements" class="border rounded p-3 bg-body-tertiary mt-2">
    <div class="text-muted small mb-2">Requisitos do modelo selecionado</div>

    <div v-if="requiresComment" class="mb-3">
      <label class="form-label">Descrição do modelo</label>
      <textarea
        v-model.trim="wall.comment_referring_model"
        class="form-control"
        rows="3"
        maxlength="500"
        :disabled="disabled"
      ></textarea>
    </div>

    <div v-if="requiresLink" class="mb-3">
      <label class="form-label">Link de referência</label>
      <input
        v-model.trim="wall.link_referring_model"
        type="url"
        class="form-control"
        placeholder="https://exemplo.com"
        :disabled="disabled"
      />
    </div>

    <div v-if="requiresFiles" class="mb-3">
      <label class="form-label">Imagem de referência</label>
      <input
        ref="fileInputRef"
        type="file"
        accept="image/*"
        class="form-control"
        :disabled="disabled || uploadingFile"
        @change="handleFileChange"
      />
      <small class="text-muted">Envie uma imagem em formato JPG, PNG ou WEBP (máx. 10MB).</small>

      <div v-if="uploadingFile" class="mt-2 text-muted small">
        <span class="spinner-border spinner-border-sm me-2" role="status"></span>
        Enviando...
      </div>

      <div v-if="uploadedImagePath" class="mt-2 d-flex align-items-center gap-2">
        <img
          :src="resolveStorageUrl(uploadedImagePath)"
          alt="Imagem de referência"
          class="rounded"
          style="max-height: 80px; max-width: 120px; object-fit: contain;"
          @error="handleImageError"
        />
        <div class="flex-grow-1 small">
          <span class="text-muted">Imagem enviada</span>
          <button
            type="button"
            class="btn btn-link btn-sm text-danger p-0 ms-2"
            :disabled="disabled"
            @click="removeUploadedImage"
          >
            Remover
          </button>
        </div>
      </div>
    </div>

    <div v-if="requiresCollection" class="mb-2">
      <div class="mb-2 position-relative">
        <label class="form-label">Coleção</label>
        <div class="input-group">
          <input
            v-model="collectionSearchQuery"
            type="text"
            class="form-control"
            placeholder="Digite para buscar uma coleção..."
            autocomplete="off"
            :disabled="disabled"
            @focus="onSearchFocus"
            @blur="onSearchBlur"
            @input="onSearchInput"
          />
          <button
            v-if="selectedCollectionId"
            type="button"
            class="btn btn-outline-secondary"
            :disabled="disabled"
            title="Limpar seleção"
            @mousedown.prevent="clearCollectionSelection"
          >
            ×
          </button>
        </div>

        <div
          v-show="showSearchResults"
          class="list-group position-absolute w-100 shadow overflow-auto"
          style="z-index: 1050; max-height: 220px;"
        >
          <template v-if="loadingCollections">
            <div class="list-group-item list-group-item-secondary">Buscando...</div>
          </template>
          <template v-else-if="collectionError">
            <div class="list-group-item list-group-item-danger">{{ collectionError }}</div>
          </template>
          <template v-else-if="collectionSearchQuery.length < 1 && searchResults.length === 0">
            <div class="list-group-item list-group-item-secondary">Digite para buscar coleções</div>
          </template>
          <template v-else-if="searchResults.length === 0">
            <div class="list-group-item list-group-item-secondary">Nenhuma coleção encontrada</div>
          </template>
          <button
            v-for="item in searchResults"
            v-else
            :key="item.id"
            type="button"
            class="list-group-item list-group-item-action text-start collection-search-item"
            :class="{ active: selectedCollectionId === item.id }"
            @mousedown.prevent="selectCollection(item)"
          >
            {{ item.name }}
            <span v-if="item.parent?.name" class="text-muted small ms-1">({{ item.parent.name }})</span>
          </button>
        </div>
      </div>

      <div v-if="selectedCollectionId" class="mt-2">
        <div v-if="loadingImages" class="text-muted small">Carregando artes...</div>
        <div v-else-if="imagesError" class="text-danger small">{{ imagesError }}</div>
        <div v-else-if="!images.length" class="text-muted small">Nenhuma arte disponível.</div>
        <div v-else class="row row-cols-3 row-cols-sm-4 row-cols-md-5 row-cols-lg-6 g-2">
          <div
            v-for="image in images"
            :key="image.id"
            class="col show-collection-images"
          >
            <div
              role="button"
              tabindex="0"
              class="card h-100 w-100 p-0 text-start border overflow-hidden"
              :class="[
                wall.collection_referring_model == image.id ? 'border-success border-2' : '',
                disabled ? 'opacity-75 pe-none' : ''
              ]"
              :style="{ cursor: disabled ? 'not-allowed' : 'pointer' }"
              :aria-disabled="disabled"
              @click="!disabled && (wall.collection_referring_model = String(image.id))"
              @keydown.enter.prevent="!disabled && (wall.collection_referring_model = String(image.id))"
            >
              <div class="ratio ratio-1x1">
                <img
                  :src="getImageUrl(image)"
                  :alt="image.name || image.title"
                  class="card-img-top object-fit-cover"
                  loading="lazy"
                />
              </div>
              <div class="card-body p-2">
                <span class="small d-block" :title="image.name || image.title || `Arte ${image.id}`">
                  {{ image.name || image.title || `Arte ${image.id}` }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import debounce from 'lodash/debounce';
import { useBudgetOrderService } from '@/modules/budgets/services/budgetOrderService';
import { useBudgetService } from '@/modules/budgets/services/budgetService';

const props = defineProps({
  wall: { type: Object, required: true },
  model: { type: Object, default: null },
  disabled: { type: Boolean, default: false },
});

const budgetOrderService = useBudgetOrderService();
const budgetService = useBudgetService();
const fileInputRef = ref(null);
const uploadingFile = ref(false);
const collectionSearchQuery = ref('');
const searchResults = ref([]);
const selectedCollectionId = ref(null);
const selectedCollectionName = ref('');
const showSearchResults = ref(false);
const images = ref([]);
const loadingCollections = ref(false);
const loadingImages = ref(false);
const collectionError = ref('');
const imagesError = ref('');

const requiresComment = computed(() => Boolean(props.model?.requests?.comment));
const requiresLink = computed(() => Boolean(props.model?.requests?.link));
const requiresFiles = computed(() => Boolean(props.model?.requests?.file));
const requiresCollection = computed(() => Boolean(props.model?.requests?.collection));

const hasRequirements = computed(
  () => requiresComment.value || requiresLink.value || requiresFiles.value || requiresCollection.value
);

const uploadedImagePath = computed(() => {
  const files = Array.isArray(props.wall?.files_referring_model) ? props.wall.files_referring_model : [];
  return files[0] ?? null;
});

watch(
  () => props.wall,
  (wall) => {
    if (wall) {
      if (!Array.isArray(wall.files_referring_model)) wall.files_referring_model = [];
      if (wall.comment_referring_model == null) wall.comment_referring_model = '';
      if (wall.link_referring_model == null) wall.link_referring_model = '';
      if (wall.collection_referring_model == null) wall.collection_referring_model = '';
    }
  },
  { immediate: true, deep: false }
);

watch(
  () => props.model?.id,
  () => {
    if (!requiresCollection.value) {
      selectedCollectionId.value = null;
      selectedCollectionName.value = '';
      collectionSearchQuery.value = '';
      searchResults.value = [];
      images.value = [];
    }
  },
  { immediate: true }
);

const DEFAULT_COVER = '/assets/img/no-image.jpg';

function getImageUrl(image) {
  const url = image?.url ?? image?.path_name ?? image?.pathName;
  if (url && (url.startsWith('/') || /^https?:\/\//i.test(url))) return url;
  if (url) return `${window.location.origin.replace(/\/$/, '')}/storage/${String(url).replace(/^storage\//, '')}`;
  return DEFAULT_COVER;
}

function resolveStorageUrl(path) {
  if (!path) return '/assets/img/no-image.jpg';
  if (path.startsWith('/') || /^https?:\/\//i.test(path)) return path;
  const base = window.location.origin.replace(/\/$/, '');
  return `${base}/storage/${String(path).replace(/^storage\//, '')}`;
}

function handleImageError(event) {
  event.target.style.display = 'none';
}

async function handleFileChange(event) {
  const input = event.target;
  const file = input?.files?.[0];
  if (!file) return;

  uploadingFile.value = true;
  try {
    const path = await budgetService.uploadReferringFile(file);
    if (path) {
      props.wall.files_referring_model = [path];
    }
  } catch (err) {
    console.error('Erro ao enviar imagem:', err);
    if (window.Toast) {
      window.Toast.fire({
        icon: 'error',
        title: err?.response?.data?.message || 'Não foi possível enviar a imagem.',
      });
    }
  } finally {
    uploadingFile.value = false;
    input.value = '';
  }
}

function removeUploadedImage() {
  props.wall.files_referring_model = [];
  if (fileInputRef.value) {
    fileInputRef.value.value = '';
  }
}

async function runSearch() {
  const term = collectionSearchQuery.value.trim();
  if (!term) {
    searchResults.value = [];
    collectionError.value = '';
    return;
  }
  loadingCollections.value = true;
  collectionError.value = '';
  try {
    searchResults.value = await budgetOrderService.searchCollectionCategories(term);
  } catch (error) {
    collectionError.value = error?.response?.data?.message || 'Erro ao buscar coleções.';
    searchResults.value = [];
  } finally {
    loadingCollections.value = false;
  }
}

const performSearch = debounce(runSearch, 300);

function onSearchFocus() {
  showSearchResults.value = true;
  const term = collectionSearchQuery.value.trim();
  if (term) {
    performSearch.cancel();
    runSearch();
  }
}

function onSearchInput() {
  if (
    selectedCollectionId.value &&
    collectionSearchQuery.value.trim() !== selectedCollectionName.value
  ) {
    selectedCollectionId.value = null;
    selectedCollectionName.value = '';
    images.value = [];
  }
  performSearch();
}

function onSearchBlur() {
  setTimeout(() => {
    showSearchResults.value = false;
  }, 200);
}

function selectCollection(item) {
  selectedCollectionId.value = item.id;
  selectedCollectionName.value = item.name || '';
  collectionSearchQuery.value = item.name || '';
  showSearchResults.value = false;
  searchResults.value = [];
  loadImagesForCollection(item.id);
}

function clearCollectionSelection() {
  selectedCollectionId.value = null;
  selectedCollectionName.value = '';
  collectionSearchQuery.value = '';
  images.value = [];
  imagesError.value = '';
}

async function loadImagesForCollection(categoryId) {
  images.value = [];
  imagesError.value = '';
  if (!categoryId) return;
  loadingImages.value = true;
  try {
    images.value = await budgetOrderService.getCollectionCategoryImages(categoryId);
  } catch (error) {
    imagesError.value = error?.response?.data?.message || 'Erro ao carregar artes da coleção.';
  } finally {
    loadingImages.value = false;
  }
}
</script>

<style scoped>
.collection-search-item:hover {
  background-color: var(--bs-tertiary-bg, var(--bs-secondary-bg, #e9ecef)) !important;
}

.show-collection-images {
  width: 167px;
}
</style>
