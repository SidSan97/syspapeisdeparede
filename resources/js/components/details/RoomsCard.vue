<template>
  <div class="card mb-4">
    <div class="card-header bg-transparent">
      <h5 class="mb-0 fw-semibold">Cômodos</h5>
    </div>
    <div class="card-body">
      <div v-if="!data.rooms || data.rooms.length === 0" class="text-center text-muted py-4">
        Nenhum ambiente cadastrado
      </div>
      <template v-else>
        <div v-for="(room, roomIndex) in data.rooms" :key="roomIndex" class="card mb-3">
          <div class="card-header">
            <strong>{{ room.name || `Ambiente ${roomIndex + 1}` }}</strong>
          </div>
          <div class="card-body">
            <div v-if="!room.walls || room.walls.length === 0" class="text-muted small">
              Nenhuma parede cadastrada
            </div>
            <template v-else>
              <div
                v-for="(wall, wallIndex) in room.walls"
                :key="wallIndex"
                class="card mb-3 border"
              >
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <strong>{{ wall.name || `Parede ${wallIndex + 1}` }}</strong>
                    <span
                      v-if="showWallStatus"
                      class="badge"
                      :class="getWallStatusClass(wall, roomIndex, wallIndex)"
                    >
                      {{ getWallStatusLabel(wall, roomIndex, wallIndex) }}
                    </span>
                  </div>

                  <div class="row mb-3">
                    <div class="col-md-4" v-if="wall.direction">
                      <div class="text-muted small">Direção</div>
                      <div class="fw-semibold">
                        {{ formatDirection(wall.direction) }}
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="text-muted small">Largura (m)</div>
                      <div class="fw-semibold">
                        {{ formatNumber(wall.width) }}
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="text-muted small">Altura (m)</div>
                      <div class="fw-semibold">
                        {{ formatNumber(wall.height) }}
                      </div>
                    </div>
                  </div>

                  <!-- Continuações -->
                  <div v-if="wall.continuations && wall.continuations.length > 0" class="mb-3">
                    <div class="text-muted small mb-2">Continuações</div>
                    <div
                      v-for="(continuation, contIndex) in wall.continuations"
                      :key="contIndex"
                      class="border-start border-primary ps-3 ms-2 mb-2"
                    >
                      <div class="row">
                        <div class="col-md-4" v-if="continuation.name">
                          <div class="text-muted small">Nome</div>
                          <div class="fw-semibold">
                            {{ continuation.name }}
                          </div>
                        </div>
                        <div v-if="continuationFitLabel(continuation)" class="col-md-4">
                          <div class="text-muted small">Encaixe</div>
                          <div class="fw-semibold">
                            {{ continuationFitLabel(continuation) }}
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="text-muted small">Largura (m)</div>
                          <div class="fw-semibold">
                            {{ formatNumber(continuation.width) }}
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="text-muted small">Altura (m)</div>
                          <div class="fw-semibold">
                            {{ formatNumber(continuation.height) }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <SelectedModelsCard :wall="wall" />

                  <!-- Cálculos da parede -->
                  <div v-if="wall.total_area" class="alert alert-success mb-0">
                    <strong>Metros:</strong>
                    {{ formatNumber(wall.total_area) }} m
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { useFormatting } from '@/composables/useFormatting';
import { useWallStatus } from '@/composables/useWallStatus';
import SelectedModelsCard from '@/components/details/SelectedModelsCard.vue';

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
  showWallStatus: {
    type: Boolean,
    default: true,
  },
});

const { formatNumber, formatDirection } = useFormatting();
const { getWallStatusLabel, getWallStatusClass } = useWallStatus(() => props.data);

function continuationFitLabel(continuation) {
  const raw = continuation?.fit ?? continuation?.Fit;
  if (raw == null) {
    return '';
  }
  const s = String(raw).trim();
  return s.length > 0 ? s : '';
}
</script>
