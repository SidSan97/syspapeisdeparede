import { http } from '@/lib/http';

const endpoint = '/v1/budgets/order-budgets';

function buildCommentsUrl(orderBudgetId, commentId = null) {
  let url = `${endpoint}/${orderBudgetId}/comments`;

  if (commentId) {
    url += `/${commentId}`;
  }

  return url;
}

function sanitizeComment(comment) {
  if (!comment || !comment.trim()) {
    throw new Error('Comment cannot be empty');
  }

  return comment.trim();
}

async function unwrap(promise) {
  const { data } = await promise;
  return data;
}

export const commentService = {
  async create(orderBudgetId, comment) {
    return unwrap(
      http.post(buildCommentsUrl(orderBudgetId), {
        comment: sanitizeComment(comment),
      }),
    );
  },

  async update(orderBudgetId, commentId, comment) {
    return unwrap(
      http.put(buildCommentsUrl(orderBudgetId, commentId), {
        comment: sanitizeComment(comment),
      }),
    );
  },

  async delete(orderBudgetId, commentId) {
    return unwrap(http.delete(buildCommentsUrl(orderBudgetId, commentId)));
  },

  async list(orderBudgetId) {
    return unwrap(http.get(buildCommentsUrl(orderBudgetId)));
  },
};
