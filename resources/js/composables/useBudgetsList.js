import { computed, reactive } from 'vue';
import { useBudgetStore } from '@/stores/budgetStore';

export function useBudgetsList() {
  const budgetStore = useBudgetStore();

  const filters = reactive({
    page: 1,
    search: '',
    status: '',
    user_id: null,
    date_from: null,
    date_to: null,
  });

  const budgets = computed(() => budgetStore.budgets);
  const loading = computed(() => budgetStore.loadingBudgets);

  function fetchBudgets(params) {
    if (params) Object.assign(filters, params);

    budgetStore.loadBudgets(filters);
  }

  function goToPage(page) {
    filters.page = page;

    fetchBudgets();
  }

  return {
    budgets,
    filters,
    loading,
    fetchBudgets,
    goToPage,
  };
}
