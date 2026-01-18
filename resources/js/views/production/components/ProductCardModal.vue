<template>
    <Teleport v-if="card" to="body">
      <div class="layout-modal-overlay" @click="handleClose">
        <div class="layout-modal" @click.stop>
          <div class="layout-modal-header d-flex justify-content-between align-items-center">
            <h2 class="layout-modal-title">{{ getCardDisplayName(card) }}</h2>
            <button class="layout-modal-close" @click="handleClose">
              <i class="fa fa-times"></i>
            </button>
          </div>

          <div class="layout-modal-body">
            <div v-if="coverImage" class="layout-modal-cover">
              <img :src="coverImage" :alt="`Imagem de capa de ${card.name}`" />
            </div>

            <div class="container-fluid pt-3">
                <div class="modal-buttons-options position-relative d-flex justify-content-between">
                    <MembersSection
                      :card="card"
                      :canRemoveMembers="canRemoveMembers"
                    />

                    <div class="me-3">
                        <button
                            class="btn btn-primary"
                            @click="markAsProduced"
                            :disabled="markingAsProduced"
                            v-if="card.production_column_names_id < 2"
                        >
                            <span v-if="markingAsProduced" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            {{ markingAsProduced ? 'Atualizando...' : 'Produzido' }}
                        </button>
                        <span v-else>{{ card.production_percentage == 100 ? 'Produzido' : 'Em produção' }}</span>
                    </div>
                </div>
            </div>

            <div class="modal-content-layout">
              <div class="layout-modal-main">

                <div class="layout-modal-section">
                  <h3 class="layout-modal-section-title">
                    <i class="fa fa-calendar"></i> Prazo
                  </h3>
                  <div class="layout-modal-info">
                    <span>{{ card.delivery_date_start }} - {{ card.delivery_date_end }}</span>
                    <span class="layout-modal-info-label">{{ card.delivery_time }} dias</span>
                  </div>
                </div>

                <ProductionPercentageSection :card="card" />

                <DescriptionSection :card="card" />

                <AttachmentsSection :attachments="card.uploaded_files" />

                <WallDetailsSection :wall="card.wall" />

                <CollectionModelsSection :wall="card.wall" />

                <div v-if="card.budget" class="layout-modal-section">
                  <h3 class="layout-modal-section-title">
                    <i class="fa fa-link"></i> Links
                  </h3>
                  <div v-if="card.budget.link_referring_model" class="layout-modal-info">
                    <a
                      :href="card.budget.link_referring_model"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="layout-modal-link"
                    >
                      <i class="fa fa-external-link"></i>
                      {{ card.budget.link_referring_model }}
                    </a>
                  </div>
                  <div v-else class="layout-modal-info text-muted">
                    Nenhum link disponível
                  </div>
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
              </div>

              <CommentsAndActivitySidebar
                :card="card"
                v-model:showDetails="showDetails"
              />
            </div>
          </div>
        </div>
      </div>
    </Teleport>
</template>

<script setup>
  import { computed, ref, watch } from 'vue';
  import { useAuthStore } from '@/stores/auth';
  import axios from 'axios';
  import { getCardDisplayName } from '@/utils/cardUtils';
  import MembersSection from './productionCardModal/MembersSection.vue';
  import DescriptionSection from './productionCardModal/DescriptionSection.vue';
  import AttachmentsSection from './productionCardModal/AttachmentsSection.vue';
  import WallDetailsSection from './productionCardModal/WallDetailsSection.vue';
  import CollectionModelsSection from './productionCardModal/CollectionModelsSection.vue';
  import ProductionPercentageSection from './productionCardModal/ProductionPercentageSection.vue';
  import ProductionReportsSection from './productionCardModal/ProductionReportsSection.vue';
  import RequestArtsSection from './productionCardModal/RequestArtsSection.vue';
  import CommentsAndActivitySidebar from './productionCardModal/CommentsAndActivitySidebar.vue';

  const props = defineProps({
    card: {
      type: Object,
      default: null,
    },
  });

  const emit = defineEmits(['close']);

  const auth = useAuthStore();

  const showDetails = ref(false);

  // Solicitações de arte
  const requestLayoutArts = ref([]);
  const loadingRequestArts = ref(false);

  // Relatórios de produção
  const productionReports = ref([]);
  const loadingProductionReports = ref(false);

  // Marcar como produzido
  const markingAsProduced = ref(false);

  const coverImage = computed(() => {
    if (!props.card) {
      return '';
    }
    if (props.card.image) {
      return props.card.image;
    }
    const imageAttachment = props.card.uploaded_files?.find(file => isImageFile(file));
    return imageAttachment ? getImageUrl(imageAttachment) : '';
  });

  function getImageUrl(file) {
    if (file.url) {
      return file.url;
    }
    if (file.fileUrl) {
      return file.fileUrl;
    }
    if (file.file_path) {
      // Se for um caminho relativo, construir a URL completa
      if (file.file_path.startsWith('http')) {
        return file.file_path;
      }
      return `/storage/${file.file_path}`;
    }
    return '';
  }

  function isImageFile(file) {
    if (!file) {
      return false;
    }
    const mime = (file.mime || file.mimetype || '').toLowerCase();
    if (mime.startsWith('image/')) {
      return true;
    }
    const name = (file.name || file.original_name || file.file_name || '').toLowerCase();
    return ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.bmp'].some(ext => name.endsWith(ext));
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

  function handleClose() {
    emit('close');
  }

  const canRemoveMembers = computed(() => {
    return auth.isAdmin();
  });

  async function fetchRequestLayoutArts() {
    if (!props.card?.id || !props.card?.order?.id || !auth.user?.id) {
      requestLayoutArts.value = [];
      loadingRequestArts.value = false;
      return;
    }

    try {
      loadingRequestArts.value = true;

      const budgetId = props.card.order.id;

      const params = {
        budget_id: budgetId,
        dealer_id: auth.user.id,
      };

      const response = await axios.get('v1/budgets/request-layout-arts', {
        params,
      });

      const data = response?.data || response;

      if (data?.success && Array.isArray(data.data)) {
        requestLayoutArts.value = data.data.map((art) => {
          let imageUrl = art.image_url;
          if (!imageUrl && art.path_file) {
            imageUrl = resolveImageUrl(art.path_file);
          }

          return {
            id: art.id,
            comment: art.comment || null,
            image_url: imageUrl,
            path_file: art.path_file || null,
            created_at: art.created_at || null,
            designer_name: art.designer?.name || art.designer_name || null,
            dealer_name: art.dealer?.name || art.dealer_name || null,
            wall_info: art.wall_info || null,
            wall_name: art.wall_name || art.wall_info?.wall_name || null,
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

  async function fetchProductionReports() {
    if (!props.card?.id) {
      productionReports.value = [];
      loadingProductionReports.value = false;
      return;
    }

    try {
      loadingProductionReports.value = true;

      const response = await axios.get(`v1/orders/order-budgets/${props.card.id}/production-reports`);

      if (response.data?.success && Array.isArray(response.data.data)) {
        productionReports.value = response.data.data;
      } else {
        productionReports.value = [];
      }
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

      // Atualizar o card localmente
      if (props.card && response.data?.data) {
        props.card.production_date = response.data.data.production_date;
        props.card.production_column_names_id = response.data.data.production_column_names_id;
      }

      if (window.Toast) {
        window.Toast.fire({
          icon: 'success',
          title: response.data.message || 'Data de produção atualizada com sucesso',
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
  watch(() => [props.card?.id, props.card?.budget?.id], ([newCardId, newBudgetId]) => {
    if (newCardId) {
      // Buscar relatórios de produção quando o card mudar
      fetchProductionReports();
      fetchRequestLayoutArts();
    }
  }, { immediate: true });
</script>

<style lang="scss" scoped>
  @import '@/scss/card-modal.scss';
</style>


