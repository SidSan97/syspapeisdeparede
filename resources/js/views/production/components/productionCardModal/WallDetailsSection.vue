<template>
  <div v-if="wall" class="wall-details-section">
    <h3 class="wall-details-section-title">
      <i class="fa fa-ruler"></i> Detalhes da Parede
    </h3>
    <div class="wall-details-section-content">
      <div class="wall-details-section-grid">
        <div class="wall-details-section-item">
          <div class="wall-details-section-label">Nome da Parede</div>
          <div class="wall-details-section-value">{{ wall.name || 'Não informado' }}</div>
        </div>
        <div class="wall-details-section-item">
          <div class="wall-details-section-label">Largura</div>
          <div class="wall-details-section-value">{{ formatNumber(wall.width) }} m</div>
        </div>
        <div class="wall-details-section-item">
          <div class="wall-details-section-label">Altura</div>
          <div class="wall-details-section-value">{{ formatNumber(wall.height) }} m</div>
        </div>
        <div class="wall-details-section-item">
          <div class="wall-details-section-label">Metros</div>
          <div class="wall-details-section-value">{{ formatNumber(wall.total_area) }} m</div>
        </div>
        <div v-if="wall.strip_height" class="wall-details-section-item">
          <div class="wall-details-section-label">Tamanho da Faixa</div>
          <div class="wall-details-section-value">{{ formatNumber(wall.strip_height) }} m</div>
        </div>
        <div v-if="wall.strip_count" class="wall-details-section-item">
          <div class="wall-details-section-label">Quantidade de Faixas</div>
          <div class="wall-details-section-value">{{ wall.strip_count }}</div>
        </div>
      </div>

      <!-- Continuações -->
      <div v-if="wall.continue_same_art && wall.continuations && wall.continuations.length > 0" class="wall-details-section-continuations">
        <h4 class="wall-details-section-continuations-title">
          <i class="fa fa-arrows-h"></i> Continuações
        </h4>
        <div class="wall-details-section-continuations-list">
          <div
            v-for="(continuation, index) in wall.continuations"
            :key="index"
            class="wall-details-section-continuation-item"
          >
            <div class="wall-details-section-continuation-header">
              <span class="wall-details-section-continuation-number">Continuação {{ index + 1 }}</span>
            </div>
            <div class="wall-details-section-continuation-details">
              <div class="wall-details-section-continuation-detail">
                <span class="wall-details-section-continuation-label">Largura:</span>
                <span class="wall-details-section-continuation-value">{{ formatNumber(continuation.width) }} m</span>
              </div>
              <div class="wall-details-section-continuation-detail">
                <span class="wall-details-section-continuation-label">Altura:</span>
                <span class="wall-details-section-continuation-value">{{ formatNumber(continuation.height) }} m</span>
              </div>
              <div class="wall-details-section-continuation-detail">
                <span class="wall-details-section-continuation-label">Metro:</span>
                <span class="wall-details-section-continuation-value">
                  {{ formatNumber(getWallArea(continuation)) }} m
                </span>
              </div>
              <div class="wall-details-section-continuation-detail">
                <span class="wall-details-section-continuation-label">Quantidade de Faixas:</span>
                <span class="wall-details-section-continuation-value">{{ calculateStrips(continuation) }}</span>
              </div>
              <div class="wall-details-section-continuation-detail">
                <span class="wall-details-section-continuation-label">Tamanho da Faixa:</span>
                <span class="wall-details-section-continuation-value">{{ formatNumber(calculateStripHeight(continuation)) }} m</span>
              </div>
              <div class="wall-details-section-continuation-detail">
                <span class="wall-details-section-continuation-label">Sentido:</span>
                <span class="wall-details-section-continuation-value">{{ getDirection(continuation) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { getWallArea, calculateStrips, calculateStripHeight } from '@/utils/calculateStripsUtils.js';

const props = defineProps({
  wall: {
    type: Object,
    default: null,
  },
});

function formatNumber(value) {
  if (value === null || value === undefined) {
    return '-';
  }
  const numericValue = Number(value);
  return Number.isFinite(numericValue) ? numericValue.toFixed(2) : value;
}

function getDirection(continuation) {
  if (continuation.direction === 'left-to-right') {
    return 'Esquerda para direita';
  } else if (continuation.direction === 'right-to-left') {
    return 'Direita para esquerda';
  }
  return '';
}
</script>

<style lang="scss" scoped>
.wall-details-section {
  margin-bottom: 24px;

  &:last-child {
    margin-bottom: 0;
  }
}

.wall-details-section-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--bs-body-color);
  margin-bottom: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.wall-details-section-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.wall-details-section-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
}

.wall-details-section-item {
  padding: 0.75rem;
  background-color: var(--bs-secondary-bg);
  border-radius: 0.375rem;
}

.wall-details-section-label {
  font-size: 0.75rem;
  margin-bottom: 0.25rem;
  font-weight: 500;
}

.wall-details-section-value {
  font-size: 0.875rem;
  color: var(--bs-body-color);
  font-weight: 600;
}

.wall-details-section-continuations {
  margin-top: 8px;
}

.wall-details-section-continuations-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--bs-body-color);
  margin-bottom: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;

  i {
    color: var(--bs-secondary);
    font-size: 0.75rem;
  }
}

.wall-details-section-continuations-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.wall-details-section-continuation-item {
  padding: 0.75rem;
  background-color: var(--bs-secondary-bg);
  border-radius: 0.375rem;
  border-left: 3px solid var(--bs-primary);
}

.wall-details-section-continuation-header {
  margin-bottom: 0.5rem;
}

.wall-details-section-continuation-number {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--bs-body-color);
}

.wall-details-section-continuation-details {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.wall-details-section-continuation-detail {
  display: flex;
  align-items: center;
  gap: 6px;
}

.wall-details-section-continuation-label {
  font-size: 0.75rem;
  color: var(--bs-secondary);
}

.wall-details-section-continuation-value {
  font-size: 0.8125rem;
  color: var(--bs-body-color);
  font-weight: 600;
}
</style>

