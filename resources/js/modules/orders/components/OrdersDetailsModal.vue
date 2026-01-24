<template>
  <Teleport to="body">
    <div
      ref="modalElement"
      class="modal fade"
      tabindex="-1"
      role="dialog"
      aria-labelledby="ordersDetailsModalLabel"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="ordersDetailsModalLabel">Detalhes do Pedido</h5>
            <button
              type="button"
              class="btn-close"
              aria-label="Close"
              data-bs-dismiss="modal"
              :disabled="processing"
            ></button>
          </div>
          <div class="modal-body">
            <div v-if="details" class="pedido-details">
                <!-- Informações Principais -->
                <div class="border rounded p-3 bg-body-secondary mb-4">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <div class="text-muted small">Identificador</div>
                      <div class="badge bg-secondary fs-6">#{{ details.id }}</div>
                    </div>
                    <div class="col-md-6">
                      <div class="text-muted small">Status</div>
                      <div>
                        <span
                          class="fs-6"
                          :class="{
                            'bg-warning text-dark': details.status === 'Pendente de Revisão',
                            'bg-success': details.status === 'Aprovado',
                          }"
                        >
                          {{ details.status }}
                        </span>
                      </div>
                    </div>
                    <div class="col-12">
                      <div class="text-muted small">Nome do Pedido</div>
                      <div class="fw-semibold fs-5">{{ details.name }}</div>
                    </div>
                    <div class="col-md-6">
                      <div class="text-muted small">Prazo de Entrega</div>
                      <div class="fw-semibold">{{ details.deliveryTime }}</div>
                    </div>
                    <div class="col-md-6">
                      <div class="text-muted small">Metros</div>
                      <div class="fw-semibold">{{ details.totalArea }}</div>
                    </div>
                  </div>
                </div>

                <!-- Valores do Orçamento -->
                <div class="border rounded p-3 mb-4">
                  <h6 class="fw-semibold mb-3">Valores do Orçamento</h6>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <div class="text-muted small">Total à Vista</div>
                      <div class="fw-semibold fs-5 text-success">{{ details.totalVistaFormatted }}</div>
                    </div>
                    <div class="col-md-6">
                      <div class="text-muted small">Total a Prazo</div>
                      <div class="fw-semibold fs-5 text-primary">{{ details.totalPrazoFormatted }}</div>
                    </div>
                  </div>
                </div>

                <!-- Informações de Pagamento -->
                <div v-if="details.paymentMethod || paymentUrl" class="border rounded p-3 mb-4">
                  <h6 class="fw-semibold mb-3">Informações de Pagamento</h6>
                  <div class="row g-3">
                    <div v-if="details.paymentMethod" class="col-md-4">
                      <div class="text-muted small">Método de Pagamento</div>
                      <div class="fw-semibold">{{ formatPaymentMethod(details.paymentMethod) }}</div>
                    </div>
                    <div v-if="details.installments" class="col-md-4">
                      <div class="text-muted small">Parcelas</div>
                      <div class="fw-semibold">
                        {{ details.installments }} X
                      </div>
                    </div>
                    <div v-if="details.installmentLimit" class="col-md-4">
                      <div class="text-muted small">Valor das parcelas</div>
                      <div class="fw-semibold">
                        {{ formatInstallmentValue(details) }}
                      </div>
                    </div>
                    <div v-if="paymentUrl" class="col-12">
                      <div class="text-muted small mb-2">Link de Pagamento</div>
                      <div>
                        <a
                          :href="paymentUrl"
                          target="_blank"
                          rel="noopener noreferrer"
                          class="btn btn-primary btn-sm"
                        >
                          <i class="fa fa-external-link fa-fw me-2"></i>
                          Acessar Link de Pagamento
                        </a>
                        <button
                          type="button"
                          class="btn btn-outline-secondary btn-sm ms-2"
                          @click="copyPaymentUrl"
                          title="Copiar link"
                        >
                          <i class="fa fa-copy fa-fw"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Informações de Entrega -->
                <div v-if="details.carrierName" class="border rounded p-3 mb-4">
                  <h6 class="fw-semibold mb-3">Informações de Entrega</h6>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <div class="text-muted small">Transportadora</div>
                      <div class="fw-semibold">{{ details.carrierName }}</div>
                    </div>
                    <div v-if="details.carrierPrice" class="col-md-3">
                      <div class="text-muted small">Valor do Frete</div>
                      <div class="fw-semibold">{{ formatCurrency(details.carrierPrice) }}</div>
                    </div>
                    <div v-if="details.carrierDeliveryTime" class="col-md-3">
                      <div class="text-muted small">Prazo de Entrega</div>
                      <div class="fw-semibold">{{ formatDeliveryTime(details.carrierDeliveryTime) }}</div>
                    </div>
                    <div v-if="details.cep" class="col-md-12">
                      <div class="text-muted small">CEP</div>
                      <div class="fw-semibold">{{ details.cep }}</div>
                    </div>
                  </div>
                </div>

                <!-- Referências do Modelo -->
                <div
                  v-if="details.comment || details.link || details.files?.length"
                  class="border rounded p-3 mb-4"
                >
                  <h6 class="fw-semibold mb-3">Referências do Modelo</h6>
                  <div v-if="details.comment" class="mb-3">
                    <div class="text-muted small mb-1">Descrição</div>
                    <div class="p-2 rounded">{{ details.comment }}</div>
                  </div>
                  <div v-if="details.link" class="mb-3">
                    <div class="text-muted small mb-1">Link de Referência</div>
                    <div>
                      <a :href="details.link" target="_blank" rel="noopener noreferrer" class="text-break">
                        {{ details.link }}
                      </a>
                    </div>
                  </div>
                  <div v-if="details.files?.length" class="mb-0">
                    <div class="text-muted small mb-2">Arquivos de Referência</div>
                    <div class="d-flex flex-wrap gap-2">
                      <a
                        v-for="(file, index) in details.files"
                        :key="index"
                        :href="resolveStorageUrl(file)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-sm btn-outline-secondary"
                      >
                        <i class="fa fa-file-image me-1"></i>
                        {{ extractFileName(file) }}
                      </a>
                    </div>
                  </div>
                </div>

                <!-- Ambientes e Paredes -->
                <div v-if="details.rooms?.length" class="border rounded p-3 mb-4">
                  <h6 class="fw-semibold mb-3">Ambientes e Paredes</h6>
                  <div class="accordion" id="roomsAccordion">
                    <div
                      v-for="(room, roomIndex) in details.rooms"
                      :key="room.id ?? roomIndex"
                      class="accordion-item"
                    >
                      <h2 class="accordion-header">
                        <button
                          class="accordion-button"
                          :class="{ collapsed: roomIndex !== 0 }"
                          type="button"
                          data-bs-toggle="collapse"
                          :data-bs-target="`#room-${roomIndex}`"
                          :aria-expanded="roomIndex === 0"
                          :aria-controls="`room-${roomIndex}`"
                        >
                          <i class="fa fa-door-open fa-fw me-2"></i>
                          {{ room.name || `Ambiente ${roomIndex + 1}` }}
                        </button>
                      </h2>
                      <div
                        :id="`room-${roomIndex}`"
                        class="accordion-collapse collapse"
                        :class="{ show: roomIndex === 0 }"
                        data-bs-parent="#roomsAccordion"
                      >
                        <div class="accordion-body">
                          <div v-if="room.walls?.length" class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                              <thead class="table-light">
                                <tr>
                                  <th>Parede</th>
                                  <th class="text-center">Largura (m)</th>
                                  <th class="text-center">Altura (m)</th>
                                  <th class="text-center">Área (m²)</th>
                                  <th>Modelo</th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr v-for="(wall, wallIndex) in room.walls" :key="wall.id ?? wallIndex">
                                  <td>{{ wall.name || `Parede ${wallIndex + 1}` }}</td>
                                  <td class="text-center">{{ formatNumber(wall.width) }}</td>
                                  <td class="text-center">{{ formatNumber(wall.height) }}</td>
                                  <td class="text-center">{{ formatNumber(wall.total_area) }}</td>
                                  <td>
                                    <span class="badge bg-info">
                                      {{ wall.collection_model_name || 'N/A' }}
                                    </span>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                          <div v-else class="text-muted small">Nenhuma parede cadastrada</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Solicitação de Artes -->
                <div class="border rounded p-3 mb-4">
                  <h6 class="fw-semibold mb-3">
                    <i class="fa fa-paint-brush me-2"></i>
                    Solicitação de Artes
                  </h6>

                  <div v-if="loadingRequestArts" class="text-center text-muted py-3">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Carregando solicitações de artes...
                  </div>
                  <div v-else-if="requestLayoutArts.length === 0" class="text-center text-muted py-3">
                    Nenhuma solicitação de arte encontrada para este pedido.
                  </div>
                  <div v-else class="accordion" id="requestArtsAccordion">
                    <div
                      v-for="(art, artIndex) in requestLayoutArts"
                      :key="art.id || artIndex"
                      class="accordion-item mb-3"
                    >
                      <h2 class="accordion-header">
                        <button
                          class="accordion-button"
                          :class="{ collapsed: artIndex !== 0 }"
                          type="button"
                          data-bs-toggle="collapse"
                          :data-bs-target="`#art-${artIndex}`"
                          :aria-expanded="artIndex === 0"
                          :aria-controls="`art-${artIndex}`"
                        >
                          <i class="fa fa-image me-2"></i>
                          Arte #{{ art.id }}
                          <span v-if="art.wall_name" class="badge bg-info ms-2">
                            {{ art.wall_name }}
                          </span>
                        </button>
                      </h2>
                      <div
                        :id="`art-${artIndex}`"
                        class="accordion-collapse collapse"
                        :class="{ show: artIndex === 0 }"
                        data-bs-parent="#requestArtsAccordion"
                      >
                        <div class="accordion-body">
                          <div v-if="art.wall_info" class="mb-3 p-2 rounded border" style="background-color: var(--bs-secondary-bg);">
                            <div class="row g-2">
                              <div class="col-md-6">
                                <div class="text-muted small">Ambiente</div>
                                <div class="fw-semibold">{{ art.wall_info.room_name || 'N/A' }}</div>
                              </div>
                              <div class="col-md-6">
                                <div class="text-muted small">Parede</div>
                                <div class="fw-semibold">{{ art.wall_info.wall_name || 'N/A' }}</div>
                              </div>
                              <div v-if="art.wall_info.width" class="col-md-4">
                                <div class="text-muted small">Largura</div>
                                <div class="fw-semibold">{{ formatNumber(art.wall_info.width) }} m</div>
                              </div>
                              <div v-if="art.wall_info.height" class="col-md-4">
                                <div class="text-muted small">Altura</div>
                                <div class="fw-semibold">{{ formatNumber(art.wall_info.height) }} m</div>
                              </div>
                              <div v-if="art.wall_info.total_area" class="col-md-4">
                                <div class="text-muted small">Área</div>
                                <div class="fw-semibold">{{ formatNumber(art.wall_info.total_area) }} m²</div>
                              </div>
                            </div>
                          </div>
                          <!-- Informações do Autor -->
                          <div class="mb-3 p-2 border rounded" style="background-color: var(--bs-secondary-bg);">
                            <div class="row g-2">
                              <div v-if="art.dealer_name" class="col-md-6">
                                <div class="text-muted small">
                                  <i class="fa fa-user-tie me-1"></i>
                                  Revendedor
                                </div>
                                <div class="fw-semibold">{{ art.dealer_name }}</div>
                              </div>
                              <div v-if="art.designer_name" class="col-md-6">
                                <div class="text-muted small">
                                  <i class="fa fa-user me-1"></i>
                                  Designer
                                </div>
                                <div class="fw-semibold">{{ art.designer_name }}</div>
                              </div>
                              <div v-if="art.created_at" class="col-12">
                                <div class="text-muted small">
                                  <i class="fa fa-calendar me-1"></i>
                                  Enviado em: {{ formatDate(art.created_at) }}
                                </div>
                              </div>
                            </div>
                          </div>

                          <div v-if="art.comment" class="mb-3">
                            <div class="text-muted small mb-1">Comentário</div>
                            <div class="p-2 rounded border" style="background-color: var(--bs-secondary-bg); color: var(--bs-body-color);">{{ art.comment }}</div>
                          </div>
                          <div v-if="art.image_url" class="mb-3">
                            <div class="text-muted small mb-2">Imagem da Arte</div>
                            <div class="d-flex justify-content-center">
                              <img
                                :src="art.image_url"
                                :alt="`Arte ${art.id}`"
                                class="img-thumbnail"
                                style="max-width: 100%; max-height: 400px; object-fit: contain;"
                                @error="handleImageError"
                              />
                            </div>
                            <div class="mt-2 text-center">
                              <a
                                :href="art.image_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="btn btn-sm btn-outline-primary"
                              >
                                <i class="fa fa-external-link me-1"></i>
                                Abrir em nova aba
                              </a>
                            </div>
                          </div>

                          <!-- Formulário para adicionar comentário e arquivo -->
                          <div class="mt-4 p-3 border rounded" style="background-color: var(--bs-secondary-bg);">
                            <h6 class="fw-semibold mb-3" style="color: var(--bs-body-color);">
                              <i class="fa fa-plus-circle me-2"></i>
                              Adicionar Comentário/Arquivo
                            </h6>
                            <form @submit.prevent="handleSubmitArt(art)" enctype="multipart/form-data">
                              <div class="row g-3">
                                <div class="col-12">
                                  <label :for="`artComment-${art.id}`" class="form-label small text-muted">
                                    Comentário <span class="text-muted">(opcional)</span>
                                  </label>
                                  <textarea
                                    :id="`artComment-${art.id}`"
                                    v-model="artForms[art.id].comment"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Digite seu comentário sobre a arte..."
                                  ></textarea>
                                </div>
                                <div class="col-12">
                                  <label :for="`artFile-${art.id}`" class="form-label small text-muted">
                                    Imagem da Arte <span class="text-danger">*</span>
                                  </label>
                                  <input
                                    :id="`artFile-${art.id}`"
                                    :ref="el => setArtFileInput(art.id, el)"
                                    type="file"
                                    accept="image/*"
                                    class="form-control"
                                    @change="(e) => handleFileChange(e, art.id)"
                                    :disabled="artForms[art.id].uploading"
                                  />
                                  <div class="form-text">
                                    Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 10MB
                                  </div>
                                </div>
                                <div class="col-12">
                                  <button
                                    type="submit"
                                    class="btn btn-primary btn-sm"
                                    :disabled="artForms[art.id].uploading || !artForms[art.id].file"
                                  >
                                    <span
                                      v-if="artForms[art.id].uploading"
                                      class="spinner-border spinner-border-sm me-2"
                                      role="status"
                                      aria-hidden="true"
                                    ></span>
                                    <i v-else class="fa fa-upload me-2"></i>
                                    {{ artForms[art.id].uploading ? 'Enviando...' : 'Enviar' }}
                                  </button>
                                  <button
                                    v-if="artForms[art.id].comment || artForms[art.id].file"
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm ms-2"
                                    @click="resetArtForm(art.id)"
                                    :disabled="artForms[art.id].uploading"
                                  >
                                    <i class="fa fa-times me-2"></i>
                                    Limpar
                                  </button>
                                </div>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Datas -->
                <div class="border rounded p-3 mb-4">
                  <h6 class="fw-semibold mb-3">Informações adicionais</h6>
                  <div class="row g-3">
                    <div class="col-md-6" v-if="details.createdAt">
                      <div class="text-muted small">Criado em</div>
                      <div class="fw-semibold">{{ details.createdAt }}</div>
                    </div>
                    <div class="col-md-6" v-if="details.updatedAt">
                      <div class="text-muted small">Atualizado em</div>
                      <div class="fw-semibold">{{ details.updatedAt }}</div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center text-muted py-4">
                Não foi possível carregar os detalhes do pedido.
              </div>
            </div>
            <div class="modal-footer">
              <button
                type="button"
                class="btn btn-outline-secondary"
                @click="handleClose"
                :disabled="processing"
              >
                Fechar
              </button>
              <button
                v-if="details && details.status !== 'Aprovado'"
                type="button"
                class="btn btn-primary"
                @click="handleApprove"
                :disabled="processing"
              >
                <span
                  v-if="processing && actionType === 'approve'"
                  class="spinner-border spinner-border-sm me-2"
                  role="status"
                  aria-hidden="true"
                ></span>
                Aprovar
              </button>
            </div>
          </div>
        </div>
      </div>
  </Teleport>
</template>

<script setup>
import { computed, ref, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
// Swal importado via window.Swal do plugin
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
  pedido: {
    type: Object,
    default: null,
  },
  visible: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close', 'approve']);

const auth = useAuthStore();
const processing = ref(false);
const actionType = ref(null);
const paymentUrl = ref(null);
const requestLayoutArts = ref([]);
const loadingRequestArts = ref(false);
const artForms = ref({});
const artFileInputs = ref({});
const modalElement = ref(null);
let modalInstance = null;
let modalHiddenHandler = null;

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL',
});

function handleClose() {
  if (processing.value) {
    return;
  }
  paymentUrl.value = null;
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

function formatCurrency(value) {
  if (value === null || value === undefined) {
    return currencyFormatter.format(0);
  }

  const numericValue = Number(value);
  return currencyFormatter.format(Number.isFinite(numericValue) ? numericValue : 0);
}

function formatNumber(value) {
  if (value === null || value === undefined) {
    return '-';
  }

  const numericValue = Number(value);
  return Number.isFinite(numericValue) ? numericValue.toFixed(2) : value;
}

function formatDeliveryTime(days) {
  if (!days) {
    return 'Não informado';
  }

  return `${days} ${days === 1 ? 'dia' : 'dias'}`;
}

function formatPaymentMethod(method) {
  const methods = {
    //cash: 'Dinheiro',
    //card: 'Cartão',
    credit_card: 'Cartão de Crédito',
    //installment: 'Parcelado',
    pix: 'PIX',
  };

  return methods[method] || method;
}

function formatInstallmentValue(details) {
  if (!details.installments) {
    return '';
  }

  // Usar total a prazo se disponível, senão usar total geral
  const total = details.totalPrazo || details.total || 0;
  if (!total) {
    return '';
  }

  const value = total / details.installments;
  return formatCurrency(value);
}

function normalizeDate(value) {
  if (!value) {
    return null;
  }

  try {
    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) {
      return typeof value === 'string' ? value : null;
    }

    return date.toLocaleString('pt-BR');
  } catch (error) {
    return typeof value === 'string' ? value : null;
  }
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

function formatDate(date) {
  if (!date) {
    return '';
  }

  try {
    const parsedDate = date instanceof Date ? date : new Date(date);
    if (Number.isNaN(parsedDate.getTime())) {
      return typeof date === 'string' ? date : '';
    }

    return parsedDate.toLocaleString('pt-BR');
  } catch (error) {
    return typeof date === 'string' ? date : '';
  }
}

function resolveImageUrl(path) {
  if (!path) {
    return '';
  }

  if (/^https?:\/\//i.test(path)) {
    return path;
  }

  const baseUrl = window.location.origin.replace(/\/$/, '');
  return `${baseUrl}/storage/${String(path).replace(/^storage\//, '')}`;
}

function handleImageError(event) {
  event.target.style.display = 'none';
}

async function fetchRequestLayoutArts() {
  if (!props.pedido || !auth.user?.id) {
    requestLayoutArts.value = [];
    loadingRequestArts.value = false;
    return;
  }

  try {
    loadingRequestArts.value = true;

    const budgetId = props.pedido.id || props.pedido.budget_id || props.pedido.budgetId;
    // order_budget_id é opcional - se não tiver, buscará todas as artes do orçamento
    const orderBudgetId = props.pedido.order_budget_id || null;

    if (!budgetId) {
      requestLayoutArts.value = [];
      loadingRequestArts.value = false;
      return;
    }

    const params = {
      budget_id: budgetId,
      dealer_id: auth.user.id,
    };

    if (orderBudgetId) {
      params.order_budget_id = orderBudgetId;
    }

    const response = await axios.get('v1/budgets/request-layout-arts', {
      params,
    });

    const data = response?.data || response;

    if (data?.success && Array.isArray(data.data)) {
      requestLayoutArts.value = data.data.map((art) => {
        // Inicializar formulário para esta arte
        if (!artForms.value[art.id]) {
          artForms.value[art.id] = {
            comment: '',
            file: null,
            uploading: false,
          };
        }

        return {
          id: art.id,
          budget_id: art.budget_id || budgetId,
          order_budget_id: art.order_budget_id || orderBudgetId,
          dealer_id: art.dealer_id || auth.user.id,
          designer_id: art.designer_id || auth.user.id,
          comment: art.comment || null,
          image_url: art.image_url || (art.path_file ? resolveImageUrl(art.path_file) : null),
          created_at: art.created_at || art.createdAt || null,
          designer_name: art.designer?.name || art.designer_name || null,
          dealer_name: art.dealer?.name || art.dealer_name || null,
          wall_info: art.wall_info || null,
          wall_name: art.wall_info?.wall_name || null,
        };
      });
    } else {
      requestLayoutArts.value = [];
    }
  } catch (error) {
    console.error('Erro ao buscar solicitações de artes:', error);
    console.error('Detalhes do erro:', error.response?.data || error.message);
    requestLayoutArts.value = [];
  } finally {
    loadingRequestArts.value = false;
  }
}

function setArtFileInput(artId, el) {
  if (el) {
    artFileInputs.value[artId] = el;
  }
}

function handleFileChange(event, artId) {
  const file = event.target.files?.[0];
  if (file && artForms.value[artId]) {
    artForms.value[artId].file = file;
  }
}

function resetArtForm(artId) {
  if (artForms.value[artId]) {
    artForms.value[artId].comment = '';
    artForms.value[artId].file = null;
  }
  if (artFileInputs.value[artId]) {
    artFileInputs.value[artId].value = '';
  }
}

async function handleSubmitArt(art) {
  if (!art || !auth.user?.id) {
    await window.Swal.fire({
      title: 'Erro',
      text: 'Não foi possível enviar a arte. Dados insuficientes.',
      icon: 'error',
      confirmButtonText: 'OK',
    });
    return;
  }

  const artFormData = artForms.value[art.id];
  if (!artFormData) {
    await window.Swal.fire({
      title: 'Erro',
      text: 'Não foi possível encontrar o formulário da arte.',
      icon: 'error',
      confirmButtonText: 'OK',
    });
    return;
  }

  if (!artFormData.file) {
    await window.Swal.fire({
      title: 'Atenção',
      text: 'Por favor, selecione uma imagem para enviar.',
      icon: 'warning',
      confirmButtonText: 'OK',
    });
    return;
  }

  // Validar IDs necessários
  if (!art.budget_id || !art.order_budget_id) {
    await window.Swal.fire({
      title: 'Erro',
      text: 'IDs necessários não encontrados na arte.',
      icon: 'error',
      confirmButtonText: 'OK',
    });
    return;
  }

  try {
    artFormData.uploading = true;

    // Criar FormData para envio do arquivo
    // Reaproveitar os IDs da arte existente
    const formData = new FormData();
    formData.append('art_file', artFormData.file);
    formData.append('budget_id', art.budget_id);
    formData.append('order_budget_id', art.order_budget_id);
    formData.append('dealer_id', art.dealer_id || auth.user.id);
    formData.append('designer_id', art.designer_id || auth.user.id);

    if (artFormData.comment) {
      formData.append('comment', artFormData.comment);
    }

    const response = await axios.post('v1/budgets/order-budgets/upload-art', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    if (response.data?.success) {
      await window.Swal.fire({
        title: 'Sucesso',
        text: 'Arte enviada com sucesso!',
        confirmButtonText: 'Entendi!',
      });

      // Limpar formulário desta arte
      resetArtForm(art.id);

      // Recarregar lista de artes
      await fetchRequestLayoutArts();
    } else {
      throw new Error(response.data?.message || 'Erro ao enviar arte');
    }
  } catch (error) {
    console.error('Erro ao enviar arte:', error);
    const errorMessage = error?.response?.data?.message || error?.message || 'Não foi possível enviar a arte. Tente novamente.';

    await window.Swal.fire({
      title: 'Erro',
      text: errorMessage,
      icon: 'error',
      confirmButtonText: 'OK',
    });
  } finally {
    artFormData.uploading = false;
  }
}

async function handleApprove() {
  if (!props.pedido) {
    return;
  }

  const result = await window.Swal.fire({
    title: 'Aprovar pedido?',
    text: `Tem certeza que deseja aprovar o pedido "${props.pedido.name}"?`,
    showCancelButton: true,
    confirmButtonText: 'Sim, aprovar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#198754',
  });

  if (!result.isConfirmed) {
    return;
  }

  processing.value = true;
  actionType.value = 'approve';

  try {
    // Aprovar o orçamento
    const approveResponse = await axios.post('v1/budgets/approve', {
      id: props.pedido.id,
    });

    if (!approveResponse.data?.success) {
      throw new Error(approveResponse.data?.message || 'Erro ao aprovar orçamento');
    }

    // Obter link de pagamento da resposta
    const paymentLink = approveResponse.data?.payment_link;
    if (paymentLink) {
      // Tentar diferentes estruturas possíveis da URL
      paymentUrl.value = paymentLink.url
        || paymentLink.data?.url
        || paymentLink.data?.checkout_url
        || paymentLink.data?.public_url
        || null;

      if (!paymentUrl.value && paymentLink.data) {
        console.warn('URL de pagamento não encontrada na resposta:', paymentLink);
      }
    }

    emit('approve', props.pedido);

    await window.Swal.fire({
      title: 'Pedido aprovado',
      text: 'O pedido foi aprovado com sucesso. Acesse os DETALHES DO PEDIDO para acessar o link de pagamento.',
      confirmButtonText: 'Entendi!',
    });
  } catch (error) {
    const errorMessage = error?.response?.data?.message || error?.message || 'Não foi possível aprovar o pedido. Tente novamente.';

    await window.Swal.fire({
      title: 'Erro',
      text: errorMessage,
      icon: 'error',
      confirmButtonText: 'OK',
    });
  } finally {
    processing.value = false;
    actionType.value = null;
  }
}

function copyPaymentUrl() {
  if (!paymentUrl.value) {
    return;
  }

  navigator.clipboard.writeText(paymentUrl.value).then(() => {
    window.Swal.fire({
      title: 'Link copiado!',
      text: 'O link de pagamento foi copiado para a área de transferência.',
      timer: 2000,
      showConfirmButton: false,
    });
  }).catch(() => {
    window.Swal.fire({
      title: 'Erro',
      text: 'Não foi possível copiar o link.',
      icon: 'error',
      timer: 2000,
      showConfirmButton: false,
    });
  });
}

const details = computed(() => {
  const pedido = props.pedido;

  if (!pedido) {
    return null;
  }

  const totalRaw = Number(pedido.total_amount ?? pedido.totalAmount ?? 0);
  const total = Number.isFinite(totalRaw) ? totalRaw : 0;

  const totalVistaRaw = Number(pedido.total_amount ?? pedido.totalAmount ?? 0);
  const totalVista = Number.isFinite(totalVistaRaw) ? totalVistaRaw : 0;

  const totalPrazoRaw = Number(pedido.total_amount_installments ?? pedido.totalAmountInstallments ?? 0);
  const totalPrazo = Number.isFinite(totalPrazoRaw) ? totalPrazoRaw : 0;

  const totalAreaRaw = Number(pedido.total_area ?? pedido.totalArea ?? 0);
  const totalArea = Number.isFinite(totalAreaRaw) ? totalAreaRaw : 0;

  return {
    id: pedido.id,
    name: pedido.name ?? 'Não informado',
    total,
    totalFormatted: formatCurrency(total),
    totalVista,
    totalVistaFormatted: formatCurrency(totalVista),
    totalPrazo,
    totalPrazoFormatted: formatCurrency(totalPrazo),
    totalArea: totalArea.toFixed(2),
    deliveryTime: formatDeliveryTime(pedido.delivery_time ?? pedido.deliveryTime ?? null),
    status: pedido.status ?? null,
    paymentMethod: pedido.payment_method ?? pedido.paymentMethod ?? null,
    installments: pedido.installments ?? null,
    installmentLimit: pedido.installment_limit ?? pedido.installmentLimit ?? null,
    carrierName: pedido.selected_carrier_name ?? pedido.selectedCarrierName ?? null,
    carrierPrice: pedido.selected_carrier_price ?? pedido.selectedCarrierPrice ?? null,
    carrierDeliveryTime: pedido.selected_carrier_delivery_time ?? pedido.selectedCarrierDeliveryTime ?? null,
    cep: pedido.cep ?? null,
    comment: pedido.comment_referring_model ?? pedido.commentReferringModel ?? null,
    link: pedido.link_referring_model ?? pedido.linkReferringModel ?? null,
    files: Array.isArray(pedido.files_referring_model)
      ? pedido.files_referring_model
      : Array.isArray(pedido.filesReferringModel)
        ? pedido.filesReferringModel
        : [],
    rooms: Array.isArray(pedido.rooms) ? pedido.rooms : [],
    createdAt: normalizeDate(pedido.created_at ?? pedido.createdAt ?? null),
    updatedAt: normalizeDate(pedido.updated_at ?? pedido.updatedAt ?? null),
    budget_id: pedido.budget_id ?? pedido.budgetId ?? null,
  };
});

// Buscar solicitações de artes quando o modal for aberto
watch(() => props.visible, async (isVisible) => {
  if (isVisible && props.pedido && auth.user?.id) {
    // Aguardar o próximo tick para garantir que os dados estejam disponíveis
    await nextTick();
    // Quando o modal abrir, fazer a requisição
    fetchRequestLayoutArts();
    // Mostrar o modal
    nextTick(() => {
      showModalInstance();
    });
  } else {
    // Ocultar o modal
    hideModal();
    // Limpar dados quando o modal fechar
    requestLayoutArts.value = [];
    loadingRequestArts.value = false;
    artForms.value = {};
    artFileInputs.value = {};
  }
}, { immediate: false });

// Também observar mudanças no pedido
watch(() => props.pedido?.id, async (pedidoId) => {
  if (props.visible && pedidoId && auth.user?.id) {
    await nextTick();
    fetchRequestLayoutArts();
  }
}, { immediate: false });

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
.pedido-details {
  max-height: calc(100vh - 300px);
  overflow-y: auto;
}

.accordion-button {
  font-weight: 500;
}

.form-label {
  color: var(--bs-body-color);
}

.form-control,
.form-select {
  background-color: var(--bs-body-bg);
  color: var(--bs-body-color);
  border-color: var(--bs-border-color);
}

.form-control:focus,
.form-select:focus {
  background-color: var(--bs-body-bg);
  color: var(--bs-body-color);
  border-color: var(--bs-primary);
  box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
}

.form-text {
  color: var(--bs-secondary);
}
</style>

