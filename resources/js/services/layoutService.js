import { http } from '@/lib/http';

export const layoutService = {
  async getColumns() {
    const { data } = await http.get('v1/layout-column-names');
    return data.data;
  },

  async getLayouts() {
    const { data } = await http.get('v1/orders/layouts');

    const layouts = Array.isArray(data) ? data : [];

    return layouts.map((item) => ({
      ...item,
      column: Number(item.layout_column_names_id),
    }));
  },

  async createColumn(name) {
    const { data } = await http.post('v1/layout-column-names', { name });
    return data;
  },

  async updateColumn(columnId, newName) {
    const { data } = await http.put(`v1/layout-column-names/${columnId}`, {
      name: newName,
    });
    return data;
  },

  async deleteColumn(columnId, targetColumnId) {
    await http.delete(`v1/layout-column-names/${columnId}`, {
      data: {
        target_column_id: targetColumnId,
      },
    });
    return true;
  },

  async reorderColumns(columns) {
    await http.post('v1/layout-column-names/reorder', { columns });
  },

  async updateCardColumn(orderBudgetId, layoutColumnNamesId) {
    const { data } = await http.post('v1/budgets/layouts/update-column', {
      order_budget_id: orderBudgetId,
      layout_column_names_id: layoutColumnNamesId,
      type_page: 'layout',
    });
    return data;
  },

  /** Registra início do fluxo (order_budgets.started_at). */
  async startOrderBudget(orderBudgetId) {
    const { data } = await http.post(`v1/budgets/order-budgets/${orderBudgetId}/start`);
    return data;
  },

  /** Registra conclusão do fluxo (order_budgets.finished_at). */
  async finishOrderBudget(orderBudgetId) {
    const { data } = await http.post(`v1/budgets/order-budgets/${orderBudgetId}/finish`);
    return data;
  },
};
