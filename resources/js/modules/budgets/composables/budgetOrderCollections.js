import { computed, reactive, ref, watch } from 'vue';
import { useBudgetOrderService } from '../services/budgetOrderService';

const COLLECTION_IMAGE_PLACEHOLDER =
  'https://via.placeholder.com/300x200/ced4da/212529?text=Sem+imagem';

/**
 * Composable para gerenciar coleções e seleções de parede
 */
export function useBudgetOrderCollections(budget, requiresCollection) {
  const budgetOrderService = useBudgetOrderService();

  // Estado de coleções
  const collectionList = ref([]);
  const collectionAssets = reactive({});
  const collectionLoading = ref(false);
  const collectionError = ref('');

  // Estado de seleções de parede
  const wallSelections = reactive({});

  // Termo de busca por parede (para filtrar artes pelo nome)
  const wallSearchTerms = reactive({});

  // Computed: paredes que requerem seleção de coleção
  const wallsRequiringCollection = computed(() => {
    if (!budget.value || !Array.isArray(budget.value.rooms)) {
      return [];
    }

    const walls = [];

    budget.value.rooms.forEach((room, roomIndex) => {
      const roomName = room?.name ?? `Ambiente ${roomIndex + 1}`;
      const roomId = room?.id ?? `room-${roomIndex}`;

      (room?.walls ?? []).forEach((wall, wallIndex) => {
        const collectionModel =
          wall?.collection_model ??
          wall?.collectionModel ??
          null;

        if (!collectionModel) {
          return;
        }

        const requires =
          collectionModel?.request_collection ??
          collectionModel?.requestCollection ??
          collectionModel?.requests?.collection ??
          false;

        if (!requires) {
          return;
        }

        const wallName = wall?.name ?? `Parede ${wallIndex + 1}`;
        const wallId = wall?.id ?? `wall-${wallIndex}`;

        walls.push({
          key: `${roomId}-${wallId}`,
          roomName,
          wallName,
        });
      });
    });

    return walls;
  });

  /**
   * Sincroniza as seleções de parede com as paredes que requerem coleção
   */
  function syncWallSelections() {
    const requiredKeys = new Set(wallsRequiringCollection.value.map((wall) => wall.key));

    requiredKeys.forEach((key) => {
      if (!wallSelections[key]) {
        wallSelections[key] = {
          collectionId: null,
          imageId: null,
        };
      }
    });

    Object.keys(wallSelections).forEach((key) => {
      if (!requiredKeys.has(key)) {
        delete wallSelections[key];
      }
    });
  }

  /**
   * Obtém o estado de uma coleção específica
   */
  function getCollectionState(collectionId) {
    if (!collectionId) {
      return {
        loading: false,
        items: [],
        error: '',
      };
    }

    if (!collectionAssets[collectionId]) {
      collectionAssets[collectionId] = {
        loading: false,
        items: [],
        error: '',
      };
    }

    return collectionAssets[collectionId];
  }

  /**
   * Normaliza um resumo de coleção
   */
  function normalizeCollectionSummary(item = {}) {
    const rawId = item.id ?? null;
    const numericId = rawId === null ? null : Number(rawId);
    const finalId = Number.isNaN(numericId) ? null : numericId;
    const name = (item.name ?? '').toString().trim();

    return {
      id: finalId,
      name: name.length ? name : 'Coleção sem nome',
    };
  }

  /**
   * Normaliza uma imagem de coleção
   */
  function normalizeCollectionImage(image = {}) {
    const resolvedUrl =
      image.url ??
      resolveStorageUrl(image.path_name ?? image.pathName ?? '') ??
      COLLECTION_IMAGE_PLACEHOLDER;

    const rawId =
      image.id ??
      image.collection_image_id ??
      image.collectionImageId ??
      null;

    const numericId = rawId === null ? null : Number(rawId);
    const finalId = Number.isNaN(numericId) ? null : numericId;

    const finalUrl = !resolvedUrl || resolvedUrl === '#' ? COLLECTION_IMAGE_PLACEHOLDER : resolvedUrl;

    const nameStr = image.name != null ? String(image.name).trim() : '';
    const displayName =
      nameStr ||
      (image.path_name != null ? image.path_name : image.pathName) ||
      `Imagem #${image.id != null ? image.id : ''}`;

    return {
      id: finalId,
      url: finalUrl,
      title: displayName,
      name: displayName,
    };
  }

  /**
   * Resolve a URL de armazenamento
   */
  function resolveStorageUrl(path) {
    if (!path) {
      return '#';
    }

    if (/^https?:\/\//i.test(path)) {
      return path;
    }

    const baseUrl = window.location.origin.replace(/\/$/, '');

    return `${baseUrl}/storage/${String(path).replace(/^storage\//, '')}`;
  }

  /**
   * Carrega as coleções disponíveis
   */
  async function ensureCollectionsLoaded() {
    if (collectionLoading.value || collectionList.value.length) {
      return;
    }

    collectionLoading.value = true;
    collectionError.value = '';

    try {
      const rootCategories = await budgetOrderService.getCollectionCategories();

      collectionList.value = Array.isArray(rootCategories)
        ? rootCategories.map(normalizeCollectionSummary).filter((item) => item.id !== null)
        : [];
    } catch (error) {
      collectionList.value = [];
      collectionError.value =
        'Não foi possível carregar as coleções. Atualize a página e tente novamente.';
    } finally {
      collectionLoading.value = false;
    }
  }

  /**
   * Carrega as imagens de uma categoria de coleção
   */
  async function ensureCollectionAssets(categoryId) {
    if (!categoryId) {
      return;
    }

    const state = getCollectionState(categoryId);

    if (state.loading || state.items.length || state.error) {
      return;
    }

    state.loading = true;
    state.error = '';

    try {
      const images = await budgetOrderService.getCollectionCategoryImages(categoryId);

      state.items = Array.isArray(images)
        ? images.map(normalizeCollectionImage)
        : [];
    } catch (error) {
      state.error = 'Não foi possível carregar as imagens desta coleção.';
    } finally {
      state.loading = false;
    }
  }

  function getFilteredCollectionItems(wallKey) {
    const selection = wallSelections[wallKey];
    if (!selection?.collectionId) {
      return [];
    }
    const state = getCollectionState(selection.collectionId);
    const items = state.items ?? [];
    const term = (wallSearchTerms[wallKey] ?? '').toString().trim().toLowerCase();
    if (!term) {
      return items;
    }
    return items.filter(
      (img) =>
        (img.name ?? img.title ?? '').toLowerCase().includes(term)
    );
  }

  function setWallSearchTerm(wallKey, value) {
    wallSearchTerms[wallKey] = value;
  }

  /**
   * Manipula a mudança de seleção de coleção para uma parede
   */
  function handleCollectionSelectionChange(wallKey) {
    const selection = wallSelections[wallKey];

    if (!selection) {
      return;
    }

    selection.imageId = null;
    wallSearchTerms[wallKey] = '';

    if (selection.collectionId) {
      ensureCollectionAssets(selection.collectionId);
    }
  }

  /**
   * Seleciona uma imagem de coleção para uma parede
   */
  function selectCollectionImage(wallKey, imageId) {
    const selection = wallSelections[wallKey];

    if (!selection) {
      return;
    }

    selection.imageId = imageId;
  }

  /**
   * Manipula erros ao carregar imagens de coleção
   */
  function handleCollectionImageError(event) {
    event.target.src = COLLECTION_IMAGE_PLACEHOLDER;
  }

  /**
   * Reseta as seleções de parede
   */
  function resetWallSelections() {
    Object.keys(wallSelections).forEach((key) => {
      delete wallSelections[key];
    });
    Object.keys(wallSearchTerms).forEach((key) => {
      delete wallSearchTerms[key];
    });
  }

  // Watchers
  watch(
    [requiresCollection, () => budget.value],
    ([shouldLoad, budgetData]) => {
      if (shouldLoad && budgetData) {
        syncWallSelections();
        ensureCollectionsLoaded();
      }
    },
    { immediate: true },
  );

  watch(
    wallsRequiringCollection,
    () => {
      if (requiresCollection.value) {
        syncWallSelections();
      }
    },
    { deep: true },
  );

  return {
    // Estado
    collectionList,
    collectionAssets,
    collectionLoading,
    collectionError,
    wallSelections,
    wallSearchTerms,

    // Computed
    wallsRequiringCollection,

    // Funções
    syncWallSelections,
    getCollectionState,
    getFilteredCollectionItems,
    setWallSearchTerm,
    ensureCollectionsLoaded,
    ensureCollectionAssets,
    handleCollectionSelectionChange,
    selectCollectionImage,
    handleCollectionImageError,
    resetWallSelections,
  };
}

