<template>
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
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { useFormatting } from '@/composables/useFormatting';
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
    data: {
        type: Object,
        required: true,
    },
    isOrder: {
        type: Boolean,
        default: false,
    },
});

const auth = useAuthStore();
const { formatNumber, formatDate, resolveImageUrl } = useFormatting();

const requestLayoutArts = ref([]);
const loadingRequestArts = ref(false);
const artFiles = ref({});
const artComments = ref({});
const uploadingArt = ref({});

const isReseller = computed(() => {
    return auth.hasRole(['reseller'])
        || auth.roles?.some(role => typeof role === 'string' && role.toLowerCase().includes('revendedor'));
});

function handleImageError(event) {
    event.target.style.display = 'none';
}

async function fetchRequestLayoutArts() {
    if (!props.data || !auth.user?.id) {
        requestLayoutArts.value = [];
        loadingRequestArts.value = false;
        return;
    }

    try {
        loadingRequestArts.value = true;

        let params = {};

        if (props.isOrder) {
            const orderId = props.data.id;
            if (!orderId) {
                requestLayoutArts.value = [];
                return;
            }
            params = {
                order_id: orderId,
            };
        } else {
            const budgetId = props.data.id;
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
                    arts_count: art.arts_count || art.arts?.length || 0,
                    arts: art.arts || [],
                    card_id: art.card_id || art.order_budget_id || null,
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
    if (!interaction || !interaction.card_id || !props.data || !auth.user?.id) {
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
        let orderId = props.data.id;

        if (props.isOrder) {
            orderId = props.data.id;
        } else {
            orderId = props.data.order_id || props.data.id;
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

// Carregar dados quando o componente for montado ou quando as props mudarem
onMounted(() => {
    if (props.data && auth.user?.id) {
        fetchRequestLayoutArts();
    }
});

watch(() => props.data?.id, (newId, oldId) => {
    if (newId && newId !== oldId && auth.user?.id) {
        fetchRequestLayoutArts();
    }
});
</script>

<style scoped>
.accordion-button {
    font-weight: 500;
}
</style>

