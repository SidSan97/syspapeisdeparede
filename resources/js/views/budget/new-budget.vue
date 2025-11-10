<template>
    <section class="content">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Novo Orçamento</h3>
                        </div>
                        <div class="card-body">
                            <!-- Progress Steps -->
                            <div class="budget-steps mb-4">
                                <div class="step" :class="{ 'active': currentStep >= 1, 'completed': currentStep > 1 }">
                                    <div class="step-number">1</div>
                                    <div class="step-label">Informações Básicas</div>
                                </div>
                                <div class="step" :class="{ 'active': currentStep >= 2, 'completed': currentStep > 2 }">
                                    <div class="step-number">2</div>
                                    <div class="step-label">Ambientes</div>
                                </div>
                                <div class="step" :class="{ 'active': currentStep >= 3, 'completed': currentStep > 3 }">
                                    <div class="step-number">3</div>
                                    <div class="step-label">Produto</div>
                                </div>
                                <div class="step" :class="{ 'active': currentStep >= 4, 'completed': currentStep > 4 }">
                                    <div class="step-number">4</div>
                                    <div class="step-label">Frete</div>
                                </div>
                                <div class="step" :class="{ 'active': currentStep >= 5, 'completed': currentStep > 5 }">
                                    <div class="step-number">5</div>
                                    <div class="step-label">Pagamento</div>
                                </div>
                                <div class="step" :class="{ 'active': currentStep >= 6, 'completed': currentStep > 6 }">
                                    <div class="step-number">6</div>
                                    <div class="step-label">Finalizar</div>
                                </div>
                            </div>

                            <hr>

                            <!-- Step 1: Budget Name -->
                            <div v-show="currentStep === 1" class="step-content">
                                <h5 class="mb-4">Passo 1: Nome do Orçamento</h5>
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
                            </div>

                            <!-- Step 2: Rooms and Walls -->
                            <div v-show="currentStep === 2" class="step-content">
                                <h5 class="mb-4">Passo 2: Ambientes e Paredes</h5>

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
                                                        <strong>Área total:</strong> {{ getWallArea(wall).toFixed(2) }} m²
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
                            </div>

                            <!-- Step 3: Product Model -->
                            <div v-show="currentStep === 3" class="step-content">
                                <h5 class="mb-4">Passo 3: Definir Modelos</h5>
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
                                                            <div v-if="model.files.length" class="model-preview mb-3 position-relative">
                                                                <img
                                                                    :src="model.files[modelSlides[model.id] || 0]?.url"
                                                                    :alt="model.files[modelSlides[model.id] || 0]?.name"
                                                                    class="img-fluid rounded w-100"
                                                                />
                                                                <button
                                                                    v-if="model.files.length > 1"
                                                                    type="button"
                                                                    class="carousel-control prev"
                                                                    @click.stop="showPrevImage(model.id)"
                                                                >
                                                                    <i class="fa fa-chevron-left"></i>
                                                                </button>
                                                                <button
                                                                    v-if="model.files.length > 1"
                                                                    type="button"
                                                                    class="carousel-control next"
                                                                    @click.stop="showNextImage(model.id)"
                                                                >
                                                                    <i class="fa fa-chevron-right"></i>
                                                                </button>
                                                                <div v-if="model.files.length > 1" class="carousel-indicators">
                                                                    <span
                                                                        v-for="(file, index) in model.files"
                                                                        :key="file.id || file.name || index"
                                                                        :class="{ active: (modelSlides[model.id] || 0) === index }"
                                                                        @click.stop="modelSlides[model.id] = index"
                                                                    />
                                                                </div>
                                                            </div>
                                                            <div class="mb-2">
                                                                <strong>{{ model.displayName }}</strong>
                                                                <div v-if="model.typeName" class="text-muted small">
                                                                    {{ model.typeName }}
                                                                </div>
                                                            </div>
                                                            <div class="small text-muted">
                                                                <div><strong>Valor:</strong> R$ {{ model.value.toFixed(2) }}</div>
                                                                <div><strong>Prazo:</strong> {{ model.deadline }} dia(s)</div>
                                                                <div v-if="model.link">
                                                                    <strong>Link:</strong>
                                                                    <a :href="model.link" target="_blank" rel="noopener">
                                                                        {{ model.link }}
                                                                    </a>
                                                                </div>
                                                                <div v-if="model.comment">
                                                                    <strong>Observação:</strong> {{ model.comment }}
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

                <!-- Step 4: Freight -->
                <div v-show="currentStep === 4" class="step-content">
                                <h5 class="mb-4">Passo 4: Cálculo de Frete</h5>
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

                            <!-- Step 5: Payment -->
                            <div v-show="currentStep === 5" class="step-content">
                                <h5 class="mb-4">Passo 5: Forma de Pagamento</h5>
                                <div class="mb-3">
                                    <label class="form-label">Selecione a forma de pagamento</label>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100" :class="{ 'border-primary': budget.paymentMethod === 'pix' }" @click="budget.paymentMethod = 'pix'" style="cursor: pointer;">
                                                <div class="card-body text-center">
                                                    <i class="fa fa-qrcode fa-3x mb-3"></i>
                                                    <h6>À Vista (PIX)</h6>
                                                    <p class="text-muted small mb-0">Pagamento à vista com desconto</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3" v-if="budget.installmentLimit > 1">
                                            <div class="card h-100" :class="{ 'border-primary': budget.paymentMethod === 'installment' }" @click="budget.paymentMethod = 'installment'" style="cursor: pointer;">
                                                <div class="card-body text-center">
                                                    <i class="fa fa-credit-card fa-3x mb-3"></i>
                                                    <h6>A Prazo (Cartão ou Boleto)</h6>
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

                            <!-- Step 6: Summary -->
                            <div v-show="currentStep === 6" class="step-content">
                                <h5 class="mb-4">Passo 6: Resumo do Orçamento</h5>

                                <div class="card mb-3">
                                    <div class="card-header">
                                        <strong>Informações do Orçamento</strong>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Nome:</strong> {{ budget.name || 'Não informado' }}</p>
                                        <p><strong>Total de Ambientes:</strong> {{ budget.rooms.length }}</p>
                                        <p><strong>Total de Paredes:</strong> {{ totalWalls }}</p>
                                        <p><strong>Área Total:</strong> {{ totalArea.toFixed(2) }} m²</p>

                                        <div class="mb-3">
                                            <strong>Modelos Selecionados:</strong>
                                            <div v-for="(room, roomIndex) in budget.rooms" :key="roomIndex" class="mt-2">
                                                <strong>{{ room.name || `Ambiente ${roomIndex + 1}` }}:</strong>
                                                <ul class="mb-0">
                                                    <li v-for="(wall, wallIndex) in room.walls" :key="wallIndex">
                                                        {{ wall.name || `Parede ${wallIndex + 1}` }}:
                                                        <template v-if="wall.model && getModelById(wall.model)">
                                                            <span>
                                                                {{ getModelById(wall.model).displayName }}
                                                                (R$ {{ getModelById(wall.model).value.toFixed(2) }}, prazo {{ getModelById(wall.model).deadline }} dia(s))
                                                        </span>
                                                        </template>
                                                        <span v-else class="text-muted">Não selecionado</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <p v-if="budget.selectedCarrier !== null"><strong>Transportadora:</strong> {{ budget.carriers[budget.selectedCarrier]?.name }}</p>
                                        <p v-if="budget.selectedCarrier !== null"><strong>Frete:</strong> R$ {{ budget.carriers[budget.selectedCarrier]?.price.toFixed(2) }}</p>
                                        <p><strong>Previsão de entrega:</strong> {{ calculateDeliveryTime(budget) }} dias</p>
                                        <p><strong>Forma de Pagamento:</strong> {{ budget.paymentMethod === 'pix' ? 'À Vista (PIX)' : budget.paymentMethod === 'installment' ? `A Prazo (${budget.installments}x)` : 'Não selecionado' }}</p>
                                    </div>
                                </div>

                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <strong>Total do Orçamento</strong>
                                    </div>
                                    <div class="card-body">
                                        <h3 class="mb-0">R$ {{ totalBudget.toFixed(2) }}</h3>
                                    </div>
                                </div>
                            </div>

                            <!-- Navigation Buttons -->
                            <div class="d-flex justify-content-between mt-4">
                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    @click="previousStep"
                                    :disabled="currentStep === 1"
                                >
                                    <i class="fa fa-arrow-left"></i> Anterior
                                </button>
                                <button
                                    v-if="currentStep < 6"
                                    type="button"
                                    class="btn btn-primary"
                                    @click="nextStep"
                                >
                                    Próximo <i class="fa fa-arrow-right"></i>
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    class="btn btn-success"
                                    @click="saveBudget"
                                    :disabled="saving"
                                >
                                    <i class="fa fa-save"></i> {{ saving ? 'Salvando...' : 'Salvar Orçamento' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, computed, reactive, onMounted, watch } from 'vue';
import axios from 'axios';

const currentStep = ref(1);
const calculatingFreight = ref(false);
const saving = ref(false);

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
    installments: 1
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

onMounted(() => {
    fetchCollectionModels();
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

const totalBudget = computed(() => {
    let total = 0;
    // Cálculo baseado na área (simulado)
    const pricePerSquareMeter = 50; // Preço por m² simulado
    total = totalArea.value * pricePerSquareMeter;

    // Adicionar custos dos modelos por parede
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

    // Adicionar frete
    if (budget.selectedCarrier !== null && budget.carriers[budget.selectedCarrier]) {
        total += budget.carriers[budget.selectedCarrier].price;
    }

    // Aplicar desconto PIX (5%)
    if (budget.paymentMethod === 'pix') {
        total = total * 0.95;
    }

    return total;
});

// Methods
function nextStep() {
    if (validateStep()) {
        if (currentStep.value < 6) {
            currentStep.value++;
        }
    }
}

function previousStep() {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
}

function validateStep() {
    switch (currentStep.value) {
        case 1:
            if (!budget.name) {
                alert('Por favor, informe o nome do orçamento');
                return false;
            }
            break;
        case 2:
            // Validar se há pelo menos um ambiente com pelo menos uma parede
            if (budget.rooms.length === 0) {
                alert('Por favor, adicione pelo menos um ambiente');
                return false;
            }
            break;
        case 3:
            if (modelsLoading.value) {
                alert('Aguarde o carregamento dos modelos antes de avançar.');
                return false;
            }
            if (!productModels.value.length) {
                alert('Nenhum modelo disponível no momento.');
                return false;
            }
            // Validar se todas as paredes têm um modelo selecionado
            for (let room of budget.rooms) {
                for (let wall of room.walls) {
                    if (!wall.model) {
                        alert('Por favor, selecione um modelo para todas as paredes');
                        return false;
                    }
                }
            }
            break;
        case 4:
            if (!budget.cep || budget.cep.length < 8) {
                alert('Por favor, informe um CEP válido');
                return false;
            }
            if (budget.selectedCarrier === null) {
                alert('Por favor, selecione uma transportadora');
                return false;
            }
            break;
        case 5:
            if (!budget.paymentMethod) {
                alert('Por favor, selecione uma forma de pagamento');
                return false;
            }
            break;
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
    let area = 0;
    const baseWidth = Number(wall.width) || 0;
    const baseHeight = Number(wall.height) || 0;

    if (baseWidth && baseHeight) {
        area += baseWidth * baseHeight;
    }

    getWallContinuations(wall).forEach((continuation) => {
        const continuationWidth = Number(continuation.width) || 0;
        const continuationHeight = Number(continuation.height) || 0;
        if (continuationWidth && continuationHeight) {
            area += continuationWidth * continuationHeight;
        }
    });

    return area;
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

function saveBudget() {
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
    //console.log(payload);
    //return;

    axios.post('v1/budgets', payload)
        .then(response => {
            console.log('Orçamento salvo:', response.data);
            alert('Orçamento salvo com sucesso!');
        })
        .catch(error => {
            console.error('Erro ao salvar orçamento:', error);
            const message = error.response?.data?.message || 'Tente novamente mais tarde.';
            alert('Erro ao salvar orçamento: ' + message);
        })
        .finally(() => {
            saving.value = false;
        });
}
</script>

<style lang="scss" scoped>
.budget-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    padding: 20px 0;

    &::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 2px;
        background: var(--bs-border-color);
        z-index: 0;
    }

    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 1;
        flex: 1;

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--bs-secondary-bg);
            border: 2px solid var(--bs-border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--bs-secondary-color);
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }

        .step-label {
            font-size: 0.85rem;
            text-align: center;
            color: var(--bs-secondary-color);
        }

        &.active {
            .step-number {
                background: var(--bs-primary);
                border-color: var(--bs-primary);
                color: white;
            }
            .step-label {
                color: var(--bs-primary);
                font-weight: 500;
            }
        }

        &.completed {
            .step-number {
                background: var(--bs-success);
                border-color: var(--bs-success);
                color: white;
                &::after {
                    content: '✓';
                }
            }
        }
    }
}

.step-content {
    min-height: 400px;
    padding: 20px 0;
}

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
