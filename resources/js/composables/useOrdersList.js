import { computed, reactive } from 'vue';
import { useToast } from '@/composables/useToast';
import { validateMergeOrdersSelection } from '@/modules/orders/utils/validateMergeOrders';
import { orderService } from '@/services/orderService';
import { useOrderStore } from '@/stores/orderStore';

export function useOrdersList() {
  const toast = useToast();
  const orderStore = useOrderStore();

  const filters = reactive({
    page: 1,
    search: '',
    status: '',
    user_id: null,
    date_from: null,
    date_to: null,
  });

  const orderList = computed(() => orderStore.orders?.data || []);
  const loading = computed(() => orderStore.loadingOrders);

  function fetchOrders(params) {
    if (params) Object.assign(filters, params);

    orderStore.loadOrders(filters);
  }

  function goToPage(page) {
    filters.page = page;

    fetchOrders();
  }

  const cancelOrder = async (order) => {
    if (!order?.id) return;

    try {
      await orderService.cancel(order.id);

      toast.success('Pedido cancelado com sucesso.');

      fetchOrders();
    } catch (error) {
      console.error(error);
      toast.error('Erro ao cancelar o pedido. Tente novamente.');
    }
  };

  const deleteOrder = async (order) => {
    if (!order?.id) return;

    try {
      await orderService.delete(order.id);

      toast.success('Pedido excluído com sucesso.');

      fetchOrders();
    } catch (error) {
      console.error(error);
      toast.error('Erro ao excluir o pedido. Tente novamente.');
    }
  };

  async function mergeOrders(ids, name) {
    const selectedRows = orderList.value.filter((o) => ids.includes(o.id));

    const precheck = validateMergeOrdersSelection(selectedRows);
    if (precheck) {
      toast.error('Não é possível juntar. Verifique e tente novamente.');

      return;
    }

    try {
      await orderService.merge({
        order_ids: [...ids],
        name: name,
      });

      toast.success('Pedidos mesclados com sucesso!');

      fetchOrders();
    } catch (error) {
      console.error(error);
      toast.error('Erro ao mesclar os pedidos. Tente novamente.');
    }
  }

  return {
    filters,
    loading,
    orderList,
    fetchOrders,
    goToPage,
    cancelOrder,
    deleteOrder,
    mergeOrders,
  };
}
