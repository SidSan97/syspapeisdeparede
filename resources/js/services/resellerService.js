import { http } from '@/lib/http';

export const resellerService = {
  async search(search, currentId) {
    const { data } = await http.get('/v1/resellers/list', {
      params: {
        search: search || undefined,
        current_id: currentId || undefined,
      },
    });

    return data?.data ?? [];
  },

  async sync() {
    const { data } = await http.post('/v1/resellers/sync');

    return data;
  },
};
