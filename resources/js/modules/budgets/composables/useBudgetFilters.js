import { ref, computed, watch } from 'vue';
import debounce from 'lodash/debounce';

/**
 * Composable para gerenciar filtros de orçamentos
 */
export function useBudgetFilters(onFilterChange) {
    const searchQuery = ref('');
    const statusFilter = ref('all');
    const dateFrom = ref('');
    const dateTo = ref('');
    const selectedUserId = ref(null);

    const statusOptions = [
        { label: 'Em aberto', value: 'em aberto' },
        { label: 'Aprovado', value: 'aprovado' },
        { label: 'Cancelado', value: 'cancelado' },
    ];

    const currentStatusLabel = computed(() => {
        if (statusFilter.value === 'all') {
            return 'Situação';
        }

        const match = statusOptions.find((option) => option.value === statusFilter.value);
        return match ? match.label : 'Situação';
    });

    const filters = computed(() => ({
        search: searchQuery.value,
        status: statusFilter.value,
        dateFrom: dateFrom.value,
        dateTo: dateTo.value,
        userId: selectedUserId.value,
    }));

    function setStatusFilter(value) {
        statusFilter.value = value;
        onFilterChange();
    }

    function clearFilters() {
        dateFrom.value = '';
        dateTo.value = '';
        selectedUserId.value = null;
        onFilterChange();
    }

    // Debounced watchers para busca e datas
    const debouncedFilterChange = debounce(() => {
        onFilterChange();
    }, 500);

    const debouncedDateChange = debounce(() => {
        onFilterChange();
    }, 1500);

    watch(searchQuery, () => {
        debouncedFilterChange();
    });

    watch(dateFrom, () => {
        debouncedDateChange();
    });

    watch(dateTo, () => {
        debouncedDateChange();
    });

    watch(selectedUserId, () => {
        onFilterChange();
    });

    return {
        searchQuery,
        statusFilter,
        dateFrom,
        dateTo,
        selectedUserId,
        statusOptions,
        currentStatusLabel,
        filters,
        setStatusFilter,
        clearFilters,
    };
}

