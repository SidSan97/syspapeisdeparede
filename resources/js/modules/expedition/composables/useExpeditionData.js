import { ref } from 'vue';
import { expeditionService } from '../services/expeditionService';

export function useExpeditionData() {
  const expeditions = ref([]);
  const invoices = ref([]);
  const invoicesList = ref([]);
  const groupings = ref([]);
  const loading = ref(true);
  const loadingGroupings = ref(false);
  const paginationData = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0,
  });

  async function fetchExpeditions(page = 1, search = null) {
    try {
      loading.value = true;
      const params = {
        page,
      };

      if (search && search.trim()) {
        params.search = search.trim();
      }

      const result = await expeditionService.fetchExpeditions(params);
      expeditions.value = result.data;

      paginationData.value = result.pagination;
    } catch (error) {
      console.error('Erro ao buscar expedições:', error);
      expeditions.value = [];
      paginationData.value = {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
        from: 0,
        to: 0,
      };
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

  async function searchGroupings(carrier) {
    if (!carrier) {
      groupings.value = [];
      return;
    }

    try {
      loadingGroupings.value = true;
      groupings.value = await expeditionService.searchGroupings(carrier);
    } catch (error) {
      console.error('Erro ao buscar agrupamentos:', error);
      groupings.value = [];
      window.Swal.fire({
        title: 'Erro ao buscar agrupamentos!',
        text:
          error.response?.data?.message ||
          'Não foi possível buscar os agrupamentos. Tente novamente mais tarde.',
        icon: 'error',
        confirmButtonText: 'Entendi!',
      });
    } finally {
      loadingGroupings.value = false;
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

  return {
    expeditions,
    invoices,
    invoicesList,
    groupings,
    loading,
    loadingGroupings,
    paginationData,
    fetchExpeditions,
    fetchInvoices,
    searchInvoices,
    searchGroupings,
    searchTinyErpProducts,
    searchTinyErpCarriersTypes,
  };
}
