import { createCrudService } from './baseCrudService';

const endpoint = '/v1/users';

export const userService = createCrudService(endpoint, {
  transformParams(params) {
    return {
      page: params?.page,
      search: params?.search,
      role: params?.role,
      ...params?.filters,
    };
  },
});
