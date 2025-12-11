<template>
  <section class="content">
    <Page title="Expedição">
      <div class="border-0 shadow-sm">
        <form class="g-3 align-items-center mb-4" role="search">
          <label for="search-query" class="sr-only">Pesquisar expedição</label>

          <div class="d-flex">
            <div class="me-3">
              <div class="input-group input-group-prefix">
                <input
                  id="search-query"
                  type="text"
                  class="form-control"
                  placeholder="Pesquisar expedição"
                  v-model="searchQuery"
                />
                <span class="input-group-text">
                  <i class="fa fa-search"></i>
                </span>
              </div>
            </div>
          </div>
        </form>

        <div class="card-body p-0 mt-4">
          <div v-if="loading" class="p-5 text-center text-muted fw-semibold">
            Carregando expedições...
          </div>

          <EmptyState
            v-else-if="filteredExpeditions.length === 0"
            heading="Nenhuma expedição encontrada"
            icon="shipping-fast"
            class="p-5"
          >
            Não há itens prontos para expedição no momento.
          </EmptyState>

          <div v-else class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th scope="col" style="width: 64px;">ID</th>
                  <th scope="col" style="width: 64px;">Data</th>
                  <th class="text-nowrap" scope="col">Pedido</th>
                  <th class="text-nowrap" scope="col" style="width: 120px;">Valor Total</th>
                  <th class="text-nowrap" scope="col" style="width: 64px;">Opções</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="expedition in filteredExpeditions" :key="expedition.id">
                  <td class="fw-semibold">{{ expedition.id }}</td>
                  <td>{{ formatDate(expedition.order?.created_at || expedition.created_at) }}</td>
                  <td style="min-width: 240px;">
                    <div class="fw-semibold">{{ expedition.order?.name || '—' }}</div>
                    <small class="text-muted">Pedido #{{ expedition.order_id }}</small>
                  </td>
                  <td>{{ formatCurrency(expedition.order?.total_amount || 0) }}</td>
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
                            @click="viewDetails(expedition)"
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
    </Page>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import Page from '@/components/page/Page.vue';
import EmptyState from '@/components/empty-state/EmptyState.vue';
import axios from 'axios';
import { formatDate } from '@/utils/dateUtils';

const expeditions = ref([]);
const loading = ref(true);
const searchQuery = ref('');

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

const filteredExpeditions = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();

  return expeditions.value.filter((expedition) => {
    // Filtro de busca
    const matchesQuery =
      !query ||
      expedition.order?.name?.toLowerCase().includes(query) ||
      String(expedition.id).includes(query) ||
      String(expedition.order_id).includes(query);

    return matchesQuery;
  });
});

async function fetchExpeditions() {
  try {
    loading.value = true;

    const { data } = await axios.get('v1/orders/expedition');

    const payload = Array.isArray(data?.data) ? data.data : [];

    expeditions.value = payload;
  } catch (error) {
    console.error('Erro ao buscar expedições:', error);
    expeditions.value = [];
  } finally {
    loading.value = false;
  }
}

function viewDetails(expedition) {
  // TODO: Implementar ação de ver detalhes
  console.log('Ver detalhes da expedição:', expedition);
}

onMounted(() => {
  fetchExpeditions();
  document.title = 'Expedição';
});
</script>

<style scoped>
.search-input .form-control,
.search-input .input-group-text {
  border-radius: 0.375rem;
  padding-block: 0.85rem;
}

.search-input .input-group-text {
  border-right: none;
}

.search-input .form-control {
  border-left: none;
}

.search-input .form-control:focus {
  border-color: var(--bs-secondary);
  box-shadow: none;
}

.input-group-text,
input {
  height: 36px !important;
}
</style>

