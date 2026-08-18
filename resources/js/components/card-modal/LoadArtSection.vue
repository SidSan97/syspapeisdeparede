<template>
  <div v-if="canLoad" class="load-art-section">
    <div class="form-check mb-3">
      <input
        class="form-check-input"
        type="checkbox"
        :id="`load-art-${cardId}`"
        v-model="showInput"
      />
      <label class="form-check-label" :for="`load-art-${cardId}`"> Carregar arte </label>
    </div>
    <div v-if="showInput" class="load-art-section-form">
      <input
        ref="fileInputRef"
        type="file"
        accept="image/*"
        @change="handleFileChange"
        class="form-control mb-3"
        :disabled="uploading"
      />
      <textarea
        v-if="showInput"
        v-model="comment"
        class="form-control"
        maxlength="500"
        rows="3"
        placeholder="Escreva um comentário para a arte..."
      ></textarea>
      <button
        v-if="selectedFile"
        type="button"
        class="btn btn-primary btn-sm mt-2"
        @click="handleUpload"
        :disabled="uploading"
      >
        <span v-if="uploading" class="spinner-border spinner-border-sm me-2" role="status"></span>
        <IconUpload :size="18" class="me-2" v-else />

        {{ uploading ? 'Enviando...' : 'Enviar arte' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useToast } from '@/composables/useToast';
import { artService } from '@/modules/card-modals/services/artService';
import { useAuthStore } from '@/stores/auth';
import { IconUpload } from '@tabler/icons-vue';
import { ORDER_BUDGET_STATUS } from '@/constants/orderBudgetStatuses';

const props = defineProps({
  card: {
    type: Object,
    required: true,
  },
  canLoad: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['art-uploaded']);

const toast = useToast();
const auth = useAuthStore();

const showInput = ref(false);
const selectedFile = ref(null);
const fileInputRef = ref(null);
const uploading = ref(false);
const comment = ref('');

const cardId = computed(() => props.card?.id);

function handleFileChange(event) {
  const file = event.target.files?.[0];
  if (file) {
    selectedFile.value = file;
  }
}

async function handleUpload() {
  if (
    !selectedFile.value ||
    !props.card?.id ||
    !props.card?.order?.user_id ||
    !props.card?.order?.id ||
    !auth.user?.id
  ) {
    return;
  }

  uploading.value = true;

  try {
    const formData = new FormData();
    formData.append('art_file', selectedFile.value);
    formData.append('order_budget_id', props.card.id);
    formData.append('dealer_id', props.card.order.user_id);
    formData.append('designer_id', auth.user.id);
    formData.append('order_id', props.card.order.id);
    formData.append('comment', comment.value);

    const response = await artService.upload(formData);

    const isSuccess = Boolean(response?.success ?? response?.id);

    if (!isSuccess) {
      throw new Error(response?.message || 'Resposta inválida ao enviar a arte.');
    }

    if (props.card) {
      props.card.status = ORDER_BUDGET_STATUS.PENDING_REVIEW;
    }

    selectedFile.value = null;
    comment.value = '';
    if (fileInputRef.value) {
      fileInputRef.value.value = '';
    }

    toast.success(response.message || 'Arte carregada com sucesso.');

    emit('art-uploaded');
  } catch (error) {
    console.error('Erro ao carregar arte:', error);
    const errorMessage = error.response?.data?.message || 'Erro ao carregar arte. Tente novamente.';

    if (window.Swal) {
      window.Swal.fire('Erro!', errorMessage, 'error');
    } else {
      alert(errorMessage);
    }
  } finally {
    uploading.value = false;
  }
}

// Resetar quando o card mudar
watch(
  () => props.card?.id,
  () => {
    showInput.value = false;
    selectedFile.value = null;
    comment.value = '';
    if (fileInputRef.value) {
      fileInputRef.value.value = '';
    }
  },
);
</script>

<style lang="scss" scoped>
.load-art-section {
  margin-bottom: 24px;

  &:last-child {
    margin-bottom: 0;
  }
}

.load-art-section-form {
  margin-top: 12px;
}
</style>
