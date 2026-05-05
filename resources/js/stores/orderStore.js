import { orderService } from '@/services/orderService';
import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useOrderStore = defineStore('orders', () => {
  const orders = ref({
    data: [],
    meta: {},
  });
  const currentOrder = ref(null);

  const loadingOrders = ref(false);
  const loadingOrderById = ref(false);
  const error = ref(null);

  async function loadOrders(params = {}) {
    if (loadingOrders.value) return; // Prevent concurrent calls

    loadingOrders.value = true;
    error.value = null;

    try {
      const response = await orderService.all(params);
      orders.value = response;
    } catch (e) {
      error.value = 'Erro ao carregar pedido';
      throw e;
    } finally {
      loadingOrders.value = false;
    }
  }

  async function loadOrderById(id) {
    loadingOrderById.value = true;
    error.value = null;

    try {
      currentOrder.value = await orderService.find(id);
    } catch (e) {
      error.value = 'Erro ao carregar pedido';
      throw e;
    } finally {
      loadingOrderById.value = false;
    }
  }

  function clearCurrentOrder() {
    currentOrder.value = null;
  }

  return {
    orders,
    currentOrder,

    loadingOrders,
    loadingOrderById,
    error,

    loadOrders,
    loadOrderById,
    clearCurrentOrder,
  };
});
