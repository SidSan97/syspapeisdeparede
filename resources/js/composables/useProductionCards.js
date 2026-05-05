import { useKanbanCards } from '@/composables/useKanbanCards';
import { productionService } from '@/services/productionService';

export function useProductionCards(columnsRef) {
  return useKanbanCards(
    {
      fetchCardsFn: productionService.getLayouts,

      updateCardColumnFn: productionService.updateCardColumn,
    },
    columnsRef,
  );
}
