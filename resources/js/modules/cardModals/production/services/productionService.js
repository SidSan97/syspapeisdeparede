import axios from 'axios';

/**
 * Service para gerenciar chamadas de API relacionadas a produção
 */
export function useProductionService() {
    async function getColumns() {
        const { data } = await axios.get('v1/production-column-names');
        const payload = Array.isArray(data?.data) ? data.data : [];
        return payload.map(col => ({
            id: col.id,
            name: col.name,
        }));
    }

    async function getLayouts() {
        const { data } = await axios.get('v1/orders/production-layouts');
        return Array.isArray(data?.data) ? data.data : [];
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
        const { data } = await axios.delete(`v1/production-column-names/${columnId}`);
        return data;
    }

    async function updateCardColumn(orderBudgetId, productionColumnNamesId) {
        const { data } = await axios.post('v1/budgets/layouts/update-column', {
            order_budget_id: orderBudgetId,
            layout_column_names_id: productionColumnNamesId,
            type_page: 'product',
        });
        return data;
    }

    return {
        getColumns,
        getLayouts,
        createColumn,
        updateColumn,
        deleteColumn,
        updateCardColumn,
    };
}


