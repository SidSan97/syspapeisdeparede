<template>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col" style="width: 64px;">Número</th>
                    <th scope="col" style="width: 64px;">Data</th>
                    <th class="text-nowrap" scope="col">Pedido</th>
                    <th class="text-nowrap" scope="col">Situação</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="order in orders" :key="order.id">
                    <th scope="row">{{ order.id }}</th>
                    <td>{{ formatDate(order.created_at || order.createdAt) }}</td>
                    <td style="min-width: 240px;">
                        <button
                            class="btn btn-link text-decoration-none p-0 text-start fw-semibold"
                            @click="$emit('view-order', order)"
                        >
                            {{ order.name }}
                        </button>
                    </td>
                    <td>
                        <span class="status-dot" :class="`status-dot-${getStatusVariant(order.status)}`"></span>
                        {{ formatStatusLabel(order.status) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { formatDate } from '@/utils/dateUtils';

const props = defineProps({
    orders: {
        type: Array,
        required: true,
    },
    statusOptions: {
        type: Array,
        required: true,
    },
});

defineEmits(['view-order']);

function formatStatusLabel(status) {
    if (!status) {
        return '—';
    }
    const normalized = status.toString().toLowerCase();
    const match = props.statusOptions.find((option) => option.value === normalized);
    if (match) {
        return match.label;
    }
    return status;
}

function getStatusVariant(status) {
    const normalized = (status || '').toString().toLowerCase();
    if (normalized.includes('cancel')) {
        return 'danger';
    }
    if (normalized.includes('aprov')) {
        return 'success';
    }
    return 'info';
}
</script>

<style scoped>
.btn-link {
    color: var(--bs-body-color);
}

.btn-link:hover {
    color: var(--bs-primary);
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
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
