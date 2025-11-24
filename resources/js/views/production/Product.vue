<template>
    <section class="content">
      <Page title="Produção" :full-width="true">
        <div class="production-container">
          <div class="production-board" ref="boardRef">
            <div
              v-for="column in columns"
              :key="column.id"
              class="production-column"
              :data-column-id="column.id"
            >
              <div class="production-column-header">
                <div class="production-column-header-left">
                  <h3 v-if="!editingColumns[column.id]" class="production-column-title mb-0">
                    {{ column.name }}
                  </h3>
                  <div v-else class="production-column-edit d-flex align-items-center gap-2">
                    <input
                      v-model="editingNames[column.id]"
                      @keyup.enter="saveColumnName(column.id)"
                      @keyup.esc="cancelEdit(column.id)"
                      class="form-control form-control-sm"
                      :ref="el => editInputRefs[column.id] = el"
                    />
                    <button
                      @click="saveColumnName(column.id)"
                      class="btn btn-primary btn-sm"
                      :disabled="savingColumn === column.id"
                    >
                      <i class="fa fa-check"></i>
                    </button>
                  </div>
                </div>
                <div class="production-column-header-right d-flex align-items-center gap-2">
                  <span class="badge production-column-count-badge">{{ getCardsByColumn(column.id).length }}</span>
                  <div class="dropdown">
                    <button
                      class="btn btn-sm btn-link text-decoration-none p-1 production-column-menu-btn"
                      type="button"
                      @click.stop="toggleColumnMenu(column.id)"
                      :aria-expanded="openMenuColumn === column.id"
                    >
                      <i class="fa fa-ellipsis-v"></i>
                    </button>
                    <ul
                      v-if="openMenuColumn === column.id"
                      class="dropdown-menu dropdown-menu-end show"
                      @click.stop
                    >
                      <li>
                        <button @click="startEditColumn(column.id)" class="dropdown-item" type="button">
                          <i class="fa fa-edit me-2"></i> Editar
                        </button>
                      </li>
                      <li>
                        <button @click="confirmDeleteColumn(column.id)" class="dropdown-item text-danger" type="button">
                          <i class="fa fa-trash me-2"></i> Excluir
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
              <div
                class="production-column-content"
                @drop="handleDrop($event, column.id)"
                @dragover.prevent
                @dragenter.prevent
              >
                <div
                  v-for="card in getCardsByColumn(column.id)"
                  :key="card.id"
                  class="production-card"
                  :draggable="true"
                  @dragstart="handleDragStart($event, card)"
                  @click="openCardModal(card)"
                >
                  <div v-if="getCoverImage(card)" class="production-card-image">
                    <img :src="getCoverImage(card)" :alt="card.name" />
                  </div>
                  <div class="production-card-footer">
                    <div class="production-card-footer-content">
                      <span class="production-card-footer-text">{{ card.name || 'aaa' }}</span>
                      <div class="production-card-footer-meta">
                        <div class="production-card-deadline">
                          <i class="fa fa-clock-o"></i>
                          <span>{{ card.delivery_date_start }} - {{ card.delivery_date_end }}</span>
                        </div>
                        <div v-if="getCommentsCount(card) > 0" class="production-card-comment-count">
                          <i class="fa fa-comment"></i>
                          <span>{{ getCommentsCount(card) }}</span>
                        </div>
                        <div v-if="getActivitiesCount(card) > 0" class="production-card-activity-count">
                          <i class="fa fa-list"></i>
                          <span>{{ getActivitiesCount(card) }}</span>
                        </div>
                        <div v-if="card.uploaded_files && card.uploaded_files.length > 0" class="production-card-attachment-count">
                          <i class="fa fa-paperclip"></i>
                          <span>{{ card.uploaded_files.length }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Botão Adicionar Nova Coluna -->
            <div class="production-column production-column-add">
              <button
                v-if="!showAddColumnModal"
                class="btn btn-light w-100 d-flex align-items-center justify-content-center gap-2"
                @click="openAddColumnModal"
              >
                <i class="fa fa-plus"></i>
                <span>Adicionar outra lista</span>
              </button>
              <div v-else class="card">
                <div class="card-body p-2">
                  <input
                    v-model="newColumnName"
                    @keyup.enter="createColumn"
                    @keyup.esc="closeAddColumnModal"
                    class="form-control form-control-sm mb-2"
                    placeholder="Digite o nome da lista..."
                    ref="newColumnInputRef"
                  />
                  <div class="d-flex gap-2">
                    <button
                      class="btn btn-primary btn-sm flex-fill"
                      @click="createColumn"
                      :disabled="!newColumnName.trim() || creatingColumn"
                    >
                      {{ creatingColumn ? 'Criando...' : 'Adicionar Lista' }}
                    </button>
                    <button
                      class="btn btn-secondary btn-sm"
                      @click="closeAddColumnModal"
                    >
                      <i class="fa fa-times"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Page>

      <!-- Modal de Detalhes do Card -->
      <ProductCardModal :card="selectedCard" @close="closeCardModal" />
    </section>
  </template>

  <script setup>
  import { ref, onMounted, onUnmounted, nextTick } from 'vue';
  import axios from 'axios';
  import Page from '../../components/page/Page.vue';
  import ProductCardModal from './components/ProductCardModal.vue';

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
  const showAddColumnModal = ref(false);
  const newColumnName = ref('');
  const creatingColumn = ref(false);
  const newColumnInputRef = ref(null);

  function getCardsByColumn(columnId) {
    return cards.value.filter(card => card.column === columnId);
  }

  function isImageFile(file) {
    if (!file) {
      return false;
    }
    const mime = (file.mime || file.mimetype || '').toLowerCase();
    if (mime.startsWith('image/')) {
      return true;
    }
    const name = (file.name || file.original_name || file.file_name || '').toLowerCase();
    return ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.bmp'].some(ext => name.endsWith(ext));
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

  function getCoverImage(card) {
    if (!card) {
      return '';
    }
    if (card.image) {
      return card.image;
    }
    const imageAttachment = card.uploaded_files?.find(file => isImageFile(file));
    return imageAttachment ? getImageUrl(imageAttachment) : '';
  }

  function getCommentsCount(card) {
    if (!card || !Array.isArray(card.comments)) {
      return 0;
    }
    return card.comments.length;
  }

  function getActivitiesCount(card) {
    if (!card) {
      return 0;
    }
    let count = 0;

    // Contador atividades
    if (Array.isArray(card.activities)) {
      count += card.activities.length;
    }

    // Contador histórico
    if (Array.isArray(card.history)) {
      count += card.history.length;
    }

    // Contador comentário do budget se existir
    if (card.budget?.comment_referring_model) {
      count += 1;
    }

    return count;
  }

  async function fetchColumns() {
    try {
      const { data } = await axios.get('v1/production-column-names');
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
      const { data } = await axios.get('v1/budgets/production-layouts');

      const payload = Array.isArray(data?.data) ? data.data : [];

      // Usar o production_column_names_id do banco de dados, ou a primeira coluna se não houver
      const firstColumnId = columns.value.length > 0 ? columns.value[0].id : null;
      cards.value = payload.map(card => ({
        ...card,
        column: card.production_column_names_id || firstColumnId,
      }));
    } catch (error) {
      console.error('Erro ao carregar layouts de produção:', error);
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
      const { data } = await axios.put(`v1/production-column-names/${columnId}`, {
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
      const { data } = await axios.delete(`v1/production-column-names/${columnId}`);

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
                type_page: 'product',
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
            type_page: 'product',
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

  function openAddColumnModal() {
    showAddColumnModal.value = true;
    newColumnName.value = '';
    nextTick(() => {
      if (newColumnInputRef.value) {
        newColumnInputRef.value.focus();
      }
    });
  }

  function closeAddColumnModal() {
    showAddColumnModal.value = false;
    newColumnName.value = '';
  }

  async function createColumn() {
    const name = newColumnName.value?.trim();
    if (!name || creatingColumn.value) {
      return;
    }

    try {
      creatingColumn.value = true;
      const { data } = await axios.post('v1/production-column-names', {
        name: name,
      });

      if (data.success) {
        // Adicionar a nova coluna à lista
        columns.value.push({
          id: data.data.id,
          name: data.data.name,
        });
        closeAddColumnModal();
      }
    } catch (error) {
      console.error('Erro ao criar coluna:', error);
      alert('Erro ao criar a coluna. Tente novamente.');
    } finally {
      creatingColumn.value = false;
    }
  }

  onMounted(async () => {
    await fetchColumns();
    await fetchLayouts();
    document.title = 'Produção';
    document.addEventListener('click', handleClickOutside);
  });

  onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
  });
  </script>

  <style lang="scss" scoped>
  .production-container {
    padding: 1.25rem;
    height: calc(100vh - 120px);
    overflow: hidden;
    background-color: var(--bs-body-bg);
  }

  .production-board {
    display: flex;
    gap: 0.75rem;
    height: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    padding-bottom: 0.625rem;

    &::-webkit-scrollbar {
      height: 12px;
    }

    &::-webkit-scrollbar-track {
      background: var(--bs-border-color);
      border-radius: 6px;
    }

    &::-webkit-scrollbar-thumb {
      background: var(--bs-secondary);
      border-radius: 6px;
      opacity: 0.5;

      &:hover {
        background: var(--bs-secondary);
        opacity: 0.7;
      }
    }
  }

  .production-column {
    flex: 0 0 300px;
    background-color: var(--bs-secondary-bg);
    border-radius: 0.5rem;
    padding: 0.5rem;
    display: flex;
    flex-direction: column;
    max-height: 100%;
    overflow: hidden;
  }

  .production-column-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0.75rem;
    margin-bottom: 0.5rem;
    position: relative;
    overflow: visible;
    z-index: 10;
  }

  .production-column-header-right {
    position: relative;
  }

  .production-column-header-right .dropdown {
    position: relative;
  }

  .production-column-menu-btn {
    color: var(--bs-body-color);
    transition: all 0.15s ease-in-out;

    &:hover {
      color: var(--bs-body-color);
      background-color: var(--bs-secondary-bg);
      opacity: 0.8;
    }
  }

  .production-column-count-badge {
    background-color: var(--bs-secondary-bg);
    color: var(--bs-body-color);
    border: 1px solid var(--bs-border-color);
  }

  .production-column-header-right .dropdown-menu {
    position: absolute;
    top: calc(100% + 0.25rem);
    right: 0;
    z-index: 1050;
    min-width: 150px;
    background-color: var(--bs-dropdown-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 0.375rem;
    box-shadow: var(--bs-box-shadow-lg);
    padding: 0.25rem 0;
    display: block;
  }

  .production-column-header-right .dropdown-item {
    display: block;
    width: 100%;
    padding: 0.5rem 0.75rem;
    clear: both;
    font-weight: 400;
    color: var(--bs-dropdown-color);
    text-align: inherit;
    text-decoration: none;
    white-space: nowrap;
    background-color: transparent;
    border: 0;
    cursor: pointer;
    transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out;

    &:hover {
      background-color: var(--bs-dropdown-link-hover-bg);
      color: var(--bs-dropdown-link-hover-color);
    }

    &:focus {
      background-color: var(--bs-dropdown-link-hover-bg);
      color: var(--bs-dropdown-link-hover-color);
    }

    &.text-danger {
      color: var(--bs-danger);

      &:hover {
        background-color: var(--bs-danger-bg-subtle);
        color: var(--bs-danger);
      }

      &:focus {
        background-color: var(--bs-danger-bg-subtle);
        color: var(--bs-danger);
      }
    }

    i {
      width: 1rem;
      text-align: center;
    }
  }

  .production-column-header-left {
    flex: 1;
    min-width: 0;
  }

  .production-column-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--bs-body-color);
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .production-column-content {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 0 0.25rem;

    &::-webkit-scrollbar {
      width: 8px;
    }

    &::-webkit-scrollbar-track {
      background: transparent;
    }

    &::-webkit-scrollbar-thumb {
      background: var(--bs-secondary);
      border-radius: 4px;
      opacity: 0.5;

      &:hover {
        background: var(--bs-secondary);
        opacity: 0.7;
      }
    }
  }

  .production-card {
    background-color: var(--bs-card-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
    cursor: pointer;
    box-shadow: var(--bs-box-shadow-sm);
    transition: all 0.2s ease;
    user-select: none;
    display: flex;
    flex-direction: column;
    overflow: hidden;

    &:hover {
      box-shadow: var(--bs-box-shadow);
      transform: translateY(-2px);
    }

    &:active {
      cursor: grabbing;
    }
  }

  .production-card-image {
    width: 100%;
    height: 150px;
    overflow: hidden;
    background-color: var(--bs-secondary-bg);
    flex-shrink: 0;

    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  }

  .production-card-footer {
    background-color: var(--bs-dark);
    color: var(--bs-white);
    padding: 0.5rem 0.75rem;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    flex-shrink: 0;
    min-height: 36px;
  }

  .production-card-footer-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    width: 100%;
  }

  .production-card-footer-text {
    font-size: 0.75rem;
    color: var(--bs-white);
    font-weight: 400;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .production-card-footer-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
  }

  .production-card-footer .production-card-deadline {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.6875rem;
    color: rgba(255, 255, 255, 0.7);

    i {
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.6875rem;
    }
  }

  .production-card-comment-count,
  .production-card-activity-count,
  .production-card-attachment-count {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.6875rem;
    color: rgba(255, 255, 255, 0.7);

    i {
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.6875rem;
    }

    span {
      font-weight: 500;
    }
  }

  .production-column-add {
    flex: 0 0 300px;
    display: flex;
    align-items: flex-start;
    padding-top: 0.5rem;
  }

  @media (max-width: 768px) {
    .production-container {
      padding: 0.75rem;
      height: calc(100vh - 100px);
    }

    .production-column {
      flex: 0 0 280px;
    }

    .production-column-add {
      flex: 0 0 280px;
    }
  }
  </style>

