<template>
  <div v-if="wall" class="wall-details-section mb-4">
    <h3 class="fs-6 fw-semibold text-body mb-3 d-flex align-items-center gap-2">
      <IconRuler />

      Detalhes da Parede
    </h3>

    <div class="table-responsive">
      <table class="table table-sm table-striped table-bordered table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th scope="col">Parede</th>
            <th scope="col" class="text-end text-nowrap">Largura (m)</th>
            <th scope="col" class="text-end text-nowrap">Altura (m)</th>
            <th scope="col" class="text-end text-nowrap">Qtd. Faixas</th>
            <th scope="col" class="text-end text-nowrap">Alt. Faixas (m)</th>
            <th scope="col">Encaixe</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>{{ wall.name || 'Não informado' }}</td>
            <td class="text-end">{{ formatDimensions(wall.width) }}</td>
            <td class="text-end">{{ formatDimensions(wall.height) }}</td>
            <td class="text-end">{{ formatStripCount(wall.strip_count) }}</td>
            <td class="text-end">{{ formatDimensions(wall.strip_height) }}</td>
            <td>Inicial</td>
          </tr>
          <tr v-for="(continuation, index) in visibleContinuations" :key="`cont-${index}`">
            <td>{{ continuationRowLabel(continuation, index) }}</td>
            <td class="text-end">{{ formatDimensions(continuation.width) }}</td>
            <td class="text-end">{{ formatDimensions(continuation.height) }}</td>
            <td class="text-end">{{ calculateStrips(continuation) }}</td>
            <td class="text-end">{{ formatDimensions(calculateStripHeight(continuation)) }}</td>
            <td>{{ continuationFitDisplay(continuation) }}</td>
          </tr>
        </tbody>
        <tfoot v-if="installationDirectionsSummary" class="table-group-divider">
          <tr>
            <td colspan="6" class="bg-body-secondary">
              <div
                class="d-flex flex-column flex-md-row flex-wrap justify-content-md-between align-items-baseline gap-2"
              >
                <span class="fw-semibold mb-0">Sentido de instalação:</span>
                <span class="fw-semibold text-md-end mb-0">{{ installationDirectionsSummary }}</span>
              </div>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div v-if="stripSummary" class="mt-3 small text-muted">
      <strong class="text-body">Resumo de Faixas:</strong>
      {{ stripSummary }}
    </div>

    <button class="btn btn-default mt-2" @click="copyStripSummary">Copiar resumo</button>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useToast } from '@/composables/useToast';
import { useFormatting } from '@/composables/useFormatting';
import { calculateStrips, calculateStripHeight } from '@/utils/calculateStripsUtils.js';
import { copyBudgetSummaryText } from '@/utils/copyBudgetSummaryUtils';
import { IconRuler } from '@tabler/icons-vue';

const props = defineProps({
  wall: {
    type: Object,
    default: null,
  },
});

const toast = useToast();
const { formatNumber } = useFormatting();

const stripSummary = computed(() => {
  const w = props.wall;
  if (!w) return '';

  const groups = [];

  const q0 = Number(w.strip_count);
  const h0 = Number(w.strip_height);
  if (Number.isFinite(q0) && q0 > 0 && Number.isFinite(h0) && h0 > 0) {
    groups.push({ q: q0, h: h0 });
  }

  visibleContinuations.value.forEach((continuation) => {
    const q = Number(calculateStrips(continuation));
    const h = Number(calculateStripHeight(continuation));
    if (Number.isFinite(q) && q > 0 && Number.isFinite(h) && h > 0) {
      groups.push({ q, h });
    }
  });

  return groups.map((g) => `${g.q}F de ${formatNumber(g.h)}m`).join(' + ');
});

const visibleContinuations = computed(() => {
  const w = props.wall;
  if (
    !w ||
    !(w.continue_same_art || w.continueSameArt) ||
    !Array.isArray(w.continuations) ||
    !w.continuations.length
  ) {
    return [];
  }
  return w.continuations.filter((item) => item != null && typeof item === 'object');
});

function formatDimensions(value) {
  if (value === null || value === undefined || value === '') {
    return '-';
  }
  const n = Number(value);
  return Number.isFinite(n) ? formatNumber(n) : '-';
}

function formatStripCount(count) {
  if (count === null || count === undefined || count === '') {
    return '-';
  }
  const n = Number(count);
  return Number.isFinite(n) && n >= 0 ? String(n) : '-';
}

function continuationRowLabel(continuation, index) {
  const raw = continuation?.name;
  const name =
    typeof raw === 'string' && raw.trim().length ? raw.trim() : `Continuação ${index + 1}`;
  return name;
}

function continuationFitDisplay(continuation) {
  const raw = continuation?.fit ?? continuation?.Fit;
  if (raw == null) {
    return '-';
  }
  const s = String(raw).trim();
  return s.length ? s : '-';
}

function directionInstallLabel(direction) {
  if (direction === 'left-to-right') {
    return 'Esquerda para direita das paredes';
  }
  if (direction === 'right-to-left') {
    return 'Direita para a esquerda das paredes';
  }
  return '';
}

const installationDirectionsSummary = computed(() => {
  const w = props.wall;
  const legacyFromContinuation = visibleContinuations.value[0]?.direction;
  const direction = w?.direction ?? w?.Direction ?? legacyFromContinuation;
  const label = directionInstallLabel(direction);
  return label || '';
});

async function copyStripSummary() {
  const ok = await copyBudgetSummaryText(stripSummary.value || '');
  if (ok) {
    toast?.success?.('Resumo copiado para a área de transferência');
  } else {
    toast?.error?.('Não foi possível copiar o resumo.');
  }
}
</script>
