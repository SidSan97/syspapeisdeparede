<template>
    <div class="card mb-4" :class="cardBorderClass">
        <div class="card-header bg-transparent" :class="cardHeaderClass">
            <h5 class="mb-0 fw-semibold">
                <i v-if="data.payment_status === 'paid'" class="fa fa-check-circle text-success me-2"></i>
                <i v-else-if="data.payment_status === 'partial'" class="fa fa-exclamation-circle text-warning me-2"></i>
                Pagamento
            </h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <div class="text-muted small">Método de Pagamento</div>
                <div class="fw-semibold">{{ formatPaymentMethod(data.payment_method) }}</div>
            </div>
            <div v-if="data.installments" class="mb-3">
                <div class="text-muted small">Parcelas</div>
                <div class="fw-semibold">{{ data.installments }}x</div>
            </div>
            <div v-if="data.paid" class="mb-3">
                <div class="alert alert-success mb-0">
                    <i class="fa fa-check-circle me-2"></i>
                    Pedido já está pago
                </div>
            </div>
            <div v-if="activePaymentLinkUrl && data.paid == 0" class="mb-3">
                <div v-if="isPaymentLinkExpired(activePaymentLinkExpirationDate)" class="alert alert-warning mb-2">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    Link de pagamento expirado. Gerando novo link...
                </div>
                <div class="text-muted small mb-2">Link de Pagamento</div>
                <div>
                    <a
                        :href="activePaymentLinkUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-primary btn-sm"
                        :class="{ 'disabled': isPaymentLinkExpired(activePaymentLinkExpirationDate) }"
                    >
                        <i class="fa fa-external-link fa-fw me-2"></i>
                        Acessar Link de Pagamento
                    </a>
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm ms-2"
                        @click="copyPaymentUrl"
                        title="Copiar link"
                        :disabled="isPaymentLinkExpired(activePaymentLinkExpirationDate)"
                    >
                        <i class="fa fa-copy fa-fw"></i>
                    </button>
                </div>
                <div v-if="activePaymentLinkExpirationDate" class="text-muted small mt-2">
                    <i class="fa fa-clock me-1"></i>
                    <span :class="{ 'text-danger': isPaymentLinkExpired(activePaymentLinkExpirationDate) }">
                        {{ isPaymentLinkExpired(activePaymentLinkExpirationDate) ? 'Expirado em: ' : 'Expira em: ' }}
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
                                <td>{{ row.payment_method ? formatPaymentMethod(row.payment_method) : '-' }}</td>
                                <td>
                                    <span class="badge" :class="row.status === 'paid' ? 'text-bg-success' : 'text-bg-warning'">
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
            <div v-if="isOrder && (!Number(data.paid) || hasOutstandingPaymentBalance)" class="mb-0">
                <button
                    type="button"
                    class="btn btn-primary"
                    @click="handleGeneratePaymentLink"
                    :disabled="generatingPaymentLink"
                >
                    <span
                        v-if="generatingPaymentLink"
                        class="spinner-border spinner-border-sm me-2"
                        role="status"
                        aria-hidden="true"
                    ></span>
                    <i v-else class="fa fa-credit-card me-2"></i>
                    {{ generatingPaymentLink ? 'Gerando...' : (Number(data.paid) && hasOutstandingPaymentBalance ? 'Pagar ajuste' : 'Pagar') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useFormatting } from '@/composables/useFormatting';

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

const { formatPaymentMethod, formatDate } = useFormatting();
const moneyFormatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

const paidLinksCount = computed(() => (props.data?.payment_links || []).filter((link) => link.status === 'paid').length);
const pendingPaymentLink = computed(() => {
    const links = Array.isArray(props.data?.payment_links) ? props.data.payment_links : [];
    const pending = [...links]
        .reverse()
        .find((link) => link.status !== 'paid');

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

/** Saldo ainda não coberto pelos links pagos (ex.: ajuste de frete/metragem após pedido quitado). */
const hasOutstandingPaymentBalance = computed(() => {
    const remaining = props.data?.payment_breakdown?.remaining;
    if (!remaining) {
        return false;
    }
    const isPix = props.data?.payment_method === 'pix';
    const produtosRemaining = isPix
        ? Number(remaining.PRODUTOS_PIX || 0)
        : Number(remaining.PRODUTOS_CREDIT_CARD || 0);
    return (
        Number(remaining.ARTES || 0) > 0
        || produtosRemaining > 0
        || Number(remaining.FRETE || 0) > 0
    );
});
const paymentLinksRows = computed(() => {
    const links = props.data?.payment_links || [];
    const breakdown = props.data?.payment_breakdown || {};
    const base = breakdown.base || {};
    const remaining = breakdown.remaining || {};
    const paidByComponent = breakdown.paid || {};

    const isPix = props.data?.payment_method === 'pix';
    const productsBase = isPix
        ? Number(base.PRODUTOS_PIX || 0)
        : Number(base.PRODUTOS_CREDIT_CARD || 0);
    const productsRemaining = isPix
        ? Number(remaining.PRODUTOS_PIX || 0)
        : Number(remaining.PRODUTOS_CREDIT_CARD || 0);
    const productsPaid = Number(paidByComponent.PRODUTOS || 0);

    const artesBase = Number(base.ARTES || 0);
    const artesRemaining = Number(remaining.ARTES || 0);
    const artesPaid = Number(paidByComponent.ARTES || 0);

    const freteBase = Number(base.FRETE || 0);
    const freteRemaining = Number(remaining.FRETE || 0);
    const fretePaid = Number(paidByComponent.FRETE || 0);

    const resolveMethod = (componentName) => {
        const match = [...links].reverse().find((link) => Array.isArray(link.components) && link.components.includes(componentName));
        return match?.payment_method || null;
    };

    const resolveMethodPaid = (componentName) => {
        let last = null;
        for (const link of links) {
            if (link.status === 'paid' && Array.isArray(link.components) && link.components.includes(componentName)) {
                last = link;
            }
        }
        return last?.payment_method || null;
    };

    const rows = [];

    if (artesPaid > 0 && artesRemaining > 0) {
        rows.push({
            key: 'artes-paid',
            item: 'Artes',
            amount_total: artesPaid,
            payment_method: resolveMethodPaid('ARTES'),
            status: 'paid',
            statusLabel: 'Pago',
        });
        rows.push({
            key: 'artes-diferenca',
            item: 'Ajuste de Artes',
            amount_total: artesRemaining,
            payment_method: resolveMethod('ARTES'),
            status: 'pending',
            statusLabel: 'Pendente',
        });
    } else {
        rows.push({
            key: 'artes',
            item: 'Artes',
            amount_total: artesBase,
            payment_method: resolveMethod('ARTES'),
            status: artesRemaining <= 0 ? 'paid' : 'pending',
            statusLabel: artesRemaining <= 0 ? 'Pago' : 'Pendente',
        });
    }

    if (productsPaid > 0 && productsRemaining > 0) {
        rows.push({
            key: 'produtos-paid',
            item: 'Produtos',
            amount_total: productsPaid,
            payment_method: resolveMethodPaid('PRODUTOS'),
            status: 'paid',
            statusLabel: 'Pago',
        });
        rows.push({
            key: 'produtos-diferenca',
            item: 'Diferença de Produtos',
            amount_total: productsRemaining,
            payment_method: resolveMethod('PRODUTOS'),
            status: 'pending',
            statusLabel: 'Pendente',
        });
    } else {
        rows.push({
            key: 'produtos',
            item: 'Produtos',
            amount_total: productsBase,
            payment_method: resolveMethod('PRODUTOS'),
            status: productsRemaining <= 0 ? 'paid' : 'pending',
            statusLabel: productsRemaining <= 0 ? 'Pago' : 'Pendente',
        });
    }

    if (fretePaid > 0 && freteRemaining > 0) {
        rows.push({
            key: 'frete-paid',
            item: 'Frete',
            amount_total: fretePaid,
            payment_method: resolveMethodPaid('FRETE'),
            status: 'paid',
            statusLabel: 'Pago',
        });
        rows.push({
            key: 'frete-diferenca',
            item: 'Ajuste de Frete',
            amount_total: freteRemaining,
            payment_method: resolveMethod('FRETE'),
            status: 'pending',
            statusLabel: 'Pendente',
        });
    } else {
        rows.push({
            key: 'frete',
            item: 'Frete',
            amount_total: freteBase,
            payment_method: resolveMethod('FRETE'),
            status: freteRemaining <= 0 ? 'paid' : 'pending',
            statusLabel: freteRemaining <= 0 ? 'Pago' : 'Pendente',
        });
    }

    return rows;
});

const tableHasRowsWithMethod = computed(() => paymentLinksRows.value.some((row) => row.payment_method));
const paidStatusText = computed(() => {
    if (!tableHasRowsWithMethod.value) {
        return 'Nenhum link gerado ainda';
    }
    return `${paidLinksCount.value} pago(s) de ${dataLinksCount.value} link(s)`;
});
const dataLinksCount = computed(() => (props.data?.payment_links || []).length);
const cardBorderClass = computed(() => {
    if (props.data?.payment_status === 'paid') {
        return 'border-success border-2';
    }

    if (props.data?.payment_status === 'partial') {
        return 'border-warning border-2';
    }

    return '';
});
const cardHeaderClass = computed(() => {
    if (props.data?.payment_status === 'paid') {
        return 'bg-success-subtle';
    }

    if (props.data?.payment_status === 'partial') {
        return 'bg-warning-subtle';
    }

    return '';
});

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
            window.Toast.fire({
                icon: 'success',
                title: 'Link copiado!',
            });
        } else {
            throw new Error('Falha ao copiar');
        }
    } catch (error) {
        window.Toast.fire({
            icon: 'error',
            title: 'Não foi possível copiar o link.',
        });
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

