import { ref } from 'vue';

function cleanFilters(filters = {}) {
  return Object.fromEntries(
    Object.entries(filters).filter(([, value]) => {
      if (value === null || value === undefined || value === false || value === '') return false;
      if (Array.isArray(value)) return value.length > 0;
      return true;
    }),
  );
}

export function useKanbanCards({ fetchCardsFn, updateCardColumnFn }, columnsRef) {
  const cards = ref([]);
  const loading = ref(false);

  async function fetchCards(filters = {}) {
    try {
      loading.value = true;

      const payload = await fetchCardsFn(null, cleanFilters(filters));

      const firstColumnId = columnsRef.value[0]?.id ?? null;

      cards.value = payload.map((card) => ({
        ...card,
        column: card.column != null ? Number(card.column) : firstColumnId,
      }));
    } finally {
      loading.value = false;
    }
  }

  async function moveCard(cardId, targetColumnId) {
    const index = cards.value.findIndex((c) => c.id === cardId);

    if (index === -1) return;

    const oldColumn = cards.value[index].column;

    cards.value[index].column = targetColumnId;

    try {
      await updateCardColumnFn(cardId, targetColumnId);
    } catch (error) {
      cards.value[index].column = oldColumn;

      throw error;
    }
  }

  function moveCardsFromDeletedColumn(columnId, targetColumnId) {
    cards.value = cards.value.map((card) =>
      card.column === columnId ? { ...card, column: targetColumnId } : card,
    );
  }

  return {
    cards,
    loading,

    fetchCards,
    moveCard,
    moveCardsFromDeletedColumn,
  };
}
