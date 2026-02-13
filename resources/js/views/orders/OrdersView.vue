<template>
  <section class="content">
    <Page title="Pedidos">
      <div>
                <OrderFilters
                    v-if="canShowFilters"
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

          <div v-if="loading" class="p-5 text-center text-muted fw-semibold">
            Carregando pedidos...
          </div>

          <EmptyState
            v-else-if="orders.length === 0"
            heading="Nenhum pedido encontrado"
            icon="box"
            class="p-5"
          >
            Ajuste os filtros para encontrar pedidos.
          </EmptyState>

                    <OrderItemsTable
                        v-else
                        :orders="orders"
                        :can-register-payment="canRegisterPayment"
                        :show-values-column="showValuesColumn"
                        :show-actions-column="showActionsColumn"
                        :name-clickable="nameClickable"
                        @view-details="openDetailsModal"
                        @register-payment="openPaymentModal"
                        @edit="editOrder"
                        @cancel="openCancelModal"
                        @delete="openDeleteModal"
                    />

                    <div
                        v-if="!loading && orders.length > 0 && paginationData.last_page > 1"
                        class="p-3"
                    >
                      <pagination :data="paginationData" @pagination-change-page="handlePageChange" />
          </div>
      </div>
    </Page>

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
import OrdersRegisterPayment from '@/modules/orders/components/OrdersRegisterPayment.vue';
import OrderFilters from '@/modules/orders/components/OrderFilters.vue';
import OrderItemsTable from '@/modules/orders/components/OrderItemsTable.vue';
import OrderCancelModal from '@/modules/orders/components/OrderCancelModal.vue';
import { useAuthStore } from '@/stores/auth';
import { useOrderList } from '@/modules/orders/composables/useOrderList';
import { useOrderFilters } from '@/modules/orders/composables/useOrderFilters';
import { useOrderListService } from '@/modules/orders/services/orderListService';
import { swalConfirmation, swalSuccess, swalError } from '@/utils/alerts';

const router = useRouter();
const auth = useAuthStore();
const orderListService = useOrderListService();

const { orders, loading, paginationData, fetchOrders } = useOrderList();

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
    fetchOrders(filters.value);
});

const users = ref([]);
const loadingUsers = ref(false);
const showPaymentModal = ref(false);
const paymentPedido = ref(null);
const showCancelModal = ref(false);
const pedidoToCancel = ref(null);
const cancelling = ref(false);
const cancelError = ref('');
const orderToDelete = ref(null);
const deleting = ref(false);
const deleteError = ref('');

const isAdmin = computed(() => auth.isAdmin());
const isCommercial = computed(() => auth.hasRole('commercial'));

/** Filtros (busca, status, datas, revendedor) apenas para admin. */
const canShowFilters = computed(() => isAdmin.value);

/** Comercial: sem coluna de valores e sem coluna de ações. */
const showValuesColumn = computed(() => !isCommercial.value);
const showActionsColumn = computed(() => !isCommercial.value);
const nameClickable = computed(() => !isCommercial.value);

const canRegisterPayment = computed(() => {
  return auth.hasPermission('register payments');
});

function handlePageChange(page) {
    fetchOrders(filters.value, page);
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

function openPaymentModal(pedido) {
  paymentPedido.value = pedido;
  showPaymentModal.value = true;
}

function closePaymentModal() {
  showPaymentModal.value = false;
  paymentPedido.value = null;
}

function handlePaymentSuccess() {
    fetchOrders(filters.value, paginationData.value.current_page);
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

        fetchOrders(filters.value, paginationData.value.current_page);
  } catch (error) {
        cancelError.value = error?.response?.data?.message || 'Não foi possível cancelar o pedido. Tente novamente.';
  } finally {
    cancelling.value = false;
  }
}

function openDeleteModal(order) {
    if (!order) {
        return;
    }
    
    orderToDelete.value = order;
    deleteError.value = '';
    
    const orderName = order?.name || order?.id || 'este pedido';
    
    swalConfirmation(
        'Excluir pedido?',
        `Tem certeza que deseja excluir o pedido "${orderName}"? Esta ação não pode ser desfeita.`,
        'warning',
        'Sim, excluir',
        'Cancelar'
    ).then(async (result) => {
        if (result.isConfirmed) {
            await confirmDeleteOrder();
        } else {
            orderToDelete.value = null;
        }
    });
}

async function confirmDeleteOrder() {
    if (!orderToDelete.value?.id) {
        return;
    }

    deleting.value = true;
    deleteError.value = '';

    try {
        await orderListService.deleteOrder(orderToDelete.value.id);

        await swalSuccess('Pedido excluído', 'O pedido foi excluído com sucesso.');

        orderToDelete.value = null;
        fetchOrders(filters.value, paginationData.value.current_page);
    } catch (error) {
        deleteError.value = error?.response?.data?.message || 'Não foi possível excluir o pedido. Tente novamente.';
        
        await swalError('Erro ao excluir', deleteError.value);
    } finally {
        deleting.value = false;
    }
}

onMounted(() => {
    fetchOrders(filters.value, 1);
  if (isAdmin.value) {
    fetchUsers();
  }
  document.title = 'Pedidos';
});
</script>

<style scoped>

</style>
