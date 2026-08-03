<template>
  <div class="mb-4">
    <div class="d-flex align-items-center gap-3 mb-3">
      <IconCash :size="18" />
      <h2 class="mb-0 fs-sm fw-semibold">Pagamento</h2>
      <span v-if="!hasPendingAmounts && data.paid" class="badge text-bg-success">
        Pedido pago
      </span>
    </div>

    <div class="card-body">
      <div class="mb-3">
        <div class="text-muted small">Método de Pagamento</div>
        <div>
          {{ formatPaymentMethod(data.payment_method) }}
        </div>
      </div>
      <div v-if="data.installments" class="mb-3">
        <div class="text-muted small">Parcelas</div>
        <div>{{ data.installments }}x</div>
      </div>
      <div v-if="activePaymentLinkUrl && hasPendingAmounts" class="mb-3">
        <div
          v-if="isPaymentLinkExpired(activePaymentLinkExpirationDate)"
          class="alert alert-warning mb-2"
        >
          <IconAlertTriangle class="me-2" />

          Link de pagamento expirado. Gerando novo link...
        </div>
        <div class="text-muted small mb-2">Link de Pagamento</div>
        <div>
          <a
            :href="activePaymentLinkUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-default btn-sm"
            :class="{
              disabled: isPaymentLinkExpired(activePaymentLinkExpirationDate),
            }"
          >
            <IconExternalLink :size="16" class="me-2" />

            Acessar link de pagamento
          </a>
          <button
            type="button"
            class="btn btn-default btn-sm ms-2"
            @click="copyPaymentUrl"
            title="Copiar link"
            :disabled="isPaymentLinkExpired(activePaymentLinkExpirationDate)"
          >
            <IconCopy :size="16" />
          </button>
        </div>
        <div v-if="activePaymentLinkExpirationDate" class="text-muted small mt-2">
          <IconClock :size="16" class="me-1" />

          <span
            :class="{
              'text-danger': isPaymentLinkExpired(activePaymentLinkExpirationDate),
            }"
          >
            {{
              isPaymentLinkExpired(activePaymentLinkExpirationDate)
                ? 'Expirado em: '
                : 'Expira em: '
            }}
            {{ formatDate(activePaymentLinkExpirationDate) }}
          </span>
        </div>
      </div>

      <div v-if="hasPaymentTableData" class="mb-3">
        <div class="text-muted small mb-2">Histórico de pagamentos</div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0 small">
            <thead>
              <tr>
                <th>Item</th>
                <th>Valor</th>
                <th>Método</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in paymentLinksRows" :key="row.key">
                <td>{{ row.item }}</td>
                <td>{{ formatMoney(row.amount_total) }}</td>
                <td>
                  {{ row.payment_method ? formatPaymentMethod(row.payment_method) : '-' }}
                </td>
                <td>
                  <span
                    class="badge"
                    :class="row.status === 'paid' ? 'text-bg-success' : 'text-bg-warning'"
                  >
                    {{ row.statusLabel }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="hasPaymentTableData" class="mb-3">
        <div class="text-muted small mb-2">Links gerados</div>
        <div class="small text-muted">
          {{ paidStatusText }}
        </div>
      </div>
      <div v-if="isOrder && hasPendingAmounts" class="mb-0">
        <button
          type="button"
          class="btn btn-outline-default"
          @click="handleGeneratePaymentLink"
          :disabled="generatingPaymentLink"
        >
          <span
            v-if="generatingPaymentLink"
            class="spinner-border spinner-border-sm me-2"
            role="status"
            aria-hidden="true"
          ></span>
          <IconCreditCard v-else :size="18" class="me-2" />

          {{ generatingPaymentLink ? 'Gerando...' : 'Fazer pagamento' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

// Icons
import {
  IconAlertTriangle,
  IconCircleCheck,
  IconClock,
  IconCopy,
  IconCreditCard,
  IconExclamationCircle,
  IconExternalLink,
} from '@tabler/icons-vue';

import { useToast } from '@/composables/useToast';
import { useFormatting } from '@/composables/useFormatting';
import { IconCash } from '@tabler/icons-vue';

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
  isOrder: {
    type: Boolean,
    default: false,
  },
  generatingPaymentLink: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['generate-payment-link']);

const toast = useToast();
const { formatPaymentMethod, formatDate } = useFormatting();
const moneyFormatter = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL',
});

const paidLinksCount = computed(
  () => (props.data?.payment_links || []).filter((link) => link.status === 'paid').length,
);
const pendingPaymentLink = computed(() => {
  const links = Array.isArray(props.data?.payment_links) ? props.data.payment_links : [];
  const pending = [...links].reverse().find((link) => link.status !== 'paid');

  return pending || null;
});
const activePaymentLinkUrl = computed(() => {
  if (pendingPaymentLink.value?.payment_url) {
    return pendingPaymentLink.value.payment_url;
  }
  return null;
});
const activePaymentLinkExpirationDate = computed(() => {
  if (pendingPaymentLink.value?.expires_at) {
    return pendingPaymentLink.value.expires_at;
  }

  if (!Array.isArray(props.data?.payment_links) || props.data.payment_links.length === 0) {
    return props.data?.payment_expiration_date || null;
  }

  return null;
});
const hasPaymentTableData = computed(() => Boolean(props.data?.payment_breakdown?.base));
const hasPendingAmounts = computed(() => {
  const remaining = props.data?.payment_breakdown?.remaining || {};
  const method = props.data?.payment_method === 'pix' ? 'PRODUTOS_PIX' : 'PRODUTOS_CREDIT_CARD';

  return (
    Number(remaining.ARTES || 0) > 0 ||
    Number(remaining[method] || 0) > 0 ||
    Number(remaining.FRETE || 0) > 0
  );
});
const paymentLinksRows = computed(() => {
  const links = props.data?.payment_links || [];
  const breakdown = props.data?.payment_breakdown || {};
  const base = breakdown.base || {};
  const paid = breakdown.paid || {};
  const remaining = breakdown.remaining || {};

  const productsAmount =
    props.data?.payment_method === 'pix'
      ? Number(base.PRODUTOS_PIX || 0)
      : Number(base.PRODUTOS_CREDIT_CARD || 0);
  const productsRemaining =
    props.data?.payment_method === 'pix'
      ? Number(remaining.PRODUTOS_PIX || 0)
      : Number(remaining.PRODUTOS_CREDIT_CARD || 0);

  const resolveMethod = (componentName) => {
    const match = [...links]
      .reverse()
      .find((link) => Array.isArray(link.components) && link.components.includes(componentName));
    return match?.payment_method || null;
  };
  const getAdjustmentTotals = (componentName) => {
    const totals = {
      paid: 0,
      pending: 0,
      method: null,
    };

    links.forEach((link) => {
      const adjustmentComponents = Array.isArray(link.adjustment_components)
        ? link.adjustment_components
        : [];
      if (!adjustmentComponents.includes(componentName)) {
        return;
      }

      const value =
        componentName === 'ARTES'
          ? Number(link.amount_artes || 0)
          : componentName === 'PRODUTOS'
            ? Number(link.amount_produtos || 0)
            : Number(link.amount_frete || 0);

      if (value <= 0) {
        return;
      }

      totals.method = totals.method || link.payment_method || null;
      if (link.status === 'paid') {
        totals.paid += value;
      } else {
        totals.pending += value;
      }
    });

    return totals;
  };

  const rows = [];
  const pushComponentRows = (label, componentKey, baseAmount, paidAmount, remainingAmount) => {
    const amountBase = Number(baseAmount || 0);
    const amountPaid = Number(paidAmount || 0);
    const amountRemaining = Number(remainingAmount || 0);
    const method = resolveMethod(componentKey);
    const lower = label.toLowerCase();
    const adjustmentTotals = getAdjustmentTotals(componentKey);
    const paidWithoutAdjustments = Math.max(0, amountPaid - adjustmentTotals.paid);
    const adjustmentOpen = Math.max(0, amountRemaining - adjustmentTotals.pending);

    if (paidWithoutAdjustments > 0) {
      rows.push({
        key: `${componentKey}-paid`,
        item: label,
        amount_total: paidWithoutAdjustments,
        payment_method: method,
        status: 'paid',
        statusLabel: 'Pago',
      });
    } else {
      rows.push({
        key: `${componentKey}-base`,
        item: label,
        amount_total: amountBase,
        payment_method: method,
        status: amountRemaining <= 0 ? 'paid' : 'pending',
        statusLabel: amountRemaining <= 0 ? 'Pago' : 'Pendente',
      });
    }

    const hasAdjustmentHistory =
      amountPaid > 0 || adjustmentTotals.paid > 0 || adjustmentTotals.pending > 0;
    const adjustmentValue = adjustmentTotals.paid + adjustmentOpen + adjustmentTotals.pending;
    if (hasAdjustmentHistory && adjustmentValue > 0) {
      const adjustmentStatus =
        adjustmentOpen <= 0 && adjustmentTotals.pending <= 0 ? 'paid' : 'pending';
      rows.push({
        key: `${componentKey}-adjustment`,
        item: `Ajustes ${lower}`,
        amount_total: adjustmentValue,
        payment_method: adjustmentTotals.method,
        status: adjustmentStatus,
        statusLabel: adjustmentStatus === 'paid' ? 'Pago' : 'Pendente',
      });
    }
  };

  pushComponentRows(
    'Artes',
    'ARTES',
    Number(base.ARTES || 0),
    Number(paid.ARTES || 0),
    Number(remaining.ARTES || 0),
  );
  pushComponentRows(
    'Produtos',
    'PRODUTOS',
    productsAmount,
    Number(paid.PRODUTOS || 0),
    productsRemaining,
  );
  pushComponentRows(
    'Frete',
    'FRETE',
    Number(base.FRETE || 0),
    Number(paid.FRETE || 0),
    Number(remaining.FRETE || 0),
  );

  return rows;
});

const tableHasRowsWithMethod = computed(() =>
  paymentLinksRows.value.some((row) => row.payment_method),
);
const hasPendingAdjustmentRows = computed(() =>
  paymentLinksRows.value.some(
    (row) =>
      typeof row.item === 'string' &&
      row.item.toLowerCase().startsWith('ajustes') &&
      row.status === 'pending',
  ),
);
const paidStatusText = computed(() => {
  if (!tableHasRowsWithMethod.value) {
    return 'Nenhum link gerado ainda';
  }
  return `${paidLinksCount.value} pago(s) de ${dataLinksCount.value} link(s)`;
});
const dataLinksCount = computed(() => (props.data?.payment_links || []).length);

function formatMoney(value) {
  return moneyFormatter.format(Number(value || 0));
}

function isPaymentLinkExpired(expirationDate) {
  if (!expirationDate) {
    return false;
  }

  try {
    const expiration = new Date(expirationDate);
    const now = new Date();
    return expiration < now;
  } catch (error) {
    console.error('Erro ao verificar expiração do link:', error);
    return false;
  }
}

function copyPaymentUrl() {
  if (!activePaymentLinkUrl.value) {
    return;
  }

  try {
    const textArea = document.createElement('textarea');
    textArea.value = activePaymentLinkUrl.value;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    const successful = document.execCommand('copy');
    document.body.removeChild(textArea);

    if (successful) {
      toast.info('Link copiado!');
    } else {
      throw new Error('Falha ao copiar');
    }
  } catch (error) {
    toast.error('Não foi possível copiar o link.');
  }
}

async function handleGeneratePaymentLink() {
  emit('generate-payment-link');
}
</script>

<style scoped>
.card.border-success.border-2 {
  box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.15);
  border-color: var(--bs-success) !important;
}

.card.border-warning.border-2 {
  box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.2);
  border-color: var(--bs-warning) !important;
}
</style>
