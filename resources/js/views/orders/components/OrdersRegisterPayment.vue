<template>
  <Teleport v-if="visible" to="body">
    <div>
      <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Registrar Pagamento</h5>
              <button
                type="button"
                class="btn-close"
                aria-label="Close"
                @click="handleClose"
                :disabled="uploading"
              ></button>
            </div>
            <div class="modal-body">
              <div v-if="pedido" class="mb-3">
                <p class="mb-2">
                  <strong>Pedido:</strong> {{ pedido.name }}
                </p>
                <p class="mb-0 text-muted small">
                  Envie o comprovante de pagamento (JPEG ou PDF)
                </p>
              </div>
              <div class="mb-3">
                <label for="paymentFile" class="form-label">
                  Arquivo de Pagamento <span class="text-danger">*</span>
                </label>
                <input
                  id="paymentFile"
                  ref="paymentFileInput"
                  type="file"
                  accept=".jpeg,.jpg,.pdf,image/jpeg,application/pdf"
                  class="form-control"
                  @change="handleFileChange"
                  :disabled="uploading"
                />
                <div class="form-text">
                  Formatos aceitos: JPEG, JPG, PDF. Tamanho máximo: 10MB
                </div>
              </div>
              <div v-if="error" class="alert alert-danger mb-0">
                {{ error }}
              </div>
            </div>
            <div class="modal-footer">
              <button
                type="button"
                class="btn btn-outline-secondary"
                @click="handleClose"
                :disabled="uploading"
              >
                Cancelar
              </button>
              <button
                type="button"
                class="btn btn-primary"
                @click="handleSubmit"
                :disabled="uploading || !selectedFile"
              >
                <span
                  v-if="uploading"
                  class="spinner-border spinner-border-sm me-2"
                  role="status"
                  aria-hidden="true"
                ></span>
                <i v-else class="fa fa-upload me-2"></i>
                {{ uploading ? 'Enviando...' : 'Enviar' }}
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
import { ref, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  pedido: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'success']);

const selectedFile = ref(null);
const paymentFileInput = ref(null);
const uploading = ref(false);
const error = ref('');

function handleClose() {
  if (uploading.value) {
    return;
  }
  emit('close');
  resetForm();
}

function resetForm() {
  selectedFile.value = null;
  error.value = '';
  if (paymentFileInput.value) {
    paymentFileInput.value.value = '';
  }
}

function handleFileChange(event) {
  const file = event.target.files?.[0];
  error.value = '';

  if (!file) {
    selectedFile.value = null;
    return;
  }

  // Validar tipo de arquivo
  const validTypes = ['image/jpeg', 'image/jpg', 'application/pdf'];
  const validExtensions = ['.jpeg', '.jpg', '.pdf'];
  const fileExtension = '.' + file.name.split('.').pop().toLowerCase();

  if (!validTypes.includes(file.type) && !validExtensions.includes(fileExtension)) {
    error.value = 'Formato de arquivo inválido. Use apenas JPEG ou PDF.';
    selectedFile.value = null;
    if (paymentFileInput.value) {
      paymentFileInput.value.value = '';
    }
    return;
  }

  // Validar tamanho (10MB)
  const maxSize = 10 * 1024 * 1024; // 10MB em bytes
  if (file.size > maxSize) {
    error.value = 'Arquivo muito grande. Tamanho máximo: 10MB.';
    selectedFile.value = null;
    if (paymentFileInput.value) {
      paymentFileInput.value.value = '';
    }
    return;
  }

  selectedFile.value = file;
}

async function handleSubmit() {
  if (!props.pedido || !selectedFile.value) {
    error.value = 'Por favor, selecione um arquivo para enviar.';
    return;
  }

  uploading.value = true;
  error.value = '';

  try {
    const formData = new FormData();
    formData.append('payment_file', selectedFile.value);
    formData.append('budget_id', props.pedido.id);

    const response = await axios.post('v1/budgets/register-payment', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    if (response.data?.success) {
      await Swal.fire({
        title: 'Sucesso',
        text: 'Pagamento registrado com sucesso! O pedido foi aprovado.',
        icon: 'success',
        confirmButtonText: 'OK',
      });

      emit('success');
      handleClose();
    } else {
      throw new Error(response.data?.message || 'Erro ao registrar pagamento');
    }
  } catch (err) {
    console.error('Erro ao registrar pagamento:', err);
    error.value = err?.response?.data?.message || err?.message || 'Não foi possível registrar o pagamento. Tente novamente.';
  } finally {
    uploading.value = false;
  }
}

// Resetar formulário quando o modal fechar
watch(() => props.visible, (isVisible) => {
  if (!isVisible) {
    resetForm();
  }
});
</script>

<style scoped>
.form-label {
  color: var(--bs-body-color);
}

.form-control {
  background-color: var(--bs-body-bg);
  color: var(--bs-body-color);
  border-color: var(--bs-border-color);
}

.form-control:focus {
  background-color: var(--bs-body-bg);
  color: var(--bs-body-color);
  border-color: var(--bs-primary);
  box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
}

.form-text {
  color: var(--bs-secondary);
}
</style>

