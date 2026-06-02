<template>
  <section class="content">
    <div class="container-fluid mt-3">
      <PageHeader title="Layouts" />
    </div>

    <KanbanCanvas>
      <div class="overflow-x-auto h-100 w-100 px-2 py-0.5">
        <draggable
          v-model="columns"
          item-key="id"
          :animation="200"
          handle=".kanban-column-drag-handle"
          ghost-class="kanban-column-ghost"
          class="d-flex gap-4 h-100"
          @end="onColumnReorder"
        >
          <template #item="{ element: column }">
            <LayoutBoardColumn
              :column="column"
              :count="getCardsByColumn(column.id).length"
              @update:name="handleUpdateColumn"
              @delete="handleDeleteColumn(column.id)"
              @dragover="drag.allowDrop"
              @drop="drag.handleDrop($event, column.id)"
            >
              <KanbanColumnSkeleton v-if="loadingCards" />

              <LayoutCard
                v-for="card in getCardsByColumn(column.id)"
                :key="card.id"
                :card="card"
                draggable="true"
                @drag-start="drag.handleDragStart($event, card)"
                @click="selectedCard = card"
              />
            </LayoutBoardColumn>
          </template>

          <template #footer>
            <KanbanColumnAdd @create="board.createColumn" />
          </template>
        </draggable>
      </div>
    </KanbanCanvas>

    <LayoutCardModal
      :card="selectedCard"
      @close="selectedCard = null"
      @member-added="handleCardMemberAdded"
      @member-removed="handleCardMemberRemoved"
      @activity-updated="handleActivityUpdated"
      @card-refreshed="handleCardRefreshed"
    />
  </section>
</template>

<script setup>
import { onMounted, shallowRef } from 'vue';
import draggable from 'vuedraggable';

import { useDialog } from '@/composables/useDialog';
import { useLayoutBoard } from '@/composables/useLayoutBoard';
import { useLayoutCards } from '@/composables/useLayoutCards';
import { useKanbanDrag } from '@/composables/useKanbanDrag';

import PageHeader from '@/components/page/PageHeader.vue';
import LayoutBoardColumn from '@/modules/layouts/components/LayoutBoardColumn.vue';
import LayoutCard from '@/modules/layouts/components/LayoutCard.vue';
import LayoutCardModal from '@/modules/layouts/components/LayoutCardModal.vue';
import KanbanCanvas from '@/components/kanban/KanbanCanvas.vue';
import KanbanColumnSkeleton from '@/components/kanban/KanbanColumnSkeleton.vue';
import KanbanColumnAdd from '@/components/kanban/KanbanColumnAdd.vue';

const dialog = useDialog();
const board = useLayoutBoard();
const cards = useLayoutCards(board.columns);
const drag = useKanbanDrag(async (card, columnId) => {
  await cards.moveCard(card.id, columnId);
});

const { columns } = board;
const { loading: loadingCards } = cards;

const selectedCard = shallowRef(null);

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

function handleActivityUpdated(payload) {
  if (!payload || !selectedCard.value) return;
  Object.assign(selectedCard.value, payload);
  const card = cards.cards.value.find((c) => c.id === selectedCard.value.id);
  if (card) Object.assign(card, payload);
}

function handleCardRefreshed(freshCard) {
  if (!freshCard || !selectedCard.value) return;
  Object.assign(selectedCard.value, freshCard);
  const card = cards.cards.value.find((c) => c.id === selectedCard.value.id);
  if (card) Object.assign(card, freshCard);
}

function getCardsByColumn(columnId) {
  return cards.cards.value.filter((card) => Number(card.column) === Number(columnId));
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

  document.title = 'Layouts';
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
</style>
