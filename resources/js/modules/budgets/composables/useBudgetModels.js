import { ref, computed, reactive } from 'vue';
import { useBudgetService } from '../services/budgetService';

/**
 * Composable para gerenciamento de modelos de coleção
 */
export function useBudgetModels(budget) {
    const budgetService = useBudgetService();
    const productModels = ref([]);
    const modelsLoading = ref(false);
    const modelsError = ref(null);
    const modelSlides = reactive({});

    const productModelsMap = computed(() => {
        const map = new Map();
        productModels.value.forEach((model) => {
            map.set(model.id, model);
        });
        return map;
    });

    const getModelById = (id) => productModelsMap.value.get(id);

    async function fetchCollectionModels() {
        modelsLoading.value = true;
        modelsError.value = null;

        try {
            const normalized = await budgetService.getCollectionModels();
            const availableIds = new Set(normalized.map((item) => item.id));

            productModels.value = normalized;

            // Initialize or clamp carousel indices
            normalized.forEach((model) => {
                if (typeof modelSlides[model.id] !== 'number' || Number.isNaN(modelSlides[model.id])) {
                    modelSlides[model.id] = 0;
                } else {
                    const maxIndex = Math.max(0, model.files.length - 1);
                    modelSlides[model.id] = Math.min(Math.max(modelSlides[model.id], 0), maxIndex);
                }
            });

            // Remove slide states for removed models
            Object.keys(modelSlides).forEach((id) => {
                const numericId = Number(id);
                if (!availableIds.has(numericId)) {
                    delete modelSlides[id];
                }
            });

            budget.rooms.forEach((room) => {
                room.walls.forEach((wall) => {
                    if (wall.model && !availableIds.has(wall.model)) {
                        wall.model = null;
                    }
                });
            });
        } catch (error) {
            modelsError.value =
                error?.response?.data?.message ||
                'Não foi possível carregar os modelos. Tente novamente.';
        } finally {
            modelsLoading.value = false;
        }
    }

    return {
        productModels,
        modelsLoading,
        modelsError,
        modelSlides,
        productModelsMap,
        getModelById,
        fetchCollectionModels
    };
}
