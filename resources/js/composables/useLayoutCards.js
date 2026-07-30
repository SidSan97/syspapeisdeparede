import { computed, ref, toValue } from 'vue';

import { useKanbanCards } from './useKanbanCards';
import { useToast } from '@/composables/useToast';
import { layoutService } from '@/services/layoutService';
import {
  formatActivityCompact,
  isCardCompleted,
  mergeActivityPayload,
} from '@/utils/layoutCardActivityUtils';

export function useLayoutCards(columnsRef) {
  return useKanbanCards(
    {
      fetchCardsFn: layoutService.getLayouts,

      updateCardColumnFn: layoutService.updateCardColumn,
    },
    columnsRef,
  );
}

/**
 * Estado e ações para conclusão do card no quadro de Layout (ex.: modal).
 *
 * @param {import('vue').MaybeRefOrGetter<object|null|undefined>} cardRef
 * @param {(event: 'activity-updated', payload: object) => void} emit
 */
export function useLayoutCardCompletion(cardRef, emit) {
  const toast = useToast();
  const completing = ref(false);

  const isCompleted = computed(() => isCardCompleted(toValue(cardRef)));

  const completedAtLabel = computed(() => {
    const card = toValue(cardRef);
    if (!card?.completed_at) return '';
    const date = new Date(card.completed_at);
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  });

  const completedDurationLabel = computed(() => {
    const card = toValue(cardRef);
    const seconds = Number(card?.activity_total_seconds ?? card?.activity_elapsed_seconds);
    if (!Number.isFinite(seconds) || seconds <= 0) return '';
    return formatActivityCompact(seconds);
  });

  async function handleComplete(payload = {}) {
    const card = toValue(cardRef);
    if (!card?.id || completing.value || isCompleted.value) {
      return;
    }

    if (!payload.accepted_terms_of_use) {
      toast.warning('É necessário aceitar o Termo de Uso para concluir o card.');
      return;
    }

    completing.value = true;
    try {
      const data = await layoutService.completeOrderBudget(card.id, {
        accepted_terms_of_use: true,
      });
      const merged = mergeActivityPayload(card, data);
      emit('activity-updated', {
        activity_running_since: merged.activity_running_since,
        activity_elapsed_seconds: merged.activity_elapsed_seconds,
        activity_total_seconds: merged.activity_total_seconds,
        activity_is_running: merged.activity_is_running,
        activity_sessions: merged.activity_sessions,
        completed_at: merged.completed_at,
        is_completed: merged.is_completed,
      });
      toast.success('Card concluído.');
    } catch (error) {
      const message =
        error?.response?.data?.message ||
        error?.response?.data?.errors?.accepted_terms_of_use?.[0] ||
        error?.message ||
        'Não foi possível concluir o card.';
      toast.error(message);
    } finally {
      completing.value = false;
    }
  }

  return {
    completing,
    isCompleted,
    completedAtLabel,
    completedDurationLabel,
    handleComplete,
  };
}
