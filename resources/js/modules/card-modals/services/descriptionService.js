import { http } from '@/lib/http';

export const PAGE_TYPE = Object.freeze({
  LAYOUT: 'layout',
  PRODUCT: 'product',
});

const endpoint = '/v1/budgets/order-budgets';

function buildDescriptionUrl(orderBudgetId) {
  return `${endpoint}/${orderBudgetId}/description`;
}

function sanitizeDescription(description) {
  if (!description) return '';

  return description.trim();
}

async function unwrap(promise) {
  const { data } = await promise;
  return data;
}

export const descriptionService = {
  updateDescription(orderBudgetId, description, typePage = PAGE_TYPE.LAYOUT) {
    return unwrap(
      http.put(buildDescriptionUrl(orderBudgetId), {
        description: sanitizeDescription(description),
        type_page: typePage,
      }),
    );
  },
};
