/**
 * Espelho de App\Support\OrderBudgetStatus para o frontend.
 */
export const ORDER_BUDGET_STATUS = Object.freeze({
  APPROVE_LAYOUT: 'Aprovar Layout',
  PENDING_REVIEW: 'Pendente de Revisão',
  LAYOUT_IN_APPROVAL: 'Layout em Aprovação',
  LAYOUT_APPROVED: 'Layout Aprovado',
  WAITING_ART: 'Aguardando Arte',
  WAITING_PAYMENT: 'Aguardando pagamento',
  ART_RECEIVED: 'Arte Recebida',
  RELEASED_FOR_PRODUCTION: 'Liberado para produção',
  IN_PRODUCTION: 'Em produção',
  APPROVED: 'Aprovado',
  SENT: 'Enviado',
  DELIVERED: 'Entregue',
});

/**
 * Variantes aceitas para cada status canônico.
 * @type {Record<string, string[]>}
 */
export const ORDER_BUDGET_STATUS_VARIANTS = Object.freeze({
  [ORDER_BUDGET_STATUS.APPROVE_LAYOUT]: ['Aprovar Layout', 'aprovar layout'],
  [ORDER_BUDGET_STATUS.LAYOUT_APPROVED]: ['Layout aprovado', 'layout aprovado', 'Layout Aprovado'],
  [ORDER_BUDGET_STATUS.PENDING_REVIEW]: [
    'Pendente de Revisão',
    'pendente de revisão',
    'Pendente de Revisao',
    'pendente de revisao',
  ],
  [ORDER_BUDGET_STATUS.LAYOUT_IN_APPROVAL]: [
    'Layout em Aprovação',
    'Layout em aprovação',
    'layout em aprovação',
    'Layout em aprovacao',
    'layout em aprovacao',
  ],
  [ORDER_BUDGET_STATUS.WAITING_ART]: ['Aguardando Arte', 'aguardando arte'],
  [ORDER_BUDGET_STATUS.WAITING_PAYMENT]: [
    'Aguardando pagamento',
    'aguardando pagamento',
    'Aguardando Pagamento',
  ],
  [ORDER_BUDGET_STATUS.ART_RECEIVED]: ['Arte Recebida', 'arte recebida'],
  [ORDER_BUDGET_STATUS.RELEASED_FOR_PRODUCTION]: [
    'Liberado para produção',
    'liberado para produção',
    'Liberado para producao',
    'liberado para producao',
  ],
  [ORDER_BUDGET_STATUS.IN_PRODUCTION]: [
    'Em produção',
    'em produção',
    'Em producao',
    'em producao',
    'Em Produção',
  ],
  [ORDER_BUDGET_STATUS.APPROVED]: ['Aprovado', 'aprovado'],
  [ORDER_BUDGET_STATUS.SENT]: ['Enviado', 'enviado'],
  [ORDER_BUDGET_STATUS.DELIVERED]: ['Entregue', 'entregue'],
});

export const ORDER_BUDGET_STATUS_BADGE_CLASSES = Object.freeze({
  [ORDER_BUDGET_STATUS.APPROVE_LAYOUT]: 'bg-warning text-dark',
  [ORDER_BUDGET_STATUS.PENDING_REVIEW]: 'bg-info',
  [ORDER_BUDGET_STATUS.LAYOUT_IN_APPROVAL]: 'bg-secondary',
  [ORDER_BUDGET_STATUS.LAYOUT_APPROVED]: 'bg-primary',
  [ORDER_BUDGET_STATUS.WAITING_ART]: 'bg-secondary',
  [ORDER_BUDGET_STATUS.WAITING_PAYMENT]: 'bg-warning text-dark',
  [ORDER_BUDGET_STATUS.ART_RECEIVED]: 'bg-info',
  [ORDER_BUDGET_STATUS.RELEASED_FOR_PRODUCTION]: 'bg-secondary',
  [ORDER_BUDGET_STATUS.IN_PRODUCTION]: 'bg-dark',
  [ORDER_BUDGET_STATUS.APPROVED]: 'bg-success',
  [ORDER_BUDGET_STATUS.SENT]: 'bg-success',
  [ORDER_BUDGET_STATUS.DELIVERED]: 'bg-success',
});

/**
 * @returns {string[]}
 */
export function orderBudgetStatusValues() {
  return Object.values(ORDER_BUDGET_STATUS);
}

/**
 * Todas as variantes aceitas.
 * @returns {string[]}
 */
export function allOrderBudgetStatuses() {
  return [...new Set(Object.values(ORDER_BUDGET_STATUS_VARIANTS).flat())];
}

/**
 * @param {string} status
 * @returns {string[]}
 */
export function orderBudgetStatusVariants(status) {
  const canonical = canonicalizeOrderBudgetStatus(status);

  if (!canonical) {
    return [];
  }

  return ORDER_BUDGET_STATUS_VARIANTS[canonical] ?? [];
}

/**
 * Status da fila de aprovação de layout (com variantes).
 * @returns {string[]}
 */
export function layoutApprovalQueueStatuses() {
  return [
    ...new Set([
      ...ORDER_BUDGET_STATUS_VARIANTS[ORDER_BUDGET_STATUS.APPROVE_LAYOUT],
      ...ORDER_BUDGET_STATUS_VARIANTS[ORDER_BUDGET_STATUS.PENDING_REVIEW],
      ...ORDER_BUDGET_STATUS_VARIANTS[ORDER_BUDGET_STATUS.LAYOUT_IN_APPROVAL],
    ]),
  ];
}

/**
 * Normaliza qualquer variante para o valor canônico.
 * @param {string|null|undefined} status
 * @returns {string|null}
 */
export function canonicalizeOrderBudgetStatus(status) {
  if (status == null || status === '') {
    return null;
  }

  const normalized = String(status).toLowerCase();

  for (const [canonical, variants] of Object.entries(ORDER_BUDGET_STATUS_VARIANTS)) {
    if (variants.includes(status)) {
      return canonical;
    }

    if (variants.some((variant) => variant.toLowerCase() === normalized)) {
      return canonical;
    }
  }

  return null;
}

/**
 * @param {string|null|undefined} status
 * @param {string} expected
 * @returns {boolean}
 */
export function isOrderBudgetStatus(status, expected) {
  return canonicalizeOrderBudgetStatus(status) === canonicalizeOrderBudgetStatus(expected);
}

/**
 * @param {string|null|undefined} status
 * @returns {boolean}
 */
export function isValidOrderBudgetStatus(status) {
  return canonicalizeOrderBudgetStatus(status) !== null;
}
