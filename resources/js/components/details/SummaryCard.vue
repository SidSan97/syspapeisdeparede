<template>
  <div class="card mb-4">
    <div class="card-header bg-transparent">
      <h5 class="mb-0 fw-semibold">Resumo</h5>
    </div>
    <div class="card-body">
      <div class="mb-3">
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted">Total de Ambientes:</span>
          <strong>{{ data.rooms?.length || 0 }}</strong>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted">Total de Paredes:</span>
          <strong>{{ totalWalls }}</strong>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted">Metros:</span>
          <strong>{{ formatNumber(totalArea) }}</strong>
        </div>
        <div v-if="data.selected_carrier_price" class="d-flex justify-content-between mb-2">
          <span class="text-muted">Frete:</span>
          <strong>{{ formatCurrency(data.selected_carrier_price) }}</strong>
        </div>
        <div v-if="artsTotal > 0" class="d-flex justify-content-between mb-2">
          <span class="text-muted">Valor das artes:</span>
          <strong>{{ formatCurrency(artsTotal) }}</strong>
        </div>
        <div v-if="data.delivery_time" class="d-flex justify-content-between mb-2">
          <span class="text-muted">Prazo de entrega:</span>
          <strong>{{ formatDeliveryTime(data.delivery_time) }}</strong>
        </div>
      </div>
      <hr />
      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0 fw-semibold">Total à Vista:</h6>
          <h5 class="mb-0 text-success">
            {{ formatCurrency(data.total_amount) }}
          </h5>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0 fw-semibold">Total a Prazo:</h6>
          <h5 class="mb-0 text-primary">
            {{ formatCurrency(data.total_amount_installments) }}
          </h5>
        </div>
      </div>

      <hr />

      <div v-if="stripSummary" class="mt-3 small text-muted">
        <strong class="text-body">Resumo de Faixas:</strong>
        {{ stripSummary }}
      </div>

      <button class="btn btn-default mt-2" @click="copyStripSummary">Copiar resumo</button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
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
