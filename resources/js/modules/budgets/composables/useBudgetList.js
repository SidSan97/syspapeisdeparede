import { ref } from 'vue';
import { useBudgetListService } from '../services/budgetListService';
import { convertDateMaskToIso } from '@/utils/dateUtils';

/**
 * Composable para gerenciar a lista de orçamentos
 */
export function useBudgetList() {
    const budgetListService = useBudgetListService();

    const budgets = ref([]);
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
     * Normaliza um orçamento para formato padrão
     */
    function normalizeBudget(budget) {
        if (!budget) {
            return {
                id: null,
                name: '',
                total_amount: 0,
                total_amount_installments: 0,
                delivery_time: null,
                status: null,
                rooms: [],
                comment_referring_model: '',
                commentReferringModel: '',
                link_referring_model: '',
                linkReferringModel: '',
                files_referring_model: [],
                filesReferringModel: [],
                collection_referring_model: null,
                collectionReferringModel: null,
            };
        }

        const totalAmount = budget.total_amount ?? budget.totalAmount ?? 0;
        const totalAmountInstallments = budget.total_amount_installments ?? budget.totalAmountInstallments ?? 0;
        const deliveryTime = budget.delivery_time ?? budget.deliveryTime ?? null;
        const status = budget.status ?? budget.Status ?? null;
        const commentRef = budget.comment_referring_model ?? budget.commentReferringModel ?? '';
        const linkRef = budget.link_referring_model ?? budget.linkReferringModel ?? '';
        const filesRef = Array.isArray(budget.files_referring_model)
            ? budget.files_referring_model
            : Array.isArray(budget.filesReferringModel)
                ? budget.filesReferringModel
                : [];
        const collectionRef = budget.collection_referring_model ?? budget.collectionReferringModel ?? null;
        const rooms = Array.isArray(budget.rooms) ? budget.rooms : [];

        return {
            ...budget,
            name: budget.name ?? '',
            total_amount: totalAmount,
            total_amount_installments: totalAmountInstallments,
            delivery_time: deliveryTime,
            status,
            rooms,
            comment_referring_model: commentRef,
            commentReferringModel: commentRef,
            link_referring_model: linkRef,
            linkReferringModel: linkRef,
            files_referring_model: filesRef,
            filesReferringModel: filesRef,
            collection_referring_model: collectionRef,
            collectionReferringModel: collectionRef,
        };
    }

    /**
     * Busca orçamentos com filtros
     */
    async function fetchBudgets(filters = {}, page = 1) {
        try {
            loading.value = true;

            const params = {
                page,
                per_page: 15,
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

            const result = await budgetListService.getBudgets(params);
            budgets.value = result.items.map(normalizeBudget);
            paginationData.value = result.pagination;
        } catch (error) {
            console.error('Erro ao carregar orçamentos:', error);
            budgets.value = [];
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
        budgets,
        loading,
        paginationData,
        fetchBudgets,
        normalizeBudget,
    };
}

