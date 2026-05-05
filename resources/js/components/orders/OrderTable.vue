<template>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th scope="col" class="text-center" style="width: 40px">
            <input
              type="checkbox"
              class="form-check-input"
              :checked="isAllSelected"
              :indeterminate="isSomeSelected"
              @change="handleToggleAll"
            />
          </th>
          <th scope="col" style="width: 64px">#</th>
          <th scope="col" style="width: 64px">Data</th>
          <th class="text-nowrap" scope="col">Pedido</th>
          <th v-if="showValuesColumn" class="text-nowrap" scope="col" style="width: 120px">
            Valor total
          </th>
          <th class="text-nowrap" scope="col">Situação</th>
          <th v-if="showActionsColumn" class="text-nowrap" scope="col" style="width: 64px">
            Ações
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td colspan="7" class="p-5 text-center text-muted fw-semibold">Carregando pedidos...</td>
        </tr>
        <tr v-else-if="orders.length === 0">
          <td colspan="7" class="p-5 text-center text-muted fw-semibold">
            Nenhum pedido encontrado.
          </td>
        </tr>
        <tr v-for="order in orders" :key="order.id">
          <th class="text-center align-middle">
            <input
              type="checkbox"
              class="form-check-input"
              :checked="isSelected(order.id)"
              @change="() => toggleRow(order.id)"
            />
          </th>
          <td scope="row">{{ order.id }}</td>
          <td>
            {{ formatDate(order.created_at || order.createdAt) }}
          </td>
          <td style="min-width: 240px">
            <router-link
              v-if="nameClickable"
              class="fw-semibold"
              style="color: var(--bs-table-color-state)"
              :to="{
                name: 'orders.show',
                params: { id: order.id },
              }"
            >
              {{ order.name }}
            </router-link>
            <span v-else class="fw-semibold">{{ order.name }}</span>
          </td>
          <td v-if="showValuesColumn" class="">
            <span class="fw-semibold">{{
              formatCurrency(
                order.installments > 0 ? order.total_amount_installments : order.total_amount,
              )
            }}</span>
          </td>
          <td class="text-nowrap">
            <OrderStatusBadge :status="order.status" />
          </td>
          <td v-if="showActionsColumn">
            <BaseDropdown align="end">
              <template #trigger="{ open, toggle }">
                <button
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
                    name: 'orders.show',
                    params: { id: order.id },
                  }"
                >
                  Visualizar
                </router-link>
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
                <router-link
                  class="dropdown-item"
                  :to="{
                    name: 'orders.edit',
                    params: { id: order.id },
                  }"
                >
                  Editar
                </router-link>
              </li>
              <li v-if="order.nf_sent === 1">
                <router-link
                  class="dropdown-item"
                  :to="{
                    name: 'orders.invoice',
                    params: { orderId: order.id },
                  }"
                >
                  Ver nota fiscal
                </router-link>
              </li>
              <li v-if="!isCancelled(order)">
                <button class="dropdown-item" type="button" @click="$emit('cancel', order)">
                  Cancelar
                </button>
              </li>
              <li>
                <hr class="dropdown-divider" />
              </li>
              <li>
                <button
                  class="dropdown-item"
                  style="color: var(--ds-text-danger)"
                  type="button"
                  @click="$emit('delete', order)"
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
import { computed } from 'vue';
import { IconDotsVertical } from '@tabler/icons-vue';
import { formatDate } from '@/utils/dateUtils';
import { useFormatting } from '@/composables/useFormatting';
import { useAuthStore } from '@/stores/auth';
import { useOrderSelection } from '@/composables/useOrdersSelection';
import BaseDropdown from '@/components/common/BaseDropdown.vue';
import OrderStatusBadge from './OrderStatusBadge.vue';

const { formatCurrency } = useFormatting();

const props = defineProps({
  orders: {
    type: Array,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  showValuesColumn: {
    type: Boolean,
    default: () => !useAuthStore().hasRole('commercial'),
  },
  showActionsColumn: {
    type: Boolean,
    default: () => !useAuthStore().hasRole('commercial'),
  },
});

const emit = defineEmits(['register-payment', 'cancel', 'delete', 'update:selected']);

const authStore = useAuthStore();

const { selectedIds, toggleAll, toggleRow, isSelected } = useOrderSelection(
  computed(() => props.orders),
  emit,
);

const isAllSelected = computed(() => {
  return props.orders.length > 0 && selectedIds.value.length === props.orders.length;
});

const isSomeSelected = computed(() => {
  return selectedIds.value.length > 0 && selectedIds.value.length < props.orders.length;
});

function handleToggleAll(event) {
  toggleAll(event.target.checked);
}

const isCommercial = computed(() => authStore.hasRole('commercial'));
const canRegisterPayment = computed(() => authStore.hasPermission('register payments'));
const nameClickable = computed(() => !isCommercial.value);

const CANCELLED_STATUSES = ['cancelado', 'cancelled', 'canceled'];

function isCancelled(order) {
  const status = order?.status?.toLowerCase() || '';
  return CANCELLED_STATUSES.includes(status);
}
</script>
