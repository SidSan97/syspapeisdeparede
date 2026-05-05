<script setup>
import { computed, useSlots } from 'vue';

const props = defineProps({
  title: {
    type: String,
    default: '',
  },

  subtitle: {
    type: String,
    default: '',
  },
});

const hasSubtitleText = computed(() => Boolean(props.subtitle));

const slots = useSlots();

const hasSubtitleSlot = computed(() => Boolean(slots.subtitle));

const hasSubtitle = computed(() => hasSubtitleText.value || hasSubtitleSlot.value);
</script>

<template>
  <div class="d-flex align-items-center gap-2">
    <h1 v-if="title" class="h4 mb-0">
      {{ title }}
    </h1>

    <span v-if="hasSubtitle" class="text-muted fs-sm mb-0">
      {{ subtitle }}

      <slot name="subtitle"></slot>
    </span>
  </div>
</template>

<style scoped></style>
