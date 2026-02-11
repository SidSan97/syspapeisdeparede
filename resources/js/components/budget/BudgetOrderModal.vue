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
                <div v-if="orderSummary" class="border rounded p-3 bg-body-secondary mt-3">
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
                <template v-for="wall in wallsWithRequirements" :key="wall.key">
                  <div class="border rounded p-3 mb-3">
                    <div class="mb-3">
                      <div class="fw-semibold">{{ wall.roomName }}</div>
                      <div class="text-muted small">{{ wall.wallName }}</div>
                      <div class="text-muted small">Modelo: {{ wall.collectionModel?.name ?? 'N/A' }}</div>
                    </div>

                    <div v-if="wall.requiresComment" class="mb-3">
                      <label :for="`orderComment-${wall.key}`" class="form-label">
                        Descrição do modelo
                      </label>
                      <textarea
                        :id="`orderComment-${wall.key}`"
                        v-model.trim="wallForms[wall.key].comment"
                        class="form-control"
                        rows="3"
                        maxlength="500"
                        placeholder="Descreva o que deve ser produzido com base neste orçamento"
                        :disabled="orderSubmitting"
                      ></textarea>
                      <small class="text-muted">Máximo de 500 caracteres.</small>
                    </div>

                    <div v-if="wall.requiresFiles" class="mb-3">
                      <label :for="`orderFiles-${wall.key}`" class="form-label">
                        Uploads de referência
                      </label>
                      <input
                        :id="`orderFiles-${wall.key}`"
                        :ref="(el) => { if (el) orderFileInputs[wall.key] = el }"
                        class="form-control"
                        type="file"
                        accept="image/*"
                        multiple
                        :disabled="orderSubmitting"
                        @change="(e) => handleOrderFilesChange(wall.key, e)"
                      >
                      <small class="text-muted">Envie imagens em formatos JPG, PNG ou WEBP (máx. 5MB cada).</small>

                      <div v-if="wall.existingFiles && wall.existingFiles.length" class="mt-2">
                        <div class="text-muted small mb-1">Arquivos enviados anteriormente</div>
                        <ul class="list-unstyled small mb-0">
                          <li v-for="(file, index) in wall.existingFiles" :key="`existing-file-${wall.key}-${index}`">
                            <a :href="resolveStorageUrl(file)" target="_blank" rel="noopener">
                              {{ extractFileName(file) }}
                            </a>
                          </li>
                        </ul>
                      </div>

                      <div v-if="getWallNewFiles(wall.key).length" class="mt-2">
                        <div class="text-muted small mb-1">Arquivos selecionados</div>
                        <ul class="list-unstyled small mb-0">
                          <li
                            v-for="(file, index) in getWallNewFiles(wall.key)"
                            :key="`new-file-${wall.key}-${index}`"
                            class="d-flex align-items-center gap-2"
                          >
                            <span>{{ file.name }}</span>
                            <button
                              class="btn btn-link btn-sm text-danger p-0"
                              type="button"
                              :disabled="orderSubmitting"
                              @click="removeNewFile(wall.key, index)"
                            >
                              Remover
                            </button>
                          </li>
                        </ul>
                      </div>
                    </div>

                    <div v-if="wall.requiresLink" class="mb-3">
                      <label :for="`orderLink-${wall.key}`" class="form-label">
                        Link de referência
                      </label>
                      <input
                        :id="`orderLink-${wall.key}`"
                        v-model.trim="wallForms[wall.key].link"
                        type="url"
                        class="form-control"
                        placeholder="https://exemplo.com/referencia"
                        :disabled="orderSubmitting"
                      >
                    </div>
                  </div>
                </template>

                <div v-if="requiresCollection" class="mb-4">
                  <h6 class="fw-semibold mb-3">Selecione uma arte da coleção para a parede</h6>
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
                    <template
                      v-for="wall in wallsWithRequirements.filter((w) => w.requiresCollection)"
                      :key="wall.key"
                    >
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
                          <div v-else>
                            <label :for="`search-art-${wall.key}`" class="form-label small">
                              Buscar arte pelo nome
                            </label>
                            <input
                              :id="`search-art-${wall.key}`"
                              type="text"
                              class="form-control form-control-sm mb-2"
                              placeholder="Digite para filtrar..."
                              :value="wallSearchTerms[wall.key] ?? ''"
                              :disabled="orderSubmitting"
                              @input="setWallSearchTerm(wall.key, $event.target.value)"
                            >
                            <div
                              v-if="!getFilteredCollectionItems(wall.key).length"
                              class="text-muted small"
                            >
                              Nenhuma arte encontrada para essa busca.
                            </div>
                            <div v-else class="collection-images-grid">
                              <button
                                v-for="image in getFilteredCollectionItems(wall.key)"
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
                                  :alt="image.name ?? image.title"
                                  class="collection-image-thumb"
                                  @error="handleCollectionImageError"
                                >
                                <span class="collection-image-name text-truncate d-block w-100">
                                  {{ image.name ?? image.title }}
                                </span>
                              </button>
                            </div>
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
// Swal importado via window.Swal do plugin

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

