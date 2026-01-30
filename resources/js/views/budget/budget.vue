<template>
  <section class="content">
    <Page title="Orçamentos">
      <template #actions>
        <button class="btn btn-primary" type="button" @click="goToCreateBudget">
          Criar orçamento
        </button>
      </template>

      <div>
                <BudgetFilters
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
            Carregando orçamentos...
          </div>

          <EmptyState
            v-else-if="budgets.length === 0"
            heading="Nenhum orçamento encontrado"
            icon="file-alt"
            class="p-5"
          >
            Ajuste os filtros ou crie um novo orçamento.
          </EmptyState>

            <BudgetItemsTable
                v-else
                :budgets="budgets"
                @view-details="openDetailsModal"
                @generate-pdf="openPdfPreview"
                @create-order="openOrderModal"
                @edit="editBudget"
                @cancel="openCancelModal"
                @delete="openDeleteModal"
            />

              <div
                  v-if="!loading && budgets.length > 0 && paginationData.last_page > 1"
                  class="p-3"
              >
                <pagination :data="paginationData" @pagination-change-page="handlePageChange" />
              </div>
      </div>
    </Page>

    <BudgetDetailsModal
      :visible="showDetailsModal"
      :budget="budgetToView"
      @close="closeDetailsModal"
    />

    <BudgetOrderModal
      :visible="showOrderModal"
      :budget="orderBudget"
      @close="handleOrderClose"
      @updated="handleOrderUpdated"
    />

        <BudgetCancelModal
            :visible="showCancelModal"
            :budget="budgetToCancel"
            :cancelling="cancelling"
            :error="cancelError"
            @close="closeCancelModal"
            @confirm="confirmCancelBudget"
        />
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Page from '@/components/page/Page.vue';
import EmptyState from '@/components/empty-state/EmptyState.vue';
import BudgetDetailsModal from '@/components/budget/BudgetDetailsModal.vue';
import BudgetOrderModal from '@/components/budget/BudgetOrderModal.vue';
import BudgetFilters from '@/modules/budgets/components/BudgetFilters.vue';
import BudgetItemsTable from '@/modules/budgets/components/BudgetItemsTable.vue';
import BudgetCancelModal from '@/modules/budgets/components/BudgetCancelModal.vue';
import { useAuthStore } from '@/stores/auth';
import { useBudgetList } from '@/modules/budgets/composables/useBudgetList';
import { useBudgetFilters } from '@/modules/budgets/composables/useBudgetFilters';
import { useBudgetListService } from '@/modules/budgets/services/budgetListService';
import { swalConfirmation, swalSuccess, swalError } from '@/utils/alerts';

const router = useRouter();
const auth = useAuthStore();
const budgetListService = useBudgetListService();

const { budgets, loading, paginationData, fetchBudgets, normalizeBudget } = useBudgetList();

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
} = useBudgetFilters(() => {
    fetchBudgets(filters.value);
});

const users = ref([]);
const loadingUsers = ref(false);
const showDetailsModal = ref(false);
const budgetToView = ref(null);
const showOrderModal = ref(false);
const orderBudget = ref(null);
const showCancelModal = ref(false);
const budgetToCancel = ref(null);
const cancelling = ref(false);
const cancelError = ref('');
const budgetToDelete = ref(null);
const deleting = ref(false);
const deleteError = ref('');

const isAdmin = computed(() => auth.isAdmin());

function handlePageChange(page) {
    fetchBudgets(filters.value, page);
}

async function fetchUsers() {
    if (!isAdmin.value) {
        return;
    }

    try {
        loadingUsers.value = true;
        users.value = await budgetListService.getUsers();
  } catch (error) {
        console.error('Erro ao buscar usuários:', error);
        users.value = [];
  } finally {
        loadingUsers.value = false;
    }
}

function goToCreateBudget() {
  router.push('/budget/new-budget').catch(() => {});
}

function openDetailsModal(budget) {
  router.push(`/budget/${budget.id}`);
}

function closeDetailsModal() {
  showDetailsModal.value = false;
  budgetToView.value = null;
}

function editBudget(budget) {
    router.push(`/budget/edit/${budget.id}`);
}

function openPdfPreview(budget) {
    router.push(`/budget/${budget.id}/pdf-preview`);
}

function openOrderModal(budget) {
    orderBudget.value = normalizeBudget(budget);
    showOrderModal.value = true;
}

function handleOrderClose() {
    showOrderModal.value = false;
    orderBudget.value = null;
}

function handleOrderUpdated() {
    fetchBudgets(filters.value, paginationData.value.current_page);
}

function openCancelModal(budget) {
    budgetToCancel.value = budget;
    cancelError.value = '';
    showCancelModal.value = true;
}

function closeCancelModal() {
  if (cancelling.value) {
    return;
  }

  showCancelModal.value = false;
  budgetToCancel.value = null;
}

async function confirmCancelBudget() {
  if (!budgetToCancel.value?.id) {
    return;
  }

  cancelling.value = true;
  cancelError.value = '';

  try {
        await budgetListService.cancelBudget(budgetToCancel.value.id);

    showCancelModal.value = false;
    budgetToCancel.value = null;

        fetchBudgets(filters.value, paginationData.value.current_page);
  } catch (error) {
        cancelError.value =
            error?.response?.data?.message || 'Não foi possível cancelar o orçamento. Tente novamente.';
  } finally {
    cancelling.value = false;
  }
}

function openDeleteModal(budget) {
    budgetToDelete.value = budget;
    deleteError.value = '';
    
    swalConfirmation(
        'Excluir orçamento?',
        `Tem certeza que deseja excluir o orçamento "${budget.name}"? Esta ação não pode ser desfeita.`,
        'warning',
        'Sim, excluir',
        'Cancelar'
    ).then(async (result) => {
        if (result.isConfirmed) {
            await confirmDeleteBudget();
        } else {
            budgetToDelete.value = null;
        }
    });
}

async function confirmDeleteBudget() {
    if (!budgetToDelete.value?.id) {
        return;
    }

    deleting.value = true;
    deleteError.value = '';

    try {
        await budgetListService.deleteBudget(budgetToDelete.value.id);

        await swalSuccess('Orçamento excluído', 'O orçamento foi excluído com sucesso.');

        budgetToDelete.value = null;
        fetchBudgets(filters.value, paginationData.value.current_page);
    } catch (error) {
        deleteError.value = error?.response?.data?.message || 'Não foi possível excluir o orçamento. Tente novamente.';
        
        await swalError('Erro ao excluir', deleteError.value);
    } finally {
        deleting.value = false;
    }
}

onMounted(() => {
    fetchBudgets(filters.value, 1);
  if (isAdmin.value) {
    fetchUsers();
  }
  document.title = 'Orçamentos';
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
