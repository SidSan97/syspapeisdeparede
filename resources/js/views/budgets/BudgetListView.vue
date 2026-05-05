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
        @create-order="confirmCreateOrder"
        @cancel="confirmCancel"
        @delete="confirmDelete"
      />
      <Bootstrap5Pagination
        :data="budgetStore.budgets"
        @pagination-change-page="goToPage"
        class="justify-content-center mt-3"
      />
    </Page>
  </section>
</template>

<script setup>
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
import { onMounted } from 'vue';

import BudgetFilters from '@/components/budgets/BudgetFilters.vue';
import BudgetTable from '@/components/budgets/BudgetTable.vue';
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

const confirmCreateOrder = async (budget) => {
  const result = await window.Swal.fire({
    title: 'Criar pedido?',
    html: 'Revise se as medidas, quantidades, modelos, endereço e demais informações estão corretas antes de continuar.',
    icon: 'info',
    confirmButtonText: 'Criar pedido',
    cancelButtonText: 'Cancelar',
    showCancelButton: true,
  });

  if (result.isConfirmed) createOrder(budget);
};

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
