<template>
  <div v-if="canLoad" class="load-art-section">
    <div class="form-check mb-3">
      <input
        class="form-check-input"
        type="checkbox"
        :id="`load-art-${cardId}`"
        v-model="showInput"
      />
      <label class="form-check-label" :for="`load-art-${cardId}`">
        Carregar arte
      </label>
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
        <i v-else class="fa fa-upload me-2"></i>
        {{ uploading ? 'Enviando...' : 'Enviar Arte' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useArtService } from '@/views/layouts/services/artService';
import { useAuthStore } from '@/stores/auth';

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

const auth = useAuthStore();
const artService = useArtService();

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
  if (!selectedFile.value || !props.card?.id || !props.card?.order?.user_id || !props.card?.order?.id || !auth.user?.id) {
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

    const response = await artService.uploadArt(formData);

    if (response?.success) {
      // Atualizar o card localmente
      if (props.card) {
        props.card.status = 'Pendente de Revisão';
        if (props.card.order) {
          props.card.order.status = 'Pendente de Revisão';
        }
      }

      // Limpar o formulário
      selectedFile.value = null;
      showInput.value = false;
      comment.value = '';
      if (fileInputRef.value) {
        fileInputRef.value.value = '';
      }

      if (window.Toast) {
        window.Toast.fire({
          icon: 'success',
          title: response.message || 'Arte carregada com sucesso',
        });
      }

      emit('art-uploaded');
    }
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
watch(() => props.card?.id, () => {
  showInput.value = false;
  selectedFile.value = null;
  comment.value = '';
  if (fileInputRef.value) {
    fileInputRef.value.value = '';
  }
});
</script>

<script>
import { computed } from 'vue';

export default {
  setup() {
    return {};
  }
};
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

