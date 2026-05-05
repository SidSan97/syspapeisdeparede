<script setup>
defineOptions({ inheritAttrs: false });
</script>

<template>
  <div
    :class="['kanban-column-card card rounded-3 border-0 cursor-pointer', $attrs.class]"
    v-bind="{ ...$attrs, class: undefined }"
    @dragstart="$emit('drag-start', $event)"
    @click="$emit('click')"
    draggable="true"
  >
    <div class="pt-2 px-3 pb-1">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.kanban-column-card {
  box-shadow: var(--ds-shadow-raised);
}
</style>
