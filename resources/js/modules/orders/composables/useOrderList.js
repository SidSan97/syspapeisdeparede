import { ref, computed } from 'vue';
import { useOrderListService } from '../services/orderListService';
import { convertDateMaskToIso } from '@/utils/dateUtils';

/**
 * Composable para gerenciar a lista de pedidos
 */
export function useOrderList() {
    const orderListService = useOrderListService();

    const orders = ref([]);
    const loading = ref(false);
    const paginationData = ref({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
        from: 0,
        to: 0,
    });

    /**
     * Normaliza um pedido para formato padrão
     */
    function normalizeOrder(order) {
        if (!order) {
            return null;
        }

        return {
            ...order,
            name: order.name ?? 'Não informado',
            total_amount: Number(order.total_amount ?? order.totalAmount ?? 0),
            delivery_time: order.delivery_time ?? order.deliveryTime ?? null,
            status: order.status ?? null,
        };
    }

    /**
     * Busca pedidos com filtros
     */
    async function fetchOrders(filters = {}, page = 1) {
        try {
            loading.value = true;

            const params = {
                page,
            };

            // Adicionar filtros
            if (filters.search?.trim()) {
                params.search = filters.search.trim();
            }

            if (filters.status && filters.status !== 'all') {
                params.status = filters.status;
            }

            if (filters.dateFrom && filters.dateFrom.length === 10) {
                const convertedDate = convertDateMaskToIso(filters.dateFrom);
                if (convertedDate) {
                    params.date_from = convertedDate;
                }
            }

            if (filters.dateTo && filters.dateTo.length === 10) {
                const convertedDate = convertDateMaskToIso(filters.dateTo);
                if (convertedDate) {
                    params.date_to = convertedDate;
                }
            }

            if (filters.userId !== null && filters.userId !== undefined) {
                params.user_id = filters.userId;
            }

            const result = await orderListService.getOrders(params);
            orders.value = result.items.map(normalizeOrder);
            paginationData.value = result.pagination;
        } catch (error) {
            console.error('Erro ao carregar pedidos:', error);
            orders.value = [];
            paginationData.value = {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
                from: 0,
                to: 0,
            };
        } finally {
            loading.value = false;
        }
    }

    return {
        orders,
        loading,
        paginationData,
        fetchOrders,
    };
}

