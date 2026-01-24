import axios from 'axios';

/**
 * Service para gerenciar chamadas de API relacionadas à lista de pedidos internos
 */
export function useInternalOrderListService() {
    async function getOrders(params = {}) {
        const { data } = await axios.get('v1/orders', { params });

        if (data?.success && data?.data) {
            return {
                items: Array.isArray(data.data.data) ? data.data.data : [],
                pagination: {
                    current_page: data.data.current_page || 1,
                    last_page: data.data.last_page || 1,
                    per_page: data.data.per_page || 15,
                    total: data.data.total || 0,
                    from: data.data.from || 0,
                    to: data.data.to || 0,
                },
            };
        }

        return {
            items: [],
            pagination: {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
                from: 0,
                to: 0,
            },
        };
    }

    async function getUsers() {
        try {
            const response = await axios.get('v1/users/list-resellers');

            if (response.data?.success && response.data?.data) {
                // Se a resposta estiver paginada, pegar o array de dados
                if (response.data.data.data && Array.isArray(response.data.data.data)) {
                    return response.data.data.data;
                } else if (Array.isArray(response.data.data)) {
                    return response.data.data;
                }
            }
            return [];
        } catch (error) {
            console.error('Erro ao buscar usuários:', error);
            return [];
        }
    }

    return {
        getOrders,
        getUsers,
    };
}
