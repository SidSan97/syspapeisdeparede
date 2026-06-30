import { http } from '@/lib/http';

export const productionService = {
  async getColumns() {
    const { data } = await http.get('v1/production-column-names');
    return data.data;
  },

  async getLayouts() {
    const { data } = await http.get('v1/orders/production-layouts');

    const layouts = Array.isArray(data) ? data : [];

    return layouts.map((item) => ({
      ...item,
      column: Number(item.production_column_names_id),
    }));
  },

  async createColumn(name) {
    const { data } = await http.post('v1/production-column-names', { name });
    return data;
  },

  async updateColumn(columnId, newName) {
    const { data } = await http.put(`v1/production-column-names/${columnId}`, {
      name: newName,
    });
    return data;
  },

  async reorderColumns(columns) {
    await http.post('v1/production-column-names/reorder', { columns });
  },

  async deleteColumn(columnId, targetColumnId) {
    await http.delete(`v1/production-column-names/${columnId}`, {
      data: {
        target_column_id: targetColumnId,
      },
    });
    return true;
  },

  async updateCardColumn(orderBudgetId, productionColumnNamesId) {
    const { data } = await http.post('v1/budgets/layouts/update-column', {
      order_budget_id: orderBudgetId,
      layout_column_names_id: productionColumnNamesId,
      type_page: 'product',
    });
    return data;
  },

  /**
   * Busca os relatórios de produção de um card
   * @param {number|string} orderBudgetId - ID do order budget
   * @returns {Promise<Array>} Lista de relatórios de produção
   */
  async getProductionReports(orderBudgetId) {
    if (!orderBudgetId) return [];

    try {
      const { data } = await http.get(
        `v1/orders/order-budgets/${orderBudgetId}/production-reports`,
      );
      return Array.isArray(data) ? data : [];
    } catch (error) {
      console.error('Erro ao buscar relatórios de produção:', error);
      return [];
    }
  },

  /**
   * Marca um card como produzido
   * @param {number|string} orderBudgetId - ID do order budget
   * @returns {Promise<Object>} Dados atualizados do card
   */
  async markAsProduced(orderBudgetId) {
    if (!orderBudgetId) {
      throw new Error('ID do card é obrigatório');
    }

    const { data } = await http.post(`v1/orders/order-budgets/${orderBudgetId}/mark-as-produced`);
    return data || null;
  },
};
