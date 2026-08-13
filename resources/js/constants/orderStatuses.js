export const ORDER_STATUS = {
  OPEN: 'em aberto',
  APPROVED: 'aprovado',
  IN_PRODUCTION: 'em produção',
  SENT: 'enviado',
  CANCELED: 'cancelado',
};

export const ORDER_STATUS_LABELS = {
  [ORDER_STATUS.OPEN]: 'Em aberto',
  [ORDER_STATUS.APPROVED]: 'Aprovado',
  [ORDER_STATUS.IN_PRODUCTION]: 'Em produção',
  [ORDER_STATUS.SENT]: 'Enviado',
  [ORDER_STATUS.CANCELED]: 'Cancelado',
};

export const ORDER_STATUS_COLORS = {
  [ORDER_STATUS.OPEN]: 'info',
  [ORDER_STATUS.APPROVED]: 'success',
  [ORDER_STATUS.IN_PRODUCTION]: 'info',
  [ORDER_STATUS.SENT]: 'success',
  [ORDER_STATUS.CANCELED]: 'secondary',
};

export const ORDER_STATUS_OPTIONS = Object.values(ORDER_STATUS).map((status) => ({
  value: status,
  label: ORDER_STATUS_LABELS[status],
  color: ORDER_STATUS_COLORS[status],
}));
