import axios from 'axios';

/**
 * Service para gerenciar chamadas de API relacionadas a pedidos de orçamento
 */
export function useBudgetOrderService() {
  async function getCollectionCategories() {
    const { data } = await axios.get('v1/collection-categories', {
      params: { tree: true },
    });

    const payload = data?.data ?? data ?? {};
    const items = Array.isArray(payload) ? payload : (payload.items ?? []);

    const rootCategories = items.filter(item => !item.parent_id);

    return rootCategories;
  }

  /**
   * Busca categorias cujo nome contenha o termo informado.
   * @param {string} searchTerm
   * @returns {Promise<Array>}
   */
  async function searchCollectionCategories(searchTerm) {
    const term = String(searchTerm || '').trim();
    if (!term) return [];

    const { data } = await axios.get('v1/collection-categories', {
      params: { q: term },
    });

    const payload = data?.data ?? data ?? {};
    return Array.isArray(payload) ? payload : payload.items ?? [];
  }

  async function getCollectionCategoryImages(categoryId) {
    const { data } = await axios.get(`v1/collection-categories/${categoryId}`);
    const payload = data?.data ?? data ?? {};
    const images = Array.isArray(payload.images) ? payload.images : [];

    return images;
  }

  /**
   * Detalhe de uma arte da coleção (categoria + imagem), para exibir na edição de orçamento/pedido.
   */
  async function getCollectionImage(imageId) {
    const { data } = await axios.get(`v1/collection-images/${imageId}`);
    return data?.data ?? data ?? null;
  }

  async function placeOrder(formData) {
    const response = await axios.post('v1/budgets/place-order', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    return response.data?.data || response.data;
  }

  return {
    getCollectionCategories,
    searchCollectionCategories,
    getCollectionCategoryImages,
    getCollectionImage,
    placeOrder,
  };
}

