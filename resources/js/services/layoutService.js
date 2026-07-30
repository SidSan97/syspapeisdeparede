import { http } from '@/lib/http';

export const layoutService = {
  async getColumns() {
    const { data } = await http.get('v1/layout-column-names');
    return data.data;
  },

  async getLayouts(orderId = null) {
    const { data } = await http.get(`v1/orders/layouts${orderId ? `/${orderId}` : ''}`);

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

  /** Power-Up Activity: inicia/retoma o cronômetro do card. */
  async startActivity(orderBudgetId) {
    const { data } = await http.post(
      `v1/budgets/order-budgets/${orderBudgetId}/activity/start`,
    );
    return data;
  },

  /** Power-Up Activity: pausa o cronômetro acumulando o tempo da sessão. */
  async pauseActivity(orderBudgetId) {
    const { data } = await http.post(
      `v1/budgets/order-budgets/${orderBudgetId}/activity/pause`,
    );
    return data;
  },

  /** Marca o card como concluído (pausa o cronômetro automaticamente). */
  async completeOrderBudget(orderBudgetId, payload = {}) {
    const { data } = await http.post(
      `v1/budgets/order-budgets/${orderBudgetId}/complete`,
      payload,
    );
    return data;
  },
};
