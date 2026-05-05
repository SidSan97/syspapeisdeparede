<template>
  <li class="list-group-item d-flex justify-content-between align-items-center">
    <button class="btn btn-subtle btn-sm btn-icon" @click.prevent="toggleCollapse" v-if="!isChild">
      <IconChevronUp v-if="isCollapsed" :size="18" />
      <IconChevronDown v-else :size="18" />
    </button>
    <span class="ms-7" v-else></span>
    <div class="ms-2 me-auto">
      {{ category.name }}
    </div>
    <div>
      <BaseDropdown align="end">
        <template #trigger="{ open, toggle }">
          <button
            class="btn btn-subtle btn-sm btn-icon"
            type="button"
            :class="{ show: open }"
            :aria-expanded="open"
            @click="toggle"
          >
            <IconDotsVertical :size="16" />
          </button>
        </template>

        <li v-if="!isChild">
          <button class="dropdown-item" type="button" @click="$emit('open-modal', null, category)">
            Nova subcategoria
          </button>
        </li>
        <li>
          <button
            class="dropdown-item"
            type="button"
            @click="$emit('open-modal', category, parentCategory)"
          >
            Editar
          </button>
        </li>
        <li><hr class="dropdown-divider" /></li>
        <li>
          <button
            class="dropdown-item"
            style="color: var(--ds-text-danger)"
            type="button"
            @click="$emit('delete', category, parentCategory)"
          >
            Excluir
          </button>
        </li>
      </BaseDropdown>
    </div>
  </li>

  <div v-show="isCollapsed">
    <span v-if="children.length === 0" class="text-muted fst-italic py-2 px-4"
      >Nenhuma subcategoria</span
    >
    <template v-for="(child, index) in children" :key="child.id">
      <CollectionCategoryListItem
        :category="child"
        :is-child="true"
        :parent-category="category"
        @open-modal="(item, parent) => $emit('open-modal', item, parent)"
        @delete="(item, parent) => $emit('delete', item, parent)"
      />
      <hr v-if="index < children.length - 1" />
    </template>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { IconChevronDown, IconChevronUp, IconDotsVertical } from '@tabler/icons-vue';
import BaseDropdown from '@/components/common/BaseDropdown.vue';

const props = defineProps({
  category: {
    type: Object,
    required: true,
  },
  isChild: Boolean,
  parentCategory: {
    type: Object,
    default: null,
  },
});

defineEmits(['open-modal', 'delete']);

const isCollapsed = ref(false);

const children = computed(() => props.category.children || []);

const toggleCollapse = () => (isCollapsed.value = !isCollapsed.value);
</script>
