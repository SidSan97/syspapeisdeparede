import { http } from '@/lib/http';
import { createCrudService } from '@/services/baseCrudService';

const endpoint = '/v1/collection-categories';

const crud = createCrudService(endpoint, {
  transformParams(params) {
    return {
      page: params?.page,
      q: params?.q,
      tree: params?.tree || true,
    };
  },
});

export const collectionService = {
  ...crud,

  async create(formData) {
    const payload = new FormData();

    formData.forEach((value, key) => {
      payload.append(key, value);
    });

    const { data } = await http.post(endpoint, payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    return data;
  },

  async update(id, formData) {
    const payload = new FormData();

    formData.forEach((value, key) => {
      payload.append(key, value);
    });

    payload.append('_method', 'PUT');

    const { data } = await http.post(`${endpoint}/${id}`, payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    return data;
  },

  async getCollectionChildren(categoryId) {
    const { data } = await http.get(`v1/collection-categories/children/${categoryId}`);
    return data;
  },

  async uploadImages(formData) {
    const { data } = await http.post('v1/collection-images', formData);
    return data;
  },

  async deleteImage(id) {
    await http.delete(`v1/collection-images/${id}`);
  },
};
