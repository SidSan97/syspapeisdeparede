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
                  Altere o markup para recalcular os valores da proposta.
                </small>
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
import { computed, onMounted, ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { useRoute } from 'vue-router';
import Page from '@/components/page/Page.vue';
import { useFormatting } from '@/composables/useFormatting';
import { useToast } from '@/composables/useToast';
import { budgetPdfService } from '@/services/budgetPdfService';
import { budgetService } from '@/services/budgetService';

const toast = useToast();
const route = useRoute();
const { formatCurrency } = useFormatting();

// Estado do componente
const budget = ref(null);
const loading = ref(true);
const error = ref(null);
const generatingPdf = ref(false);
const MOCKUP_MIN = 2.1;
const mockupPercentage = ref(MOCKUP_MIN);
const pdfObservations = ref('');
const previewUrl = ref('');
const previewLoading = ref(false);

// Totais calculados a partir dos valores base do orçamento aplicando o markup.
// Somente leitura: o usuário ajusta o markup, nunca os totais diretamente.
const baseTotalCash = computed(() => parseFloat(budget.value?.total_amount || 0));
const baseTotalInstallment = computed(() =>
  parseFloat(budget.value?.total_amount_installments || 0),
);
const computedTotalCash = computed(
  () => Math.round(baseTotalCash.value * mockupPercentage.value * 100) / 100,
);
const computedTotalInstallment = computed(
  () => Math.round(baseTotalInstallment.value * mockupPercentage.value * 100) / 100,
);

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

  enforceMockupMin();
  generatingPdf.value = true;
  try {
    await budgetPdfService.generatePdf(
      budget.value.id,
      computedTotalCash.value,
      computedTotalInstallment.value,
      mockupPercentage.value,
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
    mockup_percentage: String(mockupPercentage.value ?? MOCKUP_MIN),
  });

  if (pdfObservations.value.trim()) {
    params.set('notes', pdfObservations.value.trim());
  }

  return `/budgets/${budget.value.id}/preview?${params.toString()}`;
}

const refreshPreviewUrl = useDebounceFn(() => {
  previewUrl.value = buildPreviewUrl();
}, 500);

// Monta o preview assim que o budget for carregado
watch(
  budget,
  () => {
    if (budget.value) {
      previewUrl.value = buildPreviewUrl();
    }
  },
  { immediate: true },
);

// Atualiza o iframe (com debounce) sempre que o markup ou as observações mudarem
watch([mockupPercentage, pdfObservations], () => {
  previewLoading.value = true;
  refreshPreviewUrl();
});

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
