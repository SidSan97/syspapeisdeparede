import { http } from '@/lib/http';

export const expeditionService = {
  async all(params = {}) {
    const { data } = await http.get('v1/orders/expedition', { params });

    return data;
  },

  /** @deprecated */
  async fetchExpeditions(params = {}) {
    const { data } = await http.get('v1/orders/expedition', { params });

    // Estrutura padrão de paginação do Laravel: { data, meta, links }
    if (data && Array.isArray(data.data)) {
      const meta = data.meta || {};

      return {
        items: data.data,
        pagination: {
          current_page: meta.current_page || 1,
          last_page: meta.last_page || 1,
          per_page: meta.per_page || 15,
          total: meta.total || 0,
          from: meta.from || 0,
          to: meta.to || 0,
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
  },
  async fetchInvoices() {
    const { data } = await http.get('v1/orders/ready-for-invoice');
    // API agora retorna diretamente o array de pedidos prontos para faturar
    return Array.isArray(data) ? data : [];
  },
  async searchInvoices() {
    const { data } = await http.get('v1/search-invoices');
    if (Array.isArray(data?.notas_fiscais)) {
      return data.notas_fiscais;
    }

    return [];
  },
  async searchGroupings(carrier) {
    const { data } = await http.get(`v1/search-groupings/${encodeURIComponent(carrier)}`);
    if (Array.isArray(data?.agrupamentos)) {
      return data.agrupamentos;
    }

    return [];
  },
  async searchTinyErpProducts() {
    const { data } = await http.get('v1/tiny-erp/all');
    return data;
  },
  async searchTinyErpCarriersTypes() {
    const { data } = await http.get('v1/tiny-erp/carriers');
    return data;
  },
  async generateSeparationLabel(expeditionId) {
    const { data } = await http.get(`v1/generate-separation-label/${expeditionId}`);
    return data;
  },
  async viewSeparationLabelPdf(orderBudgetId) {
    const response = await http.get(`v1/generate-separation-label-pdf/${orderBudgetId}`, {
      responseType: 'blob',
    });
    return response.data;
  },
  async generateInvoice(orderId) {
    const { data } = await http.post(`v1/generate-invoice/${orderId}`);
    return data;
  },
  async generateDanfe(nfId) {
    const { data } = await http.get(`v1/generate-danfe/${nfId}`);
    return data;
  },
  async sendInvoiceToExpedition(invoiceIds, carrier, orderIds) {
    const { data } = await http.post('v1/send-invoice-to-expedition', {
      invoice_ids: invoiceIds,
      carrier: carrier,
      order_ids: orderIds,
    });
    return data;
  },
  async printCarrierLabels(groupingId) {
    const { data } = await http.get(`v1/generate-grouping-print-label/${groupingId}`);
    return data;
  },
};
