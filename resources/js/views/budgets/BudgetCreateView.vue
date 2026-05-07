<template>
  <section class="content">
    <Page title="Criar orçamento" :breadcrumbs="routes">
      <template #extra>
        <button class="btn btn-primary" type="button" @click="saveBudget" :disabled="saving">
          {{ saving ? 'Salvando...' : 'Salvar' }}
        </button>
      </template>

      <div v-if="loading" class="text-center text-muted py-5">
        <div class="spinner-border" role="status">
          <span class="visually-hidden">Carregando...</span>
        </div>
      </div>

      <div v-else>
        <div class="row">
          <div class="col-12 col-lg-8">
            <!-- Seção: Informações Básicas -->
            <div class="card mb-4">
              <div class="card-body">
                <div class="mb-3">
                  <label for="budgetName" class="form-label">Nome do orçamento</label>
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
                      class="btn btn-sm btn-subtle text-danger"
                      @click="removeRoom(roomIndex)"
                      v-if="budget.rooms.length > 1"
                    >
                      <IconTrash :size="16" />

                      Remover ambiente
                    </button>
                  </div>
                  <div class="card-body">
                    <div class="mb-3">
                      <label :for="`room-name-${roomIndex}`" class="form-label">
                        Nome do ambiente
                      </label>
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
                      <div
                        v-for="(wall, wallIndex) in room.walls"
                        :key="wallIndex"
                        class="card mb-3 border"
                      >
                        <div class="card-body">
                          <div class="d-flex justify-content-between align-items-center mb-3">
                            <strong>Parede {{ wallIndex + 1 }}</strong>
                            <button
                              type="button"
                              class="btn btn-sm btn-subtle text-danger"
                              @click="removeWall(roomIndex, wallIndex)"
                              v-if="room.walls.length > 1"
                            >
                              <IconTrash :size="16" />

                              Remover parede
                            </button>
                          </div>

                          <div class="mb-3">
                            <label :for="`wall-name-${roomIndex}-${wallIndex}`" class="form-label"
                              >Nome da Parede</label
                            >
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
                              <label
                                :for="`wall-width-${roomIndex}-${wallIndex}`"
                                class="form-label"
                                >Largura (m)</label
                              >
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
                              <label
                                :for="`wall-height-${roomIndex}-${wallIndex}`"
                                class="form-label"
                                >Altura (m)</label
                              >
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
                                <label
                                  class="form-check-label"
                                  :for="`wall-continue-same-art-${roomIndex}-${wallIndex}`"
                                >
                                  Continuar esta parede com a mesma arte?
                                </label>
                              </div>
                            </template>
                            <template v-else>
                              <div class="alert alert-light border text-muted py-2 mb-0">
                                Informe a largura e a altura para habilitar continuações com a mesma
                                arte.
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
                                class="btn btn-sm btn-default"
                                @click="addContinuation(roomIndex, wallIndex)"
                              >
                                <IconPlus :size="16" />

                                Adicionar continuação
                              </button>
                            </div>
                            <small class="text-muted d-block mb-3">
                              Use estas continuações para representar trechos adicionais com a mesma
                              arte.
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
                                  <h6 class="mb-0">
                                    Continuação
                                    {{ continuationIndex + 1 }}
                                    {{ continuation.name }}
                                  </h6>
                                  <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    @click="
                                      removeContinuation(roomIndex, wallIndex, continuationIndex)
                                    "
                                  >
                                    <IconTrash :size="18" />
                                  </button>
                                </div>

                                <div class="mb-3">
                                  <label
                                    :for="`continuation-name-${roomIndex}-${wallIndex}-${continuationIndex}`"
                                    class="form-label"
                                  >
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
                                  <label
                                    :for="`continuation-direction-${roomIndex}-${wallIndex}-${continuationIndex}`"
                                    class="form-label"
                                  >
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

                                <div class="mb-3">
                                  <label
                                    :for="`continuation-fit-${roomIndex}-${wallIndex}-${continuationIndex}`"
                                    class="form-label"
                                  >
                                    Encaixe
                                  </label>
                                  <select
                                    v-model="continuation.fit"
                                    :id="`continuation-fit-${roomIndex}-${wallIndex}-${continuationIndex}`"
                                    class="form-control"
                                  >
                                    <option value="Inicial">Inicial</option>
                                    <option value="Superior">Superior</option>
                                    <option value="Inferior">Inferior</option>
                                    <option value="Central">Central</option>
                                  </select>
                                </div>

                                <div class="row">
                                  <div class="col-md-6 mb-3">
                                    <label
                                      :for="`continuation-width-${roomIndex}-${wallIndex}-${continuationIndex}`"
                                      class="form-label"
                                    >
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
                                    <label
                                      :for="`continuation-height-${roomIndex}-${wallIndex}-${continuationIndex}`"
                                      class="form-label"
                                    >
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
                            <strong>Metros:</strong>
                            {{ getWallArea(wall).toFixed(2) }}
                            <br />
                            <strong>Quantidade de faixas:</strong>
                            {{ calculateStrips(wall) }}
                            <br />
                            <strong>Tamanho da faixa:</strong>
                            {{ formatStripHeight(wall) }}
                            m
                          </div>
                          <p v-if="formatStripHeight(wall) > 6" class="mb-0 text-danger small">
                            Obs.:<br />
                            Faixas maiores que 6 metros são vendidas apenas em pares.
                          </p>

                          <div class="mt-4">
                            <h6 class="mb-3">Definir modelo da parede</h6>
                            <div v-if="modelsLoading" class="text-center text-muted py-3">
                              Carregando modelos...
                            </div>
                            <div v-else-if="modelsError" class="alert alert-danger" role="alert">
                              {{ modelsError }}
                            </div>
                            <div
                              v-else-if="!productModels.length"
                              class="alert alert-warning"
                              role="alert"
                            >
                              Nenhum modelo disponível. Tente novamente mais tarde.
                            </div>
                            <div v-else class="row">
                              <div
                                class="col-lg-4 col-md-6 mb-3"
                                v-for="model in productModels"
                                :key="model.id"
                              >
                                <div
                                  class="card h-100 model-card"
                                  :class="{
                                    'border-primary': wall.model === model.id,
                                  }"
                                  @click="wall.model = model.id"
                                  style="cursor: pointer"
                                >
                                  <div class="card-body d-flex flex-column">
                                    <div class="mb-2">
                                      <strong>{{ model.name }}</strong>
                                    </div>
                                    <div class="small text-muted">
                                      <div>
                                        <strong>Valor:</strong>
                                        {{ formatCurrency(model.value) }}
                                      </div>
                                      <div>
                                        <strong>Prazo:</strong>
                                        {{ model.deadline }}
                                        dia(s)
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <BudgetModelRequeriments
                              v-if="wall.model"
                              :wall="wall"
                              :model="getModelById(wall.model)"
                              :disabled="saving"
                            />
                          </div>
                        </div>
                      </div>
                    </div>

                    <button type="button" class="btn btn-default" @click="addWall(roomIndex)">
                      <IconPlus :size="18" />

                      Adicionar parede
                    </button>
                  </div>
                </div>

                <button type="button" class="btn btn-default" @click="addRoom">
                  <IconPlus :size="18" />

                  Adicionar ambiente
                </button>
              </div>
            </div>
          </div>

          <!-- Sidebar: Frete e Pagamento -->
          <div class="col-12 col-lg-4">
            <!-- Seção: Frete -->
            <div class="card mb-4">
              <div class="card-body">
                <h5 class="card-title">Frete</h5>
                <div class="row g-2 align-items-end">
                  <div class="col-auto">
                    <label for="cep" class="form-label mb-0">CEP</label>
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

                  <div class="col-auto">
                    <button
                      type="button"
                      class="btn btn-default"
                      @click="calculateFreight"
                      :disabled="!budget.cep || calculatingFreight"
                    >
                      <span
                        v-if="calculatingFreight"
                        class="spinner-border spinner-border-sm me-2"
                      ></span>

                      {{ calculatingFreight ? 'Calculando...' : 'Calcular frete' }}
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
                      :class="{
                        active: budget.selectedCarrier === index,
                      }"
                      @click="budget.selectedCarrier = index"
                      style="cursor: pointer"
                    >
                      <div class="d-flex justify-content-between align-items-center">
                        <div>
                          <strong>{{ carrier.name }}</strong>
                          <br />
                          <small class="text-muted"
                            >Prazo:
                            {{ carrier.deliveryTime }}
                            dias</small
                          >
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
                  <div class="input-group input-group-prefix">
                    <input
                      type="text"
                      id="freightValue"
                      class="form-control"
                      :value="
                        budget.selectedCarrier !== null
                          ? budget.carriers[budget.selectedCarrier]?.price
                              .toFixed(2)
                              .replace('.', ',')
                          : '0,00'
                      "
                      readonly
                    />
                    <label for="freightValue" class="input-group-text">R$</label>
                  </div>
                </div>

                <hr class="my-4" />

                <div class="mb-3">
                  <label class="form-label">Selecione a forma de pagamento</label>
                  <div class="row g-2">
                    <div class="col-12 mb-2">
                      <div
                        class="card h-100"
                        :class="{
                          'border-primary': budget.paymentMethod === 'pix',
                        }"
                        @click="budget.paymentMethod = 'pix'"
                        style="cursor: pointer"
                      >
                        <div class="card-body text-center py-3">
                          <IconQrcode :size="32" class="mb-2" />

                          <h6 class="mb-1">À Vista (PIX)</h6>
                          <p class="text-muted small mb-0">Pagamento à vista com desconto</p>
                        </div>
                      </div>
                    </div>
                    <div class="col-12" v-if="budget.installmentLimit > 1">
                      <div
                        class="card h-100"
                        :class="{
                          'border-primary': budget.paymentMethod === 'credit_card',
                        }"
                        @click="budget.paymentMethod = 'credit_card'"
                        style="cursor: pointer"
                      >
                        <div class="card-body text-center py-3">
                          <IconCreditCard :size="32" class="mb-2" />

                          <h6 class="mb-1">A Prazo (Cartão ou Boleto)</h6>
                          <p class="text-muted small mb-0">
                            Parcelado em até
                            {{ budget.installmentLimit }}x
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-if="budget.paymentMethod === 'credit_card'" class="mt-3">
                    <label for="installments" class="form-label">Número de Parcelas</label>
                    <select
                      v-model.number="budget.installments"
                      id="installments"
                      class="form-control"
                    >
                      <option v-for="n in budget.installmentLimit" :key="n" :value="n">
                        {{ n }}x
                      </option>
                    </select>
                  </div>

                  <br />

                  <span class="budget-attention-info">
                    ATENÇÃO! OS PREÇOS DO ORÇAMENTO OU PEDIDOS NÃO PAGOS SERÃO MANTIDOS POR 30 DIAS
                    CORRIDOS, APÓS ESSE PERÍODO OS VALORES PODEM SOFRER REAJUSTES AUTOMÁTICOS.
                  </span>
                </div>
              </div>
            </div>

            <!-- Seção: Resumo -->
            <ResumeProductCard
              :total-rooms="budget.rooms.length"
              :total-walls="totalWalls"
              :total-area="totalArea.toFixed(2)"
              :freight="
                budget.selectedCarrier !== null
                  ? formatCurrency(budget.carriers[budget.selectedCarrier]?.price)
                  : ''
              "
              :arts-total="totalModelsCost > 0 ? formatCurrency(totalModelsCost) : ''"
              :artwork-days="artworkDays"
              :transport-days="transportDays"
              :delivery-time="`${deliveryTimeDisplay} dias`"
              :total-vista="formatCurrency(totalBudgetVista)"
              :total-prazo="formatCurrency(totalBudgetPrazo)"
              :strip-summary="stripSummary"
            />
          </div>
        </div>
      </div>
    </Page>
  </section>
</template>

<script setup>
import BudgetModelRequeriments from '@/components/budget/BudgetModelRequeriments.vue';
import Page from '@/components/page/Page.vue';
import ResumeProductCard from '@/components/resume-product-card/ResumeProductCard.vue';
import { useFormatting } from '@/composables/useFormatting';
import { useToast } from '@/composables/useToast';
import DropshippingForm from '@/modules/budgets/components/DropshippingForm.vue';
import { useBudgetCalculations } from '@/modules/budgets/composables/useBudgetCalculations';
import { useBudgetFormatters } from '@/modules/budgets/composables/useBudgetFormatters';
import { useBudgetModels } from '@/modules/budgets/composables/useBudgetModels';
import { useBudgetStructure } from '@/modules/budgets/composables/useBudgetStructure';
import { createDefaultWall } from '@/modules/budgets/composables/useBudgetUtils';
import { validateBudget } from '@/modules/budgets/composables/useBudgetValidation';
import { budgetService } from '@/services/budgetService';
import { useAuthStore } from '@/stores/auth';
import { sumArtworkDays } from '@/utils/artWorkDaysSum';
import { buildStripSummaryFromRooms } from '@/utils/stripSummaryUtils';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

import { IconCreditCard, IconPlus, IconQrcode, IconTrash } from '@tabler/icons-vue';

const toast = useToast();
const router = useRouter();
const auth = useAuthStore();

const calculatingFreight = ref(false);
const saving = ref(false);
const enableDropshipping = ref(false);
const dropshippingFormRef = ref(null);
const tinyErpProducts = ref([]);
const loading = ref(false);
const PRECO_VISTA = ref(0);
const PRECO_PRAZO = ref(0);

const routes = [
  { path: '/', breadcrumbName: 'Início' },
  { path: '/budgets', breadcrumbName: 'Orçamentos' },
  { path: '/budgets/create', breadcrumbName: 'Criar' },
];

const canEnableDropshipping = computed(() => {
  const user = auth.user;
  return auth.hasRole('admin') || user?.is_dropshipping === 1 || user?.is_dropshipping === true;
});

const showWarning = (message) =>
  window.Swal.fire({
    title: 'Atenção',
    text: message,
    icon: 'warning',
  });

const budget = reactive({
  name: '',
  rooms: [
    {
      name: '',
      walls: [createDefaultWall()],
    },
  ],
  deliveryTime: 0,
  cep: '',
  carriers: [],
  selectedCarrier: null,
  paymentMethod: '',
  installmentLimit: 12,
  installments: 1,
  dropshipping_budget: 0,
  dropshipping_data: {},
});

// Composables
const {
  productModels,
  modelsLoading,
  modelsError,
  modelSlides,
  getModelById,
  fetchCollectionModels,
} = useBudgetModels(budget);

const artworkDays = computed(() => sumArtworkDays(budget, getModelById));
const stripSummary = computed(() => buildStripSummaryFromRooms(budget.rooms));

const transportDays = computed(() => {
  if (
    budget.selectedCarrier !== null &&
    Array.isArray(budget.carriers) &&
    budget.carriers[budget.selectedCarrier]
  ) {
    return Math.max(0, Number(budget.carriers[budget.selectedCarrier]?.deliveryTime ?? 0));
  }

  return '';
});

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
  calculateDeliveryTime,
} = useBudgetCalculations(
  budget,
  getModelById,
  PRECO_VISTA,
  PRECO_PRAZO,
  computed(() => false),
);

const {
  addRoom,
  removeRoom,
  addWall,
  removeWall,
  addContinuation,
  removeContinuation,
  handleContinuationToggle,
} = useBudgetStructure(budget);

const { formatStripHeight, formatCEP: formatCEPValue } = useBudgetFormatters();
const { formatCurrency } = useFormatting();

// Wrapper para formatCEP que recebe event
function formatCEP(event) {
  const formatted = formatCEPValue(event.target.value);
  budget.cep = formatted;
}

onMounted(() => {
  fetchCollectionModels();
  searchTinyErpProducts();
});

watch(
  () =>
    budget.rooms.map((room) =>
      room.walls.map((wall) => ({
        width: wall.width,
        height: wall.height,
        continueSameArt: wall.continueSameArt,
      })),
    ),
  () => {
    budget.rooms.forEach((room) => {
      room.walls.forEach((wall) => {
        if (!wall.width || !wall.height) {
          if (
            wall.continueSameArt ||
            (Array.isArray(wall.continuations) && wall.continuations.length)
          ) {
            wall.continueSameArt = false;
            wall.continuations = [];
          }
        }
      });
    });
  },
  { deep: true },
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
    } else if (
      budget.selectedCarrier !== null &&
      budget.selectedCarrier >= budget.carriers.length
    ) {
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
  if (!validateBudget(budget, modelsLoading, productModels, showWarning, getModelById)) {
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

  budgetService
    .create(payload)
    .then(() => {
      router.push({ name: 'budgets.list' });

      toast.success('Orçamento criado!');
    })
    .catch((error) => {
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
.card[style*='cursor: pointer'] {
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

.budget-attention-info {
  font-size: 12px;
  font-weight: 600;
}
</style>
