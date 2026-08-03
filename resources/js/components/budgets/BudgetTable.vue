<template>
  <div class="table-responsive">
    <table
      class="table table-hover table-borderless align-middle mb-0"
      style="--bs-table-color: var(--ds-text)"
    >
      <thead class="text-nowrap">
        <tr>
          <th scope="col" style="width: 64px">#</th>
          <th scope="col" style="width: 100px">Data</th>
          <th scope="col">Orçamento</th>
          <th scope="col" style="width: 110px">Situação</th>
          <th class="text-end" scope="col" style="width: 130px">Custo do orçamento</th>
          <th class="text-end" scope="col" style="width: 130px">Valor da venda</th>
          <th scope="col" style="width: 90px" />
        </tr>
      </thead>
      <tbody class="table-group-divider">
        <tr v-if="loading">
          <td colspan="7" class="p-5 text-center text-muted fw-semibold">
            Carregando orçamentos...
          </td>
        </tr>
        <tr v-else-if="budgetList.length === 0">
          <td colspan="7" class="p-5 text-center text-muted fw-semibold">
            Nenhum orçamento encontrado.
          </td>
        </tr>
        <tr v-for="budget in budgetList" v-else :key="budget.id">
          <th scope="row">{{ budget.id }}</th>
          <td>
            {{ formatDate(budget.created_at || budget.createdAt) }}
          </td>
          <td style="min-width: 240px">
            <router-link
              :to="{
                name: 'budgets.show',
                params: { id: budget.id },
              }"
            >
              {{ budget.name }}
            </router-link>
          </td>
          <td class="text-nowrap">
            <BudgetStatusBadge :status="budget.status" />
          </td>
          <td class="text-nowrap text-end">
            {{ budgetCostValue(budget) != null ? formatCurrency(budgetCostValue(budget)) : '—' }}
          </td>
          <td class="text-nowrap text-end">
            {{ markupSaleValue(budget) != null ? formatCurrency(markupSaleValue(budget)) : '—' }}
          </td>
          <td>
            <BaseDropdown align="end">
              <template #trigger="{ open, toggle }">
                <button
                  class="btn btn-subtle btn-sm dropdown-toggle"
                  type="button"
                  :class="{ show: open }"
                  :aria-expanded="open"
                  @click="toggle"
                >
                  Ações
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
                  <IconEye size="16" class="me-2" /> Ver detalhes
                </router-link>
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
                  <IconEdit size="16" class="me-2" /> Editar
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
                  <IconPrinter size="16" class="me-2" /> Imprimir
                </router-link>
              </li>
              <li
                v-if="
                  budget.status === null ||
                  (budget.status && budget.status.toString().toLowerCase() === 'em aberto')
                "
              >
                <button class="dropdown-item" type="button" @click="$emit('create-order', budget)">
                  <IconInbox size="16" class="me-2" /> Criar pedido
                </button>
              </li>
              <li>
                <button class="dropdown-item" type="button" @click="$emit('duplicate', budget)">
                  <IconCopy size="16" class="me-2" /> Duplicar
                </button>
              </li>
              <li v-if="!isCancelled(budget)">
                <button class="dropdown-item" type="button" @click="$emit('cancel', budget)">
                  <IconBan size="16" class="me-2" /> Cancelar
                </button>
              </li>
              <li>
                <hr class="dropdown-divider" />
              </li>
              <li>
                <button
                  class="dropdown-item"
                  type="button"
                  @click="$emit('delete', budget)"
                  style="color: var(--ds-text-danger)"
                >
                  <IconTrash size="16" class="me-2" /> Excluir
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
import { computed } from 'vue';
import {
  IconPrinter,
  IconTrash,
  IconBan,
  IconInbox,
  IconEdit,
  IconEye,
  IconCopy,
} from '@tabler/icons-vue';
import BudgetStatusBadge from './BudgetStatusBadge.vue';
import BaseDropdown from '@/components/common/BaseDropdown.vue';
import { formatDate } from '@/utils/dateUtils';
import { useFormatting } from '@/composables/useFormatting';

const props = defineProps({
  budgets: {
    type: Object,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['duplicate', 'create-order', 'cancel', 'delete']);

const { formatCurrency } = useFormatting();

const budgetList = computed(() => props.budgets?.data || []);

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

function budgetCostValue(budget) {
  const method = (budget?.payment_method ?? budget?.paymentMethod ?? '').toString().toLowerCase();
  const useVista = !method || method === 'pix';
  const raw = useVista
    ? (budget?.total_amount ?? budget?.totalAmount)
    : (budget?.total_amount_installments ?? budget?.totalAmountInstallments);
  if (raw === null || raw === undefined || raw === '') {
    return null;
  }
  const num = Number(raw);
  return Number.isFinite(num) ? num : null;
}

function markupSaleValue(budget) {
  const method = (budget?.payment_method ?? budget?.paymentMethod ?? '').toString().toLowerCase();
  const useVista = !method || method === 'pix';
  const raw = useVista
    ? (budget?.total_amount_markup ?? budget?.totalAmountMarkup)
    : (budget?.total_amount_installments_markup ?? budget?.totalAmountInstallmentsMarkup);
  if (raw === null || raw === undefined || raw === '') {
    return null;
  }
  const num = Number(raw);
  return Number.isFinite(num) ? num : null;
}
</script>
