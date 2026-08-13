<template>
  <div>
    <form class="g-3 align-items-center mb-4" role="search">
      <label for="search-query-separation" class="sr-only">Pesquisar separação</label>
      <div class="d-flex">
        <div class="me-3">
          <div class="input-group input-group-prefix">
            <input
              id="search-query-separation"
              type="text"
              class="form-control"
              placeholder="Pesquisar separação"
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
        Carregando separações...
      </div>

      <EmptyState
        v-else-if="filteredItems.length === 0"
        heading="Nenhuma separação encontrada"
        :icon="IconTruckDelivery"
        class="p-5"
      >
        Não há itens prontos para separação no momento.
      </EmptyState>

      <div v-else>
        <div
          v-if="selectedCount > 0"
          class="d-flex align-items-center justify-content-between mb-3 p-3 rounded count-nf-section"
        >
          <div class="d-flex align-items-center">
            <span class="fw-semibold me-2">{{ selectedCount }}</span>
            <span class="text-muted">selecionado(s)</span>
          </div>
          <button
            type="button"
            class="btn btn-primary"
            :disabled="printingLabels"
            @click="$emit('print-selected-labels', selectedIds)"
          >
            <span
              v-if="printingLabels"
              class="spinner-border spinner-border-sm me-2"
              role="status"
              aria-hidden="true"
            ></span>
            Imprimir etiquetas
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th scope="col" style="width: 50px">
                  <input
                    type="checkbox"
                    class="form-check-input"
                    :checked="isAllSelected"
                    @change="toggleSelectAll"
                  />
                </th>
                <th scope="col" style="width: 64px">Nº</th>
                <th scope="col" style="width: 64px">Data</th>
                <th class="text-nowrap" scope="col">Pedido</th>
                <th class="text-nowrap" scope="col" style="width: 120px">Valor total</th>
                <th class="text-nowrap" scope="col" style="width: 64px">Opções</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="expedition in filteredItems" :key="expedition.id">
                <td>
                  <input
                    type="checkbox"
                    class="form-check-input"
                    :checked="isSelected(expedition.id)"
                    @change="toggleSelect(expedition.id)"
                  />
                </td>
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
                          @click="$emit('generate-separation-label', expedition)"
                        >
                          Gerar etiqueta de separação
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

// Icons
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
  printingLabels: {
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

const emit = defineEmits([
  'view-details',
  'generate-separation-label',
  'print-selected-labels',
  'page-change',
  'search-change',
]);

const searchQuery = ref('');
const selectedIds = ref([]);

const separations = computed(() => {
  return props.expeditions.filter((expedition) => {
    return (
      Number(expedition?.ready_to_expedition) === 0 &&
      Number(expedition?.picking_label_generated) !== 1
    );
  });
});

const filteredItems = computed(() => {
  return separations.value;
});

const visibleIds = computed(() => filteredItems.value.map((item) => item.id));

const selectedCount = computed(() => selectedIds.value.length);

const isAllSelected = computed(() => {
  return visibleIds.value.length > 0 && visibleIds.value.every((id) => selectedIds.value.includes(id));
});

watch(searchQuery, (newValue) => {
  emit('search-change', newValue);
});

watch(visibleIds, (ids) => {
  selectedIds.value = selectedIds.value.filter((id) => ids.includes(id));
});

function isSelected(id) {
  return selectedIds.value.includes(id);
}

function toggleSelect(id) {
  if (isSelected(id)) {
    selectedIds.value = selectedIds.value.filter((selectedId) => selectedId !== id);
    return;
  }

  selectedIds.value = [...selectedIds.value, id];
}

function toggleSelectAll() {
  if (isAllSelected.value) {
    selectedIds.value = [];
    return;
  }

  selectedIds.value = [...visibleIds.value];
}

function clearSelection() {
  selectedIds.value = [];
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

defineExpose({
  clearSelection,
});
</script>

<style scoped>
.count-nf-section {
  background: var(--bs-tertiary-bg);
  border: 1px solid var(--bs-border-color);
}
</style>
