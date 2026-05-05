import { http } from '@/lib/http';

const endpoint = '/v1/budgets';

function unwrap(promise) {
  return promise.then((res) => res.data);
}

function buildUploadUrl() {
  return `${endpoint}/order-budgets/upload-art`;
}

function buildRequestLayoutArtsUrl() {
  return `${endpoint}/request-layout-arts`;
}

function buildRequestParams({ orderId, budgetId } = {}) {
  const params = {};

  if (orderId && budgetId) {
    throw new Error('Use only orderId OR budgetId, not both');
  }

  if (orderId) {
    params.order_id = orderId;
  }

  if (budgetId) {
    params.budget_id = budgetId;
  }

  return params;
}

export const artService = {
  /**
   * Upload de arte
   */
  upload(formData) {
    if (!(formData instanceof FormData)) {
      throw new Error('formData must be an instance of FormData');
    }

    return unwrap(
      http.post(buildUploadUrl(), formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }),
    );
  },

  /**
   * Buscar artes de layout
   */
  listRequestLayoutArts({ orderId, budgetId } = {}) {
    return unwrap(
      http.get(buildRequestLayoutArtsUrl(), {
        params: buildRequestParams({ orderId, budgetId }),
      }),
    );
  },
};
