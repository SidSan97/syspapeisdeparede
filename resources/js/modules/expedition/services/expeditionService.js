import { http } from '@/lib/http';
import { createCrudService } from '@/services/baseCrudService';

const endpoint = '/v1/orders/expedition';

const crud = createCrudService(endpoint, {
  transformParams(params) {
    return {
      page: params?.page,
      search: params?.search,
      stage: params?.stage,
    };
  },
});

export const expeditionService = {
  ...crud,

  // async all(params = {}) {
  //   const { data } = await http.get('v1/orders/expedition', { params });

  //   return data;
  // },

  /** @deprecated */
  async fetchExpeditions(params = {}) {
    return this.all(params);
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
  async viewSeparationLabelsPdf(orderBudgetIds) {
    const response = await http.post(
      'v1/generate-separation-labels-pdf',
      { order_budget_ids: orderBudgetIds },
      { responseType: 'blob' },
    );
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
  async invoiceOrderCards(orderBudgetIds, packing = {}) {
    const { data } = await http.post('v1/orders/order-budgets/ready-to-expedition', {
      order_budget_ids: orderBudgetIds,
      packer_name: packing.packer_name,
      quantidade_volumes: packing.quantidade_volumes,
    });
    return data;
  },
};
