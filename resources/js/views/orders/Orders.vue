<template>
  <section class="content">
    <Page title="Pedidos">
      <div class="border-0 shadow-sm">
                <OrderFilters
                    :is-admin="isAdmin"
                    :loading="loading"
                    :loading-users="loadingUsers"
                    :users="users"
                    :search-query="searchQuery"
                    :status-filter="statusFilter"
                    :date-from="dateFrom"
                    :date-to="dateTo"
                    :selected-user-id="selectedUserId"
                    :status-options="statusOptions"
                    :current-status-label="currentStatusLabel"
                    @update:search-query="searchQuery = $event"
                    @update:status-filter="setStatusFilter($event)"
                    @update:date-from="dateFrom = $event"
                    @update:date-to="dateTo = $event"
                    @update:selected-user-id="selectedUserId = $event"
                    @clear-filters="clearFilters"
                />

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

                    <OrderItemsTable
                        v-else
                        :pedidos="pedidos"
                        :can-register-payment="canRegisterPayment"
                        @view-details="openDetailsModal"
                        @register-payment="openPaymentModal"
                        @edit="editOrder"
                        @cancel="openCancelModal"
                    />

                    <div
                        v-if="!loading && pedidos.length > 0 && paginationData.last_page > 1"
                        class="p-3"
                    >
                        <pagination :data="paginationData" @pagination-change-page="handlePageChange" />
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

        <OrderCancelModal
            :visible="showCancelModal"
            :pedido="pedidoToCancel"
            :cancelling="cancelling"
            :error="cancelError"
            @close="closeCancelModal"
            @confirm="confirmCancelPedido"
        />
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Page from '../../components/page/Page.vue';
import EmptyState from '../../components/empty-state/EmptyState.vue';
import PedidoDetailsModal from './components/OrdersDetailsModal.vue';
import OrdersRegisterPayment from './components/OrdersRegisterPayment.vue';
import OrderFilters from './components/OrderFilters.vue';
import OrderItemsTable from './components/OrderItemsTable.vue';
import OrderCancelModal from './components/OrderCancelModal.vue';
import { useAuthStore } from '@/stores/auth';
import { useOrderList } from './composables/useOrderList';
import { useOrderFilters } from './composables/useOrderFilters';
import { useOrderListService } from './services/orderListService';

const router = useRouter();
const auth = useAuthStore();
const orderListService = useOrderListService();

const { pedidos, loading, paginationData, fetchPedidos } = useOrderList();

const {
    filters,
    searchQuery,
    statusFilter,
    dateFrom,
    dateTo,
    selectedUserId,
    statusOptions,
    currentStatusLabel,
    setStatusFilter,
    clearFilters,
} = useOrderFilters(() => {
    fetchPedidos(filters.value);
});

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

const isAdmin = computed(() => auth.isAdmin());

const canRegisterPayment = computed(() => {
  return auth.hasPermission('register payments');
});

function handlePageChange(page) {
    fetchPedidos(filters.value, page);
}

async function fetchUsers() {
  if (!isAdmin.value) {
    return;
  }

  try {
    loadingUsers.value = true;
        users.value = await orderListService.getUsers();
  } catch (error) {
    console.error('Erro ao buscar usuários:', error);
    users.value = [];
  } finally {
    loadingUsers.value = false;
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
    fetchPedidos(filters.value, paginationData.value.current_page);
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
    fetchPedidos(filters.value, paginationData.value.current_page);
}

function editOrder(order) {
  router.push({ name: 'EditOrder', params: { id: order.id } });
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
        await orderListService.cancelOrder(pedidoToCancel.value.id);

    showCancelModal.value = false;
    pedidoToCancel.value = null;

        fetchPedidos(filters.value, paginationData.value.current_page);
  } catch (error) {
        cancelError.value = error?.response?.data?.message || 'Não foi possível cancelar o pedido. Tente novamente.';
  } finally {
    cancelling.value = false;
  }
}

onMounted(() => {
    fetchPedidos(filters.value, 1);
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
</style>
