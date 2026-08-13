<template>
  <section class="content mb-3">
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

          <div class="card mt-3">
            <div class="card-body">
              <h5 class="card-title">Preços por ambiente</h5>
              <p class="text-muted small mb-3">
                Edite os valores para atualizar o preview e os totais. Não altera o markup.
              </p>

              <div
                v-for="item in editableItems"
                :key="item.id"
                class="border rounded p-3 mb-3"
              >
                <div class="fw-semibold mb-2">{{ item.title }}</div>
                <div class="row g-2">
                  <div class="col-6">
                    <label class="form-label small mb-1">À vista</label>
                    <money
                      v-model.number="item.total"
                      v-bind="moneyConfig"
                      class="form-control form-control-sm"
                      @change="onItemPriceChange"
                    />
                  </div>
                  <div class="col-6">
                    <label class="form-label small mb-1">A prazo</label>
                    <money
                      v-model.number="item.installment_total"
                      v-bind="moneyConfig"
                      class="form-control form-control-sm"
                      @change="onItemPriceChange"
                    />
                  </div>
                </div>
              </div>

              <div v-if="!editableItems.length" class="text-muted small">
                Nenhum ambiente carregado.
              </div>
            </div>
          </div>
        </div>
      </div>
    </Page>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { useRoute } from 'vue-router';
import Page from '@/components/page/Page.vue';
import { useFormatting } from '@/composables/useFormatting';
import { useToast } from '@/composables/useToast';
import { budgetPdfService } from '@/services/budgetPdfService';
import { budgetService } from '@/services/budgetService';
import { http } from '@/lib/http';

const toast = useToast();
const route = useRoute();
const { formatCurrency } = useFormatting();

const moneyConfig = {
  decimal: ',',
  thousands: '.',
  precision: 2,
  prefix: '',
  allowBlank: false,
  min: 0,
  max: null,
  disableNegative: true,
  minimumNumberOfCharacters: 0,
};

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
const editableItems = ref([]);
const syncingFromMarkup = ref(false);

const freightCost = computed(() => {
  const raw =
    budget.value?.selected_carrier_price ?? budget.value?.carrier_price ?? 0;
  return parseFloat(raw) || 0;
});

const computedTotalCash = computed(() => {
  const itemsSum = editableItems.value.reduce(
    (sum, item) => sum + (Number(item.total) || 0),
    0,
  );
  return itemsSum + freightCost.value;
});

const computedTotalInstallment = computed(() => {
  const itemsSum = editableItems.value.reduce(
    (sum, item) => sum + (Number(item.installment_total) || 0),
    0,
  );
  return itemsSum + freightCost.value;
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
      await applyMarkup();
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

async function fetchPreviewItems(markup) {
  if (!budget.value?.id) {
    return [];
  }

  const { data } = await http.get(`/v1/budgets/${budget.value.id}/preview-items`, {
    params: {
      mockup_percentage: markup,
    },
  });

  return data?.data?.items ?? [];
}

async function generatePdf() {
  if (!budget.value?.id) return;

  generatingPdf.value = true;
  try {
    await budgetPdfService.generatePdf(
      budget.value.id,
      computedTotalCash.value,
      computedTotalInstallment.value,
      appliedMarkup.value,
      pdfObservations.value.trim(),
      editableItems.value.map((item) => ({
        id: item.id,
        total: Number(item.total) || 0,
        installment_total: Number(item.installment_total) || 0,
      })),
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

  editableItems.value.forEach((item, index) => {
    params.set(`items[${index}][id]`, String(item.id));
    params.set(`items[${index}][total]`, String(Number(item.total) || 0));
    params.set(
      `items[${index}][installment_total]`,
      String(Number(item.installment_total) || 0),
    );
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

const refreshPreviewDebounced = useDebounceFn(() => {
  refreshPreview();
}, 400);

function onItemPriceChange() {
  if (syncingFromMarkup.value) {
    return;
  }
  refreshPreviewDebounced();
}

async function applyMarkup() {
  enforceMockupMin();
  const markup = Number(mockupPercentage.value);
  appliedMarkup.value = Number.isFinite(markup) && markup > 0 ? markup : MOCKUP_MIN;

  try {
    syncingFromMarkup.value = true;
    const items = await fetchPreviewItems(appliedMarkup.value);
    editableItems.value = items.map((item) => ({
      id: item.id,
      title: item.title,
      total: Number(item.total) || 0,
      installment_total: Number(item.installment_total) || 0,
    }));
  } catch (err) {
    console.error('Erro ao carregar preços dos ambientes:', err);
    toast.error('Não foi possível recalcular os preços dos ambientes.');
  } finally {
    syncingFromMarkup.value = false;
  }

  refreshPreview();
}

watch(
  editableItems,
  () => {
    if (syncingFromMarkup.value || !budget.value?.id) {
      return;
    }
    refreshPreviewDebounced();
  },
  { deep: true },
);

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
