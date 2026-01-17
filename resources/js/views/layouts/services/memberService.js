import axios from 'axios';

/**
 * Service para gerenciar chamadas de API relacionadas a membros
 */
export function useMemberService() {
    /**
     * Buscar membros disponíveis (designers)
     * @param {string} role - Role para filtrar (ex: 'designer')
     * @returns {Promise}
     */
    async function searchMembers(role = 'designer') {
        const response = await axios.get('v1/users/search', {
            params: { role }
        });
        return response.data;
    }

    /**
     * Adicionar um membro ao card
     * @param {number} orderBudgetId - ID do orçamento
     * @param {number} userId - ID do usuário
     * @returns {Promise}
     */
    async function addMember(orderBudgetId, userId) {
        const response = await axios.post(`v1/budgets/order-budgets/${orderBudgetId}/members`, {
            user_id: userId,
            type_page: 'layout',
        });
        return response.data;
    }

    /**
     * Remover um membro do card
     * @param {number} orderBudgetId - ID do orçamento
     * @param {number} userId - ID do usuário
     * @returns {Promise}
     */
    async function removeMember(orderBudgetId, userId) {
        const response = await axios.delete(`v1/budgets/order-budgets/${orderBudgetId}/members/${userId}`, {
            data: { type_page: 'layout' }
        });
        return response.data;
    }

    return {
        searchMembers,
        addMember,
        removeMember,
    };
}

