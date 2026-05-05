import { defineStore } from 'pinia';
import { ref } from 'vue';
import { budgetService } from '@/services/budgetService';

export const useBudgetStore = defineStore('budgets', () => {
  const budgets = ref({
    data: [],
    meta: {},
  });
  const currentBudget = ref(null);

  const loadingBudgets = ref(false);
  const loadingBudgetById = ref(false);
  const error = ref(null);

  async function loadBudgets(params = {}) {
    if (loadingBudgets.value) return; // Prevent concurrent calls

    loadingBudgets.value = true;
    error.value = null;

    try {
      const response = await budgetService.all(params);
      budgets.value = response;
    } catch (e) {
      error.value = 'Erro ao carregar orçamentos';
      throw e;
    } finally {
      loadingBudgets.value = false;
    }
  }

  async function loadBudgetById(id) {
    loadingBudgetById.value = true;
    error.value = null;

    try {
      currentBudget.value = await budgetService.find(id);
    } catch (e) {
      error.value = 'Erro ao carregar orçamento';
      throw e;
    } finally {
      loadingBudgetById.value = false;
    }
  }

  function clearCurrentBudget() {
    currentBudget.value = null;
  }

  return {
    budgets,
    currentBudget,

    loadingBudgets,
    loadingBudgetById,
    error,

    loadBudgets,
    loadBudgetById,
    clearCurrentBudget,
  };
});
