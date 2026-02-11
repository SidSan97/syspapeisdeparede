<template>
  <div v-if="budget?.rooms?.length" class="border rounded p-3 mb-3">
    <h6 class="fw-semibold mb-3">Cômodos e medidas</h6>
    <div v-for="(room, roomIdx) in budget.rooms" :key="roomIdx" class="mb-3">
      <div class="fw-semibold text-primary mb-2">{{ room.name || `Ambiente ${roomIdx + 1}` }}</div>
      <div v-for="(wall, wallIdx) in (room.walls || [])" :key="wallIdx" class="border-start border-2 border-secondary ps-3 ms-2 mb-2">
        <div class="fw-semibold small">{{ wall.name || `Parede ${wallIdx + 1}` }}</div>
        <div class="row small text-muted g-1 mt-1">
          <div class="col-auto">Largura: {{ formatNumber(wall.width) }} m</div>
          <div class="col-auto">Altura: {{ formatNumber(wall.height) }} m</div>
          <div v-if="wall.total_area" class="col-auto">Metragem: {{ formatNumber(wall.total_area) }} m²</div>
        </div>
        <div v-if="wall.collection_model || wall.collectionModel" class="small mt-1">
          Modelo: {{ (wall.collection_model || wall.collectionModel)?.name || (wall.collection_model_name || wall.collectionModelName) || '-' }}
        </div>
        <template v-if="getWallReviewData">
          <div v-if="getWallReviewData(wall, room, roomIdx, wallIdx).comment" class="small mt-1">
            <span class="text-muted">Descrição:</span> {{ getWallReviewData(wall, room, roomIdx, wallIdx).comment }}
          </div>
          <div v-if="getWallReviewData(wall, room, roomIdx, wallIdx).link" class="small mt-1">
            <span class="text-muted">Link:</span>
            <a :href="getWallReviewData(wall, room, roomIdx, wallIdx).link" target="_blank" rel="noopener" class="text-break">{{ getWallReviewData(wall, room, roomIdx, wallIdx).link }}</a>
          </div>
          <div v-if="getWallReviewData(wall, room, roomIdx, wallIdx).artName" class="small mt-1">
            <span class="text-muted">Arte selecionada:</span> {{ getWallReviewData(wall, room, roomIdx, wallIdx).artName }}
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useFormatting } from '@/composables/useFormatting';

defineProps({
  budget: {
    type: Object,
    default: null,
  },
  getWallReviewData: {
    type: Function,
    default: null,
  },
});

const { formatNumber } = useFormatting();
</script>
