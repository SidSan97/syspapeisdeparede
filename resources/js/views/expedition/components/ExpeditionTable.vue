<template>
  <div>
    <form class="g-3 align-items-center mb-4" role="search">
      <label for="search-query-expedition" class="sr-only">Pesquisar Notas Fiscais</label>
      <div class="d-flex flex-wrap gap-3">
        <div>
          <div class="input-group input-group-prefix">
            <input
              id="search-query-expedition"
              type="text"
              class="form-control"
              placeholder="Pesquisar Notas Fiscais"
              v-model="searchQuery"
            />
            <span class="input-group-text">
              <i class="fa fa-search"></i>
            </span>
          </div>
        </div>
        <div style="min-width: 200px;">
          <select
            class="form-select"
            :value="selectedCarrier"
            @change="$emit('carrier-changed', $event.target.value)"
          >
            <option :value="null">Todas as transportadoras</option>
            <option
              v-for="carrier in carriersList"
              :key="carrier"
              :value="carrier"
            >
              {{ carrier }}
            </option>
          </select>
        </div>
      </div>
    </form>

    <div class="card-body p-0 mt-4">
      <div v-if="loading" class="p-5 text-center text-muted fw-semibold">
        Carregando notas fiscais...
      </div>

      <EmptyState
        v-else-if="filteredItems.length === 0"
        heading="Nenhuma nota fiscal encontrada"
        icon="shipping-fast"
        class="p-5"
      >
        Não há notas fiscais para expedição no momento.
      </EmptyState>

      <div v-else>
        <!-- Barra de seleção -->
        <div v-if="selectedCount > 0" class="d-flex align-items-center justify-content-between mb-3 p-3 rounded count-nf-section">
          <div class="d-flex align-items-center">
            <span class="fw-semibold me-2">{{ selectedCount }}</span>
            <span class="text-muted">selecionados</span>
          </div>
          <button class="btn btn-primary" @click="$emit('expedir')">
            Agrupar e expedir
          </button>
        </div>

        <div class="table-responsive">
          <h6 class="mt-3">Notas Fiscais</h6>
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th scope="col" style="width: 50px;">
                  <input
                    ref="selectAllCheckbox"
                    type="checkbox"
                    class="form-check-input checkbox-invoice"
                    :checked="isAllSelected"
                    @change="$emit('toggle-select-all')"
                  />
                </th>
                <th class="text-nowrap" scope="col">Nome</th>
                <th scope="col" style="width: 120px;">Data de Emissão</th>
                <th scope="col" style="width: 100px;">Nº Pedido</th>
                <th scope="col" style="width: 100px;">Nº Nota Fiscal</th>
                <th class="text-nowrap" scope="col">Transportador</th>
                <th class="text-nowrap" scope="col">Valor</th>
                <th class="text-nowrap" scope="col" style="width: 64px;">Opções</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="invoice in filteredItems" :key="invoice.nota_fiscal?.id">
                <th scope="row">
                  <input
                    type="checkbox"
                    class="form-check-input checkbox-invoice"
                    :checked="isInvoiceSelected(invoice.nota_fiscal?.id)"
                    @change="$emit('toggle-invoice', invoice.nota_fiscal?.id)"
                  />
                </th>
                <td style="min-width: 240px;">
                  <div class="fw-semibold">{{ invoice.nota_fiscal?.nome || '—' }}</div>
                </td>
                <td>{{ invoice.nota_fiscal?.data_emissao || '—' }}</td>
                <td class="fw-semibold">#{{ formatInvoiceNumber(invoice.nota_fiscal?.numero_ecommerce) }}</td>
                <td class="fw-semibold">{{ invoice.nota_fiscal?.id || '—' }}</td>
                <td>{{ invoice.nota_fiscal?.transportador?.nome || '—' }}</td>
                <td>{{ formatCurrency(invoice.nota_fiscal?.valor || 0) }}</td>
                <td>
                  <div class="dropdown">
                    <button
                      class="btn btn-subtle btn-sm"
                      type="button"
                      data-bs-toggle="dropdown"
                      aria-expanded="false"
                    >
                      <i class="fa fa-ellipsis-h"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      <li>
                        <button
                          class="dropdown-item"
                          type="button"
                          @click="$emit('view-invoice-details', invoice)"
                        >
                          Ver detalhes
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
  </div>
</template>

<script setup>
import { computed, ref, watch, nextTick } from 'vue';
import EmptyState from '@/components/empty-state/EmptyState.vue';
import { getCarriersList } from '@/constants/carriers';

const props = defineProps({
  invoices: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  selectedInvoices: {
    type: Set,
    default: () => new Set(),
  },
  selectedCarrier: {
    type: String,
    default: null,
  },
});

const emit = defineEmits([
  'carrier-changed',
  'toggle-select-all',
  'toggle-invoice',
  'expedir',
  'view-invoice-details',
]);

const searchQuery = ref('');
const carriersList = ref(getCarriersList());
const selectAllCheckbox = ref(null);

const filteredItems = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();

  return props.invoices.filter((invoice) => {
    const notaFiscal = invoice.nota_fiscal;

    // Filtro por busca
    const matchesQuery =
      !query ||
      notaFiscal?.nome?.toLowerCase().includes(query) ||
      notaFiscal?.numero_ecommerce?.toString().includes(query) ||
      notaFiscal?.transportador?.nome?.toLowerCase().includes(query) ||
      notaFiscal?.data_emissao?.includes(query);

    // Filtro por transportadora
    const matchesCarrier =
      !props.selectedCarrier ||
      notaFiscal?.transportador?.nome === props.selectedCarrier;

    return matchesQuery && matchesCarrier;
  });
});

const selectedCount = computed(() => props.selectedInvoices.size);

const isAllSelected = computed(() => {
  if (filteredItems.value.length === 0) return false;
  return filteredItems.value.every((invoice) =>
    props.selectedInvoices.has(invoice.nota_fiscal?.id)
  );
});

const isIndeterminate = computed(() => {
  const selected = selectedCount.value;
  const total = filteredItems.value.length;
  return selected > 0 && selected < total;
});

watch([isIndeterminate, isAllSelected], () => {
  nextTick(() => {
    if (selectAllCheckbox.value) {
      selectAllCheckbox.value.indeterminate = isIndeterminate.value;
    }
  });
});

function isInvoiceSelected(invoiceId) {
  return props.selectedInvoices.has(invoiceId);
}

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

function formatInvoiceNumber(numero) {
  if (!numero) return '00000';
  return String(numero).padStart(5, '0');
}
</script>

<style scoped>
.checkbox-invoice {
  width: 16px !important;
  height: 16px !important;
}

.count-nf-section {
  background-color: var(--ds-background-accent-gray-subtlest-hovered);
}
</style>
