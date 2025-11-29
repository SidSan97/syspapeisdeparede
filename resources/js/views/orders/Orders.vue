<template>
  <section class="content">
    <Page title="Pedidos">
      <div class="card">
        <div class="card-body p-0">
          <div v-if="loading" class="p-4 text-center text-muted">
            Carregando pedidos...
          </div>

          <EmptyState
            v-else-if="pedidos.length === 0"
            heading="Nenhum pedido encontrado"
            icon="box"
            class="p-5"
          >
            Não há pedidos pendentes de revisão ou aprovados no momento.
          </EmptyState>

          <div v-else class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Nome</th>
                  <th class="text-end">Valor Total</th>
                  <th>Prazo de Entrega</th>
                  <th>Status</th>
                  <th class="text-center">Ações</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="pedido in pedidos" :key="pedido.id">
                  <td>
                    <button
                      class="btn btn-link text-start p-0 text-decoration-none fw-semibold"
                      @click="openDetailsModal(pedido)"
                    >
                      {{ pedido.name }}
                    </button>
                  </td>
                  <td class="text-end">
                    <span class="fw-semibold">{{ formatCurrency(pedido.total_amount) }}</span>
                  </td>
                  <td>{{ formatDeliveryTime(pedido.delivery_time) }}</td>
                  <td>
                    <span
                      class="badge"
                      :class="{
                        'bg-warning text-dark': pedido.status === 'Pendente de Revisão',
                        'bg-success': pedido.status === 'Aprovado',
                      }"
                    >
                      {{ pedido.status }}
                    </span>
                  </td>
                  <td class="text-center">
                    <div class="d-flex gap-2 justify-content-center">
                      <button
                        class="btn btn-sm btn-outline-primary"
                        @click="openDetailsModal(pedido)"
                      >
                        Ver detalhes
                      </button>
                      <button
                        v-if="pedido.status !== 'Aprovado' && canRegisterPayment"
                        class="btn btn-sm btn-primary"
                        @click="openPaymentModal(pedido)"
                      >
                        Registrar pagamento
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </Page>

    <PedidoDetailsModal
      :visible="showDetailsModal"
      :pedido="selectedPedido"
      @close="closeDetailsModal"
      @approve="handleApprove"
    />

    <OrdersRegisterPayment
      :visible="showPaymentModal"
      :pedido="paymentPedido"
      @close="closePaymentModal"
      @success="handlePaymentSuccess"
    />
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Page from '../../components/page/Page.vue';
import EmptyState from '../../components/empty-state/EmptyState.vue';
import PedidoDetailsModal from './components/OrdersDetailsModal.vue';
import OrdersRegisterPayment from './components/OrdersRegisterPayment.vue';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const pedidos = ref([]);
const loading = ref(false);
const showDetailsModal = ref(false);
const selectedPedido = ref(null);
const showPaymentModal = ref(false);
const paymentPedido = ref(null);

import { USER_TYPES } from '@/constants/userTypes';

const canRegisterPayment = computed(() => {
  return auth.user?.user_type_id === USER_TYPES.ADMIN;
});

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

function normalizePedido(pedido) {
  if (!pedido) {
    return null;
  }

  return {
    ...pedido,
    name: pedido.name ?? 'Não informado',
    total_amount: Number(pedido.total_amount ?? pedido.totalAmount ?? 0),
    delivery_time: pedido.delivery_time ?? pedido.deliveryTime ?? null,
    status: pedido.status ?? null,
  };
}

async function fetchPedidos() {
  try {
    loading.value = true;

    const { data } = await axios.get('v1/budgets/orders');

    const payload = Array.isArray(data?.data)
      ? data.data.map(normalizePedido)
      : Array.isArray(data?.data?.data)
        ? data.data.data.map(normalizePedido)
        : [];

    pedidos.value = payload;
  } catch (error) {
    console.error('Erro ao carregar pedidos:', error);
    pedidos.value = [];
  } finally {
    loading.value = false;
  }
}

function openDetailsModal(pedido) {
  selectedPedido.value = pedido;
  showDetailsModal.value = true;
}

function closeDetailsModal() {
  showDetailsModal.value = false;
  selectedPedido.value = null;
}

function handleApprove(pedido) {
  closeDetailsModal();
  // Recarrega a lista para atualizar os status
  fetchPedidos();
}

function openPaymentModal(pedido) {
  paymentPedido.value = pedido;
  showPaymentModal.value = true;
}

function closePaymentModal() {
  showPaymentModal.value = false;
  paymentPedido.value = null;
}

function handlePaymentSuccess() {
  // Recarregar lista de pedidos após sucesso
  fetchPedidos();
}

onMounted(() => {
  fetchPedidos();
  document.title = 'Pedidos';
});
</script>

<style scoped>
.btn-link {
  color: var(--bs-body-color);
}

.btn-link:hover {
  color: var(--bs-primary);
}
</style>

