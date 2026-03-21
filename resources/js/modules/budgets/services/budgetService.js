import axios from 'axios';
import {
    createDefaultWall,
    extractItemsFromResponse,
    normalizeCollectionModel,
    normalizeBudgetFromAPI
} from '../composables/useBudgetUtils';

/**
 * Service para gerenciar chamadas de API relacionadas a orçamentos (criação, edição, consulta)
 */

export function useBudgetService() {
    /**
     * Busca um orçamento por ID
     */
    async function getBudget(budgetId) {
        const { data } = await axios.get(`v1/budgets/${budgetId}`);
        return data?.data || data;
    }

    /**
     * Cria um novo orçamento
     */
    async function createBudget(payload) {
        const { data } = await axios.post('v1/budgets', payload);
        return data;
    }

    /**
     * Atualiza um orçamento
     */
    async function updateBudget(budgetId, payload) {
        const { data } = await axios.put(`v1/budgets/${budgetId}`, payload);
        return data;
    }

    /**
     * Busca modelos de coleção
     */
    async function getCollectionModels() {
        const { data } = await axios.get('v1/collection-models');
        const payload = data?.data;
        const { items } = extractItemsFromResponse(payload);
        return items.map(normalizeCollectionModel);
    }

    /**
     * Busca produtos do Tiny ERP
     */
    async function getTinyErpProducts() {
        const { data } = await axios.get('v1/tiny-erp/all');
        return data;
    }

    /**
     * Faz upload de uma imagem de referência do modelo (uma por vez)
     */
    async function uploadReferringFile(file) {
        const formData = new FormData();
        formData.append('file', file);

        const { data } = await axios.post('v1/budgets/upload-referring-file', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        return data?.path ?? data;
    }

    /**
     * Calcula frete usando Frenet
     */
    async function calculateFreight(cep, productData) {
        const { data } = await axios.post('v1/frenet/calculate-shipping', {
            cep,
            productData
        });

        // Mapear os dados da resposta para o formato esperado
        if (data?.data?.ShippingSevicesArray && Array.isArray(data.data.ShippingSevicesArray)) {
            return data.data.ShippingSevicesArray
                .filter(service => !service.Error) // Filtrar apenas serviços sem erro
                .map(service => ({
                    name: `${service.Carrier} - ${service.ServiceDescription}`,
                    price: parseFloat(service.ShippingPrice) || 0,
                    deliveryTime: parseInt(service.DeliveryTime) || 0
                }));
        }

        return [];
    }

    return {
        getBudget,
        createBudget,
        updateBudget,
        getCollectionModels,
        getTinyErpProducts,
        calculateFreight,
        uploadReferringFile,
    };
}
