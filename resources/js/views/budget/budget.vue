<template>
  <section class="content">
    <Page title="Orçamentos">
      <template #actions>
        <button class="btn btn-primary" type="button" @click="goToCreateBudget">
          Criar orçamento
        </button>
      </template>

      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pb-0">
          <div class="d-flex flex-column gap-3">
            <div class="row buttons-filters">
              <div class="col-lg-4">
                <div class="input-group input-group-prefix">
                  <input id="search-query" type="text" class="form-control" placeholder="Pesquisar orçamento"
                   v-model="searchQuery"
                  >
                  <span class="input-group-text">
                    <i class="fa fa-search"></i>
                  </span>
                </div>
              </div>

              <div class="dropdown col-lg-3 mt-2 mt-lg-0">
                <button
                  class="btn btn-outline-secondary btn-lg d-flex align-items-center gap-2"
                  type="button"
                  data-bs-toggle="dropdown"
                  aria-expanded="false"
                >
                  {{ currentStatusLabel }}
                  <i class="fa fa-chevron-down small"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                  <li>
                    <button
                      class="dropdown-item"
                      type="button"
                      :class="{ active: statusFilter === 'all' }"
                      @click="setStatusFilter('all')"
                    >
                      Todas as situações
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

            <!-- Filtros de Admin (Período e Revendedor) -->
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
          </div>
        </div>

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
                  <th scope="col">Número</th>
                  <th scope="col">Data</th>
                  <th scope="col">Orçamento</th>
                  <th scope="col">Situação</th>
                  <th scope="col" class="text-end">Ações</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="budget in filteredBudgets" :key="budget.id">
                  <td class="fw-semibold">#{{ budget.id }}</td>
                  <td>{{ formatDate(budget.created_at || budget.createdAt) }}</td>
                  <td>
                    <button
                      class="btn btn-link text-decoration-none p-0 text-start fw-semibold"
                      @click="editBudget(budget)"
                    >
                      {{ budget.name }}
                    </button>
                  </td>
                  <td>
                    <div class="d-flex align-items-center gap-2 text-capitalize">
                      <span class="status-dot" :class="`status-dot-${getStatusVariant(budget.status)}`"></span>
                      {{ formatStatusLabel(budget.status) }}
                    </div>
                  </td>
                  <td class="text-end">
                    <div class="dropdown">
                      <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                        <li v-if="budget.status === null">
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
    <Teleport v-if="showPdfModal" to="body">
      <div>
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Gerar PDF do orçamento</h5>
                <button type="button" class="btn-close" aria-label="Close" @click="closeGeneratePdfModal"></button>
              </div>
              <div class="modal-body">
                <div v-if="selectedBudgetSummary" class="mb-4">
                  <div class="border rounded p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="text-muted small">Orçamento</span>
                      <span class="badge bg-primary text-light">#{{ selectedBudgetSummary.id }}</span>
                    </div>
                    <div class="fw-semibold">{{ selectedBudgetSummary.name }}</div>
                    <div class="text-muted small mt-2">
                      Valor à vista original: <span class="fw-semibold">{{ selectedBudgetSummary.formattedTotal }}</span>
                    </div>
                    <div v-if="selectedBudgetSummary.totalInstallments > 0" class="text-muted small">
                      Valor a prazo original: <span class="fw-semibold">{{ selectedBudgetSummary.formattedTotalInstallments }}</span>
                    </div>
                    <div class="text-muted small">
                      Prazo de entrega: {{ selectedBudgetSummary.deliveryTime }}
                    </div>
                    <div v-if="selectedBudgetSummary.status" class="text-muted small">
                      Status atual: {{ selectedBudgetSummary.status }}
                    </div>
                  </div>
                </div>
                <p class="mb-3">
                  Edite os valores que serão exibidos no PDF ou informe um percentual de acréscimo para aplicar automaticamente.
                </p>
                <div class="row mb-3">
                  <div class="col-12 col-md-6 mb-3">
                    <label for="pdfCashValue" class="form-label">Valor à vista (R$)</label>
                    <input
                      id="pdfCashValue"
                      v-model="pdfCashValue"
                      type="number"
                      class="form-control"
                      min="0"
                      step="0.01"
                      placeholder="0,00"
                      :disabled="generatingPdf"
                      @input="updateCashValueFromInput"
                    >
                  </div>
                  <div class="col-12 col-md-6 mb-3">
                    <label for="pdfInstallmentValue" class="form-label">Valor a prazo (R$)</label>
                    <input
                      id="pdfInstallmentValue"
                      v-model="pdfInstallmentValue"
                      type="number"
                      class="form-control"
                      min="0"
                      step="0.01"
                      placeholder="0,00"
                      :disabled="generatingPdf || !hasInstallmentValue"
                      @input="updateInstallmentValueFromInput"
                    >
                    <small v-if="!hasInstallmentValue" class="text-muted">Não há valor a prazo para este orçamento</small>
                  </div>
                </div>
                <div class="mb-3">
                  <label for="pdfIncrease" class="form-label">Ou aplicar acréscimo (%)</label>
                  <input
                    id="pdfIncrease"
                    v-model="pdfPercentage"
                    type="number"
                    class="form-control"
                    min="0"
                    step="0.01"
                    placeholder="0"
                    :disabled="generatingPdf"
                    @input="applyPercentageToValues"
                  >
                </div>
                <div v-if="selectedBudgetSummary" class="border rounded p-3 bg-body-secondary">
                  <div class="d-flex justify-content-between fw-semibold mb-2 pb-2 border-bottom">
                    <span>Valor à vista para o PDF</span>
                    <span>{{ pdfTotals.totalFormatted }}</span>
                  </div>
                  <div v-if="pdfTotals.totalInstallments > 0" class="d-flex justify-content-between fw-semibold mt-2">
                    <span>Valor a prazo para o PDF</span>
                    <span>{{ pdfTotals.totalInstallmentsFormatted }}</span>
                  </div>
                </div>
                <p class="text-muted small mb-0 mt-3">
                  Os valores informados serão exibidos no PDF do orçamento.
                </p>
                <p v-if="pdfError" class="text-danger small mt-3 mb-0">
                  {{ pdfError }}
                </p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" :disabled="generatingPdf" @click="closeGeneratePdfModal">
                  Cancelar
                </button>
                <button type="button" class="btn btn-primary" :disabled="generatingPdf" @click="confirmGeneratePdf">
                  <span
                    v-if="generatingPdf"
                    class="spinner-border spinner-border-sm me-2"
                    role="status"
                    aria-hidden="true"
                  ></span>
                  Gerar PDF
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
import EmptyState from '@/components/empty-state/EmptyState.vue';
import BudgetDetailsModal from '@/components/budget/BudgetDetailsModal.vue';
import BudgetOrderModal from '@/components/budget/BudgetOrderModal.vue';
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

const isAdmin = computed(() => auth.user?.user_type_id === 2);
const showCancelModal = ref(false);
const budgetToCancel = ref(null);
const cancelling = ref(false);
const cancelError = ref('');
const showDetailsModal = ref(false);
const budgetToView = ref(null);
const showPdfModal = ref(false);
const budgetToGeneratePdf = ref(null);
const pdfPercentage = ref(0);
const pdfCashValue = ref(null);
const pdfInstallmentValue = ref(null);
const generatingPdf = ref(false);
const pdfError = ref('');
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
  budgetToView.value = budget;
  showDetailsModal.value = true;
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
  pdfPercentage.value = 0;
  pdfError.value = '';

  // Inicializar valores editáveis com os valores originais
  const rawTotal = Number(budget.total_amount ?? budget.totalAmount ?? 0);
  const rawTotalInstallments = Number(budget.total_amount_installments ?? budget.totalAmountInstallments ?? 0);
  pdfCashValue.value = Number.isFinite(rawTotal) && rawTotal > 0 ? rawTotal : null;
  pdfInstallmentValue.value = Number.isFinite(rawTotalInstallments) && rawTotalInstallments > 0 ? rawTotalInstallments : null;

  showPdfModal.value = true;
}

function closeGeneratePdfModal() {
  if (generatingPdf.value) {
    return;
  }

  showPdfModal.value = false;
  budgetToGeneratePdf.value = null;
  pdfPercentage.value = 0;
  pdfCashValue.value = null;
  pdfInstallmentValue.value = null;
  pdfError.value = '';
}

function parsePercentage(value) {
  if (value === null || value === undefined || value === '') {
    return 0;
  }

  const normalized = String(value).replace(',', '.');
  return Number(normalized);
}

const selectedBudgetSummary = computed(() => {
  const budget = budgetToGeneratePdf.value;

  if (!budget) {
    return null;
  }

  const rawTotal = Number(budget.total_amount ?? budget.totalAmount ?? 0);
  const total = Number.isFinite(rawTotal) ? rawTotal : 0;

  const rawTotalInstallments = Number(budget.total_amount_installments ?? budget.totalAmountInstallments ?? 0);
  const totalInstallments = Number.isFinite(rawTotalInstallments) ? rawTotalInstallments : 0;

  return {
    id: budget.id,
    name: budget.name ?? 'Não informado',
    total,
    formattedTotal: formatCurrency(total),
    totalInstallments,
    formattedTotalInstallments: formatCurrency(totalInstallments),
    deliveryTime: formatDeliveryTime(budget.delivery_time),
    status: budget.status ?? null,
  };
});

const parsedPdfPercentageValue = computed(() => {
  const parsed = parsePercentage(pdfPercentage.value);

  if (!Number.isFinite(parsed) || parsed < 0) {
    return 0;
  }

  return parsed;
});

const pdfPercentageDisplay = computed(() => {
  const value = parsedPdfPercentageValue.value;

  return value.toLocaleString('pt-BR', {
    minimumFractionDigits: value % 1 === 0 ? 0 : 2,
    maximumFractionDigits: 2,
  });
});

const hasInstallmentValue = computed(() => {
  return selectedBudgetSummary.value && selectedBudgetSummary.value.totalInstallments > 0;
});

const pdfTotals = computed(() => {
  if (!selectedBudgetSummary.value) {
    return {
      increment: 0,
      incrementFormatted: formatCurrency(0),
      total: 0,
      totalFormatted: formatCurrency(0),
      totalInstallments: 0,
      totalInstallmentsFormatted: formatCurrency(0),
      incrementInstallments: 0,
      incrementInstallmentsFormatted: formatCurrency(0),
    };
  }

  // Se há valores editados manualmente, usar eles
  let updatedTotal = 0;
  let updatedTotalInstallments = 0;

  if (pdfCashValue.value !== null && Number.isFinite(Number(pdfCashValue.value))) {
    updatedTotal = Number(Number(pdfCashValue.value).toFixed(2));
  } else {
    // Caso contrário, calcular com porcentagem
    const baseTotal = selectedBudgetSummary.value.total;
    const increment = Number((baseTotal * (parsedPdfPercentageValue.value / 100)).toFixed(2));
    updatedTotal = Number((baseTotal + increment).toFixed(2));
  }

  if (pdfInstallmentValue.value !== null && Number.isFinite(Number(pdfInstallmentValue.value))) {
    updatedTotalInstallments = Number(Number(pdfInstallmentValue.value).toFixed(2));
  } else if (hasInstallmentValue.value) {
    // Caso contrário, calcular com porcentagem
    const baseTotalInstallments = selectedBudgetSummary.value.totalInstallments;
    const incrementInstallments = Number((baseTotalInstallments * (parsedPdfPercentageValue.value / 100)).toFixed(2));
    updatedTotalInstallments = Number((baseTotalInstallments + incrementInstallments).toFixed(2));
  }

  return {
    total: updatedTotal,
    totalFormatted: formatCurrency(updatedTotal),
    totalInstallments: updatedTotalInstallments,
    totalInstallmentsFormatted: formatCurrency(updatedTotalInstallments),
  };
});

function updateCashValueFromInput() {
  // Limpar porcentagem quando editar valor diretamente
  pdfPercentage.value = 0;
}

function updateInstallmentValueFromInput() {
  // Limpar porcentagem quando editar valor diretamente
  pdfPercentage.value = 0;
}

function applyPercentageToValues() {
  if (!selectedBudgetSummary.value) return;

  const parsed = parsePercentage(pdfPercentage.value);
  if (!Number.isFinite(parsed) || parsed < 0) return;

  // Aplicar porcentagem aos valores originais
  const baseTotal = selectedBudgetSummary.value.total;
  const increment = Number((baseTotal * (parsed / 100)).toFixed(2));
  pdfCashValue.value = Number((baseTotal + increment).toFixed(2));

  if (hasInstallmentValue.value) {
    const baseTotalInstallments = selectedBudgetSummary.value.totalInstallments;
    const incrementInstallments = Number((baseTotalInstallments * (parsed / 100)).toFixed(2));
    pdfInstallmentValue.value = Number((baseTotalInstallments + incrementInstallments).toFixed(2));
  }
}

async function confirmGeneratePdf() {
  if (!budgetToGeneratePdf.value?.id) {
    return;
  }

  // Validar valores
  const cashValue = pdfCashValue.value !== null && Number.isFinite(Number(pdfCashValue.value))
    ? Number(Number(pdfCashValue.value).toFixed(2))
    : null;

  const installmentValue = pdfInstallmentValue.value !== null && Number.isFinite(Number(pdfInstallmentValue.value))
    ? Number(Number(pdfInstallmentValue.value).toFixed(2))
    : null;

  if (cashValue === null || cashValue < 0) {
    pdfError.value = 'Informe um valor à vista válido.';
    return;
  }

  generatingPdf.value = true;
  pdfError.value = '';

  try {
    const payload = {
      id: budgetToGeneratePdf.value.id,
      cash_value: cashValue,
    };

    // Incluir valor a prazo apenas se for fornecido e válido
    if (installmentValue !== null && installmentValue >= 0) {
      payload.installment_value = installmentValue;
    }

    const response = await axios.post('v1/budgets/generate-pdf', payload, {
      responseType: 'blob',
    });

    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `orcamento-${budgetToGeneratePdf.value.id}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    closeGeneratePdfModal();
  } catch (error) {
    pdfError.value = 'Não foi possível gerar o PDF. Tente novamente.';
  } finally {
    generatingPdf.value = false;
  }
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
