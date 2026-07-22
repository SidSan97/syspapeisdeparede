<template>
  <VSelect
    :model-value="selectedOption"
    :options="options"
    :get-option-label="formatLabel"
    :filterable="false"
    :disabled="disabled"
    :class="{ 'is-invalid': invalid }"
    placeholder="Buscar por nome, nome fantasia ou CNPJ..."
    @search="onSearch"
    @update:model-value="onSelect"
  >
    <template #option="reseller">
      <div>
        <strong>{{ reseller.name }}</strong>
        <small v-if="reseller.cnpj" class="text-muted"> — {{ reseller.cnpj }}</small>
      </div>
    </template>

    <template #no-options>Nenhum revendedor encontrado.</template>
  </VSelect>
</template>

<script setup>
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import VSelect from 'vue-select';
import 'vue-select/dist/vue-select.css';
import { resellerService } from '@/services/resellerService';

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null,
  },
  selectedReseller: {
    type: Object,
    default: null,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  invalid: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue']);

function formatLabel(reseller) {
  if (!reseller) return '';

  return reseller.cnpj ? `${reseller.name} — ${reseller.cnpj}` : reseller.name;
}

const options = ref([]);
const selectedOption = ref(props.selectedReseller);

const fetchSuggestions = debounce((search, toggleLoading) => {
  resellerService.search(search, props.modelValue).then((resellers) => {
    options.value = resellers;
    toggleLoading(false);
  });
}, 300);

function onSearch(search, toggleLoading) {
  toggleLoading(true);
  fetchSuggestions(search, toggleLoading);
}

function onSelect(reseller) {
  selectedOption.value = reseller;
  emit('update:modelValue', reseller?.id ?? null);
}

watch(
  () => props.selectedReseller,
  (reseller) => {
    selectedOption.value = reseller;
  },
);
</script>

<style>
.v-select {
  --bs-input-color: var(--ds-text);
  --bs-input-bg: var(--ds-background-input);
  --bs-input-border-color: var(--ds-border-input);
  --bs-input-hover-bg: var(--ds-background-input-hovered);
  --bs-input-focus-color: var(--ds-text);
  --bs-input-focus-bg: var(--ds-background-input);
  --bs-input-focus-border-color: var(--ds-border-focused);
  --bs-input-disabled-color: var(--ds-text-disabled);
  --bs-input-disabled-bg: var(--ds-background-disabled);
  --bs-input-disabled-border-color: var(--ds-border-disabled);

  --vs-border-color: var(--bs-input-border-color);
  --vs-search-input-color: var(--bs-input-color);
  --vs-dropdown-bg: var(--bs-input-bg);
  --vs-dropdown-color: var(--bs-input-color);
  --vs-dropdown-option-color: var(--bs-input-color);
  --vs-selected-color: var(--bs-input-color);
  --vs-state-disabled-bg: var(--bs-input-disabled-bg);
  --vs-state-disabled-color: var(--bs-input-disabled-color);
}

.v-select .vs__dropdown-toggle {
  background-color: var(--bs-input-bg);
}

.v-select:not(.vs--disabled):hover .vs__dropdown-toggle {
  background-color: var(--bs-input-hover-bg);
}

.v-select.vs--open .vs__dropdown-toggle {
  background-color: var(--bs-input-focus-bg);
  border-color: var(--bs-input-focus-border-color);
}

.v-select.vs--disabled .vs__dropdown-toggle {
  border-color: var(--bs-input-disabled-border-color);
}
</style>
