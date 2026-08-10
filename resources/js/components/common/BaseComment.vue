<script setup>
defineProps({
  author: {
    type: String,
    default: null,
  },
  badge: {
    type: String,
    default: null,
  },
});
</script>

<template>
  <div class="d-flex gap-2">
    <slot name="avatar" />

    <div class="flex-1">
      <div class="d-flex align-items-center gap-1">
        <div v-if="author" class="text-body-secondary fs-sm fw-medium">{{ author }}</div>
        <span v-if="badge" class="badge text-bg-neutral fs-sm">{{ badge }}</span>
      </div>
      <slot />
    </div>
  </div>
</template>
