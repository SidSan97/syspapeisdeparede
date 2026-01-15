<template>
    <section class="content">
        <Page :title="pageTitle" :back-to="backTo">
            <template #actions>
                <button
                    v-if="isOrder && data && data.status !== 'Aprovado'"
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

                            <ModelReferencesCard :data="data" />

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
import axios from 'axios';
import Page from '@/components/page/Page.vue';
import { useAuthStore } from '@/stores/auth';
import { USER_TYPES } from '@/constants/userTypes';
import { useFormatting } from '@/composables/useFormatting';
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

const { formatNumber, formatDate } = useFormatting();

// Determinar se é orçamento ou pedido baseado na rota
const isOrder = computed(() => route.path.includes('/pedidos') || route.path.includes('/orders'));
const isBudget = computed(() => route.path.includes('/budget') || route.path.includes('/orçamento'));

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
        let responseData = null;

        if (isOrder.value) {
            // Para pedidos, buscar pelo endpoint específico
            const { data: response } = await axios.get(`v1/orders/${id}`);
            responseData = response?.data || response;
        } else {
            // Para orçamentos, buscar da lista e encontrar pelo ID
            const { data: response } = await axios.get('v1/budgets');
            const budgets = response?.data?.data ?? response?.data ?? [];
            responseData = budgets.find(b => b.id === Number(id));

            if (!responseData) {
                throw new Error('Orçamento não encontrado');
            }
        }

        if (!responseData) {
            throw new Error('Dados não encontrados');
        }

        data.value = responseData;

        // Verificar se o link de pagamento está expirado e gerar novo se necessário
        if (isOrder.value && responseData.link_payment && responseData.payment_expiration_date) {
            if (isPaymentLinkExpired(responseData.payment_expiration_date)) {
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

async function fetchRequestLayoutArts() {
    if (!data.value || !auth.user?.id) {
        requestLayoutArts.value = [];
        loadingRequestArts.value = false;
        return;
    }

    try {
        loadingRequestArts.value = true;

        let params = {};

        if (isOrder.value) {
            const orderId = data.value.id;
            if (!orderId) {
                requestLayoutArts.value = [];
                return;
            }
            params = {
                order_id: orderId,
            };
        } else {
            const budgetId = data.value.id;
            if (!budgetId) {
                requestLayoutArts.value = [];
                return;
            }
            params = {
                budget_id: budgetId,
                dealer_id: auth.user.id,
            };
        }

        const response = await axios.get('v1/budgets/request-layout-arts', { params });
        const responseData = response?.data || response;

        if (responseData?.success && Array.isArray(responseData.data)) {
            requestLayoutArts.value = responseData.data.map((art) => {
                let imageUrl = art.image_url;
                if (!imageUrl && art.path_file) {
                    imageUrl = resolveImageUrl(art.path_file);
                }
                
                return {
                    id: art.id,
                    order_id: art.order_id || null,
                    order_budget_id: art.order_budget_id || null,
                    dealer_id: art.dealer_id || null,
                    designer_id: art.designer_id || null,
                    comment: art.comment || null,
                    path_file: art.path_file || null,
                    image_url: imageUrl,
                    created_at: art.created_at || null,
                    designer_name: art.designer?.name || art.designer_name || null,
                    dealer_name: art.dealer?.name || art.dealer_name || null,
                    wall_info: art.wall_info || null,
                    wall_name: art.wall_name || art.wall_info?.wall_name || null,
                };
            });
        } else {
            requestLayoutArts.value = [];
        }
    } catch (error) {
        console.error('Erro ao buscar solicitações de artes:', error);
        requestLayoutArts.value = [];
    } finally {
        loadingRequestArts.value = false;
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
        const approveResponse = await axios.post('v1/orders/approve', {
            id: data.value.id,
        });

        if (!approveResponse.data?.success) {
            throw new Error(approveResponse.data?.message || 'Erro ao aprovar pedido');
        }

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

async function generatePaymentLink(showSuccessMessage = true) {
    if (!data.value?.id || !isOrder.value) {
        return;
    }

    generatingPaymentLink.value = true;

    try {
        const { data: response } = await axios.post(`v1/orders/${data.value.id}/generate-payment-link`);

        if (!response?.success) {
            throw new Error(response?.message || 'Erro ao gerar link de pagamento');
        }

        // Atualizar dados locais
        if (response.data) {
            data.value = response.data;
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

function handleArtFileChange(event, interactionId) {
    const file = event.target.files[0];
    if (file) {
        artFiles.value[interactionId] = file;
    }
}

function clearArtFile(interactionId) {
    delete artFiles.value[interactionId];
    const input = document.getElementById(`art-file-${interactionId}`);
    if (input) {
        input.value = '';
    }
}

async function handleRespondToInteraction(interaction) {
    if (!interaction || !interaction.card_id || !data.value || !auth.user?.id) {
        window.Swal.fire({
            title: 'Erro',
            text: 'Dados insuficientes para responder a interação.',
            icon: 'error',
            showCloseButton: true,
            confirmButtonText: 'OK',
        });
        return;
    }

    const interactionId = interaction.id;
    const artFile = artFiles.value[interactionId];
    const comment = (artComments.value[interactionId] || '').trim();

    if (!comment) {
        window.Swal.fire({
            title: 'Atenção',
            text: 'Por favor, insira um comentário para enviar a resposta.',
            icon: 'warning',
            showCloseButton: true,
            confirmButtonText: 'OK',
        });
        return;
    }

    uploadingArt.value[interactionId] = true;

    try {
        let orderId = data.value.id;

        if (isOrder.value) {
            orderId = data.value.id;
        } else {
            orderId = data.value.order_id || data.value.id;
        }

        const formData = new FormData();
        if (artFile) {
            formData.append('art_file', artFile);
        }
        formData.append('order_budget_id', interaction.card_id);
        formData.append('dealer_id', auth.user.id);
        formData.append('designer_id', auth.user.id);
        formData.append('order_id', orderId);
        formData.append('comment', comment);

        const response = await axios.post('v1/budgets/order-budgets/upload-art', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        if (response.data?.success) {
            // Limpar formulário
            clearArtFile(interactionId);
            artComments.value[interactionId] = '';

            // Recarregar solicitações de artes
            await fetchRequestLayoutArts();

            window.Toast.fire({
                icon: 'success',
                title: response.data.message || 'Arte enviada com sucesso.',
            });
        } else {
            throw new Error(response.data?.message || 'Erro ao enviar arte');
        }
    } catch (error) {
        console.error('Erro ao responder interação:', error);
        const errorMessage = error?.response?.data?.message || error?.message || 'Não foi possível enviar a arte. Tente novamente.';

        window.Swal.fire({
            title: 'Erro',
            text: errorMessage,
            icon: 'error',
            showCloseButton: true,
            confirmButtonText: 'OK',
        });
    } finally {
        uploadingArt.value[interactionId] = false;
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
