<template>
  <div class="mb-4">
    <div class="d-flex align-items-center gap-3 mb-3">
      <IconTruckDelivery :size="18" />
      <h5 class="mb-0 fs-sm fw-semibold">Frete</h5>
    </div>
    <div class="fs-sm mb-1">
      <span class="text-muted">CEP: </span>
      <span>{{ data.cep || '-' }}</span>
    </div>
    <div v-if="data.selected_carrier_name" class="fs-sm mb-1">
      <span class="text-muted">Transportadora: </span>
      <span>{{ data.selected_carrier_name }}</span>
    </div>
    <div v-if="data.selected_carrier_price" class="fs-sm mb-1">
      <span class="text-muted">Valor do Frete: </span>
      <span>{{ formatCurrency(data.selected_carrier_price) }}</span>
    </div>
    <div v-if="data.selected_carrier_delivery_time" class="fs-sm">
      <span class="text-muted">Prazo de Entrega: </span>
      <span>{{ formatDeliveryTime(data.selected_carrier_delivery_time) }}</span>
    </div>
  </div>
</template>

<script setup>
import { IconTruckDelivery } from '@tabler/icons-vue';
import { useFormatting } from '@/composables/useFormatting';

defineProps({
  data: {
    type: Object,
    required: true,
  },
});

const { formatCurrency, formatDeliveryTime } = useFormatting();
</script>
