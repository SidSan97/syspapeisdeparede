<template>
  <VueSimpleSuggest
    v-model="query"
    :list="fetchSuggestions"
    :filter-by-query="false"
    :min-length="0"
    :debounce="300"
    display-attribute="label"
    placeholder="Buscar por nome, nome fantasia ou CNPJ..."
    @select="onSelect"
  >
    <template #default="{ field }">
      <input
        v-bind="field"
        type="text"
        class="form-control"
        :class="{ 'is-invalid': invalid }"
        :disabled="disabled"
        autocomplete="off"
      />
    </template>

    <template #suggestion-item="{ suggestion }">
      <div>
        <strong>{{ suggestion.name }}</strong>
        <small v-if="suggestion.cnpj" class="text-muted"> — {{ suggestion.cnpj }}</small>
      </div>
    </template>
  </VueSimpleSuggest>
</template>

<script setup>
import { ref, watch } from 'vue';
import VueSimpleSuggest from '@vojtechlanka/vue-simple-suggest';
import '@vojtechlanka/vue-simple-suggest/style.css';
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

const query = ref(formatLabel(props.selectedReseller));

async function fetchSuggestions(search) {
  const resellers = await resellerService.search(search, props.modelValue);

  return resellers.map((reseller) => ({
    ...reseller,
    label: formatLabel(reseller),
  }));
}

function onSelect(reseller) {
  emit('update:modelValue', reseller?.id ?? null);
}

watch(
  () => props.selectedReseller,
  (reseller) => {
    query.value = formatLabel(reseller);
  },
);

watch(
  () => props.modelValue,
  (value) => {
    if (!value) {
      query.value = '';
    }
  },
);
</script>
