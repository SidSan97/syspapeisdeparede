<template>
    <div class="card mb-4">
        <div class="card-header bg-transparent">
            <h5 class="mb-0 fw-semibold">Cômodos</h5>
        </div>
        <div class="card-body">
            <div v-if="!data.rooms || data.rooms.length === 0" class="text-center text-muted py-4">
                Nenhum ambiente cadastrado
            </div>
            <template v-else>
                <div v-for="(room, roomIndex) in data.rooms" :key="roomIndex" class="card mb-3">
                    <div class="card-header">
                        <strong>{{ room.name || `Ambiente ${roomIndex + 1}` }}</strong>
                    </div>
                    <div class="card-body">
                        <div v-if="!room.walls || room.walls.length === 0" class="text-muted small">
                            Nenhuma parede cadastrada
                        </div>
                        <template v-else>
                            <div v-for="(wall, wallIndex) in room.walls" :key="wallIndex" class="card mb-3 border">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <strong>{{ wall.name || `Parede ${wallIndex + 1}` }}</strong>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="text-muted small">Largura (m)</div>
                                            <div class="fw-semibold">{{ formatNumber(wall.width) }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-muted small">Altura (m)</div>
                                            <div class="fw-semibold">{{ formatNumber(wall.height) }}</div>
                                        </div>
                                    </div>

                                    <!-- Modelo selecionado -->
                                    <div v-if="wall.collection_model || wall.collection_model_name" class="mb-3">
                                        <div class="text-muted small">Modelo</div>
                                        <div class="fw-semibold">
                                            {{ wall.collection_model?.name || wall.collection_model_name || '-' }}
                                        </div>
                                    </div>

                                    <!-- Referências do Modelo por Parede -->
                                    <div v-if="hasWallModelReferences(wall, roomIndex, wallIndex)" class="mb-3 pt-3 border-top">
                                        <div class="text-muted small mb-2 fw-semibold">Referências do Modelo</div>

                                        <div v-if="getWallModelReference(wall, roomIndex, wallIndex, 'comment')" class="mb-2">
                                            <div class="text-muted small mb-1">Descrição</div>
                                            <div class="p-2 rounded border">{{ getWallModelReference(wall, roomIndex, wallIndex, 'comment') }}</div>
                                        </div>

                                        <div v-if="getWallModelReference(wall, roomIndex, wallIndex, 'link')" class="mb-2">
                                            <div class="text-muted small mb-1">Link de Referência</div>
                                            <div>
                                                <a
                                                    :href="getWallModelReference(wall, roomIndex, wallIndex, 'link')"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="text-break"
                                                >
                                                    {{ getWallModelReference(wall, roomIndex, wallIndex, 'link') }}
                                                </a>
                                            </div>
                                        </div>

                                        <div v-if="getWallModelReference(wall, roomIndex, wallIndex, 'files')?.length" class="mb-0">
                                            <div class="text-muted small mb-2">Arquivos de Referência</div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <a
                                                    v-for="(file, fileIndex) in getWallModelReference(wall, roomIndex, wallIndex, 'files')"
                                                    :key="fileIndex"
                                                    :href="resolveStorageUrl(file)"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="btn btn-sm btn-outline-secondary"
                                                >
                                                    <i class="fa fa-file-image me-1"></i>
                                                    {{ extractFileName(file) }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Continuações -->
                                    <div v-if="wall.continuations && wall.continuations.length > 0" class="mb-3">
                                        <div class="text-muted small mb-2">Continuações</div>
                                        <div v-for="(continuation, contIndex) in wall.continuations" :key="contIndex" class="border-start border-primary ps-3 ms-2 mb-2">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="text-muted small">Direção</div>
                                                    <div class="fw-semibold">{{ formatDirection(continuation.direction) }}</div>
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

                                    <!-- Cálculos da parede -->
                                    <div v-if="wall.total_area" class="alert alert-success mb-0">
                                        <strong>Metros:</strong> {{ formatNumber(wall.total_area) }} m
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useFormatting } from '@/composables/useFormatting';

const props = defineProps({
    data: {
        type: Object,
        required: true,
    },
});

const { formatNumber, formatDirection, resolveStorageUrl, extractFileName } = useFormatting();

/**
 * Verifica se uma parede tem referências do modelo
 */
function hasWallModelReferences(wall, roomIndex, wallIndex) {
    const hasGlobalReferences = props.data?.comment_referring_model ||
                                props.data?.link_referring_model ||
                                (props.data?.files_referring_model && props.data.files_referring_model.length > 0);

    return hasGlobalReferences && (wall.collection_model || wall.collection_model_name);
}

/**
 * Obtém a referência do modelo para uma parede específica
 */
function getWallModelReference(wall, roomIndex, wallIndex, type) {
    if (!hasWallModelReferences(wall, roomIndex, wallIndex)) {
        return null;
    }

    switch (type) {
        case 'comment':
            return props.data?.comment_referring_model || null;
        case 'link':
            return props.data?.link_referring_model || null;
        case 'files':
            return Array.isArray(props.data?.files_referring_model)
                ? props.data.files_referring_model
                : [];
        default:
            return null;
    }
}
</script>

