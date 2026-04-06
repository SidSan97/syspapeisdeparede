import axios from 'axios';

/**
 * Chamadas de API relacionadas a parede / referências de modelo (reutilizável em vários fluxos).
 */
export function useWallService() {
  /**
   * Registro em `collection_images` (nome, path, URL pública).
   * GET v1/collection-images/{id}
   *
   * @param {number|string} imageId
   * @returns {Promise<{ id?: number, name?: string|null, path_name?: string|null, url?: string|null } | null>}
   */
  async function getCollectionImageById(imageId) {
    const id =
      typeof imageId === 'number' && Number.isInteger(imageId)
        ? imageId
        : parseInt(String(imageId ?? '').trim(), 10);
    if (!Number.isFinite(id) || id <= 0) {
      return null;
    }
    const { data } = await axios.get(`v1/collection-images/${id}`);
    return data?.data ?? data ?? null;
  }

  return {
    getCollectionImageById,
  };
}
