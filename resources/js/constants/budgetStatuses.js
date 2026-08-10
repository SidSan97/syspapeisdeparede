export const BUDGET_STATUS = Object.freeze({
  OPEN: 'em aberto',
  APPROVED: 'aprovado',
  CANCELED: 'cancelado',
});

export const BUDGET_STATUS_LABELS = {
  [BUDGET_STATUS.OPEN]: 'Em aberto',
  [BUDGET_STATUS.APPROVED]: 'Aprovado',
  [BUDGET_STATUS.CANCELED]: 'Cancelado',
};

export const BUDGET_STATUS_COLORS = {
  [BUDGET_STATUS.OPEN]: 'info',
  [BUDGET_STATUS.APPROVED]: 'success',
  [BUDGET_STATUS.CANCELED]: 'secondary',
};

export const BUDGET_STATUS_OPTIONS = Object.values(BUDGET_STATUS).map((status) => ({
  value: status,
  label: BUDGET_STATUS_LABELS[status],
  color: BUDGET_STATUS_COLORS[status],
}));
