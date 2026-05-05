import { computed, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from '@/composables/useToast';
import { budgetService } from '@/services/budgetService';
import { useBudgetStore } from '@/stores/budgetStore';

export function useBudgetsList() {
  const router = useRouter();
  const toast = useToast();
  const budgetStore = useBudgetStore();

  const filters = reactive({
    page: 1,
    search: '',
    status: '',
    user_id: null,
    date_from: null,
    date_to: null,
  });

  const budgetList = computed(() => budgetStore.budgets?.data || []);
  const loading = computed(() => budgetStore.loadingBudgets);

  function fetchBudgets(params) {
    if (params) Object.assign(filters, params);

    budgetStore.loadBudgets(filters);
  }

  function goToPage(page) {
    filters.page = page;

    fetchBudgets();
  }

  const duplicateBudget = async (budget) => {
    if (!budget?.id) return;

    try {
      const created = await budgetService.duplicate(budget.id);

      toast.success('Orçamento duplicado com sucesso.');

      router.push({ name: 'budgets.edit', params: { id: created.id } });
    } catch (error) {
      console.error(error);
      toast.error('Erro ao duplicar o orçamento. Tente novamente.');
    }
  };

  const cancelBudget = async (budget) => {
    if (!budget?.id) return;

    try {
      await budgetService.cancel(budget.id);

      toast.success('Orçamento cancelado com sucesso.');

      fetchBudgets();
    } catch (error) {
      console.error(error);
      toast.error('Erro ao cancelar o orçamento. Tente novamente.');
    }
  };

  const deleteBudget = async (budget) => {
    if (!budget?.id) return;

    try {
      await budgetService.delete(budget.id);

      toast.success('Orçamento excluído com sucesso.');

      fetchBudgets();
    } catch (error) {
      console.error(error);
      toast.error('Opa! Erro ao excluir o orçamento. Tente novamente.');
    }
  };

  const createOrder = async (budget) => {
    if (!budget?.id) return;

    try {
      const budgetUpdated = await budgetService.createOrder(budget.id);

      toast.success('Pedido criado com sucesso.');

      router.push({
        name: 'orders.show',
        params: { id: budgetUpdated.order_id },
      });
    } catch (error) {
      console.error(error);
      toast.error('Opa! Erro ao criar o pedido. Tente novamente.');
    }
  };

  return {
    filters,
    loading,
    budgetList,
    fetchBudgets,
    goToPage,
    duplicateBudget,
    cancelBudget,
    deleteBudget,
    createOrder,
  };
}
