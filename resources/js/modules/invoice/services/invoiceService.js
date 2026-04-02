import axios from 'axios';

/**
 * Chamadas HTTP relacionadas à nota fiscal (Tiny ERP).
 */
export function useInvoiceService() {
    /**
     * @param {string|number} orderId
     * @returns {Promise<object>}
     */
    async function getInvoiceByOrderId(orderId) {
        const { data } = await axios.get(`v1/tiny-erp/invoice-by-order-id/${orderId}`);
        return data;
    }

    return {
        getInvoiceByOrderId,
    };
}
