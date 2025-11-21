<template>
  <Teleport v-if="visible" to="body">
    <div>
      <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Detalhes do orçamento</h5>
              <button type="button" class="btn-close" aria-label="Close" @click="handleClose"></button>
            </div>
            <div class="modal-body">
              <div v-if="details" class="budget-details">
                <div class="border rounded p-3 bg-body-secondary">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Identificador</span>
                    <span class="badge bg-secondary">#{{ details.id }}</span>
                  </div>
                  <div class="fw-semibold fs-5">{{ details.name }}</div>
                  <div class="text-muted small mt-2">
                    Prazo de entrega: {{ details.deliveryTime }}
                  </div>
                  <div v-if="details.status" class="text-muted small">
                    Status: {{ details.status }}
                  </div>
                </div>

                <!-- Valores do Orçamento -->
                <div class="border rounded p-3 mt-3">
                  <h6 class="fw-semibold mb-3">Valores do Orçamento</h6>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <div class="text-muted small">Total à Vista</div>
                      <div class="fw-semibold fs-5 text-success">{{ details.totalVistaFormatted }}</div>
                    </div>
                    <div class="col-md-6">
                      <div class="text-muted small">Total a Prazo</div>
                      <div class="fw-semibold fs-5 text-primary">{{ details.totalPrazoFormatted }}</div>
                    </div>
                  </div>
                </div>

                <div class="row gy-3 mt-3">
                  <div class="col-md-6" v-if="details.customer">
                    <div class="text-muted small">Cliente</div>
                    <div class="fw-semibold">{{ details.customer }}</div>
                  </div>
                  <div class="col-md-6" v-if="details.customerEmail">
                    <div class="text-muted small">Contato</div>
                    <div class="fw-semibold">{{ details.customerEmail }}</div>
                  </div>
                  <div class="col-md-6" v-if="details.createdAt">
                    <div class="text-muted small">Criado em</div>
                    <div class="fw-semibold">{{ details.createdAt }}</div>
                  </div>
                  <div class="col-md-6" v-if="details.updatedAt">
                    <div class="text-muted small">Atualizado em</div>
                    <div class="fw-semibold">{{ details.updatedAt }}</div>
                  </div>
                </div>

                <div v-if="details.description" class="mt-3">
                  <div class="text-muted small mb-1">Observações</div>
                  <p class="mb-0">{{ details.description }}</p>
                </div>

                <div v-if="details.items && details.items.length" class="mt-4">
                  <h6 class="fw-semibold mb-2">Itens do orçamento</h6>
                  <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                      <thead class="table-light">
                        <tr>
                          <th>Item</th>
                          <th class="text-center">Qtd.</th>
                          <th class="text-end">Valor unitário</th>
                          <th class="text-end">Subtotal</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in details.items" :key="item.id ?? index">
                          <td>{{ resolveItemName(item, index) }}</td>
                          <td class="text-center">
                            <span v-if="resolveItemQuantity(item) !== null">{{ resolveItemQuantity(item) }}</span>
                            <span v-else>-</span>
                          </td>
                          <td class="text-end">{{ formatMaybeCurrency(resolveItemUnitPrice(item)) }}</td>
                          <td class="text-end">{{ formatMaybeCurrency(resolveItemTotal(item)) }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
              <div v-else class="text-center text-muted py-4">
                Não foi possível carregar os detalhes do orçamento.
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" @click="handleClose">
                Fechar
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-backdrop fade show"></div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  budget: {
    type: Object,
    default: null,
  },
  visible: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close']);

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL',
});

function handleClose() {
  emit('close');
}

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

function normalizeDate(value) {
  if (!value) {
    return null;
  }

  try {
    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) {
      return typeof value === 'string' ? value : null;
    }

    return date.toLocaleString('pt-BR');
  } catch (error) {
    return typeof value === 'string' ? value : null;
  }
}

function resolveItemName(item, index) {
  if (!item) {
    return `Item ${index + 1}`;
  }

  return (
    item.name ??
    item.title ??
    item.description ??
    item.product_name ??
    item.productName ??
    `Item ${index + 1}`
  );
}

function resolveItemQuantity(item) {
  if (!item) {
    return null;
  }

  const quantity =
    item.quantity ??
    item.qty ??
    item.amount ??
    item.count ??
    null;

  if (quantity === null || quantity === undefined) {
    return null;
  }

  const numeric = Number(quantity);

  return Number.isFinite(numeric) ? numeric : quantity;
}

function resolveItemUnitPrice(item) {
  if (!item) {
    return null;
  }

  const unit =
    item.unit_price ??
    item.unitPrice ??
    item.price ??
    item.value ??
    null;

  if (unit === null || unit === undefined) {
    return null;
  }

  const numeric = Number(unit);

  return Number.isFinite(numeric) ? numeric : unit;
}

function resolveItemTotal(item) {
  if (!item) {
    return null;
  }

  const total =
    item.total ??
    item.total_price ??
    item.totalPrice ??
    item.amount_total ??
    item.subtotal ??
    null;

  if (total === null || total === undefined) {
    return null;
  }

  const numeric = Number(total);

  return Number.isFinite(numeric) ? numeric : total;
}

function formatMaybeCurrency(value) {
  if (value === null || value === undefined) {
    return '-';
  }

  const numeric = Number(value);

  if (Number.isFinite(numeric)) {
    return formatCurrency(numeric);
  }

  return value;
}

const details = computed(() => {
  const budget = props.budget;

  if (!budget) {
    return null;
  }

  const totalRaw = Number(
    budget.total_amount ?? budget.totalAmount ?? budget.total ?? 0,
  );
  const total = Number.isFinite(totalRaw) ? totalRaw : 0;

  const totalVistaRaw = Number(budget.total_amount ?? budget.totalAmount ?? 0);
  const totalVista = Number.isFinite(totalVistaRaw) ? totalVistaRaw : 0;

  const totalPrazoRaw = Number(budget.total_amount_installments ?? budget.totalAmountInstallments ?? 0);
  const totalPrazo = Number.isFinite(totalPrazoRaw) ? totalPrazoRaw : 0;

  const customerName =
    budget.customer_name ??
    budget.customerName ??
    budget.customer?.name ??
    null;

  const customerEmail =
    budget.customer_email ??
    budget.customerEmail ??
    budget.customer?.email ??
    null;

  const description =
    budget.description ??
    budget.observation ??
    budget.observations ??
    budget.notes ??
    null;

  const rawItems = Array.isArray(budget.items) ? budget.items : [];

  return {
    id: budget.id,
    name: budget.name ?? 'Não informado',
    total,
    totalFormatted: formatCurrency(total),
    totalVista,
    totalVistaFormatted: formatCurrency(totalVista),
    totalPrazo,
    totalPrazoFormatted: formatCurrency(totalPrazo),
    deliveryTime: formatDeliveryTime(
      budget.delivery_time ?? budget.deliveryTime ?? null,
    ),
    status: budget.status ?? null,
    createdAt: normalizeDate(
      budget.created_at ?? budget.createdAt ?? budget.createdAtFormatted ?? null,
    ),
    updatedAt: normalizeDate(
      budget.updated_at ?? budget.updatedAt ?? budget.updatedAtFormatted ?? null,
    ),
    customer: customerName,
    customerEmail,
    description,
    items: rawItems,
  };
});
</script>
