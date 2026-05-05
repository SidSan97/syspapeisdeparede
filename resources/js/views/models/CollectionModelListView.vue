<template>
  <section class="content">
    <Page title="Modelos" :breadcrumbs="routes">
      <template #extra>
        <router-link class="btn btn-primary" :to="{ name: 'settings.models.create' }">
          Criar modelo
        </router-link>
      </template>
      <CollectionModelTable :models="models" :loading="isLoading" @delete="confirmDelete" />
    </Page>
  </section>
</template>

<script setup>
import CollectionModelTable from '@/components/collection-models/CollectionModelTable.vue';
import Page from '@/components/page/Page.vue';
import { useCollectionModelList } from '@/composables/useCollectionModelList';
import { useDialog } from '@/composables/useDialog';
import { onMounted } from 'vue';

const dialog = useDialog();

const { models, isLoading, fetchModels, deleteModel } = useCollectionModelList();

const routes = [
  {
    path: '/settings',
    breadcrumbName: 'Configurações',
  },
  {
    path: '/settings/models',
    breadcrumbName: 'Modelos',
  },
];

const confirmDelete = async (user) => {
  const confirmed = await dialog.confirmDelete({
    title: 'Excluir modelo?',
    text: 'Tem certeza que deseja excluir o modelo? Esta ação não pode ser desfeita.',
  });

  if (confirmed) deleteModel(user);
};

onMounted(() => {
  fetchModels();
});
</script>
