<template>
  <div class="card mb-4">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
      <h5 class="mb-0 fw-semibold">Cômodos e solicitações de arte</h5>
      <span v-if="!loadingRequestArts && totalRequestArts > 0" class="badge bg-secondary">
        {{ totalRequestArts }} solicitação(ões)
      </span>
    </div>

    <div class="card-body">
      <div v-if="loadingRequestArts" class="text-center text-muted py-3">
        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
        Carregando solicitações de artes...
      </div>

      <div v-else-if="!rooms.length" class="text-center text-muted py-4">
        Nenhum ambiente cadastrado
      </div>

      <template v-else>
        <div v-for="room in rooms" :key="room.id ?? room.roomIndex" class="card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <strong>{{ room.name || `Ambiente ${room.roomIndex + 1}` }}</strong>
            <span v-if="room.requestArtsCount" class="badge bg-info">
              {{ room.requestArtsCount }} arte(s)
            </span>
          </div>

          <div class="card-body">
            <div v-if="!room.walls?.length" class="text-muted small">Nenhuma parede cadastrada</div>

            <div
              v-for="wall in room.walls"
              :key="wall.id ?? `${room.roomIndex}-${wall.wallIndex}`"
              class="card mb-3 border"
            >
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <strong>{{ wall.name || `Parede ${wall.wallIndex + 1}` }}</strong>
                  <span v-if="wall.requestArts?.length" class="badge bg-secondary">
                    {{ wall.requestArts.length }} interação(ões)
                  </span>
                </div>

                <div class="row mb-3">
                  <div v-if="wall.direction" class="col-md-4">
                    <div class="text-muted small">Direção</div>
                    <div class="fw-semibold">{{ formatDirection(wall.direction) }}</div>
                  </div>
                  <div class="col-md-4">
                    <div class="text-muted small">Largura (m)</div>
                    <div class="fw-semibold">{{ formatNumber(wall.width) }}</div>
                  </div>
                  <div class="col-md-4">
                    <div class="text-muted small">Altura (m)</div>
                    <div class="fw-semibold">{{ formatNumber(wall.height) }}</div>
                  </div>
                </div>

                <div v-if="wall.continuations?.length" class="mb-3">
                  <div class="text-muted small mb-2">Continuações</div>
                  <div
                    v-for="(continuation, contIndex) in wall.continuations"
                    :key="contIndex"
                    class="border-start border-primary ps-3 ms-2 mb-2"
                  >
                    <div class="row">
                      <div v-if="continuation.name" class="col-md-4">
                        <div class="text-muted small">Nome</div>
                        <div class="fw-semibold">{{ continuation.name }}</div>
                      </div>
                      <div v-if="continuationFitLabel(continuation)" class="col-md-4">
                        <div class="text-muted small">Encaixe</div>
                        <div class="fw-semibold">{{ continuationFitLabel(continuation) }}</div>
                      </div>
                      <div class="col-md-4">
                        <div class="text-muted small">Largura (m)</div>
                        <div class="fw-semibold">{{ formatNumber(continuation.width) }}</div>
                      </div>
                      <div class="col-md-4">
                        <div class="text-muted small">Altura (m)</div>
                        <div class="fw-semibold">{{ formatNumber(continuation.height) }}</div>
                      </div>
                    </div>
                  </div>
                </div>

                <SelectedModelsCard :wall="wall" />

                <div v-if="wall.total_area" class="alert alert-success mb-0 mt-3">
                  <strong>Metros:</strong>
                  {{ formatNumber(wall.total_area) }} m
                </div>

                <WallRequestArtsList
                  v-if="wall.requestArts?.length"
                  :arts="wall.requestArts"
                  :can-approve="isReseller"
                  :updating-approval-status="updatingApprovalStatus"
                  :accordion-suffix="wall.id ?? `${room.roomIndex}-${wall.wallIndex}`"
                  @update-approval="handleUpdateApproval"
                />
                <div v-else class="mt-3 text-muted small">
                  Nenhuma solicitação de arte para esta parede.
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="unmatchedRequestArts.length" class="card border-warning">
          <div class="card-header bg-transparent">
            <strong>Outras solicitações</strong>
            <span class="text-muted small ms-2">(sem parede correspondente)</span>
          </div>
          <div class="card-body">
            <WallRequestArtsList
              :arts="unmatchedRequestArts"
              :can-approve="isReseller"
              :updating-approval-status="updatingApprovalStatus"
              accordion-suffix="unmatched"
              @update-approval="handleUpdateApproval"
            />
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';

import SelectedModelsCard from '@/components/details/SelectedModelsCard.vue';
import WallRequestArtsList from '@/components/budgets/WallRequestArtsList.vue';
import { useAuthStore } from '@/stores/auth';
import { useFormatting } from '@/composables/useFormatting';
import { useToast } from '@/composables/useToast';
import { buildRoomsWithRequestArts } from '@/modules/budgets/composables/useRoomsWithRequestArts';
import { requestArtService } from '@/services/requestArtService';

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
});

const auth = useAuthStore();
const toast = useToast();
const { formatNumber, formatDirection, resolveImageUrl } = useFormatting();

const rawArts = ref([]);
const loadingRequestArts = ref(false);
const updatingApprovalStatus = ref({});

const isReseller = computed(() => {
  return (
    auth.hasRole(['reseller']) ||
    auth.roles?.some(
      (role) => typeof role === 'string' && role.toLowerCase().includes('revendedor'),
    )
  );
});

const grouped = computed(() =>
  buildRoomsWithRequestArts(props.data, rawArts.value, resolveImageUrl),
);

const rooms = computed(() => grouped.value.rooms);
const unmatchedRequestArts = computed(() => grouped.value.unmatchedRequestArts);
const totalRequestArts = computed(() => grouped.value.totalRequestArts);

function continuationFitLabel(continuation) {
  const raw = continuation?.fit ?? continuation?.Fit;
  if (raw == null) {
    return '';
  }
  const value = String(raw).trim();
  return value.length > 0 ? value : '';
}

async function fetchRequestLayoutArts() {
  if (!props.data?.id || !auth.user?.id) {
    rawArts.value = [];
    loadingRequestArts.value = false;
    return;
  }

  loadingRequestArts.value = true;
  try {
    const data = await requestArtService.getRequestLayoutArts({
      budget_id: props.data.id,
      dealer_id: auth.user.id,
    });
    const dataArray = Array.isArray(data) ? data : (data?.data ?? []);
    rawArts.value = Array.isArray(dataArray) ? dataArray : [];
  } catch (error) {
    console.error('Erro ao buscar solicitações de artes:', error);
    rawArts.value = [];
  } finally {
    loadingRequestArts.value = false;
  }
}

async function handleUpdateApproval({ art, status }) {
  if (!art?.id) {
    return;
  }

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

onMounted(() => {
  if (props.data?.id && auth.user?.id) {
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
