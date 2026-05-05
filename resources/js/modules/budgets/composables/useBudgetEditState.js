import { ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { normalizeBudgetFromAPI } from './useBudgetUtils';
import { budgetService } from '@/services/budgetService';

/**
 * Função auxiliar para normalizar valores para comparação
 */
function normalizeForComparison(value) {
  if (value === null || value === undefined) return null;
  if (typeof value === 'string') return value.trim();
  if (typeof value === 'number') return value;
  if (Array.isArray(value)) {
    return value.map((item) => normalizeForComparison(item));
  }
  if (typeof value === 'object') {
    const normalized = {};
    for (const key in value) {
      normalized[key] = normalizeForComparison(value[key]);
    }
    return normalized;
  }
  return value;
}

/**
 * Composable para gerenciar o estado de edição de orçamento
 */
export function useBudgetEditState(budget) {
  const router = useRouter();
  const route = useRoute();

  const budgetId = ref(null);
  const loadingBudget = ref(false);
  const originalBudget = ref(null);

  /**
   * Detecta se há mudanças no orçamento
   */
  const hasChanges = computed(() => {
    if (!originalBudget.value) return false;

    // Criar objetos com a mesma estrutura para comparação
    const original = {
      name: originalBudget.value.name || '',
      status:
        originalBudget.value.status === null ||
        originalBudget.value.status === undefined ||
        originalBudget.value.status === ''
          ? null
          : originalBudget.value.status,
      rooms: originalBudget.value.rooms || [],
      cep: originalBudget.value.cep || '',
      selectedCarrier: originalBudget.value.selectedCarrier,
      paymentMethod: originalBudget.value.paymentMethod || '',
      installments: originalBudget.value.installments || 1,
    };

    const current = {
      name: budget.name || '',
      status:
        budget.status === null || budget.status === undefined || budget.status === ''
          ? null
          : budget.status,
      rooms: budget.rooms || [],
      cep: budget.cep || '',
      selectedCarrier: budget.selectedCarrier,
      paymentMethod: budget.paymentMethod || '',
      installments: budget.installments || 1,
    };

    // Normalizar antes de comparar
    const normalizedOriginal = normalizeForComparison(original);
    const normalizedCurrent = normalizeForComparison(current);

    // Comparar usando JSON.stringify
    return JSON.stringify(normalizedOriginal) !== JSON.stringify(normalizedCurrent);
  });

  /**
   * Carrega um orçamento por ID
   */
  async function loadBudget() {
    const id = route.params.id;
    if (!id) {
      window.Swal.fire({
        title: 'Erro!',
        text: 'ID do orçamento não encontrado',
        icon: 'error',
        confirmButtonText: 'Entendi!',
      });
      router.push({ name: 'budgets.list' });
      return;
    }

    budgetId.value = Number(id);
    loadingBudget.value = true;

    try {
      const budgetData = await budgetService.find(budgetId.value);

      if (!budgetData) {
        window.Swal.fire({
          title: 'Erro!',
          text: 'Orçamento não encontrado',
          icon: 'error',
          confirmButtonText: 'Entendi!',
        });
        router.push({ name: 'budgets.list' });
        return;
      }

      // Normalizar e carregar dados
      const normalized = normalizeBudgetFromAPI(budgetData);
      Object.assign(budget, normalized);

      // Salvar cópia para comparação
      originalBudget.value = JSON.parse(
        JSON.stringify({
          name: budget.name || '',
          status:
            budget.status === null || budget.status === undefined || budget.status === ''
              ? null
              : budget.status,
          rooms: budget.rooms || [],
          cep: budget.cep || '',
          selectedCarrier: budget.selectedCarrier,
          paymentMethod: budget.paymentMethod || '',
          installments: budget.installments || 1,
        }),
      );

      return normalized;
    } catch (error) {
      console.error('Erro ao carregar orçamento:', error);
      window.Swal.fire({
        title: 'Erro!',
        text: 'Não foi possível carregar o orçamento',
        icon: 'error',
        confirmButtonText: 'Entendi!',
      });
      router.push({ name: 'budgets.list' });
      throw error;
    } finally {
      loadingBudget.value = false;
    }
  }

  /**
   * Atualiza o originalBudget após salvar
   */
  function updateOriginalBudget() {
    originalBudget.value = JSON.parse(
      JSON.stringify({
        name: budget.name || '',
        status:
          budget.status === null || budget.status === undefined || budget.status === ''
            ? null
            : budget.status,
        rooms: budget.rooms || [],
        cep: budget.cep || '',
        selectedCarrier: budget.selectedCarrier,
        paymentMethod: budget.paymentMethod || '',
        installments: budget.installments || 1,
      }),
    );
  }

  return {
    budgetId,
    loadingBudget,
    originalBudget,
    hasChanges,
    loadBudget,
    updateOriginalBudget,
  };
}
