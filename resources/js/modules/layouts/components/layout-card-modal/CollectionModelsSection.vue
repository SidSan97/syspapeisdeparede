<template>
    <div
        v-if="
            wall &&
            (wall.collection_model || hasWallModelReferringContent(wall))
        "
        class="collection-models-section"
    >
        <!-- Modelos selecionados -->
        <div
            v-if="wall.collection_model"
            class="collection-models-section-item"
        >
            <h3
                class="collection-models-section-title d-flex align-items-center gap-2 mb-1"
            >
                <IconCube />

                Modelos selecionados
            </h3>
            <div
                v-if="wall.collection_model.name"
                class="collection-models-section-name"
            >
                {{ wall.collection_model.name }}
            </div>
            <div v-else class="collection-models-section-empty text-muted">
                Nenhum modelo selecionado
            </div>
        </div>

        <WallModelReferringFields
            v-if="hasWallModelReferringContent(wall)"
            :wall="wall"
            class="collection-models-section-item"
        />

        <!-- Imagens da Parede Específica -->
        <div
            v-if="wall.collection_model"
            class="collection-models-section-item"
        >
            <h3
                class="collection-models-section-title d-flex align-items-center gap-2 mb-1"
            >
                <IconPhoto />

                Imagens da Parede
            </h3>
            <div
                v-if="
                    wall.collection_model.files &&
                    wall.collection_model.files.length > 0
                "
                class="collection-models-section-images"
            >
                <div
                    v-for="(file, fileIndex) in wall.collection_model.files"
                    :key="fileIndex"
                    class="collection-models-section-image"
                >
                    <img
                        :src="getImageUrl(file)"
                        :alt="file.name || 'Imagem da parede'"
                    />
                </div>
            </div>
            <div v-else class="collection-models-section-empty text-muted">
                Nenhuma imagem disponível para esta parede
            </div>
        </div>
    </div>
</template>

<script setup>
import WallModelReferringFields from '@/components/details/WallModelReferringFields.vue';
import { hasWallModelReferringContent } from '@/utils/wallModelReferringContent';
import { IconCube, IconPhoto } from '@tabler/icons-vue';

defineProps({
    wall: {
        type: Object,
        default: null,
    },
});

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
</script>

<style lang="scss" scoped>
.collection-models-section {
    margin-bottom: 24px;

    &:last-child {
        margin-bottom: 0;
    }
}

.collection-models-section-item {
    margin-bottom: 24px;

    &:last-child {
        margin-bottom: 0;
    }
}

.collection-models-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--bs-body-color);
}

.collection-models-section-name {
    font-size: 0.875rem;
    line-height: 1.5;
    color: var(--bs-body-color);
}

.collection-models-section-empty {
    font-size: 0.875rem;
    line-height: 1.5;
}

.collection-models-section-images {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.collection-models-section-image {
    width: 120px;
    height: 120px;
    border-radius: 0.25rem;
    overflow: hidden;
    background-color: var(--bs-card-bg);

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}
</style>
