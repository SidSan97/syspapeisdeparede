<template>
  <BaseModal
    v-model="isVisible"
    size="xl"
    :show-footer="false"
    :scrollable="true"
    @close="handleClose"
  >
    <template #header></template>

    <template #body>
      <div class="d-flex">
        <div class="row g-0 flex-fill">
          <div v-if="coverImage" class="layout-modal-cover">
            <img :src="coverImage" :alt="`Imagem de capa de ${card.name}`" />
          </div>

          <div class="col-md-7 overflow-y-auto h-100">
            <main class="p-4">
              <header class="d-flex align-items-center gap-3 mb-4">
                <h3 class="fw-semibold m-0">
                  {{ getCardDisplayName(card) }}
                </h3>
              </header>
              <div class="row">
                <div class="col col-md-auto">
                  <MembersSection
                    :card="card"
                    :canRemoveMembers="canRemoveMembers"
                    typePage="layout"
                    @member-removed="handleMemberRemoved"
                    @member-added="handleMemberAdded"
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
                    <span class="badge bg-info fs-xs fw-semibold ms-2"
                      >{{ card.delivery_time }} dias</span
                    >
                  </div>
                </div>
              </div>
  
              <PowerUpActivity :card="card" @activity-updated="handleActivityUpdated" />

              <hr>

              <DescriptionSection :card="card" typePage="layout" />

              <AttachmentsSection :attachments="card.uploaded_files" />

              <WallDetailsSection :wall="card.wall" />

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
                    <IconExternalLink :size="16" />

                    {{ card.budget.link_referring_model }}
                  </a>
                </div>
                <div v-else class="layout-modal-info text-muted">Nenhum link disponível</div>
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
            <CommentsAndActivitySidebar
              :card="card"
              v-model:showDetails="showDetails"
              typePage="layout"
            />
          </div>
        </div>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { computed, ref, toRef } from 'vue';

import { useAuthStore } from '@/stores/auth';
import { useRequestLayoutArts } from '@/composables/useRequestLayoutArts';

import { getCardDisplayName } from '@/utils/cardUtils';
import { getCoverImage } from '@/modules/card-modals/composables/useCardUtils';

import BaseModal from '@/components/common/BaseModal.vue';
import CommentsAndActivitySidebar from '@/components/card-modal/CommentsAndActivitySidebar.vue';
import DescriptionSection from '@/components/card-modal/DescriptionSection.vue';
import AttachmentsSection from './layout-card-modal/AttachmentsSection.vue';
import LoadArtSection from '@/components/card-modal/LoadArtSection.vue';
import PowerUpActivity from '@/components/card-modal/PowerUpActivity.vue';
import RequestArtsSection from '@/components/card-modal/RequestArtsSection.vue';
import MembersSection from '@/components/card-modal/MembersSection.vue';
import WallDetailsSection from '@/components/card-modal/WallDetailsSection.vue';
import CollectionModelsSection from './layout-card-modal/CollectionModelsSection.vue';

import { IconExternalLink, IconLink } from '@tabler/icons-vue';

const props = defineProps({
  card: {
    type: Object,
    default: null,
  },
});
const emit = defineEmits(['close', 'member-added', 'member-removed', 'activity-updated']);

const isVisible = computed(() => !!props.card);

const auth = useAuthStore();

const showDetails = ref(false);

const cardRef = toRef(props, 'card');
const { requestLayoutArts, loadingRequestArts, fetchRequestLayoutArts } =
  useRequestLayoutArts(cardRef);

const coverImage = computed(() => getCoverImage(props.card));

function handleActivityUpdated(payload) {
  emit('activity-updated', payload);
}

function handleClose() {
  emit('close');
}

const canRemoveMembers = computed(() => auth.isAdmin());

const canLoadArt = computed(() => auth.hasRole(['designer', 'admin', 'production']));

function handleArtUploaded() {
  fetchRequestLayoutArts();
}

function handleMemberAdded(member) {
  emit('member-added', member);
}

function handleMemberRemoved(memberId) {
  emit('member-removed', memberId);
}
</script>

<style lang="scss" scoped>
@import '@/scss/card-modal.scss';
</style>
