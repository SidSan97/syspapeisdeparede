<template>
  <section class="content">
    <Page title="Coleção" :breadcrumbs="routes">
      <template #extra>
        <button type="button" class="btn btn-primary" @click="openCreateModal">
          Criar categoria
        </button>
      </template>

      <CollectionCategoryList
        :categories="categoryList"
        :is-loading-collections="loading"
        @open-modal="openEditModal"
        @delete="confirmDelete"
      />

      <CollectionCategoryFormModal
        v-model="isCategoryModalOpen"
        :collection="currentCategory"
        :parent-collection="parentCategory"
        :loading="saving"
        @close="closeModal"
        @saved="handleSave"
      />
    </Page>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';

import CollectionCategoryFormModal from '@/components/collection-categories/CollectionCategoryFormModal.vue';
import CollectionCategoryList from '@/components/collection-categories/CollectionCategoryList.vue';
import Page from '@/components/page/Page.vue';

import { useCollectionCategoryList } from '@/composables/useCollectionCategoryList';
import { useDialog } from '@/composables/useDialog';
import { useToast } from '@/composables/useToast';
import { collectionService } from '@/modules/settings/collections/services/collectionService';

const routes = [
  { path: '/settings', breadcrumbName: 'Configurações' },
  { path: '/settings/collections', breadcrumbName: 'Coleção' },
];

const dialog = useDialog();
const toast = useToast();

const { categoryList, loading, fetchCategories, deleteCategory } = useCollectionCategoryList();

const isCategoryModalOpen = ref(false);
const currentCategory = ref(null);
const parentCategory = ref(null);
const saving = ref(false);

const openCreateModal = () => {
  currentCategory.value = null;
  parentCategory.value = null;

  isCategoryModalOpen.value = true;
};

const openEditModal = (item, parent) => {
  currentCategory.value = item ?? null;

  parentCategory.value = parent ?? null;

  isCategoryModalOpen.value = true;
};

const closeModal = () => {
  currentCategory.value = null;
  parentCategory.value = null;

  isCategoryModalOpen.value = false;
};

const confirmDelete = async (item) => {
  if (!item?.id) return;

  const confirmed = await dialog.confirmDelete({
    title: 'Excluir categoria?',
    text: 'Essa operação é irreversível.',
  });

  if (!confirmed) return;

  try {
    await deleteCategory(item);
  } catch (err) {
    console.error(err);
  }
};

const handleSave = async (formData, item) => {
  saving.value = true;

  try {
    if (item?.id) {
      await collectionService.update(item.id, formData);
    } else {
      await collectionService.create(formData);
    }

    toast.success('Categoria salva com sucesso.');

    await fetchCategories(true);

    closeModal();
  } catch (err) {
    toast.error('Não foi possível salvar a categoria.');

    throw err;
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  await fetchCategories();

  document.title = 'Gerenciar Coleções';
});
</script>
