import { useKanbanCards } from './useKanbanCards';
import { layoutService } from '@/services/layoutService';

export function useLayoutCards(columnsRef) {
  return useKanbanCards(
    {
      fetchCardsFn: layoutService.getLayouts,

      updateCardColumnFn: layoutService.updateCardColumn,
    },
    columnsRef,
  );
}
