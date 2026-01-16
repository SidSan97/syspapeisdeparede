<template>
    <div class="card mb-4">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Nome</label>
                <div class="fw-semibold fs-5">{{ data.name }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <div>
                    <span
                        class="badge text-white rounded-pill px-3 py-2"
                        :class="statusBadgeClass"
                    >
                        {{ upperCaseFirstLetter(data.status) || 'Sem status' }}
                    </span>
                </div>
            </div>
            <div v-if="isDropshippingEnabled" class="mb-3">
                <label class="form-label">Dropshipping</label>
                <div>
                    <span class="badge bg-primary text-white rounded-pill px-3 py-2">Habilitado</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: {
        type: Object,
        required: true,
    },
    isDropshippingEnabled: {
        type: Boolean,
        default: false,
    },
});

function upperCaseFirstLetter(string) {
    return string?.charAt(0).toUpperCase() + string?.slice(1);
}

const statusBadgeClass = computed(() => {
    const status = (props.data?.status || '').toString().toLowerCase();
    
    if (status.includes('cancelado') || status.includes('cancel')) {
        return 'bg-danger';
    }
    if (status.includes('aprovado') || status.includes('aprovar layout') || status.includes('liberado')) {
        return 'bg-success';
    }
    if (status.includes('pendente') || status.includes('revisão')) {
        return 'bg-warning';
    }
    if (status.includes('em aberto') || status.includes('aberto')) {
        return 'bg-info'; 
    }
    
    return 'bg-secondary'; 
});
</script>
