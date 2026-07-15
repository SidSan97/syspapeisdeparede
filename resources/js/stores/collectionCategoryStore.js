import { ref } from 'vue';
import { defineStore } from 'pinia';

import { collectionService } from '@/modules/settings/collections/services/collectionService';

export const useCollectionCategoryStore = defineStore('collection-categories', () => {
  const categoriesMap = ref({});
  const categoryIds = ref([]);
  const loadingList = ref(false);
  const loadingItem = ref(false);
  const error = ref(null);

  const getAllCategories = () => categoryIds.value.map((id) => categoriesMap.value[id]);

  const getCategoryById = (id) => categoriesMap.value[id] ?? null;

  const getRootCategories = () => {
    return categoryIds.value.map((id) => categoriesMap.value[id]).filter((item) => !item.parent_id);
  };

  const getChildrenByParentId = (parentId) => {
    const parent = categoriesMap.value[parentId];

    return parent?.children ?? [];
  };

  async function loadCategories(force = false) {
    if (loadingList.value || (categoryIds.value.length && !force)) {
      return;
    }

    loadingList.value = true;
    error.value = null;

    try {
      const { data } = await collectionService.all({ tree: true });

      const items = Array.isArray(data) ? data : [];

      const map = {};
      const ids = [];

      for (const item of items) {
        const id = Number(item.id);

        map[id] = item;
        ids.push(id);
      }

      categoriesMap.value = map;
      categoryIds.value = ids;
    } catch (err) {
      error.value = 'Erro ao carregar categorias';

      throw err;
    } finally {
      loadingList.value = false;
    }
  }

  async function loadCategory(id, force = false) {
    if (!id) return;

    // Já carregado
    if (categoriesMap.value[id] && !force) {
      return categoriesMap.value[id];
    }

    if (loadingItem.value) return;

    loadingItem.value = true;
    error.value = null;

    try {
      const { data } = await collectionService.get(id);

      const item = data?.data ?? data;

      if (item) {
        categoriesMap.value[item.id] = item;

        if (!categoryIds.value.includes(item.id)) {
          categoryIds.value.push(item.id);
        }
      }

      return item;
    } catch (err) {
      error.value = 'Erro ao carregar categoria';

      throw err;
    } finally {
      loadingItem.value = false;
    }
  }

  function clearCache() {
    categoriesMap.value = {};
    categoryIds.value = [];
  }

  return {
    /* state */
    categoriesMap,
    categoryIds,

    loadingList,
    loadingItem,

    error,

    /* getters */
    getAllCategories,
    getCategoryById,
    getRootCategories,
    getChildrenByParentId,

    /* actions */
    loadCategories,
    loadCategory,

    clearCache,
  };
});
