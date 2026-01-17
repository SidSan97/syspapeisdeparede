import axios from 'axios';

/**
 * Service para gerenciar chamadas de API relacionadas a artes
 */
export function useArtService() {
    /**
     * Fazer upload de uma arte
     * @param {FormData} formData - FormData com os dados da arte
     * @returns {Promise}
     */
    async function uploadArt(formData) {
        const response = await axios.post('v1/budgets/order-budgets/upload-art', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        return response.data;
    }

    /**
     * Buscar solicitações de artes de layout
     * @param {number} orderBudgetId - ID do orçamento
     * @param {number} orderId - ID do pedido (opcional)
     * @returns {Promise}
     */
    async function fetchRequestLayoutArts(orderBudgetId, orderId = null) {
        const params = {
            order_budget_id: orderBudgetId,
        };

        if (orderId) {
            params.order_id = orderId;
        }

        const response = await axios.get('v1/budgets/request-layout-arts', {
            params,
        });
        return response.data;
    }

    return {
        uploadArt,
        fetchRequestLayoutArts,
    };
}

