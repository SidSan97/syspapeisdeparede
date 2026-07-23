<template>
  <BaseModal
    v-model="isVisible"
    size="xl"
    :show-footer="false"
    :scrollable="true"
    @close="handleClose"
  >
    <template #body>
      <div class="row g-0 flex-fill">
        <div v-if="coverImage" class="layout-modal-cover">
          <img :src="coverImage" :alt="`Imagem de capa de ${card.name}`" />
        </div>

        <div class="col-md-7 overflow-y-auto h-100">
          <main class="p-4">
            <header class="d-flex align-items-center gap-3 mb-4 flex-wrap">
              <button
                class="btn btn-sm btn-subtle rounded-pill p-2"
                @click="markAsProduced"
                :disabled="markingAsProduced"
                v-if="card.production_column_names_id < 2"
              >
                <IconCircle />
              </button>
              <IconCircleCheck
                v-else-if="card.production_percentage == 100"
                class="text-success"
                :size="20"
              />
              <IconCircleHalf2 v-else class="text-secondary" :size="20" />

              <div class="gap-2">
                <h3 class="fw-semibold m-0">{{ getCardDisplayName(card) }}</h3>
                <span
                  v-if="card.status"
                  class="badge"
                  :class="getOrderBudgetStatusBadgeClass(card.status)"
                  >{{ card.status }}</span
                >
              </div>
            </header>
            <div class="row">
              <div class="col col-md-auto">
                <MembersSection
                  :card="card"
                  :canRemoveMembers="canRemoveMembers"
                  typePage="product"
                  @member-added="handleMemberAdded"
                  @member-removed="handleMemberRemoved"
                />
              </div>
              <div class="col col-md-auto">
                <h3 class="fs-xs text-body-secondary">Prazo</h3>
                <div class="btn btn-default">
                  <span
                    >{{ card.delivery_date_start }}
                    -
                    {{ card.delivery_date_end }}</span
                  >
                  <span class="badge bg-info fs-xs fw-semibold ms-2">
                    {{ card.delivery_time }} dias
                  </span>
                </div>
              </div>
            </div>

            <ProductionPercentageSection :card="card" />

            <DescriptionSection :card="card" typePage="product" />

            <AttachmentsSection :attachments="card.uploaded_files" />

            <WallDetailsSection :wall="card.wall" />

            <OthersWallsRooms
                :cards="orderProductCards"
                :current-card-id="card?.id"
                :loading="loadingOrderProductCards"
                @select="handleSelectOtherProductCard"
              />

            <CollectionModelsSection :wall="card.wall" />

            <div v-if="card.budget" class="layout-modal-section">
              <h3 class="layout-modal-section-title">
                <IconLink />

                Links
              </h3>
              <div v-if="card.budget.link_referring_model" class="layout-modal-info">
                <a
                  :href="card.budget.link_referring_model"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="layout-modal-link"
                >
                  <IconExternalLink :size="18" />

                  {{ card.budget.link_referring_model }}
                </a>
              </div>
              <div v-else class="layout-modal-info text-muted">Nenhum link disponível</div>
            </div>

            <RequestArtsSection
              :show="true"
              :loading="loadingRequestArts"
              :arts="requestLayoutArts"
              accordionId="requestArtsAccordion"
              prefix=""
            />

            <ProductionReportsSection
              :card="card"
              :reports="productionReports"
              :loading="loadingProductionReports"
            />
          </main>
        </div>

        <div class="col-md-5 overflow-y-auto h-100">
          <CommentsAndActivitySidebar
            :card="card"
            v-model:showDetails="showDetails"
            typePage="product"
          />
        </div>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { computed, ref, watch, toRef } from 'vue';
import {
  IconCircle,
  IconCircleCheck,
  IconCircleHalf2,
  IconExternalLink,
  IconLink,
} from '@tabler/icons-vue';
import { useToast } from '@/composables/useToast';
import { useAuthStore } from '@/stores/auth';
import { useProductionReportsStore } from '@/stores/productionReports';
import { productionService } from '@/services/productionService';
import { getCardDisplayName, getOrderBudgetStatusBadgeClass } from '@/utils/cardUtils';
import { getCoverImage } from '@/modules/card-modals/composables/useCardUtils';
import BaseModal from '@/components/common/BaseModal.vue';
import MembersSection from '@/components/card-modal/MembersSection.vue';
import DescriptionSection from '@/components/card-modal/DescriptionSection.vue';
import AttachmentsSection from './production-card-modal/AttachmentsSection.vue';
import WallDetailsSection from '@/components/card-modal/WallDetailsSection.vue';
import OthersWallsRooms from '@/components/card-modal/OthersWallsRooms.vue';
import CollectionModelsSection from './production-card-modal/CollectionModelsSection.vue';
import ProductionPercentageSection from './production-card-modal/ProductionPercentageSection.vue';
import ProductionReportsSection from './production-card-modal/ProductionReportsSection.vue';
import RequestArtsSection from '@/components/card-modal/RequestArtsSection.vue';
import CommentsAndActivitySidebar from '@/components/card-modal/CommentsAndActivitySidebar.vue';
import { useRequestLayoutArts } from '@/composables/useRequestLayoutArts';

const props = defineProps({
  card: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits([
  'close',
  'card-updated',
  'member-added',
  'member-removed',
  'card-refreshed',
  'order-cards-refreshed',
  'select-card',
]);

const isVisible = computed(() => !!props.card);

const orderProductCards = ref([]);
const loadingOrderProductCards = ref(false);

const toast = useToast();
const auth = useAuthStore();

const productionReportsStore = useProductionReportsStore();

const showDetails = ref(false);

const cardRef = toRef(props, 'card');
const { requestLayoutArts, loadingRequestArts, fetchRequestLayoutArts } =
  useRequestLayoutArts(cardRef);

const productionReports = computed(() => {
  return props.card?.id ? productionReportsStore.getReportsByCardId(props.card.id) : [];
});

const loadingProductionReports = computed(() => {
  return props.card?.id ? productionReportsStore.isLoadingByCardId(props.card.id) : false;
});

const markingAsProduced = computed(() => {
  return props.card?.id ? productionReportsStore.isMarkingAsProducedByCardId(props.card.id) : false;
});

const coverImage = computed(() => {
  return getCoverImage(props.card);
});

function applyFreshCard(cardId) {
  const fresh = orderProductCards.value.find((item) => Number(item.id) === Number(cardId));
  if (fresh) {
    emit('card-refreshed', fresh);
  }
}

async function loadOrderProductCards() {
  const card = props.card;
  if (!card?.order_id || !card?.id) {
    orderProductCards.value = [];
    return;
  }

  const orderId = card.order_id;
  const cardId = card.id;

  loadingOrderProductCards.value = true;
  try {
    const layouts = await productionService.getLayouts(orderId);
    if (!isVisible.value) {
      return;
    }

    orderProductCards.value = layouts;
    emit('order-cards-refreshed', layouts);
    applyFreshCard(cardId);
  } catch (error) {
    console.error(error);
    toast.error('Não foi possível atualizar os dados do card.');
  } finally {
    loadingOrderProductCards.value = false;
  }
}

function handleSelectOtherProductCard(card) {
  if (!card?.id || Number(card.id) === Number(props.card?.id)) {
    return;
  }

  emit('select-card', card);
  applyFreshCard(card.id);
}

function handleClose() {
  emit('close');
}

const canRemoveMembers = computed(() => auth.isAdmin());

function handleMemberAdded(member) {
  emit('member-added', member);
}

function handleMemberRemoved(memberId) {
  emit('member-removed', memberId);
}

async function markAsProduced() {
  if (!props.card?.id || markingAsProduced.value) {
    return;
  }

  try {
    const updated = await productionReportsStore.markAsProduced(props.card.id);

    // Emitir evento para atualizar o card no componente pai
    if (props.card && updated) {
      emit('card-updated', {
        ...props.card,
        production_date: updated.production_date,
        production_column_names_id: updated.production_column_names_id,
      });
    }

    toast.success('Data de produção atualizada com sucesso');
  } catch (error) {
    const errorMessage =
      error.response?.data?.message || 'Erro ao atualizar data de produção. Tente novamente.';

    toast.error(errorMessage);
  }
}

watch(isVisible, (visible) => {
  if (visible) {
    loadOrderProductCards();
  } else {
    orderProductCards.value = [];
  }
});

watch(
  () => props.card?.id,
  (newCardId) => {
    if (newCardId) {
      productionReportsStore.fetchProductionReports(newCardId);
    }

    if (!newCardId || !isVisible.value || orderProductCards.value.length === 0) {
      return;
    }

    applyFreshCard(newCardId);
  },
  { immediate: true },
);
</script>

<style lang="scss" scoped>
@import '@/scss/card-modal.scss';
</style>
