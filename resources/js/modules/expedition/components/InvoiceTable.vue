<template>
  <div>
    <form class="g-3 align-items-center mb-4" role="search">
      <label for="search-query-invoice" class="sr-only">Pesquisar faturar</label>
      <div class="d-flex">
        <div class="me-3">
          <div class="input-group input-group-prefix">
            <input
              id="search-query-invoice"
              type="text"
              class="form-control"
              placeholder="Pesquisar faturar"
              v-model="searchQuery"
            />
            <span class="input-group-text">
              <IconSearch :size="18" />
            </span>
          </div>
        </div>
      </div>
    </form>

    <div class="card-body p-0 mt-4">
      <div v-if="loading" class="p-5 text-center text-muted fw-semibold">
        Carregando pedidos para faturar...
      </div>

      <EmptyState
        v-else-if="filteredItems.length === 0"
        heading="Nenhum pedido encontrado"
        :icon="IconTruckDelivery"
        class="p-5"
      >
        Não há pedidos prontos para faturar no momento.
      </EmptyState>

      <div v-else class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th scope="col" style="width: 64px">ID</th>
              <th scope="col" style="width: 64px">Data</th>
              <th class="text-nowrap" scope="col">Pedido</th>
              <th class="text-nowrap" scope="col" style="width: 120px">Valor total</th>
              <th class="text-nowrap" scope="col" style="width: 120px">Status</th>
              <th class="text-nowrap" scope="col" style="width: 64px">Opções</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="invoice in filteredItems" :key="invoice.id">
              <th scope="row">{{ invoice.id }}</th>
              <td>
                {{ formatDate(invoice?.created_at || invoice.created_at) }}
              </td>
              <td style="min-width: 240px">
                <div class="fw-semibold">
                  {{ invoice?.name || '—' }}
                </div>
                <small class="text-muted">Pedido #{{ invoice.order_id }}</small>
              </td>
              <td>
                {{ formatCurrency(invoice?.total_amount || 0) }}
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <span
                    class="dot me-2"
                    :class="invoice?.nf_sent === 1 ? 'dot-success' : 'dot-secondary'"
                  ></span>
                  <span class="text-muted small">
                    {{ invoice?.nf_sent === 1 ? 'Faturado' : 'Não faturado' }}
                  </span>
                </div>
              </td>
              <td>
                <div class="dropdown">
                  <button
                    class="btn btn-subtle btn-sm"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <IconDotsVertical :size="18" />
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li v-if="invoice?.nf_sent === 0">
                      <button
                        class="dropdown-item"
                        type="button"
                        @click="$emit('generate-invoice', invoice)"
                      >
                        Gerar Nota Fiscal
                      </button>
                    </li>
                    <li v-if="invoice?.nf_sent === 1">
                      <button
                        class="dropdown-item"
                        type="button"
                        @click="$emit('generate-danfe', invoice.nf_id)"
                      >
                        Gerar DANFE
                      </button>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import EmptyState from '@/components/empty-state/EmptyState.vue';
import { formatDate } from '@/utils/dateUtils';

// Icons
import { IconDotsVertical, IconSearch, IconTruckDelivery } from '@tabler/icons-vue';

const props = defineProps({
  invoices: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['view-details', 'generate-invoice', 'generate-danfe']);

const searchQuery = ref('');

const filteredItems = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();

  return props.invoices.filter((invoice) => {
    const matchesQuery =
      !query ||
      invoice.order?.name?.toLowerCase().includes(query) ||
      String(invoice.id).includes(query) ||
      String(invoice.order_id).includes(query);

    return matchesQuery;
  });
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
</script>

<style scoped>
.dot {
  display: inline-block;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.dot-success {
  background-color: #28a745;
}

.dot-secondary {
  background-color: #6c757d;
}
</style>
