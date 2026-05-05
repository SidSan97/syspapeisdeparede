<template>
  <div v-if="show" class="request-arts-section">
    <h3 class="request-arts-section-title">
      <IconBrush />

      Solicitações de Artes
    </h3>
    <div v-if="loading" class="request-arts-section-loading text-muted">
      <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
      Carregando solicitações de artes...
    </div>
    <div v-else-if="arts.length === 0" class="request-arts-section-empty text-muted">
      Nenhuma solicitação de arte encontrada para este card.
    </div>
    <div v-else class="accordion" :id="accordionId">
      <div
        v-for="(interaction, interactionIndex) in arts"
        :key="interaction.id || interactionIndex"
        class="accordion-item mb-3"
      >
        <h2 class="accordion-header">
          <button
            class="accordion-button p-3 me-1"
            :class="{ collapsed: interactionIndex !== 0 }"
            type="button"
            data-bs-toggle="collapse"
            :data-bs-target="`#interaction-${prefix}-${interactionIndex}`"
            :aria-expanded="interactionIndex === 0"
            :aria-controls="`interaction-${prefix}-${interactionIndex}`"
          >
            <IconMessages class="me-2" />

            Interação #{{ interaction.id }}
            <span v-if="interaction.wall_info?.wall_name" class="badge bg-info ms-2">
              {{ interaction.wall_info.wall_name }}
            </span>
            <span class="badge bg-secondary ms-2"> {{ interaction.arts_count }} arte(s) </span>
          </button>
        </h2>
        <div
          :id="`interaction-${prefix}-${interactionIndex}`"
          class="accordion-collapse collapse"
          :class="{ show: interactionIndex === 0 }"
          :data-bs-parent="`#${accordionId}`"
        >
          <div class="accordion-body">
            <div v-if="interaction.wall_info" class="mb-3 p-2 rounded border">
              <div class="row g-2">
                <div class="col-md-12">
                  <div class="text-muted small">
                    <h5>Comentário</h5>
                  </div>
                  <div class="fw-semibold mb-3">
                    {{ interaction.comment || 'N/A' }}
                  </div>

                  <div class="d-flex justify-content-center">
                    <img
                      v-if="interaction.image_url"
                      :src="interaction.image_url"
                      alt="Imagem da arte"
                      class="img-fluid img-request"
                    />
                  </div>

                  <div class="mt-2 text-center">
                    <a
                      :href="interaction.image_url"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="btn btn-sm btn-default"
                    >
                      <IconExternalLink :size="16" class="me-1" />

                      Abrir em nova aba
                    </a>
                  </div>
                </div>
                <hr />
                <div class="col-md-6">
                  <div class="text-muted small">Ambiente</div>
                  <div class="fw-semibold">
                    {{ interaction.wall_info.room_name || 'N/A' }}
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="text-muted small">Parede</div>
                  <div class="fw-semibold">
                    {{ interaction.wall_info.wall_name || 'N/A' }}
                  </div>
                </div>
                <div v-if="interaction.wall_info.width" class="col-md-4">
                  <div class="text-muted small">Largura</div>
                  <div class="fw-semibold">
                    {{ formatNumber(interaction.wall_info.width) }}
                    m
                  </div>
                </div>
                <div v-if="interaction.wall_info.height" class="col-md-4">
                  <div class="text-muted small">Altura</div>
                  <div class="fw-semibold">
                    {{ formatNumber(interaction.wall_info.height) }}
                    m
                  </div>
                </div>
                <div v-if="interaction.wall_info.total_area" class="col-md-4">
                  <div class="text-muted small">Área</div>
                  <div class="fw-semibold">
                    {{ formatNumber(interaction.wall_info.total_area) }}
                    m²
                  </div>
                </div>
              </div>
            </div>

            <!-- Lista de Artes da Interação -->
            <div v-if="interaction.arts && interaction.arts.length > 0" class="mt-3">
              <h6 class="mb-3">
                <IconLibraryPhoto :size="18" class="me-2" />

                Artes ({{ interaction.arts.length }})
              </h6>
              <div
                v-for="(art, artIndex) in interaction.arts"
                :key="art.id || artIndex"
                class="card mb-3 border"
                :class="{ 'border-top': artIndex > 0 }"
              >
                <div class="card-body">
                  <div class="mb-3 p-2 border rounded">
                    <div class="row g-2">
                      <div v-if="art.dealer_name" class="col-md-6">
                        <div class="text-muted small">
                          <IconUserStar class="me-1" />

                          Revendedor
                        </div>
                        <div class="fw-semibold">
                          {{ art.dealer_name }}
                        </div>
                      </div>
                      <div v-if="art.designer_name" class="col-md-6">
                        <div class="text-muted small">
                          <IconUser class="me-1" />

                          Designer
                        </div>
                        <div class="fw-semibold">
                          {{ art.designer_name }}
                        </div>
                      </div>
                      <div v-if="art.created_at" class="col-12">
                        <div class="text-muted small">
                          <IconCalendar :size="18" class="me-1" />

                          Enviado em:
                          {{ formatDate(art.created_at) }}
                        </div>
                      </div>
                      <div v-if="approvalDisplay(art)" class="col-12">
                        <span class="badge" :class="approvalDisplay(art).badgeClass">
                          {{ approvalDisplay(art).label }}
                        </span>
                      </div>
                    </div>
                  </div>
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
import {
  IconBrush,
  IconCalendar,
  IconExternalLink,
  IconLibraryPhoto,
  IconMessages,
  IconUser,
  IconUserStar,
} from '@tabler/icons-vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  arts: {
    type: Array,
    default: () => [],
  },
  accordionId: {
    type: String,
    required: true,
  },
  prefix: {
    type: String,
    default: '',
  },
});

function formatNumber(value) {
  if (value === null || value === undefined) {
    return '-';
  }
  const numericValue = Number(value);
  return Number.isFinite(numericValue) ? numericValue.toFixed(2) : value;
}

function formatDate(date) {
  if (!date) {
    return '';
  }
  const parsedDate = new Date(date);
  if (Number.isNaN(parsedDate.getTime())) {
    return date;
  }
  const formatter = new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'medium',
    timeStyle: 'short',
  });
  return formatter.format(parsedDate);
}

function handleImageError(event) {
  event.target.style.display = 'none';
}

/** Tarja só para aprovada/rejeitada; pendente não exibe nada. */
function approvalDisplay(art) {
  const s = (art?.approval_status || 'pending').toLowerCase();
  if (s === 'approved') {
    return { label: 'Aprovada', badgeClass: 'bg-success' };
  }
  if (s === 'rejected') {
    return { label: 'Rejeitado', badgeClass: 'bg-danger' };
  }
  return null;
}
</script>

<style lang="scss" scoped>
.request-arts-section {
  margin-bottom: 24px;
  padding: 0.25rem;

  &:last-child {
    margin-bottom: 0;
  }
}

.request-arts-section-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--bs-body-color);
  margin-bottom: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.request-arts-section-loading,
.request-arts-section-empty {
  font-size: 0.875rem;
  line-height: 1.5;
}

.img-request {
  max-width: 100%;
  max-height: 360px;
  object-fit: contain;
  border-radius: 5px;
}
</style>
