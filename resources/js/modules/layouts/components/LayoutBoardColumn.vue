<template>
  <KanbanColumn ref="rootRef">
    <KanbanColumnHeader :count="count">
      <template #default>
        <button
          v-if="!isEditing"
          type="button"
          class="btn btn-subtle text-start w-100 ps-3"
          @click="startEditing"
        >
          {{ column.name }}
        </button>
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
              class="btn btn-subtle btn-icon"
              :class="{ show: open }"
              @click.stop="toggle"
              :aria-expanded="open"
              title="Ações da Lista"
            >
              <IconDots :size="18" />
            </button>
          </template>

          <li><h6 class="dropdown-header">Ações da Lista</h6></li>
          <li><button class="dropdown-item" @click="startEditing">Renomear</button></li>
          <li><hr class="dropdown-divider" /></li>
          <li><button class="dropdown-item" @click="emit('delete')">Excluir esta Lista</button></li>
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
import { IconDots } from '@tabler/icons-vue';
import BaseDropdown from '@/components/common/BaseDropdown.vue';
import KanbanColumn from '@/components/kanban/KanbanColumn.vue';
import KanbanColumnHeader from '@/components/kanban/KanbanColumnHeader.vue';
import KanbanColumnCards from '@/components/kanban/KanbanColumnCards.vue';

const props = defineProps({
  column: { type: Object, required: true },
  count: { type: Number, default: 0 },
});

const emit = defineEmits({
  delete: null,
  drop: (e) => e instanceof DragEvent,
  'update:name': (payload) => payload?.columnId,
});

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
