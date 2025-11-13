<template>
  <Teleport v-if="visible" to="body">
    <div>
      <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Detalhes do Pedido</h5>
              <button
                type="button"
                class="btn-close"
                aria-label="Close"
                @click="handleClose"
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
                        <span class="badge bg-warning text-dark fs-6">{{ details.status }}</span>
                      </div>
                    </div>
                    <div class="col-12">
                      <div class="text-muted small">Nome do Pedido</div>
                      <div class="fw-semibold fs-5">{{ details.name }}</div>
                    </div>
                    <div class="col-md-4">
                      <div class="text-muted small">Valor Total</div>
                      <div class="fw-semibold fs-5 text-primary">{{ details.totalFormatted }}</div>
                    </div>
                    <div class="col-md-4">
                      <div class="text-muted small">Prazo de Entrega</div>
                      <div class="fw-semibold">{{ details.deliveryTime }}</div>
                    </div>
                    <div class="col-md-4">
                      <div class="text-muted small">Área Total</div>
                      <div class="fw-semibold">{{ details.totalArea }} m²</div>
                    </div>
                  </div>
                </div>

                <!-- Informações de Pagamento -->
                <div v-if="details.paymentMethod" class="border rounded p-3 mb-4">
                  <h6 class="fw-semibold mb-3">Informações de Pagamento</h6>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <div class="text-muted small">Método de Pagamento</div>
                      <div class="fw-semibold">{{ formatPaymentMethod(details.paymentMethod) }}</div>
                    </div>
                    <div v-if="details.installments" class="col-md-6">
                      <div class="text-muted small">Parcelas</div>
                      <div class="fw-semibold">
                        {{ details.installments }}
                        <span v-if="details.installmentLimit">
                          de {{ details.installmentLimit }}
                        </span>
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
                    <div class="bg-light p-2 rounded">{{ details.comment }}</div>
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
                          <i class="fa fa-door-open me-2"></i>
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

                <!-- Datas -->
                <div class="border rounded p-3 mb-4">
                  <h6 class="fw-semibold mb-3">Informações</h6>
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
                type="button"
                class="btn btn-danger"
                @click="handleReject"
                :disabled="processing"
              >
                <span
                  v-if="processing && actionType === 'reject'"
                  class="spinner-border spinner-border-sm me-2"
                  role="status"
                  aria-hidden="true"
                ></span>
                <i v-else class="fa fa-times me-2"></i>
                Rejeitar
              </button>
              <button
                type="button"
                class="btn btn-success"
                @click="handleApprove"
                :disabled="processing"
              >
                <span
                  v-if="processing && actionType === 'approve'"
                  class="spinner-border spinner-border-sm me-2"
                  role="status"
                  aria-hidden="true"
                ></span>
                <i v-else class="fa fa-check me-2"></i>
                Aprovar
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
import { computed, ref } from 'vue';
import Swal from 'sweetalert2';

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

const emit = defineEmits(['close', 'approve', 'reject']);

const processing = ref(false);
const actionType = ref(null);

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL',
});

function handleClose() {
  if (processing.value) {
    return;
  }
  emit('close');
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
    cash: 'Dinheiro',
    card: 'Cartão',
    installment: 'Parcelado',
    pix: 'PIX',
  };

  return methods[method] || method;
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

async function handleApprove() {
  if (!props.pedido) {
    return;
  }

  const result = await Swal.fire({
    title: 'Aprovar pedido?',
    text: `Tem certeza que deseja aprovar o pedido "${props.pedido.name}"?`,
    icon: 'question',
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
    // TODO: Implementar chamada ao backend quando estiver pronto
    // await axios.post(`v1/budgets/${props.pedido.id}/approve`);

    emit('approve', props.pedido);

    await Swal.fire({
      title: 'Pedido aprovado',
      text: 'O pedido foi aprovado com sucesso.',
      icon: 'success',
      confirmButtonText: 'OK',
    });
  } catch (error) {
    await Swal.fire({
      title: 'Erro',
      text: 'Não foi possível aprovar o pedido. Tente novamente.',
      icon: 'error',
      confirmButtonText: 'OK',
    });
  } finally {
    processing.value = false;
    actionType.value = null;
  }
}

async function handleReject() {
  if (!props.pedido) {
    return;
  }

  const result = await Swal.fire({
    title: 'Rejeitar pedido?',
    text: `Tem certeza que deseja rejeitar o pedido "${props.pedido.name}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sim, rejeitar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#dc3545',
  });

  if (!result.isConfirmed) {
    return;
  }

  processing.value = true;
  actionType.value = 'reject';

  try {
    // TODO: Implementar chamada ao backend quando estiver pronto
    // await axios.post(`v1/budgets/${props.pedido.id}/reject`);

    emit('reject', props.pedido);

    await Swal.fire({
      title: 'Pedido rejeitado',
      text: 'O pedido foi rejeitado com sucesso.',
      icon: 'success',
      confirmButtonText: 'OK',
    });
  } catch (error) {
    await Swal.fire({
      title: 'Erro',
      text: 'Não foi possível rejeitar o pedido. Tente novamente.',
      icon: 'error',
      confirmButtonText: 'OK',
    });
  } finally {
    processing.value = false;
    actionType.value = null;
  }
}

const details = computed(() => {
  const pedido = props.pedido;

  if (!pedido) {
    return null;
  }

  const totalRaw = Number(pedido.total_amount ?? pedido.totalAmount ?? 0);
  const total = Number.isFinite(totalRaw) ? totalRaw : 0;

  const totalAreaRaw = Number(pedido.total_area ?? pedido.totalArea ?? 0);
  const totalArea = Number.isFinite(totalAreaRaw) ? totalAreaRaw : 0;

  return {
    id: pedido.id,
    name: pedido.name ?? 'Não informado',
    total,
    totalFormatted: formatCurrency(total),
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
  };
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
</style>

