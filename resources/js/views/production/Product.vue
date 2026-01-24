<template>
    <section class="content">
      <Page title="Produção" :full-width="true">
        <div class="board-container">
            <div class="board" ref="boardRef">
                    <ProductionColumn
                        v-for="column in columns"
                        :key="column.id"
                        :column="column"
                        :cards-count="getCardsByColumn(column.id).length"
                        :total-metragem="getTotalMetragem(column.id)"
                        :is-editing="editingColumns[column.id]"
                        :editing-name="editingNames[column.id]"
                        :saving="savingColumn === column.id"
                        :menu-open="openMenuColumn === column.id"
                        :input-ref="editInputRefs[column.id]"
                        @update:editing-name="editingNames[column.id] = $event"
                        @save="handleSaveColumn(column.id)"
                        @cancel="cancelEdit(column.id)"
                        @toggle-menu="toggleColumnMenu(column.id)"
                        @edit="handleStartEditColumn(column.id)"
                        @delete="confirmDeleteColumn(column.id)"
                        @drop="handleDrop($event, column.id)"
                    >
                        <!-- Placeholders de carregamento -->
                        <template v-if="cardsLoading">
                            <div
                                v-for="i in 3"
                                :key="`placeholder-${column.id}-${i}`"
                                class="card-placeholder mb-2"
                            >
                                <div class="placeholder-glow">
                                    <div class="placeholder placeholder-lg w-100 mb-2" style="height: 120px; border-radius: 0.375rem;"></div>
                                </div>
                            </div>
                        </template>

                        <!-- Cards reais -->
                        <ProductionCard
                            v-for="card in getCardsByColumn(column.id)"
                            :key="card.id"
                            :card="card"
                            :cover-image="getCoverImage(card)"
                            :display-name="getCardDisplayName(card)"
                            :comments-count="getCommentsCount(card)"
                            :activities-count="getActivitiesCount(card)"
                            :formatted-production-date="formatProductionDate(card.production_date)"
                            :production-timer-text="getProductionTimerTextForCard(card)"
                            :production-timer-class="getProductionTimerClassForCard(card)"
                            :is-fully-produced="isCardFullyProduced(card)"
                            @drag-start="handleDragStart($event, card)"
                            @click="openCardModal(card)"
                        />
                    </ProductionColumn>

                    <ProductionAddColumn
                        :show-modal="showAddColumnModal"
                        :column-name="newColumnName"
                        :creating="creatingColumn"
                        :input-ref="newColumnInputRef"
                        @open="openAddColumnModal"
                        @close="closeAddColumnModal"
                        @create="handleCreateColumn"
                        @update:column-name="newColumnName = $event"
                    />
            </div>
        </div>
      </Page>

      <ProductCardModal :card="selectedCard" @close="closeCardModal" />
    </section>
  </template>

  <script setup>
    import { ref, onMounted, onUnmounted, nextTick } from 'vue';
    import Page from '@/components/page/Page.vue';
    import ProductCardModal from '@/modules/production/components/ProductCardModal.vue';
    import ProductionColumn from '@/modules/production/components/ProductionColumn.vue';
    import ProductionCard from '@/modules/production/components/ProductionCard.vue';
    import ProductionAddColumn from '@/modules/production/components/ProductionAddColumn.vue';
    import { useProductionColumns } from '@/modules/production/composables/useProductionColumns';
    import { useProductionCards } from '@/modules/production/composables/useProductionCards';

    const boardRef = ref(null);
    const editInputRefs = ref({});
    const newColumnInputRef = ref(null);
    let timerInterval = null;

// Composables
const {
    columns,
    loading: columnsLoading,
    openMenuColumn,
    editingColumns,
    editingNames,
    savingColumn,
    showAddColumnModal,
    newColumnName,
    creatingColumn,
    fetchColumns,
    toggleColumnMenu,
    startEditColumn,
    cancelEdit,
    saveColumnName,
    deleteColumn,
    createColumn,
    openAddColumnModal,
    closeAddColumnModal,
} = useProductionColumns();

const {
    cards,
    loading: cardsLoading,
    selectedCard,
    draggedCard,
    getCardsByColumn,
    getTotalMetragem,
    getCoverImage,
    getCommentsCount,
    getActivitiesCount,
    getCardDisplayName,
    formatProductionDate,
    getProductionTimerTextForCard,
    getProductionTimerClassForCard,
    isCardFullyProduced,
    fetchLayouts,
    handleDragStart,
    handleDrop,
    moveCardsToColumn,
    openCardModal,
    closeCardModal,
    updateCurrentTime,
} = useProductionCards(columns);

// Handlers
async function handleStartEditColumn(columnId) {
    const colId = startEditColumn(columnId);
    if (colId) {
        await nextTick();
        if (editInputRefs.value[colId]) {
            editInputRefs.value[colId].focus();
            editInputRefs.value[colId].select();
        }
    }
}

async function handleSaveColumn(columnId) {
    const success = await saveColumnName(columnId);
    if (!success) {
        alert('Erro ao salvar o nome da coluna. Tente novamente.');
    }
}

async function handleCreateColumn() {
    const newColumn = await createColumn();
    if (newColumn) {
        closeAddColumnModal();
      await nextTick();
        if (newColumnInputRef.value) {
            newColumnInputRef.value.focus();
        }
    } else {
        alert('Erro ao criar a coluna. Tente novamente.');
    }
  }

  function confirmDeleteColumn(columnId) {
    if (confirm('Tem certeza que deseja excluir esta coluna?')) {
        handleDeleteColumn(columnId);
    }
    openMenuColumn.value = null;
  }

async function handleDeleteColumn(columnId) {
    const firstColumnId = await deleteColumn(columnId);

        if (firstColumnId) {
          const cardsToMove = cards.value.filter(card => card.column === columnId);

          cards.value.forEach(card => {
            if (card.column === columnId) {
              card.column = firstColumnId;
            }
          });

        await moveCardsToColumn(cardsToMove, firstColumnId);
    } else {
      alert('Erro ao excluir a coluna. Tente novamente.');
    }
  }

  function handleClickOutside() {
    openMenuColumn.value = null;
  }

// Lifecycle
  onMounted(async () => {
    await fetchColumns();
    await fetchLayouts();
    document.title = 'Produção';
    document.addEventListener('click', handleClickOutside);

    // Atualizar o timer a cada minuto
    timerInterval = setInterval(() => {
        updateCurrentTime();
    }, 60000);
  });

  onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    if (timerInterval) {
      clearInterval(timerInterval);
    }
  });
  </script>

  <style lang="scss" scoped>
    @import '@/scss/board-layout.scss';
  </style>
