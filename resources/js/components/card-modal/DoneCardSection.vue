<template>
  <div v-if="!isCompleted" class="mb-4">
    <button
      type="button"
      class="btn btn-success btn-sm d-inline-flex align-items-center"
      :disabled="completing || !cardId"
      @click="$emit('complete')"
    >
      <span v-if="completing" class="spinner-border spinner-border-sm me-1" role="status"></span>
      <IconCheck v-else :size="18" class="me-1" />
      Concluir
    </button>
  </div>
  <div v-else class="alert alert-success d-flex align-items-center gap-2 py-2 mb-4">
    <IconCircleCheck :size="20" />
    <div>
      Card concluído em
      <strong>{{ completedAtLabel }}</strong>
      <span v-if="completedDurationLabel" class="ms-2 text-body-secondary">
        · Duração total: <strong>{{ completedDurationLabel }}</strong>
      </span>
    </div>
  </div>
</template>

<script setup>
import { IconCheck, IconCircleCheck } from '@tabler/icons-vue';

defineProps({
  isCompleted: {
    type: Boolean,
    required: true,
  },
  completing: {
    type: Boolean,
    default: false,
  },
  completedAtLabel: {
    type: String,
    default: '',
  },
  completedDurationLabel: {
    type: String,
    default: '',
  },
  /** Identificador do order_budget (para habilitar o botão Concluir). */
  cardId: {
    type: [Number, String],
    default: null,
  },
});

defineEmits(['complete']);
</script>
