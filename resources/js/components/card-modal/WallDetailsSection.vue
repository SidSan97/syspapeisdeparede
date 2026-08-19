<template>
  <section v-if="wall" class="wall-details-section d-flex gap-3 mb-4">
    <IconRuler size="32" class="py-1" />

    <div class="flex-fill">
      <header class="mb-3">
        <h3 class="fs-sm fw-bold text-body m-0">Detalhes da Parede</h3>
      </header>

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
              <td class="text-end">{{ formatStripCount(mainWallStripCount) }}</td>
              <td class="text-end">{{ formatDimensions(wall.strip_height) }}</td>
              <td>Inicial</td>
            </tr>
            <tr v-for="(continuation, index) in visibleContinuations" :key="`cont-${index}`">
              <td>{{ continuationRowLabel(continuation, index) }}</td>
              <td class="text-end">{{ formatDimensions(continuation.width) }}</td>
              <td class="text-end">{{ formatDimensions(continuation.height) }}</td>
              <td class="text-end">{{ formatStripCount(continuationStripCount(index)) }}</td>
              <td class="text-end">{{ formatDimensions(continuationStripHeight(index)) }}</td>
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
                  <span class="fw-semibold text-md-end mb-0">{{
                    installationDirectionsSummary
                  }}</span>
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

      <button class="btn btn-outline-default mt-2" @click="copyStripSummary">Copiar resumo</button>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import { useToast } from '@/composables/useToast';
import { useFormatting } from '@/composables/useFormatting';
import {
  calculateStrips,
  calculateStripHeight,
  calculateWallWithContinuations,
} from '@/utils/calculateStripsUtils.js';
import { copyBudgetSummaryText } from '@/utils/copyBudgetSummaryUtils';
import { formatCardId } from '@/utils/cardUtils';
import { IconRuler } from '@tabler/icons-vue';

const props = defineProps({
  wall: {
    type: Object,
    default: null,
  },
  card: {
    type: Object,
    default: null,
  },
});

const toast = useToast();
const { formatNumber } = useFormatting();

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

const wallSequence = computed(() => {
  const w = props.wall;
  if (!w || visibleContinuations.value.length === 0) {
    return null;
  }

  return calculateWallWithContinuations(w);
});

/** Faixas só da parede principal (sem somar continuações). */
const mainWallStripCount = computed(() => {
  const w = props.wall;
  if (!w) return null;

  if (wallSequence.value) {
    const strips = wallSequence.value.perPart?.[0]?.strips;
    return strips ?? null;
  }

  return w.strip_count;
});

const stripSummary = computed(() => {
  const w = props.wall;
  if (!w) return '';

  const groups = [];

  if (wallSequence.value) {
    wallSequence.value.perPart.forEach((part) => {
      const q = Number(part?.strips ?? 0);
      const h = Number(part?.stripHeight ?? 0);
      if (Number.isFinite(q) && q > 0 && Number.isFinite(h) && h > 0) {
        groups.push({ q, h });
      }
    });
  } else {
    const q0 = Number(w.strip_count);
    const h0 = Number(w.strip_height);
    if (Number.isFinite(q0) && q0 > 0 && Number.isFinite(h0) && h0 > 0) {
      groups.push({ q: q0, h: h0 });
    }
  }

  return groups.map((g) => `${g.q}F de ${formatNumber(g.h)}m`).join(' + ');
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

function continuationStripCount(index) {
  if (wallSequence.value) {
    return wallSequence.value.perPart?.[index + 1]?.strips ?? null;
  }

  const continuation = visibleContinuations.value[index];
  return continuation ? calculateStrips(continuation) : null;
}

function continuationStripHeight(index) {
  if (wallSequence.value) {
    return wallSequence.value.perPart?.[index + 1]?.stripHeight ?? null;
  }

  const continuation = visibleContinuations.value[index];
  return continuation ? calculateStripHeight(continuation) : null;
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

const stripSummaryCopyText = computed(() => {
  const summary = stripSummary.value || '';
  const card = props.card;

  if (!card) {
    return summary;
  }

  const orderId = formatCardId(card.order_id ?? card.order?.id);
  const bracketLabel =
    String(card.name || '').trim() ||
    [card.order?.name, card.wall?.room?.name || props.wall?.room?.name, props.wall?.name]
      .map((part) => String(part || '').trim())
      .filter(Boolean)
      .join(' - ');

  if (!bracketLabel) {
    return summary ? `${orderId} – ${summary}` : orderId;
  }

  return summary
    ? `${orderId} [${bracketLabel}] – ${summary}`
    : `${orderId} [${bracketLabel}]`;
});

async function copyStripSummary() {
  const ok = await copyBudgetSummaryText(stripSummaryCopyText.value || '');
  if (ok) {
    toast?.success?.('Resumo copiado para a área de transferência');
  } else {
    toast?.error?.('Não foi possível copiar o resumo.');
  }
}
</script>
