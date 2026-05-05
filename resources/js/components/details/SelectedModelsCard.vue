<template>
  <div
    v-if="
      data.rooms &&
      data.rooms.some(
        (r) => r.walls && r.walls.some((w) => w.collection_model || w.collection_model_name),
      )
    "
    class="card mb-4"
  >
    <div class="card-header bg-transparent">
      <h5 class="mb-0 fw-semibold">Modelos Selecionados</h5>
    </div>
    <div class="card-body">
      <div v-for="(room, roomIndex) in data.rooms" :key="roomIndex">
        <div v-if="room.walls && room.walls.some((w) => showWallInSelectedModels(w))" class="mb-4">
          <h6 class="mb-3">{{ room.name || `Ambiente ${roomIndex + 1}` }}</h6>
          <div v-for="(wall, wallIndex) in room.walls" :key="wallIndex">
            <div
              v-if="wall.collection_model || wall.collection_model_name"
              class="mb-3 pb-3 border-bottom"
            >
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="fw-semibold">{{ wall.name || `Parede ${wallIndex + 1}` }}</div>
                  <div class="text-muted small">
                    Modelo: {{ wall.collection_model?.name || wall.collection_model_name }}
                  </div>
                  <div v-if="wall.collection_model" class="text-muted small mt-1">
                    <span>Valor: {{ formatCurrency(wall.collection_model.value) }}</span>
                    <span class="ms-3">Prazo: {{ wall.collection_model.deadline }} dia(s)</span>
                  </div>
                </div>
              </div>
            </div>

            <WallModelReferringFields
              v-if="hasWallModelReferringContent(wall)"
              :wall="wall"
              class="collection-models-section-item"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import WallModelReferringFields from '@/components/details/WallModelReferringFields.vue';
import { useFormatting } from '@/composables/useFormatting';
import { hasWallModelReferringContent } from '@/utils/wallModelReferringContent';

defineProps({
  data: {
    type: Object,
    required: true,
  },
});

const { formatCurrency } = useFormatting();

function showWallInSelectedModels(wall) {
  return Boolean(
    wall?.collection_model || wall?.collection_model_name || hasWallModelReferringContent(wall),
  );
}
</script>
