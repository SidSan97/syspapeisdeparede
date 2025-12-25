<template>
  <div class="pdf-preview-container">
    <div class="pdf-preview-header">
      <button class="btn btn-primary" @click="goBack" style="background: #fff; border-color: #000; color: #000;">
        <i class="fa fa-arrow-left me-2"></i>
        Voltar
      </button>
      <button class="btn btn-success" @click="generatePdf" style="background: #000; border-color: #000; color: #fff;">
        <i class="fa fa-file-pdf me-2"></i>
        Gerar PDF
      </button>
    </div>

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
      <div v-if="budget?.comment_referring_model" class="pdf-observations-section">
        <strong>Observações</strong>
        <p class="mb-0">{{ budget.comment_referring_model }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
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

const dropshippingData = computed(() => {
  return budget.value?.dropshipping_data || null;
});

const totalRooms = computed(() => {
  if (!budget.value?.rooms) return 0;
  return budget.value.rooms.length;
});

const totalItems = computed(() => {
  if (!budget.value?.rooms) return 0;
  let count = 0;
  budget.value.rooms.forEach(room => {
    if (room.walls && Array.isArray(room.walls)) {
      count += room.walls.length;
    }
  });
  return count;
});

const totalMeters = computed(() => {
  if (!budget.value?.rooms) return 0;
  let total = 0;
  budget.value.rooms.forEach(room => {
    if (room.walls && Array.isArray(room.walls)) {
      room.walls.forEach(wall => {
        if (wall.total_area) {
          total += parseFloat(wall.total_area);
        } else if (wall.width && wall.height) {
          total += parseFloat(wall.width) * parseFloat(wall.height);
        }
      });
    }
  });
  return total;
});

const totalProducts = computed(() => {
  // Usar total_amount do budget se disponível, senão calcular
  if (budget.value?.total_amount !== undefined && budget.value?.total_amount !== null) {
    return parseFloat(budget.value.total_amount);
  }
  let total = 0;
  if (budget.value?.rooms) {
    budget.value.rooms.forEach(room => {
      if (room.walls && Array.isArray(room.walls)) {
        room.walls.forEach(wall => {
          total += calculateWallPrice(wall);
        });
      }
    });
  }
  return total;
});

const totalOrder = computed(() => {
  const products = totalProducts.value;
  const shipping = parseFloat(budget.value?.selected_carrier_price || 0);
  return products + shipping;
});

function calculateWallPrice(wall) {
  // Usar o preço da parede se disponível, senão calcular baseado na área
  if (wall.price !== undefined && wall.price !== null) {
    return parseFloat(wall.price);
  }
  if (wall.total_area) {
    // Se não tiver preço direto, usar área * preço por m² (assumindo R$ 10/m² como padrão)
    return parseFloat(wall.total_area) * 10;
  }
  if (wall.width && wall.height) {
    const area = parseFloat(wall.width) * parseFloat(wall.height);
    return area * 10;
  }
  return 0;
}

function formatWallDetails(wall) {
  const parts = [];
  if (wall.name) {
    parts.push(wall.name);
  }
  if (wall.width && wall.height) {
    parts.push(`${wall.width}m x ${wall.height}m`);
  }
  
  return parts.length > 0 ? parts.join(' | ') : 'Parede sem detalhes';
}

function formatDocument(doc) {
  if (!doc) return '—';
  const cleaned = doc.replace(/\D/g, '');
  if (cleaned.length === 11) {
    return cleaned.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
  }
  if (cleaned.length === 14) {
    return cleaned.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
  }
  return doc;
}

function formatPhone(phone) {
  if (!phone) return '—';
  const cleaned = phone.replace(/\D/g, '');
  if (cleaned.length === 10) {
    return cleaned.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
  }
  if (cleaned.length === 11) {
    return cleaned.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
  }
  return phone;
}

function formatAddressLine1(dropshipping) {
  if (!dropshipping) return '—';
  const parts = [];
  if (dropshipping.public_space) {
    parts.push(dropshipping.public_space);
  }
  if (dropshipping.number) {
    parts.push(`Nº ${dropshipping.number}`);
  }
  if (dropshipping.complement) {
    parts.push(dropshipping.complement);
  }
  if (dropshipping.neighborhood) {
    parts.push(`Bairro: ${dropshipping.neighborhood}`);
  }
  return parts.length > 0 ? parts.join('. ') : '—';
}

function formatAddressLine2(dropshipping) {
  if (!dropshipping) return '';
  const parts = [];
  if (dropshipping.cep) {
    const cep = dropshipping.cep.replace(/\D/g, '');
    const formattedCep = cep.length === 8 ? cep.replace(/(\d{5})(\d{3})/, '$1-$2') : dropshipping.cep;
    parts.push(formattedCep);
  }
  if (dropshipping.city && dropshipping.uf) {
    parts.push(`${dropshipping.city}, ${dropshipping.uf}`);
  }
  return parts.length > 0 ? parts.join(' - ') : '';
}

function formatDate(date) {
  if (!date) return '—';
  const d = new Date(date);
  if (isNaN(d.getTime())) return '—';
  return d.toLocaleDateString('pt-BR');
}

function formatEstimatedDate(deliveryTime) {
  if (!deliveryTime) return '—';
  const today = new Date();
  const estimated = new Date(today);
  estimated.setDate(today.getDate() + parseInt(deliveryTime));
  return estimated.toLocaleDateString('pt-BR');
}

function formatDeliveryTime(deliveryTime) {
  if (!deliveryTime) return 'Não informado';
  const days = parseInt(deliveryTime);
  return `${days} ${days === 1 ? 'dia' : 'dias'}`;
}

function formatCurrency(value) {
  if (value === null || value === undefined) {
    return 'R$ 0,00';
  }
  const numValue = parseFloat(value);
  if (isNaN(numValue)) {
    return 'R$ 0,00';
  }
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(numValue);
}

function getCarrierName(carrierName) {
  if (!carrierName) return null;
  // Extrair apenas o nome antes do hífen (ex: "Jadlog - Package" -> "Jadlog")
  if (carrierName.includes(' - ')) {
    return carrierName.split(' - ')[0].trim();
  }
  return carrierName.trim();
}

async function loadBudget() {
  loading.value = true;
  error.value = null;
  
  try {
    const { data } = await axios.get(`v1/budgets/${route.params.id}`);
    
    if (data.success && data.data) {
      budget.value = data.data;
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

function goBack() {
  router.back();
}

async function generatePdf() {
  if (!budget.value?.id) return;
  
  generatingPdf.value = true;
  try {
    const response = await axios.post(
      'v1/budgets/generate-pdf',
      {
        id: budget.value.id,
        total_amount: editableTotalCash.value,
        total_amount_installments: editableTotalInstallment.value,
        mockup_percentage: mockupPercentage.value,
      },
      {
        responseType: 'blob',
      }
    );

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `orcamento-${budget.value.id}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    console.error('Erro ao gerar PDF:', err);
    window.Toast?.fire({
      icon: 'error',
      title: 'Erro ao gerar PDF. Tente novamente.',
    });
  } finally {
    generatingPdf.value = false;
  }
}

onMounted(() => {
  document.title = 'Preview PDF - Orçamento';
  loadBudget();
});
</script>

<style scoped>
.pdf-preview-container {
  background: #ffffff;
  min-height: 100vh;
  padding: 20px;
}

.pdf-preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding: 15px;
  background: white;
  border: 1px solid #000;
}

.pdf-preview-content {
  background: white;
  padding: 40px;
  max-width: 210mm;
  margin: 0 auto;
  border: 1px solid #000;
}

/* Cabeçalho */
.pdf-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 2px solid #000;
}

.pdf-logo {
  flex: 0 0 150px;
}

.logo-placeholder {
  width: 120px;
  height: 80px;
  border: 2px solid #000;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #000;
  font-size: 12px;
  background: #fff;
}

.logo-placeholder i {
  font-size: 24px;
  margin-bottom: 5px;
}

.pdf-header-right {
  flex: 1;
  text-align: right;
}

.pdf-title {
  font-size: 24px;
  font-weight: bold;
  margin: 0 0 5px 0;
  color: #000;
}

.pdf-subtitle {
  font-size: 14px;
  color: #000;
  margin: 0;
}

/* Seção de Informações */
.pdf-info-section {
  display: flex;
  justify-content: space-between;
  margin-bottom: 30px;
  gap: 20px;
}

.pdf-info-table {
  width: 48%;
  border-collapse: collapse;
  border: 1px solid #000;
  font-size: 12px;
  background: #f0f0f0;
  font-weight: bold;
  color: #000;
}

.pdf-info-table td {
  padding: 9px 0 0 5px;
  border: 1px solid #000;
  vertical-align: top;
}

.pdf-info-value {
  background: #ffffff;
  color: #000;
}

.pdf-info-value div {
  margin: 0;
  line-height: 1.4;
}

/* Tabela de Itens */
.pdf-items-section {
  margin-bottom: 30px;
}

.pdf-items-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
  color: #232222;
}

.pdf-items-table thead {
  background: #ffffff;
}

.pdf-items-table th {
  padding: 10px 8px;
  text-align: left;
  border: 1px solid #000;
  color: #000;
  font-weight: bold;
  font-size: 11px;
}

.pdf-items-table td {
  padding: 8px;
  border: 1px solid #000;
  vertical-align: top;
  font-size: 12px;
}

.wall-details {
  font-size: 12px;
  color: #000;
  margin-top: 5px;
}

/* Resumo */
.pdf-summary-section {
  margin-bottom: 30px;
  padding: 20px;
  background: #ffffff;
  border: 1px solid #000;
  color: #000;
}

.pdf-summary-left p {
  margin: 8px 0;
  font-size: 13px;
  color: #000;
}

.pdf-summary-left p strong {
  color: #000;
}

.pdf-total-cash {
  font-size: 15px !important;
  font-weight: bold;
  margin-top: 12px !important;
  padding-top: 12px;
  border-top: 2px solid #000;
  color: #000;
}

.pdf-total-installment {
  font-size: 15px !important;
  font-weight: bold;
  margin-top: 8px !important;
  color: #000;
}

.pdf-mockup-input {
  font-size: 13px !important;
  margin-top: 12px !important;
  padding-top: 12px;
  border-top: 1px solid #ccc;
  color: #000;
}

.pdf-edit-icon {
  margin-left: 10px;
  cursor: pointer;
  color: #000;
  font-size: 14px;
  transition: color 0.2s;
}

.pdf-edit-icon:hover {
  color: #666;
}

.pdf-edit-input {
  margin-left: 10px;
  padding: 4px 8px;
  border: 1px solid #000;
  border-radius: 3px;
  font-size: 12px;
  width: 120px;
  color: #000;
  background: #fff;
}

.pdf-edit-input:focus {
  outline: none;
  border-color: #000;
  box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.1);
}

/* Entrega */
.pdf-shipping-section {
  display: flex;
  gap: 40px;
  margin-bottom: 30px;
}

.pdf-shipping-section .pdf-info-group {
  flex: 1;
  color: #1b1b1b
}

/* Observações */
.pdf-observations-section {
  margin-top: 30px;
  padding-top: 20px;
  border-top: 1px solid #000;
}

.pdf-observations-section strong {
  display: block;
  margin-bottom: 10px;
  font-size: 12px;
}

.pdf-observations-section p {
  font-size: 11px;
  color: #000;
  line-height: 1.5;
}

/* Estilos para impressão */
@media print {
  .pdf-preview-header {
    display: none;
  }

  .pdf-preview-container {
    background: white;
    padding: 0;
  }

  .pdf-preview-content {
    box-shadow: none;
    padding: 20mm;
  }
}
</style>

