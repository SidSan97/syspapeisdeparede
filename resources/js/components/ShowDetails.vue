<template>
    <section class="content">
        <Page :title="pageTitle" :back-to="backTo">
            <template #actions>
                <button
                    v-if="isOrder && data && data.status !== 'Aprovado' && data.status != 'cancelado'"
                    type="button"
                    class="btn btn-primary me-3"
                    @click="handleApprove"
                    :disabled="processing"
                >
                    <span
                        v-if="processing && actionType === 'approve'"
                        class="spinner-border spinner-border-sm me-2"
                        role="status"
                        aria-hidden="true"
                    ></span>
                    Aprovar
                </button>
            </template>

            <div class="container py-4">
                <div v-if="loading" class="text-center text-muted py-5">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>

                <template v-else-if="data">
                    <div class="row">
                        <div class="col-12 col-lg-8">
                            <BasicInfoCard :data="data" :is-dropshipping-enabled="isDropshippingEnabled" />

                            <DropshippingDataCard
                                :is-dropshipping-enabled="isDropshippingEnabled"
                                :dropshipping-data="dropshippingData"
                            />

                            <RoomsCard :data="data" />

                            <SelectedModelsCard :data="data" />

                            <RequestArtsCard :data="data" :is-order="isOrder" />
                        </div>

                        <!-- Sidebar: Frete, Pagamento e Resumo -->
                        <div class="col-12 col-lg-4">
                            <ShippingCard :data="data" />

                            <PaymentCard
                                :data="data"
                                :is-order="isOrder"
                                :generating-payment-link="generatingPaymentLink"
                                @generate-payment-link="generatePaymentLink"
                            />

                            <SummaryCard :data="data" />

                            <AdditionalInfoCard :data="data" />
                        </div>
                    </div>
                </template>

                <div v-else class="text-center text-muted py-5">
                    <p>Não foi possível carregar os detalhes.</p>
                </div>
            </div>
        </Page>
    </section>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import Page from '@/components/page/Page.vue';
import { useAuthStore } from '@/stores/auth';
import { USER_TYPES } from '@/constants/userTypes';
import { useFormatting } from '@/composables/useFormatting';
import { useOrderService } from '@/services/orderService';
import BasicInfoCard from '@/components/details/BasicInfoCard.vue';
import DropshippingDataCard from '@/components/details/DropshippingDataCard.vue';
import RoomsCard from '@/components/details/RoomsCard.vue';
import SelectedModelsCard from '@/components/details/SelectedModelsCard.vue';
import ModelReferencesCard from '@/components/details/ModelReferencesCard.vue';
import ShippingCard from '@/components/details/ShippingCard.vue';
import PaymentCard from '@/components/details/PaymentCard.vue';
import SummaryCard from '@/components/details/SummaryCard.vue';
import AdditionalInfoCard from '@/components/details/AdditionalInfoCard.vue';
import RequestArtsCard from '@/components/details/RequestArtsCard.vue';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();

const loading = ref(false);
const data = ref(null);
const dropshippingData = ref(null);
const processing = ref(false);
const actionType = ref(null);
const generatingPaymentLink = ref(false);

const orderService = useOrderService();

// Determinar se é orçamento ou pedido baseado na rota
const isOrder = computed(() => route.path.includes('/pedidos') || route.path.includes('/orders'));

const pageTitle = computed(() => {
    return isOrder.value ? 'Detalhes do Pedido' : 'Detalhes do Orçamento';
});

const backTo = computed(() => {
    return isOrder.value ? '/pedidos' : '/budget';
});

const isDropshippingEnabled = computed(() => {
    return data.value?.dropshipping_budget === 1;
});

// Carregar dados
async function loadData() {
    const id = route.params.id;
    if (!id) {
        router.push(backTo.value);
        return;
    }

    loading.value = true;

    try {
        const responseData = await orderService.getDetails(id, isOrder.value);

        if (!responseData) {
            throw new Error('Dados não encontrados');
        }

        data.value = responseData;

        // Verificar se o link de pagamento está expirado e gerar novo se necessário
        if (isOrder.value && responseData.link_payment && responseData.payment_expiration_date) {
            if (orderService.isPaymentLinkExpired(responseData.payment_expiration_date)) {
                await generatePaymentLink(false);
            }
        }

        // Carregar dados de dropshipping se existirem
        if (responseData.dropshipping_data) {
            dropshippingData.value = responseData.dropshipping_data;
        }
    } catch (error) {
        console.error('Erro ao carregar dados:', error);
        window.Swal.fire({
            title: 'Erro!',
            text: error.message || 'Não foi possível carregar os detalhes',
            icon: 'error',
            showCloseButton: true,
            confirmButtonText: 'Entendi!',
        });
        router.push(backTo.value);
    } finally {
        loading.value = false;
    }
}

async function handleApprove() {
    if (!data.value || !isOrder.value) {
        return;
    }

    const result = await window.Swal.fire({
        title: 'Aprovar pedido?',
        text: `Tem certeza que deseja aprovar o pedido "${data.value.name}"?`,
        icon: 'question',
        showCancelButton: true,
        showCloseButton: true,
        reverseButtons: true,
        confirmButtonText: 'Sim, aprovar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#198754',
    });

    if (!result.isConfirmed) {
        return;
    }

    processing.value = true;
    actionType.value = 'approve';

    try {
        await orderService.approveOrder(data.value.id);
        await loadData();

        await window.Swal.fire({
            title: 'Pedido aprovado',
            text: 'O pedido foi aprovado com sucesso. Consulte os DETALHES DO PEDIDO para acessar o link de pagamento.',
            icon: 'success',
            showCloseButton: true,
            confirmButtonText: 'Entendi!',
        });
    } catch (error) {
        const errorMessage = error?.response?.data?.message || error?.message || 'Não foi possível aprovar o pedido. Tente novamente.';

        await window.Swal.fire({
            title: 'Erro',
            text: errorMessage,
            icon: 'error',
            showCloseButton: true,
            confirmButtonText: 'OK',
        });
    } finally {
        processing.value = false;
        actionType.value = null;
    }
}

async function generatePaymentLink(showSuccessMessage = true) {
    if (!data.value?.id || !isOrder.value) {
        return;
    }

    generatingPaymentLink.value = true;

    try {
        const responseData = await orderService.generatePaymentLink(data.value.id);

        // Atualizar dados locais
        if (responseData) {
            data.value = responseData;
        }

        if (showSuccessMessage) {
            await window.Swal.fire({
                title: 'Link gerado!',
                text: 'Link de pagamento gerado com sucesso.',
                icon: 'success',
                confirmButtonText: 'OK',
            });
        }
    } catch (error) {
        const errorMessage = error?.response?.data?.message || error?.message || 'Não foi possível gerar o link de pagamento.';

        if (showSuccessMessage) {
            await window.Swal.fire({
                title: 'Erro',
                text: errorMessage,
                icon: 'error',
                confirmButtonText: 'OK',
            });
        }
    } finally {
        generatingPaymentLink.value = false;
    }
}

onMounted(() => {
    loadData();
});

// Observar mudanças na rota
watch(() => route.params.id, () => {
    loadData();
});
</script>

<style scoped>
.accordion-button {
    font-weight: 500;
}

.card.border-success.border-2 {
    box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.15);
    border-color: var(--bs-success) !important;
}
</style>
