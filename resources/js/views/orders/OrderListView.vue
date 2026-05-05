<template>
  <section class="content">
    <Page title="Pedidos">
      <div>
        <OrderFilters v-model="filters" @search="fetchOrders" />
        <SelectionBar
          :count="selectedCount"
          selected-label="pedido selecionado"
          selected-plural-label="pedidos selecionados"
          @close="clearSelection"
        >
          <template #actions>
            <button
              :disabled="!canMerge"
              class="btn btn-primary btn-sm"
              :class="{ 'opacity-50': !canMerge }"
              :title="
                !canMerge
                  ? `Selecione pelo menos 2 itens para mesclar`
                  : 'Mesclar pedidos selecionados'
              "
              @click="confirmMerge"
            >
              Mesclar
            </button>
          </template>
        </SelectionBar>
        <OrderTable
          :loading="loading"
          :orders="orderList"
          @cancel="confirmCancel"
          @delete="confirmDelete"
          @register-payment="handleRegisterPayment"
          @update:selected="handleSelectionChange"
        />
        <Bootstrap5Pagination
          :data="orderStore.orders"
          @pagination-change-page="goToPage"
          class="justify-content-center mt-3"
        />
      </div>
    </Page>
    <OrderRegisterPaymentModal
      v-model="paymentModalVisible"
      :order-id="paymentPedido?.id"
      @success="handlePaymentSuccess"
      @error="() => true"
    />
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';

import Page from '@/components/page/Page.vue';
import SelectionBar from '@/components/SelectionBar.vue';
import OrderRegisterPaymentModal from '@/components/orders/OrderRegisterPaymentModal.vue';
import OrderFilters from '@/components/orders/OrderFilters.vue';
import OrderTable from '@/components/orders/OrderTable.vue';

import { useDialog } from '@/composables/useDialog';
import { useAuthStore } from '@/stores/auth';
import { useOrdersList } from '@/composables/useOrdersList';
import { useOrderStore } from '@/stores/orderStore';

const dialog = useDialog();
const authStore = useAuthStore();
const orderStore = useOrderStore();

const {
  filters,
  loading,
  orderList,
  fetchOrders,
  goToPage,
  cancelOrder,
  deleteOrder,
  mergeOrders,
} = useOrdersList();

const paymentModalVisible = ref(false);
const paymentPedido = ref(null);
const selectedOrderIds = ref([]);

const canMerge = computed(() => authStore.isAdmin() && selectedOrderIds.value.length >= 2);

const selectedCount = computed(() => selectedOrderIds.value.length);

function handleSelectionChange(ids) {
  selectedOrderIds.value = ids;
}

const clearSelection = () => {
  selectedOrderIds.value = [];
};

const confirmMerge = async () => {
  const ids = selectedOrderIds.value;

  const { value: name, isDismissed } = await window.Swal.fire({
    title: 'Mesclar pedidos',
    html: 'Informe o nome do <strong>novo pedido</strong> que reunirá os ambientes e modelos selecionados.',
    input: 'text',
    confirmButtonText: 'Mesclar',
    cancelButtonText: 'Cancelar',
    showCancelButton: true,
    inputValidator: (v) => (!v?.trim() ? 'Digite um nome para o pedido.' : null),
  });

  if (isDismissed || !name) return;

  await mergeOrders(ids, name.trim());
};

function handleRegisterPayment(pedido) {
  paymentPedido.value = pedido;
  paymentModalVisible.value = true;
}

function handlePaymentSuccess() {
  fetchOrders();
}

const confirmCancel = async (order) => {
  const confirmed = await dialog.confirm({
    title: 'Cancelar pedido?',
    text: 'Essa ação não pode ser desfeita.',
    confirmButtonText: 'Sim, cancelar pedido',
    cancelButtonText: 'Voltar',
  });

  if (confirmed) cancelOrder(order);
};

const confirmDelete = async (order) => {
  const confirmed = await dialog.confirmDelete({
    title: 'Excluir pedido?',
    text: 'Tem certeza que deseja excluir o pedido? Esta ação não pode ser desfeita.',
  });

  if (confirmed) deleteOrder(order);
};

onMounted(() => {
  fetchOrders();

  document.title = 'Pedidos';
});
</script>
