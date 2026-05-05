import { useKanbanBoard } from './useKanbanBoard';
import { layoutService } from '@/services/layoutService';

export function useLayoutBoard() {
  return useKanbanBoard({
    fetchColumnsFn: layoutService.getColumns,
    createColumnFn: layoutService.createColumn,
    updateColumnFn: layoutService.updateColumn,
    deleteColumnFn: layoutService.deleteColumn,
    reorderColumnsFn: layoutService.reorderColumns,
  });
}
