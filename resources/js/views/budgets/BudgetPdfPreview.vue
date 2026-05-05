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

      <div v-else class="pdf-preview-content">
        <!-- Cabeçalho -->
        <div class="pdf-header">
          <div class="pdf-logo">
            <div class="logo-placeholder">
              <IconPhoto />

              <span>Logo</span>
            </div>
          </div>
          <div class="pdf-header-right">
            <h1 class="pdf-title">
              Nº {{ budget?.id || '—' }} -
              {{ budget?.name || '—' }}
            </h1>
          </div>
        </div>

        <!-- Informações do Orçamento e Cliente -->
        <div class="pdf-info-section">
          <table class="pdf-info-table" v-if="dropshippingData">
            <tbody>
              <tr>
                <td class="pdf-info-label">Cliente</td>
                <td class="pdf-info-value">
                  {{ dropshippingData?.name || '—' }}
                </td>
              </tr>
              <tr>
                <td class="pdf-info-label">Endereço</td>
                <td class="pdf-info-value">
                  <div>
                    {{ formatAddressLine1(dropshippingData) }}
                  </div>
                  <div>
                    {{ formatAddressLine2(dropshippingData) }}
                  </div>
                </td>
              </tr>
              <tr>
                <td class="pdf-info-label">Contato</td>
                <td class="pdf-info-value">
                  <div v-if="dropshippingData?.phone">
                    Fone:
                    {{ formatPhone(dropshippingData.phone) }}
                  </div>
                  <div v-if="dropshippingData?.email">
                    {{ dropshippingData.email }}
                  </div>
                  <div v-if="!dropshippingData?.phone && !dropshippingData?.email">—</div>
                </td>
              </tr>
            </tbody>
          </table>
          <table class="pdf-info-table">
            <tbody>
              <tr>
                <td class="pdf-info-label">Data</td>
                <td class="pdf-info-value">
                  {{ formatDateOnly(budget?.created_at) }}
                </td>
              </tr>
              <tr>
                <td class="pdf-info-label">Data prevista</td>
                <td class="pdf-info-value">
                  {{ formatEstimatedDate(budget?.delivery_time) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Tabela de Itens -->
        <div class="pdf-items-section">
          <table class="pdf-items-table">
            <thead>
              <tr>
                <th>Item</th>
                <th>Modelo</th>
                <th>Metros</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(room, roomIndex) in budget?.rooms" :key="room.id">
                <tr>
                  <td>
                    <strong>{{ room.name || `Ambiente ${roomIndex + 1}` }}</strong>
                    <div class="wall-details">
                      <template v-for="(wall, wallIndex) in room.walls" :key="wall.id">
                        <div v-if="wallIndex > 0" style="margin-top: 5px">
                          {{ formatWallDetails(wall) }}
                        </div>
                        <div v-else>
                          {{ formatWallDetails(wall) }}
                        </div>
                      </template>
                    </div>
                  </td>
                  <td>
                    <template v-for="(wall, wallIndex) in room.walls" :key="wall.id">
                      <div v-if="wallIndex === 0">
                        {{ wall.collection_model?.name || wall.collection_model_name || '—' }}
                      </div>
                      <div v-else style="margin-top: 5px">
                        {{ wall.collection_model?.name || wall.collection_model_name || '—' }}
                      </div>
                    </template>
                  </td>
                  <td>
                    {{ totalMeters.toFixed(2).replace('.', ',') }}
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <!-- Resumo do Pedido -->
        <div class="pdf-summary-section">
          <div class="pdf-summary-left">
            <p>
              <strong>Total de Ambientes:</strong>
              {{ totalRooms }}
            </p>
            <p><strong>Total de Paredes:</strong> {{ totalItems }}</p>
            <p>
              <strong>Metros:</strong>
              {{ totalMeters.toFixed(2).replace('.', ',') }}
            </p>
            <p>
              <strong>Frete:</strong>
              {{ formatCurrency(budget?.selected_carrier_price || 0) }}
            </p>
            <p>
              <strong>Previsão de entrega:</strong>
              {{ formatDeliveryTime(budget?.delivery_time) }}
            </p>
            <p class="pdf-total-cash">
              <strong>Total à Vista:</strong>
              {{ formatCurrency(editableTotalCash) }}
              <IconEdit @click="toggleEditCash" title="Editar valor" class="pdf-edit-icon" />

              <input
                v-if="editingCash"
                type="number"
                v-model.number="editableTotalCash"
                step="0.01"
                class="pdf-edit-input"
                placeholder="0.00"
                @blur="editingCash = false"
                @keyup.enter="editingCash = false"
                ref="cashInputRef"
              />
            </p>
            <p class="pdf-total-installment">
              <strong>Total a Prazo:</strong>
              {{ formatCurrency(editableTotalInstallment) }}
              <IconEdit @click="toggleEditInstallment" title="Editar valor" class="pdf-edit-icon" />
              <input
                v-if="editingInstallment"
                type="number"
                v-model.number="editableTotalInstallment"
                step="0.01"
                class="pdf-edit-input"
                placeholder="0.00"
                @blur="editingInstallment = false"
                @keyup.enter="editingInstallment = false"
                ref="installmentInputRef"
              />
            </p>
            <p class="pdf-mockup-input">
              <strong>Markup Multiplicador (Mín.: 2,1)</strong>
              <input
                type="number"
                v-model.number="mockupPercentage"
                step="0.01"
                class="pdf-edit-input"
                :placeholder="String(MOCKUP_MIN)"
                :min="MOCKUP_MIN"
                @blur="enforceMockupMin"
              />
            </p>
          </div>
        </div>

        <!-- Informações de Entrega -->
        <div class="pdf-shipping-section">
          <div class="pdf-info-group">
            <strong>Transportadora</strong>
            <p class="mb-0">
              {{ getCarrierName(budget?.selected_carrier_name) || '—' }}
            </p>
          </div>
          <div class="pdf-info-group">
            <strong>Modalidade de frete</strong>
            <p class="mb-0">Contratação do Frete por conta do Destinatário (FOB)</p>
          </div>
        </div>

        <!-- Observações -->
        <div v-if="allObservations.length > 0" class="pdf-observations-section">
          <strong>Observações</strong>
          <div v-for="(observation, index) in allObservations" :key="index" class="mb-2">
            <p class="mb-0">{{ observation }}</p>
          </div>
        </div>
      </div>
    </Page>
  </section>
</template>

<script setup>
import Page from '@/components/page/Page.vue';
import { useFormatting } from '@/composables/useFormatting';
import { useToast } from '@/composables/useToast';
import { useBudgetPdfGenerate } from '@/modules/budgets/composables/useBudgetPdfGenerate';
import { budgetPdfService } from '@/services/budgetPdfService';
import { budgetService } from '@/services/budgetService';
import { IconEdit, IconPhoto } from '@tabler/icons-vue';
import { onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';

const toast = useToast();
const route = useRoute();

const {
  formatCurrency,
  formatDeliveryTime,
  formatDateOnly,
  formatPhone,
  formatAddressLine1,
  formatAddressLine2,
  formatEstimatedDate,
} = useFormatting();

// Estado do componente
const budget = ref(null);
const loading = ref(true);
const error = ref(null);
const generatingPdf = ref(false);
const editableTotalCash = ref(0);
const editableTotalInstallment = ref(0);
const MOCKUP_MIN = 2.1;
const mockupPercentage = ref(MOCKUP_MIN);
const editingCash = ref(false);
const editingInstallment = ref(false);
const cashInputRef = ref(null);
const installmentInputRef = ref(null);

const {
  formatWallDetails,
  getCarrierName,
  totalRooms,
  totalItems,
  totalMeters,
  totalOrder,
  allObservations,
  dropshippingData,
} = useBudgetPdfGenerate(budget);

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
      editableTotalCash.value,
      editableTotalInstallment.value,
      mockupPercentage.value,
    );

    toast.success('PDF gerado com sucesso');
  } catch (err) {
    console.error('Erro ao gerar PDF:', err);
    toast.error('Erro ao gerar PDF. Tente novamente.');
  } finally {
    generatingPdf.value = false;
  }
}

function toggleEditCash() {
  editingCash.value = !editingCash.value;
  if (editingCash.value) {
    // Focar no input após ele aparecer
    setTimeout(() => {
      if (cashInputRef.value) {
        cashInputRef.value.focus();
        cashInputRef.value.select();
      }
    }, 10);
  }
}

function toggleEditInstallment() {
  editingInstallment.value = !editingInstallment.value;
  if (editingInstallment.value) {
    // Focar no input após ele aparecer
    setTimeout(() => {
      if (installmentInputRef.value) {
        installmentInputRef.value.focus();
        installmentInputRef.value.select();
      }
    }, 10);
  }
}

function enforceMockupMin() {
  const num = Number(mockupPercentage.value);
  if (Number.isNaN(num) || num < MOCKUP_MIN) {
    mockupPercentage.value = MOCKUP_MIN;
  }
}

// Watch para inicializar valores editáveis quando o budget for carregado
watch(
  [budget, totalOrder],
  () => {
    if (budget.value) {
      editableTotalCash.value = parseFloat(budget.value?.total_amount || totalOrder.value || 0);
      editableTotalInstallment.value = parseFloat(
        budget.value?.total_amount_installments || totalOrder.value || 0,
      );
    }
  },
  { immediate: true },
);

onMounted(() => {
  document.title = 'Preview PDF - Orçamento';
  loadBudget();
});
</script>

<style scoped>
@import '@/modules/budgets/css/budgetPdfPreview.css';
</style>
