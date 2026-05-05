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
          <strong>{{ formatNumber(data.total_area) }}</strong>
        </div>
        <div v-if="data.selected_carrier_price" class="d-flex justify-content-between mb-2">
          <span class="text-muted">Frete:</span>
          <strong>{{ formatCurrency(data.selected_carrier_price) }}</strong>
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
import { buildBudgetSummaryText, copyBudgetSummaryText } from '@/utils/copyBudgetSummaryUtils';
import { buildStripSummaryFromRooms } from '@/utils/stripSummaryUtils';

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
const stripSummary = computed(() => buildStripSummaryFromRooms(props.data?.rooms || []));

async function copyStripSummary() {
  const text = buildBudgetSummaryText({
    totalWalls: totalWalls.value,
    totalArea: formatNumber(props.data?.total_area || 0),
    totalVista: formatCurrency(props.data?.total_amount || 0),
    totalPrazo: formatCurrency(props.data?.total_amount_installments || 0),
    stripSummary: stripSummary.value || '-',
  });

  const ok = await copyBudgetSummaryText(text);
  if (ok) {
    toast.success('Resumo copiado para a área de transferência');
  } else {
    toast.error('Não foi possivel copiar o resumo.');
  }
}
</script>
