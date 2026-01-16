<template>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col" style="width: 64px;">Número</th>
                    <th scope="col" style="width: 64px;">Data</th>
                    <th class="text-nowrap" scope="col">Pedido</th>
                    <th class="text-nowrap" scope="col" style="width: 120px;">Valor total</th>
                    <th class="text-nowrap" scope="col">Situação</th>
                    <th class="text-nowrap" scope="col" style="width: 64px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="pedido in pedidos" :key="pedido.id">
                    <th scope="row">{{ pedido.id }}</th>
                    <td>{{ formatDate(pedido.created_at || pedido.createdAt) }}</td>
                    <td style="min-width: 240px;">
                        <button
                            class="btn btn-link text-decoration-none p-0 text-start fw-semibold"
                            @click="$emit('view-details', pedido)"
                        >
                            {{ pedido.name }}
                        </button>
                    </td>
                    <td class="">
                        <span class="fw-semibold">{{ formatCurrency(pedido.total_amount) }}</span>
                    </td>
                    <td>
                        <OrderStatusBadge :status="pedido.status" />
                    </td>
                    <td>
                        <div class="dropdown">
                            <button
                                class="btn btn-subtle btn-sm"
                                type="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                <i class="fa fa-ellipsis-h"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button
                                        class="dropdown-item"
                                        type="button"
                                        @click="$emit('view-details', pedido)"
                                    >
                                        Ver detalhes
                                    </button>
                                </li>
                                <li v-if="pedido.status !== 'Aprovado' && canRegisterPayment">
                                    <button
                                        class="dropdown-item"
                                        type="button"
                                        @click="$emit('register-payment', pedido)"
                                    >
                                        Registrar pagamento
                                    </button>
                                </li>
                                <li>
                                    <button
                                        class="dropdown-item"
                                        type="button"
                                        @click="$emit('edit', pedido)"
                                    >
                                        Editar
                                    </button>
                                </li>
                                <li v-if="!isCancelled(pedido)">
                                    <button
                                        class="dropdown-item text-danger"
                                        type="button"
                                        @click="$emit('cancel', pedido)"
                                    >
                                        Cancelar
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { formatDate } from '@/utils/dateUtils';
import OrderStatusBadge from './OrderStatusBadge.vue';

const props = defineProps({
    pedidos: {
        type: Array,
        required: true,
    },
    canRegisterPayment: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['view-details', 'register-payment', 'edit', 'cancel']);

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

function formatCurrency(value) {
    if (value === null || value === undefined) {
        return currencyFormatter.format(0);
    }

    const numericValue = Number(value);
    return currencyFormatter.format(Number.isFinite(numericValue) ? numericValue : 0);
}

function isCancelled(pedido) {
    const status = (pedido?.status ?? '').toString().toLowerCase();
    return status === 'cancelled' || status === 'cancelado';
}
</script>

<style scoped>
.btn-link {
    color: var(--bs-body-color);
}

.btn-link:hover {
    color: var(--bs-primary);
}
</style>
