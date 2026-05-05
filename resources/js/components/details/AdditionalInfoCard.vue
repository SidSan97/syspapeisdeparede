<template>
  <div class="card mb-4">
    <div class="card-header bg-transparent">
      <h5 class="mb-0 fw-semibold">Informações Adicionais</h5>
    </div>
    <div class="card-body">
      <div v-if="data.created_at" class="mb-3">
        <div class="text-muted small">Criado em</div>
        <div class="fw-semibold">{{ formatDate(data.created_at) }}</div>
      </div>
      <div v-if="data.updated_at" class="mb-0">
        <div class="text-muted small">Atualizado em</div>
        <div class="fw-semibold">{{ formatDate(data.updated_at) }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useFormatting } from '@/composables/useFormatting';

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
});

const { formatDate } = useFormatting();
</script>
