<template>
  <select class="form-select" :value="modelValue" @change="onChange">
    <option value="">{{ placeholder }}</option>

    <option v-for="role in USER_ROLE_OPTIONS" :key="role.value" :value="role.value">
      {{ role.label }}
    </option>
  </select>
</template>

<script setup>
import { USER_ROLE_OPTIONS } from '@/constants/userRoles';

const props = defineProps({
  modelValue: String,
  placeholder: {
    type: String,
    default: 'Selecione um papel',
  },
});

const emit = defineEmits(['update:modelValue']);

const onChange = (event) => {
  emit('update:modelValue', event.target.value);
};
</script>
