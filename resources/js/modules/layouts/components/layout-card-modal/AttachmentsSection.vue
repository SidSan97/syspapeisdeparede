<template>
    <div
        v-if="attachments && attachments.length > 0"
        class="attachments-section"
    >
        <h3
            class="d-flex align-items-center gap-2 text-body mb-3 attachments-section-title"
        >
            <IconPaperclip class="text-primary" />

            Anexos
        </h3>
        <div class="d-flex flex-column gap-3">
            <div
                v-for="(file, fileIndex) in attachments"
                :key="fileIndex"
                class="d-flex gap-3 p-3 align-items-center attachments-section-item"
            >
                <div
                    class="overflow-hidden bg-body d-flex align-items-center justify-content-center attachments-section-item-preview"
                >
                    <img
                        v-if="isImageFile(file)"
                        class="w-100 h-100"
                        :src="getImageUrl(file)"
                        :alt="getAttachmentName(file, fileIndex)"
                    />
                    <IconFile v-else class="text-secondary" />
                </div>
                <div class="d-flex flex-column gap-2 flex-grow-1">
                    <div
                        class="fw-semibold text-body attachments-section-item-name"
                    >
                        {{ getAttachmentName(file, fileIndex) }}
                    </div>
                    <div class="text-secondary attachments-section-item-meta">
                        {{ formatDate(file.created_at) }}
                    </div>
                    <a
                        class="align-self-start text-decoration-none attachments-section-item-button"
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
</template>

<script setup>
import { IconFile, IconPaperclip } from '@tabler/icons-vue';

const props = defineProps({
    attachments: {
        type: Array,
        default: () => [],
    },
});

const activityDateFormatter = new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'medium',
    timeStyle: 'short',
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

function getAttachmentName(file, index = 0) {
    return (
        file?.name ||
        file?.original_name ||
        file?.file_name ||
        `Arquivo ${index + 1}`
    );
}

function isImageFile(file) {
    if (!file) {
        return false;
    }
    const mime = (file.mime || file.mimetype || '').toLowerCase();
    if (mime.startsWith('image/')) {
        return true;
    }
    const name = (
        file.name ||
        file.original_name ||
        file.file_name ||
        ''
    ).toLowerCase();
    return ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.bmp'].some((ext) =>
        name.endsWith(ext),
    );
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
</script>

<style lang="scss" scoped>
.attachments-section {
    margin-bottom: 24px;

    &:last-child {
        margin-bottom: 0;
    }
}

.attachments-section-title {
    font-size: 1.125rem;
    font-weight: 600;
}

.attachments-section-item {
    border-radius: 0.5rem;
    background-color: var(--bs-secondary-bg);
}

.attachments-section-item-preview {
    width: 64px;
    height: 64px;
    border-radius: 6px;

    img {
        object-fit: cover;
    }

    i {
        font-size: 1.5rem;
    }
}

.attachments-section-item-name {
    font-size: 14px;
}

.attachments-section-item-meta {
    font-size: 12px;
}

.attachments-section-item-button {
    padding: 0.375rem 0.75rem;
    background-color: var(--bs-dark, var(--bs-body-color));
    color: var(--bs-white, var(--bs-body-bg));
    border-radius: 0.25rem;
    font-size: 0.75rem;
    transition: background-color 0.2s ease;

    &:hover {
        opacity: 0.85;
    }
}
</style>
