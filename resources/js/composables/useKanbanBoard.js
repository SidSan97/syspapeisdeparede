import { ref } from 'vue';

export function useKanbanBoard({ fetchColumnsFn, createColumnFn, updateColumnFn, deleteColumnFn, reorderColumnsFn }) {
  const columns = ref([]);
  const loading = ref(false);

  async function fetchColumns() {
    try {
      loading.value = true;

      columns.value = await fetchColumnsFn();
    } finally {
      loading.value = false;
    }
  }

  async function createColumn(name) {
    const column = await createColumnFn(name);

    columns.value.push(column);
  }

  async function updateColumnName(columnId, newName, oldName) {
    const column = columns.value.find((c) => c.id === columnId);

    if (column) column.name = newName;

    try {
      await updateColumnFn(columnId, newName);
    } catch (error) {
      if (column) column.name = oldName;

      throw error;
    }
  }

  async function deleteColumn(columnId, targetColumnId) {
    await deleteColumnFn(columnId, targetColumnId);

    columns.value = columns.value.filter((c) => c.id !== columnId);
  }

  async function reorderColumns(newOrder) {
    if (!reorderColumnsFn) return;

    const payload = newOrder.map((id, index) => ({ id, order: index }));
    await reorderColumnsFn(payload);
  }

  function getFirstColumnId() {
    return columns.value[0]?.id ?? null;
  }

  return {
    columns,
    loading,

    fetchColumns,
    createColumn,
    updateColumnName,
    deleteColumn,

    getFirstColumnId,
    reorderColumns,
  };
}
