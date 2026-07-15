<template>
  <BaseModal
    v-model="isVisible"
    title="Registrar pagamento"
    :show-confirm-button="true"
    :close-on-backdrop="false"
  >
    <template #body>
      <form @submit.prevent="submitPayment" ref="formRef">
        <div class="mb-3">
          <label class="form-label fw-bold"> Pedido #{{ orderId }} </label>
        </div>
        <div class="mb-3">
          <label for="paymentFile" class="form-label">
            Comprovante de Pagamento
            <span class="text-danger">*</span>
          </label>
          <input
            type="file"
            id="receipt"
            class="form-control"
            :class="{ 'is-invalid': errors.receipt }"
            @change="handleFileUpload"
            accept=".pdf,.jpg,.jpeg,.png,.heic"
            required
          />
          <div class="form-text">Formatos aceitos: PDF, JPG, PNG (máx. 10MB)</div>
          <div v-if="errors.receipt" class="invalid-feedback">
            {{ errors.receipt }}
          </div>

          <div v-if="filePreview" class="mt-2">
            <div class="alert alert-success d-flex justify-content-between align-items-center">
              <span>
                <IconFileCheck :size="18" />

                {{ filePreview.name }} ({{ formatFileSize(filePreview.size) }})
              </span>
              <button type="button" class="btn-close" @click="removeFile"></button>
            </div>
            <div v-if="isImagePreview" class="mt-2">
              <img :src="imagePreviewUrl" class="img-thumbnail" style="max-height: 200px" />
            </div>
          </div>
        </div>
      </form>
    </template>

    <template #footer>
      <button type="button" class="btn btn-subtle" @click="close" :disabled="loading">
        Cancelar
      </button>
      <button type="button" class="btn btn-primary" @click="submitPayment" :disabled="loading">
        <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
        Confirmar Pagamento
      </button>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import BaseModal from '@/components/common/BaseModal.vue';
import { orderService } from '@/services/orderService';
import { IconFileCheck } from '@tabler/icons-vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  orderId: {
    type: [Number, String],
    required: true,
  },
});

const emit = defineEmits(['update:modelValue', 'success', 'error']);

const form = ref({});
const file = ref(null);
const filePreview = ref(null);
const imagePreviewUrl = ref(null);
const loading = ref(false);
const errors = ref({});

const isVisible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const isImagePreview = computed(() => {
  return filePreview.value && filePreview.value.type.startsWith('image/');
});

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const handleFileUpload = (event) => {
  const selectedFile = event.target.files[0];

  if (selectedFile) {
    // Validações
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];

    if (selectedFile.size > maxSize) {
      errors.value.receipt = 'Arquivo muito grande. Máximo 5MB';
      return;
    }

    if (!allowedTypes.includes(selectedFile.type)) {
      errors.value.receipt = 'Formato não suportado. Use PDF, JPG ou PNG';
      return;
    }

    file.value = selectedFile;
    filePreview.value = {
      name: selectedFile.name,
      size: selectedFile.size,
      type: selectedFile.type,
    };

    // Preview para imagens
    if (selectedFile.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = (e) => {
        imagePreviewUrl.value = e.target.result;
      };
      reader.readAsDataURL(selectedFile);
    }

    delete errors.value.receipt;
  }
};

const removeFile = () => {
  file.value = null;
  filePreview.value = null;
  imagePreviewUrl.value = null;
  const fileInput = document.getElementById('receipt');
  if (fileInput) fileInput.value = '';
};

const validateForm = () => {
  errors.value = {};

  if (!file.value) {
    errors.value.receipt = 'Comprovante de pagamento é obrigatório';
  }

  return Object.keys(errors.value).length === 0;
};

async function submitPayment() {
  if (!validateForm()) return;

  loading.value = true;

  try {
    const formData = new FormData();
    formData.append('order_id', props.orderId);
    formData.append('payment_file', file.value);

    const response = await orderService.registerPayment(formData);

    emit('success', response);
    close();
  } catch (error) {
    console.error('Erro ao registrar pagamento:', error);
    emit('error', error.message);
  } finally {
    loading.value = false;
  }
}

const resetForm = () => {
  form.value = {};
  removeFile();
  errors.value = {};
};

const close = () => {
  if (!loading.value) {
    isVisible.value = false;
    resetForm();
  }
};

// Watch para resetar quando fechar externamente
watch(
  () => props.modelValue,
  (newVal) => {
    if (!newVal) {
      resetForm();
    }
  },
);
</script>
