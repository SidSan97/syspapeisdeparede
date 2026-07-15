import { productionService } from '@/services/productionService';
import { useKanbanBoard } from './useKanbanBoard';

export function useProductionBoard() {
  return useKanbanBoard({
    fetchColumnsFn: productionService.getColumns,
    createColumnFn: productionService.createColumn,
    updateColumnFn: productionService.updateColumn,
    deleteColumnFn: productionService.deleteColumn,
    reorderColumnsFn: productionService.reorderColumns,
  });
}
