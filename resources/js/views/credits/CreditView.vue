<template>
  <section class="content">
    <Page title="Créidto">
      <template #extra>
        <button
          type="button"
          class="btn btn-outline-default btn-sm"
          :disabled="loading"
          @click="fetchWallet(1)"
        >
          <span v-if="loading" class="spinner-border spinner-border-sm me-1" role="status" />
          Atualizar
        </button>
      </template>

      <div class="card bd-card border-0 mb-4 mt-1">
        <div class="card-body">
          <p class="text-muted text-uppercase small mb-1">Saldo disponível</p>
          <p class="display-6 mb-0 fw-semibold text-body">
            {{ formattedBalance }}
          </p>
        </div>
      </div>

      <p v-if="error" class="text-danger small mb-3">{{ error }}</p>

      <div v-if="loading" class="p-5 text-center text-muted fw-semibold">
        Carregando histórico...
      </div>

      <EmptyState
        v-else-if="!error && paginated.data.length === 0"
        heading="Nenhuma movimentação"
        :icon="IconWallet"
        class="p-5 mt-1"
      >
        Quando houver créditos ou débitos, eles aparecerão aqui.
      </EmptyState>

      <template v-else>
        <div class="table-responsive mt-1">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Data</th>
                <th>Tipo</th>
                <th class="text-start">Valor</th>
                <th class="text-start">Saldo</th>
                <th>Descrição</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in paginated.data" :key="row.id">
                <td class="text-nowrap">
                  {{ formatDate(row.created_at) }}
                </td>
                <td class="">
                  {{ formatTransactionType(row.type) }}
                </td>
                <td class="text-start font-monospace" :class="amountClass(row.amount)">
                  {{ formatMoney(row.amount) }}
                </td>
                <td class="text-start font-monospace">
                  {{ formatMoney(row.balance_after) }}
                </td>
                <td class="text-muted">
                  {{ row.description || '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Bootstrap5Pagination
          :data="paginated"
          @pagination-change-page="fetchWallet"
          class="justify-content-center mt-3"
        />
      </template>
    </Page>
  </section>
</template>

<script setup>
import EmptyState from '@/components/empty-state/EmptyState.vue';
import Page from '@/components/page/Page.vue';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
import { computed, onMounted, ref } from 'vue';

import { http } from '@/lib/http';
import { IconWallet } from '@tabler/icons-vue';

const loading = ref(true);
const error = ref('');
const balance = ref('0.00');
const paginated = ref({ data: [] });

const formattedBalance = computed(() => formatMoney(balance.value));

function formatMoney(value) {
  const n = Number(value);
  if (Number.isNaN(n)) return 'R$ 0,00';
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(n);
}

function formatDate(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '—';
  return new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(d);
}

function amountClass(amount) {
  const n = Number(amount);
  if (n > 0) return 'text-success';
  if (n < 0) return 'text-danger';
  return '';
}

const TRANSACTION_TYPE_LABELS = {
  order_edit_credit: 'Crédito por revisão do pedido',
  order_payment_boleto: 'Pagamento de pedido (boleto via saldo)',
};

function formatTransactionType(type) {
  if (!type) return '—';
  return TRANSACTION_TYPE_LABELS[type] ?? type;
}

const fetchWallet = async (page = 1) => {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await http.get('v1/wallet', { params: { page } });
    balance.value = data.balance ?? '0.00';
    paginated.value = data;
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Não foi possível carregar a carteira.';
    paginated.value = { data: [] };
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  document.title = 'Minha carteira';
  fetchWallet(1);
});
</script>
