import { computed } from 'vue';

/**
 * Composable para lógica de geração de PDF de orçamentos
 * @param {import('vue').Ref} budget - Referência reativa do orçamento
 */
export function useBudgetPdfGenerate(budget) {
    /**
     * Calcula o preço de uma parede
     */
    function calculateWallPrice(wall) {
        // Usar o preço da parede se disponível, senão calcular baseado na área
        if (wall.price !== undefined && wall.price !== null) {
            return parseFloat(wall.price);
        }
        if (wall.total_area) {
            // Se não tiver preço direto, usar área * preço por m² (assumindo R$ 10/m² como padrão)
            return parseFloat(wall.total_area) * 10;
        }
        if (wall.width && wall.height) {
            const area = parseFloat(wall.width) * parseFloat(wall.height);
            return area * 10;
        }
        return 0;
    }

    /**
     * Formata os detalhes de uma parede
     */
    function formatWallDetails(wall) {
        const parts = [];
        if (wall.name) {
            parts.push(wall.name);
        }
        if (wall.width && wall.height) {
            parts.push(`${wall.width}m x ${wall.height}m`);
        }

        return parts.length > 0 ? parts.join(' | ') : 'Parede sem detalhes';
    }

    /**
     * Extrai o nome da transportadora (remove descrição do serviço)
     */
    function getCarrierName(carrierName) {
        if (!carrierName) return null;
        // Extrair apenas o nome antes do hífen (ex: "Jadlog - Package" -> "Jadlog")
        if (carrierName.includes(' - ')) {
            return carrierName.split(' - ')[0].trim();
        }
        return carrierName.trim();
    }

    /**
     * Total de ambientes
     */
    const totalRooms = computed(() => {
        if (!budget.value?.rooms) return 0;
        return budget.value.rooms.length;
    });

    /**
     * Total de paredes
     */
    const totalItems = computed(() => {
        if (!budget.value?.rooms) return 0;
        let count = 0;
        budget.value.rooms.forEach(room => {
            if (room.walls && Array.isArray(room.walls)) {
                count += room.walls.length;
            }
        });
        return count;
    });

    /**
     * Total de metros quadrados
     */
    const totalMeters = computed(() => {
        if (!budget.value?.rooms) return 0;
        let total = 0;
        budget.value.rooms.forEach(room => {
            if (room.walls && Array.isArray(room.walls)) {
                room.walls.forEach(wall => {
                    if (wall.total_area) {
                        total += parseFloat(wall.total_area);
                    } else if (wall.width && wall.height) {
                        total += parseFloat(wall.width) * parseFloat(wall.height);
                    }
                });
            }
        });
        return total;
    });

    /**
     * Total de produtos
     */
    const totalProducts = computed(() => {
        // Usar total_amount do budget se disponível, senão calcular
        if (budget.value?.total_amount !== undefined && budget.value?.total_amount !== null) {
            return parseFloat(budget.value.total_amount);
        }

        let total = 0;

        if (budget.value?.rooms) {
            budget.value.rooms.forEach(room => {
                if (room.walls && Array.isArray(room.walls)) {
                    room.walls.forEach(wall => {
                        total += calculateWallPrice(wall);
                    });
                }
            });
        }
        return total;
    });

    /**
     * Total do pedido (produtos + frete)
     */
    const totalOrder = computed(() => {
        const products = totalProducts.value;
        const shipping = parseFloat(budget.value?.selected_carrier_price || 0);
        return products + shipping;
    });

    /**
     * Todas as observações das paredes
     */
    const allObservations = computed(() => {
        if (!budget.value?.rooms) return [];
        const observations = [];

        budget.value.rooms.forEach(room => {
            if (room.walls && Array.isArray(room.walls)) {
                room.walls.forEach(wall => {
                    if (wall.comment_referring_model && wall.comment_referring_model.trim()) {
                        observations.push(wall.comment_referring_model);
                    }
                });
            }
        });
        return observations;
    });

    /**
     * Dados de dropshipping
     */
    const dropshippingData = computed(() => {
        return budget.value?.dropshipping_data || null;
    });

    return {
        calculateWallPrice,
        formatWallDetails,
        getCarrierName,
        totalRooms,
        totalItems,
        totalMeters,
        totalProducts,
        totalOrder,
        allObservations,
        dropshippingData
    };
}
