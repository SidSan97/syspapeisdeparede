<template>
    <section class="content">
        <Page title="Layouts" :full-width="true">
            <div class="board-container">
                <div class="board" ref="boardRef">
                    <LayoutColumn
                        v-for="column in columns"
                        :key="column.id"
                        :column="column"
                        :cards-count="getCardsByColumn(column.id).length"
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
                        <LayoutCard
                            v-for="card in getCardsByColumn(column.id)"
                            :key="card.id"
                            :card="card"
                            :cover-image="getCoverImage(card)"
                            :display-name="getCardDisplayName(card)"
                            :comments-count="getCommentsCount(card)"
                            :activities-count="getActivitiesCount(card)"
                            @drag-start="handleDragStart($event, card)"
                            @click="openCardModal(card)"
                        />
                    </LayoutColumn>

                    <LayoutAddColumn
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

        <LayoutCardModal :card="selectedCard" @close="closeCardModal" />
    </section>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import Page from '@/components/page/Page.vue';
import LayoutCardModal from './components/LayoutCardModal.vue';
import LayoutColumn from './components/LayoutColumn.vue';
import LayoutCard from './components/LayoutCard.vue';
import LayoutAddColumn from './components/LayoutAddColumn.vue';
import { useLayoutColumns } from './composables/useLayoutColumns';
import { useLayoutCards } from './composables/useLayoutCards';

const boardRef = ref(null);
const editInputRefs = ref({});
const newColumnInputRef = ref(null);

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
} = useLayoutColumns();

const {
    cards,
    loading: cardsLoading,
    selectedCard,
    draggedCard,
    getCardsByColumn,
    getCoverImage,
    getCommentsCount,
    getActivitiesCount,
    getCardDisplayName,
    fetchLayouts,
    handleDragStart,
    handleDrop,
    moveCardsToColumn,
    openCardModal,
    closeCardModal,
} = useLayoutCards(columns);

const loading = ref(false);

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
        // Mover cards dessa coluna para a primeira coluna disponível
        const cardsToMove = cards.value.filter(card => card.column === columnId);

        // Atualizar no frontend
        cards.value.forEach(card => {
            if (card.column === columnId) {
                card.column = firstColumnId;
            }
        });

        // Salvar no banco de dados
        await moveCardsToColumn(cardsToMove, firstColumnId);
    } else {
        alert('Erro ao excluir a coluna. Tente novamente.');
    }
}

function handleClickOutside() {
    openMenuColumn.value = null;
}

async function openAddColumnModalHandler() {
    openAddColumnModal();
    await nextTick();
    if (newColumnInputRef.value) {
        newColumnInputRef.value.focus();
    }
}

// Lifecycle
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
@import '@/scss/board-layout.scss';

@media (max-width: 768px) {
    .trello-column {
        flex: 0 0 280px;
    }

    .trello-column-add {
        flex: 0 0 280px;
    }
}
</style>
