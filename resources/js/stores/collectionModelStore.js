import { ref } from 'vue';
import { defineStore } from 'pinia';
import { collectionModelService } from '@/services/collectionModelService';

export const useCollectionModelStore = defineStore('collectionModels', () => {
  const collectionModels = ref({
    data: [],
  });
  const currentCollectionModel = ref(null);

  const loadingCollectionModels = ref(false);
  const loadingCollectionModelById = ref(false);
  const error = ref(null);

  async function loadCollectionModels(params = {}) {
    if (loadingCollectionModels.value) return; // Prevent concurrent calls

    loadingCollectionModels.value = true;
    error.value = null;

    try {
      const response = await collectionModelService.all(params);
      collectionModels.value = response;
    } catch (e) {
      error.value = 'Erro ao carregar usuários';
      throw e;
    } finally {
      loadingCollectionModels.value = false;
    }
  }

  async function loadCollectionModelById(id) {
    loadingCollectionModelById.value = true;
    error.value = null;

    try {
      currentCollectionModel.value = await collectionModelService.find(id);
    } catch (e) {
      error.value = 'Erro ao carregar usuário';
      throw e;
    } finally {
      loadingCollectionModelById.value = false;
    }
  }

  function clearCurrentCollectionModel() {
    currentCollectionModel.value = null;
  }

  return {
    collectionModels,
    currentCollectionModel,

    loadingCollectionModels,
    loadingCollectionModelById,
    error,

    loadCollectionModels,
    loadCollectionModelById,
    clearCurrentCollectionModel,
  };
});
