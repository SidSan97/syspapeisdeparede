<template>
  <section class="content">
    <Page title="Orçamento" :back-to="{ name: 'budgets.list' }" :breadcrumbs="routes">
      <template #extra>
        <router-link
          class="btn btn-default"
          :class="{ disabled: !canEditBudget(budget) }"
          :to="{
            name: 'budgets.edit',
            params: { id: budget.id },
          }"
          v-if="budget"
        >
          Editar
        </router-link>

        <BaseDropdown v-if="budget" align="end">
          <template #trigger="{ open, toggle }">
            <button
              class="btn btn-default"
              type="button"
              :class="{ show: open }"
              :aria-expanded="open"
              @click="toggle"
            >
              Mais ações <IconChevronDown size="14" />
            </button>
          </template>

          <li>
            <button class="dropdown-item" type="button" @click="confirmDuplicate">
              <IconCopy size="16" class="me-2" /> Duplicar
            </button>
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
          <li v-if="showCreateOrder(budget)">
            <button class="dropdown-item" type="button" @click="confirmCreateOrder">
              <IconInbox size="16" class="me-2" /> Criar pedido
            </button>
          </li>
          <li v-if="!isCancelled(budget)">
            <button class="dropdown-item" type="button" @click="confirmCancel">
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
              @click="confirmDelete"
              style="color: var(--ds-text-danger)"
            >
              <IconTrash size="16" class="me-2" /> Excluir
            </button>
          </li>
        </BaseDropdown>
      </template>

      <div v-if="budgetStore.loadingBudgetById" class="text-center text-muted py-5">
        <div class="spinner-border" role="status">
          <span class="visually-hidden">Carregando...</span>
        </div>
      </div>
      <template v-else-if="budget">
        <div class="row">
          <div class="col-12 col-lg-8">
            <BasicInfoCard :data="budget" :is-dropshipping-enabled="isDropshippingEnabled" />
            <DropshippingDataCard
              :is-dropshipping-enabled="isDropshippingEnabled"
              :dropshipping-data="budget?.dropshipping_data"
            />
            <RoomsWithArtsCard :data="budget" />
          </div>

          <!-- Sidebar: Frete, Pagamento e Resumo -->
          <div class="col-12 col-lg-4">
            <ShippingCard :data="budget" />
            <PaymentCard
              :data="budget"
              :is-order="false"
              :generating-payment-link="isGeneratingPaymentLink"
              @generate-payment-link="openPaymentModal"
            />
            <SummaryCard :data="budget" />
            <AdditionalInfoCard :data="budget" />
          </div>
        </div>
      </template>
      <EmptyState v-else> Não foi possível carregar os detalhes. </EmptyState>
    </Page>
    <GeneratePaymentLinkModal
      :visible="isPaymentModalOpen"
      :submitting="isGeneratingPaymentLink"
      :default-installments="Number(budget?.installments || 1)"
      :payment-breakdown="budget?.payment_breakdown || null"
      :wallet-balance="walletBalance"
      @close="closePaymentModal"
      @submit="handleGeneratePaymentLink"
    />
    <CreateOrderModal
      v-model="createOrderModalOpen"
      :budget="budget"
      :submitting="creatingOrder"
      @confirm="handleCreateOrderConfirm"
      @close="closeCreateOrderModal"
    />
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import {
  IconPrinter,
  IconCopy,
  IconChevronDown,
  IconInbox,
  IconBan,
  IconTrash,
} from '@tabler/icons-vue';
import Page from '@/components/page/Page.vue';
import EmptyState from '@/components/empty-state/EmptyState.vue';
import BaseDropdown from '@/components/common/BaseDropdown.vue';

import BasicInfoCard from '@/components/details/BasicInfoCard.vue';
import DropshippingDataCard from '@/components/details/DropshippingDataCard.vue';
import ShippingCard from '@/components/details/ShippingCard.vue';
import PaymentCard from '@/components/details/PaymentCard.vue';
import SummaryCard from '@/components/details/SummaryCard.vue';
import AdditionalInfoCard from '@/components/details/AdditionalInfoCard.vue';
import GeneratePaymentLinkModal from '@/components/details/GeneratePaymentLinkModal.vue';
import RoomsWithArtsCard from '@/components/details/RoomsWithArtsCard.vue';
import CreateOrderModal from '@/components/budgets/CreateOrderModal.vue';

import { useBudgetStore } from '@/stores/budgetStore';
import { useBudgetsList } from '@/composables/useBudgetsList';
import { useToast } from '@/composables/useToast';
import { budgetService } from '@/services/budgetService';
import { orderService } from '@/services/orderService';
import { http } from '@/lib/http';

const router = useRouter();
const route = useRoute();
const budgetStore = useBudgetStore();
const toast = useToast();
const { duplicateBudget, createOrder } = useBudgetsList();

const routes = [
  { path: '/', breadcrumbName: 'Início' },
  { path: '/budgets', breadcrumbName: 'Orçamentos' },
  { path: '#', breadcrumbName: 'Visualizar' },
];

const isGeneratingPaymentLink = ref(false);
const isPaymentModalOpen = ref(false);
const walletBalance = ref(0);
const createOrderModalOpen = ref(false);
const creatingOrder = ref(false);

const budget = computed(() => budgetStore.currentBudget);

const isDropshippingEnabled = computed(() => {
  return budget.value?.dropshipping_budget === 1;
});

function isApprovedBudget(b) {
  const status = (b?.status ?? '').toString().toLowerCase().trim();
  return status === 'aprovado';
}

function hasLinkedOrder(b) {
  const orderId = b?.order_id ?? b?.orderId;
  if (orderId === null || orderId === undefined || orderId === '') {
    return false;
  }
  const n = Number(orderId);
  return Number.isFinite(n) && n > 0;
}

function canEditBudget(b) {
  if (isApprovedBudget(b) && hasLinkedOrder(b)) {
    return false;
  }
  return true;
}

function isCancelled(b) {
  const status = (b?.status ?? '').toString().toLowerCase();
  return status === 'cancelled' || status === 'cancelado';
}

function showCreateOrder(b) {
  return b?.status === null || (b?.status && b.status.toString().toLowerCase() === 'em aberto');
}

async function confirmDuplicate() {
  const result = await window.Swal.fire({
    title: 'Duplicar orçamento?',
    html: 'Tem certeza que deseja duplicar este orçamento?',
    icon: 'question',
    confirmButtonText: 'Duplicar',
    cancelButtonText: 'Cancelar',
    showCancelButton: true,
  });

  if (result.isConfirmed && budget.value) {
    await duplicateBudget(budget.value);
  }
}

async function confirmCreateOrder() {
  if (!budget.value) {
    return;
  }

  createOrderModalOpen.value = true;
}

function closeCreateOrderModal() {
  if (creatingOrder.value) {
    return;
  }

  createOrderModalOpen.value = false;
}

async function handleCreateOrderConfirm({ budget: selectedBudget, walls }) {
  if (!selectedBudget?.id || creatingOrder.value) {
    return;
  }

  creatingOrder.value = true;
  try {
    await createOrder(selectedBudget, { walls });
    createOrderModalOpen.value = false;
  } catch (error) {
    // Erro já tratado no composable
  } finally {
    creatingOrder.value = false;
  }
}

async function confirmCancel() {
  const result = await window.Swal.fire({
    title: 'Cancelar orçamento?',
    html: 'Tem certeza que deseja cancelar o orçamento?',
    icon: 'warning',
    confirmButtonText: 'Cancelar orçamento',
    cancelButtonText: 'Não, manter',
    showCancelButton: true,
  });

  if (!result.isConfirmed || !budget.value?.id) {
    return;
  }

  try {
    await budgetService.cancel(budget.value.id);
    toast.success('Orçamento cancelado com sucesso.');
    await budgetStore.loadBudgetById(budget.value.id);
  } catch (error) {
    console.error(error);
    toast.error('Erro ao cancelar o orçamento. Tente novamente.');
  }
}

async function confirmDelete() {
  const result = await window.Swal.fire({
    title: 'Excluir orçamento?',
    html: 'Tem certeza que deseja excluir o orçamento? Esta ação não pode ser desfeita.',
    icon: 'warning',
    confirmButtonText: 'Excluir',
    cancelButtonText: 'Cancelar',
    showCancelButton: true,
  });

  if (!result.isConfirmed || !budget.value?.id) {
    return;
  }

  try {
    await budgetService.delete(budget.value.id);
    toast.success('Orçamento excluído com sucesso.');
    router.push({ name: 'budgets.list' });
  } catch (error) {
    console.error(error);
    toast.error('Opa! Erro ao excluir o orçamento. Tente novamente.');
  }
}

async function fetchBudget() {
  const id = route.params.id;

  try {
    await budgetStore.loadBudgetById(id);
  } catch (error) {
    handleFetchError(error);
  }
}

async function refreshWalletBalance() {
  try {
    const { data } = await http.get('v1/wallet', { params: { page: 1 } });
    walletBalance.value = Number(data.balance ?? 0);
  } catch {
    walletBalance.value = 0;
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
    const updatedBudget = await orderService.generatePaymentLinkByComponents(
      budget.value.id,
      formValues,
    );

    if (updatedBudget) {
      budgetStore.currentBudget = updatedBudget;
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
  router.push({ name: 'budgets.list' });
}

onMounted(fetchBudget);
</script>
