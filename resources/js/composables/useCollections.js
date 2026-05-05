import { computed } from 'vue';
import { useCollectionCategoryStore } from '@/stores/collectionCategoryStore';

export function useCollections() {
  const collectionCategoryStore = useCollectionCategoryStore();

  const categoryList = computed(() => collectionCategoryStore.categories?.data || []);
  const loading = computed(() => collectionCategoryStore.loadingCategories);

  const loadCollections = async () => {
    return await collectionCategoryStore.loadCategories();
  };

  return {
    categoryList,
    loading,
    loadCollections,
  };
}
