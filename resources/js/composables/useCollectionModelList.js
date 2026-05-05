import { computed } from 'vue';
import { useToast } from '@/composables/useToast';
import { useCollectionModelStore } from '@/stores/collectionModelStore';
import { collectionModelService } from '@/services/collectionModelService';

export function useCollectionModelList() {
  const toast = useToast();
  const collectionModelStore = useCollectionModelStore();

  const models = computed(() => collectionModelStore.collectionModels?.data || []);
  const isLoading = computed(() => collectionModelStore.loadingCollectionModels);

  async function fetchModels() {
    collectionModelStore.loadCollectionModels();
  }

  async function deleteModel(model) {
    if (!model?.id) return;

    try {
      await collectionModelService.delete(model.id);

      await fetchModels();

      toast.success('Modelo excluído com sucesso');
    } catch (error) {
      const message =
        error?.response?.data?.message ?? 'Erro ao excluir o modelo. Tente novamente.';

      toast.error(message);
    }
  }

  return {
    // State
    models,
    isLoading,

    // Methods
    fetchModels,
    deleteModel,
  };
}
