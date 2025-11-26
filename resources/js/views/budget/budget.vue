<template>
  <section class="content">
    <Page title="Orçamentos" back-to="/">
      <template #actions>
        <button class="btn btn-primary btn-lg" type="button" @click="goToCreateBudget">
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
                  <div class="border rounded p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="text-muted small">Orçamento</span>
                      <span class="badge bg-secondary">#{{ selectedBudgetSummary.id }}</span>
                    </div>
                    <div class="fw-semibold">{{ selectedBudgetSummary.name }}</div>
                    <div class="text-muted small mt-2">
                      Valor original: <span class="fw-semibold">{{ selectedBudgetSummary.formattedTotal }}</span>
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
                  Informe o percentual de acréscimo que deseja aplicar ao valor total antes de gerar o PDF.
                </p>
                <div class="mb-3">
                  <label for="pdfIncrease" class="form-label">Acréscimo (%)</label>
                  <input
                    id="pdfIncrease"
                    v-model="pdfPercentage"
                    type="number"
                    class="form-control"
                    min="0"
                    step="0.01"
                    placeholder="0"
                    :disabled="generatingPdf"
                  >
                </div>
                <div v-if="selectedBudgetSummary" class="border rounded p-3 bg-body-secondary">
                  <div class="d-flex justify-content-between text-muted small">
                    <span>Acréscimo ({{ pdfPercentageDisplay }}%)</span>
                    <span>{{ pdfTotals.incrementFormatted }}</span>
                  </div>
                  <div class="d-flex justify-content-between fw-semibold mt-2">
                    <span>Valor atualizado</span>
                    <span>{{ pdfTotals.totalFormatted }}</span>
                  </div>
                </div>
                <p class="text-muted small mb-0 mt-3">
                  O valor final apresentado no PDF será atualizado com o acréscimo informado.
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
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import Page from '@/components/page/Page.vue';
import EmptyState from '@/components/empty-state/EmptyState.vue';
import BudgetDetailsModal from '@/components/budget/BudgetDetailsModal.vue';
import BudgetOrderModal from '@/components/budget/BudgetOrderModal.vue';
import axios from 'axios';

const budgets = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const statusFilter = ref('all');
const showCancelModal = ref(false);
const budgetToCancel = ref(null);
const cancelling = ref(false);
const cancelError = ref('');
const showDetailsModal = ref(false);
const budgetToView = ref(null);
const showPdfModal = ref(false);
const budgetToGeneratePdf = ref(null);
const pdfPercentage = ref(0);
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
  { label: 'Cancelado', value: 'cancelado' },
];

const filteredBudgets = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  const status = statusFilter.value;

  return budgets.value.filter((budget) => {
    const matchesQuery = !query
      || budget.name?.toLowerCase().includes(query)
      || String(budget.id).includes(query);

    const normalizedStatus = (budget.status || '').toString().toLowerCase();
    const matchesStatus = status === 'all' || normalizedStatus === status;

    return matchesQuery && matchesStatus;
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

onMounted(() => {
  fetchBudgets();
  document.title = 'Orçamentos';
});

function openGeneratePdfModal(budget) {
  budgetToGeneratePdf.value = budget;
  pdfPercentage.value = 0;
  pdfError.value = '';
  showPdfModal.value = true;
}

function closeGeneratePdfModal() {
  if (generatingPdf.value) {
    return;
  }

  showPdfModal.value = false;
  budgetToGeneratePdf.value = null;
  pdfPercentage.value = 0;
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

  return {
    id: budget.id,
    name: budget.name ?? 'Não informado',
    total,
    formattedTotal: formatCurrency(total),
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

const pdfTotals = computed(() => {
  if (!selectedBudgetSummary.value) {
    return {
      increment: 0,
      incrementFormatted: formatCurrency(0),
      total: 0,
      totalFormatted: formatCurrency(0),
    };
  }

  const baseTotal = selectedBudgetSummary.value.total;
  const increment = Number((baseTotal * (parsedPdfPercentageValue.value / 100)).toFixed(2));
  const updatedTotal = Number((baseTotal + increment).toFixed(2));

  return {
    increment,
    incrementFormatted: formatCurrency(increment),
    total: updatedTotal,
    totalFormatted: formatCurrency(updatedTotal),
  };
});

async function confirmGeneratePdf() {
  if (!budgetToGeneratePdf.value?.id) {
    return;
  }

  const parsed = parsePercentage(pdfPercentage.value);

  if (!Number.isFinite(parsed) || parsed < 0) {
    pdfError.value = 'Informe uma porcentagem válida.';
    return;
  }

  generatingPdf.value = true;
  pdfError.value = '';

  try {
    const response = await axios.post('v1/budgets/generate-pdf', {
      id: budgetToGeneratePdf.value.id,
      percentage: parsed,
    }, {
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
    height: 46px !important;
}
</style>
