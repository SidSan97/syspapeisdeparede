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
          <div class="pdf-header-top">
            <!--div class="pdf-logo">
              <div class="logo-placeholder">
                <IconPhoto />

                <span>Logo</span>
              </div>
            </div-->

            <div v-if="dropshippingData" class="pdf-header-client">
              <p class="pdf-header-client-name">
                {{ dropshippingData?.name || '—' }}
              </p>
              <p v-if="dropshippingData?.cpf_cnpj">
                {{ dropshippingData.cpf_cnpj }}
              </p>
              <p v-if="formatAddressLine1(dropshippingData) !== '—'">
                {{ formatAddressLine1(dropshippingData) }}
              </p>
              <p v-if="formatAddressLine2(dropshippingData)">
                {{ formatAddressLine2(dropshippingData) }}
              </p>
              <p v-if="dropshippingData?.phone">
                Fone: {{ formatPhone(dropshippingData.phone) }}
              </p>
              <p v-if="dropshippingData?.email">
                {{ dropshippingData.email }}
              </p>
            </div>
          </div>

          <h1 class="pdf-title pdf-title-centered">
            Proposta Comercial Nº {{ budget?.id || '—' }}
          </h1>
        </div>

        <!-- Tabela de Itens -->
        <div class="pdf-items-section">
          <table class="pdf-items-table">
            <thead>
              <tr>
                <th>Item</th>
                <th>Modelo</th>
                <th>Metros</th>
                <th>Preço</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(room, roomIndex) in roomSummaries" :key="`room-${roomIndex}`">
                <td>
                  <strong>{{ room.name }}</strong>
                  <div class="wall-details">
                    <div
                      v-for="(wall, wallIndex) in room.walls"
                      :key="`wall-${roomIndex}-${wallIndex}`"
                      :style="wallIndex > 0 ? { marginTop: '5px' } : undefined"
                    >
                      <div>{{ wall.details.main }}</div>
                      <div
                        v-for="(continuationLine, contIndex) in wall.details.continuations"
                        :key="`cont-${roomIndex}-${wallIndex}-${contIndex}`"
                        class="wall-continuation-line"
                      >
                        {{ continuationLine }}
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <div
                    v-for="(wall, wallIndex) in room.walls"
                    :key="`model-${roomIndex}-${wallIndex}`"
                    :style="wallIndex > 0 ? { marginTop: '5px' } : undefined"
                  >
                    {{ wall.modelName }}
                  </div>
                </td>
                <td>{{ formatNumber(room.meters) }}</td>
                <td>{{ formatCurrency(getRoomDisplayPrice(room)) }}</td>
              </tr>
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
        </div>

        <!-- Observações -->
        <div class="pdf-observations-section">
          <strong class="text-black">Observações</strong>
          <textarea
            v-model="pdfObservations"
            class="form-control pdf-observations-input bg-light text-black"
            rows="4"
            placeholder="Digite observações que devem aparecer na impressão..."
          ></textarea>
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
  formatNumber,
  formatDeliveryTime,
  formatPhone,
  formatAddressLine1,
  formatAddressLine2,
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
const pdfObservations = ref('');

const {
  getCarrierName,
  totalRooms,
  totalItems,
  totalMeters,
  totalOrder,
  totalModelsCost,
  roomSummaries,
  pricePerMeterCash,
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

function getRoomDisplayPrice(room) {
  const freight = Number(budget.value?.selected_carrier_price ?? 0);
  const models = totalModelsCost.value;
  const wallpaperTotal = Math.max(0, editableTotalCash.value - freight - models);

  if (totalMeters.value <= 0) {
    return room.modelCost;
  }

  return (room.meters / totalMeters.value) * wallpaperTotal + room.modelCost;
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
