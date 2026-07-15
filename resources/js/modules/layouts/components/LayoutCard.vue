<template>
  <div
    class="card rounded-3 cursor-pointer layout-card"
    :class="{ 'border border-success border-2 shadow-sm': isCompleted, 'border-0': !isCompleted }"
    :draggable="true"
    @dragstart="$emit('drag-start', $event)"
    @click="$emit('click')"
  >
    <div v-if="coverImage" class="trello-card-image">
      <img :src="coverImage" :alt="card.name" />
    </div>
    <div class="pt-2 px-3 pb-1">
      <div class="trello-card-footer-content">
        <p class="text-truncate m-0">{{ displayName }}</p>
        <div
          v-if="isCompleted"
          class="d-flex align-items-center justify-content-start p-1 mt-1 rounded bg-success text-white small w-50"
          title="Card concluído"
        >
          <IconCircleCheck :size="18" stroke-width="2.5" />
          <span class="ms-1">Concluído</span>
        </div>
        <div class="trello-card-footer-meta">
          <div class="badge badge-custom fs-xs">
            <IconClock :size="16" />

            <span class="ps-1 pe-0.5"
              >{{ card.delivery_date_start }} - {{ card.delivery_date_end }}
            </span>
          </div>
          <div
            v-if="activityLabel"
            class="badge fs-xs trello-card-activity-badge d-inline-flex align-items-center gap-1"
            :class="activityIsRunning ? 'text-bg-danger' : 'text-bg-light text-body'"
            :title="
              activityIsRunning
                ? `Temporizador em execução · ${activityLabel}`
                : `Tempo registrado · ${activityLabel}`
            "
          >
            <span
              v-if="activityIsRunning"
              class="trello-card-activity-pulse"
              aria-hidden="true"
            ></span>
            <IconClockHour4 v-else :size="14" />
            <span>{{ activityLabel }}</span>
          </div>
          <div v-if="commentsCount > 0" class="trello-card-comment-count">
            <IconMessage :size="16" />

            <span>{{ commentsCount }}</span>
          </div>
          <div v-if="activitiesCount > 0" class="trello-card-activity-count">
            <IconList :size="16" />

            <span>{{ activitiesCount }}</span>
          </div>
          <div
            v-if="card.uploaded_files && card.uploaded_files.length > 0"
            class="trello-card-attachment-count"
          >
            <IconPaperclip :size="16" />

            <span>{{ card.uploaded_files.length }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
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

import {
  IconCircleCheck,
  IconClock,
  IconClockHour4,
  IconList,
  IconMessage,
  IconPaperclip,
} from '@tabler/icons-vue';

const props = defineProps({
  card: {
    type: Object,
    required: true,
  },
});

const displayName = computed(() => getCardDisplayName(props.card));
const coverImage = computed(() => getCoverImage(props.card));
const isCompleted = computed(() => isCardCompleted(props.card));
const activitiesCount = computed(() => getActivitiesCount(props.card));
const commentsCount = computed(() => getCommentsCount(props.card));

const now = ref(new Date());
let tickerId = null;

const activityIsRunning = computed(() => isCardActivityRunning(props.card));
const activityTotalSeconds = computed(() => getCardTotalSeconds(props.card, now.value));
const activityLabel = computed(() => {
  if (!activityIsRunning.value && activityTotalSeconds.value === 0) return '';
  return formatActivityCompact(activityTotalSeconds.value);
});

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

defineEmits(['drag-start', 'click']);
</script>

<style scoped>
.layout-card {
  box-shadow: var(--ds-shadow-raised);
}

.badge-custom {
  background-color: var(--ds-background-neutral);
  color: var(--ds-text);
  font-size: 0.6875rem;
  display: flex;
  align-items: center;
  height: 1.5rem;
  padding: 0.125rem;
  border-radius: 0.125rem;
}

.trello-card {
  background-color: var(--bs-card-bg);
  border: 1px solid var(--bs-border-color);
  border-radius: 0.5rem;
  margin-bottom: 0.5rem;
  cursor: pointer;
  box-shadow: var(--bs-box-shadow-sm);
  transition: all 0.2s ease;
  user-select: none;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.trello-card:hover {
  box-shadow: var(--bs-box-shadow);
  transform: translateY(-2px);
}

.trello-card:active {
  cursor: grabbing;
}

.trello-card-image {
  width: 100%;
  height: 150px;
  overflow: hidden;
  background-color: var(--bs-secondary-bg);
  flex-shrink: 0;
}

.trello-card-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.trello-card-footer {
  background-color: var(--ds-surface-sunken);
  color: var(--ds-text);
  padding: 0.5rem 0.75rem;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  flex-shrink: 0;
  min-height: 36px;
}

.trello-card-footer-content {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  width: 100%;
}

.trello-card-activity-badge {
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.02em;
  font-size: 0.6875rem;
  height: 1.5rem;
  padding: 0.125rem 0.375rem;
  border-radius: 0.125rem;
}

.trello-card-activity-pulse {
  display: inline-block;
  width: 0.375rem;
  height: 0.375rem;
  border-radius: 50%;
  background-color: currentColor;
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

.trello-card-footer-text {
  font-size: 0.75rem;
  color: var(--ds-text);
  font-weight: 700;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.trello-card-footer-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.trello-card-deadline {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.6875rem;
  color: var(--ds-text);
}

.trello-card-deadline i {
  color: var(--ds-text);
  font-size: 0.6875rem;
}

.trello-card-comment-count,
.trello-card-activity-count,
.trello-card-attachment-count {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.6875rem;
  color: var(--ds-text);
}

.trello-card-comment-count i,
.trello-card-activity-count i,
.trello-card-attachment-count i {
  color: var(--ds-text);
  font-size: 0.6875rem;
}

.trello-card-comment-count span,
.trello-card-activity-count span,
.trello-card-attachment-count span {
  font-weight: 500;
}
</style>
