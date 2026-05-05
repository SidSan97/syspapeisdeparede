<template>
  <div class="card mb-4">
    <div class="card-header bg-transparent">
      <h5 class="mb-0 fw-semibold">Solicitação de artes</h5>
    </div>
    <div class="card-body">
      <div v-if="loadingRequestArts" class="text-center text-muted py-3">
        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
        Carregando solicitações de artes...
      </div>
      <div v-else-if="requestLayoutArts.length === 0" class="text-center text-muted py-3">
        Nenhuma solicitação de arte encontrada.
      </div>
      <div v-else class="accordion" id="requestArtsAccordion">
        <div
          v-for="(interaction, interactionIndex) in requestLayoutArts"
          :key="interaction.id || interactionIndex"
          class="accordion-item mb-3"
        >
          <h2 class="accordion-header">
            <button
              class="accordion-button p-2"
              :class="{ collapsed: interactionIndex !== 0 }"
              type="button"
              data-bs-toggle="collapse"
              :data-bs-target="`#interaction-${interactionIndex}`"
              :aria-expanded="interactionIndex === 0"
              :aria-controls="`interaction-${interactionIndex}`"
            >
              <IconMessages :size="18" class="me-2" />

              Interação #{{ interaction.id }}
              <span v-if="interaction.wall_info?.wall_name" class="badge bg-info ms-2">
                {{ interaction.wall_info.wall_name }}
              </span>
              <span class="badge bg-secondary ms-2"> {{ interaction.arts_count }} arte(s) </span>
            </button>
          </h2>
          <div
            :id="`interaction-${interactionIndex}`"
            class="accordion-collapse collapse"
            :class="{ show: interactionIndex === 0 }"
            data-bs-parent="#requestArtsAccordion"
          >
            <div class="accordion-body p-4">
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
                  <IconLibraryPhoto class="me-2" />

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
                            <IconUserStar :size="16" class="me-1" />

                            Revendedor
                          </div>
                          <div class="fw-semibold">
                            {{ art.dealer_name }}
                          </div>
                        </div>
                        <div v-if="art.designer_name" class="col-md-6">
                          <div class="text-muted small">
                            <IconUser :size="16" class="me-1" />

                            Designer
                          </div>
                          <div class="fw-semibold">
                            {{ art.designer_name }}
                          </div>
                        </div>
                        <div v-if="art.created_at" class="col-12">
                          <div class="text-muted small">
                            <IconCalendar :size="16" class="me-1" />

                            Enviado em:
                            {{ formatDate(art.created_at) }}
                          </div>
                        </div>
                        <div class="col-12">
                          <div
                            class="d-flex align-items-center justify-content-between flex-wrap gap-2"
                          >
                            <div v-if="approvalDisplay(art)">
                              <span class="badge" :class="approvalDisplay(art).badgeClass">
                                {{ approvalDisplay(art).label }}
                              </span>
                            </div>
                            <div class="row">
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
                            <div v-if="auth.user && isReseller" class="d-flex gap-2">
                              <button
                                type="button"
                                class="btn btn-sm btn-success"
                                :disabled="
                                  updatingApprovalStatus[art.id] ||
                                  art.approval_status === 'approved' ||
                                  hasAnotherApprovedArt(interaction, art.id)
                                "
                                :title="
                                  hasAnotherApprovedArt(interaction, art.id)
                                    ? 'Já existe uma arte aprovada para esta parede.'
                                    : ''
                                "
                                @click="updateApprovalStatus(art, 'approved')"
                              >
                                <span
                                  v-if="updatingApprovalStatus[art.id] === 'approved'"
                                  class="spinner-border spinner-border-sm me-1"
                                  role="status"
                                  aria-hidden="true"
                                ></span>
                                Aprovar
                              </button>
                              <button
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                :disabled="
                                  updatingApprovalStatus[art.id] ||
                                  art.approval_status === 'rejected'
                                "
                                @click="updateApprovalStatus(art, 'rejected')"
                              >
                                <span
                                  v-if="updatingApprovalStatus[art.id] === 'rejected'"
                                  class="spinner-border spinner-border-sm me-1"
                                  role="status"
                                  aria-hidden="true"
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

              <!-- Formulário de Resposta do Revendedor -->
              <div v-if="auth.user && isReseller" class="mt-4 pt-3 border-top">
                <h6 class="mb-3">
                  <IconCornerLeftUp class="me-2" />

                  Responder Interação
                </h6>
                <form @submit.prevent="handleRespondToInteraction(interaction)">
                  <div class="mb-3">
                    <label :for="'art-file-' + interaction.id" class="form-label">
                      Imagem da Arte (opcional)
                    </label>
                    <input
                      :id="'art-file-' + interaction.id"
                      type="file"
                      accept="image/*"
                      class="form-control"
                      @change="handleArtFileChange($event, interaction.id)"
                      :disabled="uploadingArt[interaction.id]"
                    />
                    <div class="form-text">
                      Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 10MB
                    </div>
                    <div v-if="artFiles[interaction.id]" class="mt-2">
                      <span class="badge bg-info">
                        <IconPhoto :size="16" class="me-1" />

                        {{ artFiles[interaction.id].name }}
                      </span>
                      <button
                        type="button"
                        class="btn btn-sm btn-link text-danger p-0 ms-2"
                        @click="clearArtFile(interaction.id)"
                        :disabled="uploadingArt[interaction.id]"
                      >
                        <IconX :size="18" />
                      </button>
                    </div>
                  </div>
                  <div class="mb-3">
                    <label :for="'art-comment-' + interaction.id" class="form-label">
                      Comentário
                      <span class="text-danger">*</span>
                    </label>
                    <textarea
                      :id="'art-comment-' + interaction.id"
                      v-model="artComments[interaction.id]"
                      class="form-control"
                      rows="3"
                      placeholder="Adicione um comentário sobre a arte..."
                      :disabled="uploadingArt[interaction.id]"
                    ></textarea>
                    <input type="hidden" v-model="interaction.designer_id" />
                  </div>
                  <div class="d-flex justify-content-end">
                    <button
                      type="submit"
                      class="btn btn-primary"
                      :disabled="
                        !artComments[interaction.id] ||
                        !artComments[interaction.id].trim() ||
                        uploadingArt[interaction.id]
                      "
                    >
                      <span
                        v-if="uploadingArt[interaction.id]"
                        class="spinner-border spinner-border-sm me-2"
                        role="status"
                        aria-hidden="true"
                      ></span>
                      {{ uploadingArt[interaction.id] ? 'Enviando...' : 'Enviar Resposta' }}
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import {
  IconCalendar,
  IconCornerLeftUp,
  IconExternalLink,
  IconLibraryPhoto,
  IconMessages,
  IconPhoto,
  IconUser,
  IconUserStar,
  IconX,
} from '@tabler/icons-vue';
import { useToast } from '@/composables/useToast';
import { useFormatting } from '@/composables/useFormatting';
import { useAuthStore } from '@/stores/auth';
import { requestArtService } from '@/services/requestArtService';

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
  isOrder: {
    type: Boolean,
    default: false,
  },
});

const toast = useToast();
const auth = useAuthStore();
const { formatNumber, formatDate, resolveImageUrl } = useFormatting();

const requestLayoutArts = ref([]);
const loadingRequestArts = ref(false);
const artFiles = ref({});
const artComments = ref({});
const uploadingArt = ref({});
const updatingApprovalStatus = ref({});

const isReseller = computed(() => {
  return (
    auth.hasRole(['reseller']) ||
    auth.roles?.some(
      (role) => typeof role === 'string' && role.toLowerCase().includes('revendedor'),
    )
  );
});

function handleImageError(event) {
  event.target.style.display = 'none';
}

function handleArtFileChange(event, interactionId) {
  const file = event.target.files[0];
  if (file) {
    artFiles.value[interactionId] = file;
  }
}

function clearArtFile(interactionId) {
  delete artFiles.value[interactionId];
  const input = document.getElementById(`art-file-${interactionId}`);
  if (input) {
    input.value = '';
  }
}

function normalizeToInteractions(dataArray) {
  return dataArray.map((art) => {
    let imageUrl = art.image_url;
    if (!imageUrl && art.path_file) {
      imageUrl = resolveImageUrl(art.path_file);
    }
    const designerName = art.designer?.name ?? art.designer_name ?? null;
    const dealerName = art.dealer?.name ?? art.dealer_name ?? null;
    const normalized = {
      id: art.id,
      order_id: art.order_id ?? null,
      order_budget_id: art.order_budget_id ?? null,
      dealer_id: art.dealer_id ?? art.dealer?.id ?? null,
      designer_id: art.designer_id ?? art.designer?.id ?? null,
      comment: art.comment ?? null,
      approval_status: art.approval_status ?? 'pending',
      path_file: art.path_file ?? null,
      image_url: imageUrl,
      created_at: art.created_at ?? null,
      designer_name: designerName,
      dealer_name: dealerName,
      wall_info: art.wall_info ?? null,
      wall_name: art.wall_name ?? art.wall_info?.wall_name ?? null,
      card_id: art.card_id ?? art.order_budget_id ?? null,
    };
    const arts =
      Array.isArray(art.arts) && art.arts.length > 0
        ? art.arts.map((a) => ({
            ...a,
            approval_status: a.approval_status ?? 'pending',
            designer_name: a.designer?.name ?? a.designer_name ?? designerName,
            dealer_name: a.dealer?.name ?? a.dealer_name ?? dealerName,
          }))
        : [normalized];
    const arts_count = art.arts_count ?? art.arts?.length ?? arts.length;
    return { ...normalized, arts_count, arts };
  });
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

function hasAnotherApprovedArt(interaction, currentArtId) {
  if (!interaction?.arts || !Array.isArray(interaction.arts)) return false;

  return interaction.arts.some((item) => {
    if (!item?.id || item.id === currentArtId) return false;
    return (item.approval_status || '').toLowerCase() === 'approved';
  });
}

async function updateApprovalStatus(art, status) {
  if (!art?.id) return;

  updatingApprovalStatus.value[art.id] = status;
  try {
    const response = await requestArtService.updateArtApprovalStatus({
      request_layout_art_id: art.id,
      approval_status: status,
    });

    art.approval_status = response?.approval_status ?? status;

    toast.success(response?.message || 'Status atualizado com sucesso.');
  } catch (error) {
    const errorMessage =
      error?.response?.data?.message ?? 'Não foi possível atualizar o status da iteração.';
    window.Swal.fire({
      title: 'Erro',
      text: errorMessage,
      icon: 'error',
      showCloseButton: true,
      confirmButtonText: 'OK',
    });
  } finally {
    delete updatingApprovalStatus.value[art.id];
  }
}

async function fetchRequestLayoutArts() {
  if (!props.data || !auth.user?.id) {
    requestLayoutArts.value = [];
    loadingRequestArts.value = false;
    return;
  }
  let params = {};
  if (props.isOrder) {
    const orderId = props.data.id;
    if (!orderId) {
      requestLayoutArts.value = [];
      return;
    }
    params = { order_id: orderId };
  } else {
    const budgetId = props.data.id;
    if (!budgetId) {
      requestLayoutArts.value = [];
      return;
    }
    params = { budget_id: budgetId, dealer_id: auth.user.id };
  }
  try {
    loadingRequestArts.value = true;
    const data = await requestArtService.getRequestLayoutArts(params);
    const dataArray = Array.isArray(data) ? data : (data?.data ?? []);
    if (!Array.isArray(dataArray)) {
      requestLayoutArts.value = [];
      return;
    }
    requestLayoutArts.value = normalizeToInteractions(dataArray);
  } catch (error) {
    console.error('Erro ao buscar solicitações de artes:', error);
    requestLayoutArts.value = [];
  } finally {
    loadingRequestArts.value = false;
  }
}

async function handleRespondToInteraction(interaction) {
  if (!interaction?.card_id || !props.data || !auth.user?.id) {
    window.Swal.fire({
      title: 'Erro',
      text: 'Dados insuficientes para responder a interação.',
      icon: 'error',
      showCloseButton: true,
      confirmButtonText: 'OK',
    });
    return;
  }
  const interactionId = interaction.id;
  const artFile = artFiles.value[interactionId];
  const comment = (artComments.value[interactionId] || '').trim();
  if (!comment) {
    window.Swal.fire({
      title: 'Atenção',
      text: 'Por favor, insira um comentário para enviar a resposta.',
      icon: 'warning',
      showCloseButton: true,
      confirmButtonText: 'OK',
    });
    return;
  }
  const orderId = props.isOrder ? props.data.id : (props.data.order_id ?? props.data.id);
  const formData = new FormData();
  if (artFile) formData.append('art_file', artFile);
  formData.append('order_budget_id', interaction.card_id);
  formData.append('dealer_id', auth.user.id);
  formData.append('designer_id', auth.user.id);
  formData.append('order_id', orderId);
  formData.append('comment', comment);

  uploadingArt.value[interactionId] = true;
  try {
    const response = await requestArtService.uploadArt(formData);
    if (response.data?.id) {
      clearArtFile(interactionId);
      artComments.value[interactionId] = '';
      await fetchRequestLayoutArts();

      toast.sucess(response.data.message || 'Resposta enviada com sucesso.');
    } else {
      throw new Error(response.data?.message || 'Erro ao enviar resposta');
    }
  } catch (error) {
    console.error('Erro ao responder interação:', error);
    const errorMessage =
      error?.response?.data?.message ??
      error?.message ??
      'Não foi possível enviar a arte. Tente novamente.';
    window.Swal.fire({
      title: 'Erro',
      text: errorMessage,
      icon: 'error',
      showCloseButton: true,
      confirmButtonText: 'OK',
    });
  } finally {
    uploadingArt.value[interactionId] = false;
  }
}

onMounted(() => {
  if (props.data && auth.user?.id) {
    fetchRequestLayoutArts();
  }
});

watch(
  () => props.data?.id,
  (newId, oldId) => {
    if (newId && newId !== oldId && auth.user?.id) {
      fetchRequestLayoutArts();
    }
  },
);
</script>

<style scoped>
.accordion-button {
  font-weight: 500;
}

.img-request {
  max-width: 100%;
  max-height: 400px;
  object-fit: contain;
  border-radius: 5px;
}
</style>
