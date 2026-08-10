import { useRouter } from 'vue-router';
import { useDialog } from './useDialog';
import { useToast } from '@/composables/useToast';
import { budgetService } from '@/services/budgetService';

export function useBudgetActions() {
  const router = useRouter();
  const dialog = useDialog();
  const toast = useToast();

  const confirmDuplicateBudget = () => {
    return dialog.confirm({
      title: 'Duplicar orçamento?',
      text: 'Tem certeza que deseja duplicar este orçamento?',
      icon: 'question',
      confirmText: 'Duplicar',
      cancelText: 'Cancelar',
    });
  };

  const duplicateBudget = async (budget) => {
    if (!budget?.id) return;

    if (!(await confirmDuplicateBudget())) return;

    try {
      const created = await budgetService.duplicate(budget.id);

      toast.success('Orçamento duplicado com sucesso.');

      router.push({ name: 'budgets.edit', params: { id: created.id } });
    } catch (error) {
      console.error(error);
      toast.error('Erro ao duplicar o orçamento. Tente novamente.');
    }
  };

  const confirmCancelBudget = async () => {
    return dialog.confirm({
      title: 'Cancelar orçamento?',
      text: 'Tem certeza que deseja cancelar o orçamento?',
      confirmText: 'Cancelar orçamento',
      cancelText: 'Não, manter',
    });
  };

  const cancelBudget = async (budget) => {
    if (!budget?.id) return;

    if (!(await confirmCancelBudget())) return;

    try {
      await budgetService.cancel(budget.id);

      toast.success('Orçamento cancelado com sucesso.');
    } catch (error) {
      console.error(error);
      toast.error('Erro ao cancelar o orçamento. Tente novamente.');
    }
  };

  const confirmDeleteBudget = async () => {
    return dialog.confirmDelete({
      title: 'Excluir orçamento?',
      text: 'Tem certeza que deseja excluir o orçamento? Esta ação não pode ser desfeita.',
    });
  };

  const deleteBudget = async (budget) => {
    if (!budget?.id) return;

    if (!(await confirmDeleteBudget())) return;

    try {
      await budgetService.delete(budget.id);

      toast.success('Orçamento excluído com sucesso.');
    } catch (error) {
      console.error(error);
      toast.error('Opa! Erro ao excluir o orçamento. Tente novamente.');
    }
  };

  const createOrder = async (budget, payload = {}) => {
    if (!budget?.id) return;

    try {
      const budgetUpdated = await budgetService.createOrder(budget.id, payload);

      toast.success('Pedido criado com sucesso.');

      router.push({
        name: 'orders.show',
        params: { id: budgetUpdated.order_id },
      });

      return budgetUpdated;
    } catch (error) {
      console.error(error);
      toast.error('Opa! Erro ao criar o pedido. Tente novamente.');
      throw error;
    }
  };

  return {
    duplicateBudget,
    cancelBudget,
    deleteBudget,
    createOrder,
  };
}
