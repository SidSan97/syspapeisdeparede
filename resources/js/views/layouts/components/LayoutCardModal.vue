<template>
    <Teleport v-if="card" to="body">
      <div class="layout-modal-overlay" @click="handleClose">
        <div class="layout-modal" @click.stop>
          <div class="layout-modal-header">
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
                <MembersSection
                  :card="card"
                  :canRemoveMembers="canRemoveMembers"
                />
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

                <DescriptionSection :card="card" />

                <div v-if="card.uploaded_files && card.uploaded_files.length > 0" class="layout-modal-section">
                  <h3 class="layout-modal-section-title">
                    <i class="fa fa-paperclip"></i> Anexos
                  </h3>
                  <div class="d-flex flex-column gap-3">
                    <div
                      v-for="(file, fileIndex) in card.uploaded_files"
                      :key="fileIndex"
                      class="layout-modal-attachment"
                    >
                      <div class="layout-modal-attachment-preview">
                        <img v-if="isImageFile(file)" :src="getImageUrl(file)" :alt="getAttachmentName(file, fileIndex)" />
                        <i v-else class="fa fa-file fa-fw"></i>
                      </div>
                      <div class="d-flex flex-column gap-2 flex-grow-1">
                        <div class="layout-modal-attachment-name">{{ getAttachmentName(file, fileIndex) }}</div>
                        <div class="layout-modal-attachment-meta">
                          {{ formatDate(file.created_at) }}
                        </div>
                        <a
                          class="layout-modal-attachment-button"
                          :href="getImageUrl(file)"
                          target="_blank"
                          rel="noopener noreferrer"
                        >
                          Abrir
                        </a>
                      </div>
                    </div>
                  </div>
                </div>

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
  import { computed, ref, watch, toRef } from 'vue';
  import { useAuthStore } from '@/stores/auth';
  import axios from 'axios';
  import { getCardDisplayName } from '@/utils/cardUtils';
  import CommentsAndActivitySidebar from './layoutCardModal/CommentsAndActivitySidebar.vue';
  import DescriptionSection from './layoutCardModal/DescriptionSection.vue';
  import LoadArtSection from './layoutCardModal/LoadArtSection.vue';
  import RequestArtsSection from './layoutCardModal/RequestArtsSection.vue';
  import MembersSection from './layoutCardModal/MembersSection.vue';
  import WallDetailsSection from './layoutCardModal/WallDetailsSection.vue';
  import CollectionModelsSection from './layoutCardModal/CollectionModelsSection.vue';
  import { useRequestLayoutArts } from '@/views/layouts/composables/useRequestLayoutArts';

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

  const activityDateFormatter = new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'medium',
    timeStyle: 'short',
  });

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

  function formatCurrency(value) {
    if (value === null || value === undefined) {
      return currencyFormatter.format(0);
    }
    const numericValue = Number(value);
    return currencyFormatter.format(Number.isFinite(numericValue) ? numericValue : 0);
  }

  function formatNumber(value) {
    if (value === null || value === undefined) {
      return '-';
    }
    const numericValue = Number(value);
    return Number.isFinite(numericValue) ? numericValue.toFixed(2) : value;
  }

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

  function getAttachmentName(file, index = 0) {
    return file?.name || file?.original_name || file?.file_name || `Arquivo ${index + 1}`;
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

  function formatDate(date) {
    if (!date) {
      return '';
    }
    const parsedDate = new Date(date);
    if (Number.isNaN(parsedDate.getTime())) {
      return date;
    }
    return activityDateFormatter.format(parsedDate);
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
