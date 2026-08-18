import { ref } from 'vue';
import { useDialog } from '@/composables/useDialog';
import { useToast } from '@/composables/useToast';
import { productionService } from '@/services/productionService';

export function applyProductionPercentageUpdate(card, updated) {
  if (!card || !updated) {
    return;
  }

  card.production_percentage = updated.production_percentage;

  if (Object.prototype.hasOwnProperty.call(updated, 'production_date')) {
    card.production_date = updated.production_date;
  }

  if (Object.prototype.hasOwnProperty.call(updated, 'production_column_names_id')) {
    card.production_column_names_id = updated.production_column_names_id;
  }
}

export function useReopenProductionCard() {
  const dialog = useDialog();
  const toast = useToast();
  const isReopening = ref(false);

  async function reopenCard(card) {
    if (!card?.id || isReopening.value) {
      return null;
    }

    const confirmed = await dialog.confirm({
      title: 'Reabrir card?',
      text: 'A porcentagem de produção será zerada (0%).',
      confirmText: 'Sim, reabrir',
    });

    if (!confirmed) {
      return null;
    }

    isReopening.value = true;

    try {
      const updated = await productionService.updateProductionPercentage(card.id, 0);
      applyProductionPercentageUpdate(card, updated);
      toast.success('Card reaberto com sucesso.');
      return updated;
    } catch (error) {
      console.error('Erro ao reabrir card:', error);
      toast.error(error.response?.data?.message || 'Erro ao reabrir o card. Tente novamente.');
      return null;
    } finally {
      isReopening.value = false;
    }
  }

  return {
    isReopening,
    reopenCard,
  };
}
