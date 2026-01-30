import axios from 'axios';

/**
 * Service para gerenciar chamadas de API relacionadas a descrição
 */
export function useDescriptionService() {
    /**
     * Atualizar a descrição de um orçamento
     * @param {number} orderBudgetId - ID do orçamento
     * @param {string} description - Nova descrição
     * @param {string} typePage - Tipo de página ('layout' ou 'product')
     * @returns {Promise}
     */
    async function updateDescription(orderBudgetId, description, typePage = 'layout') {
        const response = await axios.put(`v1/budgets/order-budgets/${orderBudgetId}/description`, {
            description: description,
            type_page: typePage,
        });
        return response.data;
    }

    return {
        updateDescription,
    };
}

