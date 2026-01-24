import axios from 'axios';

/**
 * Service para gerenciar chamadas de API relacionadas a layouts
 */
export function useLayoutService() {
    async function getColumns() {
        const { data } = await axios.get('v1/layout-column-names');
        const payload = Array.isArray(data) ? data : (Array.isArray(data?.data) ? data.data : []);
        return payload.map(col => ({
            id: col.id,
            name: col.name,
        }));
    }

    async function getLayouts() {
        const { data } = await axios.get('v1/orders/layouts');
        return Array.isArray(data) ? data : [];
    }

    async function createColumn(name) {
        const { data } = await axios.post('v1/layout-column-names', { name });
        return data;
    }

    async function updateColumn(columnId, name) {
        const { data } = await axios.put(`v1/layout-column-names/${columnId}`, { name });
        return data;
    }

    async function deleteColumn(columnId) {
        await axios.delete(`v1/layout-column-names/${columnId}`);
        return true;
    }

    async function updateCardColumn(orderBudgetId, layoutColumnNamesId) {
        const { data } = await axios.post('v1/budgets/layouts/update-column', {
            order_budget_id: orderBudgetId,
            layout_column_names_id: layoutColumnNamesId,
            type_page: 'layout',
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

