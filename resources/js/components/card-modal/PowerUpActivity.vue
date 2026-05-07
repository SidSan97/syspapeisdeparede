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

      <div v-if="!isCompleted" class="d-flex flex-wrap gap-2 align-items-center mb-2">
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

        <div class="vr d-none d-sm-block"></div>

        <button
          type="button"
          class="btn btn-outline-secondary btn-sm"
          :disabled="busyAdvance5 || !cardId"
          title="Avançar 5 minutos"
          @click="handleAdvance(300)"
        >
          <span
            v-if="busyAdvance5"
            class="spinner-border spinner-border-sm me-1"
            role="status"
          ></span>
          <IconPlus v-else :size="16" class="me-1" />
          5 min
        </button>
        <button
          type="button"
          class="btn btn-outline-secondary btn-sm"
          :disabled="busyAdvance15 || !cardId"
          title="Avançar 15 minutos"
          @click="handleAdvance(900)"
        >
          <span
            v-if="busyAdvance15"
            class="spinner-border spinner-border-sm me-1"
            role="status"
          ></span>
          <IconPlus v-else :size="16" class="me-1" />
          15 min
        </button>

        <button
          type="button"
          class="btn btn-outline-danger btn-sm ms-auto"
          :disabled="busyReset || !cardId || (totalSeconds === 0 && !isRunning)"
          @click="handleReset"
        >
          <span
            v-if="busyReset"
            class="spinner-border spinner-border-sm me-1"
            role="status"
          ></span>
          <IconRefresh v-else :size="16" class="me-1" />
          Reiniciar
        </button>
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
  IconPlus,
  IconRefresh,
} from '@tabler/icons-vue';

import { useDialog } from '@/composables/useDialog';
import { useToast } from '@/composables/useToast';
import { layoutService } from '@/services/layoutService';
import {
  formatActivityClock,
  getCardTotalSeconds,
  isCardActivityRunning,
  isCardCompleted,
  mergeActivityPayload,
} from '@/utils/layoutCardActivityUtils';

const props = defineProps({
  card: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['activity-updated']);

const toast = useToast();
const dialog = useDialog();

const busyStart = ref(false);
const busyPause = ref(false);
const busyReset = ref(false);
const busyAdvance5 = ref(false);
const busyAdvance15 = ref(false);

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

async function handleAdvance(seconds) {
  if (!cardId.value) return;
  const ref5 = seconds === 300;
  const busyRef = ref5 ? busyAdvance5 : busyAdvance15;
  if (busyRef.value) return;
  busyRef.value = true;
  try {
    const data = await layoutService.advanceActivity(cardId.value, seconds);
    applyPayload(data);
    toast.success(`+${Math.round(seconds / 60)} min adicionados.`);
  } catch (error) {
    toast.error(extractError(error, 'Não foi possível avançar o temporizador.'));
  } finally {
    busyRef.value = false;
  }
}

async function handleReset() {
  if (!cardId.value || busyReset.value) return;

  const confirmed = await dialog.confirmDelete({
    title: 'Reiniciar temporizador?',
    text: 'Todo o tempo registrado neste cartão será zerado.',
    confirmText: 'Sim, reiniciar',
  });

  if (!confirmed) return;

  busyReset.value = true;
  try {
    const data = await layoutService.resetActivity(cardId.value);
    applyPayload(data);
    toast.success('Temporizador reiniciado.');
  } catch (error) {
    toast.error(extractError(error, 'Não foi possível reiniciar o temporizador.'));
  } finally {
    busyReset.value = false;
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
