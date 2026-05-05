<script setup>
import { computed, useSlots } from 'vue';

const props = defineProps({
  for: String,

  text: String,

  description: String,

  labelClass: [String, Array, Object],
});

defineOptions({
  inheritAttrs: false,
});

const slots = useSlots();

const hasLabelContent = computed(() => Boolean(slots.default) || Boolean(props.text));

const hasDescriptionContent = computed(
  () => Boolean(slots.description) || Boolean(props.description),
);

const labelClasses = computed(() => ['form-label', props.labelClass]);
</script>

<template>
  <label v-if="hasLabelContent" class="" :class="labelClasses" :for="props.for" v-bind="$attrs">
    <slot>
      {{ text }}
    </slot>

    <span v-if="hasDescriptionContent" class="form-label-description">
      <slot name="description">
        {{ description }}
      </slot>
    </span>
  </label>
</template>

<style scoped></style>
