<template>
  <div class="card mt-4 mb-4">
    <div class="card-header bg-transparent">
      <h5 class="mb-0 fw-semibold">Histórico de alterações</h5>
    </div>
    <div class="card-body">
      <div v-if="!items.length" class="text-muted small">
        Nenhuma alteração registrada para este pedido aprovado.
      </div>

      <div v-else class="accordion" id="orderChangeHistoryAccordion">
        <div
          v-for="(entry, index) in items"
          :key="entry.id"
          class="accordion-item"
        >
          <h2 class="accordion-header">
            <button
              class="accordion-button"
              :class="{ collapsed: openIndex !== index }"
              type="button"
              data-bs-toggle="collapse"
              :data-bs-target="`#${collapseId(entry)}`"
              :aria-expanded="openIndex === index"
              :aria-controls="collapseId(entry)"
              @click="toggleOpen(index)"
            >
              <div class="d-flex flex-wrap align-items-center gap-2 w-100 pe-3">
                <span class="fw-medium">{{ entry.description }}</span>
                <span
                  v-if="changedRows(entry).length"
                  class="badge text-bg-secondary"
                >
                  {{ changedRows(entry).length }}
                  {{ changedRows(entry).length === 1 ? 'campo' : 'campos' }}
                </span>
                <span class="text-muted small ms-auto">
                  {{ formatDate(entry.created_at) }}
                </span>
              </div>
            </button>
          </h2>

          <div
            :id="collapseId(entry)"
            class="accordion-collapse collapse"
            :class="{ show: openIndex === index }"
            data-bs-parent="#orderChangeHistoryAccordion"
          >
            <div class="accordion-body p-2">
              <div v-if="entry.user_name" class="text-muted small mb-3">
                Alterado por <strong>{{ entry.user_name }}</strong>
              </div>

              <div v-if="!changedRows(entry).length" class="text-muted small">
                Sem detalhamento de campos nesta alteração.
              </div>

              <div v-else class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th style="width: 28%">Campo</th>
                      <th style="width: 36%">Antes</th>
                      <th style="width: 36%">Depois</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="row in changedRows(entry)" :key="row.field">
                      <td class="fw-medium">{{ row.label }}</td>
                      <td>
                        <span class="text-danger-emphasis">{{ row.before }}</span>
                      </td>
                      <td>
                        <span class="text-success-emphasis">{{ row.after }}</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useFormatting } from '@/composables/useFormatting';

const props = defineProps({
  histories: {
    type: Array,
    default: () => [],
  },
});

const { formatDate, formatCurrency } = useFormatting();

const openIndex = ref(0);

const items = computed(() => props.histories ?? []);

const FIELD_LABELS = {
  name: 'Nome',
  status: 'Status',
  total_area: 'Área total',
  total_amount: 'Total à vista',
  total_amount_installments: 'Total a prazo',
  delivery_time: 'Prazo de entrega',
  selected_carrier_name: 'Transportadora',
  selected_carrier_price: 'Valor do frete',
  observation: 'Observação',
  rooms_count: 'Quantidade de ambientes',
  walls_count: 'Quantidade de paredes',
};

function collapseId(entry) {
  return `order-change-history-${entry.id}`;
}

function toggleOpen(index) {
  openIndex.value = openIndex.value === index ? -1 : index;
}

function changedRows(entry) {
  const before = entry?.changes?.before ?? {};
  const after = entry?.changes?.after ?? {};
  const hasSnapshots =
    (before && typeof before === 'object' && Object.keys(before).length > 0) ||
    (after && typeof after === 'object' && Object.keys(after).length > 0);

  if (!hasSnapshots) {
    return [];
  }

  let fields = entry?.changes?.changed_fields;
  if (!Array.isArray(fields) || !fields.length) {
    fields = resolveChangedFields(before, after);
  }

  // Se ainda não houver diff explícito, exibe todos os campos do snapshot.
  if (!fields.length) {
    fields = [...new Set([...Object.keys(before), ...Object.keys(after)])];
  }

  return fields.map((field) => ({
    field,
    label: FIELD_LABELS[field] || field,
    before: formatFieldValue(field, before[field]),
    after: formatFieldValue(field, after[field]),
  }));
}

function resolveChangedFields(before, after) {
  const keys = [...new Set([...Object.keys(before ?? {}), ...Object.keys(after ?? {})])];

  return keys.filter((field) => {
    const previous = before?.[field];
    const next = after?.[field];

    if (isNumericLike(previous) && isNumericLike(next)) {
      return Number(previous) !== Number(next);
    }

    return String(previous ?? '') !== String(next ?? '');
  });
}

function isNumericLike(value) {
  return value !== null && value !== undefined && value !== '' && !Number.isNaN(Number(value));
}

function formatFieldValue(field, value) {
  if (value === null || value === undefined || value === '') {
    return '—';
  }

  if (
    field === 'total_amount' ||
    field === 'total_amount_installments' ||
    field === 'selected_carrier_price'
  ) {
    return formatCurrency(value);
  }

  if (field === 'delivery_time') {
    const days = Number(value) || 0;
    return `${days} ${days === 1 ? 'dia' : 'dias'}`;
  }

  if (field === 'total_area') {
    return `${Number(value).toFixed(2)} m²`;
  }

  return String(value);
}
</script>

<style scoped>
.accordion-button {
 padding-left: 5px !important;
 padding-right: 5px !important;
}
</style>