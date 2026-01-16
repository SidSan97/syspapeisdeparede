<template>
    <section class="content">
      <Page title="Pedidos">
        <div class="border-0 shadow-sm">
                <InternalOrderFilters
                    :is-admin="isAdmin"
                    :is-commercial="isCommercialUser"
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
                        v-else-if="orders.length === 0"
              heading="Nenhum pedido encontrado"
              icon="file-alt"
              class="p-5"
            >
              Ajuste os filtros ou crie um novo pedido.
            </EmptyState>

                    <InternalOrderItemsTable
                        v-else
                        :orders="orders"
                        :status-options="statusOptions"
                        @view-order="viewOrder"
                    />

                    <div
                        v-if="!loading && orders.length > 0 && paginationData.last_page > 1"
                        class="p-3"
                    >
                        <pagination :data="paginationData" @pagination-change-page="handlePageChange" />
            </div>
          </div>
        </div>
      </Page>
    </section>
  </template>

<script setup>
import { ref, computed, onMounted } from 'vue';
  import { useRouter } from 'vue-router';
  import Page from '@/components/page/Page.vue';
  import EmptyState from '@/components/empty-state/EmptyState.vue';
import InternalOrderFilters from './components/InternalOrderFilters.vue';
import InternalOrderItemsTable from './components/InternalOrderItemsTable.vue';
  import { useAuthStore } from '@/stores/auth';
import { useInternalOrderList } from './composables/useInternalOrderList';
import { useInternalOrderFilters } from './composables/useInternalOrderFilters';
import { useInternalOrderListService } from './services/internalOrderListService';

const router = useRouter();
  const auth = useAuthStore();
const internalOrderListService = useInternalOrderListService();

const { orders, loading, paginationData, fetchOrders } = useInternalOrderList();

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
} = useInternalOrderFilters(() => {
    fetchOrders(filters.value);
});

  const users = ref([]);
  const loadingUsers = ref(false);

  const isAdmin = computed(() => auth.isAdmin());
  const isCommercialUser = computed(() => auth.hasRole('commercial'));

function handlePageChange(page) {
    fetchOrders(filters.value, page);
  }

  async function fetchUsers() {
    if (!isAdmin.value && !isCommercialUser.value) {
      return;
    }

    try {
      loadingUsers.value = true;
        users.value = await internalOrderListService.getUsers();
    } catch (error) {
      console.error('Erro ao buscar usuários:', error);
      users.value = [];
    } finally {
      loadingUsers.value = false;
    }
  }

function viewOrder(order) {
    router.push({ name: 'ShowOrderDetails', params: { id: order.id } });
  }

  onMounted(() => {
    fetchOrders(filters.value, 1);
    if (isAdmin.value || isCommercialUser.value) {
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
