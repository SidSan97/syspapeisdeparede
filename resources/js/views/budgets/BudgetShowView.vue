<template>
  <section class="content">
    <Page :title="budget?.name || 'Orçamento'" :breadcrumbs="routes">
      <template #extra>
        <router-link
          class="btn btn-outline-default"
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
              class="btn btn-outline-default dropdown-toggle"
              type="button"
              :class="{ show: open }"
              :aria-expanded="open"
              @click="toggle"
            >
              Mais ações
            </button>
          </template>

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
          <li>
            <button class="dropdown-item" type="button" @click="duplicateBudget(budget)">
              <IconCopy size="16" class="me-2" /> Duplicar
            </button>
          </li>
          <li v-if="!isCancelled(budget)">
            <button class="dropdown-item" type="button" @click="handleCancel">
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
              @click="handleDelete"
              style="color: var(--ds-text-danger)"
            >
              <IconTrash size="16" class="me-2" /> Excluir
            </button>
          </li>
        </BaseDropdown>
      </template>

      <template #subtitle> <BudgetStatusBadge :status="budget?.status" /> </template>

      <div v-if="budgetStore.loadingBudgetById" class="text-center text-muted py-5">
        <div class="spinner-border" role="status">
          <span class="visually-hidden">Carregando...</span>
        </div>
      </div>
      <template v-else-if="budget">
        <BaseComment
          :author="budget.reseller_name || 'Revendedor não informado'"
          badge="revendedor"
          class="mb-5"
        >
          <template #avatar>
            <BaseAvatar :name="budget.reseller_name || 'N/A'" />
          </template>

          <div class="fs-xs">
            <span v-if="budget?.created_at">Criado {{ formatDate(budget.created_at) }}</span>
            &bull;
            <span v-if="budget?.updated_at">Atualizado em {{ formatDate(budget.updated_at) }}</span>
          </div>
        </BaseComment>

        <div class="row">
          <div class="col-12 col-lg-8">
            <DropshippingDataCard
              :is-dropshipping-enabled="isDropshippingEnabled"
              :dropshipping-data="budget?.dropshipping_data"
            />
            <RoomsWithArtsCard :data="budget" />
          </div>

          <!-- Sidebar: Frete, Pagamento e Resumo -->
          <div class="col-12 col-lg-4">
            <ShippingCard :data="budget" />

            <hr />

            <PaymentCard
              :data="budget"
              :is-order="false"
              :generating-payment-link="isGeneratingPaymentLink"
              @generate-payment-link="openPaymentModal"
            />

            <hr />

            <SummaryCard :data="budget" />
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
import { IconPrinter, IconCopy, IconInbox, IconBan, IconTrash } from '@tabler/icons-vue';
import Page from '@/components/page/Page.vue';
import EmptyState from '@/components/empty-state/EmptyState.vue';
import BaseDropdown from '@/components/common/BaseDropdown.vue';
import BaseComment from '@/components/common/BaseComment.vue';
import BaseAvatar from '@/components/common/BaseAvatar.vue';

import DropshippingDataCard from '@/components/details/DropshippingDataCard.vue';
import ShippingCard from '@/components/details/ShippingCard.vue';
import PaymentCard from '@/components/details/PaymentCard.vue';
import SummaryCard from '@/components/details/SummaryCard.vue';
import GeneratePaymentLinkModal from '@/components/details/GeneratePaymentLinkModal.vue';
import RoomsWithArtsCard from '@/components/details/RoomsWithArtsCard.vue';
import CreateOrderModal from '@/components/budgets/CreateOrderModal.vue';

import { useFormatting } from '@/composables/useFormatting';
import { useBudgetStore } from '@/stores/budgetStore';
import { useDialog } from '@/composables/useDialog';
import { orderService } from '@/services/orderService';
import { http } from '@/lib/http';
import BudgetStatusBadge from '@/components/budgets/BudgetStatusBadge.vue';
import { useBudgetActions } from '@/composables/useBudgetActions';

const router = useRouter();
const route = useRoute();

const budgetStore = useBudgetStore();

const dialog = useDialog();
const { formatDate } = useFormatting();
const { duplicateBudget, cancelBudget, deleteBudget, createOrder } = useBudgetActions();

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
  } finally {
    creatingOrder.value = false;
  }
}

async function handleCancel() {
  await cancelBudget();

  fetchBudget();
}

async function handleDelete() {
  await deleteBudget();

  router.push({ name: 'budgets.list' });
}

async function fetchBudget() {
  const id = route.params.id;

  try {
    await budgetStore.loadBudgetById(id);
  } catch (error) {
    dialog.error({
      text: error.message || 'Não foi possível carregar os detalhes',
    });

    router.push({ name: 'budgets.list' });
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
    await dialog.success({
      text: isBoleto ? 'Pagamento realizado!' : 'Link de pagamento gerado com sucesso!',
    });

    closePaymentModal();

    if (isBoleto) {
      await refreshWalletBalance();
    }
  } catch (error) {
    await dialog.error({
      text:
        error?.response?.data?.message ||
        error?.message ||
        'Não foi possível gerar o link de pagamento.',
    });
  } finally {
    isGeneratingPaymentLink.value = false;
  }
}

onMounted(fetchBudget);
</script>
