import axios from 'axios';

/**
 * Service para gerenciar chamadas de API relacionadas à lista de orçamentos
 */
export function useBudgetListService() {
    async function getBudgets(params = {}) {
        const { data } = await axios.get('v1/budgets', { params });

        // Estrutura padrão de paginação de Resource do Laravel
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
    }

    async function getUsers() {
        try {
            const response = await axios.get('v1/users/list-resellers');

            if (response.data?.data) {
                return response.data.data;
            }
            return [];
        } catch (error) {
            console.error('Erro ao buscar usuários:', error);
            return [];
        }
    }

    async function cancelBudget(budgetId) {
        const response = await axios.post('v1/budgets/cancel', {
            id: budgetId,
        });

        if (!response.data?.success) {
            throw new Error(response.data?.message || 'Erro ao cancelar orçamento');
        }

        return response.data;
    }

    async function deleteBudget(budgetId) {
        const response = await axios.delete(`v1/budgets/${budgetId}`);

        if (!response.data?.success) {
            throw new Error(response.data?.message || 'Erro ao excluir orçamento');
        }

        return response.data;
    }

    async function duplicateBudget(budgetId) {
        const { data } = await axios.post(`v1/budgets/${budgetId}/duplicate`);

        return data?.data ?? data;
    }

    return {
        getBudgets,
        getUsers,
        cancelBudget,
        deleteBudget,
        duplicateBudget,
    };
}

