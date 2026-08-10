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
                    @click="openApprovalTerms(interaction, art)"
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

            <div v-if="canRespond" class="mt-4 pt-3 border-top">
              <h6 class="mb-3 d-flex align-items-center">
                <IconCornerLeftUp :size="18" class="me-2" />
                Responder Interação
              </h6>

              <form @submit.prevent="submitResponse(interaction)">
                <div class="mb-3">
                  <label :for="`${accordionId}-art-file-${interaction.id}`" class="form-label">
                    Imagem da Arte (opcional)
                  </label>
                  <input
                    :id="`${accordionId}-art-file-${interaction.id}`"
                    type="file"
                    accept="image/*"
                    class="form-control"
                    :disabled="isUploading(interaction.id)"
                    @change="handleFileChange($event, interaction.id)"
                  />
                  <div class="form-text">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 10MB</div>

                  <div v-if="files[interaction.id]" class="mt-2">
                    <span class="badge bg-info">
                      <IconPhoto :size="16" class="me-1" />
                      {{ files[interaction.id].name }}
                    </span>
                    <button
                      type="button"
                      class="btn btn-sm btn-link text-danger p-0 ms-2"
                      :disabled="isUploading(interaction.id)"
                      @click="clearFile(interaction.id)"
                    >
                      <IconX :size="18" />
                    </button>
                  </div>
                </div>

                <div class="mb-3">
                  <label :for="`${accordionId}-art-comment-${interaction.id}`" class="form-label">
                    Comentário
                    <span class="text-danger">*</span>
                  </label>
                  <textarea
                    :id="`${accordionId}-art-comment-${interaction.id}`"
                    v-model="comments[interaction.id]"
                    class="form-control"
                    rows="3"
                    placeholder="Adicione um comentário sobre a arte..."
                    :disabled="isUploading(interaction.id)"
                  ></textarea>
                </div>

                <div class="d-flex justify-content-end">
                  <button
                    type="submit"
                    class="btn btn-primary"
                    :disabled="!hasComment(interaction.id) || isUploading(interaction.id)"
                  >
                    <span
                      v-if="isUploading(interaction.id)"
                      class="spinner-border spinner-border-sm me-2"
                      role="status"
                      aria-hidden="true"
                    ></span>
                    {{ isUploading(interaction.id) ? 'Enviando...' : 'Enviar Resposta' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <TermsOfUseAcceptModal
      v-model="termsModalOpen"
      :submitting="isApprovingPendingArt"
      confirm-button-text="Aprovar arte"
      @confirm="confirmApproval"
      @close="cancelApproval"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import {
  IconCornerLeftUp,
  IconExternalLink,
  IconLibraryPhoto,
  IconMessages,
  IconPhoto,
  IconX,
} from '@tabler/icons-vue';
import TermsOfUseAcceptModal from '@/components/details/TermsOfUseAcceptModal.vue';
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
  /** Habilita o formulário de resposta (envio de arte) da interação. */
  canRespond: {
    type: Boolean,
    default: false,
  },
  updatingApprovalStatus: {
    type: Object,
    default: () => ({}),
  },
  uploadingArt: {
    type: Object,
    default: () => ({}),
  },
  accordionSuffix: {
    type: [String, Number],
    default: null,
  },
});

const emit = defineEmits(['update-approval', 'respond']);

const { formatDate } = useFormatting();

const files = ref({});
const comments = ref({});
const termsModalOpen = ref(false);
const pendingApproval = ref(null);

const isApprovingPendingArt = computed(() =>
  pendingApproval.value ? isUpdating(pendingApproval.value.art?.id) : false,
);

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

/** A aprovação só acontece após o aceite do termo de uso. */
function openApprovalTerms(interaction, art) {
  pendingApproval.value = { interaction, art };
  termsModalOpen.value = true;
}

function cancelApproval() {
  pendingApproval.value = null;
}

function confirmApproval() {
  const art = pendingApproval.value?.art;
  if (!art) {
    return;
  }

  emit('update-approval', { art, status: 'approved', acceptedTermsOfUse: true });
}

/** Fecha o modal assim que o pai termina a requisição de aprovação. */
watch(isApprovingPendingArt, (isApproving, wasApproving) => {
  if (wasApproving && !isApproving) {
    termsModalOpen.value = false;
    pendingApproval.value = null;
  }
});

function isUploading(interactionId) {
  return Boolean(props.uploadingArt?.[interactionId]);
}

function hasComment(interactionId) {
  return Boolean((comments.value[interactionId] || '').trim());
}

function handleFileChange(event, interactionId) {
  const file = event.target.files?.[0];
  if (file) {
    files.value[interactionId] = file;
  }
}

function clearFile(interactionId) {
  delete files.value[interactionId];
  const input = document.getElementById(`${accordionId.value}-art-file-${interactionId}`);
  if (input) {
    input.value = '';
  }
}

function submitResponse(interaction) {
  const comment = (comments.value[interaction.id] || '').trim();
  if (!comment) {
    return;
  }

  emit('respond', {
    interaction,
    file: files.value[interaction.id] ?? null,
    comment,
    reset: () => {
      clearFile(interaction.id);
      comments.value[interaction.id] = '';
    },
  });
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
