<template>
  <div v-if="budget?.rooms?.length" class="border rounded p-3 mb-3">
    <h6 class="fw-semibold mb-3">Ambientes e medidas</h6>
    <div
      v-for="(room, roomIdx) in budget.rooms"
      :key="room.id ?? roomIdx"
      class="mb-3"
    >
      <div class="fw-semibold text-secondary small mb-2">
        {{ room.name ?? `Ambiente ${roomIdx + 1}` }}
      </div>
      <div
        v-for="(wall, wallIdx) in (room.walls ?? [])"
        :key="wall.id ?? wallIdx"
        class="d-flex flex-wrap align-items-center gap-2 small py-2 border-bottom border-secondary border-opacity-25"
      >
        <span class="fw-medium">{{ wall.name ?? `Parede ${wallIdx + 1}` }}</span>
        <span class="text-muted">
          {{ formatMeasure(wall.width) }} × {{ formatMeasure(wall.height) }} m
        </span>
        <span v-if="wall.total_area" class="badge bg-light text-dark">
          Área: {{ formatMeasure(wall.total_area) }} m²
        </span>
        <span v-if="wall.collection_model?.name || wall.collection_model_name" class="text-muted">
          · {{ wall.collection_model?.name ?? wall.collection_model_name }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  budget: { type: Object, default: null },
});

function formatMeasure(value) {
  if (value == null || value === '') return '–';
  const n = Number(value);
  return Number.isFinite(n) ? n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : String(value);
}
</script>