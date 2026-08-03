import { http } from '@/lib/http';

import { createCrudService } from '@/services/baseCrudService';

const endpoint = '/v1/orders';

const crud = createCrudService(endpoint, {
  transformParams(params) {
    return {
      page: params?.page,
      'filter[search]': params?.search,
      'filter[status]': params?.status,
      'filter[user_id]': params?.user_id,
      'filter[created_from]': params?.date_from,
      'filter[created_to]': params?.date_to,
      ...params?.filters,
    };
  },
});

export const orderService = {
  ...crud,

  async merge(payload) {
    const { data } = await http.post(`${endpoint}/merge`, payload);
    return data;
  },

  async approve(id) {
    const { data } = await http.post(`${endpoint}/${id}/approve`);
    return data?.data || data;
  },

  async cancel(id) {
    const { data } = await http.post(`${endpoint}/${id}/cancel`);
    return data?.data || data;
  },

  async generatePaymentLink(id) {
    const { data } = await http.post(`${endpoint}/${id}/generate-payment-link`);
    return data?.data || data;
  },

  async generatePaymentLinkByComponents(id, payload) {
    const { data } = await http.post(`${endpoint}/${id}/payment-links`, payload);
    return data?.data || data;
  },

  async getRequestLayoutArts(params = {}) {
    const { data: response } = await http.get(`${endpoint}/request-layout-arts`, { params });
    const responseData = response?.data || response;

    if (responseData?.success && Array.isArray(responseData.data)) {
      return responseData.data;
    }

    return [];
  },

  async uploadArt(formData) {
    const response = await http.post('v1/budgets/order-budgets/upload-art', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    if (!response.data?.success) {
      throw new Error(response.data?.message || 'Erro ao enviar arte');
    }

    return response.data;
  },

  async registerPayment(formData) {
    const { data } = await http.post('v1/budgets/register-payment', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    return data;
  },

  isPaymentLinkExpired(expirationDate) {
    if (!expirationDate) return false;

    try {
      const expiration = new Date(expirationDate);
      const now = new Date();
      return expiration < now;
    } catch (error) {
      console.error('Erro ao verificar expiração do link:', error);
      return false;
    }
  },
};
