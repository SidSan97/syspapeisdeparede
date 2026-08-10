<script setup>
import { IconDotsVertical, IconEdit, IconTrash } from '@tabler/icons-vue';
import { useFormatting } from '@/composables/useFormatting';
import BaseDropdown from '@/components/common/BaseDropdown.vue';

defineProps({
  models: {
    type: Array,
    required: true,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['delete']);

const { formatCurrency } = useFormatting();
</script>

<template>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Valor</th>
          <th>Prazo (dias)</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td colspan="4" class="p-5 text-center text-muted fw-semibold">Carregando modelos...</td>
        </tr>
        <tr v-else-if="models.length === 0">
          <td colspan="4" class="p-5 text-center text-muted fw-semibold">
            Nenhum modelo encontrado.
          </td>
        </tr>
        <tr v-else v-for="model in models" :key="model.id">
          <td class="text-nowrap">
            <router-link
              class="text-body"
              :to="{
                name: 'settings.models.edit',
                params: { id: model.id },
              }"
            >
              {{ model.name }}
            </router-link>
          </td>
          <td class="text-nowrap">
            {{ formatCurrency(model.value) }}
          </td>
          <td>{{ model.deadline }}</td>
          <td class="text-end">
            <BaseDropdown align="end">
              <template #trigger="{ open, toggle }">
                <button
                  class="btn btn-subtle btn-sm"
                  type="button"
                  :aria-expanded="open"
                  @click="toggle"
                >
                  <IconDotsVertical :size="18" />
                </button>
              </template>
              <li>
                <router-link
                  class="dropdown-item"
                  :to="{
                    name: 'settings.models.edit',
                    params: { id: model.id },
                  }"
                >
                  <IconEdit size="16" class="me-2" /> Editar
                </router-link>
              </li>
              <li>
                <button
                  class="dropdown-item"
                  style="color: var(--ds-text-danger)"
                  type="button"
                  @click="$emit('delete', model)"
                >
                  <IconTrash size="16" class="me-2" /> Excluir
                </button>
              </li>
            </BaseDropdown>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
