/**
 * Normaliza método de pagamento como na API (installment → credit_card).
 * @param {string|null|undefined} method
 * @returns {string|null}
 */
export function normalizePaymentMethod(method) {
  if (method == null || method === '') {
    return null;
  }
  return method === 'installment' ? 'credit_card' : method;
}

function digitsOnly(value) {
  return String(value ?? '').replace(/\D/g, '');
}

function normalizeCep(cep) {
  return digitsOnly(cep);
}

/**
 * Valida se os pedidos selecionados podem ser juntos (regras alinhadas ao backend).
 * @param {object[]} selectedOrders
 * @returns {string|null} mensagem de erro ou null se ok
 */
export function validateMergeOrdersSelection(selectedOrders) {
  if (!Array.isArray(selectedOrders) || selectedOrders.length < 2) {
    return 'Selecione pelo menos dois pedidos.';
  }

  const first = selectedOrders[0];

  const tenantIds = new Set(selectedOrders.map((o) => o.tenant_id ?? null));
  if (tenantIds.size > 1) {
    return 'Os pedidos devem pertencer ao mesmo revendedor.';
  }

  for (const o of selectedOrders) {
    if (Number(o.paid) === 1) {
      return 'Não é possível juntar pedidos já pagos.';
    }
    const st = String(o.status ?? '').toLowerCase();
    if (st === 'cancelado') {
      return 'Não é possível juntar pedidos cancelados.';
    }
  }

  const pay0 = normalizePaymentMethod(first.payment_method);
  for (const o of selectedOrders) {
    if (normalizePaymentMethod(o.payment_method) !== pay0) {
      return 'Todos os pedidos devem ter o mesmo tipo de pagamento.';
    }
  }

  if (pay0 === 'credit_card') {
    const inst0 = Number(first.installments ?? 0);
    const limit0 = Number(first.installment_limit ?? 0);
    for (const o of selectedOrders) {
      if (Number(o.installments ?? 0) !== inst0) {
        return 'No cartão parcelado, todos os pedidos devem ter o mesmo número de parcelas.';
      }
      if (Number(o.installment_limit ?? 0) !== limit0) {
        return 'No cartão parcelado, todos os pedidos devem ter o mesmo limite de parcelas.';
      }
    }
  }

  const name0 = first.selected_carrier_name ?? null;
  const price0 = Number(first.selected_carrier_price ?? 0);
  const time0 = Number(first.selected_carrier_delivery_time ?? 0);
  const cep0 = normalizeCep(first.cep);

  for (const o of selectedOrders) {
    if ((o.selected_carrier_name ?? null) !== name0) {
      return 'Todos os pedidos devem ter exatamente a mesma transportadora (frete).';
    }
    if (Number(o.selected_carrier_price ?? 0) !== price0) {
      return 'Todos os pedidos devem ter o mesmo valor de frete.';
    }
    if (Number(o.selected_carrier_delivery_time ?? 0) !== time0) {
      return 'Todos os pedidos devem ter o mesmo prazo de frete.';
    }
    if (normalizeCep(o.cep) !== cep0) {
      return 'Todos os pedidos devem ter o mesmo CEP de frete.';
    }
  }

  const dsFlags = selectedOrders.map((o) => Number(o.dropshipping_budget ?? 0));
  const hasDs = dsFlags.some((f) => f === 1);
  const allDs = dsFlags.every((f) => f === 1);
  if (hasDs && !allDs) {
    return 'Se algum pedido tiver dropshipping, todos devem ter dropshipping.';
  }

  if (allDs) {
    const docs = selectedOrders.map((o) => digitsOnly(o.dropshipping_data?.cpf_cnpj));
    if (docs.some((d) => !d)) {
      return 'Dados de dropshipping incompletos (CPF/CNPJ) em um dos pedidos.';
    }
    const d0 = docs[0];
    if (!docs.every((d) => d === d0)) {
      return 'Com dropshipping, todos os pedidos devem ter o mesmo CPF/CNPJ.';
    }
  }

  return null;
}
