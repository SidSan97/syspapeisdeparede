<template>
  <div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-3">
      <IconNotes :size="18" />
      <h5 class="mb-0 fs-sm fw-semibold">Resumo</h5>

      <button class="btn btn-outline-default btn-sm ms-auto" @click="copyStripSummary">
        <IconCopy :size="16" />
        Copiar resumo
      </button>
    </div>

    <div class="fs-sm mb-3">
      <div class="d-flex justify-content-between mb-1">
        <span class="text-muted">Total de Ambientes:</span>
        <span>{{ data.rooms?.length || 0 }}</span>
      </div>
      <div class="d-flex justify-content-between mb-1">
        <span class="text-muted">Total de Paredes:</span>
        <span>{{ totalWalls }}</span>
      </div>
      <div class="d-flex justify-content-between mb-1">
        <span class="text-muted">Metros:</span>
        <span>{{ formatNumber(totalArea) }}</span>
      </div>
      <div v-if="data.selected_carrier_price" class="d-flex justify-content-between mb-1">
        <span class="text-muted">Frete:</span>
        <span>{{ formatCurrency(data.selected_carrier_price) }}</span>
      </div>
      <div v-if="artsTotal > 0" class="d-flex justify-content-between mb-1">
        <span class="text-muted">Valor das artes:</span>
        <span>{{ formatCurrency(artsTotal) }}</span>
      </div>
      <div v-if="data.delivery_time" class="d-flex justify-content-between mb-1">
        <span class="text-muted">Prazo de entrega:</span>
        <span>{{ formatDeliveryTime(data.delivery_time) }}</span>
      </div>
    </div>

    <hr />

    <div class="d-flex justify-content-between align-items-center fs-sm fw-medium mb-1">
      <span>Total à Vista:</span>
      <span>{{ formatCurrency(data.total_amount) }}</span>
    </div>
    <div class="d-flex justify-content-between align-items-center fs-sm fw-medium mb-1">
      <span>Total a Prazo:</span>
      <span>{{ formatCurrency(data.total_amount_installments) }}</span>
    </div>

    <hr />

    <div v-if="stripSummary" class="fs-sm">
      <span class="fw-medium">Resumo de Faixas: </span>
      <span class="text-muted">{{ stripSummary }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

import { IconNotes, IconCopy } from '@tabler/icons-vue';

import { useToast } from '@/composables/useToast';
import { useFormatting } from '@/composables/useFormatting';
import { calculatePartsTotalArea } from '@/utils/calculateStripsUtils.js';
import { copyBudgetSummary } from '@/utils/copyBudgetSummaryUtils';
import { buildStripSummaryByRoom, buildStripSummaryFromParts } from '@/utils/stripSummaryUtils';

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
});

const toast = useToast();
const { formatCurrency, formatNumber, formatDeliveryTime } = useFormatting();

const totalWalls = computed(() => {
  if (!props.data?.rooms) return 0;
  return props.data.rooms.reduce((total, room) => {
    return total + (room.walls?.length || 0);
  }, 0);
});
const stripSummary = computed(() => buildStripSummaryFromParts(props.data?.rooms || []));

const roomStripSummary = computed(() => buildStripSummaryByRoom(props.data?.rooms || []));

const totalArea = computed(() => {
  const rooms = props.data?.rooms;
  if (Array.isArray(rooms) && rooms.length) {
    return calculatePartsTotalArea(rooms);
  }
  return Number(props.data?.total_area ?? 0);
});

const freightAmount = computed(() => Number(props.data?.selected_carrier_price ?? 0));
const artsTotal = computed(() => Number(props.data?.payment_breakdown?.base?.ARTES ?? 0));

async function copyStripSummary() {
  await copyBudgetSummary(
    {
      totalStrips: roomStripSummary.value.totalStrips,
      totalArea: formatNumber(totalArea.value),
      totalVista: formatCurrency(props.data?.total_amount || 0),
      totalPrazo: formatCurrency(props.data?.total_amount_installments || 0),
      roomSummaryLines: roomStripSummary.value.lines,
      freight: freightAmount.value > 0 ? formatCurrency(freightAmount.value) : '',
      artsTotal: artsTotal.value > 0 ? formatCurrency(artsTotal.value) : '',
    },
    toast,
  );
}
</script>
