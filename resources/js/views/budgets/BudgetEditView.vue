<template>
  <section class="content">
    <Page title="Editar orçamento" :back-to="{ name: 'budgets.list' }" :breadcrumbs="routes">
      <template #extra>
        <button class="btn btn-primary" type="button" @click="updateBudget" :disabled="saving">
          {{ saving ? 'Salvando...' : 'Salvar' }}
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
            <p class="ms-1"><strong>Revendedor: </strong> {{ budget.reseller_name }}</p>

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
                <div class="mb-3">
                  <label for="budgetStatus" class="form-label">Status</label>
                  <select v-model="budget.status" id="budgetStatus" class="form-control">
                    <option :value="null">Sem status</option>
                    <option value="Em aberto">Em aberto</option>
                    <option value="Cancelado">Cancelado</option>
                    <option value="Aprovado">Aprovado</option>
                  </select>
                </div>

                <!-- Checkbox Dropshipping (para admin, revendedor ou is_dropshipping === 1) -->
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
              v-model="dropshippingData"
              ref="dropshippingFormRef"
            />

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
                                class="btn btn-sm btn-subtle"
                                @click="removeWall(roomIndex, wallIndex)"
                                v-if="room.walls.length > 1"
                              >
                                <IconTrash :size="16" />

                                Remover parede
                              </button>
                            </div>

                            <div class="row">
                              <div class="col-md-6 mb-3">
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
                              <div class="col-md-6 mb-3">
                                <label
                                  :for="`wall-direction-${roomIndex}-${wallIndex}`"
                                  class="form-label"
                                >
                                  Direção
                                </label>
                                <select
                                  v-model="wall.direction"
                                  :id="`wall-direction-${roomIndex}-${wallIndex}`"
                                  class="form-control"
                                >
                                  <option value="">Selecione a direção</option>
                                  <option value="left-to-right">Da esquerda para direita</option>
                                  <option value="right-to-left">Da direita para esquerda</option>
                                </select>
                              </div>
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

                            <WallPartMetrics :metrics="getWallPartMetrics(wall, 0)" />

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
                                  Informe a largura e a altura para habilitar continuações com a
                                  mesma arte.
                                </div>
                              </template>
                            </div>

                            <div
                              v-if="wall.continueSameArt && wall.width && wall.height"
                              class="mb-3 border-start border-primary ps-3 ms-2"
                            >
                              <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Continuações da parede</h6>
                              </div>
                              <small class="text-muted d-block mb-3">
                                Use estas continuações para representar trechos adicionais com a
                                mesma arte.
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
                                  <div
                                    class="d-flex justify-content-between align-items-start mb-3"
                                  >
                                    <h6 class="mb-0">
                                      Continuação
                                      {{ continuationIndex + 1 }}
                                      <span v-if="continuation.name"
                                        >- {{ continuation.name }}</span
                                      >
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
                                      :for="`continuation-fit-${roomIndex}-${wallIndex}-${continuationIndex}`"
                                      class="form-label"
                                    >
                                      Encaixe
                                    </label>
                                    <input v-if="continuationIndex === 0" type="text" v-model="continuation.fit" :id="`continuation-fit-${roomIndex}-${wallIndex}-${continuationIndex}`" class="form-control" readonly>
                                    <select
                                      v-else
                                      v-model="continuation.fit"
                                      :id="`continuation-fit-${roomIndex}-${wallIndex}-${continuationIndex}`"
                                      class="form-control">
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

                                <WallPartMetrics
                                  :metrics="getWallPartMetrics(wall, continuationIndex + 1)"
                                />
                              </div>
                            </div>
                          </div>

                            <div class="d-flex justify-content-end">
                              <button
                                type="button"
                                class="btn btn-sm btn-default mb-3"
                                @click="addContinuation(roomIndex, wallIndex)"
                              >
                                <IconPlus :size="16" />

                                Adicionar continuação
                              </button>
                            </div>
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
                </template>
              </div>
            </div>
          </div>

          <!-- Sidebar: Frete e Resumo -->
          <div class="col-12 col-lg-4">
            <!-- Seção: Frete -->
            <div class="card mb-4 mt-2">
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

                      {{ calculatingFreight ? 'Calculando...' : 'Calcular Frete' }}
                    </button>
                  </div>
                </div>

                <div v-if="budget.carriers && budget.carriers.length > 0" class="mt-4">
                  <label class="form-label">Transportadora</label>
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

                <span class="budget-attention-info d-block mt-3">
                  ATENÇÃO! OS PREÇOS DO ORÇAMENTO OU PEDIDOS NÃO PAGOS SERÃO MANTIDOS POR 30 DIAS
                  CORRIDOS, APÓS ESSE PERÍODO OS VALORES PODEM SOFRER REAJUSTES AUTOMÁTICOS.
                </span>
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
              :delivery-time="`${calculateDeliveryTime(budget)} dias`"
              :total-vista="formatCurrency(totalBudgetVista)"
              :total-prazo="formatCurrency(totalBudgetPrazo)"
              :strip-summary="stripSummary"
              :rooms="budget.rooms"
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
import WallPartMetrics from '@/components/budget/WallPartMetrics.vue';
import { useFormatting } from '@/composables/useFormatting';
import { useToast } from '@/composables/useToast';
import DropshippingForm from '@/modules/budgets/components/DropshippingForm.vue';
import {
  calculateStripHeight as wallStripHeightForSummary,
  useBudgetCalculations,
} from '@/modules/budgets/composables/useBudgetCalculations';
import { useBudgetEditState } from '@/modules/budgets/composables/useBudgetEditState';
import { useBudgetFormatters } from '@/modules/budgets/composables/useBudgetFormatters';
import { useBudgetModels } from '@/modules/budgets/composables/useBudgetModels';
import { useBudgetStructure } from '@/modules/budgets/composables/useBudgetStructure';
import { createDefaultWall } from '@/modules/budgets/composables/useBudgetUtils';
import { validateBudget } from '@/modules/budgets/composables/useBudgetValidation';
import { budgetService } from '@/services/budgetService';
import { useAuthStore } from '@/stores/auth';
import { sumArtworkDays } from '@/utils/artWorkDaysSum';
import { calculateWallWithContinuations } from '@/utils/calculateStripsUtils.js';
import { buildStripSummaryFromParts } from '@/utils/stripSummaryUtils';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

// Icons
import { IconPlus, IconTrash } from '@tabler/icons-vue';

const toast = useToast();
const router = useRouter();
const auth = useAuthStore();

const calculatingFreight = ref(false);
const saving = ref(false);
const enableDropshipping = ref(false);
const dropshippingData = ref({});
const dropshippingFormRef = ref(null);

const tinyErpProducts = ref([]);
const loading = ref(false);

const PRECO_VISTA = ref(0);
const PRECO_PRAZO = ref(0);

const routes = [
  { path: '/', breadcrumbName: 'Início' },
  { path: '/budgets', breadcrumbName: 'Orçamentos' },
  { path: '#', breadcrumbName: 'Editar' },
];

const showWarning = (message) =>
  window.Swal.fire({
    title: 'Atenção',
    text: message,
    icon: 'warning',
  });

const budget = reactive({
  id: null,
  name: '',
  status: null,
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
  total_amount: 0,
  total_amount_installments: 0,
  dropshipping_budget: 0,
  dropshipping_data: null,
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
const stripSummary = computed(() => buildStripSummaryFromParts(budget.rooms));

const wallSequenceMap = computed(() => {
  const map = new WeakMap();
  budget.rooms.forEach((room) => {
    if (!Array.isArray(room?.walls)) return;
    room.walls.forEach((wall) => {
      map.set(wall, calculateWallWithContinuations(wall));
    });
  });
  return map;
});

function getWallPartMetrics(wall, partIndex) {
  const seq = wallSequenceMap.value.get(wall);
  return seq?.perPart?.[partIndex] ?? null;
}

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

const { budgetId, loadingBudget, originalBudget, hasChanges, loadBudget, updateOriginalBudget } =
  useBudgetEditState(budget);

const {
  totalWalls,
  totalArea,
  totalModelsCost,
  freightCost,
  totalBudgetVista,
  totalBudgetPrazo,
  totalBudget,
  calculateDeliveryTime,
} = useBudgetCalculations(budget, getModelById, PRECO_VISTA, PRECO_PRAZO, hasChanges);

const {
  addRoom,
  removeRoom,
  addWall,
  removeWall,
  addContinuation,
  removeContinuation,
  handleContinuationToggle,
} = useBudgetStructure(budget);

const { formatCEP: formatCEPValue } = useBudgetFormatters();
const { formatCurrency } = useFormatting();

function formatCEP(event) {
  const formatted = formatCEPValue(event.target.value);
  budget.cep = formatted;
}

// Computed para verificar se pode habilitar dropshipping
const canEnableDropshipping = computed(() => {
  return auth.hasRole(['admin', 'reseller']) || auth.user?.is_dropshipping === 1;
});

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

onMounted(async () => {
  fetchCollectionModels();
  const normalized = await loadBudget();

  // Carregar dados de dropshipping se existirem
  if (normalized && normalized.dropshipping_budget === 1 && normalized.dropshipping_data) {
    enableDropshipping.value = true;
    dropshippingData.value = { ...normalized.dropshipping_data };
  } else {
    enableDropshipping.value = false;
    dropshippingData.value = {};
  }

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

    // Resetar a seleção se não houver carriers ou se o índice selecionado não existir mais
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

function updateBudget() {
  if (!validateBudget(budget, modelsLoading, productModels, showWarning, getModelById)) {
    return;
  }

  // Validar dropshipping se estiver habilitado
  if (enableDropshipping.value && dropshippingFormRef.value) {
    const isValid = dropshippingFormRef.value.validate();
    if (!isValid) {
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
  delete payload.id;

  // Configurar dropshipping_budget e dropshipping_data
  payload.dropshipping_budget = enableDropshipping.value ? 1 : 0;

  if (enableDropshipping.value && dropshippingFormRef.value) {
    payload.dropshipping_data = dropshippingFormRef.value.getData();
  } else {
    delete payload.dropshipping_data;
  }

  budgetService
    .update(budgetId.value, payload)
    .then(() => {
      toast.success('Orçamento atualizado!');

      router.push({ name: 'budgets.list' });
    })
    .catch((error) => {
      console.error('Erro ao atualizar orçamento:', error);
      const message = error.response?.data?.message || 'Tente novamente mais tarde.';
      window.Swal.fire({
        title: 'Erro ao atualizar orçamento!',
        text: message ?? 'Tente novamente mais tarde.',
        confirmButtonText: 'Entendi!',
      });
    })
    .finally(() => {
      saving.value = false;
    });
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
