<template>
  <section class="content">
    <Page title="Preview PDF - Orçamento" :back-to="{ name: 'ShowBudgetDetails', params: { id: route.params.id } }">
      <template #actions>
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
          <i v-else class="fa fa-file-pdf me-2"></i>
          Gerar PDF
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
            <i class="fa fa-image"></i>
            <span>Logo</span>
          </div>
        </div>
        <div class="pdf-header-right">
          <h1 class="pdf-title">Orçamento de venda Nº {{ budget?.id || '—' }}</h1>
          <p class="pdf-subtitle">{{ budget?.name || '—' }}</p>
        </div>
      </div>

      <!-- Informações do Orçamento e Cliente -->
      <div class="pdf-info-section">
        <table class="pdf-info-table">
          <tbody>
            <tr>
              <td class="pdf-info-label">Cliente</td>
              <td class="pdf-info-value">{{ dropshippingData?.name || '—' }}</td>
            </tr>
            <tr>
              <td class="pdf-info-label">Endereço</td>
              <td class="pdf-info-value">
                <div>{{ formatAddressLine1(dropshippingData) }}</div>
                <div>{{ formatAddressLine2(dropshippingData) }}</div>
              </td>
            </tr>
            <tr>
              <td class="pdf-info-label">Contato</td>
              <td class="pdf-info-value">
                <div v-if="dropshippingData?.phone">Fone: {{ formatPhone(dropshippingData.phone) }}</div>
                <div v-if="dropshippingData?.email">{{ dropshippingData.email }}</div>
                <div v-if="!dropshippingData?.phone && !dropshippingData?.email">—</div>
              </td>
            </tr>
          </tbody>
        </table>
        <table class="pdf-info-table">
          <tbody>
            <tr>
              <td class="pdf-info-label">Número do pedido</td>
              <td class="pdf-info-value">{{ budget?.id || '—' }}</td>
            </tr>
            <tr>
              <td class="pdf-info-label">Data</td>
              <td class="pdf-info-value">{{ formatDate(budget?.created_at) }}</td>
            </tr>
            <tr>
              <td class="pdf-info-label">Data prevista</td>
              <td class="pdf-info-value">{{ formatEstimatedDate(budget?.delivery_time) }}</td>
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
              <th>Quantidade de Paredes</th>
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
                      <div v-if="wallIndex > 0" style="margin-top: 5px;">
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
                    <div v-else style="margin-top: 5px;">
                      {{ wall.collection_model?.name || wall.collection_model_name || '—' }}
                    </div>
                  </template>
                </td>
                <td>{{ room.walls?.length || 0 }}</td>
                <td>{{ totalMeters.toFixed(2).replace('.', ',') }}</td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Resumo do Pedido -->
      <div class="pdf-summary-section">
        <div class="pdf-summary-left">
          <p><strong>Total de Ambientes:</strong> {{ totalRooms }}</p>
          <p><strong>Total de Paredes:</strong> {{ totalItems }}</p>
          <p><strong>Metros:</strong> {{ totalMeters.toFixed(2).replace('.', ',') }}</p>
          <p><strong>Frete:</strong> {{ formatCurrency(budget?.selected_carrier_price || 0) }}</p>
          <p><strong>Previsão de entrega:</strong> {{ formatDeliveryTime(budget?.delivery_time) }}</p>
          <p class="pdf-total-cash">
            <strong>Total à Vista:</strong> {{ formatCurrency(editableTotalCash) }}
            <i
              class="fa fa-edit pdf-edit-icon"
              @click="toggleEditCash"
              title="Editar valor"
            ></i>
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
            <strong>Total a Prazo:</strong> {{ formatCurrency(editableTotalInstallment) }}
            <i
              class="fa fa-edit pdf-edit-icon"
              @click="toggleEditInstallment"
              title="Editar valor"
            ></i>
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
            <strong>Ou insira o valor do mockup (%):</strong>
            <input
              type="number"
              v-model.number="mockupPercentage"
              step="0.01"
              class="pdf-edit-input"
              placeholder="0.00"
            />
          </p>
        </div>
      </div>

      <!-- Informações de Entrega -->
      <div class="pdf-shipping-section">
        <div class="pdf-info-group">
          <strong>Transportadora</strong>
          <p class="mb-0">{{ getCarrierName(budget?.selected_carrier_name) || '—' }}</p>
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
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Page from '@/components/page/Page.vue';
import { useBudgetService } from '@/modules/budgets/services/budgetService';
import { useBudgetPdfService } from '@/modules/budgets/services/budgetPdfService';
import { useBudgetPdfGenerate } from '@/modules/budgets/composables/useBudgetPdfGenerate';
import { useFormatting } from '@/composables/useFormatting';

const route = useRoute();
const router = useRouter();

// Services e composables
const budgetService = useBudgetService();
const pdfService = useBudgetPdfService();
const { formatCurrency, formatDeliveryTime, formatDate, formatPhone, formatAddressLine1, formatAddressLine2, formatEstimatedDate } = useFormatting();

// Estado do componente
const budget = ref(null);
const loading = ref(true);
const error = ref(null);
const generatingPdf = ref(false);
const editableTotalCash = ref(0);
const editableTotalInstallment = ref(0);
const mockupPercentage = ref(0);
const editingCash = ref(false);
const editingInstallment = ref(false);
const cashInputRef = ref(null);
const installmentInputRef = ref(null);

// Composable de PDF
const {
    formatWallDetails,
    getCarrierName,
    totalRooms,
    totalItems,
    totalMeters,
    totalProducts,
    totalOrder,
    allObservations,
    dropshippingData
} = useBudgetPdfGenerate(budget);

async function loadBudget() {
    loading.value = true;
    error.value = null;

    try {
        const payload = await budgetService.getBudget(route.params.id);
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

    generatingPdf.value = true;
    try {
        await pdfService.generatePdf(
            budget.value.id,
            editableTotalCash.value,
            editableTotalInstallment.value,
            mockupPercentage.value
        );
    } catch (err) {
        console.error('Erro ao gerar PDF:', err);
        if (window.Toast) {
            window.Toast.fire({
                icon: 'error',
                title: 'Erro ao gerar PDF. Tente novamente.',
            });
        }
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

// Watch para inicializar valores editáveis quando o budget for carregado
watch([budget, totalOrder], () => {
    if (budget.value) {
        editableTotalCash.value = parseFloat(budget.value?.total_amount || totalOrder.value || 0);
        editableTotalInstallment.value = parseFloat(budget.value?.total_amount_installments || totalOrder.value || 0);
        if (mockupPercentage.value === 0) {
            mockupPercentage.value = 0;
        }
    }
}, { immediate: true });

onMounted(() => {
    document.title = 'Preview PDF - Orçamento';
    loadBudget();
});
</script>

<style scoped>
  @import '@/modules/budgets/css/budgetPdfPreview.css';
</style>

