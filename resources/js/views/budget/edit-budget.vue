<template>
    <section class="content">
        <Page title="Editar orçamento" back-to="/budget">
            <template #actions>
                <button class="btn btn-success btn-lg" type="button" @click="updateBudget" :disabled="saving">
                    <i class="fa fa-save"></i> {{ saving ? 'Salvando...' : 'Salvar Orçamento' }}
                </button>
            </template>
        <div class="container py-4">
            <div class="row">
                <div class="col-12 col-lg-8">
                    <!-- Seção: Informações Básicas -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="budgetName" class="form-label">Nome do Orçamento</label>
                                <input
                                    v-model="budget.name"
                                    type="text"
                                    id="budgetName"
                                    class="form-control"
                                    placeholder="Ex: Orçamento Casa - Sala"
                                />
                            </div>
                            <div class="mb-3">
                                <label for="budgetStatus" class="form-label">Status</label>
                                <select
                                    v-model="budget.status"
                                    id="budgetStatus"
                                    class="form-control"
                                >
                                    <option :value="null">Sem status</option>
                                    <option value="Pendente de Revisão">Pendente de Revisão</option>
                                    <option value="Aprovado">Aprovado</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Ambientes e Paredes -->
                    <div class="card mb-4">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0 fw-semibold">Cômodos</h5>
                        </div>
                        <div class="card-body">
                                <div v-if="loadingBudget" class="text-center text-muted py-4">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Carregando...</span>
                                    </div>
                                </div>
                                <template v-else>
                                <div v-for="(room, roomIndex) in budget.rooms" :key="roomIndex" class="card mb-3">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <strong>{{ room.name || `Ambiente ${roomIndex + 1}` }}</strong>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-danger"
                                            @click="removeRoom(roomIndex)"
                                            v-if="budget.rooms.length > 1"
                                        >
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label :for="`room-name-${roomIndex}`" class="form-label">Nome do Ambiente</label>
                                            <input
                                                v-model="room.name"
                                                type="text"
                                                :id="`room-name-${roomIndex}`"
                                                class="form-control"
                                                placeholder="Ex: Quarto Alice"
                                            />
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Paredes</label>
                                            <div v-for="(wall, wallIndex) in room.walls" :key="wallIndex" class="card mb-3 border">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <strong>Parede {{ wallIndex + 1 }}</strong>
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-danger"
                                                            @click="removeWall(roomIndex, wallIndex)"
                                                            v-if="room.walls.length > 1"
                                                        >
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label :for="`wall-name-${roomIndex}-${wallIndex}`" class="form-label">Nome da Parede</label>
                                                        <input
                                                            v-model="wall.name"
                                                            type="text"
                                                            :id="`wall-name-${roomIndex}-${wallIndex}`"
                                                            class="form-control"
                                                            placeholder="Ex: Parede janela"
                                                        />
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label :for="`wall-width-${roomIndex}-${wallIndex}`" class="form-label">Largura (m)</label>
                                                            <input
                                                                v-model.number="wall.width"
                                                                type="number"
                                                                step="0.01"
                                                                :id="`wall-width-${roomIndex}-${wallIndex}`"
                                                                class="form-control"
                                                                placeholder="0.00"
                                                            />
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label :for="`wall-height-${roomIndex}-${wallIndex}`" class="form-label">Altura (m)</label>
                                                            <input
                                                                v-model.number="wall.height"
                                                                type="number"
                                                                step="0.01"
                                                                :id="`wall-height-${roomIndex}-${wallIndex}`"
                                                                class="form-control"
                                                                placeholder="0.00"
                                                            />
                                                        </div>
                                                    </div>

                                                    <!-- Continuations -->
                                                    <div class="mb-3">
                                                        <template v-if="wall.width && wall.height">
                                                            <div class="form-check form-switch">
                                                            <input
                                                                class="form-check-input"
                                                                type="checkbox"
                                                                    :id="`wall-continue-same-art-${roomIndex}-${wallIndex}`"
                                                                    v-model="wall.continueSameArt"
                                                                    @change="handleContinuationToggle(roomIndex, wallIndex)"
                                                            />
                                                                <label class="form-check-label" :for="`wall-continue-same-art-${roomIndex}-${wallIndex}`">
                                                                    Continuar esta parede com a mesma arte?
                                                            </label>
                                                        </div>
                                                        </template>
                                                        <template v-else>
                                                            <div class="alert alert-light border text-muted py-2 mb-0">
                                                                Informe a largura e a altura para habilitar continuações com a mesma arte.
                                                            </div>
                                                        </template>
                                                    </div>

                                                    <div
                                                        v-if="wall.continueSameArt && wall.width && wall.height"
                                                        class="mb-3 border-start border-primary ps-3 ms-2"
                                                    >
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <h6 class="mb-0">Continuações da parede</h6>
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-secondary"
                                                                @click="addContinuation(roomIndex, wallIndex)"
                                                            >
                                                                <i class="fa fa-plus"></i>
                                                                Adicionar continuação
                                                            </button>
                                                        </div>
                                                        <small class="text-muted d-block mb-3">
                                                            Use estas continuações para representar trechos adicionais com a mesma arte.
                                                        </small>

                                                        <div v-if="!wall.continuations.length" class="alert alert-info py-2">
                                                            Nenhuma continuação adicionada. Clique em "Adicionar continuação".
                                                        </div>

                                                        <div
                                                            v-for="(continuation, continuationIndex) in wall.continuations"
                                                            :key="`continuation-${roomIndex}-${wallIndex}-${continuationIndex}`"
                                                            class="card border border-primary-subtle mb-3"
                                                        >
                                                            <div class="card-body">
                                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                                    <h6 class="mb-0">Continuação {{ continuationIndex + 1 }}</h6>
                                                                    <button
                                                                        type="button"
                                                                        class="btn btn-sm btn-outline-danger"
                                                                        @click="removeContinuation(roomIndex, wallIndex, continuationIndex)"
                                                                    >
                                                                        <i class="fa fa-trash"></i>
                                                                    </button>
                                                                </div>

                                                        <div class="mb-3">
                                                                    <label :for="`continuation-direction-${roomIndex}-${wallIndex}-${continuationIndex}`" class="form-label">
                                                                        Direção
                                                                    </label>
                                                            <select
                                                                        v-model="continuation.direction"
                                                                        :id="`continuation-direction-${roomIndex}-${wallIndex}-${continuationIndex}`"
                                                                class="form-control"
                                                            >
                                                                <option value="">Selecione a direção</option>
                                                                <option value="left-to-right">Da esquerda para direita</option>
                                                                <option value="right-to-left">Da direita para esquerda</option>
                                                            </select>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                        <label :for="`continuation-width-${roomIndex}-${wallIndex}-${continuationIndex}`" class="form-label">
                                                                            Largura (m)
                                                                        </label>
                                                                <input
                                                                            v-model.number="continuation.width"
                                                                    type="number"
                                                                    step="0.01"
                                                                            :id="`continuation-width-${roomIndex}-${wallIndex}-${continuationIndex}`"
                                                                    class="form-control"
                                                                    placeholder="0.00"
                                                                />
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                        <label :for="`continuation-height-${roomIndex}-${wallIndex}-${continuationIndex}`" class="form-label">
                                                                            Altura (m)
                                                                        </label>
                                                                <input
                                                                            v-model.number="continuation.height"
                                                                    type="number"
                                                                    step="0.01"
                                                                            :id="`continuation-height-${roomIndex}-${wallIndex}-${continuationIndex}`"
                                                                    class="form-control"
                                                                    placeholder="0.00"
                                                                />
                                                            </div>
                                                        </div>

                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Wall Calculation -->
                                                    <div class="alert alert-success" v-if="getWallArea(wall) > 0">
                                                        <strong>Metro:</strong> {{ getWallArea(wall).toFixed(2) }} m
                                                        <br>
                                                        <strong>Quantidade de faixas:</strong> {{ calculateStrips(wall) }}
                                                        <br>
                                                        <strong>Tamanho da faixa:</strong> {{ formatStripHeight(wall) }} m
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <button
                                            type="button"
                                            class="btn btn-outline-primary"
                                            @click="addWall(roomIndex)"
                                        >
                                            <i class="fa fa-plus"></i> Adicionar Parede
                                        </button>
                                    </div>
                                </div>

                            <button
                                type="button"
                                class="btn btn-primary"
                                @click="addRoom"
                            >
                                <i class="fa fa-plus"></i> Adicionar Ambiente
                            </button>
                                </template>
                        </div>
                    </div>

                    <!-- Seção: Definir Modelos -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Definir Modelos</h5>
                            <div class="alert alert-info mb-4">
                                <strong>Cada parede deve conter um modelo:</strong>
                            </div>

                                <div v-for="(room, roomIndex) in budget.rooms" :key="roomIndex" class="card mb-3">
                                    <div class="card-header">
                                        <strong>{{ room.name || `Ambiente ${roomIndex + 1}` }}</strong>
                                    </div>
                                    <div class="card-body">
                                        <div v-for="(wall, wallIndex) in room.walls" :key="wallIndex" class="mb-4 pb-3 border-bottom">
                                            <h6 class="mb-3">{{ wall.name || `Parede ${wallIndex + 1}` }}</h6>
                                            <div v-if="modelsLoading" class="text-center text-muted py-4">
                                                Carregando modelos...
                                            </div>
                                            <div v-else-if="modelsError" class="alert alert-danger" role="alert">
                                                {{ modelsError }}
                                            </div>
                                            <div v-else-if="!productModels.length" class="alert alert-warning" role="alert">
                                                Nenhum modelo disponível. Tente novamente mais tarde.
                                            </div>
                                            <div v-else class="row">
                                                <div class="col-lg-4 col-md-6 mb-3" v-for="model in productModels" :key="model.id">
                                                    <div
                                                        class="card h-100 model-card"
                                                        :class="{ 'border-primary': wall.model === model.id }"
                                                        @click="wall.model = model.id"
                                                        style="cursor: pointer;"
                                                    >
                                                        <div class="card-body d-flex flex-column">
                                                            <div class="mb-2">
                                                                <strong>{{ model.displayName }}</strong>
                                                            </div>
                                                            <div class="small text-muted">
                                                                <div><strong>Valor:</strong> R$ {{ model.value.toFixed(2) }}</div>
                                                                <div><strong>Prazo:</strong> {{ model.deadline }} dia(s)</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar: Frete e Pagamento -->
                <div class="col-12 col-lg-4">
                    <!-- Seção: Frete -->
                    <div class="card mb-4 mt-2">
                        <div class="card-body">
                            <h5 class="card-title">Frete</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="cep" class="form-label">CEP</label>
                                        <input
                                            v-model="budget.cep"
                                            type="text"
                                            id="cep"
                                            class="form-control"
                                            placeholder="00000-000"
                                            maxlength="9"
                                            @input="formatCEP"
                                        />
                                    </div>
                                    <div class="col-md-6 mb-3 d-flex align-items-end">
                                        <button
                                            type="button"
                                            class="btn btn-primary"
                                            @click="calculateFreight"
                                            :disabled="!budget.cep || calculatingFreight"
                                        >
                                            <i class="fa fa-truck"></i> Calcular Frete
                                        </button>
                                    </div>
                                </div>

                                <div v-if="budget.carriers && budget.carriers.length > 0" class="mt-4">
                                    <label class="form-label">Transportadoras Disponíveis</label>
                                    <div class="list-group">
                                        <div
                                            v-for="(carrier, index) in budget.carriers"
                                            :key="index"
                                            class="list-group-item"
                                            :class="{ 'active': budget.selectedCarrier === index }"
                                            @click="budget.selectedCarrier = index"
                                            style="cursor: pointer;"
                                        >
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>{{ carrier.name }}</strong>
                                                    <br>
                                                    <small class="text-muted">Prazo: {{ carrier.deliveryTime }} dias</small>
                                                </div>
                                                <div class="text-end">
                                                    <strong class="text-primary">R$ {{ carrier.price.toFixed(2) }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>

                    <!-- Seção: Pagamento -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Pagamento</h5>
                            <div class="mb-3">
                                <label for="freightValue" class="form-label">Valor do frete</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input
                                        type="text"
                                        id="freightValue"
                                        class="form-control"
                                        :value="budget.selectedCarrier !== null ? budget.carriers[budget.selectedCarrier]?.price.toFixed(2).replace('.', ',') : '0,00'"
                                        readonly
                                    />
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="mb-3">
                                <label class="form-label">Selecione a forma de pagamento</label>
                                <div class="row g-2">
                                    <div class="col-12 mb-2">
                                        <div class="card h-100" :class="{ 'border-primary': budget.paymentMethod === 'pix' }" @click="budget.paymentMethod = 'pix'" style="cursor: pointer;">
                                            <div class="card-body text-center py-3">
                                                <i class="fa fa-qrcode fa-2x mb-2"></i>
                                                <h6 class="mb-1">À Vista (PIX)</h6>
                                                <p class="text-muted small mb-0">Pagamento à vista com desconto</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12" v-if="budget.installmentLimit > 1">
                                        <div class="card h-100" :class="{ 'border-primary': budget.paymentMethod === 'installment' }" @click="budget.paymentMethod = 'installment'" style="cursor: pointer;">
                                            <div class="card-body text-center py-3">
                                                <i class="fa fa-credit-card fa-2x mb-2"></i>
                                                <h6 class="mb-1">A Prazo (Cartão ou Boleto)</h6>
                                                <p class="text-muted small mb-0">Parcelado em até {{ budget.installmentLimit }}x</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="budget.paymentMethod === 'installment'" class="mt-3">
                                    <label for="installments" class="form-label">Número de Parcelas</label>
                                    <select v-model.number="budget.installments" id="installments" class="form-control">
                                        <option v-for="n in budget.installmentLimit" :key="n" :value="n">{{ n }}x</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                     <!-- Seção: Resumo -->
                     <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Resumo</h5>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total de Ambientes:</span>
                                    <strong>{{ budget.rooms.length }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total de Paredes:</span>
                                    <strong>{{ totalWalls }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Metros:</span>
                                    <strong>{{ totalArea.toFixed(2) }}</strong>
                                </div>
                                <div v-if="budget.selectedCarrier !== null" class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Frete:</span>
                                    <strong>R$ {{ budget.carriers[budget.selectedCarrier]?.price.toFixed(2) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Previsão de entrega:</span>
                                    <strong>{{ calculateDeliveryTime(budget) }} dias</strong>
                                </div>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0 fw-semibold">Total à Vista:</h6>
                                    <h5 class="mb-0 text-success">R$ {{ totalBudgetVista.toFixed(2) }}</h5>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0 fw-semibold">Total a Prazo:</h6>
                                    <h5 class="mb-0 text-primary">R$ {{ totalBudgetPrazo.toFixed(2) }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </Page>
    </section>
</template>

<script setup>
import { ref, computed, reactive, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';
import { swalSuccess, swalError } from '../../../utils/alerts';
import Page from '@/components/page/Page.vue';

const router = useRouter();
const route = useRoute();

const calculatingFreight = ref(false);
const saving = ref(false);
const budgetId = ref(null);
const loadingBudget = ref(false);
const originalBudget = ref(null);

// Modelos de produto disponíveis
const productModels = ref([]);
const modelsLoading = ref(false);
const modelsError = ref(null);
const modelSlides = reactive({});

const STRIP_WIDTH = 0.6;
const STRIP_HEIGHT_OPTIONS = [
    1.0, 1.2, 1.5, 1.7, 2.0, 2.2, 2.5, 2.7, 3.0, 3.2, 3.3, 3.4, 3.5, 3.6,
    3.7, 3.8, 3.9, 4.0, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 5.0,
    5.1, 5.2, 5.3, 5.4, 5.5, 6.0, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8,
    6.9, 7.0, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 7.9, 8.0
];

const PRECO_VISTA = 41.90;
const PRECO_PRAZO = 47.90;

const createDefaultContinuation = () => ({
                        direction: '',
                        width: null,
                        height: null,
                        sameArt: false
});

const createDefaultWall = () => ({
    name: '',
    width: null,
    height: null,
    model: null,
    continueSameArt: false,
    continuations: []
});

const showWarning = (message) =>
    Swal.fire({
        title: 'Atenção',
        text: message,
        icon: 'warning'
    });

const extractItemsFromResponse = (payload) => {
    if (!payload) {
        return { items: [], meta: {} };
    }

    if (Array.isArray(payload)) {
        return { items: payload, meta: {} };
    }

    const resourceItems = payload.items?.data ?? payload.items ?? [];
    const meta =
        payload.meta ??
        payload.items?.meta ?? {
            current_page: payload.items?.current_page ?? 1,
            per_page: payload.items?.per_page ?? resourceItems.length,
            total: payload.items?.total ?? resourceItems.length,
            last_page: payload.items?.last_page ?? 1
        };

    return {
        items: resourceItems,
        meta
    };
};

const normalizeCollectionModel = (model = {}) => {
    const files = Array.isArray(model.files)
        ? model.files.map((file) => ({
              id: file.id ?? null,
              name: file.name ?? file.fileName ?? file.file_name ?? 'Arquivo',
              url: file.url ?? file.fileUrl ?? null
          }))
        : [];

    const name = (model.name ?? '').toString().trim();
    const typeName =
        model.type?.name ??
        model.type_name ??
        model.typeModelName ??
        null;

    const displayName = name.length > 0
        ? name
        : model.comment?.trim()
            ? model.comment.trim()
            : `Modelo ${model.id}`;

    return {
        id: Number(model.id),
        displayName,
        typeName,
        value: Number(model.value ?? 0),
        deadline: Number(model.deadline ?? 0),
        requests: {
            link: Boolean(model?.requests?.link),
            comment: Boolean(model?.requests?.comment),
            file: Boolean(model?.requests?.file)
        },
        link: model.link ?? '',
        comment: model.comment ?? '',
        files
    };
};

const budget = reactive({
    id: null,
    name: '',
    status: '',
    rooms: [
        {
            name: '',
            walls: [createDefaultWall()]
        }
    ],
    deliveryTime: 0,
    cep: '',
    carriers: [],
    selectedCarrier: null,
    paymentMethod: '',
    installmentLimit: 12,
    installments: 1,
    total_amount: 0,
    total_amount_installments: 0
});

async function fetchCollectionModels() {
    modelsLoading.value = true;
    modelsError.value = null;

    try {
        const { data } = await axios.get('v1/collection-models', {
            params: { per_page: 100 }
        });

        const payload = data?.data;
        const { items } = extractItemsFromResponse(payload);

    const normalized = items.map(normalizeCollectionModel);
        const availableIds = new Set(normalized.map((item) => item.id));

        productModels.value = normalized;

    // Initialize or clamp carousel indices
    normalized.forEach((model) => {
        if (typeof modelSlides[model.id] !== 'number' || Number.isNaN(modelSlides[model.id])) {
            modelSlides[model.id] = 0;
        } else {
            const maxIndex = Math.max(0, model.files.length - 1);
            modelSlides[model.id] = Math.min(Math.max(modelSlides[model.id], 0), maxIndex);
        }
    });

    // Remove slide states for removed models
    Object.keys(modelSlides).forEach((id) => {
        const numericId = Number(id);
        if (!availableIds.has(numericId)) {
            delete modelSlides[id];
        }
    });

        budget.rooms.forEach((room) => {
            room.walls.forEach((wall) => {
                if (wall.model && !availableIds.has(wall.model)) {
                    wall.model = null;
                }
            });
        });
    } catch (error) {
        modelsError.value =
            error?.response?.data?.message ||
            'Não foi possível carregar os modelos. Tente novamente.';
    } finally {
        modelsLoading.value = false;
    }
}

const productModelsMap = computed(() => {
    const map = new Map();
    productModels.value.forEach((model) => {
        map.set(model.id, model);
    });
    return map;
});

const getModelById = (id) => productModelsMap.value.get(id);

const showPrevImage = (modelId) => {
    if (!modelSlides[modelId]) {
        modelSlides[modelId] = 0;
    }

    const model = getModelById(modelId);
    if (!model || !model.files.length) {
        return;
    }

    modelSlides[modelId] =
        (modelSlides[modelId] - 1 + model.files.length) % model.files.length;
};

const showNextImage = (modelId) => {
    if (!modelSlides[modelId]) {
        modelSlides[modelId] = 0;
    }

    const model = getModelById(modelId);
    if (!model || !model.files.length) {
        return;
    }

    modelSlides[modelId] = (modelSlides[modelId] + 1) % model.files.length;
};

// Detectar mudanças
const hasChanges = computed(() => {
    if (!originalBudget.value) return false;

    const original = JSON.stringify(originalBudget.value);
    const current = JSON.stringify({
        name: budget.name,
        status: budget.status,
        rooms: budget.rooms,
        cep: budget.cep,
        selectedCarrier: budget.selectedCarrier,
        paymentMethod: budget.paymentMethod,
        installments: budget.installments
    });

    return original !== current;
});

// Normalizar dados do orçamento da API
function normalizeBudgetFromAPI(budgetData) {
    const rooms = [];

    if (budgetData.rooms && Array.isArray(budgetData.rooms)) {
        budgetData.rooms.forEach((room) => {
            const walls = [];

            if (room.walls && Array.isArray(room.walls)) {
                room.walls.forEach((wall) => {
                    const wallData = {
                        name: wall.name || '',
                        width: wall.width ? Number(wall.width) : null,
                        height: wall.height ? Number(wall.height) : null,
                        model: (wall.collection_model_id || wall.collection_model?.id) ? Number(wall.collection_model_id || wall.collection_model?.id) : null,
                        continueSameArt: false,
                        continuations: []
                    };

                    // Processar continuações se existirem
                    if (wall.continue_same_art || (wall.continuations && Array.isArray(wall.continuations) && wall.continuations.length > 0)) {
                        wallData.continueSameArt = Boolean(wall.continue_same_art);
                        if (wall.continuations && Array.isArray(wall.continuations)) {
                            wallData.continuations = wall.continuations.map(cont => ({
                                direction: cont.direction || '',
                                width: cont.width ? Number(cont.width) : null,
                                height: cont.height ? Number(cont.height) : null,
                                sameArt: false
                            }));
                        }
                    }

                    walls.push(wallData);
                });
            }

            rooms.push({
                name: room.name || '',
                walls: walls.length > 0 ? walls : [createDefaultWall()]
            });
        });
    }

    // Encontrar transportadora selecionada
    let selectedCarrierIndex = null;
    if (budgetData.selected_carrier_name && budgetData.carriers_snapshot) {
        const carriers = Array.isArray(budgetData.carriers_snapshot)
            ? budgetData.carriers_snapshot
            : [];
        selectedCarrierIndex = carriers.findIndex(c => c.name === budgetData.selected_carrier_name);
        if (selectedCarrierIndex < 0) selectedCarrierIndex = null;
    }

    return {
        id: budgetData.id,
        name: budgetData.name || '',
        status: budgetData.status || '',
        rooms: rooms.length > 0 ? rooms : [{ name: '', walls: [createDefaultWall()] }],
        cep: budgetData.cep || '',
        carriers: budgetData.carriers_snapshot || [],
        selectedCarrier: selectedCarrierIndex,
        paymentMethod: budgetData.payment_method || '',
        installmentLimit: budgetData.installment_limit || 12,
        installments: budgetData.installments || 1,
        total_amount: budgetData.total_amount ? Number(budgetData.total_amount) : 0,
        total_amount_installments: budgetData.total_amount_installments ? Number(budgetData.total_amount_installments) : 0
    };
}

// Carregar orçamento
async function loadBudget() {
    const id = route.params.id;
    if (!id) {
        swalError('ID do orçamento não encontrado');
        router.push('/budget');
        return;
    }

    budgetId.value = Number(id);
    loadingBudget.value = true;

    try {
        // Buscar da lista de orçamentos
        const { data } = await axios.get('v1/budgets');
        const budgets = data?.data?.data ?? data?.data ?? [];
        const budgetData = budgets.find(b => b.id === budgetId.value);

        if (!budgetData) {
            swalError('Orçamento não encontrado');
            router.push('/budget');
            return;
        }

        // Normalizar e carregar dados
        const normalized = normalizeBudgetFromAPI(budgetData);
        Object.assign(budget, normalized);
        originalBudget.value = JSON.parse(JSON.stringify(normalized));

    } catch (error) {
        console.error('Erro ao carregar orçamento:', error);
        swalError('Não foi possível carregar o orçamento');
        router.push('/budget');
    } finally {
        loadingBudget.value = false;
    }
}

onMounted(() => {
    fetchCollectionModels();
    loadBudget();
});

watch(
    () => budget.rooms.map((room) =>
        room.walls.map((wall) => ({
            width: wall.width,
            height: wall.height,
            continueSameArt: wall.continueSameArt
        }))
    ),
    () => {
        budget.rooms.forEach((room) => {
            room.walls.forEach((wall) => {
                if (!wall.width || !wall.height) {
                    if (wall.continueSameArt || (Array.isArray(wall.continuations) && wall.continuations.length)) {
                        wall.continueSameArt = false;
                        wall.continuations = [];
                    }
                }
            });
        });
    },
    { deep: true }
);

// Computed
const totalWalls = computed(() => {
    return budget.rooms.reduce((total, room) => total + room.walls.length, 0);
});

const totalArea = computed(() => {
    let area = 0;
    budget.rooms.forEach(room => {
        room.walls.forEach(wall => {
            area += getWallArea(wall);
        });
    });
    return area;
});

// Calcular custo dos modelos
const totalModelsCost = computed(() => {
    let total = 0;
    budget.rooms.forEach(room => {
        room.walls.forEach(wall => {
            if (wall.model) {
                const model = getModelById(wall.model);
                if (model) {
                    total += model.value;
                }
            }
        });
    });
    return total;
});

// Calcular custo do frete
const freightCost = computed(() => {
    if (budget.selectedCarrier !== null && budget.carriers[budget.selectedCarrier]) {
        return budget.carriers[budget.selectedCarrier].price;
    }
    return 0;
});

// Calcular total à vista dinamicamente
const calculatedTotalBudgetVista = computed(() => {
    return (totalArea.value * PRECO_VISTA) + totalModelsCost.value + freightCost.value;
});

// Calcular total a prazo dinamicamente
const calculatedTotalBudgetPrazo = computed(() => {
    return (totalArea.value * PRECO_PRAZO) + totalModelsCost.value + freightCost.value;
});

// Total à vista: usar valor do banco se não houver mudanças, senão calcular dinamicamente
const totalBudgetVista = computed(() => {
    if (hasChanges.value) {
        return calculatedTotalBudgetVista.value;
    }
    // Por padrão, usar valores do banco que vieram na requisição
    return budget.total_amount || calculatedTotalBudgetVista.value;
});

// Total a prazo: usar valor do banco se não houver mudanças, senão calcular dinamicamente
const totalBudgetPrazo = computed(() => {
    if (hasChanges.value) {
        return calculatedTotalBudgetPrazo.value;
    }
    // Por padrão, usar valores do banco que vieram na requisição
    return budget.total_amount_installments || calculatedTotalBudgetPrazo.value;
});

// Total baseado na forma de pagamento selecionada
const totalBudget = computed(() => {
    if (budget.paymentMethod === 'pix') {
        return totalBudgetVista.value;
    } else if (budget.paymentMethod === 'installment') {
        return totalBudgetPrazo.value;
    }
    return 0;
});

// Methods
function validateBudget() {
    if (!budget.name) {
        showWarning('Por favor, informe o nome do orçamento');
        return false;
    }

    // Validar se há pelo menos um ambiente com pelo menos uma parede
    if (budget.rooms.length === 0) {
        showWarning('Por favor, adicione pelo menos um ambiente');
        return false;
    }

    const isValidDimension = (value) =>
        typeof value === 'number' && !Number.isNaN(value) && value > 0;

    for (let roomIndex = 0; roomIndex < budget.rooms.length; roomIndex++) {
        const room = budget.rooms[roomIndex];
        const roomLabel = room.name?.trim() || `Ambiente ${roomIndex + 1}`;

        if (!room.name || !room.name.toString().trim()) {
            showWarning(`Informe o nome do ${roomLabel}.`);
            return false;
        }

        if (!room.walls.length) {
            showWarning(`Adicione pelo menos uma parede em ${roomLabel}.`);
            return false;
        }

        for (let wallIndex = 0; wallIndex < room.walls.length; wallIndex++) {
            const wall = room.walls[wallIndex];
            const wallLabel = wall.name?.trim() || `Parede ${wallIndex + 1}`;

            if (!wall.name || !wall.name.toString().trim()) {
                showWarning(`Informe o nome da ${wallLabel} em ${roomLabel}.`);
                return false;
            }

            if (!isValidDimension(wall.width) || !isValidDimension(wall.height)) {
                showWarning(
                    `Informe largura e altura válidas para ${wallLabel} em ${roomLabel}.`
                );
                return false;
            }

            if (wall.continueSameArt && Array.isArray(wall.continuations)) {
                for (
                    let continuationIndex = 0;
                    continuationIndex < wall.continuations.length;
                    continuationIndex++
                ) {
                    const continuation = wall.continuations[continuationIndex];
                    if (!isValidDimension(continuation.width)) {
                        showWarning(
                            `Informe a largura da continuação ${continuationIndex + 1} em ${wallLabel} (${roomLabel}).`
                        );
                        return false;
                    }
                    if (!isValidDimension(continuation.height)) {
                        showWarning(
                            `Informe a altura da continuação ${continuationIndex + 1} em ${wallLabel} (${roomLabel}).`
                        );
                        return false;
                    }
                }
            }

            if (!wall.model) {
                showWarning(`Por favor, selecione um modelo para ${wallLabel} em ${roomLabel}`);
                return false;
            }
        }
    }

    if (modelsLoading.value) {
        showWarning('Aguarde o carregamento dos modelos antes de salvar.');
        return false;
    }

    if (!productModels.value.length) {
        showWarning('Nenhum modelo disponível no momento.');
        return false;
    }

    if (budget.cep && budget.cep.length >= 8 && budget.selectedCarrier === null) {
        showWarning('Por favor, selecione uma transportadora ou remova o CEP');
        return false;
    }

    if (!budget.paymentMethod) {
        showWarning('Por favor, selecione uma forma de pagamento');
        return false;
    }

    return true;
}

function addRoom() {
    budget.rooms.push({
        name: '',
        walls: [createDefaultWall()]
    });
}

function removeRoom(index) {
    budget.rooms.splice(index, 1);
}

function addWall(roomIndex) {
    budget.rooms[roomIndex].walls.push(createDefaultWall());
}

function removeWall(roomIndex, wallIndex) {
    budget.rooms[roomIndex].walls.splice(wallIndex, 1);
}

function addContinuation(roomIndex, wallIndex) {
    const wall = budget.rooms[roomIndex].walls[wallIndex];
    if (!Array.isArray(wall.continuations)) {
        wall.continuations = [];
    }
    if (!wall.continueSameArt) {
        wall.continueSameArt = true;
    }
    wall.continuations.push(createDefaultContinuation());
}

function removeContinuation(roomIndex, wallIndex, continuationIndex) {
    const wall = budget.rooms[roomIndex].walls[wallIndex];
    if (!Array.isArray(wall.continuations)) {
        return;
    }
    wall.continuations.splice(continuationIndex, 1);
}

function handleContinuationToggle(roomIndex, wallIndex) {
    const wall = budget.rooms[roomIndex].walls[wallIndex];

    if (!wall.continueSameArt) {
        wall.continuations = [];
        return;
    }

    if (!wall.width || !wall.height) {
        wall.continueSameArt = false;
        wall.continuations = [];
        return;
    }

    if (!Array.isArray(wall.continuations) || !wall.continuations.length) {
        wall.continuations = [createDefaultContinuation()];
    }
}

function getWallContinuations(wall) {
    if (!wall.continueSameArt) {
        return [];
    }

    return Array.isArray(wall.continuations) ? wall.continuations : [];
}

function getWallArea(wall) {
    const strips = calculateStrips(wall);
    const stripHeight = calculateStripHeight(wall);

    if (strips > 0 && stripHeight) {
        return strips * stripHeight;
    }

    return 0;
}

function getStripCalculation(wall) {
    const baseWidth = Number(wall.width) || 0;
    const continuationWidth = getWallContinuations(wall).reduce((sum, continuation) => {
        const width = Number(continuation.width) || 0;
        return sum + width;
    }, 0);
    const width = baseWidth + continuationWidth;

    const heights = [];
    const baseHeight = Number(wall.height) || 0;
    if (baseHeight) {
        heights.push(baseHeight);
    }

    getWallContinuations(wall).forEach((continuation) => {
        const continuationHeight = Number(continuation.height) || 0;
        if (continuationHeight) {
            heights.push(continuationHeight);
        }
    });

    const height = heights.length ? Math.max(...heights) : 0;

    if (!width || !height) {
        return {
            numberOfStrips: 0,
            stripHeight: null
        };
    }

    let numberOfStrips = Math.ceil(width / STRIP_WIDTH);
    const stripHeight = STRIP_HEIGHT_OPTIONS.find(
        (alt) => alt >= height + 0.09
    );

    if (stripHeight && stripHeight >= 6 && numberOfStrips % 2 !== 0) {
        numberOfStrips += 1;
    }

    return {
        numberOfStrips,
        stripHeight: stripHeight || null
    };
}

function calculateStrips(wall) {
    return getStripCalculation(wall).numberOfStrips;
}

function calculateStripHeight(wall) {
    return getStripCalculation(wall).stripHeight;
}

function formatStripHeight(wall) {
    const height = calculateStripHeight(wall);
    return height ? height.toFixed(2) : 'N/D';
}

function formatCEP(event) {
    let value = event.target.value.replace(/\D/g, '');
    if (value.length > 5) {
        value = value.substring(0, 5) + '-' + value.substring(5, 8);
    }
    budget.cep = value;
}

function calculateFreight() {
    calculatingFreight.value = true;

    // Simulação de cálculo de frete
    setTimeout(() => {
        budget.carriers = [
            {
                name: 'Transportadora A',
                price: 45.90,
                deliveryTime: 5
            },
            {
                name: 'Transportadora B',
                price: 38.50,
                deliveryTime: 7
            },
            {
                name: 'Transportadora C',
                price: 52.00,
                deliveryTime: 3
            }
        ];
        calculatingFreight.value = false;
    }, 1000);
}

function calculateDeliveryTime(budget) {
    // Encontrar o maior tempo de desenvolvimento de arte entre todas as paredes
    let maxDevelopmentTime = 0;

    budget.rooms.forEach(room => {
        room.walls.forEach(wall => {
            if (wall.model) {
                const model = getModelById(wall.model);
                if (model) {
                    const days = Math.max(0, Number(model.deadline ?? 0));
                    if (days > maxDevelopmentTime) {
                        maxDevelopmentTime = days;
                    }
                }
            }
        });
    });

    // Tempo de produção base (5 dias) + maior tempo de desenvolvimento + tempo de frete
    const productionTime = 5;
    const freightTime = budget.carriers[budget.selectedCarrier]?.deliveryTime || 0;

    budget.deliveryTime = productionTime + maxDevelopmentTime + freightTime;

    return budget.deliveryTime;
}

function updateBudget() {
    if (!validateBudget()) {
        return;
    }

    saving.value = true;

    const payload = JSON.parse(JSON.stringify(budget));
    if (
        payload.selectedCarrier !== null &&
        Array.isArray(payload.carriers) &&
        payload.carriers[payload.selectedCarrier]
    ) {
        payload.selectedCarrier = payload.carriers[payload.selectedCarrier];
    } else {
        payload.selectedCarrier = null;
    }

    delete payload.carriers;
    delete payload.id;

    if (payload.paymentMethod === 'pix') {
        payload.installmentLimit = null;
        payload.installments = null;
    }

    axios.put(`v1/budgets/${budgetId.value}`, payload)
        .then(response => {
            console.log('Orçamento atualizado:', response.data);
            swalSuccess('Orçamento atualizado com sucesso!');
            // Atualizar originalBudget e budget para refletir as mudanças salvas
            const updatedData = response.data?.data || budget;
            const normalized = normalizeBudgetFromAPI(updatedData);
            // Atualizar valores salvos no budget
            budget.total_amount = normalized.total_amount;
            budget.total_amount_installments = normalized.total_amount_installments;
            originalBudget.value = JSON.parse(JSON.stringify(normalized));
            // Redirecionar para a lista de orçamentos
            setTimeout(() => {
                router.push('/budget');
            }, 1500);
        })
        .catch(error => {
            console.error('Erro ao atualizar orçamento:', error);
            const message = error.response?.data?.message || 'Tente novamente mais tarde.';
            swalError('Erro ao atualizar orçamento: ' + message);
        })
        .finally(() => {
            saving.value = false;
        });
}
</script>

<style lang="scss" scoped>

.card[style*="cursor: pointer"] {
    transition: all 0.2s ease;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    &.border-primary {
        box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.1);
    }
}

.list-group-item {
    transition: all 0.2s ease;

    &:hover {
        background-color: var(--bs-tertiary-bg);
    }

    &.active {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
        color: white;

        .text-muted {
            color: rgba(255, 255, 255, 0.8) !important;
        }

        .text-primary {
            color: white !important;
        }
    }
}

.model-card {
    transition: all 0.2s ease;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    &.border-primary {
        box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.1);
    }
}

.model-preview {
    overflow: hidden;
    border-radius: 0.5rem;

    img {
        width: 100%;
        height: 180px;
        object-fit: cover;
        display: block;
    }

    .carousel-control {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 0, 0, 0.45);
        color: #fff;
        border: none;
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s ease;

        &:hover {
            background: rgba(0, 0, 0, 0.65);
        }

        &.prev {
            left: 0.5rem;
        }

        &.next {
            right: 0.5rem;
        }

        i {
            font-size: 0.9rem;
        }
    }

    .carousel-indicators {
        position: absolute;
        bottom: 0.5rem;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 0.35rem;

        span {
            width: 0.6rem;
            height: 0.6rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;

            &.active {
                background: rgba(255, 255, 255, 0.95);
                transform: scale(1.1);
            }
        }
    }
}
</style>

