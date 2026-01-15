<template>
    <div v-if="hasModelReferences" class="card mb-4">
        <div class="card-header bg-transparent">
            <h5 class="mb-0 fw-semibold">Referências do Modelo</h5>
        </div>
        <div class="card-body">
            <div v-if="data.comment_referring_model" class="mb-3">
                <div class="text-muted small mb-1">Descrição</div>
                <div class="p-2 rounded border">{{ data.comment_referring_model }}</div>
            </div>
            <div v-if="data.link_referring_model" class="mb-3">
                <div class="text-muted small mb-1">Link de Referência</div>
                <div>
                    <a :href="data.link_referring_model" target="_blank" rel="noopener noreferrer" class="text-break">
                        {{ data.link_referring_model }}
                    </a>
                </div>
            </div>
            <div v-if="data.files_referring_model && data.files_referring_model.length > 0" class="mb-0">
                <div class="text-muted small mb-2">Arquivos de Referência</div>
                <div class="d-flex flex-wrap gap-2">
                    <a
                        v-for="(file, index) in data.files_referring_model"
                        :key="index"
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

const { resolveStorageUrl, extractFileName } = useFormatting();

const hasModelReferences = computed(() => {
    return props.data?.comment_referring_model ||
           props.data?.link_referring_model ||
           (props.data?.files_referring_model && props.data.files_referring_model.length > 0);
});
</script>

