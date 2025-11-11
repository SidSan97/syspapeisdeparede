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

              <div v-if="orderCollectionModelIds.length" class="mt-3">
                <div class="text-muted small mb-1">Modelos associados</div>
                <div class="fw-semibold">{{ orderCollectionModelIds.join(', ') }}</div>
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

const orderCollectionModelIds = computed(() => {
  if (!props.budget) {
    return [];
  }

  return extractCollectionModelIdsFromBudget(props.budget);
});

const requiresComment = computed(() => orderCollectionModelIds.value.includes('1'));
const requiresFiles = computed(() => orderCollectionModelIds.value.includes('2'));
const requiresLink = computed(() => orderCollectionModelIds.value.includes('3'));
const orderHasRequirements = computed(
  () => requiresComment.value || requiresFiles.value || requiresLink.value,
);
const orderNewFiles = computed(() => (Array.isArray(orderForm.files) ? orderForm.files : []));

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

function extractCollectionModelIdsFromBudget(budget) {
  if (!budget || !Array.isArray(budget.rooms)) {
    return [];
  }

  const ids = new Set();

  budget.rooms.forEach((room) => {
    const walls = Array.isArray(room?.walls) ? room.walls : [];

    walls.forEach((wall) => {
      const wallId = wall?.collection_model_id ?? wall?.collectionModelId ?? null;

      if (wallId !== null && wallId !== undefined && wallId !== '') {
        ids.add(String(wallId));
      }
    });
  });

  return Array.from(ids);
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

