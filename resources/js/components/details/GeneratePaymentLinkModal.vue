<template>
  <Teleport to="body">
    <div
      ref="modalElement"
      class="modal fade"
      tabindex="-1"
      role="dialog"
      aria-labelledby="generatePaymentLinkModalLabel"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="generatePaymentLinkModalLabel">Gerar link de pagamento</h5>
            <button
              type="button"
              class="btn-close"
              aria-label="Close"
              data-bs-dismiss="modal"
              :disabled="submitting"
            ></button>
          </div>

          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label mb-1">Componentes</label>
              <div class="form-check">
                <input
                  id="component-artes"
                  v-model="form.artes"
                  class="form-check-input"
                  type="checkbox"
                  :disabled="componentState.artes.paid"
                />
                <label class="form-check-label" for="component-artes">
                  ARTES
                  <span class="text-muted">({{ formatMoney(componentValues.artes) }})</span>
                  <span v-if="componentState.artes.paid" class="badge text-bg-success ms-1">Pago</span>
                </label>
              </div>
              <div class="form-check">
                <input
                  id="component-produtos"
                  v-model="form.produtos"
                  class="form-check-input"
                  type="checkbox"
                  :disabled="componentState.produtos.paid"
                />
                <label class="form-check-label" for="component-produtos">
                  PRODUTOS
                  <span class="text-muted">({{ formatMoney(componentValues.produtos) }})</span>
                  <span v-if="componentState.produtos.paid" class="badge text-bg-success ms-1">Pago</span>
                </label>
              </div>
              <div class="form-check">
                <input
                  id="component-frete"
                  v-model="form.frete"
                  class="form-check-input"
                  type="checkbox"
                  :disabled="form.payment_method !== 'pix' || componentState.frete.paid"
                />
                <label class="form-check-label" for="component-frete">
                  FRETE
                  <span class="text-muted">({{ formatMoney(componentValues.frete) }})</span>
                  <span v-if="componentState.frete.paid" class="badge text-bg-success ms-1">Pago</span>
                </label>
              </div>
              <div v-if="form.payment_method !== 'pix'" class="small text-muted mt-1">
                O frete so pode ser pago via Pix.
              </div>
            </div>

            <div class="mb-3">
              <label for="payment-method" class="form-label mb-1">Metodo de pagamento</label>
              <select id="payment-method" v-model="form.payment_method" class="form-select">
                <option value="pix">Pix</option>
                <option value="credit_card">Cartao</option>
              </select>
            </div>

            <div class="mb-0" v-if="form.payment_method === 'credit_card'">
              <label for="payment-installments" class="form-label mb-1">Parcelas (cartao)</label>
              <select
                id="payment-installments"
                v-model.number="form.installments"
                class="form-control"
                :disabled="form.payment_method !== 'credit_card'"
              >
                <option v-for="installment in installmentsOptions" :key="installment" :value="installment">
                  {{ installment }}x
                </option>
              </select>
            </div>

            <div class="mt-3">
              <div class="small text-muted mb-1">Resumo do link</div>
              <div class="d-flex justify-content-between small">
                <span>ARTES</span>
                <span>{{ formatMoney(form.artes ? componentValues.artes : 0) }}</span>
              </div>
              <div class="d-flex justify-content-between small">
                <span>PRODUTOS</span>
                <span>{{ formatMoney(form.produtos ? componentValues.produtos : 0) }}</span>
              </div>
              <div class="d-flex justify-content-between small">
                <span>FRETE</span>
                <span>{{ formatMoney(form.frete ? componentValues.frete : 0) }}</span>
              </div>
              <div class="d-flex justify-content-between fw-semibold mt-2">
                <span>Total</span>
                <span>{{ formatMoney(selectedTotal) }}</span>
              </div>
            </div>

            <div v-if="error" class="alert alert-danger mt-3 mb-0">
              {{ error }}
            </div>
          </div>

          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-subtle"
              @click="handleClose"
              :disabled="submitting"
            >
              Cancelar
            </button>
            <button
              type="button"
              class="btn btn-primary"
              @click="handleSubmit"
              :disabled="submitting"
            >
              <span
                v-if="submitting"
                class="spinner-border spinner-border-sm me-2"
                role="status"
                aria-hidden="true"
              ></span>
              {{ submitting ? 'Gerando...' : 'Gerar link' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, reactive, ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';

const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  defaultInstallments: {
    type: Number,
    default: 1,
  },
  submitting: {
    type: Boolean,
    default: false,
  },
  paymentBreakdown: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'submit']);

const form = reactive({
  artes: true,
  produtos: true,
  frete: true,
  payment_method: 'pix',
  installments: 1,
});
const installmentsOptions = Array.from({ length: 12 }, (_, index) => index + 1);
const moneyFormatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

const componentValues = computed(() => {
  const remaining = props.paymentBreakdown?.remaining || {};
  const base = props.paymentBreakdown?.base || {};
  const isPix = form.payment_method === 'pix';

  const artes = Number(remaining.ARTES ?? base.ARTES ?? 0);
  const frete = Number(remaining.FRETE ?? base.FRETE ?? 0);
  const produtos = isPix
    ? Number(remaining.PRODUTOS_PIX ?? base.PRODUTOS_PIX ?? 0)
    : Number(remaining.PRODUTOS_CREDIT_CARD ?? base.PRODUTOS_CREDIT_CARD ?? 0);

  return { artes, produtos, frete };
});

const selectedTotal = computed(() => {
  let total = 0;
  if (form.artes) total += componentValues.value.artes;
  if (form.produtos) total += componentValues.value.produtos;
  if (form.frete) total += componentValues.value.frete;
  return Number(total.toFixed(2));
});
const componentState = computed(() => ({
  artes: {
    paid: componentValues.value.artes <= 0,
  },
  produtos: {
    paid: componentValues.value.produtos <= 0,
  },
  frete: {
    paid: componentValues.value.frete <= 0,
  },
}));

const error = ref('');
const modalElement = ref(null);
let modalInstance = null;
let modalHiddenHandler = null;

function formatMoney(value) {
  return moneyFormatter.format(Number(value || 0));
}

function resetForm() {
  form.payment_method = 'pix';
  form.installments = props.defaultInstallments || 1;
  applyComponentSelectionDefaults();
  error.value = '';
}

function applyComponentSelectionDefaults() {
  form.artes = !componentState.value.artes.paid;
  form.produtos = !componentState.value.produtos.paid;
  form.frete = !componentState.value.frete.paid;
}

function getComponents() {
  const components = [];
  if (form.artes) components.push('ARTES');
  if (form.produtos) components.push('PRODUTOS');
  if (form.frete) components.push('FRETE');
  return components;
}

function handleClose() {
  if (props.submitting) {
    return;
  }
  emit('close');
  resetForm();
}

function initializeModal() {
  if (!modalElement.value || modalInstance) {
    return;
  }

  modalInstance = new window.bootstrap.Modal(modalElement.value, {
    backdrop: true,
    keyboard: true,
    focus: true,
  });

  modalHiddenHandler = () => {
    handleClose();
  };

  modalElement.value.addEventListener('hidden.bs.modal', modalHiddenHandler);
}

function showModalInstance() {
  if (!modalInstance && modalElement.value) {
    initializeModal();
  }

  if (modalInstance) {
    modalInstance.show();
  }
}

function hideModal() {
  if (modalInstance) {
    modalInstance.hide();
  }
}

function disposeModal() {
  if (modalElement.value && modalHiddenHandler) {
    modalElement.value.removeEventListener('hidden.bs.modal', modalHiddenHandler);
    modalHiddenHandler = null;
  }

  if (modalInstance) {
    modalInstance.dispose();
    modalInstance = null;
  }
}

function handleSubmit() {
  error.value = '';

  if (form.payment_method !== 'pix' && form.frete) {
    error.value = 'Frete so pode ser pago via Pix.';
    return;
  }

  const components = getComponents();
  const paidComponents = [];
  if (form.artes && componentState.value.artes.paid) paidComponents.push('ARTES');
  if (form.produtos && componentState.value.produtos.paid) paidComponents.push('PRODUTOS');
  if (form.frete && componentState.value.frete.paid) paidComponents.push('FRETE');

  if (paidComponents.length) {
    error.value = `Os itens ${paidComponents.join(', ')} ja estao pagos e nao podem ser cobrados novamente.`;
    return;
  }

  if (!components.length) {
    error.value = 'Selecione ao menos um componente.';
    return;
  }

  if (form.payment_method === 'credit_card' && (!form.installments || form.installments < 1 || form.installments > 12)) {
    error.value = 'Informe parcelas validas entre 1 e 12.';
    return;
  }

  emit('submit', {
    components,
    payment_method: form.payment_method,
    installments: form.payment_method === 'credit_card' ? form.installments : null,
  });
}

watch(() => props.visible, (isVisible) => {
  if (isVisible) {
    resetForm();
    nextTick(() => {
      showModalInstance();
    });
  } else {
    hideModal();
  }
});

watch(() => props.defaultInstallments, (value) => {
  if (value && value > 0) {
    form.installments = value;
  }
});

watch(() => form.payment_method, (method) => {
  if (method !== 'pix') {
    form.frete = false;
  }

  if (componentState.value.produtos.paid) {
    form.produtos = false;
  }
  if (componentState.value.artes.paid) {
    form.artes = false;
  }
  if (componentState.value.frete.paid) {
    form.frete = false;
  }
});

onMounted(() => {
  if (props.visible) {
    nextTick(() => {
      initializeModal();
      showModalInstance();
    });
  }
});

onBeforeUnmount(() => {
  disposeModal();
});
</script>

