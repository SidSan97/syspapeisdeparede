<template>
  <section class="content d-flex flex-column">
    <div class="container-fluid mt-3">
      <PageHeader title="Produção">
        <template #extra>
          <div class="btn-group">
            <button class="btn btn-secondary" title="Filtrar cartões (F)" @click="isFilterOpen = true">
              <IconFilter2 size="16" />
              Filtrar
              <span v-if="activeFilterCount > 0" class="badge text-bg-primary ms-1">
                {{ activeFilterCount }}
              </span>
            </button>
            <button
              v-if="activeFilterCount > 0"
              class="btn btn-secondary"
              title="Limpar Filtros (X)"
              @click="handleResetFilters"
            >
              Limpar tudo
            </button>
          </div>
        </template>
      </PageHeader>
    </div>

    <BoardFilterOffcanvas
      v-model="isFilterOpen"
      :filters="activeFilters"
      @apply-filters="handleApplyFilters"
      @reset-filters="handleResetFilters"
    />

    <KanbanCanvas class="flex-grow-1">
      <div class="overflow-x-auto h-100 w-100 px-2 py-0.5">
        <draggable
          v-model="columns"
          item-key="id"
          :animation="200"
          handle=".kanban-column-header-drag"
          :force-fallback="true"
          ghost-class="kanban-column-ghost"
          drag-class="kanban-column-dragging"
          class="d-flex gap-4 h-100"
          @end="onColumnReorder"
        >
          <template #item="{ element: column }">
            <ProductionColumn
              :column="column"
              :count="getCardsByColumn(column.id).length || 0"
              :total-metragem="getTotalMetragem(column.id)"
              @update:name="handleUpdateColumn"
              @delete="handleDeleteColumn(column.id)"
              @dragover="drag.allowDrop"
              @drop="drag.handleDrop($event, column.id)"
            >
              <KanbanColumnSkeleton v-if="loadingCards" />

              <ProductionCard
                v-for="card in getCardsByColumn(column.id)"
                :key="card.id"
                :card="card"
                draggable="true"
                @drag-start="drag.handleDragStart($event, card)"
                @click="selectedCard = card"
                class="mb-2"
              />
            </ProductionColumn>
          </template>

          <template #footer>
            <KanbanColumnAdd @create="board.createColumn" />
          </template>
        </draggable>
      </div>
    </KanbanCanvas>

    <ProductCardModal
      :card="selectedCard"
      @close="selectedCard = null"
      @card-updated="handleCardUpdated"
      @member-added="handleCardMemberAdded"
      @member-removed="handleCardMemberRemoved"
      @card-refreshed="handleCardRefreshed"
      @order-cards-refreshed="handleOrderCardsRefreshed"
      @select-card="handleSelectCard"
    />
  </section>
</template>

<script setup>
import { computed, onMounted, ref, shallowRef } from 'vue';
import draggable from 'vuedraggable';
import { IconFilter2 } from '@tabler/icons-vue';
import ProductCardModal from '@/modules/production/components/ProductCardModal.vue';
import ProductionColumn from '@/modules/production/components/ProductionColumn.vue';
import ProductionCard from '@/modules/production/components/ProductionCard.vue';

import { useDialog } from '@/composables/useDialog';

import KanbanColumnSkeleton from '@/components/kanban/KanbanColumnSkeleton.vue';
import KanbanCanvas from '@/components/kanban/KanbanCanvas.vue';
import KanbanColumnAdd from '@/components/kanban/KanbanColumnAdd.vue';
import BoardFilterOffcanvas from '@/components/kanban/BoardFilterOffcanvas.vue';
import { useProductionBoard } from '@/composables/useProductionBoard';
import { useKanbanDrag } from '@/composables/useKanbanDrag';
import { useToast } from '@/composables/useToast';
import { useProductionCards } from '@/composables/useProductionCards';
import PageHeader from '@/components/page/PageHeader.vue';

const dialog = useDialog();
const toast = useToast();
const board = useProductionBoard();
const cards = useProductionCards(board.columns);
const drag = useKanbanDrag(async (card, columnId) => {
  const isFullyProduced = isCardFullyProduced(card);

  if (isFullyProduced) {
    toast.warning('Não é possível mover um card que está 100% produzido.');
    return;
  }

  await cards.moveCard(card.id, columnId);
});

const { columns } = board;
const { loading: loadingCards } = cards;

const selectedCard = shallowRef(null);

const isFilterOpen = ref(false);
const activeFilters = ref({});

const activeFilterCount = computed(() => {
  const filters = activeFilters.value;

  return [
    filters.is_completed !== null && filters.is_completed !== undefined,
    Boolean(filters.delivery_date),
    Boolean(filters.order_number),
    Boolean(filters.quote_name),
    Boolean(filters.unassigned),
    Boolean(filters.assigned_to_me),
    Boolean(filters.member_ids?.length),
  ].filter(Boolean).length;
});

async function handleApplyFilters(filters) {
  activeFilters.value = filters;
  await cards.fetchCards(filters);
}

async function handleResetFilters() {
  activeFilters.value = {};
  await cards.fetchCards();
}

function handleCardUpdated(updatedFields) {
  if (!selectedCard.value) return;
  const card = cards.cards.value.find((c) => c.id === selectedCard.value.id);
  if (card) Object.assign(card, updatedFields);
}

function handleCardMemberAdded(member) {
  if (!selectedCard.value) return;
  const card = cards.cards.value.find((c) => c.id === selectedCard.value.id);
  if (card) card.members = [...(card.members ?? []), member];
}

function handleCardMemberRemoved(memberId) {
  if (!selectedCard.value) return;
  const card = cards.cards.value.find((c) => c.id === selectedCard.value.id);
  if (card) card.members = (card.members ?? []).filter((m) => m.id !== memberId);
}

function handleCardRefreshed(freshCard) {
  if (!freshCard || !selectedCard.value) return;
  Object.assign(selectedCard.value, freshCard);
  const card = cards.cards.value.find((c) => c.id === selectedCard.value.id);
  if (card) Object.assign(card, freshCard);
}

function handleOrderCardsRefreshed(layouts) {
  if (!Array.isArray(layouts)) return;

  layouts.forEach((freshCard) => {
    const card = cards.cards.value.find((c) => Number(c.id) === Number(freshCard.id));
    if (card) {
      Object.assign(card, freshCard);
    }
  });
}

function handleSelectCard(card) {
  if (!card?.id) return;

  const boardCard = cards.cards.value.find((c) => Number(c.id) === Number(card.id));
  selectedCard.value = boardCard ?? card;
}

function getCardsByColumn(columnId) {
  return cards.cards.value.filter((card) => Number(card.column) === Number(columnId));
}

function getTotalMetragem(columnId) {
  const columnCards = getCardsByColumn(columnId);
  const total = columnCards.reduce((sum, card) => {
    const area = card?.wall?.total_area || 0;
    return sum + Number(area);
  }, 0);
  return total.toFixed(2);
}

function isCardFullyProduced(card) {
  return card.production_percentage === 100 || Number(card.production_percentage) === 100;
}

function getFallbackColumnId(deletedId) {
  return columns.value.find((c) => c.id !== deletedId)?.id;
}

async function handleDeleteColumn(columnId) {
  const confirmed = await dialog.confirmDelete({ title: 'Excluir coluna?' });

  if (!confirmed) return;

  const targetColumnId = getFallbackColumnId(columnId);

  if (!targetColumnId) return;

  try {
    await board.deleteColumn(columnId, targetColumnId);

    cards.moveCardsFromDeletedColumn(columnId, targetColumnId);
  } catch (error) {
    console.error(error);
  }
}

async function handleUpdateColumn(payload) {
  await board.updateColumnName(payload.columnId, payload.newName);
}

async function onColumnReorder() {
  const orderedIds = columns.value.map((c) => c.id);
  await board.reorderColumns(orderedIds);
}

onMounted(async () => {
  await board.fetchColumns();
  await cards.fetchCards();

  document.title = 'Produção';
});
</script>

<style lang="scss" scoped>
@import '@/scss/board-layout.scss';

.content {
  grid-area: content;
}

.kanban-column-ghost {
  opacity: 0.4;
}

.kanban-column-dragging {
  transform: rotate(3deg);
  cursor: grabbing;
}
</style>
