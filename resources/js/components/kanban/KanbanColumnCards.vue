<template>
  <div
    class="flex-grow-1 overflow-y-auto overflow-x-hidden p-1"
    style="min-height: 40px"
    :class="{ 'bg-light border rounded': isDragging }"
    @drop="handleDrop"
    @dragover.prevent
    @dragenter.prevent="isDragging = true"
    @dragleave="isDragging = false"
  >
    <slot />
  </div>
</template>

<script setup>
import { ref } from 'vue';

const emit = defineEmits({
  drop: (e) => e instanceof DragEvent,
});

const isDragging = ref(false);

function handleDrop(e) {
  isDragging.value = false;
  emit('drop', e);
}
</script>
