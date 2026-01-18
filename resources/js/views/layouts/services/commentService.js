import axios from 'axios';

/**
 * Service para gerenciar chamadas de API relacionadas a comentários
 */
export function useCommentService() {
    async function createComment(orderBudgetId, comment) {
        const response = await axios.post(`v1/budgets/order-budgets/${orderBudgetId}/comments`, {
            comment: comment.trim(),
        });
        return response.data;
    }

    async function updateComment(orderBudgetId, commentId, comment) {
        const { data } = await axios.put(`v1/budgets/order-budgets/${orderBudgetId}/comments/${commentId}`, {
            comment: comment.trim(),
        });
        return data;
    }

    async function deleteComment(orderBudgetId, commentId) {
        const { data } = await axios.delete(`v1/budgets/order-budgets/${orderBudgetId}/comments/${commentId}`);
        return data;
    }

    return {
        createComment,
        updateComment,
        deleteComment,
    };
}

