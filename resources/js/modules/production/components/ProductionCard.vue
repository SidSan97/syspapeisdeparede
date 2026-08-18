<template>
  <div
    class="card rounded cursor-pointer production-card border-0"
    tabindex="0"
    role="button"
    :draggable="!isFullyProduced"
    @dragstart="handleDragStart"
    @click="handleClick"
    @keydown.enter="handleClick"
    @keydown.space.prevent="handleClick"
  >
    <div v-if="coverImage" class="ratio ratio-16x9">
      <img :src="coverImage" :alt="itemName" class="card-img-top object-fit-cover" />
    </div>
    <div class="card-body pt-2 px-3 pb-1">
      <div class="mb-2">
        <p class="m-0 small text-body-secondary">
          {{ displayName }}
        </p>

        <div class="d-flex gap-2 align-items-start flex-wrap">
          <p
            v-if="itemName"
            class="fs-sm mb-0 flex-grow-1 d-flex align-items-start gap-1"
            :class="{ 'text-body-secondary': isFullyProduced }"
          >
            <button
              v-if="isFullyProduced"
              type="button"
              class="btn p-0 border-0 bg-transparent flex-shrink-0 mt-1 production-card-reopen"
              title="Reabrir card"
              :disabled="isReopening"
              @click.stop="handleReopen"
            >
              <IconCircleCheckFilled :size="16" class="text-success" />
            </button>
            <span>{{ itemName }}</span>
          </p>
        </div>

        <span
          v-if="hasStatus"
          class="badge text-wrap align-self-start flex-shrink-0 me-2"
          :class="statusBadgeClass"
        >
          {{ card.status }}
        </span>
        <span v-if="!isFullyProduced && productionTimerText" class="d-inline-block mt-1">
          <span class="badge text-wrap production-deadline-badge" :class="productionTimerClass">{{
            productionTimerText
          }}</span>
        </span>
      </div>

      <div class="d-flex align-items-center gap-2">
        <span class="badge d-inline-flex align-items-center" :class="deliveryBadgeClass">
          <IconClock :size="14" class="me-1" />
          {{ deliveryRangeText }}
        </span>

        <span
          v-if="hasComments"
          class="badge text-bg-transparent d-inline-flex align-items-center"
        >
          <IconMessage :size="14" class="me-1" />
          {{ commentsCount }}
        </span>

        <span
          v-if="hasActivities"
          class="badge text-bg-transparent d-inline-flex align-items-center"
        >
          <IconList :size="14" class="me-1" />
          {{ activitiesCount }}
        </span>

        <span
          v-if="hasUploads"
          class="badge text-bg-transparent d-inline-flex align-items-center"
        >
          <IconPaperclip :size="14" class="me-1" />
          {{ uploadedFilesCount }}
        </span>
      </div>
      <div v-if="hasProductionDate" class="mt-2">
        <span class="small text-body-secondary"> Produção: {{ productionDateText }} </span>
      </div>

      <div v-if="stripGroups.length" class="table-responsive mt-2">
        <table class="table table-sm table-bordered mb-0 align-middle production-strips-table">
          <thead class="table-light">
            <tr>
              <th scope="col" class="text-end text-nowrap">Qtd. Faixas</th>
              <th scope="col" class="text-end text-nowrap">Alt. Faixas (m)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(group, index) in stripGroups" :key="index">
              <td class="text-end">{{ group.q }}</td>
              <td class="text-end">{{ formatNumber(group.h) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="installationDirectionText" class="mt-2 small">
        <span class="text-body-secondary d-block">Instalação:</span>
        <span class="text-body">{{ installationDirectionText }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import {
  IconCircleCheckFilled,
  IconClock,
  IconList,
  IconMessage,
  IconPaperclip,
} from '@tabler/icons-vue';
import {
  getActivitiesCount,
  getCommentsCount,
  getCoverImage,
} from '@/modules/card-modals/composables/useCardUtils';
import {
  getCardDisplayName,
  getOrderBudgetStatusBadgeClass,
  getProductionTimerText,
  getProductionTimerClass,
} from '@/utils/cardUtils';
import { calculateWallWithContinuations } from '@/utils/calculateStripsUtils';

// Icons
import { useFormatting } from '@/composables/useFormatting';
import { useReopenProductionCard } from '@/modules/production/composables/useReopenProductionCard';

const props = defineProps({
  card: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['drag-start', 'click']);

const { formatDateOnly, formatNumber } = useFormatting();
const { isReopening, reopenCard } = useReopenProductionCard();

let timerInterval = null;

const currentTime = ref(new Date());

// Card
const displayName = computed(() => getCardDisplayName(props.card));
const itemName = computed(() => props.card.name || '');
const coverImage = computed(() => getCoverImage(props.card));

// Status
const hasStatus = computed(() => Boolean(props.card.status));
const statusBadgeClass = computed(() => getOrderBudgetStatusBadgeClass(props.card.status));
const isFullyProduced = computed(() => {
  return (
    props.card.production_percentage === 100 || Number(props.card.production_percentage) === 100
  );
});
const deliveryRangeText = computed(
  () => `${props.card.delivery_date_start} - ${props.card.delivery_date_end}`,
);
const deliveryBadgeClass = computed(() =>
  isFullyProduced.value ? 'text-bg-success' : 'text-bg-transparent',
);
const hasProductionDate = computed(() => Boolean(props.card.production_date));
const productionDateText = computed(() => formatDateOnly(props.card.production_date));

// Faixas
const stripGroups = computed(() => {
  const wall = props.card.wall;
  if (!wall) return [];

  const groups = [];
  calculateWallWithContinuations(wall).perPart.forEach((part) => {
    const q = Number(part?.strips ?? 0);
    const h = Number(part?.stripHeight ?? 0);
    if (!Number.isFinite(q) || q <= 0 || !Number.isFinite(h) || h <= 0) return;

    const last = groups[groups.length - 1];
    if (last && Math.abs(last.h - h) < 1e-9) {
      last.q += q;
    } else {
      groups.push({ q, h });
    }
  });

  return groups;
});

function directionInstallLabel(direction) {
  if (direction === 'left-to-right') {
    return 'Esquerda para direita das paredes';
  }
  if (direction === 'right-to-left') {
    return 'Direita para a esquerda das paredes';
  }
  return '';
}

const installationDirectionText = computed(() => {
  const wall = props.card.wall;
  if (!wall) return '';

  const legacyFromContinuation = wall.continuations?.[0]?.direction;
  const direction = wall.direction ?? wall.Direction ?? legacyFromContinuation;
  return directionInstallLabel(direction);
});

// Counters
const commentsCount = computed(() => getCommentsCount(props.card));
const activitiesCount = computed(() => getActivitiesCount(props.card));
const uploadedFilesCount = computed(() => props.card.uploaded_files?.length ?? 0);
const hasComments = computed(() => commentsCount.value > 0);
const hasActivities = computed(() => activitiesCount.value > 0);
const hasUploads = computed(() => uploadedFilesCount.value > 0);

// Production timer
const productionTimerText = computed(() => getProductionTimerText(props.card, currentTime.value));
const productionTimerClass = computed(() =>
  getProductionTimerClass(props.card, currentTime.value, 'production-card-timer'),
);

onMounted(async () => {
  timerInterval = setInterval(() => {
    currentTime.value = new Date();
  }, 60000);
});

onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval);
  }
});

function handleClick() {
  emit('click');
}

async function handleReopen() {
  if (isReopening.value) {
    return;
  }

  await reopenCard(props.card);
}

function handleDragStart(event) {
  emit('drag-start', event);
}
</script>

<style scoped>
.text-bg-transparent {
  background-color: transparent;
  color: var(--ds-text);
}

.production-card {
  box-shadow: var(--ds-shadow-raised);
  transition:
    box-shadow 0.15s ease,
    transform 0.15s ease;
}

.production-card:hover {
  box-shadow:
    var(--ds-shadow-raised),
    0 0 0 2px var(--ds-border-focused);
}

.production-card:active {
  cursor: grabbing;
}

.production-card:focus-visible {
  outline: 2px solid var(--bs-primary);
  outline-offset: 2px;
}

.production-card-reopen {
  cursor: pointer;
  line-height: 1;
}

.production-card-reopen:disabled {
  cursor: wait;
  opacity: 0.7;
}

.production-card-reopen:focus-visible {
  outline: 2px solid var(--bs-primary);
  outline-offset: 2px;
  border-radius: 999px;
}

.production-deadline-badge {
  font-size: 0.75rem;
  font-weight: 600;
  color: #fff;
  max-width: 100%;
}

.production-deadline-badge--success {
  background-color: #198754;
}

.production-deadline-badge--warning {
  background-color: #fd7e14;
}

.production-deadline-badge--danger {
  background-color: #dc3545;
}
</style>
