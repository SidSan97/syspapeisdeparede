<template>
  <section class="power-up-activity d-flex gap-3 mb-4">
    <IconClockHour4 :size="32" class="py-1 text-body-secondary" />

    <div class="flex-fill">
      <header class="d-flex justify-content-between align-items-center mb-2">
        <h3 class="fs-sm fw-bold text-body m-0">Atividade</h3>

        <span
          v-if="isCompleted"
          class="badge text-bg-success d-inline-flex align-items-center gap-1"
        >
          <IconCircleCheck :size="14" />
          Concluído
        </span>
        <span
          v-else-if="isRunning"
          class="badge text-bg-danger d-inline-flex align-items-center gap-1"
        >
          <span class="power-up-activity-pulse" aria-hidden="true"></span>
          Em execução
        </span>
        <span v-else-if="totalSeconds > 0" class="badge text-bg-success">Pausado</span>
        <span v-else class="badge text-bg-secondary">Sem registro</span>
      </header>

      <div class="d-flex align-items-baseline gap-2 mb-3">
        <span
          class="power-up-activity-clock fw-semibold"
          :class="clockColorClass"
          :title="
            isCompleted
              ? `Duração final registrada: ${clockLabel}`
              : `Tempo total registrado neste cartão: ${clockLabel}`
          "
        >
          {{ clockLabel }}
        </span>
        <small class="text-body-secondary">
          {{ isCompleted ? 'duração final' : 'tempo registrado' }}
        </small>
      </div>

      <div v-if="!isCompleted" class="mb-2">
        <button
          v-if="!isRunning"
          type="button"
          class="btn btn-success btn-sm d-inline-flex align-items-center"
          :disabled="busyStart || !cardId"
          @click="handleStart"
        >
          <span
            v-if="busyStart"
            class="spinner-border spinner-border-sm me-1"
            role="status"
          ></span>
          <IconPlayerPlayFilled v-else :size="18" class="me-1" />
          Iniciar
        </button>
        <button
          v-else
          type="button"
          class="btn btn-danger btn-sm d-inline-flex align-items-center"
          :disabled="busyPause || !cardId"
          @click="handlePause"
        >
          <span
            v-if="busyPause"
            class="spinner-border spinner-border-sm me-1"
            role="status"
          ></span>
          <IconPlayerPauseFilled v-else :size="18" class="me-1" />
          Pausar
        </button>
      </div>

      <div
        v-if="sessionRows.length > 0"
        class="power-up-activity-sessions small border rounded p-2 mb-2"
      >
        <div class="fw-semibold text-body-secondary mb-1">Data</div>
        <ul class="list-unstyled mb-2">
          <li
            v-for="row in sessionRows"
            :key="row.id"
            class="power-up-activity-session-row text-body"
          >
            {{ row.label }}
          </li>
        </ul>
        <div class="fw-semibold text-body border-top pt-2">
          Tempo Total: {{ sessionsTotalLabel }}
        </div>
      </div>

      <p
        v-if="isCompleted"
        class="small text-body-secondary m-0 d-flex align-items-center gap-1"
      >
        <IconLock :size="14" />
        Temporizador bloqueado: o card foi marcado como concluído.
      </p>
      <p
        v-else-if="isRunning && runningSinceLabel"
        class="small text-body-secondary m-0"
      >
        Em execução desde {{ runningSinceLabel }}.
      </p>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';

import {
  IconCircleCheck,
  IconClockHour4,
  IconLock,
  IconPlayerPauseFilled,
  IconPlayerPlayFilled,
} from '@tabler/icons-vue';

import { useToast } from '@/composables/useToast';
import { layoutService } from '@/services/layoutService';
import {
  formatActivityClock,
  formatActivitySessionDate,
  formatActivitySessionDuration,
  getCardTotalSeconds,
  isCardActivityRunning,
  isCardCompleted,
  mergeActivityPayload,
  sumActivitySessionsSeconds,
} from '@/utils/layoutCardActivityUtils';

const props = defineProps({
  card: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['activity-updated']);

const toast = useToast();

const busyStart = ref(false);
const busyPause = ref(false);

const now = ref(new Date());
let tickerId = null;

const cardId = computed(() => props.card?.id ?? null);
const isCompleted = computed(() => isCardCompleted(props.card));
const isRunning = computed(() => !isCompleted.value && isCardActivityRunning(props.card));
const totalSeconds = computed(() => getCardTotalSeconds(props.card, now.value));
const clockLabel = computed(() => formatActivityClock(totalSeconds.value));
const clockColorClass = computed(() => {
  if (isCompleted.value) return 'text-success';
  return isRunning.value ? 'text-danger' : 'text-body';
});

const sessionRows = computed(() => {
  const sessions = props.card?.activity_sessions;
  if (!Array.isArray(sessions)) {
    return [];
  }

  return sessions.map((session) => {
    const dateLabel = formatActivitySessionDate(session.ended_at);
    const durationLabel = formatActivitySessionDuration(session.duration_seconds);

    return {
      id: session.id,
      label: `${dateLabel} – ${durationLabel}`,
    };
  });
});

const sessionsTotalLabel = computed(() => {
  const seconds = sumActivitySessionsSeconds(props.card?.activity_sessions);
  return formatActivitySessionDuration(seconds);
});

const runningSinceLabel = computed(() => {
  if (!props.card?.activity_running_since) return '';
  const date = new Date(props.card.activity_running_since);
  if (Number.isNaN(date.getTime())) return '';
  return date.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
});

function startTicker() {
  if (tickerId) return;
  tickerId = window.setInterval(() => {
    now.value = new Date();
  }, 1000);
}

function stopTicker() {
  if (!tickerId) return;
  window.clearInterval(tickerId);
  tickerId = null;
}

watch(
  isRunning,
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

function applyPayload(payload) {
  const merged = mergeActivityPayload(props.card, payload);
  emit('activity-updated', {
    activity_running_since: merged.activity_running_since,
    activity_elapsed_seconds: merged.activity_elapsed_seconds,
    activity_total_seconds: merged.activity_total_seconds,
    activity_is_running: merged.activity_is_running,
    activity_sessions: merged.activity_sessions,
    completed_at: merged.completed_at,
    is_completed: merged.is_completed,
  });
  now.value = new Date();
}

async function handleStart() {
  if (!cardId.value || busyStart.value) return;
  busyStart.value = true;
  try {
    const data = await layoutService.startActivity(cardId.value);
    applyPayload(data);
    toast.success('Temporizador iniciado.');
  } catch (error) {
    toast.error(extractError(error, 'Não foi possível iniciar o temporizador.'));
  } finally {
    busyStart.value = false;
  }
}

async function handlePause() {
  if (!cardId.value || busyPause.value) return;
  busyPause.value = true;
  try {
    const data = await layoutService.pauseActivity(cardId.value);
    applyPayload(data);
    toast.success('Temporizador pausado.');
  } catch (error) {
    toast.error(extractError(error, 'Não foi possível pausar o temporizador.'));
  } finally {
    busyPause.value = false;
  }
}

function extractError(error, fallback) {
  return error?.response?.data?.message || error?.message || fallback;
}
</script>

<style scoped>
.power-up-activity-clock {
  font-variant-numeric: tabular-nums;
  font-size: 1.5rem;
  letter-spacing: 0.05em;
}

.power-up-activity-session-row {
  font-variant-numeric: tabular-nums;
  line-height: 1.6;
}

.power-up-activity-pulse {
  display: inline-block;
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  background-color: currentColor;
  animation: power-up-activity-pulse 1.2s ease-in-out infinite;
}

@keyframes power-up-activity-pulse {
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
