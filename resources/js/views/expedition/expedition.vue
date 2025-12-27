<template>
  <section class="content">
    <Page title="Expedição">
      <div class="border-0 shadow-sm">
        <!-- Abas -->
        <ul class="nav nav-tabs mb-4" role="tablist">
          <li class="nav-item" role="presentation">
            <button
              class="nav-link"
              :class="{ active: activeTab === 'separation' }"
              @click="activeTab = 'separation'"
              type="button"
              role="tab"
            >
              Separação
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button
              class="nav-link"
              :class="{ active: activeTab === 'invoice' }"
              @click="activeTab = 'invoice'"
              type="button"
              role="tab"
            >
              Faturar
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button
              class="nav-link"
              :class="{ active: activeTab === 'expedition' }"
              @click="activeTab = 'expedition'"
              type="button"
              role="tab"
            >
              Expedição
            </button>
          </li>
        </ul>

        <div class="tab-content">
          <!-- Aba Separação -->
          <div
            v-show="activeTab === 'separation'"
            class="tab-pane"
            :class="{ active: activeTab === 'separation' }"
            role="tabpanel"
          >
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
                      v-model="searchQuerySeparation"
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
                Carregando separações...
              </div>

              <EmptyState
                v-else-if="filteredSeparations.length === 0"
                heading="Nenhuma separação encontrada"
                icon="shipping-fast"
                class="p-5"
              >
                Não há itens prontos para separação no momento.
              </EmptyState>

              <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead>
                    <tr>
                      <th scope="col" style="width: 64px;">Nº</th>
                      <th scope="col" style="width: 64px;">Data</th>
                      <th class="text-nowrap" scope="col">Pedido</th>
                      <th class="text-nowrap" scope="col" style="width: 120px;">Valor total</th>
                      <th class="text-nowrap" scope="col" style="width: 64px;">Opções</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="expedition in filteredSeparations" :key="expedition.id">
                      <td class="fw-semibold">{{ expedition.id }}</td>
                      <td>{{ formatDate(expedition.order?.created_at || expedition.created_at) }}</td>
                      <td style="min-width: 240px;">
                        <div class="fw-semibold">{{ expedition.order?.name || '—' }}</div>
                        <small class="text-muted">Pedido #{{ expedition.order_id }}</small> -
                        <small class="text-muted">Layout {{ expedition.order_index }} de {{ expedition.total_index }}</small>
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
                            <li>
                              <button
                                class="dropdown-item"
                                type="button"
                                @click="generateSeparationLabel(expedition)"
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
          </div>

          <!-- Aba Expedição -->
          <div
            v-show="activeTab === 'expedition'"
            class="tab-pane"
            :class="{ active: activeTab === 'expedition' }"
            role="tabpanel"
          >
            <form class="g-3 align-items-center mb-4" role="search">
              <label for="search-query-expedition" class="sr-only">Pesquisar expedição</label>

              <div class="d-flex">
                <div class="me-3">
                  <div class="input-group input-group-prefix">
                    <input
                      id="search-query-expedition"
                      type="text"
                      class="form-control"
                      placeholder="Pesquisar expedição"
                      v-model="searchQueryExpedition"
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
                      <th class="text-nowrap" scope="col" style="width: 120px;">Valor total</th>
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
                            <li>
                              <button
                                class="dropdown-item"
                                type="button"
                                @click="generateSeparationLabel(expedition)"
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
          </div>

          <!-- Aba Faturar -->
          <div
            v-show="activeTab === 'invoice'"
            class="tab-pane"
            :class="{ active: activeTab === 'invoice' }"
            role="tabpanel"
          >
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
                      v-model="searchQueryInvoice"
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
                Carregando pedidos para faturar...
              </div>

              <EmptyState
                v-else-if="filteredInvoices.length === 0"
                heading="Nenhum pedido encontrado"
                icon="shipping-fast"
                class="p-5"
              >
                Não há pedidos prontos para faturar no momento.
              </EmptyState>

              <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead>
                    <tr>
                      <th scope="col" style="width: 64px;">ID</th>
                      <th scope="col" style="width: 64px;">Data</th>
                      <th class="text-nowrap" scope="col">Pedido</th>
                      <th class="text-nowrap" scope="col" style="width: 120px;">Valor total</th>
                      <th class="text-nowrap" scope="col" style="width: 64px;">Opções</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="invoice in filteredInvoices" :key="invoice.id">
                      <td class="fw-semibold">{{ invoice.id }}</td>
                      <td>{{ formatDate(invoice?.created_at || invoice.created_at) }}</td>
                      <td style="min-width: 240px;">
                        <div class="fw-semibold">{{ invoice?.name || '—' }}</div>
                        <small class="text-muted">Pedido #{{ invoice.order_id }}</small>
                      </td>
                      <td>{{ formatCurrency(invoice?.total_amount || 0) }}</td>
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
                                @click="viewDetails(invoice)"
                              >
                                Ver detalhes
                              </button>
                            </li>
                            <li>
                              <button
                                class="dropdown-item"
                                type="button"
                                @click="generateInvoice(invoice)"
                              >
                                Gerar Nota Fiscal
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
      </div>
    </Page>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import Page from '@/components/page/Page.vue';
import EmptyState from '@/components/empty-state/EmptyState.vue';
import axios from 'axios';
import { formatDate } from '@/utils/dateUtils';

const expeditions = ref([]);
const invoices = ref([]);
const loading = ref(true);
const activeTab = ref('separation');
const searchQuerySeparation = ref('');
const searchQueryExpedition = ref('');
const searchQueryInvoice = ref('');

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

const separations = computed(() => {
  return expeditions.value.filter((expedition) => {
    return expedition?.ready_to_expedition === 0;
  });
});

const expeditionsList = computed(() => {
  return expeditions.value.filter((expedition) => {
    return expedition?.ready_to_expedition === 1;
  });
});

// Filtros de busca
const filteredSeparations = computed(() => {
  const query = searchQuerySeparation.value.trim().toLowerCase();

  return separations.value.filter((expedition) => {
    const matchesQuery =
      !query ||
      expedition.order?.name?.toLowerCase().includes(query) ||
      String(expedition.id).includes(query) ||
      String(expedition.order_id).includes(query);

    return matchesQuery;
  });
});

const filteredExpeditions = computed(() => {
  const query = searchQueryExpedition.value.trim().toLowerCase();

  return expeditionsList.value.filter((expedition) => {
    const matchesQuery =
      !query ||
      expedition.order?.name?.toLowerCase().includes(query) ||
      String(expedition.id).includes(query) ||
      String(expedition.order_id).includes(query);

    return matchesQuery;
  });
});

const filteredInvoices = computed(() => {
  const query = searchQueryInvoice.value.trim().toLowerCase();

  return invoices.value.filter((invoice) => {
    const matchesQuery =
      !query ||
      invoice.order?.name?.toLowerCase().includes(query) ||
      String(invoice.id).includes(query) ||
      String(invoice.order_id).includes(query);

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

async function fetchInvoices() {
  try {
    loading.value = true;

    const { data } = await axios.get('v1/orders/ready-for-invoice');

    const payload = Array.isArray(data?.data) ? data.data : [];

    invoices.value = payload;
  } catch (error) {
    console.error('Erro ao buscar pedidos para faturar:', error);
    invoices.value = [];
  } finally {
    loading.value = false;
  }
}

function viewDetails(expedition) {
  // TODO: Implementar ação de ver detalhes
  console.log('Ver detalhes da expedição:', expedition);
}

async function generateSeparationLabel(expedition) {
    try {
        loading.value = true;
        const { data } = await axios.get(`v1/generate-separation-label/${expedition.id}`);

        if (data.success) {
            const result = await window.Swal.fire({
                title: 'Etiqueta gerada com sucesso!',
                text: 'Deseja visualizar a etiqueta agora?',
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: 'Visualizar etiqueta',
                cancelButtonText: 'Fechar',
            });

            if (result.isConfirmed) {
                viewSeparationLabelPdf(expedition.id);
            }
        }
    } catch (error) {
        console.error('Erro ao gerar etiqueta de separação:', error);
        window.Swal.fire({
            title: 'Erro ao gerar etiqueta de separação!',
            text: 'Não foi possível gerar a etiqueta de separação. Tente novamente mais tarde.',
            icon: 'error',
            confirmButtonText: 'Entendi!',
        });
    } finally {
        loading.value = false;
    }
}

async function viewSeparationLabelPdf(orderBudgetId) {
    try {
        loading.value = true;
        const response = await axios.get(`v1/generate-separation-label-pdf/${orderBudgetId}`, {
            responseType: 'blob',
        });

        const blob = new Blob([response.data], { type: 'application/pdf' });
        const url = URL.createObjectURL(blob);
        window.open(url, '_blank');

        // Limpar a URL após um tempo para liberar memória
        setTimeout(() => {
            URL.revokeObjectURL(url);
        }, 100);
    } catch (error) {
        console.error('Erro ao visualizar PDF da etiqueta:', error);
        window.Swal.fire({
            title: 'Erro ao visualizar etiqueta!',
            text: 'Não foi possível abrir a etiqueta. Tente novamente mais tarde.',
            icon: 'error',
            confirmButtonText: 'Entendi!',
        });
    } finally {
        loading.value = false;
    }
}

async function generateInvoice(invoice) {
    try {
        loading.value = true;
        const { data } = await axios.post(`v1/generate-invoice/${invoice.order_id}`);

        if (data.success) {
            window.Swal.fire({
                title: 'Nota Fiscal gerada com sucesso!',
                text: data.message || 'A nota fiscal foi gerada com sucesso.',
                icon: 'success',
                confirmButtonText: 'Entendi!',
            });
        }
    } catch (error) {
        console.error('Erro ao gerar nota fiscal:', error);
        window.Swal.fire({
            title: 'Erro ao gerar nota fiscal!',
            text: error.response?.data?.message || 'Não foi possível gerar a nota fiscal. Tente novamente mais tarde.',
            icon: 'error',
            confirmButtonText: 'Entendi!',
        });
    } finally {
        loading.value = false;
    }
}

async function searchInvoices() {
    try {
        loading.value = true;
        const { data } = await axios.get('v1/search-invoices');
        
        // Por enquanto apenas faz a chamada, sem lógica adicional
        console.log('Dados de search-invoices:', data);
    } catch (error) {
        console.error('Erro ao buscar invoices:', error);
    } finally {
        loading.value = false;
    }
}

watch(activeTab, (newTab) => {
  if (newTab === 'invoice' && invoices.value.length === 0) {
    fetchInvoices();
  }
  
  if (newTab === 'expedition') {
    searchInvoices();
  }
});

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

