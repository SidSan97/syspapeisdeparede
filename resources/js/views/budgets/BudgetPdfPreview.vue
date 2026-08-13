<template>
  <section class="content">
    <Page
      title="Orçamento"
      :back-to="{ name: 'budgets.show', params: { id: route.params.id } }"
      :breadcrumbs="routes"
    >
      <template #extra>
        <button
          type="button"
          class="btn btn-primary"
          @click="generatePdf"
          :disabled="generatingPdf || !budget"
        >
          <span
            v-if="generatingPdf"
            class="spinner-border spinner-border-sm me-2"
            role="status"
            aria-hidden="true"
          ></span>
          Salvar em PDF
        </button>
      </template>

      <div v-if="loading" class="text-center p-5">
        <div class="spinner-border" role="status">
          <span class="visually-hidden">Carregando...</span>
        </div>
      </div>

      <div v-else-if="error" class="alert alert-danger m-4">
        {{ error }}
      </div>

      <div v-else class="row g-4 pdf-preview-layout">
        <div class="col-lg-8">
          <div class="pdf-preview-frame-wrapper">
            <div v-if="previewLoading" class="pdf-preview-loading">
              <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Atualizando...</span>
              </div>
            </div>
            <iframe
              v-if="previewUrl"
              :src="previewUrl"
              class="pdf-preview-iframe"
              title="Preview do orçamento"
              @load="previewLoading = false"
            ></iframe>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Valores da proposta</h5>
              <div class="mb-3">
                <label class="form-label">Markup Multiplicador (Mín.: {{ MOCKUP_MIN }})</label>
                <input
                  type="number"
                  v-model.number="mockupPercentage"
                  step="0.01"
                  class="form-control"
                  :placeholder="String(MOCKUP_MIN)"
                  :min="MOCKUP_MIN"
                  @blur="enforceMockupMin"
                />
                <small class="text-muted">
                  Informe o markup e clique em aplicar para recalcular a proposta.
                </small>
                <br />

                <button type="button" class="btn btn-sm btn-primary mt-2" @click="applyMarkup">
                  Aplicar markup
                </button>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Total à Vista</label>
                    <input
                      type="text"
                      class="form-control-plaintext fw-bold"
                      readonly
                      :value="formatCurrency(computedTotalCash)"
                    />
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Total a Prazo</label>
                    <input
                      type="text"
                      class="form-control-plaintext fw-bold"
                      readonly
                      :value="formatCurrency(computedTotalInstallment)"
                    />
                  </div>
                </div>
              </div>

              <div class="mb-0">
                <label class="form-label">Observações</label>
                <textarea
                  v-model="pdfObservations"
                  class="form-control"
                  rows="4"
                  placeholder="Digite observações que devem aparecer na impressão..."
                ></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Page>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import Page from '@/components/page/Page.vue';
import { useFormatting } from '@/composables/useFormatting';
import { useToast } from '@/composables/useToast';
import { budgetPdfService } from '@/services/budgetPdfService';
import { budgetService } from '@/services/budgetService';

const toast = useToast();
const route = useRoute();
const { formatCurrency } = useFormatting();

const budget = ref(null);
const loading = ref(true);
const error = ref(null);
const generatingPdf = ref(false);
const MOCKUP_MIN = 2.1;
const mockupPercentage = ref(MOCKUP_MIN);
const appliedMarkup = ref(MOCKUP_MIN);
const pdfObservations = ref('');
const previewUrl = ref('');
const previewLoading = ref(false);

// Totais usam só o markup aplicado (botão). Default 2.1. Frete estático.
const baseTotalCash = computed(() => parseFloat(budget.value?.total_amount || 0) || 0);
const baseTotalInstallment = computed(
  () => parseFloat(budget.value?.total_amount_installments || 0) || 0,
);
const freightCost = computed(() => {
  const raw =
    budget.value?.selected_carrier_price ?? budget.value?.carrier_price ?? 0;
  return parseFloat(raw) || 0;
});
const computedTotalCash = computed(() => {
  const markup = appliedMarkup.value || MOCKUP_MIN;
  const products = Math.max(0, baseTotalCash.value - freightCost.value);
  return products * markup + freightCost.value;
});
const computedTotalInstallment = computed(() => {
  const markup = appliedMarkup.value || MOCKUP_MIN;
  const products = Math.max(0, baseTotalInstallment.value - freightCost.value);
  return products * markup + freightCost.value;
});

const routes = [
  { path: '/', breadcrumbName: 'Início' },
  { path: '/budgets', breadcrumbName: 'Orçamentos' },
  { path: '#', breadcrumbName: 'Imprimir' },
];

async function loadBudget() {
  loading.value = true;
  error.value = null;

  try {
    const payload = await budgetService.find(route.params.id);
    if (payload) {
      budget.value = payload;
      mockupPercentage.value = MOCKUP_MIN;
      applyMarkup();
    } else {
      error.value = 'Orçamento não encontrado';
    }
  } catch (err) {
    console.error('Erro ao carregar orçamento:', err);
    error.value = err.response?.data?.message || 'Erro ao carregar orçamento';
  } finally {
    loading.value = false;
  }
}

async function generatePdf() {
  if (!budget.value?.id) return;

  applyMarkup();
  generatingPdf.value = true;
  try {
    await budgetPdfService.generatePdf(
      budget.value.id,
      computedTotalCash.value,
      computedTotalInstallment.value,
      appliedMarkup.value,
      pdfObservations.value.trim(),
    );

    toast.success('PDF gerado com sucesso');
  } catch (err) {
    console.error('Erro ao gerar PDF:', err);
    toast.error('Erro ao gerar PDF. Tente novamente.');
  } finally {
    generatingPdf.value = false;
  }
}

function enforceMockupMin() {
  const num = Number(mockupPercentage.value);
  if (Number.isNaN(num) || num < MOCKUP_MIN) {
    mockupPercentage.value = MOCKUP_MIN;
  }
}

function buildPreviewUrl() {
  if (!budget.value?.id) {
    return '';
  }

  const params = new URLSearchParams({
    mockup_percentage: String(appliedMarkup.value ?? MOCKUP_MIN),
    total_amount: String(computedTotalCash.value),
    total_amount_installments: String(computedTotalInstallment.value),
  });

  if (pdfObservations.value.trim()) {
    params.set('notes', pdfObservations.value.trim());
  }

  return `${window.LaravelApp.appUrl}budgets/${budget.value.id}/preview?${params.toString()}`;
}

function refreshPreview() {
  if (!budget.value?.id) {
    return;
  }

  previewLoading.value = true;
  previewUrl.value = buildPreviewUrl();
}

function applyMarkup() {
  enforceMockupMin();
  const markup = Number(mockupPercentage.value);
  appliedMarkup.value = Number.isFinite(markup) && markup > 0 ? markup : MOCKUP_MIN;
  refreshPreview();
}

onMounted(() => {
  document.title = 'Preview PDF - Orçamento';
  loadBudget();
});
</script>

<style scoped>
.pdf-preview-layout {
  align-items: stretch;
}

.pdf-preview-frame-wrapper {
  position: relative;
  background: #f5f5f5;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  padding: 12px;
  height: 100%;
}

.pdf-preview-loading {
  position: absolute;
  top: 20px;
  right: 20px;
  z-index: 1;
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid #dee2e6;
  border-radius: 50%;
  padding: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pdf-preview-iframe {
  width: 100%;
  min-height: 80vh;
  border: 1px solid #000;
  background: #fff;
}
</style>
