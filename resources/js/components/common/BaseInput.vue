<script setup>
import { computed, useAttrs } from 'vue';

import BaseInputLabel from './BaseInputLabel.vue';

const props = defineProps({
  label: String,

  modelValue: [String, Number],

  labelClass: [String, Array, Object],

  labelDescription: String,

  formGroupClass: [String, Array, Object],

  inputGroupClass: [String, Array, Object],
});

const emit = defineEmits(['update:modelValue']);

defineOptions({
  inheritAttrs: false,
});

const attrs = useAttrs();

const inputId = computed(() => {
  if (attrs.id) return attrs.id;

  return `input-${Math.random().toString(36).slice(2, 9)}`;
});

const formGroupClasses = computed(() => ['form-group', props.formGroupClass]);

const inputGroupClasses = computed(() => ['input-group', props.inputGroupClass]);

function handleInput(event) {
  emit('update:modelValue', event.target.value);
}
</script>

<template>
  <div class="" :class="formGroupClasses">
    <BaseInputLabel v-if="label" :for="inputId" :label-class="labelClass">
      {{ label }}

      <template #description>
        <slot name="label-description">
          {{ labelDescription }}
        </slot>
      </template>
    </BaseInputLabel>

    <div class="" :class="inputGroupClasses">
      <slot name="prepend"></slot>

      <input
        ref=""
        class="form-control"
        :id="inputId"
        :value="modelValue"
        v-bind="attrs"
        @input="handleInput"
      />

      <slot name="append"></slot>
    </div>

    <slot name="bottom"></slot>
  </div>
</template>

<style scoped></style>
