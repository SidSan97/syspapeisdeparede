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
  
              <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <button
                  type="button"
                  class="btn btn-primary btn-sm"
                  :disabled="workflowStarting || !!card.started_at || !card?.id"
                  @click="handleStartWorkflow"
                >
                  <IconPlayerPlay :size="18" class="me-1" />
                  Iniciar
                </button>
                <button
                  type="button"
                  class="btn btn-success btn-sm"
                  :disabled="
                    workflowFinishing ||
                    !card?.started_at ||
                    !!card?.finished_at ||
                    !card?.id
                  "
                  @click="handleFinishWorkflow"
                >
                  <IconCircleCheck :size="18" class="me-1" />
                  Concluir
                </button>
              </div>
              <div v-if="card?.started_at" class="small text-body-secondary">
                <div>
                  <span class="text-body-secondary">Início:</span>
                  <span class="fw-semibold text-body ms-1">{{
                    formatWorkflowDateTime(card.started_at)
                  }}</span>
                </div>
                <div v-if="card.finished_at" class="mt-1">
                  <span class="text-body-secondary">Fim:</span>
                  <span class="fw-semibold text-body ms-1">{{
                    formatWorkflowDateTime(card.finished_at)
                  }}</span>
                </div>
                <div v-if="workflowDurationLabel" class="mt-1">
                  <span class="text-body-secondary">Duração:</span>
                  <span class="fw-semibold text-body ms-1">{{ workflowDurationLabel }}</span>
                </div>
              </div>

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
import { differenceInCalendarDays } from 'date-fns';

import { useAuthStore } from '@/stores/auth';
import { useRequestLayoutArts } from '@/composables/useRequestLayoutArts';
import { useToast } from '@/composables/useToast';
import { layoutService } from '@/services/layoutService';

import { getCardDisplayName } from '@/utils/cardUtils';
import { getCoverImage } from '@/modules/card-modals/composables/useCardUtils';

import BaseModal from '@/components/common/BaseModal.vue';
import CommentsAndActivitySidebar from '@/components/card-modal/CommentsAndActivitySidebar.vue';
import DescriptionSection from '@/components/card-modal/DescriptionSection.vue';
import AttachmentsSection from './layout-card-modal/AttachmentsSection.vue';
import LoadArtSection from '@/components/card-modal/LoadArtSection.vue';
import RequestArtsSection from '@/components/card-modal/RequestArtsSection.vue';
import MembersSection from '@/components/card-modal/MembersSection.vue';
import WallDetailsSection from '@/components/card-modal/WallDetailsSection.vue';
import CollectionModelsSection from './layout-card-modal/CollectionModelsSection.vue';

// Icons
import { IconCircleCheck, IconExternalLink, IconLink, IconPlayerPlay } from '@tabler/icons-vue';

const props = defineProps({
  card: {
    type: Object,
    default: null,
  },
});
const emit = defineEmits(['close', 'member-added', 'member-removed', 'workflow-updated']);

const isVisible = computed(() => !!props.card);

const toast = useToast();

const auth = useAuthStore();

const workflowStarting = ref(false);
const workflowFinishing = ref(false);

const showDetails = ref(false);

const cardRef = toRef(props, 'card');
const { requestLayoutArts, loadingRequestArts, fetchRequestLayoutArts } =
  useRequestLayoutArts(cardRef);

const coverImage = computed(() => getCoverImage(props.card));

const workflowDurationLabel = computed(() => {
  const c = props.card;
  if (!c?.started_at || !c?.finished_at) {
    return '';
  }
  const start = new Date(c.started_at);
  const end = new Date(c.finished_at);
  if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
    return '';
  }
  const days = differenceInCalendarDays(end, start);
  if (days < 0) {
    return '';
  }
  return `${days} dia${days === 1 ? '' : 's'}`;
});

function formatWorkflowDateTime(iso) {
  if (!iso) {
    return '-';
  }
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) {
    return String(iso);
  }
  return d.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

async function handleStartWorkflow() {
  if (!props.card?.id || props.card.started_at) {
    return;
  }
  workflowStarting.value = true;
  try {
    const data = await layoutService.startOrderBudget(props.card.id);
    const payload = {
      started_at: data.started_at ?? null,
      finished_at: data.finished_at ?? props.card.finished_at ?? null,
    };
    emit('workflow-updated', payload);
    toast.success('Card iniciado.');
  } catch (error) {
    const msg =
      error.response?.data?.message || error.message || 'Não foi possível iniciar o card.';
    toast.error(msg);
  } finally {
    workflowStarting.value = false;
  }
}

async function handleFinishWorkflow() {
  if (!props.card?.id || !props.card.started_at || props.card.finished_at) {
    return;
  }
  workflowFinishing.value = true;
  try {
    const data = await layoutService.finishOrderBudget(props.card.id);
    const payload = {
      started_at: data.started_at ?? props.card.started_at,
      finished_at: data.finished_at ?? null,
    };
    emit('workflow-updated', payload);
    toast.success('Card concluído.');
  } catch (error) {
    const msg =
      error.response?.data?.message || error.message || 'Não foi possível concluir o card.';
    toast.error(msg);
  } finally {
    workflowFinishing.value = false;
  }
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
