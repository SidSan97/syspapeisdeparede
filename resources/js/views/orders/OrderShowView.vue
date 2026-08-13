<template>
  <section class="content">
    <Page :title="order?.name || 'Pedido'" :breadcrumbs="routes">
      <template #extra>
        <button
          class="btn btn-outline-default"
          type="button"
          @click="
            router.replace({
              name: 'orders.edit',
              params: {
                id: route.params.id,
              },
            })
          "
        >
          Editar
        </button>
        <button
          v-if="canApproveOrder"
          class="btn btn-outline-default"
          type="button"
          @click="handleApprove"
          :disabled="isApproving"
        >
          <span
            v-if="isApproving"
            class="spinner-border spinner-border-sm me-2"
            role="status"
            aria-hidden="true"
          ></span>
          Aprovar
        </button>
      </template>

      <template #subtitle> <OrderStatusBadge :status="order?.status" /> </template>

      <div v-if="orderStore.loadingOrderById" class="text-center text-muted py-5">
        <div class="spinner-border" role="status">
          <span class="visually-hidden">Carregando...</span>
        </div>
      </div>
      <template v-else-if="order">
        <BaseComment
          :author="order?.reseller_name || 'Revendedor não informado'"
          badge="revendedor"
          class="mb-5"
        >
          <template #avatar>
            <BaseAvatar :name="order?.reseller_name || 'N/A'" />
          </template>

          <div class="fs-xs">
            <span v-if="order?.created_at">Criado {{ formatDate(order.created_at) }}</span>
            &bull;
            <span v-if="order?.updated_at">Atualizado em {{ formatDate(order.updated_at) }}</span>
          </div>
        </BaseComment>

        <div class="row">
          <div class="col-12 col-lg-8">
            <!-- <BasicInfoCard :data="order" :is-dropshipping-enabled="isDropshippingEnabled" /> -->
            <DropshippingDataCard
              :is-dropshipping-enabled="isDropshippingEnabled"
              :dropshipping-data="order.dropshipping_data"
            />
            <RoomsWithArtsCard :data="order" :is-order="true" :show-wall-status="true" />
          </div>

          <!-- Sidebar: Frete, Pagamento e Resumo -->
          <div class="col-12 col-lg-4">
            <ShippingCard :data="order" />

            <hr />

            <PaymentCard
              :data="order"
              :is-order="true"
              :generating-payment-link="isGeneratingPaymentLink"
              @generate-payment-link="openPaymentModal"
            />

            <hr />

            <SummaryCard :data="order" />
            <!-- <AdditionalInfoCard :data="order" /> -->
          </div>
        </div>

        <OrderChangeHistoryCard :histories="order.change_histories || []" />
      </template>
      <EmptyState v-else> Não foi possível carregar os detalhes. </EmptyState>
    </Page>
    <GeneratePaymentLinkModal
      :visible="isPaymentModalOpen"
      :submitting="isGeneratingPaymentLink"
      :default-installments="Number(order?.installments || 1)"
      :payment-breakdown="order?.payment_breakdown || null"
      :wallet-balance="walletBalance"
      @close="closePaymentModal"
      @submit="handleGeneratePaymentLink"
    />
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ORDER_STATUS } from '@/constants/orderStatuses';
import { walletService } from '@/services/walletService';
import { useFormatting } from '@/composables/useFormatting';
import { useOrderStore } from '@/stores/orderStore';

import EmptyState from '@/components/empty-state/EmptyState.vue';
import Page from '@/components/page/Page.vue';
import BaseComment from '@/components/common/BaseComment.vue';
import BaseAvatar from '@/components/common/BaseAvatar.vue';

import AdditionalInfoCard from '@/components/details/AdditionalInfoCard.vue';
import BasicInfoCard from '@/components/details/BasicInfoCard.vue';
import DropshippingDataCard from '@/components/details/DropshippingDataCard.vue';
import GeneratePaymentLinkModal from '@/components/details/GeneratePaymentLinkModal.vue';
import PaymentCard from '@/components/details/PaymentCard.vue';
import RoomsWithArtsCard from '@/components/details/RoomsWithArtsCard.vue';
import ShippingCard from '@/components/details/ShippingCard.vue';
import SummaryCard from '@/components/details/SummaryCard.vue';
import OrderChangeHistoryCard from '@/components/details/OrderChangeHistoryCard.vue';
import { orderService } from '@/services/orderService';
import OrderStatusBadge from '@/components/orders/OrderStatusBadge.vue';

const router = useRouter();
const route = useRoute();
const { formatDate } = useFormatting();
const orderStore = useOrderStore();

const isApproving = ref(false);
const isGeneratingPaymentLink = ref(false);
const isPaymentModalOpen = ref(false);
const walletBalance = ref(0);

const routes = [
  { path: '/', breadcrumbName: 'Início' },
  { path: '/orders', breadcrumbName: 'Pedidos' },
  { path: '#', breadcrumbName: 'Visualizar' },
];

const order = computed(() => orderStore.currentOrder);

const isDropshippingEnabled = computed(() => order.value?.dropshipping_budget === 1);

const canApproveOrder = computed(() => {
  return (
    order.value &&
    order.value.status != ORDER_STATUS.APPROVED &&
    order.value.status != ORDER_STATUS.CANCELED
  );
});

const fetchOrder = async () => {
  try {
    await orderStore.loadOrderById(route.params.id);
    await afterOrderLoaded();
  } catch (error) {
    handleFetchError(error);
  }
};

async function afterOrderLoaded() {
  await Promise.all([refreshWalletBalance(), checkAndRefreshExpiredPaymentLink()]);
}

const refreshWalletBalance = async () => {
  try {
    const balance = walletService.getBalance();
    walletBalance.value = Number(balance ?? 0);
  } catch {
    walletBalance.value = 0;
  }
};

const checkAndRefreshExpiredPaymentLink = async () => {
  if (!order.value) return;
  if (!order.value.link_payment || !order.value.payment_expiration_date) return;

  if (orderService.isPaymentLinkExpired(order.value.payment_expiration_date)) {
    await regeneratePaymentLink();
  }
};

async function regeneratePaymentLink() {
  if (!order.value?.id) return;

  isGeneratingPaymentLink.value = true;
  try {
    const updatedOrder = await orderService.generatePaymentLink(order.value.id);
    if (updatedOrder) {
      orderStore.currentOrder = updatedOrder;
    }
  } catch (error) {
    console.error('Erro ao regenerar link:', error);
  } finally {
    isGeneratingPaymentLink.value = false;
  }
}

async function handleApprove() {
  if (!order.value) return;

  const confirmed = await confirmApproval();
  if (!confirmed) return;

  isApproving.value = true;

  try {
    await orderService.approve(order.value.id);
    await fetchOrder();
    await showSuccessMessage('Pedido aprovado com sucesso!');
  } catch (error) {
    await showErrorMessage(error, 'Não foi possível aprovar o pedido.');
  } finally {
    isApproving.value = false;
  }
}

async function openPaymentModal() {
  await refreshWalletBalance();
  isPaymentModalOpen.value = true;
}

function closePaymentModal() {
  isPaymentModalOpen.value = false;
}

async function handleGeneratePaymentLink(formValues) {
  isGeneratingPaymentLink.value = true;

  try {
    const updatedOrder = await orderService.generatePaymentLinkByComponents(
      order.value.id,
      formValues,
    );

    if (updatedOrder) {
      orderStore.currentOrder = updatedOrder;
    }

    const isBoleto = formValues?.payment_method === 'boleto';
    await showSuccessMessage(
      isBoleto ? 'Pagamento realizado!' : 'Link de pagamento gerado com sucesso!',
    );

    closePaymentModal();

    if (isBoleto) {
      await refreshWalletBalance();
    }
  } catch (error) {
    await showErrorMessage(error, 'Não foi possível gerar o link de pagamento.');
  } finally {
    isGeneratingPaymentLink.value = false;
  }
}

const confirmApproval = async () => {
  const result = await window.Swal.fire({
    title: 'Aprovar pedido?',
    text: 'Tem certeza que deseja aprovar o pedido?',
    icon: 'question',
    showCancelButton: true,
    showCloseButton: true,
    reverseButtons: true,
    confirmButtonText: 'Sim, aprovar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#198754',
  });

  return result.isConfirmed;
};

async function showSuccessMessage(message) {
  await window.Swal.fire({
    title: 'Sucesso!',
    text: message,
    icon: 'success',
    confirmButtonText: 'OK',
  });
}

async function showErrorMessage(error, defaultMessage) {
  const errorMessage = error?.response?.data?.message || error?.message || defaultMessage;
  await window.Swal.fire({
    title: 'Erro',
    text: errorMessage,
    icon: 'error',
    confirmButtonText: 'OK',
  });
}

function handleFetchError(error) {
  window.Swal.fire({
    title: 'Erro!',
    text: error.message || 'Não foi possível carregar os detalhes',
    icon: 'error',
    showCloseButton: true,
    confirmButtonText: 'Entendi!',
  });
  console.error('Erro ao carregar dados:', error);
  router.push({ name: 'orders.list' });
}

onMounted(fetchOrder);
</script>
