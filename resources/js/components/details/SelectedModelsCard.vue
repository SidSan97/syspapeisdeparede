<template>
  <div v-if="show">
    <div v-if="hasModel" class="mb-3">
      <div class="text-muted small">Modelo</div>
      <div class="fw-semibold">
        {{ modelName || '-' }}
      </div>
      <div v-if="hasModelDetails" class="text-muted small mt-1">
        <span v-if="modelValueLabel">Valor: {{ modelValueLabel }}</span>
        <span v-if="modelDeadlineLabel" class="ms-3">Prazo: {{ modelDeadlineLabel }}</span>
      </div>
    </div>

    <WallModelReferringFields v-if="hasReferringContent" :wall="wall" :compact="compact" />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import WallModelReferringFields from '@/components/details/WallModelReferringFields.vue';
import { useFormatting } from '@/composables/useFormatting';
import { hasWallModelReferringContent } from '@/utils/wallModelReferringContent';

const props = defineProps({
  wall: {
    type: Object,
    required: true,
  },
  compact: {
    type: Boolean,
    default: false,
  },
});

const { formatCurrency } = useFormatting();

const modelName = computed(
  () => props.wall?.collection_model?.name || props.wall?.collection_model_name || '',
);

const hasModel = computed(
  () => Boolean(props.wall?.collection_model || props.wall?.collection_model_name),
);

const hasReferringContent = computed(() => hasWallModelReferringContent(props.wall));

const show = computed(() => hasModel.value || hasReferringContent.value);

const modelValueLabel = computed(() => {
  const value = props.wall?.collection_model?.value;
  if (value === null || value === undefined || value === '') {
    return '';
  }
  const numeric = Number(value);
  if (!Number.isFinite(numeric)) {
    return '';
  }
  return formatCurrency(numeric);
});

const modelDeadlineLabel = computed(() => {
  const deadline = props.wall?.collection_model?.deadline;
  if (deadline === null || deadline === undefined || deadline === '') {
    return '';
  }
  const numeric = Number(deadline);
  if (!Number.isFinite(numeric)) {
    return '';
  }
  return `${numeric} dia${numeric === 1 ? '' : '(s)'}`;
});

const hasModelDetails = computed(
  () => Boolean(modelValueLabel.value) || Boolean(modelDeadlineLabel.value),
);
</script>
