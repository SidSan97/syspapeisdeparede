<template>
  <KanbanColumn ref="rootRef">
    <KanbanColumnHeader :count="count">
      <template #default>
        <button
          type="button"
          class="btn btn-sm btn-subtle btn-icon kanban-column-drag-handle"
          aria-label="Arrastar coluna"
        >
          <IconGripVertical :size="16" />
        </button>
        <template v-if="!isEditing">
          <button
            type="button"
            class="btn btn-sm btn-subtle w-100 text-start"
            @click="startEditing"
          >
            {{ column.name }}
          </button>
          <small class="text-muted ms-1">Total de metros: {{ totalMetragem }}</small>
        </template>
        <input
          v-else
          ref="inputRef"
          v-model="editingName"
          class="form-control form-control-sm"
          @keydown="handleKeydown"
          aria-label="Nome da coluna"
        />
      </template>

      <template #actions>
        <BaseDropdown align="end">
          <template #trigger="{ open, toggle }">
            <button
              type="button"
              class="btn btn-sm btn-subtle btn-icon"
              :class="{ show: open }"
              @click.stop="toggle"
              :aria-expanded="open"
              aria-label="Abrir menu da coluna"
            >
              <IconDots :size="18" />
            </button>
          </template>
          <li><button class="dropdown-item" @click="startEditing">Editar</button></li>
          <li><button class="dropdown-item" @click="emit('delete')">Excluir</button></li>
        </BaseDropdown>
      </template>
    </KanbanColumnHeader>

    <KanbanColumnCards @drop="emit('drop', $event)">
      <slot />
    </KanbanColumnCards>
  </KanbanColumn>
</template>

<script setup>
import { ref, nextTick } from 'vue';
import { onClickOutside } from '@vueuse/core';
import { useFormatting } from '@/composables/useFormatting';
import BaseDropdown from '@/components/common/BaseDropdown.vue';
import KanbanColumn from '@/components/kanban/KanbanColumn.vue';
import KanbanColumnCards from '@/components/kanban/KanbanColumnCards.vue';
import KanbanColumnHeader from '@/components/kanban/KanbanColumnHeader.vue';

// Icons
import { IconDots, IconGripVertical } from '@tabler/icons-vue';

const props = defineProps({
  column: { type: Object, required: true },
  count: { type: Number, default: 0 },
  totalMetragem: { type: Number, default: 0 },
});

const emit = defineEmits({
  delete: null,
  drop: (e) => e instanceof DragEvent,
  'update:name': (payload) => payload?.columnId,
});

const { formatNumber } = useFormatting();

const isEditing = ref(false);
const editingName = ref('');
const inputRef = ref(null);
const rootRef = ref(null);

async function startEditing() {
  editingName.value = props.column.name;
  isEditing.value = true;
  await nextTick();
  inputRef.value?.focus();
  inputRef.value?.select();
}

function stopEditing() {
  isEditing.value = false;
}

function save() {
  const newName = editingName.value.trim();
  if (newName && newName !== props.column.name) {
    emit('update:name', {
      columnId: props.column.id,
      oldName: props.column.name,
      newName,
    });
  }
  stopEditing();
}

function cancel() {
  stopEditing();
}

function handleKeydown(e) {
  if (e.key === 'Enter') save();
  if (e.key === 'Escape') cancel();
}

onClickOutside(rootRef, () => {
  if (isEditing.value) cancel();
});
</script>

<style scoped>
.kanban-column-drag-handle {
  cursor: grab;
  color: var(--ds-text-subtle, #6c757d);
}

.kanban-column-drag-handle:active {
  cursor: grabbing;
}
</style>
