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
              Expedir
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button
              class="nav-link"
              :class="{ active: activeTab === 'groupings' }"
              @click="activeTab = 'groupings'"
              type="button"
              role="tab"
            >
              Agrupamentos
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
            <SeparationTable
              :expeditions="expeditions"
              :loading="loading"
              @view-details="viewDetails"
              @generate-separation-label="generateSeparationLabel"
            />
          </div>

          <!-- Aba Expedição -->
          <div
            v-show="activeTab === 'expedition'"
            class="tab-pane"
            :class="{ active: activeTab === 'expedition' }"
            role="tabpanel"
          >
            <ExpeditionTable
              :invoices="invoicesList"
              :loading="loading"
              :selected-invoices="selectedInvoices"
              :selected-carrier="selectedCarrier"
              @carrier-changed="selectedCarrier = $event"
              @toggle-select-all="toggleSelectAll"
              @toggle-invoice="toggleSelectInvoice"
              @expedir="expedir"
              @view-invoice-details="viewInvoiceDetails"
            />
          </div>

          <!-- Aba Agrupamentos -->
          <div
            v-show="activeTab === 'groupings'"
            class="tab-pane"
            :class="{ active: activeTab === 'groupings' }"
            role="tabpanel"
          >
            <GroupingsTable
              :groupings="groupings"
              :loading="loadingGroupings"
              :selected-carrier="selectedGroupingCarrier"
              @carrier-changed="handleGroupingCarrierChanged"
              @view-details="viewGroupingDetails"
              @print-labels="printCarrierLabels"
            />
          </div>

          <!-- Aba Faturar -->
          <div
            v-show="activeTab === 'invoice'"
            class="tab-pane"
            :class="{ active: activeTab === 'invoice' }"
            role="tabpanel"
          >
            <InvoiceTable
              :invoices="invoices"
              :loading="loading"
              @view-details="viewDetails"
              @generate-invoice="generateInvoice"
              @generate-danfe="generateDanfe"
            />
          </div>
        </div>
      </div>
    </Page>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import Page from '@/components/page/Page.vue';
import SeparationTable from './components/SeparationTable.vue';
import ExpeditionTable from './components/ExpeditionTable.vue';
import InvoiceTable from './components/InvoiceTable.vue';
import GroupingsTable from './components/GroupingsTable.vue';
import { useExpeditionService } from './services/expeditionService';
import { createLinkAlertConfig } from '@/utils/sweetalertHelpers';

const router = useRouter();
const expeditionService = useExpeditionService();

const expeditions = ref([]);
const invoices = ref([]);
const invoicesList = ref([]);
const loading = ref(true);
const activeTab = ref('separation');
const selectedInvoices = ref(new Set());
const selectedCarrier = ref(null);
const selectedGroupingCarrier = ref(null);
const groupings = ref([]);
const loadingGroupings = ref(false);

const filteredExpeditions = computed(() => {
  return invoicesList.value;
});

async function fetchExpeditions() {
  try {
    loading.value = true;
    expeditions.value = await expeditionService.fetchExpeditions();
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
    invoices.value = await expeditionService.fetchInvoices();
  } catch (error) {
    console.error('Erro ao buscar pedidos para faturar:', error);
    invoices.value = [];
  } finally {
    loading.value = false;
  }
}

async function searchInvoices() {
  try {
    loading.value = true;
    invoicesList.value = await expeditionService.searchInvoices();
  } catch (error) {
    console.error('Erro ao buscar invoices:', error);
    invoicesList.value = [];
  } finally {
    loading.value = false;
  }
}

async function searchTinyErpProducts() {
    try {
    await expeditionService.searchTinyErpProducts();
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

async function searchTinyErpCarriersTypes() {
    try {
    await expeditionService.searchTinyErpCarriersTypes();
    } catch (error) {
        console.error('Erro ao buscar tipos de transportadores:', error);
    }
}

function toggleSelectAll() {
  if (isAllSelected.value) {
    filteredExpeditions.value.forEach((invoice) => {
      selectedInvoices.value.delete(invoice.nota_fiscal?.id);
    });
  } else {
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

const isAllSelected = computed(() => {
  if (filteredExpeditions.value.length === 0) return false;
  return filteredExpeditions.value.every((invoice) =>
    selectedInvoices.value.has(invoice.nota_fiscal?.id)
  );
});

async function expedir() {
  try {
    loading.value = true;

    const invoiceIds = Array.from(selectedInvoices.value);

    if (invoiceIds.length === 0) {
      loading.value = false;
      window.Swal.fire({
        title: 'Nenhuma nota fiscal selecionada!',
        text: 'Por favor, selecione pelo menos uma nota fiscal para expedir.',
        icon: 'warning',
        confirmButtonText: 'Entendi!',
      });
      return;
    }

    const selectedInvoicesData = filteredExpeditions.value.filter(invoice =>
      invoiceIds.includes(invoice.nota_fiscal?.id)
    );

    const orderIds = selectedInvoicesData
      .map(invoice => invoice.nota_fiscal?.numero_ecommerce)
      .filter(orderId => orderId);

    const transporters = selectedInvoicesData
      .map(invoice => invoice.nota_fiscal?.transportador?.nome)
      .filter(transporter => transporter);

    if (transporters.length === 0 || transporters.length !== selectedInvoicesData.length) {
      loading.value = false;
      window.Swal.fire({
        title: 'Erro ao validar transportadoras!',
        text: 'Algumas notas fiscais selecionadas não possuem transportador.',
        icon: 'error',
        confirmButtonText: 'Entendi!',
      });
      return;
    }

    const firstTransporter = transporters[0];
    const allSameTransporter = transporters.every(transporter => transporter === firstTransporter);

    if (!allSameTransporter) {
      loading.value = false;
      window.Swal.fire({
        title: 'Transportadoras diferentes!',
        text: 'Todas as notas fiscais selecionadas devem ter exatamente o mesmo transportador para serem agrupadas.',
        icon: 'warning',
        confirmButtonText: 'Entendi!',
      });
      return;
    }

    const data = await expeditionService.sendInvoiceToExpedition(
      invoiceIds,
      firstTransporter,
      orderIds
    );

    if (data.success) {
      window.Swal.fire({
        title: 'Notas fiscais enviadas com sucesso!',
        text: data.message || 'As notas fiscais foram enviadas para expedição.',
        icon: 'success',
        confirmButtonText: 'Entendi!',
      });

      selectedInvoices.value.clear();
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

function viewDetails(expedition) {
  // TODO: Implementar ação de ver detalhes
  console.log('Ver detalhes da expedição:', expedition);
}

async function generateSeparationLabel(expedition) {
    try {
        loading.value = true;
    const data = await expeditionService.generateSeparationLabel(expedition.id);

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
        await viewSeparationLabelPdf(expedition.id);
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
    const blobData = await expeditionService.viewSeparationLabelPdf(orderBudgetId);

    const blob = new Blob([blobData], { type: 'application/pdf' });
        const url = URL.createObjectURL(blob);
        window.open(url, '_blank');

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
    const data = await expeditionService.generateInvoice(invoice.order_id);

        if (data.success) {
            window.Swal.fire({
                title: 'Nota Fiscal gerada com sucesso!',
                text: data.message || 'A nota fiscal foi gerada com sucesso.',
                icon: 'success',
                confirmButtonText: 'Entendi!',
            });

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

async function generateDanfe(id) {
    try {
        loading.value = true;
    const data = await expeditionService.generateDanfe(id);

        if (data.success && data.data.link_nfe) {
            const result = await window.Swal.fire(
                createLinkAlertConfig({
                    title: 'DANFE gerado com sucesso!',
                    linkId: 'danfe-link',
                    linkValue: data.data.link_nfe,
                    message: 'Deseja abrir o DANFE agora?',
                    successMessage: 'O link do DANFE foi copiado para a área de transferência.',
                    confirmButtonText: 'Abrir DANFE',
                    cancelButtonText: 'Fechar'
                })
            );

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

async function printCarrierLabels(groupingId) {
    try {
        loading.value = true;
    const data = await expeditionService.printCarrierLabels(groupingId);

        if (data.success && data.data.links) {
            const result = await window.Swal.fire(
                createLinkAlertConfig({
                    title: 'Etiqueta gerada com sucesso!',
                    linkId: 'label-link',
                    linkValue: data.data.links[0].link,
                    message: 'Deseja visualizar a etiqueta agora?',
                    successMessage: 'O link da etiqueta foi copiado para a área de transferência.',
                    confirmButtonText: 'Visualizar etiqueta',
                    cancelButtonText: 'Fechar'
                })
            );

            if (result.isConfirmed) {
                window.open(data.data.links[0].link, '_blank');
            }
        } else {
            window.Swal.fire({
                title: 'Erro ao gerar etiqueta!',
                text: 'Não foi possível gerar a etiqueta. Tente novamente mais tarde.',
                icon: 'error',
                confirmButtonText: 'Entendi!',
            });
        }
    } catch (error) {
        console.error('Erro ao imprimir etiquetas:', error);
        window.Swal.fire({
            title: 'Erro ao imprimir etiquetas!',
            text: error.response?.data?.message || 'Não foi possível imprimir as etiquetas. Tente novamente mais tarde.',
            icon: 'error',
            confirmButtonText: 'Entendi!',
        });
    } finally {
        loading.value = false;
    }
}

function viewInvoiceDetails(invoice) {
    const invoiceId = invoice.nota_fiscal?.id;
    if (invoiceId) {
        sessionStorage.setItem(`invoice_${invoiceId}`, JSON.stringify(invoice.nota_fiscal));
        router.push({
            name: 'ShowInvoiceDetails',
            params: { id: invoiceId }
        });
    }
}

function handleGroupingCarrierChanged(carrier) {
  selectedGroupingCarrier.value = carrier;
  searchGroupings();
}

async function searchGroupings() {
  if (!selectedGroupingCarrier.value) {
    groupings.value = [];
    return;
  }

  try {
    loadingGroupings.value = true;
    groupings.value = await expeditionService.searchGroupings(selectedGroupingCarrier.value);
  } catch (error) {
    console.error('Erro ao buscar agrupamentos:', error);
    groupings.value = [];
    window.Swal.fire({
      title: 'Erro ao buscar agrupamentos!',
      text: error.response?.data?.message || 'Não foi possível buscar os agrupamentos. Tente novamente mais tarde.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
  } finally {
    loadingGroupings.value = false;
  }
}

function viewGroupingDetails(grouping) {
  const groupingId = grouping.idAgrupamento;
  if (groupingId) {
    sessionStorage.setItem(`grouping_${groupingId}`, JSON.stringify(grouping));
    router.push({
      name: 'ShowGroupingDetails',
      params: { id: groupingId }
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

  if (newTab === 'groupings') {
    if (!selectedGroupingCarrier.value) {
      groupings.value = [];
    }
  }
});

onMounted(() => {
  fetchExpeditions();
  searchTinyErpProducts();
  searchTinyErpCarriersTypes();
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

