<template>
  <div class="blankslate">
    <component v-if="icon" :is="icon" class="text-muted m-4" :size="64" />

    <div class="blankslate-body">
      <h4 v-if="heading">{{ heading }}</h4>
      <p>
        <slot />
      </p>
    </div>

    <div v-if="hasActionsSlot" class="blankslate-actions">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
import { computed, useSlots } from 'vue';

const slots = useSlots();

const props = defineProps({
  icon: {
    type: [Object, Function],
    default: null,
  },
  heading: String,
});

const hasActionsSlot = computed(() => !!slots.actions);
</script>
