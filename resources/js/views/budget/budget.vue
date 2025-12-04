<template>
  <section class="content">
    <Page title="Orçamentos">
      <template #actions>
        <button class="btn btn-primary" type="button" @click="goToCreateBudget">
          Criar orçamento
        </button>
      </template>

      <div class="border-0 shadow-sm">
        <form class="g-3 align-items-center mb-4" role="search">
          <label for="search-query" class="sr-only">Pesquisar orçamento</label>

          <div class="d-flex">
            <div class="me-3">
                <div class="input-group input-group-prefix">
                    <input id="search-query" type="text" class="form-control"
                        placeholder="Pesquisar orçamento" v-model="searchQuery">
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
                type="date"
                class="form-control"
                :disabled="loading"
              />
            </div>

            <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
              <label for="dateTo" class="form-label small mb-1">Data Final</label>
              <input
                id="dateTo"
                v-model="dateTo"
                type="date"
                class="form-control"
                :disabled="loading"
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
                class="btn btn-outline-secondary btn-lg w-100"
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
            Carregando orçamentos...
          </div>

          <EmptyState
            v-else-if="filteredBudgets.length === 0"
            heading="Nenhum orçamento encontrado"
            icon="file-alt"
            class="p-5"
          >
            Ajuste os filtros ou crie um novo orçamento.
          </EmptyState>

          <div v-else class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th scope="col" style="width: 64px;">Número</th>
                  <th scope="col" style="width: 64px;">Data</th>
                  <th class="text-nowrap" scope="col">Orçamento</th>
                  <th class="text-nowrap" scope="col">Situação</th>
                  <th class="text-nowrap" scope="col" style="width: 64px;">Ações</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="budget in filteredBudgets" :key="budget.id">
                  <td class="fw-semibold">{{ budget.id }}</td>
                  <td>{{ formatDate(budget.created_at || budget.createdAt) }}</td>
                  <td style="min-width: 240px;">
                    <button
                      class="btn btn-link text-decoration-none p-0 text-start fw-semibold"
                      @click="editBudget(budget)"
                    >
                      {{ budget.name }}
                    </button>
                  </td>
                  <td>

                      <span class="status-dot" :class="`status-dot-${getStatusVariant(budget.status)}`"></span>
                      {{ formatStatusLabel(budget.status) }}

                  </td>
                  <td >
                    <div class="dropdown">
                      <button class="btn btn-subtle btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa fa-ellipsis-h"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                          <button class="dropdown-item" type="button" @click="openDetailsModal(budget)">
                            Ver detalhes
                          </button>
                        </li>
                        <li>
                          <button class="dropdown-item" type="button" @click="openGeneratePdfModal(budget)">
                            Gerar PDF
                          </button>
                        </li>
                        <li v-if="budget.status === null || (budget.status && budget.status.toString().toLowerCase() === 'em aberto')">
                          <button class="dropdown-item" type="button" @click="openOrderModal(budget)">
                            Fazer pedido
                          </button>
                        </li>
                        <li v-if="!isCancelled(budget)">
                          <button class="dropdown-item text-danger" type="button" @click="openCancelModal(budget)">
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

    <GeneratePdfModal
      :visible="showPdfModal"
      :budget="budgetToGeneratePdf"
      @close="closeGeneratePdfModal"
      @success="handlePdfSuccess"
    />

    <Teleport v-if="showCancelModal" to="body">
      <div>
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Cancelar orçamento</h5>
                <button type="button" class="btn-close" aria-label="Close" @click="closeCancelModal"></button>
              </div>
              <div class="modal-body">
                <p class="mb-3">
                  Tem certeza que deseja cancelar o orçamento
                  <strong>{{ budgetToCancel?.name }}</strong>?
                </p>
                <p class="text-muted small mb-0">
                  Essa ação não pode ser desfeita. O status do orçamento será alterado para <strong>Cancelado</strong>.
                </p>
                <p v-if="cancelError" class="text-danger small mt-3 mb-0">
                  {{ cancelError }}
                </p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" :disabled="cancelling" @click="closeCancelModal">
                  Manter orçamento
                </button>
                <button type="button" class="btn btn-danger" :disabled="cancelling" @click="confirmCancelBudget">
                  <span
                    v-if="cancelling"
                    class="spinner-border spinner-border-sm me-2"
                    role="status"
                    aria-hidden="true"
                  ></span>
                  Cancelar orçamento
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
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import Page from '@/components/page/Page.vue';
import { USER_TYPES } from '@/constants/userTypes';
import EmptyState from '@/components/empty-state/EmptyState.vue';
import BudgetDetailsModal from '@/components/budget/BudgetDetailsModal.vue';
import BudgetOrderModal from '@/components/budget/BudgetOrderModal.vue';
import GeneratePdfModal from '@/components/budget/GeneratePdfModal.vue';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const budgets = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const statusFilter = ref('all');
const dateFrom = ref('');
const dateTo = ref('');
const selectedUserId = ref(null);
const users = ref([]);
const loadingUsers = ref(false);

const isAdmin = computed(() => auth.user?.user_type_id === USER_TYPES.ADMIN);
const showCancelModal = ref(false);
const budgetToCancel = ref(null);
const cancelling = ref(false);
const cancelError = ref('');
const showDetailsModal = ref(false);
const budgetToView = ref(null);
const showPdfModal = ref(false);
const budgetToGeneratePdf = ref(null);
const showOrderModal = ref(false);
const orderBudget = ref(null);
const router = useRouter();

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

function formatDate(value) {
  if (!value) {
    return '—';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat('pt-BR').format(date);
}

function normalizeBudget(budget) {
  if (!budget) {
    return {
      id: null,
      name: '',
      total_amount: 0,
      total_amount_installments: 0,
      delivery_time: null,
      status: null,
      rooms: [],
      comment_referring_model: '',
      commentReferringModel: '',
      link_referring_model: '',
      linkReferringModel: '',
      files_referring_model: [],
      filesReferringModel: [],
      collection_referring_model: null,
      collectionReferringModel: null,
    };
  }

  const totalAmount = budget.total_amount ?? budget.totalAmount ?? 0;
  const totalAmountInstallments = budget.total_amount_installments ?? budget.totalAmountInstallments ?? 0;
  const deliveryTime = budget.delivery_time ?? budget.deliveryTime ?? null;
  const status = budget.status ?? budget.Status ?? null;
  const commentRef = budget.comment_referring_model ?? budget.commentReferringModel ?? '';
  const linkRef = budget.link_referring_model ?? budget.linkReferringModel ?? '';
  const filesRef = Array.isArray(budget.files_referring_model)
    ? budget.files_referring_model
    : Array.isArray(budget.filesReferringModel)
      ? budget.filesReferringModel
      : [];
  const collectionRef = budget.collection_referring_model ?? budget.collectionReferringModel ?? null;
  const rooms = Array.isArray(budget.rooms) ? budget.rooms : [];

  return {
    ...budget,
    name: budget.name ?? '',
    total_amount: totalAmount,
    total_amount_installments: totalAmountInstallments,
    delivery_time: deliveryTime,
    status,
    rooms,
    comment_referring_model: commentRef,
    commentReferringModel: commentRef,
    link_referring_model: linkRef,
    linkReferringModel: linkRef,
    files_referring_model: filesRef,
    filesReferringModel: filesRef,
    collection_referring_model: collectionRef,
    collectionReferringModel: collectionRef,
  };
}

const statusOptions = [
  { label: 'Em aberto', value: 'em aberto' },
  { label: 'Aprovado', value: 'aprovado' },
  { label: 'Aprovar Layout', value: 'aprovar layout' },
  { label: 'Pendente de Revisão', value: 'pendente de revisão' },
  { label: 'Cancelado', value: 'cancelado' },
];

const filteredBudgets = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  const status = statusFilter.value;

  return budgets.value.filter((budget) => {
    // Filtro de busca
    const matchesQuery = !query
      || budget.name?.toLowerCase().includes(query)
      || String(budget.id).includes(query);

    // Filtro de status
    const normalizedStatus = (budget.status || '').toString().toLowerCase();
    const matchesStatus = status === 'all' || normalizedStatus === status;

    // Filtro de período (apenas para admin)
    let matchesPeriod = true;
    if (isAdmin.value && (dateFrom.value || dateTo.value)) {
      const budgetDateStr = budget.created_at || budget.createdAt;
      if (!budgetDateStr) {
        matchesPeriod = false;
      } else {
        const budgetDate = new Date(budgetDateStr);
        if (Number.isNaN(budgetDate.getTime())) {
          matchesPeriod = false;
        } else {
          const budgetDateOnly = new Date(budgetDate.getFullYear(), budgetDate.getMonth(), budgetDate.getDate());

          if (dateFrom.value) {
            const fromDate = new Date(dateFrom.value);
            fromDate.setHours(0, 0, 0, 0);
            if (budgetDateOnly < fromDate) {
              matchesPeriod = false;
            }
          }

          if (dateTo.value && matchesPeriod) {
            const toDate = new Date(dateTo.value);
            toDate.setHours(23, 59, 59, 999);
            const toDateOnly = new Date(toDate.getFullYear(), toDate.getMonth(), toDate.getDate());
            if (budgetDateOnly > toDateOnly) {
              matchesPeriod = false;
            }
          }
        }
      }
    }

    // Filtro de revendedor (apenas para admin)
    let matchesUser = true;
    if (isAdmin.value && selectedUserId.value !== null) {
      const budgetUserId = budget.user_id || budget.userId || budget.user?.id;
      matchesUser = Number(budgetUserId) === Number(selectedUserId.value);
    }

    return matchesQuery && matchesStatus && matchesPeriod && matchesUser;
  });
});

const currentStatusLabel = computed(() => {
  if (statusFilter.value === 'all') {
    return 'Situação';
  }

  const match = statusOptions.find((option) => option.value === statusFilter.value);
  return match ? match.label : 'Situação';
});

async function fetchBudgets() {
  try {
    loading.value = true;

    const { data } = await axios.get('v1/budgets');

    const payload = Array.isArray(data?.data)
      ? data.data.map(normalizeBudget)
      : Array.isArray(data?.data?.data)
        ? data.data.data.map(normalizeBudget)
        : [];

    budgets.value = payload;
  } catch (error) {
    budgets.value = [];
  } finally {
    loading.value = false;
  }
}

function isCancelled(budget) {
  const status = (budget?.status ?? '').toString().toLowerCase();
  return status === 'cancelled' || status === 'cancelado';
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
}

function goToCreateBudget() {
  router.push('/budget/new-budget').catch(() => {});
}

async function shareBudget(budget) {
  const shareUrl = `${window.location.origin}/budgets/${budget.id}`;
  try {
    if (navigator.share) {
      await navigator.share({
        title: budget.name,
        url: shareUrl,
      });
      return;
    }

    if (navigator.clipboard) {
      await navigator.clipboard.writeText(shareUrl);
      window.Toast?.fire({
        icon: 'success',
        title: 'Link copiado para a área de transferência',
      });
      return;
    }
  } catch (error) {
    console.error('Erro ao compartilhar orçamento:', error);
  }

  alert(shareUrl);
}

function openCancelModal(budget) {
  budgetToCancel.value = budget;
  cancelError.value = '';
  showCancelModal.value = true;
}

function editBudget(budget) {
  router.push(`/budget/edit/${budget.id}`);
}

function openDetailsModal(budget) {
  router.push(`/budget/${budget.id}`);
}

function closeDetailsModal() {
  showDetailsModal.value = false;
  budgetToView.value = null;
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
    await axios.post('v1/budgets/cancel', {
      id: budgetToCancel.value.id,
    });

    budgets.value = budgets.value.map((budget) =>
      budget.id === budgetToCancel.value.id
        ? {
            ...budget,
            status: 'cancelado',
          }
        : budget,
    );

    showCancelModal.value = false;
    budgetToCancel.value = null;
  } catch (error) {
    cancelError.value = 'Não foi possível cancelar o orçamento. Tente novamente.';
  } finally {
    cancelling.value = false;
  }
}

async function fetchUsers() {
  if (!isAdmin.value) {
    return;
  }

  try {
    loadingUsers.value = true;
    const response = await axios.get('v1/users/search');

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
}

onMounted(() => {
  fetchBudgets();
  if (isAdmin.value) {
    fetchUsers();
  }
  document.title = 'Orçamentos';
});

function openGeneratePdfModal(budget) {
  budgetToGeneratePdf.value = budget;
  showPdfModal.value = true;
}

function closeGeneratePdfModal() {
  budgetToGeneratePdf.value = null;
  showPdfModal.value = false;
}

function handlePdfSuccess() {
  // Recarregar lista de orçamentos após sucesso
  fetchBudgets();
}

function openOrderModal(budget) {
  orderBudget.value = normalizeBudget(budget);
  showOrderModal.value = true;
}

function handleOrderClose() {
  showOrderModal.value = false;
  orderBudget.value = null;
}

function handleOrderUpdated(updatedBudgetRaw) {
  const updatedBudget = normalizeBudget(updatedBudgetRaw);

  budgets.value = budgets.value.map((budget) =>
    budget.id === updatedBudget.id ? updatedBudget : budget,
  );

  if (budgetToView.value?.id === updatedBudget.id) {
    budgetToView.value = updatedBudget;
  }

  if (budgetToGeneratePdf.value?.id === updatedBudget.id) {
    budgetToGeneratePdf.value = updatedBudget;
  }

  if (budgetToCancel.value?.id === updatedBudget.id) {
    budgetToCancel.value = updatedBudget;
  }
}
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
</style>
