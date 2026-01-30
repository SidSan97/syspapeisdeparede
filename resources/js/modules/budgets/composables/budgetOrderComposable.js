import { computed, reactive, ref } from 'vue';
import { useBudgetOrderService } from '../services/budgetOrderService';
import { useBudgetOrderCollections } from './budgetOrderCollections';
import { useBudgetOrderValidation } from './budgetOrderValidation';

/**
 * Composable para gerenciar o estado e lógica do formulário de pedido de orçamento
 */
export function useBudgetOrderComposable(budget) {
  const budgetOrderService = useBudgetOrderService();

  // Estado do formulário global (termos)
  const orderForm = reactive({
    termsAccepted: false,
  });

  // Estado de formulários por parede
  const wallForms = reactive({});

  const orderSubmitting = ref(false);
  const orderError = ref('');
  const orderFileInputs = reactive({});

  // Formatação de moeda
  const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  });

  // Computed properties
  const orderSummary = computed(() => {
    if (!budget.value) {
      return null;
    }

    const rawTotal = Number(budget.value.total_amount ?? budget.value.totalAmount ?? 0);
    const total = Number.isFinite(rawTotal) ? rawTotal : 0;

    return {
      id: budget.value.id,
      name: budget.value.name ?? 'Não informado',
      formattedTotal: formatCurrency(total),
      deliveryTime: formatDeliveryTime(budget.value.delivery_time),
      status: budget.value.status ?? null,
    };
  });

  const orderCollectionModels = computed(() => {
    if (!budget.value) {
      return [];
    }

    return extractCollectionModelsFromBudget(budget.value);
  });

  const requiresComment = computed(() =>
    wallsWithRequirements.value.some((wall) => wall.requiresComment),
  );
  const requiresFiles = computed(() =>
    wallsWithRequirements.value.some((wall) => wall.requiresFiles),
  );
  const requiresLink = computed(() =>
    wallsWithRequirements.value.some((wall) => wall.requiresLink),
  );
  const requiresCollection = computed(() =>
    wallsWithRequirements.value.some((wall) => wall.requiresCollection),
  );

  // Computed: paredes com requisitos
  const wallsWithRequirements = computed(() => {
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

        const wallName = wall?.name ?? `Parede ${wallIndex + 1}`;
        const wallId = wall?.id ?? `wall-${wallIndex}`;
        const wallKey = `${roomId}-${wallId}`;

        const requiresComment =
          collectionModel?.request_comment ??
          collectionModel?.requestComment ??
          false;

        const requiresLink =
          collectionModel?.request_link ??
          collectionModel?.requestLink ??
          false;

        const requiresFiles =
          collectionModel?.request_file ??
          collectionModel?.requestFile ??
          false;

        const requiresCollection =
          collectionModel?.request_collection ??
          collectionModel?.requestCollection ??
          false;

        // Normalizar valores booleanos
        const normalizeBool = (value) => {
          if (typeof value === 'boolean') return value;
          if (typeof value === 'string') return value === 'true' || value === '1';
          if (typeof value === 'number') return value === 1 || value === 3;
          return Boolean(value);
        };

        const hasRequirements =
          normalizeBool(requiresComment) ||
          normalizeBool(requiresLink) ||
          normalizeBool(requiresFiles) ||
          normalizeBool(requiresCollection);

        if (hasRequirements) {
          walls.push({
            key: wallKey,
            id: wallId,
            wallId: wall.id,
            roomId: roomId,
            roomName,
            wallName,
            collectionModel,
            requiresComment: normalizeBool(requiresComment),
            requiresLink: normalizeBool(requiresLink),
            requiresFiles: normalizeBool(requiresFiles),
            requiresCollection: normalizeBool(requiresCollection),
            existingComment: wall?.comment_referring_model ?? wall?.commentReferringModel ?? '',
            existingLink: wall?.link_referring_model ?? wall?.linkReferringModel ?? '',
            existingFiles: Array.isArray(wall?.files_referring_model)
              ? wall.files_referring_model
              : Array.isArray(wall?.filesReferringModel)
                ? wall.filesReferringModel
                : [],
          });
        }
      });
    });

    return walls;
  });

  const orderHasRequirements = computed(() => wallsWithRequirements.value.length > 0);

  // Usar o composable de coleções
  const {
    collectionList,
    collectionAssets,
    collectionLoading,
    collectionError,
    wallSelections,
    wallsRequiringCollection,
    syncWallSelections,
    getCollectionState,
    ensureCollectionsLoaded,
    ensureCollectionAssets,
    handleCollectionSelectionChange,
    selectCollectionImage,
    handleCollectionImageError,
    resetWallSelections,
  } = useBudgetOrderCollections(budget, requiresCollection);

  // Funções auxiliares de formatação
  function formatCurrency(value) {
    if (value === null || value === undefined) {
      return currencyFormatter.format(0);
    }

    const numericValue = Number(value);
    return currencyFormatter.format(Number.isFinite(numericValue) ? numericValue : 0);
  }

  function formatDeliveryTime(days) {
    if (!days) {
      return 'Não informado';
    }

    return `${days} ${days === 1 ? 'dia' : 'dias'}`;
  }

  // Funções de extração de dados
  function extractCollectionModelsFromBudget(budgetData) {
    if (!budgetData || !Array.isArray(budgetData.rooms)) {
      return [];
    }

    const collection = new Map();

    budgetData.rooms.forEach((room) => {
      const walls = Array.isArray(room?.walls) ? room.walls : [];

      walls.forEach((wall) => {
        const id =
          wall?.collection_model_id ??
          wall?.collectionModelId ??
          wall?.collection_model?.id ??
          null;

        if (id === null || id === undefined || id === '') {
          return;
        }

        if (!collection.has(id)) {
          collection.set(id, {
            id: String(id),
            name:
              wall?.collection_model_name ??
              wall?.collection_model?.model_type?.name ??
              wall?.collectionModelName ??
              `Modelo ${id}`,
          });
        }
      });
    });

    return Array.from(collection.values());
  }

  function hasRequirement(field) {
    if (!budget.value || !Array.isArray(budget.value.rooms)) {
      return false;
    }

    return budget.value.rooms.some((room) => {
      const walls = Array.isArray(room?.walls) ? room.walls : [];

      return walls.some((wall) => {
        const collectionModel = wall?.collection_model ?? wall?.collectionModel ?? null;

        if (!collectionModel || !(field in collectionModel)) {
          return false;
        }

        const value = collectionModel[field];

        if (typeof value === 'boolean') {
          return value;
        }

        if (typeof value === 'string') {
          return value === 'true' || value === '1';
        }

        if (typeof value === 'number') {
          return value === 1 || value === 3;
        }

        return Boolean(value);
      });
    });
  }

  // Funções de manipulação de arquivos por parede
  function handleOrderFilesChange(wallKey, event) {
    const files = event?.target?.files ? Array.from(event.target.files) : [];

    if (!wallForms[wallKey]) {
      wallForms[wallKey] = createWallForm(wallKey);
    }

    wallForms[wallKey].files = files;

    if (event?.target) {
      event.target.value = '';
    }
  }

  function removeNewFile(wallKey, index) {
    if (!wallForms[wallKey] || !Array.isArray(wallForms[wallKey].files)) {
      return;
    }

    wallForms[wallKey].files = wallForms[wallKey].files.filter(
      (_, fileIndex) => fileIndex !== index,
    );
  }

  function getWallNewFiles(wallKey) {
    if (!wallForms[wallKey] || !Array.isArray(wallForms[wallKey].files)) {
      return [];
    }
    return wallForms[wallKey].files;
  }

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

  function extractFileName(path) {
    if (!path) {
      return '';
    }

    const segments = String(path).split('/');
    return segments[segments.length - 1] ?? path;
  }

  // Função auxiliar para criar formulário de parede
  function createWallForm(wallKey) {
    const wall = wallsWithRequirements.value.find((w) => w.key === wallKey);

    if (!wall) {
      return {
        comment: '',
        link: '',
        files: [],
      };
    }

    return {
      comment: wall.existingComment ?? '',
      link: wall.existingLink ?? '',
      files: [],
    };
  }

  // Funções de inicialização e reset
  function initializeForm() {
    orderForm.termsAccepted = false;
    orderError.value = '';
    orderSubmitting.value = false;

    // Inicializar formulários para cada parede
    wallsWithRequirements.value.forEach((wall) => {
      wallForms[wall.key] = createWallForm(wall.key);

      // Limpar inputs de arquivo
      if (orderFileInputs[wall.key] && orderFileInputs[wall.key].value) {
        orderFileInputs[wall.key].value = '';
      }
    });

    // Remover formulários de paredes que não existem mais
    Object.keys(wallForms).forEach((key) => {
      if (!wallsWithRequirements.value.find((w) => w.key === key)) {
        delete wallForms[key];
      }
    });

    if (requiresCollection.value) {
      syncWallSelections();
      ensureCollectionsLoaded();
    }
  }

  function resetForm() {
    orderForm.termsAccepted = false;
    orderError.value = '';
    orderSubmitting.value = false;

    // Resetar formulários de cada parede
    Object.keys(wallForms).forEach((key) => {
      const wall = wallsWithRequirements.value.find((w) => w.key === key);
      if (wall) {
        wallForms[key] = createWallForm(key);
      } else {
        delete wallForms[key];
      }

      if (orderFileInputs[key] && orderFileInputs[key].value) {
        orderFileInputs[key].value = '';
      }
    });

    resetWallSelections();
  }

  // Validação customizada para formulários por parede
  function validateOrder() {
    if (!orderForm.termsAccepted) {
      orderError.value = 'É necessário aceitar os termos para continuar.';
      return false;
    }

    // Validar cada parede
    for (const wall of wallsWithRequirements.value) {
      const form = wallForms[wall.key];

      if (!form) {
        continue;
      }

      if (wall.requiresComment && !form.comment.trim()) {
        orderError.value = `Informe a descrição para ${wall.roomName} - ${wall.wallName}.`;
        return false;
      }

      if (wall.requiresLink && !form.link.trim()) {
        orderError.value = `Informe o link de referência para ${wall.roomName} - ${wall.wallName}.`;
        return false;
      }

      if (wall.requiresFiles && !wall.existingFiles.length && !form.files.length) {
        orderError.value = `Envie pelo menos um arquivo de referência para ${wall.roomName} - ${wall.wallName}.`;
        return false;
      }

      if (wall.requiresCollection) {
        const selection = wallSelections[wall.key];
        if (!selection?.collectionId || !selection?.imageId) {
          orderError.value = `Selecione uma arte da coleção para ${wall.roomName} - ${wall.wallName}.`;
          return false;
        }
      }
    }

    return true;
  }

  async function submitOrder() {
    if (!budget.value?.id) {
      return null;
    }

    if (!validateOrder()) {
      return null;
    }

    orderSubmitting.value = true;
    orderError.value = '';

    try {
      const formData = new FormData();
      formData.append('id', budget.value.id);

      // Enviar dados por parede
      wallsWithRequirements.value.forEach((wall) => {
        const form = wallForms[wall.key];

        if (!form) {
          return;
        }

        const wallId = wall.wallId;

        if (wall.requiresComment && form.comment) {
          formData.append(`walls[${wallId}][comment_referring_model]`, form.comment);
        }

        if (wall.requiresLink && form.link) {
          formData.append(`walls[${wallId}][link_referring_model]`, form.link);
        }

        if (wall.requiresFiles && form.files.length) {
          form.files.forEach((file) => {
            formData.append(`walls[${wallId}][files_referring_model][]`, file);
          });
        }

        if (wall.requiresCollection) {
          const selection = wallSelections[wall.key];
          if (selection?.imageId) {
            formData.append(`walls[${wallId}][collection_referring_model]`, selection.imageId);
          }
        }
      });

      formData.append('terms_accepted', orderForm.termsAccepted ? '1' : '0');

      const payload = await budgetOrderService.placeOrder(formData);

      if (!payload) {
        throw new Error('Resposta inválida do servidor.');
      }

      let orderId = payload.order_id;

      if (!orderId) {
        throw new Error('ID do pedido não encontrado na resposta.');
      }

      return { orderId, payload };
    } catch (error) {
      const firstError = error.response?.data?.errors
        ? Object.values(error.response.data.errors).flat().shift()
        : null;

      orderError.value =
        firstError ??
        error.response?.data?.message ??
        error.message ??
        'Não foi possível realizar o pedido. Tente novamente.';

      throw error;
    } finally {
      orderSubmitting.value = false;
    }
  }

  return {
    // Estado
    orderForm,
    wallForms,
    orderSubmitting,
    orderError,
    orderFileInputs,
    collectionList,
    collectionAssets,
    collectionLoading,
    collectionError,
    wallSelections,

    // Computed
    orderSummary,
    orderCollectionModels,
    requiresComment,
    requiresFiles,
    requiresLink,
    requiresCollection,
    orderHasRequirements,
    wallsWithRequirements,
    wallsRequiringCollection,

    // Funções
    initializeForm,
    resetForm,
    handleOrderFilesChange,
    removeNewFile,
    getWallNewFiles,
    resolveStorageUrl,
    extractFileName,
    getCollectionState,
    ensureCollectionsLoaded,
    ensureCollectionAssets,
    handleCollectionSelectionChange,
    selectCollectionImage,
    handleCollectionImageError,
    validateOrder,
    submitOrder,
  };
}

