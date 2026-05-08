<template>
  <template v-if="metrics">
    <small class="text-muted d-block mt-1 mb-2">
      Metros: {{ formatMeters(metrics.meters) }}, Quantidade: {{ metrics.strips }}, Tamanho:
      {{ formatStripHeight(metrics.stripHeight) }} m
    </small>
    <p v-if="metrics.stripHeight > 6" class="mb-0 text-danger small">
      Obs.: faixas maiores que 6 metros são vendidas apenas em pares.
    </p>
  </template>
</template>

<script setup>
import { calculatePartMetrics } from '@/utils/calculateStripsUtils.js';
import { computed } from 'vue';

const props = defineProps({
  width: {
    type: [Number, String],
    default: 0,
  },
  height: {
    type: [Number, String],
    default: 0,
  },
});

const metrics = computed(() => calculatePartMetrics({ width: props.width, height: props.height }));

function formatMeters(value) {
  const n = Number(value);
  if (!Number.isFinite(n)) {
    return '0,00';
  }
  return n.toLocaleString('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

function formatStripHeight(value) {
  const n = Number(value);
  if (!Number.isFinite(n) || n <= 0) {
    return '0,00';
  }
  return n.toLocaleString('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}
</script>
