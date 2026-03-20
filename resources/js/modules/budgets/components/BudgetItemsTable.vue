<template>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col" style="width: 64px;">Número</th>
                    <th scope="col" style="width: 64px;">Data</th>
                    <th class="text-nowrap" scope="col">Orçamento</th>
                    <th class="text-nowrap" scope="col">Valor da venda</th>
                    <th class="text-nowrap" scope="col">Situação</th>
                    <th class="text-nowrap" scope="col" style="width: 64px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="budget in budgets" :key="budget.id">
                    <th scope="row">{{ budget.id }}</th>
                    <td>{{ formatDate(budget.created_at || budget.createdAt) }}</td>
                    <td style="min-width: 240px;">
                        <button
                            class="btn btn-link text-decoration-none p-0 text-start fw-semibold"
                            @click="$emit('edit', budget)"
                        >
                            {{ budget.name }}
                        </button>
                    </td>
                    <td class="text-nowrap">
                        {{
                            markupInstallmentsValue(budget) != null
                                ? formatCurrency(markupInstallmentsValue(budget))
                                : '—'
                        }}
                    </td>
                    <td class="text-nowrap">
                        <BudgetStatusBadge :status="budget.status" />
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
                                        @click="$emit('view-details', budget)"
                                    >
                                        Ver detalhes
                                    </button>
                                </li>
                                <li>
                                    <button
                                        class="dropdown-item"
                                        type="button"
                                        @click="$emit('edit', budget)"
                                    >
                                        Editar
                                    </button>
                                </li>
                                <li>
                                    <button
                                        class="dropdown-item"
                                        type="button"
                                        @click="$emit('generate-pdf', budget)"
                                    >
                                        Gerar PDF
                                    </button>
                                </li>
                                <li
                                    v-if="
                                        budget.status === null ||
                                        (budget.status &&
                                            budget.status.toString().toLowerCase() === 'em aberto')
                                    "
                                >
                                    <button
                                        class="dropdown-item"
                                        type="button"
                                        @click="$emit('create-order', budget)"
                                    >
                                        Fazer pedido
                                    </button>
                                </li>
                                <li v-if="!isCancelled(budget)">
                                    <button
                                        class="dropdown-item"
                                        type="button"
                                        @click="$emit('cancel', budget)"
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
                                        @click="$emit('delete', budget)"
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
import { formatDate } from '@/utils/dateUtils';
import { useFormatting } from '@/composables/useFormatting';
import BudgetStatusBadge from './BudgetStatusBadge.vue';

const { formatCurrency } = useFormatting();

const props = defineProps({
    budgets: {
        type: Array,
        required: true,
    },
});

defineEmits(['view-details', 'generate-pdf', 'create-order', 'edit', 'cancel', 'delete']);

function isCancelled(budget) {
    const status = (budget?.status ?? '').toString().toLowerCase();
    return status === 'cancelled' || status === 'cancelado';
}

function markupInstallmentsValue(budget) {
    const raw =
        budget?.total_amount_installments_markup ??
        budget?.totalAmountInstallmentsMarkup;
    if (raw === null || raw === undefined || raw === '') {
        return null;
    }
    const num = Number(raw);
    return Number.isFinite(num) ? num : null;
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

