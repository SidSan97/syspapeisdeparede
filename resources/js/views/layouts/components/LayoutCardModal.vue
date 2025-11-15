<template>
  <Teleport v-if="card" to="body">
    <div class="trello-modal-overlay" @click="handleClose">
      <div class="trello-modal" @click.stop>
        <div class="trello-modal-header">
          <h2 class="trello-modal-title">{{ card.name }}</h2>
          <button class="trello-modal-close" @click="handleClose">
            <i class="fa fa-times"></i>
          </button>
        </div>
        <div class="trello-modal-body">
          <div v-if="card.image" class="trello-modal-image">
            <img :src="card.image" :alt="card.name" />
          </div>

          <div class="trello-modal-section">
            <h3 class="trello-modal-section-title">
              <i class="fa fa-calendar"></i> Prazo
            </h3>
            <div class="trello-modal-info">
              <span>{{ card.delivery_date_start }} - {{ card.delivery_date_end }}</span>
              <span class="trello-modal-info-label">{{ card.delivery_time }} dias</span>
            </div>
          </div>

          <div class="trello-modal-section">
            <h3 class="trello-modal-section-title">
              <i class="fa fa-money"></i> Valor do Orçamento
            </h3>
            <div class="trello-modal-info">
              <span class="trello-modal-amount">{{ formatCurrency(card.total_amount) }}</span>
            </div>
          </div>

          <div v-if="card.budget" class="trello-modal-section">
            <h3 class="trello-modal-section-title">
              <i class="fa fa-cube"></i> Detalhes do Modelo
            </h3>
            <div v-if="getCollectionModels(card.budget).length > 0" class="trello-modal-models">
              <div
                v-for="(model, index) in getCollectionModels(card.budget)"
                :key="index"
                class="trello-modal-model"
              >
                <div class="trello-modal-model-name">{{ model.name }}</div>
                <div v-if="model.files && model.files.length > 0" class="trello-modal-model-images">
                  <div
                    v-for="(file, fileIndex) in model.files"
                    :key="fileIndex"
                    class="trello-modal-model-image"
                  >
                    <img :src="getImageUrl(file)" :alt="file.name || 'Imagem'" />
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="trello-modal-info text-muted">
              Nenhum modelo selecionado
            </div>
          </div>

          <div v-if="card.budget" class="trello-modal-section">
            <h3 class="trello-modal-section-title">
              <i class="fa fa-link"></i> Links
            </h3>
            <div v-if="card.budget.link_referring_model" class="trello-modal-info">
              <a
                :href="card.budget.link_referring_model"
                target="_blank"
                rel="noopener noreferrer"
                class="trello-modal-link"
              >
                <i class="fa fa-external-link"></i>
                {{ card.budget.link_referring_model }}
              </a>
            </div>
            <div v-else class="trello-modal-info text-muted">
              Nenhum link disponível
            </div>
          </div>

          <div v-if="card.budget" class="trello-modal-section">
            <h3 class="trello-modal-section-title">
              <i class="fa fa-comment"></i> Comentários
            </h3>
            <div v-if="card.budget.comment_referring_model" class="trello-modal-comment">
              {{ card.budget.comment_referring_model }}
            </div>
            <div v-else class="trello-modal-info text-muted">
              Nenhum comentário disponível
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
const props = defineProps({
  card: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close']);

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL',
});

function formatCurrency(value) {
  if (value === null || value === undefined) {
    return currencyFormatter.format(0);
  }
  const numericValue = Number(value);
  return currencyFormatter.format(Number.isFinite(numericValue) ? numericValue : 0);
}

function getCollectionModels(budget) {
  if (!budget || !budget.rooms) {
    return [];
  }

  const models = [];
  budget.rooms.forEach(room => {
    if (room.walls) {
      room.walls.forEach(wall => {
        // Pode vir como collection_model ou collectionModel
        const model = wall.collection_model || wall.collectionModel;
        if (model) {
          models.push(model);
        }
      });
    }
  });

  return models;
}

function getImageUrl(file) {
  if (file.url) {
    return file.url;
  }
  if (file.fileUrl) {
    return file.fileUrl;
  }
  if (file.file_path) {
    // Se for um caminho relativo, construir a URL completa
    if (file.file_path.startsWith('http')) {
      return file.file_path;
    }
    return `/storage/${file.file_path}`;
  }
  return '';
}

function handleClose() {
  emit('close');
}
</script>

<style lang="scss" scoped>
// Modal Styles
.trello-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 20px;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.trello-modal {
  background-color: #ffffff;
  border-radius: 8px;
  width: 100%;
  max-width: 768px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 8px 16px rgba(9, 30, 66, 0.25);
  animation: slideUp 0.3s ease;
  overflow: hidden;
}

@keyframes slideUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.trello-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid #dfe1e6;
}

.trello-modal-title {
  font-size: 20px;
  font-weight: 600;
  color: #172b4d;
  margin: 0;
}

.trello-modal-close {
  background: none;
  border: none;
  font-size: 20px;
  color: #5e6c84;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: background-color 0.2s ease;

  &:hover {
    background-color: #dfe1e6;
    color: #172b4d;
  }
}

.trello-modal-body {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}

.trello-modal-image {
  width: 100%;
  max-height: 300px;
  margin-bottom: 24px;
  border-radius: 8px;
  overflow: hidden;
  background-color: #f4f5f7;

  img {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }
}

.trello-modal-section {
  margin-bottom: 24px;

  &:last-child {
    margin-bottom: 0;
  }
}

.trello-modal-section-title {
  font-size: 16px;
  font-weight: 600;
  color: #172b4d;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;

  i {
    color: #5e6c84;
  }
}

.trello-modal-info {
  color: #172b4d;
  font-size: 14px;
  line-height: 1.5;
}

.trello-modal-info-label {
  display: inline-block;
  margin-left: 8px;
  padding: 2px 8px;
  background-color: #dfe1e6;
  border-radius: 4px;
  font-size: 12px;
  color: #5e6c84;
}

.trello-modal-amount {
  font-size: 18px;
  font-weight: 600;
  color: #0079bf;
}

.trello-modal-models {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.trello-modal-model {
  padding: 12px;
  background-color: #f4f5f7;
  border-radius: 6px;
}

.trello-modal-model-name {
  font-weight: 600;
  color: #172b4d;
  margin-bottom: 8px;
}

.trello-modal-model-images {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.trello-modal-model-image {
  width: 120px;
  height: 120px;
  border-radius: 4px;
  overflow: hidden;
  background-color: #ffffff;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

.trello-modal-link {
  color: #0079bf;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  word-break: break-all;

  &:hover {
    text-decoration: underline;
  }

  i {
    font-size: 12px;
  }
}

.trello-modal-comment {
  padding: 12px;
  background-color: #f4f5f7;
  border-radius: 6px;
  color: #172b4d;
  line-height: 1.5;
  white-space: pre-wrap;
}
</style>

