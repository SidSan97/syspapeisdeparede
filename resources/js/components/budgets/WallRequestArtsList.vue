<template>
  <div class="wall-request-arts mt-3">
    <div class="text-muted small mb-2 d-flex align-items-center gap-1">
      <IconLibraryPhoto :size="16" />
      <h6 class="mb-0">Solicitações de arte ({{ arts.length }})</h6>
    </div>

    <div class="accordion" :id="accordionId">
      <div
        v-for="(interaction, interactionIndex) in arts"
        :key="interaction.id || interactionIndex"
        class="accordion-item mb-2"
      >
        <h2 class="accordion-header" :id="`${accordionId}-heading-${interactionIndex}`">
          <button
            class="accordion-button p-2"
            :class="{ collapsed: interactionIndex !== 0 }"
            type="button"
            data-bs-toggle="collapse"
            :data-bs-target="`#${collapseId(interactionIndex)}`"
            :aria-expanded="interactionIndex === 0"
            :aria-controls="collapseId(interactionIndex)"
          >
            <IconMessages :size="18" class="me-2" />
            Interação #{{ interaction.id }}
            <span class="badge bg-secondary ms-2">{{ interaction.arts_count }} arte(s)</span>
          </button>
        </h2>

        <div
          :id="collapseId(interactionIndex)"
          class="accordion-collapse collapse"
          :class="{ show: interactionIndex === 0 }"
          :aria-labelledby="`${accordionId}-heading-${interactionIndex}`"
          :data-bs-parent="`#${accordionId}`"
        >
          <div class="accordion-body p-3">
            <div
              v-for="(art, artIndex) in interaction.arts"
              :key="art.id || artIndex"
              class="mb-3"
              :class="{ 'border-top pt-3': artIndex > 0 }"
            >
              <div v-if="art.comment" class="mb-2">
                <div class="text-muted small">Comentário</div>
                <div class="fw-semibold">{{ art.comment }}</div>
              </div>

              <div v-if="art.image_url" class="text-center mb-2">
                <img
                  :src="art.image_url"
                  alt="Imagem da arte"
                  class="img-fluid img-request rounded"
                />
                <div class="mt-2">
                  <a
                    :href="art.image_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn btn-sm btn-default"
                  >
                    <IconExternalLink :size="16" class="me-1" />
                    Abrir em nova aba
                  </a>
                </div>
              </div>

              <div class="row g-2 small mb-2">
                <div v-if="art.dealer_name" class="col-md-6">
                  <div class="text-muted">Revendedor</div>
                  <div class="fw-semibold">{{ art.dealer_name }}</div>
                </div>
                <div v-if="art.designer_name" class="col-md-6">
                  <div class="text-muted">Designer</div>
                  <div class="fw-semibold">{{ art.designer_name }}</div>
                </div>
                <div v-if="art.created_at" class="col-12">
                  <div class="text-muted">Enviado em: {{ formatDate(art.created_at) }}</div>
                </div>
              </div>

              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div v-if="approvalDisplay(art)">
                  <span class="badge" :class="approvalDisplay(art).badgeClass">
                    {{ approvalDisplay(art).label }}
                  </span>
                </div>

                <div v-if="canApprove" class="d-flex gap-2">
                  <button
                    type="button"
                    class="btn btn-sm btn-success"
                    :disabled="
                      isUpdating(art.id) ||
                      art.approval_status === 'approved' ||
                      hasAnotherApprovedArt(interaction, art.id)
                    "
                    :title="
                      hasAnotherApprovedArt(interaction, art.id)
                        ? 'Já existe uma arte aprovada para esta parede.'
                        : ''
                    "
                    @click="emit('update-approval', { art, status: 'approved' })"
                  >
                    <span
                      v-if="updatingApprovalStatus[art.id] === 'approved'"
                      class="spinner-border spinner-border-sm me-1"
                      role="status"
                    ></span>
                    Aprovar
                  </button>
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    :disabled="isUpdating(art.id) || art.approval_status === 'rejected'"
                    @click="emit('update-approval', { art, status: 'rejected' })"
                  >
                    <span
                      v-if="updatingApprovalStatus[art.id] === 'rejected'"
                      class="spinner-border spinner-border-sm me-1"
                      role="status"
                    ></span>
                    Reprovar
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { IconExternalLink, IconLibraryPhoto, IconMessages } from '@tabler/icons-vue';
import { useFormatting } from '@/composables/useFormatting';

const props = defineProps({
  arts: {
    type: Array,
    default: () => [],
  },
  canApprove: {
    type: Boolean,
    default: false,
  },
  updatingApprovalStatus: {
    type: Object,
    default: () => ({}),
  },
  accordionSuffix: {
    type: [String, Number],
    default: null,
  },
});

const emit = defineEmits(['update-approval']);

const { formatDate } = useFormatting();

const accordionId = computed(() => {
  const suffix =
    props.accordionSuffix != null && String(props.accordionSuffix).trim() !== ''
      ? String(props.accordionSuffix)
      : Math.random().toString(36).slice(2, 9);
  return `wall-request-arts-${suffix}`;
});

function collapseId(index) {
  return `${accordionId.value}-collapse-${index}`;
}

function approvalDisplay(art) {
  const status = (art?.approval_status || 'pending').toLowerCase();
  if (status === 'approved') {
    return { label: 'Aprovada', badgeClass: 'bg-success' };
  }
  if (status === 'rejected') {
    return { label: 'Rejeitado', badgeClass: 'bg-danger' };
  }
  return null;
}

function hasAnotherApprovedArt(interaction, currentArtId) {
  if (!interaction?.arts || !Array.isArray(interaction.arts)) {
    return false;
  }

  return interaction.arts.some((item) => {
    if (!item?.id || item.id === currentArtId) {
      return false;
    }
    return (item.approval_status || '').toLowerCase() === 'approved';
  });
}

function isUpdating(artId) {
  return Boolean(props.updatingApprovalStatus?.[artId]);
}
</script>

<style scoped>
.accordion-button {
  font-weight: 500;
}

.img-request {
  max-height: 280px;
  object-fit: contain;
}
</style>
