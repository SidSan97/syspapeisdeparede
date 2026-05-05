<template>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th scope="col" style="width: 64px">#</th>
          <th scope="col" style="width: 64px">Data</th>
          <th class="text-nowrap" scope="col">Orçamento</th>
          <th class="text-nowrap" scope="col">Custo do orçamento</th>
          <th class="text-nowrap" scope="col">Valor da venda</th>
          <th class="text-nowrap" scope="col">Situação</th>
          <th class="text-nowrap" scope="col" style="width: 64px">Ações</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td colspan="7" class="p-5 text-center text-muted fw-semibold">
            Carregando orçamentos...
          </td>
        </tr>
        <tr v-else-if="budgets.length === 0">
          <td colspan="7" class="p-5 text-center text-muted fw-semibold">
            Nenhum orçamento encontrado.
          </td>
        </tr>
        <tr v-for="budget in budgets" v-else :key="budget.id">
          <th scope="row">{{ budget.id }}</th>
          <td>
            {{ formatDate(budget.created_at || budget.createdAt) }}
          </td>
          <td style="min-width: 240px">
            <router-link
              v-if="canEditBudget(budget)"
              class="btn btn-link text-decoration-none p-0 text-start fw-semibold"
              :to="{
                name: 'budgets.edit',
                params: { id: budget.id },
              }"
            >
              {{ budget.name }}
            </router-link>
            <span v-else class="fw-semibold text-body" :title="editBlockedTitle">
              {{ budget.name }}
            </span>
          </td>
          <td class="text-nowrap">
            {{ budgetCostValue(budget) != null ? formatCurrency(budgetCostValue(budget)) : '—' }}
          </td>
          <td class="text-nowrap">
            {{ markupSaleValue(budget) != null ? formatCurrency(markupSaleValue(budget)) : '—' }}
          </td>
          <td class="text-nowrap">
            <BudgetStatusBadge :status="budget.status" />
          </td>
          <td>
            <BaseDropdown align="end">
              <template #trigger="{ open, toggle }"
                ><button
                  class="btn btn-subtle btn-sm"
                  type="button"
                  :class="{ show: open }"
                  :aria-expanded="open"
                  @click="toggle"
                >
                  <IconDotsVertical :size="18" />
                </button>
              </template>

              <li>
                <router-link
                  class="dropdown-item"
                  :to="{
                    name: 'budgets.show',
                    params: { id: budget.id },
                  }"
                >
                  Visualizar
                </router-link>
              </li>
              <li>
                <button class="dropdown-item" type="button" @click="$emit('duplicate', budget)">
                  Duplicar
                </button>
              </li>
              <li>
                <router-link
                  class="dropdown-item"
                  :class="{
                    disabled: !canEditBudget(budget),
                  }"
                  :to="{
                    name: 'budgets.edit',
                    params: { id: budget.id },
                  }"
                >
                  Editar
                </router-link>
              </li>
              <li>
                <router-link
                  class="dropdown-item"
                  :to="{
                    name: 'budgets.pdf-preview',
                    params: { id: budget.id },
                  }"
                >
                  Imprimir
                </router-link>
              </li>
              <li
                v-if="
                  budget.status === null ||
                  (budget.status && budget.status.toString().toLowerCase() === 'em aberto')
                "
              >
                <button class="dropdown-item" type="button" @click="$emit('create-order', budget)">
                  Criar pedido
                </button>
              </li>
              <li v-if="!isCancelled(budget)">
                <button class="dropdown-item" type="button" @click="$emit('cancel', budget)">
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
            </BaseDropdown>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { IconDotsVertical } from '@tabler/icons-vue';
import { formatDate } from '@/utils/dateUtils';
import { useFormatting } from '@/composables/useFormatting';
import BaseDropdown from '@/components/common/BaseDropdown.vue';
import BudgetStatusBadge from './BudgetStatusBadge.vue';

const { formatCurrency } = useFormatting();

const props = defineProps({
  budgets: {
    type: Array,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['duplicate', 'create-order', 'cancel', 'delete']);

const editBlockedTitle = 'Orçamento aprovado com pedido vinculado não pode ser editado.';

function isApprovedBudget(budget) {
  const s = (budget?.status ?? '').toString().toLowerCase().trim();
  return s === 'aprovado';
}

function hasLinkedOrder(budget) {
  const oid = budget?.order_id ?? budget?.orderId;
  if (oid === null || oid === undefined || oid === '') {
    return false;
  }
  const n = Number(oid);
  return Number.isFinite(n) && n > 0;
}

function canEditBudget(budget) {
  if (isApprovedBudget(budget) && hasLinkedOrder(budget)) {
    return false;
  }
  return true;
}

function isCancelled(budget) {
  const status = (budget?.status ?? '').toString().toLowerCase();
  return status === 'cancelled' || status === 'cancelado';
}

function isPixPayment(budget) {
  const method = (budget?.payment_method ?? budget?.paymentMethod ?? '').toString().toLowerCase();
  return method === 'pix';
}

function budgetCostValue(budget) {
  const raw = isPixPayment(budget)
    ? (budget?.total_amount ?? budget?.totalAmount)
    : (budget?.total_amount_installments ?? budget?.totalAmountInstallments);
  if (raw === null || raw === undefined || raw === '') {
    return null;
  }
  const num = Number(raw);
  return Number.isFinite(num) ? num : null;
}

function markupSaleValue(budget) {
  const raw = isPixPayment(budget)
    ? (budget?.total_amount_markup ?? budget?.totalAmountMarkup)
    : (budget?.total_amount_installments_markup ?? budget?.totalAmountInstallmentsMarkup);
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
