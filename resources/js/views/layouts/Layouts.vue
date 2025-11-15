<template>
  <section class="content">
    <Page title="Layouts" :full-width="true">
      <div class="trello-container">
        <div class="trello-board" ref="boardRef">
          <div
            v-for="column in columns"
            :key="column.id"
            class="trello-column"
            :data-column-id="column.id"
          >
            <div class="trello-column-header">
              <h3 class="trello-column-title">{{ column.title }}</h3>
              <span class="trello-column-count">{{ getCardsByColumn(column.id).length }}</span>
            </div>
            <div
              class="trello-column-content"
              @drop="handleDrop($event, column.id)"
              @dragover.prevent
              @dragenter.prevent
            >
              <div
                v-for="card in getCardsByColumn(column.id)"
                :key="card.id"
                class="trello-card"
                :draggable="true"
                @dragstart="handleDragStart($event, card)"
                @click="openCardModal(card)"
              >
                <div v-if="card.image" class="trello-card-image">
                  <img :src="card.image" :alt="card.name" />
                </div>
                <div class="trello-card-content">
                  <div class="trello-card-title">{{ card.name }}</div>
                  <div class="trello-card-deadline">
                    <i class="fa fa-clock-o"></i>
                    <span>{{ card.delivery_date_start }} - {{ card.delivery_date_end }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Page>

    <!-- Modal de Detalhes do Card -->
    <Teleport v-if="selectedCard" to="body">
      <div class="trello-modal-overlay" @click="closeCardModal">
        <div class="trello-modal" @click.stop>
          <div class="trello-modal-header">
            <h2 class="trello-modal-title">{{ selectedCard.name }}</h2>
            <button class="trello-modal-close" @click="closeCardModal">
              <i class="fa fa-times"></i>
            </button>
          </div>
          <div class="trello-modal-body">
            <div v-if="selectedCard.image" class="trello-modal-image">
              <img :src="selectedCard.image" :alt="selectedCard.name" />
            </div>

            <div class="trello-modal-section">
              <h3 class="trello-modal-section-title">
                <i class="fa fa-calendar"></i> Prazo
              </h3>
              <div class="trello-modal-info">
                <span>{{ selectedCard.delivery_date_start }} - {{ selectedCard.delivery_date_end }}</span>
                <span class="trello-modal-info-label">{{ selectedCard.delivery_time }} dias</span>
              </div>
            </div>

            <div class="trello-modal-section">
              <h3 class="trello-modal-section-title">
                <i class="fa fa-money"></i> Valor do Orçamento
              </h3>
              <div class="trello-modal-info">
                <span class="trello-modal-amount">{{ formatCurrency(selectedCard.total_amount) }}</span>
              </div>
            </div>

            <div v-if="selectedCard.budget" class="trello-modal-section">
              <h3 class="trello-modal-section-title">
                <i class="fa fa-cube"></i> Detalhes do Modelo
              </h3>
              <div v-if="getCollectionModels(selectedCard.budget).length > 0" class="trello-modal-models">
                <div
                  v-for="(model, index) in getCollectionModels(selectedCard.budget)"
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

            <div v-if="selectedCard.budget" class="trello-modal-section">
              <h3 class="trello-modal-section-title">
                <i class="fa fa-link"></i> Links
              </h3>
              <div v-if="selectedCard.budget.link_referring_model" class="trello-modal-info">
                <a
                  :href="selectedCard.budget.link_referring_model"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="trello-modal-link"
                >
                  <i class="fa fa-external-link"></i>
                  {{ selectedCard.budget.link_referring_model }}
                </a>
              </div>
              <div v-else class="trello-modal-info text-muted">
                Nenhum link disponível
              </div>
            </div>

            <div v-if="selectedCard.budget" class="trello-modal-section">
              <h3 class="trello-modal-section-title">
                <i class="fa fa-comment"></i> Comentários
              </h3>
              <div v-if="selectedCard.budget.comment_referring_model" class="trello-modal-comment">
                {{ selectedCard.budget.comment_referring_model }}
              </div>
              <div v-else class="trello-modal-info text-muted">
                Nenhum comentário disponível
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </section>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Page from '../../components/page/Page.vue';

const cards = ref([]);
const loading = ref(false);
const selectedCard = ref(null);
const draggedCard = ref(null);
const boardRef = ref(null);

const columns = [
  { id: 'desenhista', title: 'Desenhista' },
  { id: 'versao01', title: 'Versão 01' },
  { id: 'revisao01', title: 'Revisão 01' },
  { id: 'revisao02', title: 'Revisão 02' },
  { id: 'final', title: 'Final' },
];

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

function getCardsByColumn(columnId) {
  return cards.value.filter(card => card.column === columnId);
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

async function fetchLayouts() {
  try {
    loading.value = true;
    const { data } = await axios.get('v1/budgets/layouts');

    const payload = Array.isArray(data?.data) ? data.data : [];

    // Inicializar todos os cards na coluna "Desenhista"
    cards.value = payload.map(card => ({
      ...card,
      column: 'desenhista', // Todos começam na coluna Desenhista
    }));
  } catch (error) {
    console.error('Erro ao carregar layouts:', error);
    cards.value = [];
  } finally {
    loading.value = false;
  }
}

function openCardModal(card) {
  selectedCard.value = card;
}

function closeCardModal() {
  selectedCard.value = null;
}

function handleDragStart(event, card) {
  draggedCard.value = card;
  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData('text/html', event.target.outerHTML);
}

function handleDrop(event, columnId) {
  event.preventDefault();
  if (draggedCard.value) {
    const cardIndex = cards.value.findIndex(c => c.id === draggedCard.value.id);
    if (cardIndex !== -1) {
      cards.value[cardIndex].column = columnId;
      // Aqui você pode adicionar uma chamada à API para salvar a mudança de coluna
    }
    draggedCard.value = null;
  }
}

onMounted(() => {
  fetchLayouts();
  document.title = 'Layouts';
});
</script>

<style lang="scss" scoped>
.trello-container {
  padding: 20px;
  height: calc(100vh - 120px);
  overflow: hidden;
  background-color: #f4f5f7;
}

.trello-board {
  display: flex;
  gap: 12px;
  height: 100%;
  overflow-x: auto;
  overflow-y: hidden;
  padding-bottom: 10px;

  &::-webkit-scrollbar {
    height: 12px;
  }

  &::-webkit-scrollbar-track {
    background: #e4e6eb;
    border-radius: 6px;
  }

  &::-webkit-scrollbar-thumb {
    background: #c1c7d0;
    border-radius: 6px;

    &:hover {
      background: #a5adba;
    }
  }
}

.trello-column {
  flex: 0 0 300px;
  background-color: #ebecf0;
  border-radius: 8px;
  padding: 8px;
  display: flex;
  flex-direction: column;
  max-height: 100%;
  overflow: hidden;
}

.trello-column-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px;
  margin-bottom: 8px;
}

.trello-column-title {
  font-size: 14px;
  font-weight: 600;
  color: #172b4d;
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.trello-column-count {
  background-color: #dfe1e6;
  color: #5e6c84;
  border-radius: 12px;
  padding: 2px 8px;
  font-size: 12px;
  font-weight: 600;
}

.trello-column-content {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 0 4px;

  &::-webkit-scrollbar {
    width: 8px;
  }

  &::-webkit-scrollbar-track {
    background: transparent;
  }

  &::-webkit-scrollbar-thumb {
    background: #c1c7d0;
    border-radius: 4px;

    &:hover {
      background: #a5adba;
    }
  }
}

.trello-card {
  background-color: #ffffff;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 8px;
  cursor: pointer;
  box-shadow: 0 1px 0 rgba(9, 30, 66, 0.25);
  transition: all 0.2s ease;
  user-select: none;

  &:hover {
    background-color: #f4f5f7;
    box-shadow: 0 2px 4px rgba(9, 30, 66, 0.15);
  }

  &:active {
    cursor: grabbing;
  }
}

.trello-card-image {
  width: 100%;
  height: 150px;
  margin-bottom: 8px;
  border-radius: 4px;
  overflow: hidden;
  background-color: #f4f5f7;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

.trello-card-content {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.trello-card-title {
  font-size: 14px;
  font-weight: 500;
  color: #172b4d;
  line-height: 1.4;
}

.trello-card-deadline {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #5e6c84;

  i {
    color: #5e6c84;
  }
}

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

