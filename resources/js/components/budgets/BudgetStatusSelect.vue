<template>
  <select class="form-select" :value="modelValue" @change="onChange">
    <option value="">{{ placeholder }}</option>

    <option v-for="role in BUDGET_STATUS_OPTIONS" :key="role.value" :value="role.value">
      {{ role.label }}
    </option>
  </select>
</template>

<script setup>
import { BUDGET_STATUS_OPTIONS } from '@/constants/budgetStatuses';

defineProps({
  modelValue: {
    type: String,
    default: null,
  },
  placeholder: {
    type: String,
    default: 'Situação',
  },
});

const emit = defineEmits(['update:modelValue']);

const onChange = (event) => {
  emit('update:modelValue', event.target.value);
};
</script>
