<template>
  <section class="content">
    <Page title="Expedição">
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
            :class="{ active: activeTab === 'in-separation' }"
            @click="activeTab = 'in-separation'"
            type="button"
            role="tab"
          >
            Em separação
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
            ref="separationTableRef"
            :expeditions="expeditions"
            :loading="loading"
            :printing-labels="printingLabels"
            :pagination-data="paginationData"
            @view-details="viewDetails"
            @generate-separation-label="handleGenerateSeparationLabel"
            @print-selected-labels="handlePrintSelectedLabels"
            @page-change="handleSeparationPageChange"
            @search-change="handleSeparationSearchChange"
          />
        </div>

        <!-- Aba Em separação -->
        <div
          v-show="activeTab === 'in-separation'"
          class="tab-pane"
          :class="{ active: activeTab === 'in-separation' }"
          role="tabpanel"
        >
          <InSeparationTable
            :expeditions="inSeparationExpeditions"
            :loading="loadingInSeparation"
            :invoicing="invoicingOrderCards"
            :pagination-data="inSeparationPaginationData"
            @view-details="viewDetails"
            @invoice-order-cards="handleInvoiceOrderCards"
            @page-change="handleInSeparationPageChange"
            @search-change="handleInSeparationSearchChange"
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
            @print-labels="handlePrintCarrierLabels"
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
            @generate-invoice="handleGenerateInvoice"
            @generate-danfe="handleGenerateDanfe"
          />
        </div>
      </div>
    </Page>
  </section>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import Page from '@/components/page/Page.vue';
import SeparationTable from '@/modules/expedition/components/SeparationTable.vue';
import InSeparationTable from '@/modules/expedition/components/InSeparationTable.vue';
import ExpeditionTable from '@/modules/expedition/components/ExpeditionTable.vue';
import InvoiceTable from '@/modules/expedition/components/InvoiceTable.vue';
import GroupingsTable from '@/modules/expedition/components/GroupingsTable.vue';
import { useExpeditionData } from '@/modules/expedition/composables/useExpeditionData';
import { useExpeditionSelection } from '@/modules/expedition/composables/useExpeditionSelection';
import { useExpeditionActions } from '@/modules/expedition/composables/useExpeditionActions';

const activeTab = ref('separation');
const selectedCarrier = ref(null);
const selectedGroupingCarrier = ref(null);
const printingLabels = ref(false);
const invoicingOrderCards = ref(false);
const separationTableRef = ref(null);
const separationSearchQuery = ref('');
const inSeparationSearchQuery = ref('');

const {
  expeditions,
  inSeparationExpeditions,
  invoices,
  invoicesList,
  groupings,
  loading,
  loadingInSeparation,
  loadingGroupings,
  paginationData,
  inSeparationPaginationData,
  fetchExpeditions,
  fetchInSeparation,
  fetchInvoices,
  searchInvoices,
  searchGroupings,
  searchTinyErpProducts,
  searchTinyErpCarriersTypes,
} = useExpeditionData();

const {
  selectedInvoices,
  isAllSelected,
  toggleSelectAll,
  toggleSelectInvoice,
  expedir: handleExpedir,
} = useExpeditionSelection(invoicesList, searchInvoices);

const {
  generateSeparationLabel,
  generateSeparationLabels,
  generateInvoice,
  generateDanfe,
  printCarrierLabels,
  invoiceOrderCards,
  viewDetails,
  viewInvoiceDetails,
  viewGroupingDetails,
} = useExpeditionActions();

function handleGroupingCarrierChanged(carrier) {
  selectedGroupingCarrier.value = carrier;
  searchGroupings(carrier);
}

function expedir() {
  handleExpedir(loading);
}

function handleGenerateSeparationLabel(expedition) {
  generateSeparationLabel(expedition, loading, async () => {
    await Promise.all([
      fetchExpeditions(paginationData.value?.current_page || 1, separationSearchQuery.value),
      fetchInSeparation(
        inSeparationPaginationData.value?.current_page || 1,
        inSeparationSearchQuery.value,
      ),
    ]);
  });
}

async function handlePrintSelectedLabels(orderBudgetIds) {
  await generateSeparationLabels(orderBudgetIds, printingLabels, async () => {
    separationTableRef.value?.clearSelection?.();
    await Promise.all([
      fetchExpeditions(paginationData.value?.current_page || 1, separationSearchQuery.value),
      fetchInSeparation(
        inSeparationPaginationData.value?.current_page || 1,
        inSeparationSearchQuery.value,
      ),
    ]);
  });
}

function handleInvoiceOrderCards(expedition) {
  invoiceOrderCards(expedition, invoicingOrderCards, async () => {
    await Promise.all([
      fetchInSeparation(
        inSeparationPaginationData.value?.current_page || 1,
        inSeparationSearchQuery.value,
      ),
      fetchInvoices(),
    ]);
  });
}

function handleGenerateInvoice(invoice) {
  generateInvoice(invoice, loading, fetchInvoices);
}

function handleGenerateDanfe(id) {
  generateDanfe(id, loading);
}

function handlePrintCarrierLabels(groupingId) {
  printCarrierLabels(groupingId, loading);
}

function handleSeparationPageChange(page) {
  fetchExpeditions(page, separationSearchQuery.value);
}

function handleSeparationSearchChange(search) {
  separationSearchQuery.value = search;
  fetchExpeditions(1, search);
}

function handleInSeparationPageChange(page) {
  fetchInSeparation(page, inSeparationSearchQuery.value);
}

function handleInSeparationSearchChange(search) {
  inSeparationSearchQuery.value = search;
  fetchInSeparation(1, search);
}

watch(activeTab, (newTab) => {
  if (newTab === 'in-separation') {
    fetchInSeparation(
      inSeparationPaginationData.value?.current_page || 1,
      inSeparationSearchQuery.value,
    );
  }

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

<style scoped></style>
