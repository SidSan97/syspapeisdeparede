<template>
  <KanbanColumn>
    <KanbanColumnHeader v-if="!isEditing">
      <button
        class="btn btn-subtle w-100 d-flex align-items-center justify-content-start gap-2"
        @click="openEditor"
      >
        <IconPlus :size="16" />
        <span>Adicionar outra coluna</span>
      </button>
    </KanbanColumnHeader>

    <KanbanColumnCard v-else class="kanban-column-card-add">
      <input
        ref="inputEl"
        v-model.trim="localColumnName"
        class="form-control form-control-sm fw-bolder mb-2"
        placeholder="Digite o nome da coluna..."
        @keyup.enter="handleCreate"
        @keyup.esc="closeEditor"
      />

      <div class="d-flex align-items-center gap-1">
        <button class="btn btn-primary" :disabled="isCreateDisabled" @click="handleCreate">
          Adicionar coluna
        </button>

        <button type="button" class="btn-close" aria-label="Fechar" @click="closeEditor" />
      </div>
    </KanbanColumnCard>
  </KanbanColumn>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue';

import KanbanColumn from '@/components/kanban/KanbanColumn.vue';
import KanbanColumnCard from '@/components/kanban/KanbanColumnCard.vue';
import KanbanColumnHeader from '@/components/kanban/KanbanColumnHeader.vue';

import { IconPlus } from '@tabler/icons-vue';

const emit = defineEmits(['create', 'cancel']);

const isEditing = ref(false);
const localColumnName = ref('');
const inputEl = ref(null);

const isCreateDisabled = computed(() => !localColumnName.value.trim());

async function openEditor() {
  isEditing.value = true;

  await nextTick();
  inputEl.value?.focus();
}

function closeEditor() {
  isEditing.value = false;
  localColumnName.value = '';
  emit('cancel');
}

function handleCreate() {
  if (isCreateDisabled.value) return;

  emit('create', localColumnName.value);

  localColumnName.value = '';
  isEditing.value = false;
}
</script>

<style scoped>
.kanban-column-card-add {
  --bs-card-bg: var(--ds-surface-sunken);
}
</style>
