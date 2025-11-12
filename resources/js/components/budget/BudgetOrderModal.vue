<template>
  <Teleport v-if="visible" to="body">
    <div>
      <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Fazer pedido</h5>
              <button type="button" class="btn-close" aria-label="Close" @click="handleClose"></button>
            </div>
            <div class="modal-body">
              <div v-if="orderSummary" class="border rounded p-3 bg-body-secondary">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="text-muted small">Orçamento</span>
                  <span class="badge bg-secondary">#{{ orderSummary.id }}</span>
                </div>
                <div class="fw-semibold">{{ orderSummary.name }}</div>
                <div class="text-muted small mt-2">
                  Valor total: <span class="fw-semibold">{{ orderSummary.formattedTotal }}</span>
                </div>
                <div class="text-muted small">
                  Prazo de entrega: {{ orderSummary.deliveryTime }}
                </div>
                <div v-if="orderSummary.status" class="text-muted small">
                  Status atual: {{ orderSummary.status }}
                </div>
              </div>

              <div v-if="orderCollectionModels.length" class="mt-3">
                <div class="text-muted small mb-1">Modelos associados</div>
                <div class="fw-semibold">
                  {{ orderCollectionModels.map((item) => item.name).join(', ') }}
                </div>
              </div>

              <div class="mt-4">
                <div v-if="requiresComment" class="mb-3">
                  <label for="orderComment" class="form-label">Descrição do modelo</label>
                  <textarea
                    id="orderComment"
                    v-model.trim="orderForm.comment"
                    class="form-control"
                    rows="3"
                    maxlength="500"
                    placeholder="Descreva o que deve ser produzido com base neste orçamento"
                    :disabled="orderSubmitting"
                  ></textarea>
                  <small class="text-muted">Máximo de 500 caracteres.</small>
                </div>

                <div v-if="requiresFiles" class="mb-3">
                  <label for="orderFiles" class="form-label">Uploads de referência</label>
                  <input
                    id="orderFiles"
                    ref="orderFileInput"
                    class="form-control"
                    type="file"
                    accept="image/*"
                    multiple
                    :disabled="orderSubmitting"
                    @change="handleOrderFilesChange"
                  >
                  <small class="text-muted">Envie imagens em formatos JPG, PNG ou WEBP (máx. 5MB cada).</small>

                  <div v-if="orderExistingFiles.length" class="mt-2">
                    <div class="text-muted small mb-1">Arquivos enviados anteriormente</div>
                    <ul class="list-unstyled small mb-0">
                      <li v-for="(file, index) in orderExistingFiles" :key="`existing-file-${index}`">
                        <a :href="resolveStorageUrl(file)" target="_blank" rel="noopener">
                          {{ extractFileName(file) }}
                        </a>
                      </li>
                    </ul>
                  </div>

                  <div v-if="orderNewFiles.length" class="mt-2">
                    <div class="text-muted small mb-1">Arquivos selecionados</div>
                    <ul class="list-unstyled small mb-0">
                      <li
                        v-for="(file, index) in orderNewFiles"
                        :key="`new-file-${index}`"
                        class="d-flex align-items-center gap-2"
                      >
                        <span>{{ file.name }}</span>
                        <button
                          class="btn btn-link btn-sm text-danger p-0"
                          type="button"
                          :disabled="orderSubmitting"
                          @click="removeNewFile(index)"
                        >
                          Remover
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>

                <div v-if="requiresLink" class="mb-3">
                  <label for="orderLink" class="form-label">Link de referência</label>
                  <input
                    id="orderLink"
                    v-model.trim="orderForm.link"
                    type="url"
                    class="form-control"
                    placeholder="https://exemplo.com/referencia"
                    :disabled="orderSubmitting"
                  >
                </div>

                <div v-if="requiresCollection" class="mb-4">
                  <h6 class="fw-semibold mb-3">Selecione uma arte da coleção para cada parede</h6>
                  <div v-if="collectionLoading" class="alert alert-warning mb-0">
                    Carregando coleções disponíveis...
                  </div>
                  <div v-else-if="collectionError" class="alert alert-danger mb-0">
                    {{ collectionError }}
                  </div>
                  <div v-else-if="!collectionList.length" class="alert alert-info mb-0">
                    Nenhuma coleção disponível. Entre em contato com o suporte para prosseguir.
                  </div>
                  <div v-else class="d-flex flex-column gap-3">
                    <template v-for="wall in wallsRequiringCollection" :key="wall.key">
                      <div v-if="wallSelections[wall.key]" class="collection-selection">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                          <div>
                            <div class="fw-semibold">{{ wall.roomName }}</div>
                            <div class="text-muted small">{{ wall.wallName }}</div>
                          </div>
                          <div class="w-100 w-md-50">
                            <label :for="`collection-select-${wall.key}`" class="form-label">
                              Coleção
                            </label>
                            <select
                              :id="`collection-select-${wall.key}`"
                              class="form-select"
                              v-model="wallSelections[wall.key].collectionId"
                              :disabled="orderSubmitting || collectionLoading"
                              @change="handleCollectionSelectionChange(wall.key)"
                            >
                              <option :value="null">Selecione uma coleção</option>
                              <option
                                v-for="collection in collectionList"
                                :key="collection.id"
                                :value="collection.id"
                              >
                                {{ collection.name }}
                              </option>
                            </select>
                          </div>
                        </div>

                        <div v-if="wallSelections[wall.key].collectionId">
                          <div
                            v-if="
                              getCollectionState(wallSelections[wall.key].collectionId).loading
                            "
                            class="text-muted small"
                          >
                            Carregando imagens...
                          </div>
                          <div
                            v-else-if="
                              getCollectionState(wallSelections[wall.key].collectionId).error
                            "
                            class="text-danger small"
                          >
                            {{ getCollectionState(wallSelections[wall.key].collectionId).error }}
                          </div>
                          <div
                            v-else-if="
                              !getCollectionState(wallSelections[wall.key].collectionId).items.length
                            "
                            class="text-muted small"
                          >
                            Nenhuma imagem disponível nesta coleção.
                          </div>
                          <div v-else class="collection-images-grid">
                            <button
                              v-for="image in getCollectionState(wallSelections[wall.key].collectionId).items"
                              :key="image.id ?? `image-${wall.key}`"
                              type="button"
                              class="collection-image-button"
                              :class="{
                                selected: wallSelections[wall.key].imageId === image.id,
                              }"
                              @click="selectCollectionImage(wall.key, image.id)"
                              :disabled="orderSubmitting"
                            >
                              <img
                                :src="image.url"
                                :alt="image.title"
                                class="collection-image-thumb"
                                @error="handleCollectionImageError"
                              >

                            </button>
                          </div>
                        </div>
                        <div v-else class="text-muted small">
                          Escolha uma coleção para visualizar as artes disponíveis.
                        </div>
                      </div>
                    </template>
                  </div>
                </div>

                <div v-if="!orderHasRequirements" class="alert alert-info mb-0">
                  Nenhuma informação adicional é necessária para este orçamento. Confirme para continuar com o pedido.
                </div>
              </div>

              <div class="form-check mt-4">
                <input
                  id="orderTerms"
                  v-model="orderForm.termsAccepted"
                  class="form-check-input"
                  type="checkbox"
                  :disabled="orderSubmitting"
                >
                <label class="form-check-label" for="orderTerms">
                  Estou de acordo com os termos do pedido.
                </label>
              </div>

              <p v-if="orderError" class="text-danger small mt-3 mb-0">
                {{ orderError }}
              </p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" :disabled="orderSubmitting" @click="handleClose">
                Cancelar
              </button>
              <button
                type="button"
                class="btn btn-success"
                :disabled="orderSubmitting || !orderForm.termsAccepted"
                @click="submitOrder"
              >
                <span
                  v-if="orderSubmitting"
                  class="spinner-border spinner-border-sm me-2"
                  role="status"
                  aria-hidden="true"
                ></span>
                Confirmar pedido
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-backdrop fade show"></div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  budget: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'updated']);

const orderForm = reactive({
  comment: '',
  link: '',
  files: [],
  termsAccepted: false,
});
const orderExistingFiles = ref([]);
const orderSubmitting = ref(false);
const orderError = ref('');
const orderFileInput = ref(null);

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL',
});

watch(
  () => props.visible,
  (visible) => {
    if (visible) {
      initializeForm();
    } else {
      resetForm();
    }
  },
);

watch(
  () => props.budget,
  () => {
    if (props.visible) {
      initializeForm();
    }
  },
  { deep: true },
);

const orderSummary = computed(() => {
  const budget = props.budget;

  if (!budget) {
    return null;
  }

  const rawTotal = Number(budget.total_amount ?? budget.totalAmount ?? 0);
  const total = Number.isFinite(rawTotal) ? rawTotal : 0;

  return {
    id: budget.id,
    name: budget.name ?? 'Não informado',
    formattedTotal: formatCurrency(total),
    deliveryTime: formatDeliveryTime(budget.delivery_time),
    status: budget.status ?? null,
  };
});

const orderCollectionModels = computed(() => {
  if (!props.budget) {
    return [];
  }

  return extractCollectionModelsFromBudget(props.budget);
});

const requiresComment = computed(() => hasRequirement('request_comment'));
const requiresFiles = computed(() => hasRequirement('request_file'));
const requiresLink = computed(() => hasRequirement('request_link'));
const requiresCollection = computed(() => hasRequirement('request_collection'));
const orderHasRequirements = computed(
  () =>
    requiresComment.value ||
    requiresFiles.value ||
    requiresLink.value ||
    requiresCollection.value,
);
const orderNewFiles = computed(() => (Array.isArray(orderForm.files) ? orderForm.files : []));

const wallSelections = reactive({});
const collectionList = ref([]);
const collectionAssets = reactive({});
const collectionLoading = ref(false);
const collectionError = ref('');
const COLLECTION_IMAGE_PLACEHOLDER =
  'https://via.placeholder.com/300x200/ced4da/212529?text=Sem+imagem';

const wallsRequiringCollection = computed(() => {
  if (!props.budget || !Array.isArray(props.budget.rooms)) {
    return [];
  }

  const walls = [];

  props.budget.rooms.forEach((room, roomIndex) => {
    const roomName = room?.name ?? `Ambiente ${roomIndex + 1}`;
    const roomId = room?.id ?? `room-${roomIndex}`;

    (room?.walls ?? []).forEach((wall, wallIndex) => {
      const collectionModel =
        wall?.collection_model ??
        wall?.collectionModel ??
        null;

      if (!collectionModel) {
        return;
      }

      const requires =
        collectionModel?.request_collection ??
        collectionModel?.requestCollection ??
        collectionModel?.requests?.collection ??
        false;

      if (!requires) {
        return;
      }

      const wallName = wall?.name ?? `Parede ${wallIndex + 1}`;
      const wallId = wall?.id ?? `wall-${wallIndex}`;

      walls.push({
        key: `${roomId}-${wallId}`,
        roomName,
        wallName,
      });
    });
  });

  return walls;
});

function syncWallSelections() {
  const requiredKeys = new Set(wallsRequiringCollection.value.map((wall) => wall.key));

  requiredKeys.forEach((key) => {
    if (!wallSelections[key]) {
      wallSelections[key] = {
        collectionId: null,
        imageId: null,
      };
    }
  });

  Object.keys(wallSelections).forEach((key) => {
    if (!requiredKeys.has(key)) {
      delete wallSelections[key];
    }
  });
}

async function ensureCollectionsLoaded() {
  if (collectionLoading.value || collectionList.value.length) {
    return;
  }

  collectionLoading.value = true;
  collectionError.value = '';

  try {
    const { data } = await axios.get('v1/collection-arts', {
      params: { per_page: 100 },
    });

    const payload = data?.data ?? data ?? {};
    const items = payload.items ?? payload ?? [];

    collectionList.value = Array.isArray(items)
      ? items.map(normalizeCollectionSummary).filter((item) => item.id !== null)
      : [];
  } catch (error) {
    collectionList.value = [];
    collectionError.value =
      'Não foi possível carregar as coleções. Atualize a página e tente novamente.';
  } finally {
    collectionLoading.value = false;
  }
}

function normalizeCollectionSummary(item = {}) {
  const rawId = item.id ?? item.collection_art_id ?? null;
  const numericId = rawId === null ? null : Number(rawId);
  const finalId = Number.isNaN(numericId) ? null : numericId;
  const name = (item.name ?? '').toString().trim();

  return {
    id: finalId,
    name: name.length ? name : 'Coleção sem nome',
  };
}

function getCollectionState(collectionId) {
  if (!collectionId) {
    return {
      loading: false,
      items: [],
      error: '',
    };
  }

  if (!collectionAssets[collectionId]) {
    collectionAssets[collectionId] = {
      loading: false,
      items: [],
      error: '',
    };
  }

  return collectionAssets[collectionId];
}

async function ensureCollectionAssets(collectionId) {
  if (!collectionId) {
    return;
  }

  const state = getCollectionState(collectionId);

  if (state.loading || state.items.length || state.error) {
    return;
  }

  state.loading = true;
  state.error = '';

  try {
    const { data } = await axios.get(`v1/collection-arts/${collectionId}`);
    const payload = data?.data ?? data ?? {};
    const images = Array.isArray(payload.images)
      ? payload.images.map(normalizeCollectionImage)
      : [];

    state.items = images;
  } catch (error) {
    state.error = 'Não foi possível carregar as imagens desta coleção.';
  } finally {
    state.loading = false;
  }
}

function normalizeCollectionImage(image = {}) {
  const resolvedUrl =
    image.url ??
    resolveStorageUrl(image.path_name ?? image.pathName ?? '') ??
    COLLECTION_IMAGE_PLACEHOLDER;

  const rawId =
    image.id ??
    image.collection_image_id ??
    image.collectionImageId ??
    image.collection_art_id ??
    image.collectionArtId ??
    null;

  const numericId = rawId === null ? null : Number(rawId);
  const finalId = Number.isNaN(numericId) ? null : numericId;

  const finalUrl = !resolvedUrl || resolvedUrl === '#' ? COLLECTION_IMAGE_PLACEHOLDER : resolvedUrl;

  return {
    id: finalId,
    url: finalUrl,
    title: image.path_name ?? image.pathName ?? `Imagem #${image.id ?? ''}`,
  };
}

function handleCollectionSelectionChange(wallKey) {
  const selection = wallSelections[wallKey];

  if (!selection) {
    return;
  }

  selection.imageId = null;

  if (selection.collectionId) {
    ensureCollectionAssets(selection.collectionId);
  }
}

function selectCollectionImage(wallKey, imageId) {
  const selection = wallSelections[wallKey];

  if (!selection) {
    return;
  }

  selection.imageId = imageId;
}

function handleCollectionImageError(event) {
  event.target.src = COLLECTION_IMAGE_PLACEHOLDER;
}

watch(
  [requiresCollection, () => props.visible],
  ([shouldLoad, visible]) => {
    if (shouldLoad && visible) {
      syncWallSelections();
      ensureCollectionsLoaded();
    }
  },
  { immediate: true },
);

watch(
  wallsRequiringCollection,
  () => {
    if (requiresCollection.value) {
      syncWallSelections();
    }
  },
  { deep: true },
);

function initializeForm() {
  const budget = props.budget ?? {};

  orderForm.comment = budget.comment_referring_model ?? budget.commentReferringModel ?? '';
  orderForm.link = budget.link_referring_model ?? budget.linkReferringModel ?? '';
  orderForm.files = [];
  orderForm.termsAccepted = false;
  orderExistingFiles.value = Array.isArray(budget.files_referring_model)
    ? budget.files_referring_model
    : Array.isArray(budget.filesReferringModel)
      ? budget.filesReferringModel
      : [];
  orderError.value = '';
  orderSubmitting.value = false;

  if (orderFileInput.value) {
    orderFileInput.value.value = '';
  }

  if (requiresCollection.value) {
    syncWallSelections();
    ensureCollectionsLoaded();
  }
}

function resetForm() {
  orderForm.comment = '';
  orderForm.link = '';
  orderForm.files = [];
  orderForm.termsAccepted = false;
  orderExistingFiles.value = [];
  orderError.value = '';
  orderSubmitting.value = false;

  if (orderFileInput.value) {
    orderFileInput.value.value = '';
  }

  Object.keys(wallSelections).forEach((key) => {
    delete wallSelections[key];
  });
}

function formatCurrency(value) {
  if (value === null || value === undefined) {
    return currencyFormatter.format(0);
  }

  const numericValue = Number(value);
  return currencyFormatter.format(Number.isFinite(numericValue) ? numericValue : 0);
}

function formatDeliveryTime(days) {
  if (!days) {
    return 'Não informado';
  }

  return `${days} ${days === 1 ? 'dia' : 'dias'}`;
}

function extractCollectionModelsFromBudget(budget) {
  if (!budget || !Array.isArray(budget.rooms)) {
    return [];
  }

  const collection = new Map();

  budget.rooms.forEach((room) => {
    const walls = Array.isArray(room?.walls) ? room.walls : [];

    walls.forEach((wall) => {
      const id =
        wall?.collection_model_id ??
        wall?.collectionModelId ??
        wall?.collection_model?.id ??
        null;

      if (id === null || id === undefined || id === '') {
        return;
      }

      if (!collection.has(id)) {
        collection.set(id, {
          id: String(id),
          name:
            wall?.collection_model_name ??
            wall?.collection_model?.model_type?.name ??
            wall?.collectionModelName ??
            `Modelo ${id}`,
        });
      }
    });
  });

  return Array.from(collection.values());
}

function hasRequirement(field) {
  const budget = props.budget;

  if (!budget || !Array.isArray(budget.rooms)) {
    return false;
  }

  return budget.rooms.some((room) => {
    const walls = Array.isArray(room?.walls) ? room.walls : [];

    return walls.some((wall) => {
      const collectionModel = wall?.collection_model ?? wall?.collectionModel ?? null;

      if (!collectionModel || !(field in collectionModel)) {
        return false;
      }

      const value = collectionModel[field];

      if (typeof value === 'boolean') {
        return value;
      }

      if (typeof value === 'string') {
        return value === 'true' || value === '1';
      }

      if (typeof value === 'number') {
        return value === 1 || value === 3;
      }

      return Boolean(value);
    });
  });
}

function handleClose() {
  if (orderSubmitting.value) {
    return;
  }

  emit('close');
}

function handleOrderFilesChange(event) {
  const files = event?.target?.files ? Array.from(event.target.files) : [];
  orderForm.files = files;

  if (event?.target) {
    event.target.value = '';
  }
}

function removeNewFile(index) {
  if (!Array.isArray(orderForm.files)) {
    return;
  }

  orderForm.files = orderForm.files.filter((_, fileIndex) => fileIndex !== index);
}

function resolveStorageUrl(path) {
  if (!path) {
    return '#';
  }

  if (/^https?:\/\//i.test(path)) {
    return path;
  }

  const baseUrl = window.location.origin.replace(/\/$/, '');

  return `${baseUrl}/storage/${String(path).replace(/^storage\//, '')}`;
}

function extractFileName(path) {
  if (!path) {
    return '';
  }

  const segments = String(path).split('/');
  return segments[segments.length - 1] ?? path;
}

async function submitOrder() {
  if (!props.budget?.id) {
    return;
  }

  if (!orderForm.termsAccepted) {
    orderError.value = 'É necessário aceitar os termos para continuar.';
    return;
  }

  if (requiresComment.value && !orderForm.comment.trim()) {
    orderError.value = 'Informe a descrição para prosseguir.';
    return;
  }

  if (requiresLink.value && !orderForm.link.trim()) {
    orderError.value = 'Informe o link de referência para prosseguir.';
    return;
  }

  if (requiresCollection.value) {
    const pendingWall = wallsRequiringCollection.value.find((wall) => {
      const selection = wallSelections[wall.key];
      return !selection?.collectionId || !selection?.imageId;
    });

    if (pendingWall) {
      orderError.value = `Selecione uma arte da coleção para ${pendingWall.roomName} - ${pendingWall.wallName}.`;
      return;
    }
  }

  if (
    requiresFiles.value &&
    !orderExistingFiles.value.length &&
    !orderNewFiles.value.length
  ) {
    orderError.value = 'Envie pelo menos um arquivo de referência para prosseguir.';
    return;
  }

  orderSubmitting.value = true;
  orderError.value = '';

  try {
    const formData = new FormData();
    formData.append('id', props.budget.id);

    if (requiresComment.value) {
      formData.append('comment_referring_model', orderForm.comment ?? '');
    }

    if (requiresLink.value) {
      formData.append('link_referring_model', orderForm.link ?? '');
    }

    if (requiresFiles.value && orderNewFiles.value.length) {
      orderNewFiles.value.forEach((file) => {
        formData.append('files_referring_model[]', file);
      });
    }

    if (requiresCollection.value) {
      const selectedImages = wallsRequiringCollection.value
        .map((wall) => wallSelections[wall.key]?.imageId)
        .filter((value) => value !== null && value !== undefined);

      if (selectedImages.length) {
        formData.append('collection_referring_model', selectedImages.join(','));
      }
    }

    formData.append('terms_accepted', orderForm.termsAccepted ? '1' : '0');

    const response = await axios.post('v1/budgets/place-order', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    if (!response.data?.data) {
      throw new Error('Resposta inválida do servidor.');
    }

    emit('updated', response.data.data);
    emit('close');

    Swal.fire({
      title: 'Pedido realizado',
      text: response.data?.message ?? 'Pedido registrado com sucesso.',
      icon: 'success',
      confirmButtonText: 'Entendi',
    });
  } catch (error) {
    const firstError = error.response?.data?.errors
      ? Object.values(error.response.data.errors).flat().shift()
      : null;

    orderError.value =
      firstError ??
      error.response?.data?.message ??
      error.message ??
      'Não foi possível realizar o pedido. Tente novamente.';

    Swal.fire({
      title: 'Não foi possível concluir o pedido',
      text: orderError.value,
      icon: 'error',
      confirmButtonText: 'Entendi',
    });
  } finally {
    orderSubmitting.value = false;
  }
}
</script>

<style scoped>
.collection-selection {
  border: 1px solid var(--bs-border-color);
  border-radius: 0.75rem;
  padding: 1rem;
  background-color: var(--bs-body-bg);
  box-shadow: 0 0.5rem 1.25rem rgba(15, 15, 15, 0.06);
}

.collection-images-grid {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
}

.collection-image-button {
  border: 1px solid var(--bs-border-color);
  border-radius: 0.5rem;
  padding: 0.5rem;
  background-color: var(--bs-body-bg);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  align-items: center;
}

.collection-image-button:hover,
.collection-image-button:focus {
  border-color: var(--bs-primary);
  box-shadow: 0 0.5rem 1rem rgba(13, 110, 253, 0.15);
}

.collection-image-button.selected {
  border-color: var(--bs-success);
  box-shadow: 0 0.5rem 1rem rgba(25, 135, 84, 0.2);
}

.collection-image-thumb {
  width: 100%;
  height: 100px;
  object-fit: cover;
  border-radius: 0.35rem;
}

.collection-image-name {
  font-size: 0.8rem;
  text-align: center;
  color: var(--bs-body-color);
}
</style>

