<template>
  <Teleport v-if="card" to="body">
    <div class="modal show d-block" @click="handleClose">
      <div class="modal-dialog modal-xl modal-dialog-scrollable" @click.stop>
        <div class="modal-content">
          <div class="modal-header border-bottom py-3">

            <button class="btn btn-subtle btn-sm rounded-pill ms-auto" @click="handleClose">
              <i class="fa fa-times"></i>
            </button>
          </div>

          <div class="modal-body d-flex px-0">
            <div class="row g-0 flex-fill">
                <div v-if="coverImage" class="layout-modal-cover">
              <img :src="coverImage" :alt="`Imagem de capa de ${card.name}`" />
            </div>

            <div class="col-md-7 overflow-y-auto h-100">
                <main class="p-4">
                    <header class="d-flex align-items-center gap-3 mb-4">
                        <button class="btn btn-sm btn-subtle rounded-pill p-2" @click="markAsProduced" :disabled="markingAsProduced"
                        v-if="card.production_column_names_id < 2">
                        <i class="far fa-circle fa-lg"></i>
                      </button>
                      <i v-else class="fa fa-lg" :class="{ 'fa-check-circle text-success' : card.production_percentage == 100, 'fa-adjust text-secondary' : card.production_percentage != 100 }"></i>

                        <h3 class="fw-semibold m-0">{{ getCardDisplayName(card) }}</h3>
                    </header>
                  <div class="row">
                    <div class="col col-md-auto">
                      <MembersSection :card="card" :canRemoveMembers="canRemoveMembers" typePage="product" />
                    </div>
                    <div class="col col-md-auto">
                        <h3 class="fs-xs text-body-secondary">Prazo</h3>
                    <div class="btn btn-default">
                        <span>{{ card.delivery_date_start }} - {{ card.delivery_date_end }}</span>
                        <span class="badge bg-info fs-xs fw-semibold ms-2">{{ card.delivery_time }} dias</span>
                    </div>
                    </div>
                  </div>

                  <ProductionPercentageSection :card="card" />

                  <DescriptionSection :card="card" typePage="product" />

                  <AttachmentsSection :attachments="card.uploaded_files" />

                  <WallDetailsSection :wall="card.wall" />

                  <CollectionModelsSection :wall="card.wall" />

                  <div v-if="card.budget" class="layout-modal-section">
                    <h3 class="layout-modal-section-title">
                      <i class="fa fa-link"></i> Links
                    </h3>
                    <div v-if="card.budget.link_referring_model" class="layout-modal-info">
                      <a :href="card.budget.link_referring_model" target="_blank" rel="noopener noreferrer"
                        class="layout-modal-link">
                        <i class="fa fa-external-link"></i>
                        {{ card.budget.link_referring_model }}
                      </a>
                    </div>
                    <div v-else class="layout-modal-info text-muted">
                      Nenhum link disponível
                    </div>
                  </div>

                  <RequestArtsSection :show="true" :loading="loadingRequestArts" :arts="requestLayoutArts"
                    accordionId="requestArtsAccordion" prefix="" />

                  <ProductionReportsSection :card="card" :reports="productionReports"
                    :loading="loadingProductionReports" />
                </main>
            </div>

            <div class="col-md-5 overflow-y-auto h-100">
                <CommentsAndActivitySidebar :card="card" v-model:showDetails="showDetails" typePage="product" />
            </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show"></div>
  </Teleport>
</template>

<script setup>
import { computed, ref, watch, toRef } from 'vue';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';
import { getCardDisplayName } from '@/utils/cardUtils';
import { getCoverImage } from '@/modules/cardModals/composables/useCardUtils';
import MembersSection from '@/components/cardModal/MembersSection.vue';
import DescriptionSection from '@/components/cardModal/DescriptionSection.vue';
import AttachmentsSection from './productionCardModal/AttachmentsSection.vue';
import WallDetailsSection from '@/components/cardModal/WallDetailsSection.vue';
import CollectionModelsSection from './productionCardModal/CollectionModelsSection.vue';
import ProductionPercentageSection from './productionCardModal/ProductionPercentageSection.vue';
import ProductionReportsSection from './productionCardModal/ProductionReportsSection.vue';
import RequestArtsSection from '@/components/cardModal/RequestArtsSection.vue';
import CommentsAndActivitySidebar from '@/components/cardModal/CommentsAndActivitySidebar.vue';
import { useRequestLayoutArts } from '@/composables/useRequestLayoutArts';

const props = defineProps({
  card: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close']);

const auth = useAuthStore();

const showDetails = ref(false);

const cardRef = toRef(props, 'card');
const { requestLayoutArts, loadingRequestArts, fetchRequestLayoutArts } = useRequestLayoutArts(cardRef);

// Relatórios de produção
const productionReports = ref([]);
const loadingProductionReports = ref(false);

// Marcar como produzido
const markingAsProduced = ref(false);

const coverImage = computed(() => {
  return getCoverImage(props.card);
});

function handleClose() {
  emit('close');
}

const canRemoveMembers = computed(() => {
  return auth.isAdmin();
});


async function fetchProductionReports() {
  if (!props.card?.id) {
    productionReports.value = [];
    loadingProductionReports.value = false;
    return;
  }

  try {
    loadingProductionReports.value = true;

    const response = await axios.get(`v1/orders/order-budgets/${props.card.id}/production-reports`);
    const data = response?.data ?? [];

    productionReports.value = Array.isArray(data) ? data : [];
  } catch (error) {
    console.error('Erro ao buscar relatórios de produção:', error);
    productionReports.value = [];
  } finally {
    loadingProductionReports.value = false;
  }
}

async function markAsProduced() {
  if (!props.card?.id || markingAsProduced.value) {
    return;
  }

  markingAsProduced.value = true;

  try {
    const response = await axios.post(`v1/orders/order-budgets/${props.card.id}/mark-as-produced`);
    const updated = response?.data || null;

    // Atualizar o card localmente
    if (props.card && updated) {
      props.card.production_date = updated.production_date;
      props.card.production_column_names_id = updated.production_column_names_id;
    }

    if (window.Toast) {
      window.Toast.fire({
        icon: 'success',
        title: 'Data de produção atualizada com sucesso',
      });
    }
  } catch (error) {
    console.error('Erro ao marcar como produzido:', error);
    const errorMessage = error.response?.data?.message || 'Erro ao atualizar data de produção. Tente novamente.';

    if (window.Swal) {
      window.Swal.fire('Erro!', errorMessage, 'error');
    } else {
      alert(errorMessage);
    }
  } finally {
    markingAsProduced.value = false;
  }
}

// Buscar dados quando o card mudar
watch(() => props.card?.id, (newCardId) => {
  if (newCardId) {
    // Buscar relatórios de produção quando o card mudar
    fetchProductionReports();
  }
}, { immediate: true });
</script>

<style lang="scss" scoped>
@import '@/scss/card-modal.scss';
</style>
