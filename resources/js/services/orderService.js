import axios from 'axios';

/**
 * Service para gerenciar chamadas de API relacionadas a pedidos e orçamentos
 */
export function useOrderService() {
    /**
     * Busca os detalhes de um pedido
     * @param {number|string} orderId - ID do pedido
     * @returns {Promise<Object>} Dados do pedido
     */
    async function getOrder(orderId) {
        const { data } = await axios.get(`v1/orders/${orderId}`);
        return data?.data || data;
    }

    /**
     * Busca os detalhes de um orçamento
     * @param {number|string} budgetId - ID do orçamento
     * @returns {Promise<Object>} Dados do orçamento
     */
    async function getBudget(budgetId) {
        const { data } = await axios.get(`v1/budgets/${budgetId}`);
        const budget = data?.data || data;

        if (!budget) {
            throw new Error('Orçamento não encontrado');
        }

        return budget;
    }

    /**
     * Busca os detalhes de um orçamento ou pedido
     * @param {number|string} id - ID do orçamento ou pedido
     * @param {boolean} isOrder - Se true, busca como pedido; caso contrário, como orçamento
     * @returns {Promise<Object>} Dados do orçamento ou pedido
     */
    async function getDetails(id, isOrder = false) {
        if (isOrder) {
            return await getOrder(id);
        }
        return await getBudget(id);
    }

    /**
     * Aprova um pedido
     * @param {number|string} orderId - ID do pedido
     * @returns {Promise<Object>} Resposta da API
     */
    async function approveOrder(orderId) {
        const { data } = await axios.post('v1/orders/approve', {
            id: orderId,
        });

        // API retorna OrderResource ({ data: {...} })
        return data?.data || data;
    }

    /**
     * Gera um link de pagamento para um pedido
     * @param {number|string} orderId - ID do pedido
     * @returns {Promise<Object>} Dados atualizados do pedido com o link de pagamento
     */
    async function generatePaymentLink(orderId) {
        const { data } = await axios.post(`v1/orders/${orderId}/generate-payment-link`);
        return data?.data || data;
    }

    /**
     * Busca solicitações de artes de layout
     * @param {Object} params - Parâmetros da busca
     * @param {number|string} [params.order_id] - ID do pedido
     * @param {number|string} [params.budget_id] - ID do orçamento
     * @param {number|string} [params.dealer_id] - ID do revendedor
     * @returns {Promise<Array>} Lista de solicitações de artes
     */
    async function getRequestLayoutArts(params = {}) {
        const { data: response } = await axios.get('v1/budgets/request-layout-arts', { params });
        const responseData = response?.data || response;

        if (responseData?.success && Array.isArray(responseData.data)) {
            return responseData.data;
        }

        return [];
    }

    /**
     * Faz upload de uma arte para uma interação
     * @param {FormData} formData - Dados do formulário com a arte
     * @returns {Promise<Object>} Resposta da API
     */
    async function uploadArt(formData) {
        const response = await axios.post('v1/budgets/order-budgets/upload-art', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        if (!response.data?.success) {
            throw new Error(response.data?.message || 'Erro ao enviar arte');
        }

        return response.data;
    }

    /**
     * Verifica se um link de pagamento está expirado
     * @param {string} expirationDate - Data de expiração do link
     * @returns {boolean} True se o link estiver expirado
     */
    function isPaymentLinkExpired(expirationDate) {
        if (!expirationDate) {
            return false;
        }

        try {
            const expiration = new Date(expirationDate);
            const now = new Date();
            return expiration < now;
        } catch (error) {
            console.error('Erro ao verificar expiração do link:', error);
            return false;
        }
    }

    return {
        getOrder,
        getBudget,
        getDetails,
        approveOrder,
        generatePaymentLink,
        getRequestLayoutArts,
        uploadArt,
        isPaymentLinkExpired,
    };
}
