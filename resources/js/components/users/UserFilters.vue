<template>
  <div class="row row-cols-lg-auto g-3 align-items-center mb-3">
    <div class="col-12">
      <div class="input-group input-group-prefix">
        <input
          type="text"
          class="form-control"
          placeholder="Pesquisar usuário"
          v-model="localFilters.search"
        />
        <span class="input-group-text">
          <IconSearch :size="18" />
        </span>
      </div>
    </div>

    <div class="col-12">
      <RoleSelect v-model="localFilters.role" />
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { IconSearch } from '@tabler/icons-vue';

import RoleSelect from '@/components/users/RoleSelect.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['update:modelValue', 'search']);

const localFilters = reactive({
  search: '',
  role: null,
});

watch(
  () => props.modelValue,
  (newVal) => {
    if (!newVal) return;

    Object.assign(localFilters, newVal);
  },
  { immediate: true, deep: true },
);

const debouncedUpdate = useDebounceFn(() => {
  const payload = {
    search: localFilters.search,
    role: localFilters.role,
  };

  emit('update:modelValue', payload);
  emit('search', payload);
}, 500);

watch(localFilters, () => debouncedUpdate(), { deep: true });
</script>
