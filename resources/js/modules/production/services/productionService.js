import axios from 'axios';

/**
 * Service para gerenciar chamadas de API relacionadas a produção
 */
export function useProductionService() {
    async function getColumns() {
        const { data } = await axios.get('v1/production-column-names');
        const payload = Array.isArray(data) ? data : (Array.isArray(data?.data) ? data.data : []);
        return payload.map(col => ({
            id: col.id,
            name: col.name,
        }));
    }

    async function getLayouts() {
        const { data } = await axios.get('v1/orders/production-layouts');
        return Array.isArray(data) ? data : [];
    }

    async function createColumn(name) {
        const { data } = await axios.post('v1/production-column-names', { name });
        return data;
    }

    async function updateColumn(columnId, name) {
        const { data } = await axios.put(`v1/production-column-names/${columnId}`, { name });
        return data;
    }

    async function deleteColumn(columnId) {
        await axios.delete(`v1/production-column-names/${columnId}`);
        return true;
    }

    async function updateCardColumn(orderBudgetId, productionColumnNamesId) {
        const { data } = await axios.post('v1/budgets/layouts/update-column', {
            order_budget_id: orderBudgetId,
            layout_column_names_id: productionColumnNamesId,
            type_page: 'product',
        });
        return data;
    }

    /**
     * Busca os relatórios de produção de um card
     * @param {number|string} orderBudgetId - ID do order budget
     * @returns {Promise<Array>} Lista de relatórios de produção
     */
    async function getProductionReports(orderBudgetId) {
        if (!orderBudgetId) {
            return [];
        }

        try {
            const { data } = await axios.get(`v1/orders/order-budgets/${orderBudgetId}/production-reports`);
            return Array.isArray(data) ? data : [];
        } catch (error) {
            console.error('Erro ao buscar relatórios de produção:', error);
            return [];
        }
    }

    /**
     * Marca um card como produzido
     * @param {number|string} orderBudgetId - ID do order budget
     * @returns {Promise<Object>} Dados atualizados do card
     */
    async function markAsProduced(orderBudgetId) {
        if (!orderBudgetId) {
            throw new Error('ID do card é obrigatório');
        }

        const { data } = await axios.post(`v1/orders/order-budgets/${orderBudgetId}/mark-as-produced`);
        return data || null;
    }

    return {
        getColumns,
        getLayouts,
        createColumn,
        updateColumn,
        deleteColumn,
        updateCardColumn,
        getProductionReports,
        markAsProduced,
    };
}


