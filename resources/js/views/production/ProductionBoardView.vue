<template>
  <section class="content">
    <Page title="Produção" :full-width="true">
      <KanbanCanvas>
        <Kanban>
          <ProductionColumn
            v-for="column in columns"
            :key="column.id"
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
            />
          </ProductionColumn>

          <KanbanColumnAdd @create="board.createColumn" />
        </Kanban>
      </KanbanCanvas>
    </Page>

    <ProductCardModal
      :card="selectedCard"
      @close="selectedCard = null"
      @card-updated="handleCardUpdated"
      @member-added="handleCardMemberAdded"
      @member-removed="handleCardMemberRemoved"
    />
  </section>
</template>

<script setup>
import { onMounted, shallowRef } from 'vue';
import Page from '@/components/page/Page.vue';
import ProductCardModal from '@/modules/production/components/ProductCardModal.vue';
import ProductionColumn from '@/modules/production/components/ProductionColumn.vue';
import ProductionCard from '@/modules/production/components/ProductionCard.vue';

import { useDialog } from '@/composables/useDialog';

import KanbanColumnSkeleton from '@/components/kanban/KanbanColumnSkeleton.vue';
import KanbanCanvas from '@/components/kanban/KanbanCanvas.vue';
import Kanban from '@/components/kanban/Kanban.vue';
import KanbanColumnAdd from '@/components/kanban/KanbanColumnAdd.vue';
import { useProductionBoard } from '@/composables/useProductionBoard';
import { useKanbanDrag } from '@/composables/useKanbanDrag';
import { useToast } from '@/composables/useToast';
import { useProductionCards } from '@/composables/useProductionCards';

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
</style>
