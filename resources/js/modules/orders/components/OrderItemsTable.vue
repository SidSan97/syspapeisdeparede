<template>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col" class="text-center" style="width: 40px;">
                        <span class="visually-hidden">Selecionar todos os pedidos da página</span>
                        <input
                            ref="selectAllCheckboxRef"
                            type="checkbox"
                            class="form-check-input"
                            :checked="allSelectedOnPage"
                            aria-label="Selecionar todos os pedidos da página"
                            @change="onToggleAll($event)"
                        />
                    </th>
                    <th scope="col" style="width: 64px;">Número</th>
                    <th scope="col" style="width: 64px;">Data</th>
                    <th class="text-nowrap" scope="col">Pedido</th>
                    <th v-if="showValuesColumn" class="text-nowrap" scope="col" style="width: 120px;">Valor total</th>
                    <th class="text-nowrap" scope="col">Situação</th>
                    <th v-if="showActionsColumn" class="text-nowrap" scope="col" style="width: 64px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="order in orders" :key="order.id">
                    <td class="text-center align-middle">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            :checked="isOrderSelected(order.id)"
                            :aria-label="`Selecionar pedido ${order.id}`"
                            @change="onRowToggle(order.id, $event)"
                        />
                    </td>
                    <th scope="row">{{ order.id }}</th>
                    <td>{{ formatDate(order.created_at || order.createdAt) }}</td>
                    <td style="min-width: 240px;">
                        <button
                            v-if="nameClickable"
                            class="btn btn-link text-decoration-none p-0 text-start fw-semibold"
                            @click="$emit('view-details', order)"
                        >
                            {{ order.name }}
                        </button>
                        <span v-else class="fw-semibold">{{ order.name }}</span>
                    </td>
                    <td v-if="showValuesColumn" class="">
                        <span class="fw-semibold">{{ formatCurrency(order.total_amount) }}</span>
                    </td>
                    <td class="text-nowrap">
                        <OrderStatusBadge :status="order.status" />
                    </td>
                    <td v-if="showActionsColumn">
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
                                        @click="$emit('view-details', order)"
                                    >
                                        Ver detalhes
                                    </button>
                                </li>
                                <li v-if="order.status !== 'Aprovado' && canRegisterPayment && order.paid === 0">
                                    <button
                                        class="dropdown-item"
                                        type="button"
                                        @click="$emit('register-payment', order)"
                                    >
                                        Registrar pagamento
                                    </button>
                                </li>
                                <li>
                                    <button
                                        class="dropdown-item"
                                        type="button"
                                        @click="$emit('edit', order)"
                                    >
                                        Editar
                                    </button>
                                </li>
                                <li v-if="order.nf_sent === 1">
                                    <button
                                        class="dropdown-item"
                                        type="button"
                                        @click="$emit('view-invoice', order)"
                                    >
                                        Ver nota fiscal
                                    </button>
                                </li>
                                <li v-if="!isCancelled(order)">
                                    <button
                                        class="dropdown-item"
                                        type="button"
                                        @click="$emit('cancel', order)"
                                    >
                                        Cancelar
                                    </button>
                                </li>
                                <li>
                                    <hr class="dropdown-divider" />
                                </li>
                                <li>
                                    <button
                                        class="dropdown-item text-danger"
                                        type="button"
                                        @click="$emit('delete', order)"
                                    >
                                        Excluir
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
import { ref, computed, watch, nextTick } from 'vue';
import { formatDate } from '@/utils/dateUtils';
import OrderStatusBadge from './OrderStatusBadge.vue';

const props = defineProps({
    orders: {
        type: Array,
        required: true,
    },
    canRegisterPayment: {
        type: Boolean,
        default: false,
    },
    /** Exibir coluna "Valor total". Ocultar para perfil comercial. */
    showValuesColumn: {
        type: Boolean,
        default: true,
    },
    /** Exibir coluna "Ações" (dropdown). Ocultar para perfil comercial. */
    showActionsColumn: {
        type: Boolean,
        default: true,
    },
    /** Nome do pedido clicável (Ver detalhes). False para perfil comercial. */
    nameClickable: {
        type: Boolean,
        default: true,
    },
    selectedOrderIds: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits([
    'view-details',
    'register-payment',
    'edit',
    'cancel',
    'delete',
    'view-invoice',
    'update:selectedOrderIds',
]);

const selectAllCheckboxRef = ref(null);

const orderIdsOnPage = computed(() => props.orders.map((o) => o.id));

const selectedSet = computed(() => new Set(props.selectedOrderIds ?? []));

const allSelectedOnPage = computed(() => {
    const ids = orderIdsOnPage.value;
    if (ids.length === 0) {
        return false;
    }
    const set = selectedSet.value;
    return ids.every((id) => set.has(id));
});

const someSelectedOnPage = computed(() => {
    const ids = orderIdsOnPage.value;
    if (ids.length === 0) {
        return false;
    }
    const set = selectedSet.value;
    const n = ids.filter((id) => set.has(id)).length;
    return n > 0 && n < ids.length;
});

function syncSelectAllIndeterminate() {
    nextTick(() => {
        const el = selectAllCheckboxRef.value;
        if (!el) {
            return;
        }
        el.indeterminate = someSelectedOnPage.value && !allSelectedOnPage.value;
    });
}

watch(
    [allSelectedOnPage, someSelectedOnPage, orderIdsOnPage],
    () => syncSelectAllIndeterminate(),
    { flush: 'post' }
);

function emitSelection(nextSet) {
    emit('update:selectedOrderIds', [...nextSet]);
}

function onToggleAll(event) {
    const checked = event.target.checked;
    const ids = orderIdsOnPage.value;
    const next = new Set(selectedSet.value);
    if (checked) {
        ids.forEach((id) => next.add(id));
    } else {
        ids.forEach((id) => next.delete(id));
    }
    emitSelection(next);
    syncSelectAllIndeterminate();
}

function onRowToggle(orderId, event) {
    const checked = event.target.checked;
    const next = new Set(selectedSet.value);
    if (checked) {
        next.add(orderId);
    } else {
        next.delete(orderId);
    }
    emitSelection(next);
}

function isOrderSelected(orderId) {
    return selectedSet.value.has(orderId);
}

watch(
    () => props.orders.map((o) => o.id),
    (ids) => {
        const valid = new Set(ids);
        const current = props.selectedOrderIds ?? [];
        const next = new Set(current.filter((id) => valid.has(id)));
        if (next.size !== current.length) {
            emitSelection(next);
        }
    }
);

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

function isCancelled(order) {
    const status = (order?.status ?? '').toString().toLowerCase();
    return status == 'cancelado' || status == 'Cancelado';
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

