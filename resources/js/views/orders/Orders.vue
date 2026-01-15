<template>
  <section class="content">
    <Page title="Pedidos">
      <div class="border-0 shadow-sm">
        <form class="g-3 align-items-center mb-4" role="search">
          <label for="search-query" class="sr-only">Pesquisar pedido</label>

          <div class="d-flex">
            <div class="me-3">
                <div class="input-group input-group-prefix">
                    <input id="search-query" type="text" class="form-control"
                        placeholder="Pesquisar pedido" v-model="searchQuery">
                    <span class="input-group-text">
                        <i class="fa fa-search"></i>
                    </span>
                </div>
            </div>

          <div class="">
            <div class="dropdown">
              <button
                class="btn btn-outline-default dropdown-toggle"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                :value="statusFilter"
              >
                {{ currentStatusLabel }}
              </button>

              <ul class="dropdown-menu">
                <li>
                  <button
                    class="dropdown-item"
                    type="button"
                    @click="setStatusFilter('all')"
                  >
                    Todos
                  </button>
                </li>

                <li v-for="option in statusOptions" :key="option.value">
                  <button
                      class="dropdown-item"
                      type="button"
                      :class="{ active: statusFilter === option.value }"
                      @click="setStatusFilter(option.value)"
                    >
                      {{ option.label }}
                  </button>
                </li>
              </ul>
            </div>
          </div>
          </div>

          <div v-if="isAdmin" class="row buttons-filters mt-2">
            <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
              <label for="dateFrom" class="form-label small mb-1">Data Inicial</label>
              <input
                id="dateFrom"
                v-model="dateFrom"
                v-mask="'##/##/####'"
                type="text"
                class="form-control"
                placeholder="DD/MM/AAAA"
                :disabled="loading"
                maxlength="10"
              />
            </div>

            <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
              <label for="dateTo" class="form-label small mb-1">Data Final</label>
              <input
                id="dateTo"
                v-model="dateTo"
                v-mask="'##/##/####'"
                type="text"
                class="form-control"
                placeholder="DD/MM/AAAA"
                :disabled="loading"
                maxlength="10"
              />
            </div>

            <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
              <label for="userFilter" class="form-label small mb-1">Revendedor</label>
              <select
                id="userFilter"
                v-model="selectedUserId"
                class="form-control"
                :disabled="loading || loadingUsers"
              >
                <option :value="null">Todos os revendedores</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
            </div>

            <div class="col-lg-3 col-md-6 d-flex align-items-end mb-2 mb-lg-0">
              <button
                type="button"
                class="btn btn-outline-default"
                @click="clearFilters"
                :disabled="loading"
              >
                <i class="fa fa-times me-2"></i>
                Limpar Filtros
              </button>
            </div>
            </div>
        </form>

        <div class="card-body p-0 mt-4">
          <div v-if="loading" class="p-5 text-center text-muted fw-semibold">
            Carregando pedidos...
          </div>

          <EmptyState
            v-else-if="pedidos.length === 0"
            heading="Nenhum pedido encontrado"
            icon="box"
            class="p-5"
          >
            Ajuste os filtros para encontrar pedidos.
          </EmptyState>

          <div v-else class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th scope="col" style="width: 64px;">Número</th>
                  <th scope="col" style="width: 64px;">Data</th>
                  <th class="text-nowrap" scope="col">Pedido</th>
                  <th class="text-nowrap" scope="col" style="width: 120px;">Valor total</th>
                  <th class="text-nowrap" scope="col">Situação</th>
                  <th class="text-nowrap" scope="col" style="width: 64px;">Ações</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="pedido in pedidos" :key="pedido.id">
                  <td class="fw-semibold">{{ pedido.id }}</td>
                  <td>{{ formatDate(pedido.created_at || pedido.createdAt) }}</td>
                  <td style="min-width: 240px;">
                    <button
                      class="btn btn-link text-decoration-none p-0 text-start fw-semibold"
                      @click="openDetailsModal(pedido)"
                    >
                      {{ pedido.name }}
                    </button>
                  </td>
                  <td class="">
                    <span class="fw-semibold">{{ formatCurrency(pedido.total_amount) }}</span>
                  </td>
                  <td>
                    <span class="status-dot" :class="`status-dot-${getStatusVariant(pedido.status)}`"></span>
                    {{ formatStatusLabel(pedido.status) }}
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

          <div v-if="!loading && pedidos.length > 0 && paginationData.last_page > 1" class="p-3">
            <pagination
              :data="paginationData"
              @pagination-change-page="fetchPedidos"
            />
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
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import Page from '../../components/page/Page.vue';
import EmptyState from '../../components/empty-state/EmptyState.vue';
import PedidoDetailsModal from './components/OrdersDetailsModal.vue';
import OrdersRegisterPayment from './components/OrdersRegisterPayment.vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import { parseDateFromMask, formatDate, convertDateMaskToIso } from '@/utils/dateUtils';

const router = useRouter();
const auth = useAuthStore();
const pedidos = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const statusFilter = ref('all');
const dateFrom = ref('');
const dateTo = ref('');
const selectedUserId = ref(null);
const users = ref([]);
const loadingUsers = ref(false);
const showDetailsModal = ref(false);
const selectedPedido = ref(null);
const showPaymentModal = ref(false);
const paymentPedido = ref(null);
const showCancelModal = ref(false);
const pedidoToCancel = ref(null);
const cancelling = ref(false);
const cancelError = ref('');
const paginationData = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0,
});

const isAdmin = computed(() => auth.isAdmin());

const canRegisterPayment = computed(() => {
  return auth.hasPermission('register payments');
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

const statusOptions = [
  { label: 'Em aberto', value: 'em aberto' },
  { label: 'Aprovado', value: 'aprovado' },
  { label: 'Aprovar Layout', value: 'aprovar layout' },
  { label: 'Pendente de Revisão', value: 'pendente de revisão' },
  { label: 'Cancelado', value: 'cancelado' },
];


const currentStatusLabel = computed(() => {
  if (statusFilter.value === 'all') {
    return 'Situação';
  }

  const match = statusOptions.find((option) => option.value === statusFilter.value);
  return match ? match.label : 'Situação';
});

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

async function fetchPedidos(page = 1) {
  try {
    loading.value = true;

    const params = {
      page,
      per_page: 15,
    };

    // Adicionar filtros
    if (searchQuery.value.trim()) {
      params.search = searchQuery.value.trim();
    }

    if (statusFilter.value !== 'all') {
      params.status = statusFilter.value;
    }

    if (dateFrom.value && dateFrom.value.length === 10) {
      const convertedDate = convertDateMaskToIso(dateFrom.value);
      if (convertedDate) {
        params.date_from = convertedDate;
      }
    }

    if (dateTo.value && dateTo.value.length === 10) {
      const convertedDate = convertDateMaskToIso(dateTo.value);
      if (convertedDate) {
        params.date_to = convertedDate;
      }
    }

    if (selectedUserId.value !== null) {
      params.user_id = selectedUserId.value;
    }

    const { data } = await axios.get('v1/orders', { params });

    if (data?.success && data?.data) {
      const items = Array.isArray(data.data.data) ? data.data.data : [];
      pedidos.value = items.map(normalizePedido);

      // Atualizar dados de paginação
      paginationData.value = {
        current_page: data.data.current_page || 1,
        last_page: data.data.last_page || 1,
        per_page: data.data.per_page || 15,
        total: data.data.total || 0,
        from: data.data.from || 0,
        to: data.data.to || 0,
      };
    } else {
      pedidos.value = [];
      paginationData.value = {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
        from: 0,
        to: 0,
      };
    }
  } catch (error) {
    console.error('Erro ao carregar pedidos:', error);
    pedidos.value = [];
    paginationData.value = {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
      from: 0,
      to: 0,
    };
  } finally {
    loading.value = false;
  }
}

function formatStatusLabel(status) {
  if (!status) {
    return '—';
  }
  const normalized = status.toString().toLowerCase();
  const match = statusOptions.find((option) => option.value === normalized);
  if (match) {
    return match.label;
  }
  return status;
}

function getStatusVariant(status) {
  const normalized = (status || '').toString().toLowerCase();
  if (normalized.includes('cancel')) {
    return 'danger';
  }
  if (normalized.includes('aprov')) {
    return 'success';
  }
  return 'info';
}

function setStatusFilter(value) {
  statusFilter.value = value;
  fetchPedidos(1); // Resetar para primeira página quando mudar filtro
}

async function fetchUsers() {
  if (!isAdmin.value) {
    return;
  }

  try {
    loadingUsers.value = true;
    const response = await axios.get('v1/users/list');

    if (response.data?.success && response.data?.data) {
      // Se a resposta estiver paginada, pegar o array de dados
      if (response.data.data.data && Array.isArray(response.data.data.data)) {
        users.value = response.data.data.data;
      } else if (Array.isArray(response.data.data)) {
        users.value = response.data.data;
      } else {
        users.value = [];
      }
    }
  } catch (error) {
    console.error('Erro ao buscar usuários:', error);
    users.value = [];
  } finally {
    loadingUsers.value = false;
  }
}

function clearFilters() {
  dateFrom.value = '';
  dateTo.value = '';
  selectedUserId.value = null;
  fetchPedidos(1);
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
  fetchPedidos(paginationData.value.current_page);
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
  // Recarregar página atual após sucesso
  fetchPedidos(paginationData.value.current_page);
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

    showCancelModal.value = false;
    pedidoToCancel.value = null;

    // Recarregar página atual
    fetchPedidos(paginationData.value.current_page);
  } catch (error) {
    cancelError.value = 'Não foi possível cancelar o pedido. Tente novamente.';
  } finally {
    cancelling.value = false;
  }
}

let searchTimeout = null;
watch(searchQuery, () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }
  searchTimeout = setTimeout(() => {
    fetchPedidos(1);
  }, 500);
});

let dateFromTimeout = null;
watch(dateFrom, () => {
  if (dateFromTimeout) {
    clearTimeout(dateFromTimeout);
  }
  dateFromTimeout = setTimeout(() => {
    fetchPedidos(1);
  }, 1500);
});

let dateToTimeout = null;
watch(dateTo, () => {
  if (dateToTimeout) {
    clearTimeout(dateToTimeout);
  }
  dateToTimeout = setTimeout(() => {
    fetchPedidos(1);
  }, 1500);
});

watch(selectedUserId, () => {
  fetchPedidos(1);
});

onMounted(() => {
  fetchPedidos();
  if (isAdmin.value) {
    fetchUsers();
  }
  document.title = 'Pedidos';
});
</script>

<style scoped>
.search-input .form-control,
.search-input .input-group-text {
  border-radius: 0.375rem;
  padding-block: 0.85rem;
}

.search-input .input-group-text {
  border-right: none;
}

.search-input .form-control {
  border-left: none;
}

.search-input .form-control:focus {
  border-color: var(--bs-secondary);
  box-shadow: none;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
  flex-shrink: 0;
}

.status-dot-success {
  background-color: var(--bs-success);
}

.status-dot-danger {
  background-color: var(--bs-danger);
}

.status-dot-info {
  background-color: var(--bs-info);
}

.input-group-text, .buttons-filters button, input {
    height: 36px !important;
}

.btn-link {
  color: var(--bs-body-color);
}

.btn-link:hover {
  color: var(--bs-primary);
}
</style>

