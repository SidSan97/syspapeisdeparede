import { http } from '@/lib/http';
import { createCrudService } from './baseCrudService';

const endpoint = '/v1/budgets';

const crud = createCrudService(endpoint, {
  transformParams(params) {
    return {
      page: params?.page,
      search: params?.search,
      status: params?.status,
      user_id: params?.user_id,
      date_from: params?.date_from,
      date_to: params?.date_to,
      ...params?.filters,
    };
  },
});

export const budgetService = {
  ...crud,

  /** @deprecated usar updateStatus */
  async cancel(id) {
    return await this.updateBudgetStatus(id, 'canceled');
  },

  async duplicate(id) {
    if (!id || id <= 0) {
      throw new Error('ID inválido');
    }

    const { data } = await http.post(`${endpoint}/${id}/copies`);
    return data.data;
  },

  async updateBudgetStatus(id, status) {
    if (!id || id <= 0) {
      throw new Error('ID inválido');
    }

    const allowedStatuses = ['canceled', 'open', 'approved'];
    if (!allowedStatuses.includes(status)) {
      throw new Error(`Status inválido. Valores permitidos: ${allowedStatuses.join(', ')}`);
    }

    const { data } = await http.patch(`${endpoint}/${id}/status`, {
      status,
    });
    return data.data;
  },

  async createOrder(id, payload = {}) {
    if (!id || id <= 0) {
      throw new Error('ID inválido');
    }

    const { data } = await http.post(`${endpoint}/${id}/orders`, payload);
    return data.data;
  },

  async getTinyErpProducts() {
    const { data } = await http.get('v1/tiny-erp/all');
    return data;
  },

  /**
   * Faz upload de uma imagem de referência do modelo (uma por vez)
   */
  async uploadReferringFile(file) {
    const formData = new FormData();
    formData.append('file', file);

    const { data } = await http.post('v1/budgets/upload-referring-file', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    return data?.path ?? data;
  },

  async calculateFreight(cep, productData) {
    const { data } = await http.post('v1/frenet/calculate-shipping', {
      cep,
      productData,
    });

    // Mapear os dados da resposta para o formato esperado
    if (data?.data?.ShippingSevicesArray && Array.isArray(data.data.ShippingSevicesArray)) {
      return data.data.ShippingSevicesArray.filter((service) => !service.Error) // Filtrar apenas serviços sem erro
        .map((service) => ({
          name: `${service.Carrier} - ${service.ServiceDescription}`,
          price: parseFloat(service.ShippingPrice) || 0,
          deliveryTime: parseInt(service.DeliveryTime) || 0,
        }));
    }

    return [];
  },
};
