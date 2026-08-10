<template>
  <BaseModal
    v-model="isOpen"
    size="lg"
    :scrollable="true"
    :close-on-backdrop="!submitting"
    :close-on-escape="!submitting"
    :show-close-button="!submitting"
    :show-footer="true"
    @close="handleClose"
  >
    <template #header>
      <h5 class="modal-title">{{ term?.title || 'Termo de Uso' }}</h5>
    </template>

    <template #body>
      <div v-if="loading" class="text-muted d-flex align-items-center gap-2">
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Carregando termo de uso...
      </div>

      <div v-else-if="loadError" class="alert alert-warning mb-0">
        {{ loadError }}
      </div>

      <template v-else>
        <div class="border rounded p-3 mb-3 overflow-auto terms-body" v-html="termBodyHtml"></div>

        <div class="form-check">
          <input
            :id="checkboxId"
            v-model="accepted"
            class="form-check-input"
            type="checkbox"
            :disabled="submitting"
          />
          <label class="form-check-label" :for="checkboxId">
            Li e estou de acordo com o Termo de Uso
          </label>
        </div>
      </template>
    </template>

    <template #footer>
      <button type="button" class="btn btn-subtle" :disabled="submitting" @click="handleClose">
        Cancelar
      </button>
      <button type="button" class="btn btn-success" :disabled="!canConfirm" @click="handleConfirm">
        <span
          v-if="submitting"
          class="spinner-border spinner-border-sm me-2"
          role="status"
          aria-hidden="true"
        ></span>
        {{ confirmButtonText }}
      </button>
    </template>
  </BaseModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import BaseModal from '@/components/common/BaseModal.vue';
import { http } from '@/lib/http';

const BUDGET_APPROVAL_TERM_ID = 'budget_approval';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  submitting: {
    type: Boolean,
    default: false,
  },
  confirmButtonText: {
    type: String,
    default: 'Aprovar',
  },
});

const emit = defineEmits(['update:modelValue', 'confirm', 'close']);

const term = ref(null);
const loading = ref(false);
const loadError = ref('');
const accepted = ref(false);

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const checkboxId = `accept-terms-of-use-${Math.random().toString(36).slice(2, 9)}`;

const termBodyHtml = computed(() => {
  const body = term.value?.body?.trim();

  return body || '<p class="text-muted mb-0">Nenhum texto cadastrado.</p>';
});

const canConfirm = computed(
  () => accepted.value && !props.submitting && !loading.value && !loadError.value,
);

async function loadTerm() {
  loading.value = true;
  loadError.value = '';

  try {
    const { data } = await http.get('v1/terms-of-use');
    const terms = Array.isArray(data.data) ? data.data : [];

    term.value =
      terms.find((item) => item.id === BUDGET_APPROVAL_TERM_ID) ??
      terms.find((item) =>
        String(item.title || '')
          .toLowerCase()
          .includes('aprovação do orçamento'),
      ) ??
      terms[0] ??
      null;

    if (!term.value) {
      loadError.value = 'Termo de uso não encontrado. Cadastre-o em Configurações.';
    }
  } catch (error) {
    console.error('Erro ao carregar termo de uso:', error);
    term.value = null;
    loadError.value = 'Não foi possível carregar o Termo de Uso.';
  } finally {
    loading.value = false;
  }
}

function handleClose() {
  if (props.submitting) {
    return;
  }

  isOpen.value = false;
  emit('close');
}

function handleConfirm() {
  if (!canConfirm.value) {
    return;
  }

  emit('confirm');
}

watch(
  () => props.modelValue,
  (open) => {
    if (!open) {
      accepted.value = false;
      return;
    }

    if (!term.value) {
      loadTerm();
    }
  },
);
</script>

<style scoped>
.terms-body {
  max-height: 45vh;
}
</style>
