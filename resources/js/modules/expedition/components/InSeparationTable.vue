<template>
  <div>
    <form class="g-3 align-items-center mb-4" role="search">
      <label for="search-query-in-separation" class="sr-only">Pesquisar em separação</label>
      <div class="d-flex">
        <div class="me-3">
          <div class="input-group input-group-prefix">
            <input
              id="search-query-in-separation"
              v-model="searchQuery"
              type="text"
              class="form-control"
              placeholder="Pesquisar em separação"
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
        Carregando cards em separação...
      </div>

      <EmptyState
        v-else-if="filteredItems.length === 0"
        heading="Nenhum card em separação"
        :icon="IconTruckDelivery"
        class="p-5"
      >
        Não há itens com etiqueta gerada aguardando faturamento.
      </EmptyState>

      <div v-else class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th scope="col" style="width: 64px">Nº</th>
              <th scope="col" style="width: 64px">Data</th>
              <th class="text-nowrap" scope="col">Pedido</th>
              <th class="text-nowrap" scope="col" style="width: 120px">Valor total</th>
              <th class="text-nowrap" scope="col" style="width: 64px">Opções</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="expedition in filteredItems" :key="expedition.id">
              <th scope="row">{{ expedition.id }}</th>
              <td>
                {{ formatDate(expedition.order?.created_at || expedition.created_at) }}
              </td>
              <td style="min-width: 240px">
                <div class="fw-semibold">
                  {{ expedition.order?.name || '—' }}
                </div>
                <small class="text-muted">Pedido #{{ expedition.order_id }}</small>
                -
                <small class="text-muted"
                  >Layout {{ expedition.order_index }} de {{ expedition.total_index }}</small
                >
              </td>
              <td>
                {{ formatCurrency(expedition.order?.total_amount || 0) }}
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
                    <li>
                      <button
                        class="dropdown-item"
                        type="button"
                        @click="$emit('view-details', expedition)"
                      >
                        Ver detalhes
                      </button>
                    </li>
                    <li>
                      <button
                        class="dropdown-item"
                        type="button"
                        :disabled="!canInvoiceOrder(expedition) || invoicing"
                        :title="
                          canInvoiceOrder(expedition)
                            ? ''
                            : 'Aguarde a geração de etiqueta de todos os cards deste pedido.'
                        "
                        @click="$emit('invoice-order-cards', expedition)"
                      >
                        Faturar cards do pedido
                      </button>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="
          !loading && filteredItems.length > 0 && paginationData && paginationData.last_page > 1
        "
        class="p-3"
      >
        <PaginationNav :data="paginationData" @pagination-change-page="handlePageChange" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import EmptyState from '@/components/empty-state/EmptyState.vue';
import PaginationNav from '@/components/pagination/PaginationNav.vue';
import { formatDate } from '@/utils/dateUtils';
import { IconDotsVertical, IconSearch, IconTruckDelivery } from '@tabler/icons-vue';

const props = defineProps({
  expeditions: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  invoicing: {
    type: Boolean,
    default: false,
  },
  paginationData: {
    type: Object,
    default: () => ({
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
      from: 0,
      to: 0,
    }),
  },
});

const emit = defineEmits(['view-details', 'invoice-order-cards', 'page-change', 'search-change']);

const searchQuery = ref('');

const filteredItems = computed(() => {
  return props.expeditions.filter((expedition) => {
    return (
      Number(expedition?.ready_to_expedition) === 0 &&
      Number(expedition?.picking_label_generated) === 1
    );
  });
});

watch(searchQuery, (newValue) => {
  emit('search-change', newValue);
});

function canInvoiceOrder(expedition) {
  return Boolean(expedition?.can_invoice_order);
}

function handlePageChange(page) {
  emit('page-change', page);
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
</script>
