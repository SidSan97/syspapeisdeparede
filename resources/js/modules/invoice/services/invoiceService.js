import { http } from '@/lib/http';

/**
 * Chamadas HTTP relacionadas à nota fiscal (Tiny ERP).
 */
export const invoiceService = {
  /**
   * @param {string|number} orderId
   * @returns {Promise<object>}
   */
  async getInvoiceByOrderId(orderId) {
    const { data } = await http.get(`v1/tiny-erp/invoices/${orderId}`);
    return data;
  },
};
