import { ref, computed, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useInvoiceService } from '../services/invoiceService';
import {
    parseNotasFiscaisFromTinyRetorno,
    messageFromTinyRetornoErro,
} from '../utils/invoiceTinyResponse';

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

function formatInvoiceCurrency(value) {
    if (value === null || value === undefined || value === '') {
        return currencyFormatter.format(0);
    }
    const numericValue = Number(String(value).replace(',', '.'));
    return currencyFormatter.format(Number.isFinite(numericValue) ? numericValue : 0);
}

/**
 * Tela de NF por pedido: carrega Tiny e expõe estado para InvoiceView.
 */
export function useOrderInvoice() {
    const route = useRoute();
    const invoiceService = useInvoiceService();

    const orderId = computed(() => route.params.orderId);

    const loading = ref(true);
    const errorMessage = ref('');
    const notasFiscais = ref([]);
    const selectedNotaIndex = ref(0);

    const invoiceData = computed(() => {
        const list = notasFiscais.value;
        const idx = selectedNotaIndex.value;
        if (!Array.isArray(list) || idx < 0 || idx >= list.length) {
            return null;
        }
        return list[idx] ?? null;
    });

    const notasOptions = computed(() =>
        notasFiscais.value.map((nf, idx) => ({
            label: `NF ${nf?.numero ?? idx + 1}${nf?.serie ? ` — série ${nf.serie}` : ''}`,
            idx,
        }))
    );

    const pageTitle = computed(() => {
        const id = orderId.value;
        return id ? `Nota fiscal — pedido #${id}` : 'Nota fiscal';
    });

    async function loadInvoice() {
        const id = orderId.value;
        if (!id) {
            loading.value = false;
            errorMessage.value = 'Pedido inválido.';
            notasFiscais.value = [];
            return;
        }

        loading.value = true;
        errorMessage.value = '';
        notasFiscais.value = [];
        selectedNotaIndex.value = 0;

        try {
            const body = await invoiceService.getInvoiceByOrderId(id);

            if (body?.success === false) {
                errorMessage.value = body?.message || 'Não foi possível carregar a nota fiscal.';
                return;
            }

            const retorno = body?.data;
            if (!retorno) {
                return;
            }

            const tinyErro = messageFromTinyRetornoErro(retorno);
            if (tinyErro) {
                errorMessage.value = tinyErro;
                return;
            }

            const list = parseNotasFiscaisFromTinyRetorno(retorno);
            notasFiscais.value = list;
        } catch (error) {
            const res = error.response?.data;
            errorMessage.value =
                res?.message ||
                (typeof res === 'string' ? res : null) ||
                error.message ||
                'Erro ao carregar a nota fiscal.';
        } finally {
            loading.value = false;
            document.title = pageTitle.value;
        }
    }

    onMounted(loadInvoice);
    watch(orderId, () => {
        loadInvoice();
    });

    return {
        orderId,
        loading,
        errorMessage,
        notasFiscais,
        selectedNotaIndex,
        invoiceData,
        notasOptions,
        pageTitle,
        formatCurrency: formatInvoiceCurrency,
        loadInvoice,
    };
}
