import axios from 'axios';

export function useExpeditionService() {
  async function fetchExpeditions(params = {}) {
    const { data } = await axios.get('v1/orders/expedition', { params });

    if (data?.success && data?.data) {
      return {
        items: Array.isArray(data.data.data) ? data.data.data : [],
        pagination: {
          current_page: data.data.current_page || 1,
          last_page: data.data.last_page || 1,
          per_page: data.data.per_page || 15,
          total: data.data.total || 0,
          from: data.data.from || 0,
          to: data.data.to || 0,
        },
      };
    }

    return {
      items: [],
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
        from: 0,
        to: 0,
      },
    };
  }

  async function fetchInvoices() {
    const { data } = await axios.get('v1/orders/ready-for-invoice');
    const payload = Array.isArray(data?.data) ? data.data : [];
    return payload;
  }

  async function searchInvoices() {
    const { data } = await axios.get('v1/search-invoices');
    if (data.success && data.data.notas_fiscais) {
      return data.data.notas_fiscais;
    }
    return [];
  }

  async function searchGroupings(carrier) {
    const { data } = await axios.get(`v1/search-groupings/${encodeURIComponent(carrier)}`);
    if (data.success && data.data) {
      return data.data.agrupamentos || [];
    }
    return [];
  }

  async function searchTinyErpProducts() {
    const { data } = await axios.get('v1/tiny-erp/all');
    return data;
  }

  async function searchTinyErpCarriersTypes() {
    const { data } = await axios.get('v1/tiny-erp/carriers-types');
    return data;
  }

  async function generateSeparationLabel(expeditionId) {
    const { data } = await axios.get(`v1/generate-separation-label/${expeditionId}`);
    return data;
  }

  async function viewSeparationLabelPdf(orderBudgetId) {
    const response = await axios.get(`v1/generate-separation-label-pdf/${orderBudgetId}`, {
      responseType: 'blob',
    });
    return response.data;
  }

  async function generateInvoice(orderId) {
    const { data } = await axios.post(`v1/generate-invoice/${orderId}`);
    return data;
  }

  async function generateDanfe(nfId) {
    const { data } = await axios.get(`v1/generate-danfe/${nfId}`);
    return data;
  }

  async function sendInvoiceToExpedition(invoiceIds, carrier, orderIds) {
    const { data } = await axios.post('v1/send-invoice-to-expedition', {
      invoice_ids: invoiceIds,
      carrier: carrier,
      order_ids: orderIds
    });
    return data;
  }

  async function printCarrierLabels(groupingId) {
    const { data } = await axios.get(`v1/generate-grouping-print-label/${groupingId}`);
    return data;
  }

  return {
    fetchExpeditions,
    fetchInvoices,
    searchInvoices,
    searchGroupings,
    searchTinyErpProducts,
    searchTinyErpCarriersTypes,
    generateSeparationLabel,
    viewSeparationLabelPdf,
    generateInvoice,
    generateDanfe,
    sendInvoiceToExpedition,
    printCarrierLabels,
  };
}
