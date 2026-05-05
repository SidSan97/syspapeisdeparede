import { http } from '@/lib/http';

/**
 * Service para chamadas de API de solicitações de artes (request layout arts).
 */
export const requestArtService = {
  async getRequestLayoutArts(params) {
    const response = await http.get('v1/budgets/request-layout-arts', {
      params,
    });
    return response.data;
  },

  async uploadArt(formData) {
    return http.post('v1/budgets/order-budgets/upload-art', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
  },

  async updateArtApprovalStatus(payload) {
    const response = await http.patch('v1/budgets/request-layout-arts/status', payload);
    return response.data;
  },
};
