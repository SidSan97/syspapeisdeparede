<template>
  <Teleport to="body">
    <div
      ref="modalElement"
      class="modal fade"
      tabindex="-1"
      role="dialog"
      aria-labelledby="budgetOrderModalLabel"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="budgetOrderModalLabel">Fazer pedido</h5>
            <button type="button" class="btn-close" aria-label="Close" data-bs-dismiss="modal"></button>
          </div>
            <div class="modal-body">
              <template v-if="orderStep === 'review'">
                <div class="alert alert-info mb-0">
                  <p class="mb-0">
                    Revise se as medidas, quantidades, modelos, endereço e demais informações estão corretas antes de continuar.
                  </p>
                </div>
                <div class="review-scroll-container mt-3">
                  <div v-if="orderSummary" class="border rounded p-3 bg-body-secondary mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="text-muted small">Orçamento</span>
                      <span class="badge bg-secondary text-white">#{{ orderSummary.id }}</span>
                    </div>
                    <div class="fw-semibold">{{ orderSummary.name }}</div>
                    <div class="text-muted small mt-2">
                      Valor total: <span class="fw-semibold">{{ orderSummary.formattedTotal }}</span>
                    </div>
                    <div class="text-muted small">
                      Prazo de entrega: {{ orderSummary.deliveryTime }}
                    </div>
                  </div>

                  <ShowDropshippingInfoOrderBudget :budget="budget" />

                  <!-- Cômodos e medidas -->
                  <ShowRoomDetailsOrderBudget :budget="budget" />
                </div>
              </template>
              <template v-else>
              <div v-if="orderSummary" class="border rounded p-3 bg-body-secondary">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="text-muted small">Orçamento</span>
                  <span class="badge bg-secondary text-white">#{{ orderSummary.id }}</span>
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
                <ShowWallRequirimentsOrderBudget
                  :walls="wallsWithRequirements"
                  :wall-forms="wallForms"
                  :order-submitting="orderSubmitting"
                  :set-file-input-ref="setFileInputRef"
                  :handle-order-files-change="handleOrderFilesChange"
                  :remove-new-file="removeNewFile"
                  :get-wall-new-files="getWallNewFiles"
                  :resolve-storage-url="resolveStorageUrl"
                  :extract-file-name="extractFileName"
                />
                <ShowRequiresCollectionOrderBudget
                  :requires-collection="requiresCollection"
                  :walls="wallsWithRequirements"
                  :collection-loading="collectionLoading"
                  :collection-error="collectionError"
                  :collection-list="collectionList"
                  :wall-selections="wallSelections"
                  :wall-search-terms="wallSearchTerms"
                  :order-submitting="orderSubmitting"
                  :get-collection-state="getCollectionState"
                  :handle-collection-selection-change="handleCollectionSelectionChange"
                  :set-wall-search-term="setWallSearchTerm"
                  :get-filtered-collection-items="getFilteredCollectionItems"
                  :select-collection-image="selectCollectionImage"
                  :handle-collection-image-error="handleCollectionImageError"
                />
                

                <div v-if="!orderHasRequirements" class="alert alert-info mb-0">
                  Nenhuma informação adicional é necessária para este orçamento. Clique em Revisar pedido para continuar.
                </div>
              </div>

              </template>

              <p v-if="orderError" class="text-danger small mt-3 mb-0">
                {{ orderError }}
              </p>
            </div>
            <div class="modal-footer">
              <template v-if="orderStep === 'review'">
                <button type="button" class="btn btn-subtle" :disabled="orderSubmitting" @click="goBackToForm">
                  Voltar
                </button>
                <button
                  type="button"
                  class="btn btn-primary"
                  :disabled="orderSubmitting"
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
              </template>
              <template v-else>
                <button type="button" class="btn btn-subtle" :disabled="orderSubmitting" @click="handleClose">
                  Cancelar
                </button>
                <button
                  type="button"
                  class="btn btn-primary"
                  :disabled="orderSubmitting"
                  @click="goToReview"
                >
                  Revisar pedido
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>
  </Teleport>
</template>

<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useBudgetOrderComposable } from '@/modules/budgets/composables/budgetOrderComposable';
import ShowWallRequirimentsOrderBudget from '@/components/budget/ShowWallRequirimentsOrderBudget.vue';
import ShowRequiresCollectionOrderBudget from '@/components/budget/ShowRequiresCollectionOrderBudget.vue';
import ShowDropshippingInfoOrderBudget from '@/components/budget/ShowDropshippingInfoOrderBudget.vue';
import ShowRoomDetailsOrderBudget from '@/components/budget/ShowRoomDetailsOrderBudget.vue';

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

const router = useRouter();

// Usar o composable para gerenciar o estado e lógica do formulário
const budgetRef = computed(() => props.budget);
const {
  // Estado
  orderForm,
  wallForms,
  orderSubmitting,
  orderError,
  orderFileInputs,
  collectionList,
  collectionAssets,
  collectionLoading,
  collectionError,
  wallSelections,
  wallSearchTerms,
  // Computed
  orderSummary,
  orderCollectionModels,
  requiresComment,
  requiresFiles,
  requiresLink,
  requiresCollection,
  orderHasRequirements,
  wallsWithRequirements,
  wallsRequiringCollection,
  // Funções
  initializeForm,
  resetForm,
  handleOrderFilesChange,
  removeNewFile,
  getWallNewFiles,
  resolveStorageUrl,
  extractFileName,
  getCollectionState,
  getFilteredCollectionItems,
  setWallSearchTerm,
  handleCollectionSelectionChange,
  selectCollectionImage,
  handleCollectionImageError,
  validateFormRequirements,
  submitOrder: submitOrderFromComposable,
} = useBudgetOrderComposable(budgetRef);

const orderStep = ref('form');

function goToReview() {
  if (validateFormRequirements()) {
    orderForm.termsAccepted = true;
    orderStep.value = 'review';
    orderError.value = '';
  }
}

function goBackToForm() {
  orderStep.value = 'form';
  orderForm.termsAccepted = false;
}

function setFileInputRef(wallKey, el) {
  if (el) orderFileInputs[wallKey] = el;
}

function formatMeasure(value) {
  if (value == null || value === '') return '–';
  const n = Number(value);
  return Number.isFinite(n) ? n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : String(value);
}

// Estado específico do modal Bootstrap
const modalElement = ref(null);
let modalInstance = null;
let modalHiddenHandler = null;

watch(
  () => props.visible,
  (visible) => {
    if (visible) {
      initializeForm();
      orderStep.value = 'form';
      nextTick(() => {
        showModalInstance();
      });
    } else {
      hideModal();
      resetForm();
      orderStep.value = 'form';
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

function handleClose() {
  if (orderSubmitting.value) {
    return;
  }

  emit('close');
}

function initializeModal() {
  if (!modalElement.value || modalInstance) {
    return;
  }

  modalInstance = new window.bootstrap.Modal(modalElement.value, {
    backdrop: true,
    keyboard: true,
    focus: true,
  });

  // Escutar evento de fechamento do Bootstrap
  modalHiddenHandler = () => {
    handleClose();
  };
  modalElement.value.addEventListener('hidden.bs.modal', modalHiddenHandler);
}

function showModalInstance() {
  if (!modalInstance && modalElement.value) {
    initializeModal();
  }
  if (modalInstance) {
    modalInstance.show();
  }
}

function hideModal() {
  if (modalInstance) {
    modalInstance.hide();
  }
}

function disposeModal() {
  if (modalElement.value && modalHiddenHandler) {
    modalElement.value.removeEventListener('hidden.bs.modal', modalHiddenHandler);
    modalHiddenHandler = null;
  }
  if (modalInstance) {
    modalInstance.dispose();
    modalInstance = null;
  }
}

async function submitOrder() {
  try {
    const result = await submitOrderFromComposable();

    if (!result) {
      return;
    }

    const { orderId, payload } = result;

    emit('updated', payload);
    emit('close');

    router.push({ name: 'ShowOrderDetails', params: { id: orderId } });

    window.Swal.fire({
      title: 'Pedido realizado',
      text: 'Pedido registrado com sucesso.',
      confirmButtonText: 'Entendi!',
    });
  } catch (error) {
    window.Swal.fire({
      title: 'Não foi possível concluir o pedido',
      text: orderError.value,
      icon: 'error',
      confirmButtonText: 'Entendi',
    });
  }
}

onMounted(() => {
  if (props.visible) {
    nextTick(() => {
      initializeModal();
      showModalInstance();
    });
  }
});

onBeforeUnmount(() => {
  disposeModal();
});
</script>

<style scoped>
.review-scroll-container {
  max-height: 50vh;
  overflow-y: auto;
}
</style>

