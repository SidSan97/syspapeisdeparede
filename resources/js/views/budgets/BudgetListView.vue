<template>
  <section class="content">
    <Page title="Orçamentos" full-width>
      <template #extra>
        <RouterLink class="btn btn-primary" :to="{ name: 'budgets.create' }">
          Criar orçamento
        </RouterLink>
      </template>
      <BudgetFilters v-model="filters" @search="fetchBudgets" />
      <BudgetTable
        :budgets="budgets"
        :loading="loading"
        @duplicate="duplicateBudget"
        @create-order="handleCreateOrder"
        @cancel="cancelBudget"
        @delete="deleteBudget"
      />
      <Bootstrap5Pagination
        :data="budgets"
        @pagination-change-page="goToPage"
        class="justify-content-center mt-3"
      />
    </Page>

    <CreateOrderModal
      v-model="createOrderModalOpen"
      :budget="selectedBudgetForOrder"
      :submitting="creatingOrder"
      @confirm="handleCreateOrderConfirm"
      @close="handleCloseModal"
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
import { useBudgetActions } from '@/composables/useBudgetActions';

const { duplicateBudget, cancelBudget, deleteBudget, createOrder } = useBudgetActions();
const { budgets, filters, loading, fetchBudgets, goToPage } = useBudgetsList();

const createOrderModalOpen = ref(false);
const selectedBudgetForOrder = ref(null);
const creatingOrder = ref(false);

function handleCreateOrder(budget) {
  selectedBudgetForOrder.value = budget;
  createOrderModalOpen.value = true;
}

function handleCloseModal() {
  if (creatingOrder.value) {
    return;
  }

  resetModal();
}

async function handleCreateOrderConfirm({ budget, walls }) {
  if (!budget?.id || creatingOrder.value) {
    return;
  }

  creatingOrder.value = true;
  try {
    await createOrder(budget, { walls });

    resetModal();
  } finally {
    creatingOrder.value = false;
  }
}

function resetModal() {
  createOrderModalOpen.value = false;
  selectedBudgetForOrder.value = null;
}

onMounted(() => {
  fetchBudgets();
});
</script>
