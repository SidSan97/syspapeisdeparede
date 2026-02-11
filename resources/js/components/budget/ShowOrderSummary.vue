<template>
  <div v-if="orderSummary" class="border rounded p-3 bg-body-secondary mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <span class="text-muted small">Orçamento</span>
      <span class="badge bg-secondary text-white">#{{ orderSummary.id }}</span>
    </div>
    <div class="fw-semibold">{{ orderSummary.name }}</div>
    <div class="text-muted small mt-2">
      Valor total: <span class="fw-semibold">{{ orderSummary.formattedTotal }}</span>
    </div>
    <div class="text-muted small">
      Prazo de entrega: {{ orderSummary.deliveryTime }}
    </div>
    <div v-if="showStatus && orderSummary.status" class="text-muted small">
      Status atual: {{ orderSummary.status }}
    </div>
  </div>
</template>

<script setup>
defineProps({
  orderSummary: {
    type: Object,
    default: null,
  },
  showStatus: {
    type: Boolean,
    default: false,
  },
});
</script>
