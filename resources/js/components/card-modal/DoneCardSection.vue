<script setup>
import { computed, ref, watch } from 'vue';
import { IconCheck, IconCircleCheck, IconX } from '@tabler/icons-vue';
import { http } from '@/lib/http';

const props = defineProps({
  isCompleted: {
    type: Boolean,
    required: true,
  },
  completing: {
    type: Boolean,
    default: false,
  },
  completedAtLabel: {
    type: String,
    default: '',
  },
  completedDurationLabel: {
    type: String,
    default: '',
  },
  reopening: {
    type: Boolean,
    default: false,
  },
  /** Identificador do order_budget (para habilitar o botão Concluir). */
  cardId: {
    type: [Number, String],
    default: null,
  },
});

const emit = defineEmits(['complete', 'reopen']);

const BUDGET_APPROVAL_TERM_ID = 'budget_approval';

const showTerms = ref(false);
const acceptedTerms = ref(false);
const loadingTerms = ref(false);
const termsError = ref('');
const term = ref(null);

const canConfirm = computed(
  () => acceptedTerms.value && !props.completing && !loadingTerms.value && !!term.value,
);

const termBodyHtml = computed(() => {
  const body = term.value?.body?.trim();

  if (body) {
    return body;
  }

  return '<p class="text-muted mb-0">Nenhum texto cadastrado.</p>';
});

async function loadBudgetApprovalTerm() {
  loadingTerms.value = true;
  termsError.value = '';

  try {
    const { data } = await http.get('v1/terms-of-use');
    const terms = Array.isArray(data.data) ? data.data : [];
    const budgetTerm =
      terms.find((item) => item.id === BUDGET_APPROVAL_TERM_ID) ||
      terms.find((item) =>
        String(item.title || '')
          .toLowerCase()
          .includes('aprovação do orçamento'),
      ) ||
      terms[0] ||
      null;

    term.value = budgetTerm
      ? {
          id: budgetTerm.id,
          title: budgetTerm.title || 'Termo de aprovação do orçamento',
          body: budgetTerm.body || '',
        }
      : null;

    if (!term.value) {
      termsError.value = 'Termo de uso não encontrado. Configure em Configurações.';
    }
  } catch (error) {
    console.error('Erro ao carregar termo de uso:', error);
    termsError.value = 'Não foi possível carregar o Termo de Uso.';
    term.value = null;
  } finally {
    loadingTerms.value = false;
  }
}

function openTerms() {
  if (!props.cardId || props.completing) {
    return;
  }

  showTerms.value = true;
  acceptedTerms.value = false;
  loadBudgetApprovalTerm();
}

function cancelTerms() {
  showTerms.value = false;
  acceptedTerms.value = false;
  termsError.value = '';
}

function confirmComplete() {
  if (!canConfirm.value) {
    return;
  }

  emit('complete', { accepted_terms_of_use: true });
}

function reopenCard() {
  if (!props.cardId || props.reopening || props.completing) {
    return;
  }

  emit('reopen');
}

watch(
  () => props.isCompleted,
  (completed) => {
    if (completed) {
      cancelTerms();
    }
  },
);

watch(
  () => props.cardId,
  () => {
    cancelTerms();
  },
);
</script>

<template>
  <div v-if="!isCompleted" class="mb-4">
    <div v-if="!showTerms">
      <button
        type="button"
        class="btn btn-success btn-sm d-inline-flex align-items-center"
        :disabled="completing || !cardId"
        @click="openTerms"
      >
        <IconCheck :size="18" class="me-1" />
        Concluir
      </button>
    </div>

    <div v-else class="card border">
      <div class="card-header d-flex align-items-center justify-content-between py-2">
        <strong class="small mb-0">{{ term?.title || 'Termo de Uso' }}</strong>
        <button
          type="button"
          class="btn btn-sm btn-link text-body-secondary p-0"
          :disabled="completing"
          aria-label="Cancelar"
          @click="cancelTerms"
        >
          <IconX :size="18" />
        </button>
      </div>

      <div class="card-body">
        <div v-if="loadingTerms" class="text-muted d-flex align-items-center gap-2 small">
          <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
          Carregando termo de uso…
        </div>

        <div v-else-if="termsError" class="alert alert-warning py-2 mb-3 small">
          {{ termsError }}
        </div>

        <div
          v-else
          class="border rounded p-3 mb-3 small overflow-auto"
          style="max-height: 240px"
          v-html="termBodyHtml"
        ></div>

        <div class="form-check mb-3">
          <input
            id="accepted-terms-of-use"
            v-model="acceptedTerms"
            class="form-check-input"
            type="checkbox"
            :disabled="completing || loadingTerms || !!termsError"
          />
          <label class="form-check-label" for="accepted-terms-of-use">
            Li e estou de acordo com o Termo de Uso
          </label>
        </div>

        <div class="d-flex flex-wrap gap-2">
          <button
            type="button"
            class="btn btn-subtle btn-sm"
            :disabled="completing"
            @click="cancelTerms"
          >
            Cancelar
          </button>
          <button
            type="button"
            class="btn btn-success btn-sm d-inline-flex align-items-center"
            :disabled="!canConfirm"
            @click="confirmComplete"
          >
            <span
              v-if="completing"
              class="spinner-border spinner-border-sm me-1"
              role="status"
            ></span>
            <IconCheck v-else :size="18" class="me-1" />
            Confirmar conclusão
          </button>
        </div>
      </div>
    </div>
  </div>

  <div v-else class="mb-4">
    <div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-2">
      <IconCircleCheck :size="20" />
      <div>
        Card concluído em
        <strong>{{ completedAtLabel }}</strong>
        <span v-if="completedDurationLabel" class="ms-2 text-body-secondary">
          · Duração total: <strong>{{ completedDurationLabel }}</strong>
        </span>
      </div>
    </div>

    <button
      type="button"
      class="btn btn-secondary btn-sm"
      :disabled="reopening || completing || !cardId"
      @click="reopenCard"
    >
      <span
        v-if="reopening"
        class="spinner-border spinner-border-sm me-1"
        role="status"
        aria-hidden="true"
      ></span>
      {{ reopening ? 'Reabrindo...' : 'Reabrir card' }}
    </button>
  </div>
</template>
