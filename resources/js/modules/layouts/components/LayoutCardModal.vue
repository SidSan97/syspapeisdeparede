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
                        <h3 class="fw-semibold m-0">{{ getCardDisplayName(card) }}</h3>
                    </header>
                  <div class="row">
                    <div class="col col-md-auto">
                      <MembersSection :card="card" :canRemoveMembers="canRemoveMembers" typePage="layout" />
                    </div>
                    <div class="col col-md-auto">
                        <h3 class="fs-xs text-body-secondary">Prazo</h3>
                    <div class="btn btn-default">
                        <span>{{ card.delivery_date_start }} - {{ card.delivery_date_end }}</span>
                        <span class="badge bg-info fs-xs fw-semibold ms-2">{{ card.delivery_time }} dias</span>
                    </div>
                    </div>
                  </div>

                  <DescriptionSection :card="card" typePage="layout" />

                  <AttachmentsSection :attachments="card.uploaded_files" />

                  <div class="layout-modal-section">
                    <h3 class="layout-modal-section-title">
                      <i class="fa fa-money"></i> Valores do Orçamento
                    </h3>
                    <div class="row layout-modal-info">
                      <div class="col-md-6 mb-2">
                        <div class="text-muted small mb-1">Total à Vista</div>
                        <div class="layout-modal-amount text-success">{{ formatCurrency(card.total_amount || 0) }}</div>
                      </div>
                      <div class="col-md-6 mb-2">
                        <div class="text-muted small mb-1">Total a Prazo</div>
                        <div class="layout-modal-amount text-primary">{{ formatCurrency(card.total_amount_installments || 0) }}</div>
                      </div>
                    </div>
                  </div>

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

                  <RequestArtsSection
                    :show="!!card.budget"
                    :loading="loadingRequestArts"
                    :arts="requestLayoutArts"
                    accordionId="requestArtsAccordion"
                    prefix=""
                  />

                  <LoadArtSection
                    :card="card"
                    :canLoad="canLoadArt"
                    @art-uploaded="handleArtUploaded"
                  />

                  <RequestArtsSection
                    :show="true"
                    :loading="loadingRequestArts"
                    :arts="requestLayoutArts"
                    accordionId="requestArtsAccordionBottom"
                    prefix="bottom"
                  />
                </main>
            </div>

            <div class="col-md-5 overflow-y-auto h-100">
                <CommentsAndActivitySidebar :card="card" v-model:showDetails="showDetails" typePage="layout" />
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
  import { computed, ref, toRef } from 'vue';
  import { useAuthStore } from '@/stores/auth';
  import { getCardDisplayName } from '@/utils/cardUtils';
  import { getCoverImage } from '@/modules/card-modals/composables/useCardUtils';
  import CommentsAndActivitySidebar from '@/components/card-modal/CommentsAndActivitySidebar.vue';
  import DescriptionSection from '@/components/card-modal/DescriptionSection.vue';
  import AttachmentsSection from './layout-card-modal/AttachmentsSection.vue';
  import LoadArtSection from '@/components/card-modal/LoadArtSection.vue';
  import RequestArtsSection from '@/components/card-modal/RequestArtsSection.vue';
  import MembersSection from '@/components/card-modal/MembersSection.vue';
  import WallDetailsSection from '@/components/card-modal/WallDetailsSection.vue';
  import CollectionModelsSection from './layout-card-modal/CollectionModelsSection.vue';
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

  const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  });

  const coverImage = computed(() => {
    return getCoverImage(props.card);
  });

  function formatCurrency(value) {
    if (value === null || value === undefined) {
      return currencyFormatter.format(0);
    }
    const numericValue = Number(value);
    return currencyFormatter.format(Number.isFinite(numericValue) ? numericValue : 0);
  }

  function handleClose() {
    emit('close');
  }


  const canRemoveMembers = computed(() => {
    return auth.isAdmin();
  });

  const canLoadArt = computed(() => {
    return auth.hasRole(['designer', 'admin', 'production']);
  });

  function handleArtUploaded() {
    // Recarregar lista de artes quando uma arte for enviada
    fetchRequestLayoutArts();
  }

  </script>

<style lang="scss" scoped>
  @import '@/scss/card-modal.scss';
</style>
