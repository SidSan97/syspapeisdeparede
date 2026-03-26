<template>
    <div class="card mb-4" :class="{ 'border-success border-2': data.link_payment || data.paid }">
        <div class="card-header bg-transparent" :class="{ 'bg-success-subtle': data.link_payment || data.paid }">
            <h5 class="mb-0 fw-semibold">
                <i v-if="data.link_payment || data.paid" class="fa fa-check-circle text-success me-2"></i>
                Pagamento
            </h5>
        </div>
        <div class="card-body">
            <div v-if="data.payment_breakdown" class="mb-3">
                <div class="text-muted small mb-2">Saldo por componente</div>
                <div class="small">
                    <div class="d-flex justify-content-between">
                        <span>ARTES</span>
                        <span>{{ formatMoney(data.payment_breakdown.remaining?.ARTES || 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>PRODUTOS</span>
                        <span>{{ formatMoney(remainingProductsValue) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>FRETE</span>
                        <span>{{ formatMoney(data.payment_breakdown.remaining?.FRETE || 0) }}</span>
                    </div>
                </div>
            </div>

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
            <div v-if="data.link_payment && data.paid == 0" class="mb-3">
                <div v-if="isPaymentLinkExpired(data.payment_expiration_date)" class="alert alert-warning mb-2">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    Link de pagamento expirado. Gerando novo link...
                </div>
                <div class="text-muted small mb-2">Link de Pagamento</div>
                <div>
                    <a
                        :href="data.link_payment"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-primary btn-sm"
                        :class="{ 'disabled': isPaymentLinkExpired(data.payment_expiration_date) }"
                    >
                        <i class="fa fa-external-link fa-fw me-2"></i>
                        Acessar Link de Pagamento
                    </a>
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm ms-2"
                        @click="copyPaymentUrl"
                        title="Copiar link"
                        :disabled="isPaymentLinkExpired(data.payment_expiration_date)"
                    >
                        <i class="fa fa-copy fa-fw"></i>
                    </button>
                </div>
                <div v-if="data.payment_expiration_date" class="text-muted small mt-2">
                    <i class="fa fa-clock me-1"></i>
                    <span :class="{ 'text-danger': isPaymentLinkExpired(data.payment_expiration_date) }">
                        {{ isPaymentLinkExpired(data.payment_expiration_date) ? 'Expirado em: ' : 'Expira em: ' }}
                        {{ formatDate(data.payment_expiration_date) }}
                    </span>
                </div>
            </div>
            <div v-if="hasPaymentLinks" class="mb-3">
                <div class="text-muted small mb-2">Links gerados</div>
                <div class="small text-muted">
                    {{ paidLinksCount }} pago(s) de {{ data.payment_links.length }} link(s)
                </div>
            </div>
            <div v-else-if="isOrder && !data.paid" class="mb-0">
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
                    {{ generatingPaymentLink ? 'Gerando...' : 'Pagar' }}
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

const hasPaymentLinks = computed(() => Array.isArray(props.data?.payment_links) && props.data.payment_links.length > 0);
const paidLinksCount = computed(() => (props.data?.payment_links || []).filter((link) => link.status === 'paid').length);
const remainingProductsValue = computed(() => {
    if (props.data?.payment_method === 'pix') {
        return props.data?.payment_breakdown?.remaining?.PRODUTOS_PIX || 0;
    }

    return props.data?.payment_breakdown?.remaining?.PRODUTOS_CREDIT_CARD || 0;
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
    if (!props.data?.link_payment) {
        return;
    }

    try {
        const textArea = document.createElement('textarea');
        textArea.value = props.data.link_payment;
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
</style>

