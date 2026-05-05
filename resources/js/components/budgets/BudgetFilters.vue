<template>
  <div>
    <div class="row row-cols-lg-auto g-3 align-items-center mb-3">
      <div class="col-12">
        <div class="input-group input-group-prefix">
          <input
            type="text"
            class="form-control"
            placeholder="Pesquisar orçamento"
            v-model="localFilters.search"
          />
          <span class="input-group-text">
            <IconSearch :size="18" />
          </span>
        </div>
      </div>

      <div class="col-12" v-if="isAdmin">
        <UserSelect v-model="localFilters.user_id" placeholder="Revendedor" />
      </div>

      <div class="col-12">
        <select class="form-select" v-model="localFilters.status">
          <option value="">Status</option>
          <option v-for="option in statusOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
      </div>

      <div class="col-12">
        <button class="btn btn-default" type="button" @click="showMore = !showMore">
          Mais

          <IconChevronUp v-if="showMore" :size="18" />
          <IconChevronDown v-else :size="18" />
        </button>
      </div>
    </div>

    <div v-if="showMore" class="row row-cols-lg-auto g-3 align-items-center mb-3">
      <div class="col-12">
        <div class="form-group mb-2">
          <label class="form-label small mb-1">Período</label>

          <VueDatePicker
            v-model="localFilters.dateRange"
            :range="{ maxRange: new Date() }"
            :time-config="{ enableTimePicker: false }"
            :max-date="new Date()"
            :formats="{ input: 'dd/MM/yyyy' }"
            :enable-time-picker="false"
            auto-apply
            placeholder="Selecione o período"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { IconChevronDown, IconChevronUp, IconSearch } from '@tabler/icons-vue';
import { useAuthStore } from '@/stores/auth';
import UserSelect from '@/components/users/UserSelect.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['update:modelValue', 'search']);

const authStore = useAuthStore();
const isAdmin = computed(() => authStore.isAdmin());

const statusOptions = [
  { label: 'Em aberto', value: 'em aberto' },
  { label: 'Aprovado', value: 'aprovado' },
  { label: 'Cancelado', value: 'cancelado' },
];

const showMore = ref(false);

const localFilters = reactive({
  search: '',
  status: '',
  user_id: null,
  dateRange: null,
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
  const payload = buildPayload();

  emit('update:modelValue', payload);
  emit('search', payload);
}, 500);

function formatDate(date) {
  if (!date) return null;
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function buildPayload() {
  return {
    search: localFilters.search,
    status: localFilters.status,
    user_id: localFilters.user_id,
    date_from: formatDate(localFilters.dateRange?.[0]),
    date_to: formatDate(localFilters.dateRange?.[1]),
  };
}

watch(localFilters, () => debouncedUpdate(), { deep: true });
</script>
