import axios from 'axios';

/**
 * Service para gerenciar chamadas de API relacionadas a comentários
 */
export function useCommentService() {
    /**
     * Criar um novo comentário
     * @param {number} orderBudgetId - ID do orçamento
     * @param {string} comment - Texto do comentário
     * @returns {Promise}
     */
    async function createComment(orderBudgetId, comment) {
        const response = await axios.post(`v1/budgets/order-budgets/${orderBudgetId}/comments`, {
            comment: comment.trim(),
        });
        return response.data;
    }

    /**
     * Atualizar um comentário existente
     * @param {number} orderBudgetId - ID do orçamento
     * @param {number} commentId - ID do comentário
     * @param {string} comment - Novo texto do comentário
     * @returns {Promise}
     */
    async function updateComment(orderBudgetId, commentId, comment) {
        const { data } = await axios.put(`v1/budgets/order-budgets/${orderBudgetId}/comments/${commentId}`, {
            comment: comment.trim(),
        });
        return data;
    }

    /**
     * Excluir um comentário
     * @param {number} orderBudgetId - ID do orçamento
     * @param {number} commentId - ID do comentário
     * @returns {Promise}
     */
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

