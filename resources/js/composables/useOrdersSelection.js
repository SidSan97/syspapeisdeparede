import { ref, watch, computed } from 'vue';

export function useOrderSelection(ordersRef, emit) {
  const selectedIds = ref([]);

  const selectedOrders = computed(() => {
    const orders = ordersRef.value || [];
    return orders.filter((order) => selectedIds.value.includes(order.id));
  });

  const selectedCount = computed(() => selectedIds.value.length);
  const canMerge = computed(() => selectedCount.value >= 2);
  const canBulkAction = computed(() => selectedCount.value > 0);

  function toggleAll(checked) {
    const orders = ordersRef.value || [];

    selectedIds.value = checked ? orders.map((o) => o.id) : [];
  }

  function toggleRow(id) {
    if (selectedIds.value.includes(id)) {
      selectedIds.value = selectedIds.value.filter((i) => i !== id);
    } else {
      selectedIds.value = [...selectedIds.value, id];
    }
  }

  function isSelected(id) {
    return selectedIds.value.includes(id);
  }

  function clearSelection() {
    selectedIds.value = [];
  }

  // Clean invalid selections when orders change
  watch(
    () => ordersRef.value,
    (newOrders) => {
      if (!newOrders) return;
      const validIds = new Set(newOrders.map((o) => o.id));
      const validSelection = selectedIds.value.filter((id) => validIds.has(id));

      if (validSelection.length !== selectedIds.value.length) {
        selectedIds.value = validSelection;
      }
    },
    { deep: true },
  );

  // Emit changes to parent if needed
  if (emit) {
    watch(selectedIds, (val) => {
      emit('update:selected', val);
    });
  }

  return {
    selectedIds,
    selectedOrders,
    selectedCount,
    canMerge,
    canBulkAction,
    toggleAll,
    toggleRow,
    isSelected,
    clearSelection,
  };
}
