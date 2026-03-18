<template>
    <section class="content">
        <Page title="Criar orçamento">
            <template #actions>
                <button class="btn btn-primary me-3" type="button" @click="saveBudget" :disabled="saving">
                    {{ saving ? 'Salvando...' : 'Salvar Orçamento' }}
                </button>
            </template>

        <div v-if="loading" class="text-center text-muted py-5">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>

        <div v-else class="container py-4">
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

                            <!-- Checkbox Dropshipping (apenas para admin ou is_dropshipping === 1) -->
                            <div v-if="canEnableDropshipping" class="mb-3">
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        v-model="enableDropshipping"
                                        id="enableDropshipping"
                                    />
                                    <label class="form-check-label" for="enableDropshipping">
                                        Habilitar Dropshipping
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulário de Dropshipping -->
                    <DropshippingForm
                        :enabled="enableDropshipping"
                        v-model="budget.dropshipping_data"
                        ref="dropshippingFormRef"
                    />

                    <!-- Seção: Ambientes e Paredes -->
                    <div class="card mb-4">
                        <div class="card-body">
                                <h5 class="card-title">Cômodos</h5>
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
                                                                    <h6 class="mb-0">Continuação {{ continuationIndex + 1 }} {{ continuation.name }}</h6>
                                                                    <button
                                                                        type="button"
                                                                        class="btn btn-sm btn-outline-danger"
                                                                        @click="removeContinuation(roomIndex, wallIndex, continuationIndex)"
                                                                    >
                                                                        <i class="fa fa-trash"></i>
                                                                    </button>
                                                                </div>

                                                        <div class="mb-3">
                                                                    <label :for="`continuation-name-${roomIndex}-${wallIndex}-${continuationIndex}`" class="form-label">
                                                                        Nome da continuação
                                                                    </label>
                                                                <input
                                                                    v-model="continuation.name"
                                                                    type="text"
                                                                    :id="`continuation-name-${roomIndex}-${wallIndex}-${continuationIndex}`"
                                                                    class="form-control"
                                                                    placeholder="Ex: Armário"
                                                                />
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
                                                        <strong>Metros:</strong> {{ getWallArea(wall).toFixed(2) }}
                                                        <br>
                                                        <strong>Quantidade de faixas:</strong> {{ calculateStrips(wall) }}
                                                        <br>
                                                        <strong>Tamanho da faixa:</strong> {{ formatStripHeight(wall) }} m
                                                    </div>
                                                    <p
                                                        v-if="formatStripHeight(wall) > 6"
                                                        class="mb-0 text-danger small"
                                                    >
                                                        Obs.:<br />
                                                        Faixas maiores que 6 metros são vendidas apenas em pares.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <button
                                            type="button"
                                            class="btn btn-outline-primary"
                                            @click="addWall(roomIndex)"
                                        >
                                            <i class="fa fa-plus fa-fw"></i> Adicionar Parede
                                        </button>
                                    </div>
                                </div>

                            <button
                                type="button"
                                class="btn btn-primary"
                                @click="addRoom"
                            >
                                <i class="fa fa-plus fa-fw"></i> Adicionar Ambiente
                            </button>
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
                                                                <div><strong>Valor:</strong> {{ formatCurrency(model.value) }}</div>
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
                    <div class="card mb-4">
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
                                            Calcular Frete
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
                                                    <strong class="text-primary">{{ formatCurrency(carrier.price) }}</strong>
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
                                        <div class="card h-100" :class="{ 'border-primary': budget.paymentMethod === 'credit_card' }" @click="budget.paymentMethod = 'credit_card'" style="cursor: pointer;">
                                            <div class="card-body text-center py-3">
                                                <i class="fa fa-credit-card fa-2x mb-2"></i>
                                                <h6 class="mb-1">A Prazo (Cartão ou Boleto)</h6>
                                                <p class="text-muted small mb-0">Parcelado em até {{ budget.installmentLimit }}x</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="budget.paymentMethod === 'credit_card'" class="mt-3">
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
                                    <strong>{{ formatCurrency(budget.carriers[budget.selectedCarrier]?.price) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Previsão de entrega:</span>
                                    <strong>{{ deliveryTimeDisplay }} dias</strong>
                                </div>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0 fw-semibold">Total à Vista:</h6>
                                    <h5 class="mb-0 text-success">{{ formatCurrency(totalBudgetVista) }}</h5>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0 fw-semibold">Total a Prazo:</h6>
                                    <h5 class="mb-0 text-primary">{{ formatCurrency(totalBudgetPrazo) }}</h5>
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
import { useRouter } from 'vue-router';
import Page from '@/components/page/Page.vue';
import { useAuthStore } from '@/stores/auth';
import DropshippingForm from '@/modules/budgets/components/DropshippingForm.vue';
import { useBudgetService } from '@/modules/budgets/services/budgetService';
import { createDefaultWall } from '@/modules/budgets/composables/useBudgetUtils';
import { useBudgetCalculations } from '@/modules/budgets/composables/useBudgetCalculations';
import { validateBudget } from '@/modules/budgets/composables/useBudgetValidation';
import { useBudgetStructure } from '@/modules/budgets/composables/useBudgetStructure';
import { useBudgetFormatters } from '@/modules/budgets/composables/useBudgetFormatters';
import { useBudgetModels } from '@/modules/budgets/composables/useBudgetModels';
import { useFormatting } from '@/composables/useFormatting';

const router = useRouter();
const auth = useAuthStore();
const budgetService = useBudgetService();

const calculatingFreight = ref(false);
const saving = ref(false);
const enableDropshipping = ref(false);
const dropshippingFormRef = ref(null);
const tinyErpProducts = ref([]);
const loading = ref(false);
const PRECO_VISTA = ref(0);
const PRECO_PRAZO = ref(0);

const canEnableDropshipping = computed(() => {
  const user = auth.user;
  return auth.hasRole('admin') || user?.is_dropshipping === 1 || user?.is_dropshipping === true;
});

const showWarning = (message) =>
    window.Swal.fire({
        title: 'Atenção',
        text: message,
        icon: 'warning'
    });

const budget = reactive({
    name: '',
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
    dropshipping_budget: 0,
    dropshipping_data: {}
});

// Composables
const {
    productModels,
    modelsLoading,
    modelsError,
    modelSlides,
    getModelById,
    fetchCollectionModels
} = useBudgetModels(budget);

const {
    totalWalls,
    totalArea,
    totalModelsCost,
    freightCost,
    totalBudgetVista,
    totalBudgetPrazo,
    totalBudget,
    getWallArea,
    calculateStrips,
    calculateStripHeight,
    calculateDeliveryTime
} = useBudgetCalculations(budget, getModelById, PRECO_VISTA, PRECO_PRAZO, computed(() => false));

const {
    addRoom,
    removeRoom,
    addWall,
    removeWall,
    addContinuation,
    removeContinuation,
    handleContinuationToggle
} = useBudgetStructure(budget);

const { formatStripHeight, formatCEP: formatCEPValue } = useBudgetFormatters();
const { formatCurrency } = useFormatting();

// Wrapper para formatCEP que recebe event
function formatCEP(event) {
    const formatted = formatCEPValue(event.target.value);
    budget.cep = formatted;
}

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

onMounted(() => {
    fetchCollectionModels();
    searchTinyErpProducts();
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

async function calculateFreight() {
    if (!budget.cep) {
        return;
    }

    calculatingFreight.value = true;
    try {
        const carriers = await budgetService.calculateFreight(budget.cep, tinyErpProducts.value);

        budget.carriers = carriers;

        if (budget.carriers.length === 0) {
            budget.selectedCarrier = null;
        } else if (budget.selectedCarrier !== null && budget.selectedCarrier >= budget.carriers.length) {
            budget.selectedCarrier = null;
        }

        calculatingFreight.value = false;
    } catch (error) {
        console.error('Erro ao calcular frete:', error);
        window.Swal.fire({
            title: 'Erro ao calcular frete!',
            text: 'Não foi possível calcular o frete. Tente novamente mais tarde.',
            confirmButtonText: 'Entendi!',
        });
        calculatingFreight.value = false;
    }
}

function calculateDeliveryTimeWrapper() {
    const deliveryTime = calculateDeliveryTime();
    budget.deliveryTime = deliveryTime;
    return deliveryTime;
}

// Computed para deliveryTime que recalcula quando necessário
const deliveryTimeDisplay = computed(() => {
    return calculateDeliveryTime();
});

function saveBudget() {
    if (!validateBudget(budget, modelsLoading, productModels, showWarning)) {
        return;
    }

    // Validar dropshipping se estiver habilitado
    if (enableDropshipping.value && dropshippingFormRef.value) {
        const isValid = dropshippingFormRef.value.validate();
        if (!isValid) {
            showWarning('Por favor, preencha todos os campos obrigatórios do formulário de dropshipping');
            return;
        }
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

    if (payload.paymentMethod === 'pix') {
        payload.installmentLimit = null;
        payload.installments = null;
    }

    // Configurar dropshipping_budget e dropshipping_data
    payload.dropshipping_budget = enableDropshipping.value ? 1 : 0;

    if (enableDropshipping.value && dropshippingFormRef.value) {
        payload.dropshipping_data = dropshippingFormRef.value.getData();
    } else {
        delete payload.dropshipping_data;
    }

    budgetService.createBudget(payload)
        .then(response => {
            const budgetData = response?.data || response;
            console.log('Orçamento salvo:', budgetData);
            window.Swal.fire({
                title: 'Orçamento criado!',
                text: response?.message || 'Orçamento foi criado com sucesso!',
                confirmButtonText: 'Entendi!',
            });
            setTimeout(() => {
                router.push({ name: 'BudgetList' });
            }, 1500);
        })
        .catch(error => {
            console.error('Erro ao salvar orçamento:', error);
            const message = error.response?.data?.message || 'Tente novamente mais tarde.';
            window.Swal.fire({
                title: 'Erro ao criar orçamento!',
                text: message ?? 'Tente novamente mais tarde.',
                confirmButtonText: 'Entendi!',
            });
        })
        .finally(() => {
            saving.value = false;
        });
}

async function searchTinyErpProducts() {
    try {
        loading.value = true;
        const data = await budgetService.getTinyErpProducts();
        tinyErpProducts.value = data;
        PRECO_VISTA.value = tinyErpProducts.value.precoPromocionalVista;
        PRECO_PRAZO.value = tinyErpProducts.value.precoPromocionalPrazo;
    } catch (error) {
        console.error('Erro ao buscar produtos:', error);
        window.Swal.fire({
            title: 'Erro ao buscar produtos!',
            text: 'Não foi possível buscar os produtos do Tiny ERP. Tente novamente mais tarde.',
            confirmButtonText: 'Entendi!',
        });
        tinyErpProducts.value = [];
    } finally {
        loading.value = false;
    }
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

</style>
