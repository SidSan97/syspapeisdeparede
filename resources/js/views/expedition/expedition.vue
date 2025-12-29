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

              <div class="d-flex flex-wrap gap-3">
                <div class="flex-grow-1" style="min-width: 200px;">
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
                <div style="min-width: 200px;">
                  <select
                    class="form-select"
                    v-model="selectedCarrier"
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
                Carregando expedições...
              </div>

              <EmptyState
                v-else-if="filteredExpeditions.length === 0"
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
                  <button class="btn btn-primary" @click="expedir">
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
                            @change="toggleSelectAll"
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
                      <tr v-for="invoice in filteredExpeditions" :key="invoice.nota_fiscal?.id">
                        <td>
                          <input
                            type="checkbox"
                            class="form-check-input checkbox-invoice"
                            :checked="isInvoiceSelected(invoice.nota_fiscal?.id)"
                            @change="toggleSelectInvoice(invoice.nota_fiscal?.id)"
                          />
                        </td>
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
                                  @click="viewInvoiceDetails(invoice)"
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
                      <th class="text-nowrap" scope="col" style="width: 120px;">Status</th>
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
                            <li v-if="invoice?.nf_sent === 0">
                              <button
                                class="dropdown-item"
                                type="button"
                                @click="generateInvoice(invoice)"
                              >
                                Gerar Nota Fiscal
                              </button>
                            </li>
                            <li v-if="invoice?.nf_sent === 1">
                              <button
                                class="dropdown-item"
                                type="button"
                                @click="generateDanfe(invoice)"
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
        </div>
      </div>
    </Page>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import Page from '@/components/page/Page.vue';
import EmptyState from '@/components/empty-state/EmptyState.vue';
import axios from 'axios';
import { formatDate } from '@/utils/dateUtils';
import { getCarriersList } from '@/constants/carriers';

const router = useRouter();
const selectAllCheckbox = ref(null);

const expeditions = ref([]);
const invoices = ref([]);
const invoicesList = ref([]);
const loading = ref(true);
const activeTab = ref('separation');
const searchQuerySeparation = ref('');
const searchQueryExpedition = ref('');
const searchQueryInvoice = ref('');
const selectedInvoices = ref(new Set());
const selectedCarrier = ref(null);
const carriersList = ref(getCarriersList());

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

  return invoicesList.value.filter((invoice) => {
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
      !selectedCarrier.value ||
      notaFiscal?.transportador?.nome === selectedCarrier.value;

    return matchesQuery && matchesCarrier;
  });
});

const selectedCount = computed(() => selectedInvoices.value.size);

const isAllSelected = computed(() => {
  if (filteredExpeditions.value.length === 0) return false;
  return filteredExpeditions.value.every((invoice) =>
    selectedInvoices.value.has(invoice.nota_fiscal?.id)
  );
});

const isIndeterminate = computed(() => {
  const selected = selectedCount.value;
  const total = filteredExpeditions.value.length;
  return selected > 0 && selected < total;
});

watch([isIndeterminate, isAllSelected], () => {
  nextTick(() => {
    if (selectAllCheckbox.value) {
      selectAllCheckbox.value.indeterminate = isIndeterminate.value;
    }
  });
});

onMounted(() => {
  searchTinyErpProducts();
});

async function searchTinyErpProducts() {
    try {
        const { data } = await axios.get('v1/tiny-erp/all');
    } catch (error) {
        console.error('Erro ao buscar produtos:', error);
        window.Swal.fire({
            title: 'Erro ao buscar produtos!',
            text: 'Não foi possível buscar os produtos do Tiny ERP. Tente novamente mais tarde.',
            confirmButtonText: 'Entendi!',
        });
    } finally {
        loading.value = false;
    }
}

function toggleSelectAll() {
  if (isAllSelected.value) {
    // Desmarcar todos
    filteredExpeditions.value.forEach((invoice) => {
      selectedInvoices.value.delete(invoice.nota_fiscal?.id);
    });
  } else {
    // Marcar todos
    filteredExpeditions.value.forEach((invoice) => {
      if (invoice.nota_fiscal?.id) {
        selectedInvoices.value.add(invoice.nota_fiscal.id);
      }
    });
  }
}

function toggleSelectInvoice(invoiceId) {
  if (selectedInvoices.value.has(invoiceId)) {
    selectedInvoices.value.delete(invoiceId);
  } else {
    selectedInvoices.value.add(invoiceId);
  }
}

function isInvoiceSelected(invoiceId) {
  return selectedInvoices.value.has(invoiceId);
}

async function expedir() {
  try {
    loading.value = true;

    // Converter Set para Array com os Nº Nota Fiscal selecionados
    const invoiceIds = Array.from(selectedInvoices.value);
    const carrier = selectedCarrier.value;

    // Enviar para o endpoint
    const { data } = await axios.post('v1/send-invoice-to-expedition', {
      invoice_ids: invoiceIds,
      carrier: carrier
    });

    if (data.success) {
      window.Swal.fire({
        title: 'Notas fiscais enviadas com sucesso!',
        text: data.message || 'As notas fiscais foram enviadas para expedição.',
        icon: 'success',
        confirmButtonText: 'Entendi!',
      });

      // Limpar seleção
      selectedInvoices.value.clear();

      // Recarregar a lista de invoices
      await searchInvoices();
    }
  } catch (error) {
    console.error('Erro ao enviar notas fiscais para expedição:', error);
    window.Swal.fire({
      title: 'Erro ao enviar notas fiscais!',
      text: error.response?.data?.message || 'Não foi possível enviar as notas fiscais para expedição. Tente novamente mais tarde.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
  } finally {
    loading.value = false;
  }
}

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

            // Recarregar a lista de invoices
            await fetchInvoices();
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

async function generateDanfe(invoice) {
    try {
        loading.value = true;
        const { data } = await axios.get(`v1/generate-danfe/${invoice.nf_id}`);

        if (data.success && data.data.link_nfe) {
            const result = await window.Swal.fire({
                title: 'DANFE gerado com sucesso!',
                html: `
                    <p>Deseja abrir o DANFE agora?</p>
                    <div class="mt-3">
                        <div class="input-group">
                            <input
                                type="text"
                                id="danfe-link"
                                class="form-control"
                                value="${data.data.link_nfe}"
                                readonly
                                style="font-size: 0.875rem;"
                            />
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                id="copy-danfe-link"
                                title="Copiar link"
                            >
                                <i class="fa fa-copy"></i>
                            </button>
                        </div>
                    </div>
                `,
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: 'Abrir DANFE',
                cancelButtonText: 'Fechar',
                didOpen: () => {
                    const copyButton = document.getElementById('copy-danfe-link');
                    if (copyButton) {
                        copyButton.addEventListener('click', async () => {
                            try {
                                await navigator.clipboard.writeText(data.data.link_nfe);
                                window.Swal.fire({
                                    title: 'Link copiado!',
                                    text: 'O link do DANFE foi copiado para a área de transferência.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } catch (err) {
                                // Fallback para navegadores mais antigos
                                const linkInput = document.getElementById('danfe-link');
                                if (linkInput) {
                                    linkInput.select();
                                    document.execCommand('copy');
                                    window.Swal.fire({
                                        title: 'Link copiado!',
                                        text: 'O link do DANFE foi copiado para a área de transferência.',
                                        icon: 'success',
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                }
                            }
                        });
                    }
                }
            });

            if (result.isConfirmed) {
                window.open(data.data.link_nfe, '_blank');
            }
        } else {
            window.Swal.fire({
                title: 'Erro ao gerar DANFE!',
                text: 'Não foi possível gerar o DANFE. Tente novamente mais tarde.',
                icon: 'error',
                confirmButtonText: 'Entendi!',
            });
        }
    } catch (error) {
        console.error('Erro ao gerar DANFE:', error);
        window.Swal.fire({
            title: 'Erro ao gerar DANFE!',
            text: error.response?.data?.message || 'Não foi possível gerar o DANFE. Tente novamente mais tarde.',
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

        if (data.success && data.data.notas_fiscais) {
            invoicesList.value = data.data.notas_fiscais;
        } else {
            invoicesList.value = [];
        }
    } catch (error) {
        console.error('Erro ao buscar invoices:', error);
        invoicesList.value = [];
    } finally {
        loading.value = false;
    }
}

function formatInvoiceNumber(numero) {
    if (!numero) return '00000';
    return String(numero).padStart(5, '0');
}

function viewInvoiceDetails(invoice) {
    const invoiceId = invoice.nota_fiscal?.id;
    if (invoiceId) {
        // Salva os dados no sessionStorage antes de navegar
        sessionStorage.setItem(`invoice_${invoiceId}`, JSON.stringify(invoice.nota_fiscal));
        router.push({
            name: 'ShowInvoiceDetails',
            params: { id: invoiceId }
        });
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

.checkbox-invoice {
  width: 16px !important;
  height: 16px !important;
}

.count-nf-section {
  background-color: var(--ds-background-accent-gray-subtlest-hovered);
}
</style>

