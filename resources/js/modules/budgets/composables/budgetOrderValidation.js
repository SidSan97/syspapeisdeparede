/**
 * Composable para validação do formulário de pedido de orçamento
 */
export function useBudgetOrderValidation({
  orderForm,
  orderError,
  orderExistingFiles,
  orderNewFiles,
  requiresComment,
  requiresFiles,
  requiresLink,
  requiresCollection,
  wallsRequiringCollection,
  wallSelections,
}) {
  function validateTerms() {
    if (!orderForm.termsAccepted) {
      orderError.value = 'É necessário aceitar os termos para continuar.';
      return false;
    }
    return true;
  }

  function validateComment() {
    if (requiresComment.value && !orderForm.comment.trim()) {
      orderError.value = 'Informe a descrição para prosseguir.';
      return false;
    }
    return true;
  }

  function validateLink() {
    if (requiresLink.value && !orderForm.link.trim()) {
      orderError.value = 'Informe o link de referência para prosseguir.';
      return false;
    }
    return true;
  }

  function validateCollections() {
    if (!requiresCollection.value) {
      return true;
    }

    const pendingWall = wallsRequiringCollection.value.find((wall) => {
      const selection = wallSelections[wall.key];
      return !selection?.collectionId || !selection?.imageId;
    });

    if (pendingWall) {
      orderError.value = `Selecione uma arte da coleção para ${pendingWall.roomName} - ${pendingWall.wallName}.`;
      return false;
    }

    return true;
  }

  function validateFiles() {
    if (requiresFiles.value && !orderExistingFiles.value.length && !orderNewFiles.value.length) {
      orderError.value = 'Envie pelo menos um arquivo de referência para prosseguir.';
      return false;
    }
    return true;
  }

  function validateOrder() {
    if (!validateTerms()) {
      return false;
    }

    if (!validateComment()) {
      return false;
    }

    if (!validateLink()) {
      return false;
    }

    if (!validateCollections()) {
      return false;
    }

    if (!validateFiles()) {
      return false;
    }

    return true;
  }

  return {
    validateOrder,
    validateTerms,
    validateComment,
    validateLink,
    validateCollections,
    validateFiles,
  };
}
