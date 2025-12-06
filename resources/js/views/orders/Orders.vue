<template>
  <section class="content">
    <Page title="Pedidos">
      <div class="card">
        <div class="card-body p-0">
          <div v-if="loading" class="p-4 text-center text-muted">
            Carregando pedidos...
          </div>

          <EmptyState
            v-else-if="pedidos.length === 0"
            heading="Nenhum pedido encontrado"
            icon="box"
            class="p-5"
          >
            Não há pedidos pendentes de revisão ou aprovados no momento.
          </EmptyState>

          <div v-else class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Nome</th>
                  <th class="text-end">Valor Total</th>
                  <th>Prazo de Entrega</th>
                  <th>Status</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="pedido in pedidos" :key="pedido.id">
                  <td>
                    <button
                      class="btn btn-link text-start p-0 text-decoration-none fw-semibold"
                      @click="openDetailsModal(pedido)"
                    >
                      {{ pedido.name }}
                    </button>
                  </td>
                  <td class="text-end">
                    <span class="fw-semibold">{{ formatCurrency(pedido.total_amount) }}</span>
                  </td>
                  <td>{{ formatDeliveryTime(pedido.delivery_time) }}</td>
                  <td>
                    <span
                      class="badge"
                      :class="{
                        'bg-warning text-dark': pedido.status === 'Pendente de Revisão',
                        'bg-success': pedido.status === 'Aprovado',
                        'bg-danger': isCancelled(pedido),
                      }"
                    >
                      {{ pedido.status }}
                    </span>
                  </td>
                  <td>
                    <div class="dropdown">
                      <button class="btn btn-subtle btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa fa-ellipsis-h"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                          <button class="dropdown-item" type="button" @click="openDetailsModal(pedido)">
                            Ver detalhes
                          </button>
                        </li>
                        <li v-if="pedido.status !== 'Aprovado' && canRegisterPayment">
                          <button class="dropdown-item" type="button" @click="openPaymentModal(pedido)">
                            Registrar pagamento
                          </button>
                        </li>
                        <li>
                          <button class="dropdown-item" type="button" @click="editOrder(pedido)">
                            Editar
                          </button>
                        </li>
                        <li v-if="!isCancelled(pedido)">
                          <button class="dropdown-item text-danger" type="button" @click="openCancelModal(pedido)">
                            Cancelar
                          </button>
                        </li>
                      </ul>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </Page>

    <PedidoDetailsModal
      :visible="showDetailsModal"
      :pedido="selectedPedido"
      @close="closeDetailsModal"
      @approve="handleApprove"
    />

    <OrdersRegisterPayment
      :visible="showPaymentModal"
      :pedido="paymentPedido"
      @close="closePaymentModal"
      @success="handlePaymentSuccess"
    />

    <Teleport v-if="showCancelModal" to="body">
      <div>
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Cancelar pedido</h5>
                <button type="button" class="btn-close" aria-label="Close" @click="closeCancelModal"></button>
              </div>
              <div class="modal-body">
                <p class="mb-3">
                  Tem certeza que deseja cancelar o pedido
                  <strong>{{ pedidoToCancel?.name }}</strong>?
                </p>
                <p class="text-muted small mb-0">
                  Essa ação não pode ser desfeita. O status do pedido será alterado para <strong>Cancelado</strong>.
                </p>
                <p v-if="cancelError" class="text-danger small mt-3 mb-0">
                  {{ cancelError }}
                </p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" :disabled="cancelling" @click="closeCancelModal">
                  Manter pedido
                </button>
                <button type="button" class="btn btn-danger" :disabled="cancelling" @click="confirmCancelPedido">
                  <span
                    v-if="cancelling"
                    class="spinner-border spinner-border-sm me-2"
                    role="status"
                    aria-hidden="true"
                  ></span>
                  Cancelar pedido
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-backdrop fade show"></div>
      </div>
    </Teleport>
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Page from '../../components/page/Page.vue';
import EmptyState from '../../components/empty-state/EmptyState.vue';
import PedidoDetailsModal from './components/OrdersDetailsModal.vue';
import OrdersRegisterPayment from './components/OrdersRegisterPayment.vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const router = useRouter();
const auth = useAuthStore();
const pedidos = ref([]);
const loading = ref(false);
const showDetailsModal = ref(false);
const selectedPedido = ref(null);
const showPaymentModal = ref(false);
const paymentPedido = ref(null);
const showCancelModal = ref(false);
const pedidoToCancel = ref(null);
const cancelling = ref(false);
const cancelError = ref('');

import { USER_TYPES } from '@/constants/userTypes';

const canRegisterPayment = computed(() => {
  return auth.user?.user_type_id === USER_TYPES.ADMIN;
});

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL',
});

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

function normalizePedido(pedido) {
  if (!pedido) {
    return null;
  }

  return {
    ...pedido,
    name: pedido.name ?? 'Não informado',
    total_amount: Number(pedido.total_amount ?? pedido.totalAmount ?? 0),
    delivery_time: pedido.delivery_time ?? pedido.deliveryTime ?? null,
    status: pedido.status ?? null,
  };
}

async function fetchPedidos() {
  try {
    loading.value = true;

    const { data } = await axios.get('v1/orders');

    const payload = Array.isArray(data?.data)
      ? data.data.map(normalizePedido)
      : Array.isArray(data?.data?.data)
        ? data.data.data.map(normalizePedido)
        : [];

    pedidos.value = payload;
  } catch (error) {
    console.error('Erro ao carregar pedidos:', error);
    pedidos.value = [];
  } finally {
    loading.value = false;
  }
}

function openDetailsModal(pedido) {
  router.push({ name: 'ShowOrderDetails', params: { id: pedido.id } });
}

function closeDetailsModal() {
  showDetailsModal.value = false;
  selectedPedido.value = null;
}

function handleApprove(pedido) {
  closeDetailsModal();
  // Recarrega a lista para atualizar os status
  fetchPedidos();
}

function openPaymentModal(pedido) {
  paymentPedido.value = pedido;
  showPaymentModal.value = true;
}

function closePaymentModal() {
  showPaymentModal.value = false;
  paymentPedido.value = null;
}

function handlePaymentSuccess() {
  // Recarregar lista de pedidos após sucesso
  fetchPedidos();
}

function editOrder(order) {
  router.push({ name: 'EditOrder', params: { id: order.id } });
}

function isCancelled(pedido) {
  const status = (pedido?.status ?? '').toString().toLowerCase();
  return status === 'cancelled' || status === 'cancelado';
}

function openCancelModal(pedido) {
  pedidoToCancel.value = pedido;
  cancelError.value = '';
  showCancelModal.value = true;
}

function closeCancelModal() {
  if (cancelling.value) {
    return;
  }

  showCancelModal.value = false;
  pedidoToCancel.value = null;
}

async function confirmCancelPedido() {
  if (!pedidoToCancel.value?.id) {
    return;
  }

  cancelling.value = true;
  cancelError.value = '';

  try {
    await axios.post('v1/orders/cancel', {
      id: pedidoToCancel.value.id,
    });

    pedidos.value = pedidos.value.map((pedido) =>
      pedido.id === pedidoToCancel.value.id
        ? {
            ...pedido,
            status: 'cancelado',
          }
        : pedido,
    );

    showCancelModal.value = false;
    pedidoToCancel.value = null;
  } catch (error) {
    cancelError.value = 'Não foi possível cancelar o pedido. Tente novamente.';
  } finally {
    cancelling.value = false;
  }
}

onMounted(() => {
  fetchPedidos();
  document.title = 'Pedidos';
});
</script>

<style scoped>
.btn-link {
  color: var(--bs-body-color);
}

.btn-link:hover {
  color: var(--bs-primary);
}
</style>

