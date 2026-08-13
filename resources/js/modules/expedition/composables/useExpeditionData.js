import { ref } from 'vue';
import { expeditionService } from '../services/expeditionService';

const emptyPagination = {
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0,
};

function resolvePagination(result) {
  if (result?.pagination) {
    return result.pagination;
  }

  return {
    current_page: result?.current_page ?? 1,
    last_page: result?.last_page ?? 1,
    per_page: result?.per_page ?? 15,
    total: result?.total ?? 0,
    from: result?.from ?? 0,
    to: result?.to ?? 0,
  };
}

export function useExpeditionData() {
  const expeditions = ref([]);
  const inSeparationExpeditions = ref([]);
  const invoices = ref([]);
  const invoicesList = ref([]);
  const groupings = ref([]);
  const loading = ref(true);
  const loadingInSeparation = ref(false);
  const loadingGroupings = ref(false);
  const paginationData = ref({ ...emptyPagination });
  const inSeparationPaginationData = ref({ ...emptyPagination });

  async function fetchExpeditions(page = 1, search = null) {
    try {
      loading.value = true;
      const params = {
        page,
        stage: 'separation',
      };

      if (search && search.trim()) {
        params.search = search.trim();
      }

      const result = await expeditionService.fetchExpeditions(params);
      expeditions.value = result.data ?? [];
      paginationData.value = resolvePagination(result);
    } catch (error) {
      console.error('Erro ao buscar expedições:', error);
      expeditions.value = [];
      paginationData.value = { ...emptyPagination };
    } finally {
      loading.value = false;
    }
  }

  async function fetchInSeparation(page = 1, search = null) {
    try {
      loadingInSeparation.value = true;
      const params = {
        page,
        stage: 'in_separation',
      };

      if (search && search.trim()) {
        params.search = search.trim();
      }

      const result = await expeditionService.fetchExpeditions(params);
      inSeparationExpeditions.value = result.data ?? [];
      inSeparationPaginationData.value = resolvePagination(result);
    } catch (error) {
      console.error('Erro ao buscar cards em separação:', error);
      inSeparationExpeditions.value = [];
      inSeparationPaginationData.value = { ...emptyPagination };
    } finally {
      loadingInSeparation.value = false;
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
  };
}
