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
                            <!-- Seção: Informações Básicas -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Nome</label>
                                        <div class="fw-semibold fs-5">{{ data.name }}</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <div>
                                            <span
                                                class="badge"
                                                :class="{
                                                    'bg-warning text-dark': data.status === 'Pendente de Revisão',
                                                    'bg-success': data.status === 'Aprovado',
                                                    'bg-info': data.status === 'Em aberto'
                                                }"
                                            >
                                                {{ data.status || 'Sem status' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div v-if="isDropshippingEnabled" class="mb-3">
                                        <label class="form-label">Dropshipping</label>
                                        <div>
                                            <span class="badge bg-primary">Habilitado</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dados de Dropshipping -->
                            <div v-if="isDropshippingEnabled && dropshippingData" class="card mb-4">
                                <div class="card-header bg-transparent">
                                    <h5 class="mb-0 fw-semibold">Dados de Dropshipping</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="text-muted small">Nome</div>
                                            <div class="fw-semibold">{{ dropshippingData.name || '-' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-muted small">Tipo de Pessoa</div>
                                            <div class="fw-semibold">{{ dropshippingData.person_type || '-' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-muted small">CPF/CNPJ</div>
                                            <div class="fw-semibold">{{ dropshippingData.cpf_cnpj || '-' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-muted small">Email</div>
                                            <div class="fw-semibold">{{ dropshippingData.email || '-' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-muted small">Telefone</div>
                                            <div class="fw-semibold">{{ dropshippingData.phone || '-' }}</div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="text-muted small">Endereço Completo</div>
                                            <div class="fw-semibold">
                                                {{ formatDropshippingAddress() }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção: Ambientes e Paredes -->
                            <div class="card mb-4">
                                <div class="card-header bg-transparent">
                                    <h5 class="mb-0 fw-semibold">Cômodos</h5>
                                </div>
                                <div class="card-body">
                                    <div v-if="!data.rooms || data.rooms.length === 0" class="text-center text-muted py-4">
                                        Nenhum ambiente cadastrado
                                    </div>
                                    <template v-else>
                                        <div v-for="(room, roomIndex) in data.rooms" :key="roomIndex" class="card mb-3">
                                            <div class="card-header">
                                                <strong>{{ room.name || `Ambiente ${roomIndex + 1}` }}</strong>
                                            </div>
                                            <div class="card-body">
                                                <div v-if="!room.walls || room.walls.length === 0" class="text-muted small">
                                                    Nenhuma parede cadastrada
                                                </div>
                                                <template v-else>
                                                    <div v-for="(wall, wallIndex) in room.walls" :key="wallIndex" class="card mb-3 border">
                                                        <div class="card-body">
                                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                                <strong>{{ wall.name || `Parede ${wallIndex + 1}` }}</strong>
                                                            </div>

                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <div class="text-muted small">Largura (m)</div>
                                                                    <div class="fw-semibold">{{ formatNumber(wall.width) }}</div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="text-muted small">Altura (m)</div>
                                                                    <div class="fw-semibold">{{ formatNumber(wall.height) }}</div>
                                                                </div>
                                                            </div>

                                                            <!-- Modelo selecionado -->
                                                            <div v-if="wall.collection_model || wall.collection_model_name" class="mb-3">
                                                                <div class="text-muted small">Modelo</div>
                                                                <div class="fw-semibold">
                                                                    {{ wall.collection_model?.name || wall.collection_model_name || '-' }}
                                                                </div>
                                                            </div>

                                                            <!-- Continuações -->
                                                            <div v-if="wall.continuations && wall.continuations.length > 0" class="mb-3">
                                                                <div class="text-muted small mb-2">Continuações</div>
                                                                <div v-for="(continuation, contIndex) in wall.continuations" :key="contIndex" class="border-start border-primary ps-3 ms-2 mb-2">
                                                                    <div class="row">
                                                                        <div class="col-md-4">
                                                                            <div class="text-muted small">Direção</div>
                                                                            <div class="fw-semibold">{{ formatDirection(continuation.direction) }}</div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="text-muted small">Largura (m)</div>
                                                                            <div class="fw-semibold">{{ formatNumber(continuation.width) }}</div>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <div class="text-muted small">Altura (m)</div>
                                                                            <div class="fw-semibold">{{ formatNumber(continuation.height) }}</div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Cálculos da parede -->
                                                            <div v-if="wall.total_area" class="alert alert-success mb-0">
                                                                <strong>Área:</strong> {{ formatNumber(wall.total_area) }} m²
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Seção: Modelos Selecionados -->
                            <div v-if="data.rooms && data.rooms.some(r => r.walls && r.walls.some(w => w.collection_model || w.collection_model_name))" class="card mb-4">
                                <div class="card-header bg-transparent">
                                    <h5 class="mb-0 fw-semibold">Modelos Selecionados</h5>
                                </div>
                                <div class="card-body">
                                    <div v-for="(room, roomIndex) in data.rooms" :key="roomIndex">
                                        <div v-if="room.walls && room.walls.some(w => w.collection_model || w.collection_model_name)" class="mb-4">
                                            <h6 class="mb-3">{{ room.name || `Ambiente ${roomIndex + 1}` }}</h6>
                                            <div v-for="(wall, wallIndex) in room.walls" :key="wallIndex">
                                                <div v-if="wall.collection_model || wall.collection_model_name" class="mb-3 pb-3 border-bottom">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <div class="fw-semibold">{{ wall.name || `Parede ${wallIndex + 1}` }}</div>
                                                            <div class="text-muted small">
                                                                Modelo: {{ wall.collection_model?.name || wall.collection_model_name }}
                                                            </div>
                                                            <div v-if="wall.collection_model" class="text-muted small mt-1">
                                                                <span>Valor: {{ formatCurrency(wall.collection_model.value) }}</span>
                                                                <span class="ms-3">Prazo: {{ wall.collection_model.deadline }} dia(s)</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Referências do Modelo (Comentários, Link, Arquivos) -->
                            <div v-if="hasModelReferences" class="card mb-4">
                                <div class="card-header bg-transparent">
                                    <h5 class="mb-0 fw-semibold">Referências do Modelo</h5>
                                </div>
                                <div class="card-body">
                                    <div v-if="data.comment_referring_model" class="mb-3">
                                        <div class="text-muted small mb-1">Descrição</div>
                                        <div class="p-2 rounded border">{{ data.comment_referring_model }}</div>
                                    </div>
                                    <div v-if="data.link_referring_model" class="mb-3">
                                        <div class="text-muted small mb-1">Link de Referência</div>
                                        <div>
                                            <a :href="data.link_referring_model" target="_blank" rel="noopener noreferrer" class="text-break">
                                                {{ data.link_referring_model }}
                                            </a>
                                        </div>
                                    </div>
                                    <div v-if="data.files_referring_model && data.files_referring_model.length > 0" class="mb-0">
                                        <div class="text-muted small mb-2">Arquivos de Referência</div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a
                                                v-for="(file, index) in data.files_referring_model"
                                                :key="index"
                                                :href="resolveStorageUrl(file)"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="btn btn-sm btn-outline-secondary"
                                            >
                                                <i class="fa fa-file-image me-1"></i>
                                                {{ extractFileName(file) }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Solicitação de Artes -->
                            <div class="card mb-4">
                                <div class="card-header bg-transparent">
                                    <h5 class="mb-0 fw-semibold">
                                        <i class="fa fa-paint-brush me-2"></i>
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
                                            v-for="(art, artIndex) in requestLayoutArts"
                                            :key="art.id || artIndex"
                                            class="accordion-item mb-3"
                                        >
                                            <h2 class="accordion-header">
                                                <button
                                                    class="accordion-button"
                                                    :class="{ collapsed: artIndex !== 0 }"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    :data-bs-target="`#art-${artIndex}`"
                                                    :aria-expanded="artIndex === 0"
                                                    :aria-controls="`art-${artIndex}`"
                                                >
                                                    <i class="fa fa-image me-2"></i>
                                                    Arte #{{ art.id }}
                                                    <span v-if="art.wall_name" class="badge bg-info ms-2">
                                                        {{ art.wall_name }}
                                                    </span>
                                                </button>
                                            </h2>
                                            <div
                                                :id="`art-${artIndex}`"
                                                class="accordion-collapse collapse"
                                                :class="{ show: artIndex === 0 }"
                                                data-bs-parent="#requestArtsAccordion"
                                            >
                                                <div class="accordion-body">
                                                    <div v-if="art.wall_info" class="mb-3 p-2 rounded border bg-light">
                                                        <div class="row g-2">
                                                            <div class="col-md-6">
                                                                <div class="text-muted small">Ambiente</div>
                                                                <div class="fw-semibold">{{ art.wall_info.room_name || 'N/A' }}</div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="text-muted small">Parede</div>
                                                                <div class="fw-semibold">{{ art.wall_info.wall_name || 'N/A' }}</div>
                                                            </div>
                                                            <div v-if="art.wall_info.width" class="col-md-4">
                                                                <div class="text-muted small">Largura</div>
                                                                <div class="fw-semibold">{{ formatNumber(art.wall_info.width) }} m</div>
                                                            </div>
                                                            <div v-if="art.wall_info.height" class="col-md-4">
                                                                <div class="text-muted small">Altura</div>
                                                                <div class="fw-semibold">{{ formatNumber(art.wall_info.height) }} m</div>
                                                            </div>
                                                            <div v-if="art.wall_info.total_area" class="col-md-4">
                                                                <div class="text-muted small">Área</div>
                                                                <div class="fw-semibold">{{ formatNumber(art.wall_info.total_area) }} m²</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 p-2 border rounded bg-light">
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
                                                        <div class="p-2 rounded border bg-light">{{ art.comment }}</div>
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
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar: Frete, Pagamento e Resumo -->
                        <div class="col-12 col-lg-4">
                            <!-- Seção: Frete -->
                            <div class="card mb-4">
                                <div class="card-header bg-transparent">
                                    <h5 class="mb-0 fw-semibold">Frete</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="text-muted small">CEP</div>
                                        <div class="fw-semibold">{{ data.cep || '-' }}</div>
                                    </div>
                                    <div v-if="data.selected_carrier_name" class="mb-3">
                                        <div class="text-muted small">Transportadora</div>
                                        <div class="fw-semibold">{{ data.selected_carrier_name }}</div>
                                    </div>
                                    <div v-if="data.selected_carrier_price" class="mb-3">
                                        <div class="text-muted small">Valor do Frete</div>
                                        <div class="fw-semibold">{{ formatCurrency(data.selected_carrier_price) }}</div>
                                    </div>
                                    <div v-if="data.selected_carrier_delivery_time" class="mb-0">
                                        <div class="text-muted small">Prazo de Entrega</div>
                                        <div class="fw-semibold">{{ formatDeliveryTime(data.selected_carrier_delivery_time) }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção: Pagamento -->
                            <div class="card mb-4" :class="{ 'border-success border-2': paymentUrl }">
                                <div class="card-header bg-transparent" :class="{ 'bg-success-subtle': paymentUrl }">
                                    <h5 class="mb-0 fw-semibold">
                                        <i v-if="paymentUrl" class="fa fa-check-circle text-success me-2"></i>
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
                                    <div v-if="paymentUrl" class="mb-0">
                                        <div class="text-muted small mb-2">Link de Pagamento</div>
                                        <div>
                                            <a
                                                :href="paymentUrl"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="btn btn-primary btn-sm"
                                            >
                                                <i class="fa fa-external-link me-2"></i>
                                                Acessar Link de Pagamento
                                            </a>
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary btn-sm ms-2"
                                                @click="copyPaymentUrl"
                                                title="Copiar link"
                                            >
                                                <i class="fa fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção: Resumo -->
                            <div class="card mb-4">
                                <div class="card-header bg-transparent">
                                    <h5 class="mb-0 fw-semibold">Resumo</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Total de Ambientes:</span>
                                            <strong>{{ data.rooms?.length || 0 }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Total de Paredes:</span>
                                            <strong>{{ totalWalls }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Metros:</span>
                                            <strong>{{ formatNumber(data.total_area) }}</strong>
                                        </div>
                                        <div v-if="data.selected_carrier_price" class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Frete:</span>
                                            <strong>{{ formatCurrency(data.selected_carrier_price) }}</strong>
                                        </div>
                                        <div v-if="data.delivery_time" class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Prazo de entrega:</span>
                                            <strong>{{ formatDeliveryTime(data.delivery_time) }}</strong>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0 fw-semibold">Total à Vista:</h6>
                                            <h5 class="mb-0 text-success">{{ formatCurrency(data.total_amount) }}</h5>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0 fw-semibold">Total a Prazo:</h6>
                                            <h5 class="mb-0 text-primary">{{ formatCurrency(data.total_amount_installments) }}</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Informações Adicionais -->
                            <div class="card mb-4">
                                <div class="card-header bg-transparent">
                                    <h5 class="mb-0 fw-semibold">Informações Adicionais</h5>
                                </div>
                                <div class="card-body">
                                    <div v-if="data.created_at" class="mb-3">
                                        <div class="text-muted small">Criado em</div>
                                        <div class="fw-semibold">{{ formatDate(data.created_at) }}</div>
                                    </div>
                                    <div v-if="data.updated_at" class="mb-0">
                                        <div class="text-muted small">Atualizado em</div>
                                        <div class="fw-semibold">{{ formatDate(data.updated_at) }}</div>
                                    </div>
                                </div>
                            </div>
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
const paymentUrl = ref(null);

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

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

const hasModelReferences = computed(() => {
    return data.value?.comment_referring_model ||
           data.value?.link_referring_model ||
           (data.value?.files_referring_model && data.value.files_referring_model.length > 0);
});

const totalWalls = computed(() => {
    if (!data.value?.rooms) return 0;
    return data.value.rooms.reduce((total, room) => {
        return total + (room.walls?.length || 0);
    }, 0);
});

// Funções de formatação
function formatCurrency(value) {
    if (value === null || value === undefined) {
        return currencyFormatter.format(0);
    }
    const numericValue = Number(value);
    return currencyFormatter.format(Number.isFinite(numericValue) ? numericValue : 0);
}

function formatNumber(value) {
    if (value === null || value === undefined) {
        return '-';
    }
    const numericValue = Number(value);
    return Number.isFinite(numericValue) ? numericValue.toFixed(2) : value;
}

function formatDeliveryTime(days) {
    if (!days) {
        return 'Não informado';
    }
    return `${days} ${days === 1 ? 'dia' : 'dias'}`;
}

function formatPaymentMethod(method) {
    const methods = {
        credit_card: 'Cartão de Crédito',
        pix: 'PIX',
        installment: 'Parcelado',
    };
    return methods[method] || method || '-';
}

function formatDate(value) {
    if (!value) {
        return '-';
    }
    try {
        const date = value instanceof Date ? value : new Date(value);
        if (Number.isNaN(date.getTime())) {
            return typeof value === 'string' ? value : '-';
        }
        return date.toLocaleString('pt-BR');
    } catch (error) {
        return typeof value === 'string' ? value : '-';
    }
}

function formatDirection(direction) {
    const directions = {
        'left-to-right': 'Da esquerda para direita',
        'right-to-left': 'Da direita para esquerda',
    };
    return directions[direction] || direction || '-';
}

function resolveStorageUrl(path) {
    if (!path) {
        return '#';
    }
    if (typeof path === 'object' && path.url) {
        return path.url;
    }
    if (typeof path === 'object' && path.path) {
        path = path.path;
    }
    if (/^https?:\/\//i.test(path)) {
        return path;
    }
    const baseUrl = window.location.origin.replace(/\/$/, '');
    return `${baseUrl}/storage/${String(path).replace(/^storage\//, '')}`;
}

function extractFileName(file) {
    if (!file) {
        return 'Arquivo';
    }
    if (typeof file === 'object') {
        return file.name || file.fileName || 'Arquivo';
    }
    const segments = String(file).split('/');
    return segments[segments.length - 1] ?? file;
}

function formatDropshippingAddress() {
    if (!dropshippingData.value) return '-';
    const addr = dropshippingData.value;
    const parts = [
        addr.public_space,
        addr.neighborhood,
        addr.city,
        addr.state,
        addr.cep ? `CEP: ${addr.cep}` : null,
    ].filter(Boolean);
    return parts.join(', ') || '-';
}

function handleImageError(event) {
    event.target.style.display = 'none';
}

function resolveImageUrl(path) {
    if (!path) {
        return '';
    }
    if (/^https?:\/\//i.test(path)) {
        return path;
    }
    const baseUrl = window.location.origin.replace(/\/$/, '');
    return `${baseUrl}/storage/${String(path).replace(/^storage\//, '')}`;
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

        // Para pedidos, usar o ID do pedido como budget_id
        // Para orçamentos, usar o ID do orçamento como budget_id
        const budgetId = isOrder.value
            ? (data.value.budget_id || data.value.id)
            : data.value.id;

        // order_budget_id é opcional e geralmente não está disponível diretamente
        const orderBudgetId = data.value.order_budget_id || null;

        if (!budgetId) {
            requestLayoutArts.value = [];
            return;
        }

        const params = {
            budget_id: budgetId,
            dealer_id: auth.user.id,
        };

        if (orderBudgetId) {
            params.order_budget_id = orderBudgetId;
        }

        const response = await axios.get('v1/budgets/request-layout-arts', { params });
        const responseData = response?.data || response;

        if (responseData?.success && Array.isArray(responseData.data)) {
            requestLayoutArts.value = responseData.data.map((art) => ({
                id: art.id,
                budget_id: art.budget_id || budgetId,
                order_budget_id: art.order_budget_id || orderBudgetId,
                dealer_id: art.dealer_id || auth.user.id,
                designer_id: art.designer_id || auth.user.id,
                comment: art.comment || null,
                image_url: art.image_url || (art.path_file ? resolveImageUrl(art.path_file) : null),
                created_at: art.created_at || art.createdAt || null,
                designer_name: art.designer?.name || art.designer_name || null,
                dealer_name: art.dealer?.name || art.dealer_name || null,
                wall_info: art.wall_info || null,
                wall_name: art.wall_info?.wall_name || null,
            }));
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
        showCancelButton: true,
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

        // Obter link de pagamento da resposta
        const paymentLink = approveResponse.data?.payment_link;
        if (paymentLink) {
            // Tentar diferentes estruturas possíveis da URL
            paymentUrl.value = paymentLink.url
                || paymentLink.data?.url
                || paymentLink.data?.checkout_url
                || paymentLink.data?.public_url
                || null;

            if (!paymentUrl.value && paymentLink.data) {
                console.warn('URL de pagamento não encontrada na resposta:', paymentLink);
            }
        }

        await loadData();

        await window.Swal.fire({
            title: 'Pedido aprovado',
            text: 'O pedido foi aprovado com sucesso. Consulte os DETALHES DO PEDIDO para acessar o link de pagamento.',
            confirmButtonText: 'Entendi!',
        });
    } catch (error) {
        const errorMessage = error?.response?.data?.message || error?.message || 'Não foi possível aprovar o pedido. Tente novamente.';

        await window.Swal.fire({
            title: 'Erro',
            text: errorMessage,
            icon: 'error',
            confirmButtonText: 'OK',
        });
    } finally {
        processing.value = false;
        actionType.value = null;
    }
}

function copyPaymentUrl() {
    if (!paymentUrl.value) {
        return;
    }

    navigator.clipboard.writeText(paymentUrl.value).then(() => {
        window.Swal.fire({
            title: 'Link copiado!',
            text: 'O link de pagamento foi copiado para a área de transferência.',
            timer: 2000,
            showConfirmButton: false,
        });
    }).catch(() => {
        window.Swal.fire({
            title: 'Erro',
            text: 'Não foi possível copiar o link.',
            icon: 'error',
            timer: 2000,
            showConfirmButton: false,
        });
    });
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
