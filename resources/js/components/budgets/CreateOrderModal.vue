<template>
  <BaseModal
    v-model="isOpen"
    title="Criar pedido?"
    size="xl"
    :scrollable="true"
    :centered="true"
    :show-footer="true"
    :close-on-backdrop="!submitting"
    :close-on-escape="!submitting"
    @close="handleClose"
  >
    <template #body>
      <p class="mb-4">
        Revise se as medidas, quantidades, modelos, endereço e demais informações estão corretas
        antes de continuar.
      </p>

      <div v-if="loading" class="text-center text-muted py-4">Carregando paredes do orçamento...</div>

      <div v-else-if="loadError" class="alert alert-danger" role="alert">{{ loadError }}</div>

      <div v-else-if="!rooms.length" class="alert alert-warning" role="alert">
        Este orçamento não possui paredes para associar modelos.
      </div>

      <div v-else>
        <div v-for="(room, roomIndex) in rooms" :key="room.id ?? roomIndex" class="mb-4">
          <h6 class="fw-semibold mb-3">
            {{ room.name?.trim() || `Ambiente ${roomIndex + 1}` }}
          </h6>

          <div
            v-for="(wall, wallIndex) in room.walls"
            :key="wall.id ?? wallIndex"
            class="border rounded p-3 mb-3"
          >
            <div class="fw-semibold mb-3">
              {{ wall.name?.trim() || `Parede ${wallIndex + 1}` }}
              <span v-if="wall.width && wall.height" class="text-muted fw-normal">
                ({{ wall.width }}m × {{ wall.height }}m)
              </span>
            </div>

            <h6 class="mb-3">
              Definir modelo da parede
              <span class="text-muted fw-normal">(opcional)</span>
            </h6>

            <div v-if="modelsLoading" class="text-center text-muted py-3">Carregando modelos...</div>
            <div v-else-if="modelsError" class="alert alert-danger" role="alert">
              {{ modelsError }}
            </div>
            <div v-else-if="!productModels.length" class="alert alert-warning" role="alert">
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
                  :class="{ 'border-primary': wall.model === model.id }"
                  style="cursor: pointer"
                  @click="wall.model = wall.model === model.id ? null : model.id"
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
                        {{ model.deadline }} dia(s)
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
              :disabled="submitting"
            />
          </div>
        </div>
      </div>
    </template>

    <template #footer>
      <button
        type="button"
        class="btn btn-subtle"
        :disabled="submitting"
        @click="handleClose"
      >
        Cancelar
      </button>
      <button
        type="button"
        class="btn btn-primary"
        :disabled="loading || submitting || !!loadError"
        @click="handleConfirm"
      >
        {{ submitting ? 'Criando...' : 'Criar pedido' }}
      </button>
    </template>
  </BaseModal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';

import BaseModal from '@/components/common/BaseModal.vue';
import BudgetModelRequeriments from '@/components/budget/BudgetModelRequeriments.vue';
import { useFormatting } from '@/composables/useFormatting';
import { useBudgetModels } from '@/modules/budgets/composables/useBudgetModels';
import { normalizeBudgetFromAPI } from '@/modules/budgets/composables/useBudgetUtils';
import { budgetService } from '@/services/budgetService';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  budget: {
    type: Object,
    default: null,
  },
  submitting: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'confirm', 'close']);

const { formatCurrency } = useFormatting();

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const loading = ref(false);
const loadError = ref(null);
const rooms = ref([]);

const draftBudget = reactive({ rooms: [] });
const {
  productModels,
  modelsLoading,
  modelsError,
  getModelById,
  fetchCollectionModels,
} = useBudgetModels(draftBudget);

watch(
  () => [props.modelValue, props.budget?.id],
  async ([open]) => {
    if (!open || !props.budget?.id) {
      return;
    }

    await loadBudgetDetails(props.budget.id);
  },
);

async function loadBudgetDetails(budgetId) {
  loading.value = true;
  loadError.value = null;
  rooms.value = [];
  draftBudget.rooms = [];

  try {
    const [budgetData] = await Promise.all([
      budgetService.find(budgetId),
      fetchCollectionModels(),
    ]);

    const normalized = normalizeBudgetFromAPI(budgetData);
    rooms.value = Array.isArray(normalized.rooms) ? normalized.rooms : [];
    draftBudget.rooms = rooms.value;
  } catch (error) {
    console.error(error);
    loadError.value =
      error?.response?.data?.message || 'Não foi possível carregar o orçamento. Tente novamente.';
  } finally {
    loading.value = false;
  }
}

function validateWallRequirements() {
  for (let roomIndex = 0; roomIndex < rooms.value.length; roomIndex++) {
    const room = rooms.value[roomIndex];
    const roomLabel = room.name?.trim() || `Ambiente ${roomIndex + 1}`;

    for (let wallIndex = 0; wallIndex < (room.walls ?? []).length; wallIndex++) {
      const wall = room.walls[wallIndex];
      const wallLabel = wall.name?.trim() || `Parede ${wallIndex + 1}`;

      if (!wall.model) {
        continue;
      }

      const model = getModelById(wall.model);
      const requests = model?.requests ?? {};

      if (requests.comment && !String(wall.comment_referring_model ?? '').trim()) {
        return `Informe a descrição do modelo para ${wallLabel} em ${roomLabel}.`;
      }

      if (requests.link && !String(wall.link_referring_model ?? '').trim()) {
        return `Informe o link de referência para ${wallLabel} em ${roomLabel}.`;
      }

      if (requests.file) {
        const files = Array.isArray(wall.files_referring_model) ? wall.files_referring_model : [];
        if (!files.length) {
          return `Informe ao menos um arquivo de referência para ${wallLabel} em ${roomLabel}.`;
        }
      }

      if (requests.collection && !String(wall.collection_referring_model ?? '').trim()) {
        return `Selecione uma arte da coleção para ${wallLabel} em ${roomLabel}.`;
      }
    }
  }

  return null;
}

function buildWallsPayload() {
  return rooms.value.flatMap((room) =>
    (room.walls ?? [])
      .filter((wall) => wall?.id)
      .map((wall) => ({
        id: wall.id,
        model: wall.model ?? null,
        comment_referring_model: wall.comment_referring_model || null,
        link_referring_model: wall.link_referring_model || null,
        files_referring_model: Array.isArray(wall.files_referring_model)
          ? wall.files_referring_model
          : [],
        collection_referring_model: wall.collection_referring_model || null,
      })),
  );
}

function handleConfirm() {
  if (loading.value || props.submitting || loadError.value) {
    return;
  }

  const validationError = validateWallRequirements();
  if (validationError) {
    window.Swal?.fire({
      icon: 'warning',
      title: 'Atenção',
      text: validationError,
      confirmButtonText: 'Entendi',
    });
    return;
  }

  emit('confirm', {
    budget: props.budget,
    walls: buildWallsPayload(),
  });
}

function handleClose() {
  if (props.submitting) {
    return;
  }

  emit('close');
}
</script>

<style scoped>
.model-card {
  transition: all 0.2s ease;
}

.model-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.model-card.border-primary {
  border-width: 2px;
}
</style>
