<template>
  <section class="content">
    <Page title="Orçamentos">
      <template #extra>
        <RouterLink class="btn btn-primary" :to="{ name: 'budgets.create' }">
          Criar orçamento
        </RouterLink>
      </template>
      <BudgetFilters v-model="filters" @search="fetchBudgets" />
      <BudgetTable
        :budgets="budgetList"
        :loading="loading"
        @duplicate="confirmDuplicate"
        @create-order="openCreateOrderModal"
        @cancel="confirmCancel"
        @delete="confirmDelete"
      />
      <Bootstrap5Pagination
        :data="budgetStore.budgets"
        @pagination-change-page="goToPage"
        class="justify-content-center mt-3"
      />
    </Page>

    <CreateOrderModal
      v-model="createOrderModalOpen"
      :budget="selectedBudgetForOrder"
      :submitting="creatingOrder"
      @confirm="handleCreateOrderConfirm"
      @close="closeCreateOrderModal"
    />
  </section>
</template>

<script setup>
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
import { onMounted, ref } from 'vue';

import BudgetFilters from '@/components/budgets/BudgetFilters.vue';
import BudgetTable from '@/components/budgets/BudgetTable.vue';
import CreateOrderModal from '@/components/budgets/CreateOrderModal.vue';
import Page from '@/components/page/Page.vue';

import { useBudgetsList } from '@/composables/useBudgetsList';
import { useBudgetStore } from '@/stores/budgetStore';

const budgetStore = useBudgetStore();

const {
  filters,
  loading,
  budgetList,
  fetchBudgets,
  goToPage,
  duplicateBudget,
  cancelBudget,
  deleteBudget,
  createOrder,
} = useBudgetsList();

const createOrderModalOpen = ref(false);
const selectedBudgetForOrder = ref(null);
const creatingOrder = ref(false);

const confirmDuplicate = async (budget) => {
  const result = await window.Swal.fire({
    title: 'Duplicar orçamento?',
    html: 'Tem certeza que deseja duplicar este orçamento?',
    icon: 'question',
    confirmButtonText: 'Duplicar',
    cancelButtonText: 'Cancelar',
    showCancelButton: true,
  });

  if (result.isConfirmed) duplicateBudget(budget);
};

function openCreateOrderModal(budget) {
  selectedBudgetForOrder.value = budget;
  createOrderModalOpen.value = true;
}

function closeCreateOrderModal() {
  if (creatingOrder.value) {
    return;
  }

  createOrderModalOpen.value = false;
  selectedBudgetForOrder.value = null;
}

async function handleCreateOrderConfirm({ budget, walls }) {
  if (!budget?.id || creatingOrder.value) {
    return;
  }

  creatingOrder.value = true;
  try {
    await createOrder(budget, { walls });
    createOrderModalOpen.value = false;
    selectedBudgetForOrder.value = null;
  } catch (error) {
    // Erro já tratado no composable
  } finally {
    creatingOrder.value = false;
  }
}

const confirmCancel = async (budget) => {
  const result = await window.Swal.fire({
    title: 'Cancelar orçamento?',
    html: 'Tem certeza que deseja cancelar o orçamento?',
    icon: 'warning',
    confirmButtonText: 'Cancelar orçamento',
    cancelButtonText: 'Não, manter',
    showCancelButton: true,
  });

  if (result.isConfirmed) cancelBudget(budget);
};

const confirmDelete = async (budget) => {
  const result = await window.Swal.fire({
    title: 'Excluir orçamento?',
    html: 'Tem certeza que deseja excluir o orçamento? Esta ação não pode ser desfeita.',
    icon: 'warning',
    confirmButtonText: 'Excluir',
    cancelButtonText: 'Cancelar',
    showCancelButton: true,
  });

  if (result.isConfirmed) deleteBudget(budget);
};

onMounted(() => {
  fetchBudgets();

  document.title = 'Orçamentos';
});
</script>
