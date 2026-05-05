export function useBudgetList() {
  function normalizeBudget(budget) {
    if (!budget) {
      return {
        id: null,
        name: '',
        total_amount: 0,
        total_amount_installments: 0,
        delivery_time: null,
        status: null,
        rooms: [],
        comment_referring_model: '',
        commentReferringModel: '',
        link_referring_model: '',
        linkReferringModel: '',
        files_referring_model: [],
        filesReferringModel: [],
        collection_referring_model: null,
        collectionReferringModel: null,
      };
    }

    const totalAmount = budget.total_amount ?? budget.totalAmount ?? 0;
    const totalAmountInstallments =
      budget.total_amount_installments ?? budget.totalAmountInstallments ?? 0;
    const deliveryTime = budget.delivery_time ?? budget.deliveryTime ?? null;
    const status = budget.status ?? budget.Status ?? null;
    const commentRef = budget.comment_referring_model ?? budget.commentReferringModel ?? '';
    const linkRef = budget.link_referring_model ?? budget.linkReferringModel ?? '';
    const filesRef = Array.isArray(budget.files_referring_model)
      ? budget.files_referring_model
      : Array.isArray(budget.filesReferringModel)
        ? budget.filesReferringModel
        : [];
    const collectionRef =
      budget.collection_referring_model ?? budget.collectionReferringModel ?? null;
    const rooms = Array.isArray(budget.rooms) ? budget.rooms : [];

    return {
      ...budget,
      name: budget.name ?? '',
      total_amount: totalAmount,
      total_amount_installments: totalAmountInstallments,
      delivery_time: deliveryTime,
      status,
      rooms,
      comment_referring_model: commentRef,
      commentReferringModel: commentRef,
      link_referring_model: linkRef,
      linkReferringModel: linkRef,
      files_referring_model: filesRef,
      filesReferringModel: filesRef,
      collection_referring_model: collectionRef,
      collectionReferringModel: collectionRef,
    };
  }

  return {
    normalizeBudget,
  };
}
