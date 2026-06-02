<template>
  <div v-if="loading || otherCards.length > 0" class="layout-modal-section mb-4">
    <h5 class="layout-modal-section-title d-flex align-items-center gap-2">
      <IconLayoutGrid />
      Outras paredes do mesmo cômodo
    </h5>

    <div v-if="loading" class="text-body-secondary small py-2">Carregando...</div>

    <ul v-else class="list-group list-group-flush border rounded">
      <li v-for="item in otherCards" :key="item.id" class="list-group-item p-0 border-0">
        <button
          type="button"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2 py-2 px-3 border-0 rounded-0"
          @click="emit('select', item)"
        >
          <span class="text-start text-truncate">{{ wallRoomLabel(item) }}</span>
          <IconChevronRight :size="16" class="text-body-secondary flex-shrink-0" />
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed } from 'vue';

import { IconChevronRight, IconLayoutGrid } from '@tabler/icons-vue';

const props = defineProps({
  cards: {
    type: Array,
    default: () => [],
  },
  currentCardId: {
    type: [Number, String],
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['select']);

const otherCards = computed(() => {
  const currentId = props.currentCardId;
  if (currentId == null) {
    return props.cards;
  }

  return props.cards.filter((card) => Number(card.id) !== Number(currentId));
});

function wallRoomLabel(card) {
  if (!card?.name) {
    return `Parede #${card?.id ?? ''}`;
  }

  const parts = String(card.name)
    .split(' - ')
    .map((part) => part.trim())
    .filter(Boolean);

  if (parts.length >= 3) {
    return parts.slice(1).join(' - ');
  }

  return card.name;
}
</script>
