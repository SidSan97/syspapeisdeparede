<template>
  <Teleport to="body">
    <div
      ref="modalElement"
      class="modal fade"
      tabindex="-1"
      role="dialog"
      aria-labelledby="budgetOrderModalLabel"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="budgetOrderModalLabel">Fazer pedido</h5>
            <button type="button" class="btn-close" aria-label="Close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <div class="alert alert-info mb-0">
              <p class="mb-0">
                Revise se as medidas, quantidades, modelos, endereço e demais informações estão corretas antes de continuar.
              </p>
            </div>

            <p v-if="orderError" class="text-danger small mt-3 mb-0">
              {{ orderError }}
            </p>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-subtle" :disabled="orderSubmitting" @click="handleClose">
              Cancelar
            </button>
            <button
              type="button"
              class="btn btn-primary"
              :disabled="orderSubmitting"
              @click="submitOrder"
            >
              <span
                v-if="orderSubmitting"
                class="spinner-border spinner-border-sm me-2"
                role="status"
                aria-hidden="true"
              ></span>
              Confirmar pedido
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useBudgetOrderService } from '@/modules/budgets/services/budgetOrderService';

const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  budget: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'updated']);

const router = useRouter();
const budgetOrderService = useBudgetOrderService();

const orderSubmitting = ref(false);
const orderError = ref('');

const modalElement = ref(null);
let modalInstance = null;
let modalHiddenHandler = null;

watch(
  () => props.visible,
  (visible) => {
    if (visible) {
      orderError.value = '';
      nextTick(() => {
        showModalInstance();
      });
    } else {
      hideModal();
      orderError.value = '';
    }
  },
);

function handleClose() {
  if (orderSubmitting.value) {
    return;
  }

  emit('close');
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

async function submitOrder() {
  if (!props.budget?.id) {
    return;
  }

  orderSubmitting.value = true;
  orderError.value = '';

  try {
    const formData = new FormData();
    formData.append('id', props.budget.id);

    const payload = await budgetOrderService.placeOrder(formData);
    const orderId = payload?.order_id;

    if (!orderId) {
      throw new Error('ID do pedido não encontrado na resposta.');
    }

    emit('updated', payload);
    emit('close');

    window.Swal.fire({
      title: 'Pedido realizado',
      text: 'Pedido registrado com sucesso.',
      confirmButtonText: 'Entendi!',
      willClose: () => {
        router.push({ name: 'ShowOrderDetails', params: { id: orderId } });
      },
    });
  } catch (error) {
    orderError.value =
      error?.response?.data?.message ??
      error?.message ??
      'Não foi possível realizar o pedido. Tente novamente.';

    window.Swal.fire({
      title: 'Não foi possível concluir o pedido',
      text: orderError.value,
      icon: 'error',
      confirmButtonText: 'Entendi',
    });
  } finally {
    orderSubmitting.value = false;
  }
}

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

