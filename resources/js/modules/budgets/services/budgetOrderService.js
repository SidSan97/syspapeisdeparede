import { http } from '@/lib/http';

export const budgetOrderService = {
  async getCollectionCategories() {
    const { data } = await http.get('v1/collection-categories', {
      params: { tree: true },
    });

    const payload = data?.data ?? data ?? {};
    const items = Array.isArray(payload) ? payload : (payload.items ?? []);

    const rootCategories = items.filter((item) => !item.parent_id);

    return rootCategories;
  },

  /**
   * Busca categorias cujo nome contenha o termo informado.
   * @param {string} searchTerm
   * @returns {Promise<Array>}
   *
   * @deprecated migrar para `collectionService.all()`
   */
  async searchCollectionCategories(searchTerm) {
    const term = String(searchTerm || '').trim();
    if (!term) return [];

    const { data } = await http.get('v1/collection-categories', {
      params: { q: term },
    });

    const payload = data?.data ?? data ?? {};
    return Array.isArray(payload) ? payload : (payload.items ?? []);
  },
  async getCollectionCategoryImages(categoryId) {
    const { data } = await http.get(`v1/collection-categories/${categoryId}`);
    const payload = data?.data ?? data ?? {};
    const images = Array.isArray(payload.images) ? payload.images : [];

    return images;
  },

  /**
   * Imagens de uma categoria com paginação (para catálogo em orçamento / pedido).
   * @param {number|string} categoryId
   * @param {{ page?: number, perPage?: number }} options
   */
  async getCollectionCategoryImagesPage(categoryId, { page = 1, perPage = 6 } = {}) {
    const { data } = await http.get(`v1/collection-categories/${categoryId}/images`, {
      params: { page, per_page: perPage },
    });
    const payload = data?.data ?? data ?? {};

    return {
      items: Array.isArray(payload.items) ? payload.items : [],
      meta: {
        current_page: payload.meta?.current_page ?? page,
        per_page: payload.meta?.per_page ?? perPage,
        total: payload.meta?.total ?? 0,
        last_page: payload.meta?.last_page ?? 1,
      },
    };
  },

  /**
   * Detalhe de uma arte da coleção (categoria + imagem), para exibir na edição de orçamento/pedido.
   */
  async getCollectionImage(imageId) {
    const { data } = await http.get(`v1/collection-images/${imageId}`);
    return data?.data ?? data ?? null;
  },
};

export function useBudgetOrderService() {
  return budgetOrderService;
}
