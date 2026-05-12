<template>
  <template v-if="resolvedMetrics">
    <small class="text-muted d-block mt-1 mb-2">
      Metros: {{ formatNumberBR(resolvedMetrics.meters) }}, Quantidade:
      {{ resolvedMetrics.strips }}, Tamanho:
      {{ formatNumberBR(resolvedMetrics.stripHeight) }} m
    </small>
    <p v-if="resolvedMetrics.stripHeight > 6" class="mb-0 text-danger small">
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
  /**
   * Métricas pré-calculadas (parede ou continuação dentro de uma sequência).
   * Quando fornecidas, têm prioridade sobre `width`/`height`.
   */
  metrics: {
    type: Object,
    default: null,
  },
});

const resolvedMetrics = computed(() => {
  const m = props.metrics;
  if (m && Number(m.strips) > 0 && Number(m.stripHeight) > 0) {
    return {
      strips: Number(m.strips),
      stripHeight: Number(m.stripHeight),
      meters: Number(m.meters ?? Number(m.strips) * Number(m.stripHeight)),
    };
  }

  if (m) {
    return null;
  }

  return calculatePartMetrics({ width: props.width, height: props.height });
});

function formatNumberBR(value) {
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
