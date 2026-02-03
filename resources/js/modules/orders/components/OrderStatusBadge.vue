<template>
    <span class="status-dot" :class="`status-dot-${variant}`"></span>
    {{ label }}
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        default: null,
    },
});

const statusOptions = [
    { label: 'Em aberto', value: 'em aberto' },
    { label: 'Aprovado', value: 'aprovado' },
    { label: 'Aprovar layout', value: 'aprovar layout' },
    { label: 'Pendente de revisão', value: 'pendente de revisão' },
    { label: 'Cancelado', value: 'cancelado' },
];

const label = computed(() => {
    if (!props.status) {
        return '—';
    }
    const normalized = props.status.toString().toLowerCase();
    const match = statusOptions.find((option) => option.value === normalized);
    return match ? match.label : props.status;
});

const variant = computed(() => {
    const normalized = (props.status || '').toString().toLowerCase();
    if (normalized.includes('cancel')) {
        return 'danger';
    }
    if (normalized.includes('aprov')) {
        return 'success';
    }
    return 'info';
});
</script>

<style scoped>
.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
    margin-right: 6px;
}

.status-dot-success {
    background-color: var(--bs-success);
}

.status-dot-danger {
    background-color: var(--bs-danger);
}

.status-dot-info {
    background-color: var(--bs-info);
}
</style>

