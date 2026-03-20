/**
 * Composable com funções auxiliares para manipulação de orçamentos
 */

/**
 * Cria uma parede padrão vazia
 */
export function createDefaultWall() {
    return {
        id: null,
        name: '',
        width: null,
        height: null,
        model: null,
        continueSameArt: false,
        continuations: [],
        comment_referring_model: '',
        link_referring_model: '',
        files_referring_model: [],
        collection_referring_model: ''
    };
}

/**
 * Extrai items e meta de uma resposta paginada
 */
export function extractItemsFromResponse(payload) {
    if (!payload) {
        return { items: [], meta: {} };
    }

    if (Array.isArray(payload)) {
        return { items: payload, meta: {} };
    }

    const resourceItems = payload.items?.data ?? payload.items ?? [];
    const meta =
        payload.meta ??
        payload.items?.meta ?? {
            current_page: payload.items?.current_page ?? 1,
            per_page: payload.items?.per_page ?? resourceItems.length,
            total: payload.items?.total ?? resourceItems.length,
            last_page: payload.items?.last_page ?? 1
        };

    return {
        items: resourceItems,
        meta
    };
}

/**
 * Normaliza um modelo de coleção para o formato esperado
 */
export function normalizeCollectionModel(model = {}) {
    const files = Array.isArray(model.files)
        ? model.files.map((file) => ({
              id: file.id ?? null,
              name: file.name ?? file.fileName ?? file.file_name ?? 'Arquivo',
              url: file.url ?? file.fileUrl ?? null
          }))
        : [];

    const name = (model.name ?? '').toString().trim();
    const typeName =
        model.type?.name ??
        model.type_name ??
        model.typeModelName ??
        null;

    const displayName = name.length > 0
        ? name
        : model.comment?.trim()
            ? model.comment.trim()
            : `Modelo ${model.id}`;

    return {
        id: Number(model.id),
        displayName,
        typeName,
        value: Number(model.value ?? 0),
        deadline: Number(model.deadline ?? 0),
        requests: {
            link: Boolean(model?.requests?.link),
            comment: Boolean(model?.requests?.comment),
            file: Boolean(model?.requests?.file),
            collection: Boolean(
                model?.requests?.collection ??
                model?.request_collection ??
                model?.requestCollection
            )
        },
        link: model.link ?? '',
        comment: model.comment ?? '',
        files
    };
}

/**
 * Normaliza dados de orçamento vindos da API para o formato usado no frontend
 */
export function normalizeBudgetFromAPI(budgetData) {
    const rooms = [];

    if (budgetData.rooms && Array.isArray(budgetData.rooms)) {
        budgetData.rooms.forEach((room) => {
            const walls = [];

            if (room.walls && Array.isArray(room.walls)) {
                room.walls.forEach((wall) => {
                    const wallData = {
                        id: wall.id ?? null,
                        name: wall.name || '',
                        width: wall.width ? Number(wall.width) : null,
                        height: wall.height ? Number(wall.height) : null,
                        model: (wall.collection_model_id || wall.collection_model?.id) ? Number(wall.collection_model_id || wall.collection_model?.id) : null,
                        continueSameArt: false,
                        continuations: [],
                        comment_referring_model: wall.comment_referring_model ?? '',
                        link_referring_model: wall.link_referring_model ?? '',
                        files_referring_model: Array.isArray(wall.files_referring_model) ? wall.files_referring_model : [],
                        collection_referring_model: wall.collection_referring_model ?? ''
                    };

                    // Processar continuações se existirem
                    if (wall.continue_same_art || (wall.continuations && Array.isArray(wall.continuations) && wall.continuations.length > 0)) {
                        wallData.continueSameArt = Boolean(wall.continue_same_art);
                        if (wall.continuations && Array.isArray(wall.continuations)) {
                            wallData.continuations = wall.continuations.map(cont => ({
                                name: cont.name || '',
                                direction: cont.direction || '',
                                width: cont.width ? Number(cont.width) : null,
                                height: cont.height ? Number(cont.height) : null,
                                sameArt: Boolean(cont.sameArt ?? false)
                            }));
                        }
                    }

                    walls.push(wallData);
                });
            }

            rooms.push({
                name: room.name || '',
                walls: walls.length > 0 ? walls : [createDefaultWall()]
            });
        });
    }

    // Encontrar transportadora selecionada e recriar lista se necessário
    let carriers = Array.isArray(budgetData.carriers_snapshot)
        ? budgetData.carriers_snapshot
        : [];

    let selectedCarrierIndex = null;

    // Se há frete selecionado mas não há lista de transportadoras, recriar a lista
    if (budgetData.selected_carrier_name && budgetData.selected_carrier_price !== null && budgetData.selected_carrier_price !== undefined) {
        if (carriers.length === 0) {
            // Recriar lista de transportadoras com base no frete selecionado
            carriers = [
                {
                    name: budgetData.selected_carrier_name,
                    price: Number(budgetData.selected_carrier_price) || 0,
                    deliveryTime: Number(budgetData.selected_carrier_delivery_time) || 0
                }
            ];
            selectedCarrierIndex = 0;
        } else {
            // Buscar o índice da transportadora selecionada
            selectedCarrierIndex = carriers.findIndex(c => c.name === budgetData.selected_carrier_name);
            if (selectedCarrierIndex < 0) {
                // Se não encontrou, adicionar a transportadora selecionada à lista
                carriers.push({
                    name: budgetData.selected_carrier_name,
                    price: Number(budgetData.selected_carrier_price) || 0,
                    deliveryTime: Number(budgetData.selected_carrier_delivery_time) || 0
                });
                selectedCarrierIndex = carriers.length - 1;
            }
        }
    }

    const normalizedPaymentMethod = budgetData.payment_method === 'installment'
        ? 'credit_card'
        : budgetData.payment_method || '';

    const normalizedStatus = (budgetData.status === null || budgetData.status === undefined || budgetData.status === '')
        ? null
        : budgetData.status;

    return {
        id: budgetData.id,
        name: budgetData.name || '',
        status: normalizedStatus,
        rooms: rooms.length > 0 ? rooms : [{ name: '', walls: [createDefaultWall()] }],
        cep: budgetData.cep || '',
        carriers: carriers,
        selectedCarrier: selectedCarrierIndex,
        paymentMethod: normalizedPaymentMethod,
        installmentLimit: budgetData.installment_limit || 12,
        installments: budgetData.installments || 1,
        total_amount: budgetData.total_amount ? Number(budgetData.total_amount) : 0,
        total_amount_installments: budgetData.total_amount_installments ? Number(budgetData.total_amount_installments) : 0,
        dropshipping_budget: budgetData.dropshipping_budget || 0,
        dropshipping_data: budgetData.dropshipping_data || null,
        reseller_name: budgetData.reseller_name ?? null
    };
}

/**
 * Composable principal que exporta todas as funções auxiliares
 */
export function useBudgetUtils() {
    return {
        createDefaultWall,
        extractItemsFromResponse,
        normalizeCollectionModel,
        normalizeBudgetFromAPI
    };
}
