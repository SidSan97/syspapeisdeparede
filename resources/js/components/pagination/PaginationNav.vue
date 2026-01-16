<template>
  <nav v-if="data && data.last_page > 1" aria-label="Paginação">
    <ul class="pagination pagination-sm justify-content-center mb-0">
      <!-- Primeira página e anterior -->
      <li class="page-item" :class="{ disabled: data.current_page === 1 }">
        <button
          class="page-link"
          type="button"
          :disabled="data.current_page === 1"
          @click="changePage(1)"
          aria-label="Primeira página"
        >
          <i class="fa fa-angle-double-left"></i>
        </button>
      </li>

      <li class="page-item" :class="{ disabled: data.current_page === 1 }">
        <button
          class="page-link"
          type="button"
          :disabled="data.current_page === 1"
          @click="changePage(data.current_page - 1)"
          aria-label="Página anterior"
        >
          <i class="fa fa-angle-left"></i>
        </button>
      </li>

      <!-- Números das páginas (limitado) -->
      <template v-for="page in visiblePages" :key="page">
        <li class="page-item" :class="{ active: page === data.current_page }">
          <button
            class="page-link"
            type="button"
            @click="changePage(page)"
          >
            {{ page }}
          </button>
        </li>
      </template>

      <!-- Próxima e última página -->
      <li class="page-item" :class="{ disabled: data.current_page === data.last_page }">
        <button
          class="page-link"
          type="button"
          :disabled="data.current_page === data.last_page"
          @click="changePage(data.current_page + 1)"
          aria-label="Próxima página"
        >
          <i class="fa fa-angle-right"></i>
        </button>
      </li>

      <li class="page-item" :class="{ disabled: data.current_page === data.last_page }">
        <button
          class="page-link"
          type="button"
          :disabled="data.current_page === data.last_page"
          @click="changePage(data.last_page)"
          aria-label="Última página"
        >
          <i class="fa fa-angle-double-right"></i>
        </button>
      </li>
    </ul>
  </nav>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  data: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['pagination-change-page']);

// Quantidade máxima de páginas visíveis
const MAX_VISIBLE_PAGES = 5;

const visiblePages = computed(() => {
  if (!props.data || !props.data.last_page) {
    return [];
  }

  const current = props.data.current_page;
  const last = props.data.last_page;
  const maxVisible = MAX_VISIBLE_PAGES;

  // Se o total de páginas for menor ou igual ao máximo, mostrar todas
  if (last <= maxVisible) {
    return Array.from({ length: last }, (_, i) => i + 1);
  }

  let start = Math.max(1, current - Math.floor(maxVisible / 2));
  let end = Math.min(last, start + maxVisible - 1);

  // Ajustar início se estiver próximo do final
  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1);
  }

  return Array.from({ length: end - start + 1 }, (_, i) => start + i);
});

function changePage(page) {
  if (page < 1 || page > props.data.last_page || page === props.data.current_page) {
    return;
  }

  emit('pagination-change-page', page);
}
</script>

<style scoped>
.pagination {
  flex-wrap: nowrap;
  overflow-x: auto;
  overflow-y: hidden;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: thin;
  scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
}

.pagination::-webkit-scrollbar {
  height: 6px;
}

.pagination::-webkit-scrollbar-track {
  background: transparent;
}

.pagination::-webkit-scrollbar-thumb {
  background-color: rgba(0, 0, 0, 0.2);
  border-radius: 3px;
}

.pagination::-webkit-scrollbar-thumb:hover {
  background-color: rgba(0, 0, 0, 0.3);
}

.page-link {
  min-width: 38px;
  text-align: center;
  user-select: none;
}

.page-item.disabled .page-link {
  cursor: not-allowed;
  opacity: 0.5;
}

.page-item.active .page-link {
  font-weight: 600;
}
</style>
