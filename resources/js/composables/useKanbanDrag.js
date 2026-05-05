import { ref } from 'vue';

export function useKanbanDrag(onDropFn) {
  const draggedItem = ref(null);

  function handleDragStart(event, item) {
    draggedItem.value = item;

    event.dataTransfer.effectAllowed = 'move';
  }

  async function handleDrop(event, columnId) {
    event.preventDefault();

    if (!draggedItem.value) return;

    try {
      await onDropFn(draggedItem.value, columnId);
    } finally {
      draggedItem.value = null;
    }
  }

  function allowDrop(event) {
    event.preventDefault();
  }

  return {
    draggedItem,

    handleDragStart,
    handleDrop,
    allowDrop,
  };
}
