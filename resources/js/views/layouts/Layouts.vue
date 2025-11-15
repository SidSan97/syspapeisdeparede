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
              <div class="trello-column-header-left">
                <h3 v-if="!editingColumns[column.id]" class="trello-column-title">
                  {{ column.name }}
                </h3>
                <div v-else class="trello-column-edit">
                  <input
                    v-model="editingNames[column.id]"
                    @keyup.enter="saveColumnName(column.id)"
                    @keyup.esc="cancelEdit(column.id)"
                    class="trello-column-input"
                    :ref="el => editInputRefs[column.id] = el"
                  />
                  <button
                    @click="saveColumnName(column.id)"
                    class="trello-column-save-btn"
                    :disabled="savingColumn === column.id"
                  >
                    <i class="fa fa-check"></i>
                  </button>
                </div>
              </div>
              <div class="trello-column-header-right">
                <span class="trello-column-count">{{ getCardsByColumn(column.id).length }}</span>
                <div class="trello-column-menu">
                  <button
                    class="trello-column-menu-btn"
                    @click.stop="toggleColumnMenu(column.id)"
                  >
                    <i class="fa fa-ellipsis-v"></i>
                  </button>
                  <div
                    v-if="openMenuColumn === column.id"
                    class="trello-column-menu-dropdown"
                    @click.stop
                  >
                    <button @click="startEditColumn(column.id)" class="trello-column-menu-item">
                      <i class="fa fa-edit"></i> Editar
                    </button>
                    <button @click="confirmDeleteColumn(column.id)" class="trello-column-menu-item danger">
                      <i class="fa fa-trash"></i> Excluir
                    </button>
                  </div>
                </div>
              </div>
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
    <LayoutCardModal :card="selectedCard" @close="closeCardModal" />
  </section>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';
import Page from '../../components/page/Page.vue';
import LayoutCardModal from './components/LayoutCardModal.vue';

const cards = ref([]);
const columns = ref([]);
const loading = ref(false);
const selectedCard = ref(null);
const draggedCard = ref(null);
const boardRef = ref(null);
const openMenuColumn = ref(null);
const editingColumns = ref({});
const editingNames = ref({});
const savingColumn = ref(null);
const editInputRefs = ref({});

function getCardsByColumn(columnId) {
  return cards.value.filter(card => card.column === columnId);
}

async function fetchColumns() {
  try {
    const { data } = await axios.get('v1/layout-column-names');
    const payload = Array.isArray(data?.data) ? data.data : [];

    // Mapear para o formato esperado, usando o ID como identificador único
    columns.value = payload.map(col => ({
      id: col.id,
      name: col.name,
    }));
  } catch (error) {
    console.error('Erro ao carregar colunas:', error);
    columns.value = [];
  }
}

async function fetchLayouts() {
  try {
    loading.value = true;
    const { data } = await axios.get('v1/budgets/layouts');

    const payload = Array.isArray(data?.data) ? data.data : [];

    // Usar o layout_column_names_id do banco de dados, ou a primeira coluna se não houver
    const firstColumnId = columns.value.length > 0 ? columns.value[0].id : null;
    cards.value = payload.map(card => ({
      ...card,
      column: card.layout_column_names_id || firstColumnId,
    }));
  } catch (error) {
    console.error('Erro ao carregar layouts:', error);
    cards.value = [];
  } finally {
    loading.value = false;
  }
}

function toggleColumnMenu(columnId) {
  openMenuColumn.value = openMenuColumn.value === columnId ? null : columnId;
}

async function startEditColumn(columnId) {
  const column = columns.value.find(c => c.id === columnId);
  if (column) {
    editingColumns.value[columnId] = true;
    editingNames.value[columnId] = column.name;
    openMenuColumn.value = null;

    // Focar no input após renderização
    await nextTick();
    if (editInputRefs.value[columnId]) {
      editInputRefs.value[columnId].focus();
      editInputRefs.value[columnId].select();
    }
  }
}

function cancelEdit(columnId) {
  editingColumns.value[columnId] = false;
  delete editingNames.value[columnId];
}

async function saveColumnName(columnId) {
  const newName = editingNames.value[columnId]?.trim();
  if (!newName) {
    return;
  }

  try {
    savingColumn.value = columnId;
    const { data } = await axios.put(`v1/layout-column-names/${columnId}`, {
      name: newName,
    });

    if (data.success) {
      const columnIndex = columns.value.findIndex(c => c.id === columnId);
      if (columnIndex !== -1) {
        columns.value[columnIndex].name = newName;
      }
      editingColumns.value[columnId] = false;
      delete editingNames.value[columnId];
    }
  } catch (error) {
    console.error('Erro ao salvar coluna:', error);
    alert('Erro ao salvar o nome da coluna. Tente novamente.');
  } finally {
    savingColumn.value = null;
  }
}

function confirmDeleteColumn(columnId) {
  if (confirm('Tem certeza que deseja excluir esta coluna?')) {
    deleteColumn(columnId);
  }
  openMenuColumn.value = null;
}

async function deleteColumn(columnId) {
  try {
    const { data } = await axios.delete(`v1/layout-column-names/${columnId}`);

    if (data.success) {
      // Remover a coluna da lista
      columns.value = columns.value.filter(c => c.id !== columnId);

      // Mover cards dessa coluna para a primeira coluna disponível e salvar no banco
      const firstColumnId = columns.value.length > 0 ? columns.value[0].id : null;
      if (firstColumnId) {
        const cardsToMove = cards.value.filter(card => card.column === columnId);

        // Atualizar no frontend
        cards.value.forEach(card => {
          if (card.column === columnId) {
            card.column = firstColumnId;
          }
        });

        // Salvar no banco de dados
        for (const card of cardsToMove) {
          try {
            await axios.post('v1/budgets/layouts/update-column', {
              order_budget_id: card.id,
              layout_column_names_id: firstColumnId,
            });
          } catch (error) {
            console.error(`Erro ao mover card ${card.id}:`, error);
          }
        }
      }
    }
  } catch (error) {
    console.error('Erro ao excluir coluna:', error);
    alert('Erro ao excluir a coluna. Tente novamente.');
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

async function handleDrop(event, columnId) {
  event.preventDefault();
  if (draggedCard.value) {
    const cardIndex = cards.value.findIndex(c => c.id === draggedCard.value.id);
    if (cardIndex !== -1) {
      const oldColumnId = cards.value[cardIndex].column;
      cards.value[cardIndex].column = columnId;

      // Salvar a mudança no banco de dados
      try {
        await axios.post('v1/budgets/layouts/update-column', {
          order_budget_id: draggedCard.value.id,
          layout_column_names_id: columnId,
        });
      } catch (error) {
        console.error('Erro ao atualizar coluna do card:', error);
        // Reverter a mudança em caso de erro
        cards.value[cardIndex].column = oldColumnId;
        alert('Erro ao mover o card. Tente novamente.');
      }
    }
    draggedCard.value = null;
  }
}

function handleClickOutside() {
  openMenuColumn.value = null;
}

onMounted(async () => {
  await fetchColumns();
  await fetchLayouts();
  document.title = 'Layouts';
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
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
  position: relative;
}

.trello-column-header-left {
  flex: 1;
  min-width: 0;
}

.trello-column-header-right {
  display: flex;
  align-items: center;
  gap: 8px;
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

.trello-column-menu {
  position: relative;
}

.trello-column-menu-btn {
  background: none;
  border: none;
  color: #5e6c84;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: background-color 0.2s ease;
  font-size: 14px;

  &:hover {
    background-color: #dfe1e6;
    color: #172b4d;
  }
}

.trello-column-menu-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 4px;
  background-color: #ffffff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(9, 30, 66, 0.15);
  min-width: 150px;
  z-index: 1000;
  overflow: hidden;
}

.trello-column-menu-item {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 8px 12px;
  background: none;
  border: none;
  text-align: left;
  cursor: pointer;
  color: #172b4d;
  font-size: 14px;
  transition: background-color 0.2s ease;

  &:hover {
    background-color: #f4f5f7;
  }

  &.danger {
    color: #d32f2f;

    &:hover {
      background-color: #ffebee;
    }
  }

  i {
    width: 16px;
    text-align: center;
  }
}

.trello-column-edit {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
}

.trello-column-input {
  flex: 1;
  padding: 4px 8px;
  border: 2px solid #0079bf;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 600;
  color: #172b4d;
  outline: none;

  &:focus {
    border-color: #0052cc;
  }
}

.trello-column-save-btn {
  background-color: #0079bf;
  color: #ffffff;
  border: none;
  border-radius: 4px;
  padding: 4px 8px;
  cursor: pointer;
  transition: background-color 0.2s ease;

  &:hover:not(:disabled) {
    background-color: #0052cc;
  }

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
}

</style>

