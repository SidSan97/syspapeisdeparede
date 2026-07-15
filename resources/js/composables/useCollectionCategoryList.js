import { computed } from 'vue';
import { useToast } from '@/composables/useToast';
import { useCollectionCategoryStore } from '@/stores/collectionCategoryStore';
import { collectionService } from '@/modules/settings/collections/services/collectionService';

export function useCollectionCategoryList() {
  const toast = useToast();
  const store = useCollectionCategoryStore();

  const categoryList = computed(() => store.getAllCategories());
  const loading = computed(() => store.loadingList);
  const error = computed(() => store.error);

  const fetchCategories = async (force = false) => {
    try {
      await store.loadCategories(force);
    } catch (err) {
      toast.error('Não foi possível carregar as categorias.');

      throw err;
    }
  };

  const deleteCategory = async (category) => {
    if (!category?.id) return;

    const id = category.id;

    try {
      await collectionService.delete(id);

      toast.success('Categoria excluída com sucesso.');

      // Recarrega lista
      await store.loadCategories(true);
    } catch (err) {
      const msg = err?.response?.data?.message ?? 'Não foi possível excluir a categoria.';

      toast.error(msg);

      throw err;
    }
  };

  const deleteCategoryOptimistic = async (category) => {
    if (!category?.id) return;

    const id = category.id;

    // Backup
    const backup = store.categoriesMap[id];

    try {
      // Remove localmente
      delete store.categoriesMap[id];

      store.categoryIds = store.categoryIds.filter((cid) => cid !== id);

      await collectionService.delete(id);

      toast.success('Categoria excluída com sucesso.');
    } catch (err) {
      // Rollback
      if (backup) {
        store.categoriesMap[id] = backup;

        store.categoryIds.push(id);
      }

      toast.error('Não foi possível excluir a categoria.');

      throw err;
    }
  };

  return {
    /* state */
    categoryList,
    loading,
    error,

    /* actions */
    fetchCategories,
    deleteCategory,

    // opcional
    deleteCategoryOptimistic,
  };
}
