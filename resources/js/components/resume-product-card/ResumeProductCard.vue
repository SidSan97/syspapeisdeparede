<template>
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title">Resumo</h5>

      <div class="mb-3">
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted">Total de Ambientes:</span>
          <strong>{{ totalRooms }}</strong>
        </div>

        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted">Total de Paredes:</span>
          <strong>{{ totalWalls }}</strong>
        </div>

        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted">Metros:</span>
          <strong>{{ totalArea }}</strong>
        </div>

        <div
          v-if="freight && freight !== ''"
          class="d-flex justify-content-between mb-2"
        >
          <span class="text-muted">Frete:</span>
          <strong>{{ freight }}</strong>
        </div>

        <hr />

        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted">Produção:</span>
          <strong>3 a 5 dias úteis</strong>
        </div>

        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted">Nova Arte:</span>
          <strong>{{ artworkDays }} dias úteis</strong>
        </div>

        <div
          v-if="transportDays !== '' && transportDays !== null"
          class="d-flex justify-content-between mb-2"
        >
          <span class="text-muted">Transporte:</span>
          <strong>{{ transportDays }} dias úteis</strong>
        </div>

        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted">Prazo total:</span>
          <strong>{{ deliveryTime }}</strong>
        </div>
      </div>

      <hr />

      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0 fw-semibold">Total à Vista:</h6>
          <h5 class="mb-0 text-success">{{ totalVista }}</h5>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0 fw-semibold">Total a Prazo:</h6>
          <h5 class="mb-0 text-primary">{{ totalPrazo }}</h5>
        </div>

        <hr>

        <div
          v-if="stripSummary"
          class="mt-3 small text-muted"
        >
          <strong class="text-body">Resumo de Faixas:</strong>
          {{ stripSummary }}
        </div>

        <button class="btn btn-primary mt-4" @click="copyStripSummary">
          Copiar Resumo
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  buildBudgetSummaryText,
  copyBudgetSummaryText,
} from '@/utils/copyBudgetSummaryUtils';

const props = defineProps({
  totalRooms: {
    type: [Number, String],
    required: true,
  },
  totalWalls: {
    type: [Number, String],
    required: true,
  },
  totalArea: {
    type: [Number, String],
    required: true,
  },
  freight: {
    type: [Number, String],
    default: '',
  },
  deliveryTime: {
    type: [Number, String],
    required: true,
  },
  artworkDays: {
    type: [Number, String],
    required: true,
  },
  transportDays: {
    type: [Number, String],
    default: '',
  },
  totalVista: {
    type: [Number, String],
    required: true,
  },
  totalPrazo: {
    type: [Number, String],
    required: true,
  },
  stripSummary: {
    type: String,
    default: '',
  },
});

async function copyStripSummary() {
  const text = buildBudgetSummaryText({
    totalWalls: props.totalWalls,
    totalArea: props.totalArea,
    totalVista: props.totalVista,
    totalPrazo: props.totalPrazo,
    stripSummary: props.stripSummary,
  });

  const ok = await copyBudgetSummaryText(text);
  if (ok) {
    window.Toast.fire({
      icon: 'success',
      title: 'Resumo copiado para a área de transferência',
    });
  } else {
    window.Toast.fire({
      icon: 'error',
      title: 'Não foi possível copiar o resumo.',
    });
  }
}
</script>

<style scoped>

</style>
