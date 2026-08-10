<template>
  <span :class="`badge text-bg-${color} fs-sm`">{{ label }}</span>
</template>

<script setup>
import { computed } from 'vue';

import { ORDER_STATUS_COLORS, ORDER_STATUS_LABELS } from '@/constants/orderStatuses';

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

  return ORDER_STATUS_LABELS[props.status.toLocaleLowerCase()];
});

const color = computed(() => {
  if (!props.status) {
    return 'neutral';
  }

  return ORDER_STATUS_COLORS[props.status.toLocaleLowerCase()];
});
</script>
