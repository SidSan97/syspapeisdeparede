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

                            <!-- Solicitação de Artes -->
                            <div class="card mb-4">
                                <div class="card-header bg-transparent">
                                    <h5 class="mb-0 fw-semibold">
                                        Solicitação de Artes
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div v-if="loadingRequestArts" class="text-center text-muted py-3">
                                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                        Carregando solicitações de artes...
                                    </div>
                                    <div v-else-if="requestLayoutArts.length === 0" class="text-center text-muted py-3">
                                        Nenhuma solicitação de arte encontrada.
                                    </div>
                                    <div v-else class="accordion" id="requestArtsAccordion">
                                        <div
                                            v-for="(interaction, interactionIndex) in requestLayoutArts"
                                            :key="interaction.id || interactionIndex"
                                            class="accordion-item mb-3"
                                        >
                                            <h2 class="accordion-header">
                                                <button
                                                    class="accordion-button p-2"
                                                    :class="{ collapsed: interactionIndex !== 0 }"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    :data-bs-target="`#interaction-${interactionIndex}`"
                                                    :aria-expanded="interactionIndex === 0"
                                                    :aria-controls="`interaction-${interactionIndex}`"
                                                >
                                                    <i class="fa fa-comments me-2"></i>
                                                    Interação #{{ interaction.id }}
                                                    <span v-if="interaction.wall_info?.wall_name" class="badge bg-info ms-2">
                                                        {{ interaction.wall_info.wall_name }}
                                                    </span>
                                                    <span class="badge bg-secondary ms-2">
                                                        {{ interaction.arts_count }} arte(s)
                                                    </span>
                                                    <span>
                                                        
                                                    </span>
                                                </button>
                                            </h2>
                                            <div
                                                :id="`interaction-${interactionIndex}`"
                                                class="accordion-collapse collapse"
                                                :class="{ show: interactionIndex === 0 }"
                                                data-bs-parent="#requestArtsAccordion"
                                            >
                                                <div class="accordion-body">
                                                    <div v-if="interaction.wall_info" class="mb-3 p-2 rounded border">
                                                        <div class="row g-2">
                                                            <div class="col-md-12">
                                                                <div class="text-muted small">
                                                                    <h5>Comentário</h5>
                                                                </div>
                                                                <div class="fw-semibold mb-3">{{ interaction.comment || 'N/A' }}</div>

                                                                <img :src="interaction.image_url" alt="Imagem da arte" class="img-fluid">
                                                            </div>
                                                            <hr>
                                                            <div class="col-md-6">
                                                                <div class="text-muted small">Ambiente</div>
                                                                <div class="fw-semibold">{{ interaction.wall_info.room_name || 'N/A' }}</div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="text-muted small">Parede</div>
                                                                <div class="fw-semibold">{{ interaction.wall_info.wall_name || 'N/A' }}</div>
                                                            </div>
                                                            <div v-if="interaction.wall_info.width" class="col-md-4">
                                                                <div class="text-muted small">Largura</div>
                                                                <div class="fw-semibold">{{ formatNumber(interaction.wall_info.width) }} m</div>
                                                            </div>
                                                            <div v-if="interaction.wall_info.height" class="col-md-4">
                                                                <div class="text-muted small">Altura</div>
                                                                <div class="fw-semibold">{{ formatNumber(interaction.wall_info.height) }} m</div>
                                                            </div>
                                                            <div v-if="interaction.wall_info.total_area" class="col-md-4">
                                                                <div class="text-muted small">Área</div>
                                                                <div class="fw-semibold">{{ formatNumber(interaction.wall_info.total_area) }} m²</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div v-if="interaction.created_at" class="mb-3 p-2 border rounded">
                                                        <div class="text-muted small">
                                                            <i class="fa fa-calendar me-1"></i>
                                                            Interação criada em: {{ formatDate(interaction.created_at) }}
                                                        </div>
                                                    </div>

                                                    <!-- Lista de Artes da Interação -->
                                                    <div v-if="interaction.arts && interaction.arts.length > 0" class="mt-3">
                                                        <h6 class="mb-3">
                                                            <i class="fa fa-images me-2"></i>
                                                            Artes ({{ interaction.arts.length }})
                                                        </h6>
                                                        <div
                                                            v-for="(art, artIndex) in interaction.arts"
                                                            :key="art.id || artIndex"
                                                            class="card mb-3 border"
                                                            :class="{ 'border-top': artIndex > 0 }"
                                                        >
                                                            <div class="card-body">
                                                                <div class="mb-3 p-2 border rounded">
                                                                    <div class="row g-2">
                                                                        <div v-if="art.dealer_name" class="col-md-6">
                                                                            <div class="text-muted small">
                                                                                <i class="fa fa-user-tie me-1"></i>
                                                                                Revendedor
                                                                            </div>
                                                                            <div class="fw-semibold">{{ art.dealer_name }}</div>
                                                                        </div>
                                                                        <div v-if="art.designer_name" class="col-md-6">
                                                                            <div class="text-muted small">
                                                                                <i class="fa fa-user me-1"></i>
                                                                                Designer
                                                                            </div>
                                                                            <div class="fw-semibold">{{ art.designer_name }}</div>
                                                                        </div>
                                                                        <div v-if="art.created_at" class="col-12">
                                                                            <div class="text-muted small">
                                                                                <i class="fa fa-calendar me-1"></i>
                                                                                Enviado em: {{ formatDate(art.created_at) }}
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div v-if="art.comment" class="mb-3">
                                                                    <div class="text-muted small mb-1">Comentário</div>
                                                                    <div class="p-2 rounded border">{{ art.comment }}</div>
                                                                </div>
                                                                <div v-if="art.image_url" class="mb-3">
                                                                    <div class="text-muted small mb-2">Imagem da Arte</div>
                                                                    <div class="d-flex justify-content-center">
                                                                        <img
                                                                            :src="art.image_url"
                                                                            :alt="`Arte ${art.id}`"
                                                                            class="img-thumbnail"
                                                                            style="max-width: 100%; max-height: 400px; object-fit: contain;"
                                                                            @error="handleImageError"
                                                                        />
                                                                    </div>
                                                                    <div class="mt-2 text-center">
                                                                        <a
                                                                            :href="art.image_url"
                                                                            target="_blank"
                                                                            rel="noopener noreferrer"
                                                                            class="btn btn-sm btn-outline-primary"
                                                                        >
                                                                            <i class="fa fa-external-link me-1"></i>
                                                                            Abrir em nova aba
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Formulário de Resposta do Revendedor -->
                                                    <div v-if="auth.user && isReseller" class="mt-4 pt-3 border-top">
                                                        <h6 class="mb-3">
                                                            <i class="fa fa-reply me-2"></i>
                                                            Responder Interação
                                                        </h6>
                                                        <form @submit.prevent="handleRespondToInteraction(interaction)">
                                                            <div class="mb-3">
                                                                <label :for="'art-file-' + interaction.id" class="form-label">
                                                                    Imagem da Arte (opcional)
                                                                </label>
                                                                <input
                                                                    :id="'art-file-' + interaction.id"
                                                                    type="file"
                                                                    accept="image/*"
                                                                    class="form-control"
                                                                    @change="handleArtFileChange($event, interaction.id)"
                                                                    :disabled="uploadingArt[interaction.id]"
                                                                />
                                                                <div class="form-text">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 10MB</div>
                                                                <div v-if="artFiles[interaction.id]" class="mt-2">
                                                                    <span class="badge bg-info">
                                                                        <i class="fa fa-file-image me-1"></i>
                                                                        {{ artFiles[interaction.id].name }}
                                                                    </span>
                                                                    <button
                                                                        type="button"
                                                                        class="btn btn-sm btn-link text-danger p-0 ms-2"
                                                                        @click="clearArtFile(interaction.id)"
                                                                        :disabled="uploadingArt[interaction.id]"
                                                                    >
                                                                        <i class="fa fa-times"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label :for="'art-comment-' + interaction.id" class="form-label">
                                                                    Comentário <span class="text-danger">*</span>
                                                                </label>
                                                                <textarea
                                                                    :id="'art-comment-' + interaction.id"
                                                                    v-model="artComments[interaction.id]"
                                                                    class="form-control"
                                                                    rows="3"
                                                                    placeholder="Adicione um comentário sobre a arte..."
                                                                    :disabled="uploadingArt[interaction.id]"
                                                                ></textarea>
                                                            </div>
                                                            <div class="d-flex justify-content-end">
                                                                <button
                                                                    type="submit"
                                                                    class="btn btn-primary"
                                                                    :disabled="!artComments[interaction.id] || !artComments[interaction.id].trim() || uploadingArt[interaction.id]"
                                                                >
                                                                    <span
                                                                        v-if="uploadingArt[interaction.id]"
                                                                        class="spinner-border spinner-border-sm me-2"
                                                                        role="status"
                                                                        aria-hidden="true"
                                                                    ></span>
                                                                    {{ uploadingArt[interaction.id] ? 'Enviando...' : 'Enviar Resposta' }}
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();

const loading = ref(false);
const data = ref(null);
const requestLayoutArts = ref([]);
const loadingRequestArts = ref(false);
const dropshippingData = ref(null);
const processing = ref(false);
const actionType = ref(null);
const generatingPaymentLink = ref(false);
const artFiles = ref({});
const artComments = ref({});
const uploadingArt = ref({});

const { formatNumber, formatDate, resolveImageUrl } = useFormatting();

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

const isReseller = computed(() => {
    return auth.user?.user_type_id === USER_TYPES.RESELLER
        || auth.hasRole('reseller')
        || auth.hasRole('revendedor')
        || auth.roles?.some(role => typeof role === 'string' && role.toLowerCase().includes('revendedor'));
});

function handleImageError(event) {
    event.target.style.display = 'none';
}

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

        // Carregar solicitações de artes
        if (auth.user?.id) {
            await fetchRequestLayoutArts();
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
