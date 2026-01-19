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
              @generate-separation-label="handleGenerateSeparationLabel"
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
      </div>
    </Page>
  </section>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import Page from '@/components/page/Page.vue';
import SeparationTable from './components/SeparationTable.vue';
import ExpeditionTable from './components/ExpeditionTable.vue';
import InvoiceTable from './components/InvoiceTable.vue';
import GroupingsTable from './components/GroupingsTable.vue';
import { useExpeditionData } from './composables/useExpeditionData';
import { useExpeditionSelection } from './composables/useExpeditionSelection';
import { useExpeditionActions } from './composables/useExpeditionActions';

const activeTab = ref('separation');
const selectedCarrier = ref(null);
const selectedGroupingCarrier = ref(null);

// Composables
const {
  expeditions,
  invoices,
  invoicesList,
  groupings,
  loading,
  loadingGroupings,
  fetchExpeditions,
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
  generateInvoice,
  generateDanfe,
  printCarrierLabels,
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
  generateSeparationLabel(expedition, loading);
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

