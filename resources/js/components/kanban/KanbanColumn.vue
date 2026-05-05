<script setup></script>

<template>
  <div class="kanban-column" v-bind="$attrs">
    <div class="kanban-column-inner d-flex flex-column rounded-4 p-2 my-1">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.kanban-column {
  flex: 0 0 300px;
  max-height: 100%;
}

.kanban-column-inner {
  background-color: var(--ds-surface-sunken);
  box-shadow: var(--ds-shadow-raised);
  scroll-margin: var(--ds-space-100, 8px);
}
</style>
