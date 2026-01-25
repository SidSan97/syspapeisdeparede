import axios from 'axios';

/**
 * Service para gerenciar chamadas de API relacionadas a modelos
 */
export function useModelService() {
    /**
     * Busca lista de modelos paginada
     * @param {number} page - Número da página
     * @returns {Promise<Object>} Resposta da API com modelos e metadados
     */
    async function getModels(page = 1) {
        const { data } = await axios.get('v1/collection-models', {
            params: { page },
        });
        return data;
    }

    /**
     * Cria um novo modelo
     * @param {FormData} formData - Dados do formulário
     * @returns {Promise<Object>} Modelo criado
     */
    async function createModel(formData) {
        const { data } = await axios.post('v1/collection-models', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        return data;
    }

    /**
     * Atualiza um modelo existente
     * @param {number|string} modelId - ID do modelo
     * @param {FormData} formData - Dados do formulário
     * @returns {Promise<Object>} Modelo atualizado
     */
    async function updateModel(modelId, formData) {
        formData.append('_method', 'PUT');
        const { data } = await axios.post(`v1/collection-models/${modelId}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        return data;
    }

    /**
     * Exclui um modelo
     * @param {number|string} modelId - ID do modelo
     * @returns {Promise<void>}
     */
    async function deleteModel(modelId) {
        await axios.delete(`v1/collection-models/${modelId}`);
    }

    return {
        getModels,
        createModel,
        updateModel,
        deleteModel,
    };
}

