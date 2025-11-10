<template>
  <section class="content">
    <Page title="Orçamentos">
      <div class="card">
        <div class="card-body p-0">
          <div v-if="loading" class="p-4 text-center text-muted">
            Carregando orçamentos...
          </div>

          <EmptyState v-else-if="budgets.length === 0" heading="Nenhum orçamento cadastrado" icon="file-alt" class="p-5">
            Crie um novo orçamento para começar.
          </EmptyState>

          <ul v-else class="list-group list-group-flush">
            <li v-for="budget in budgets" :key="budget.id" class="list-group-item">
              <div class="d-flex align-items-start justify-content-between gap-3">
                <div class="flex-grow-1">
                  <h5 class="mb-1 text-body fw-semibold">
                    {{ budget.name }}
                  </h5>
                  <div class="text-muted small">
                    <span class="fw-semibold text-dark">
                      {{ formatCurrency(budget.total_amount) }}
                    </span>
                    <span class="mx-2">•</span>
                    <span>
                      Prazo: {{ formatDeliveryTime(budget.delivery_time) }}
                    </span>
                  </div>
                  <div v-if="isCancelled(budget)" class="text-danger small fw-semibold mt-2">
                    Status: Cancelado
                  </div>
                </div>

                <div class="dropdown">
                  <button class="btn btn-link text-secondary px-2" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <i class="fa fa-ellipsis-v"></i>
                    <span class="visually-hidden">Opções</span>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <button class="dropdown-item" type="button">
                        Ver detalhes
                      </button>
                    </li>
                    <li>
                      <button class="dropdown-item" type="button" @click="openGeneratePdfModal(budget)">
                        Gerar PDF
                      </button>
                    </li>
                    <li v-if="budget.status !== 'cancelado'">
                      <button class="dropdown-item text-danger" type="button" @click="openCancelModal(budget)">
                        Cancelar
                      </button>
                    </li>
                  </ul>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </Page>

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
import Page from '@/components/page/Page.vue';
import EmptyState from '@/components/empty-state/EmptyState.vue';
import axios from 'axios';

const budgets = ref([]);
const loading = ref(true);
const showCancelModal = ref(false);
const budgetToCancel = ref(null);
const cancelling = ref(false);
const cancelError = ref('');
const showPdfModal = ref(false);
const budgetToGeneratePdf = ref(null);
const pdfPercentage = ref(0);
const generatingPdf = ref(false);
const pdfError = ref('');

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL'
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

function normalizeBudget(budget) {
  if (!budget) {
    return {
      id: null,
      name: '',
      total_amount: 0,
      delivery_time: null,
      status: null,
    };
  }

  const totalAmount = budget.total_amount ?? budget.totalAmount ?? 0;
  const deliveryTime = budget.delivery_time ?? budget.deliveryTime ?? null;
  const status = budget.status ?? budget.Status ?? null;

  return {
    ...budget,
    name: budget.name ?? '',
    total_amount: totalAmount,
    delivery_time: deliveryTime,
    status,
  };
}

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
</script>
