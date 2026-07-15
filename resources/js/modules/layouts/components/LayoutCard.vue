<template>
  <div
    class="card rounded cursor-pointer layout-card border-0"
    tabindex="0"
    role="button"
    @dragstart="handleDragStart"
    @click="handleClick"
    @keydown.enter="handleClick"
    @keydown.space.prevent="handleClick"
    draggable
  >
    <div v-if="coverImage" class="ratio ratio-16x9">
      <img :src="coverImage" :alt="itemName" class="card-img-top object-fit-cover" />
    </div>

    <div class="card-body pt-2 px-3 pb-1">
      <p class="m-0 small text-body-secondary">
        {{ displayName }}
      </p>

      <p
        v-if="itemName"
        class="m-0 d-flex align-items-start gap-1"
        :class="{ 'text-body-secondary': isCompleted }"
      >
        <IconCircleCheckFilled
          v-if="isCompleted"
          :size="16"
          class="text-success flex-shrink-0 mt-1"
          title="Card concluído"
        />

        <span>
          {{ itemName }}
        </span>
      </p>
    </div>

    <div class="card-footer border-0 bg-transparent px-3 pt-1 pb-2">
      <span class="badge d-inline-flex align-items-center me-1" :class="deliveryBadgeClass">
        <IconClock :size="14" class="me-1" />

        {{ deliveryRangeText }}
      </span>

      <span
        v-if="activityLabel"
        class="badge d-inline-flex align-items-center me-1"
        :class="activityBadgeClass"
        :title="activityTitle"
      >
        <IconCircleFilled
          v-if="activityIsRunning"
          :size="8"
          class="trello-card-activity-pulse me-1"
        />

        <IconStopwatch v-else :size="14" class="me-1" />

        {{ activityLabel }}
      </span>

      <span
        v-if="hasComments"
        class="badge text-bg-transparent d-inline-flex align-items-center me-1"
      >
        <IconMessage :size="14" class="me-1" />

        {{ commentsCount }}
      </span>

      <span
        v-if="hasActivities"
        class="badge text-bg-transparent d-inline-flex align-items-center me-1"
      >
        <IconList :size="14" class="me-1" />

        {{ activitiesCount }}
      </span>

      <span
        v-if="hasUploads"
        class="badge text-bg-transparent d-inline-flex align-items-center me-1"
      >
        <IconPaperclip :size="14" class="me-1" />

        {{ uploadedFilesCount }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import {
  IconCircleCheckFilled,
  IconClock,
  IconList,
  IconMessage,
  IconPaperclip,
  IconCircleFilled,
  IconStopwatch,
} from '@tabler/icons-vue';
import {
  getActivitiesCount,
  getCommentsCount,
  getCoverImage,
} from '@/modules/card-modals/composables/useCardUtils';
import { getCardDisplayName } from '@/utils/cardUtils';
import {
  formatActivityCompact,
  getCardTotalSeconds,
  isCardActivityRunning,
  isCardCompleted,
} from '@/utils/layoutCardActivityUtils';

const props = defineProps({
  card: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['drag-start', 'click']);

const now = ref(new Date());
let tickerId = null;

// Card
const displayName = computed(() => getCardDisplayName(props.card));
const itemName = computed(() => props.card.name || '');
const coverImage = computed(() => getCoverImage(props.card));

// Status
const isCompleted = computed(() => isCardCompleted(props.card));
const deliveryRangeText = computed(
  () => `${props.card.delivery_date_start} - ${props.card.delivery_date_end}`,
);
const deliveryBadgeClass = computed(() =>
  isCompleted.value ? 'text-bg-success' : 'text-bg-transparent',
);

// Counters
const commentsCount = computed(() => getCommentsCount(props.card));
const activitiesCount = computed(() => getActivitiesCount(props.card));
const uploadedFilesCount = computed(() => props.card.uploaded_files?.length ?? 0);
const hasComments = computed(() => commentsCount.value > 0);
const hasActivities = computed(() => activitiesCount.value > 0);
const hasUploads = computed(() => uploadedFilesCount.value > 0);

// Activity timer
const activityIsRunning = computed(() => isCardActivityRunning(props.card));
const activityTotalSeconds = computed(() => getCardTotalSeconds(props.card, now.value));
const activityLabel = computed(() => {
  if (!activityIsRunning.value && activityTotalSeconds.value === 0) return '';
  return formatActivityCompact(activityTotalSeconds.value);
});
const activityTitle = computed(() =>
  activityIsRunning.value
    ? `Temporizador em execução · ${activityLabel.value}`
    : `Tempo registrado · ${activityLabel.value}`,
);
const activityBadgeClass = computed(() =>
  activityIsRunning.value ? 'text-bg-danger' : 'text-bg-transparent',
);

watch(
  activityIsRunning,
  (running) => {
    if (running) {
      now.value = new Date();
      startTicker();
    } else {
      stopTicker();
    }
  },
  { immediate: true },
);

onBeforeUnmount(stopTicker);

function handleClick() {
  emit('click');
}

function handleDragStart(event) {
  emit('drag-start', event);
}

function startTicker() {
  if (tickerId) return;
  tickerId = window.setInterval(() => {
    now.value = new Date();
  }, 30000);
}

function stopTicker() {
  if (!tickerId) return;
  window.clearInterval(tickerId);
  tickerId = null;
}
</script>

<style scoped>
.text-bg-transparent {
  background-color: transparent;
  color: var(--ds-text);
}

.layout-card {
  box-shadow: var(--ds-shadow-raised);
  transition:
    box-shadow 0.15s ease,
    transform 0.15s ease;
}

.layout-card:focus-visible {
  outline: 2px solid var(--bs-primary);
  outline-offset: 2px;
}

.layout-card:hover {
  box-shadow:
    var(--ds-shadow-raised),
    0 0 0 2px var(--ds-border-focused);
}

.layout-card:active {
  cursor: grabbing;
}

.trello-card-activity-pulse {
  animation: trello-card-activity-pulse 1.2s ease-in-out infinite;
}

@keyframes trello-card-activity-pulse {
  0%,
  100% {
    opacity: 0.4;
    transform: scale(1);
  }
  50% {
    opacity: 1;
    transform: scale(1.4);
  }
}
</style>
