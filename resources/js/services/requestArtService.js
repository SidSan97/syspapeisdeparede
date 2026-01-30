import axios from 'axios';

/**
 * Service para chamadas de API de solicitações de artes (request layout arts).
 */
export function useRequestArtService() {

  async function getRequestLayoutArts(params) {
    const response = await axios.get('v1/budgets/request-layout-arts', { params });
    return response.data;
  }

  async function uploadArt(formData) {
    return axios.post('v1/budgets/order-budgets/upload-art', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
  }

  return {
    getRequestLayoutArts,
    uploadArt,
  };
}
