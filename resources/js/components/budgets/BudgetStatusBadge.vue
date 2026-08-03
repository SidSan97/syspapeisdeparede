<template>
  <span :class="`badge text-bg-${color} fs-sm`">{{ label }}</span>
</template>

<script setup>
import { computed } from 'vue';
import { BUDGET_STATUS_COLORS, BUDGET_STATUS_LABELS } from '@/constants/budgetStatuses';

const props = defineProps({
  status: {
    type: String,
    default: null,
  },
});

const label = computed(() => {
  if (!props.status) {
    return '—';
  }

  return BUDGET_STATUS_LABELS[props.status.toLocaleLowerCase()];
});

const color = computed(() => {
  if (!props.status) {
    return 'neutral';
  }

  return BUDGET_STATUS_COLORS[props.status.toLocaleLowerCase()];
});
</script>
