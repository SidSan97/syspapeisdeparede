<template>
  <div class="card mt-3">
    <div class="card-body">
      <h5 class="card-title">Preços por ambiente</h5>
      <p class="text-muted small mb-3">
        Edite os valores para atualizar o preview e os totais. Não altera o markup.
      </p>

      <div v-if="items.length" class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr>
              <th>Ambiente</th>
              <th>À vista</th>
              <th>A prazo</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id">
              <td>{{ item.title }}</td>
              <td>
                <money
                  v-model.number="item.total"
                  v-bind="moneyConfig"
                  class="form-control form-control-sm"
                  @change="emitChange"
                />
              </td>
              <td>
                <money
                  v-model.number="item.installment_total"
                  v-bind="moneyConfig"
                  class="form-control form-control-sm"
                  @change="emitChange"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="text-muted small">Nenhum ambiente carregado.</div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { http } from '@/lib/http';
import { useToast } from '@/composables/useToast';

/**
 * @typedef {object} RoomPriceItem
 * @property {number} id
 * @property {string} title
 * @property {number} total
 * @property {number} installment_total
 */

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  budgetId: {
    type: [Number, String],
    default: null,
  },
});

const emit = defineEmits(['update:modelValue', 'change']);

const toast = useToast();
const syncingFromMarkup = ref(false);

const moneyConfig = {
  decimal: ',',
  thousands: '.',
  precision: 2,
  prefix: '',
  allowBlank: false,
  min: 0,
  max: null,
  disableNegative: true,
  minimumNumberOfCharacters: 0,
};

const items = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

function emitChange() {
  if (syncingFromMarkup.value) {
    return;
  }
  emit('change', items.value);
}

watch(
  items,
  () => {
    if (syncingFromMarkup.value) {
      return;
    }
    emit('change', items.value);
  },
  { deep: true },
);

/**
 * Recalcula os preços dos ambientes a partir do markup informado.
 *
 * @param {number} markup
 * @returns {Promise<RoomPriceItem[]>}
 */
async function loadFromMarkup(markup) {
  if (!props.budgetId) {
    items.value = [];
    return [];
  }

  syncingFromMarkup.value = true;

  try {
    const { data } = await http.get(`/v1/budgets/${props.budgetId}/preview-items`, {
      params: {
        mockup_percentage: markup,
      },
    });

    const nextItems = (data?.data?.items ?? []).map((item) => ({
      id: item.id,
      title: item.title,
      total: Number(item.total) || 0,
      installment_total: Number(item.installment_total) || 0,
    }));

    items.value = nextItems;

    // Aguarda o flush do watch deep para não emitir `change` após o sync.
    await nextTick();

    return nextItems;
  } catch (err) {
    console.error('Erro ao carregar preços dos ambientes:', err);
    toast.error('Não foi possível recalcular os preços dos ambientes.');
    throw err;
  } finally {
    syncingFromMarkup.value = false;
  }
}

defineExpose({
  loadFromMarkup,
  isSyncingFromMarkup: () => syncingFromMarkup.value,
});
</script>
