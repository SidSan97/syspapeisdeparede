import { http } from '@/lib/http';

export const PAGE_TYPE = Object.freeze({
  LAYOUT: 'layout',
  PRODUCT: 'product',
});

const endpoint = '/v1/budgets/order-budgets';

function buildMembersUrl(orderBudgetId, userId = null) {
  let url = `${endpoint}/${orderBudgetId}/members`;

  if (userId) {
    url += `/${userId}`;
  }

  return url;
}

function validateIds(orderBudgetId, userId) {
  if (!orderBudgetId) {
    throw new Error('orderBudgetId is required');
  }

  if (!userId) {
    throw new Error('userId is required');
  }
}

async function unwrap(promise) {
  const { data } = await promise;
  return data;
}

export const memberService = {
  addMember(orderBudgetId, userId, typePage = PAGE_TYPE.LAYOUT) {
    validateIds(orderBudgetId, userId);

    return unwrap(
      http.post(buildMembersUrl(orderBudgetId), {
        user_id: userId,
        type_page: typePage,
      }),
    );
  },

  removeMember(orderBudgetId, userId, typePage = PAGE_TYPE.LAYOUT) {
    validateIds(orderBudgetId, userId);

    return unwrap(
      http.delete(buildMembersUrl(orderBudgetId, userId), {
        data: {
          type_page: typePage,
        },
      }),
    );
  },
};
