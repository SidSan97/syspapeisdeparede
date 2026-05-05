import { http } from '@/lib/http';

function unwrap(promise) {
  return promise.then((res) => res.data);
}

function validateId(id) {
  if (!id || id <= 0) {
    throw new Error('Invalid ID');
  }
}

export function createCrudService(endpoint, options = {}) {
  const { transformParams, transformResponse } = options;

  return {
    async all(params = {}) {
      const finalParams = transformParams ? transformParams(params) : params;

      const data = await unwrap(
        http.get(endpoint, {
          params: finalParams,
        }),
      );

      return transformResponse ? transformResponse(data) : data;
    },

    async find(id) {
      validateId(id);

      const data = await unwrap(http.get(`${endpoint}/${id}`));

      return data?.data ?? data;
    },

    async create(payload) {
      const data = await unwrap(http.post(endpoint, payload));

      return data?.data ?? data;
    },

    async update(id, payload) {
      validateId(id);

      const data = await unwrap(http.put(`${endpoint}/${id}`, payload));

      return data?.data ?? data;
    },

    async delete(id) {
      validateId(id);

      await http.delete(`${endpoint}/${id}`);
    },
  };
}
