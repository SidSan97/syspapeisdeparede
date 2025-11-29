<template>
  <Teleport to="body">
    <div
      ref="pdfModalElement"
      class="modal fade"
      tabindex="-1"
      role="dialog"
      aria-labelledby="generatePdfModalLabel"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="generatePdfModalLabel">Gerar PDF do orçamento</h5>
            <button type="button" class="btn-close" aria-label="Close" data-bs-dismiss="modal"></button>
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
            <button type="button" class="btn btn-outline-secondary" :disabled="generatingPdf" data-bs-dismiss="modal">
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
  </Teleport>
</template>

<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  budget: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'success']);

const pdfPercentage = ref(0);
const pdfCashValue = ref(null);
const pdfInstallmentValue = ref(null);
const generatingPdf = ref(false);
const pdfError = ref('');
const pdfModalElement = ref(null);
let pdfModalInstance = null;
let pdfModalHiddenHandler = null;

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

function parsePercentage(value) {
  if (value === null || value === undefined || value === '') {
    return 0;
  }

  const normalized = String(value).replace(',', '.');
  return Number(normalized);
}

const selectedBudgetSummary = computed(() => {
  const budget = props.budget;

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

const hasInstallmentValue = computed(() => {
  return selectedBudgetSummary.value && selectedBudgetSummary.value.totalInstallments > 0;
});

const pdfTotals = computed(() => {
  if (!selectedBudgetSummary.value) {
    return {
      total: 0,
      totalFormatted: formatCurrency(0),
      totalInstallments: 0,
      totalInstallmentsFormatted: formatCurrency(0),
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
  if (!props.budget?.id) {
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
      id: props.budget.id,
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
    link.download = `orcamento-${props.budget.id}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    emit('success');
    handleClose();
  } catch (error) {
    pdfError.value = 'Não foi possível gerar o PDF. Tente novamente.';
  } finally {
    generatingPdf.value = false;
  }
}

function handleClose() {
  if (generatingPdf.value) {
    return;
  }

  pdfPercentage.value = 0;
  pdfCashValue.value = null;
  pdfInstallmentValue.value = null;
  pdfError.value = '';
  emit('close');
}

function initializePdfModal() {
  if (!pdfModalElement.value || pdfModalInstance) {
    return;
  }

  pdfModalInstance = new window.bootstrap.Modal(pdfModalElement.value, {
    backdrop: true,
    keyboard: true,
    focus: true,
  });

  // Escutar evento de fechamento do Bootstrap
  pdfModalHiddenHandler = () => {
    handleClose();
  };
  pdfModalElement.value.addEventListener('hidden.bs.modal', pdfModalHiddenHandler);
}

function showPdfModalInstance() {
  if (!pdfModalInstance && pdfModalElement.value) {
    initializePdfModal();
  }
  if (pdfModalInstance) {
    pdfModalInstance.show();
  }
}

function hidePdfModal() {
  if (pdfModalInstance) {
    pdfModalInstance.hide();
  }
}

function disposePdfModal() {
  if (pdfModalElement.value && pdfModalHiddenHandler) {
    pdfModalElement.value.removeEventListener('hidden.bs.modal', pdfModalHiddenHandler);
    pdfModalHiddenHandler = null;
  }
  if (pdfModalInstance) {
    pdfModalInstance.dispose();
    pdfModalInstance = null;
  }
}

// Observar mudanças na prop visible
watch(
  () => props.visible,
  (newValue) => {
    if (newValue) {
      nextTick(() => {
        showPdfModalInstance();
      });
    } else {
      hidePdfModal();
    }
  },
  { immediate: true }
);

// Observar mudanças no budget para inicializar valores
watch(
  () => props.budget,
  (newBudget) => {
    if (newBudget && props.visible) {
      pdfPercentage.value = 0;
      pdfError.value = '';

      // Inicializar valores editáveis com os valores originais
      const rawTotal = Number(newBudget.total_amount ?? newBudget.totalAmount ?? 0);
      const rawTotalInstallments = Number(newBudget.total_amount_installments ?? newBudget.totalAmountInstallments ?? 0);
      pdfCashValue.value = Number.isFinite(rawTotal) && rawTotal > 0 ? rawTotal : null;
      pdfInstallmentValue.value = Number.isFinite(rawTotalInstallments) && rawTotalInstallments > 0 ? rawTotalInstallments : null;
    }
  },
  { immediate: true }
);

onMounted(() => {
  if (props.visible) {
    nextTick(() => {
      initializePdfModal();
      showPdfModalInstance();
    });
  }
});

onBeforeUnmount(() => {
  disposePdfModal();
});
</script>

<style scoped>
.form-label {
  color: var(--bs-body-color);
}

.form-control {
  background-color: var(--bs-body-bg);
  color: var(--bs-body-color);
  border-color: var(--bs-border-color);
}

.form-control:focus {
  background-color: var(--bs-body-bg);
  color: var(--bs-body-color);
  border-color: var(--bs-primary);
  box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
}
</style>

