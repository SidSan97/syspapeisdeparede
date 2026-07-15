<template>
  <div ref="rootRef" class="d-flex align-items-center justify-content-between mb-2 px-2">
    <div class="flex-grow-1">
      <slot>
        <span class="fw-semibold">{{ heading }}</span>
        <small v-if="subheading" class="text-muted ms-1">{{ subheading }}</small>
      </slot>
    </div>

    <div class="d-flex align-items-center gap-2 ms-2">
      <span v-if="count !== undefined" class="badge border text-body">{{ count }}</span>
      <span v-if="badge" class="badge" :class="badge.class ?? 'bg-secondary'">{{
        badge.label
      }}</span>

      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  heading: { type: String, default: '' },
  subheading: { type: String, default: '' },
  count: { type: Number, default: undefined },
  // badge: { label: string, class?: string }
  badge: { type: Object, default: null },
});
</script>
