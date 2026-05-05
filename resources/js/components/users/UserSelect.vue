<template>
  <div class="position-relative">
    <!-- Input -->
    <input
      type="text"
      class="form-control"
      :placeholder="placeholder"
      v-model="search"
      @focus="openDropdown"
      @blur="handleBlur"
    />

    <!-- Dropdown -->
    <div
      v-if="showDropdown"
      class="dropdown-menu show w-100 mt-1"
      style="max-height: 250px; overflow-y: auto"
    >
      <!-- Loading -->
      <div v-if="loading" class="dropdown-item text-muted">Carregando...</div>

      <!-- Sem resultados -->
      <div v-else-if="options.length === 0" class="dropdown-item text-muted">Nenhum resultado</div>

      <!-- Lista -->
      <button
        v-for="user in options"
        :key="user.id"
        class="dropdown-item"
        @mousedown.prevent="select(user)"
      >
        {{ user.name }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { http } from '@/lib/http';

defineOptions({ name: 'UserSelect' });

const props = defineProps({
  modelValue: [String, Number],
  placeholder: {
    type: String,
    default: 'Buscar usuário...',
  },
});

const emit = defineEmits(['update:modelValue']);

const search = ref('');
const options = ref([]);
const loading = ref(false);
const showDropdown = ref(false);

let cancelTokenSource = null;

const fetchUsers = useDebounceFn(async (search) => {
  if (!search) {
    options.value = [];
    return;
  }

  // Cancela request anterior
  if (cancelTokenSource) {
    cancelTokenSource.cancel();
  }

  cancelTokenSource = axios.CancelToken.source();

  loading.value = true;

  try {
    const { data } = await http.get('v1/users', {
      params: {
        search,
        limit: 10,
      },
      cancelToken: cancelTokenSource.token,
    });

    options.value = data.data;
  } catch (err) {
    if (!axios.isCancel(err)) {
      console.error(err);
    }
  } finally {
    loading.value = false;
  }
}, 300);

watch(search, (val) => {
  fetchUsers(val);
});

const select = (user) => {
  search.value = user.name;
  emit('update:modelValue', user.id);
  showDropdown.value = false;
};

const openDropdown = () => {
  showDropdown.value = true;
};

const handleBlur = () => {
  setTimeout(() => {
    showDropdown.value = false;
  }, 150);
};
</script>

<style scoped>
.dropdown-menu {
  z-index: 1050;
}
</style>
