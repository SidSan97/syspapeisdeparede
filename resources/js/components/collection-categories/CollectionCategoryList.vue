<template>
  <div>
    <div v-if="isLoadingCollections" class="text-center text-muted py-5">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Carregando...</span>
      </div>
    </div>

    <EmptyState heading="Sem categorias!" :icon="IconFile" v-else-if="!categories.length">
      Nenhuma categoria encontrada.

      <template #actions>
        <button type="button" class="btn btn-primary" @click="$emit('open-modal', null, null)">
          Criar primeira categoria
        </button>
      </template>
    </EmptyState>

    <ul class="list-group" v-else>
      <template v-for="(cat, index) in categories" :key="cat.id">
        <CollectionCategoryListItem
          :category="cat"
          @open-modal="(item, parent) => $emit('open-modal', item, parent)"
          @delete="(item, parent) => $emit('delete', item, parent)"
        />
        <hr v-if="index < categories.length - 1" />
      </template>
    </ul>
  </div>
</template>

<script setup>
import EmptyState from '@/components/empty-state/EmptyState.vue';
import CollectionCategoryListItem from './CollectionCategoryListItem.vue';
import { IconFile } from '@tabler/icons-vue';

defineProps({
  categories: { type: Array, required: true },
  isLoadingCollections: { type: Boolean, default: false },
});

defineEmits(['open-modal', 'delete']);
</script>
