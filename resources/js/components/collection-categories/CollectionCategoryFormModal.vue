<template>
  <BaseModal v-model="isVisible" :title="modalTitle" :show-confirm-button="true">
    <template #body>
      <form @submit.prevent="handleSubmit">
        <div v-if="isSubcategory && parentCollection" class="mb-3">
          <label class="form-label">Categoria</label>
          <input
            :value="parentCollection.name"
            type="text"
            class="form-control"
            readonly
            disabled
          />
        </div>

        <BaseInput v-model.trim="form.name" label="Nome" form-group-class="mb-3" required />

        <div class="mb-3">
          <label for="paymentFile" class="form-label"> Imagem de capa </label>
          <input
            type="file"
            id="cover"
            class="form-control"
            :class="{ 'is-invalid': errors.cover }"
            @change="handleFileUpload"
            accept=".pdf,.jpg,.jpeg,.png,.heic"
            :required="!isEditing"
          />
          <div class="form-text">Formatos aceitos: JPG, PNG, GIF (máx. 5MB)</div>
          <div v-if="errors.cover" class="invalid-feedback">
            {{ errors.cover }}
          </div>

          <div v-if="filePreview" class="mt-2">
            <div class="alert alert-success d-flex justify-content-between align-items-center">
              <span>
                <IconFileCheck :size="16" />

                {{ filePreview.name }} ({{ formatFileSize(filePreview.size) }})
              </span>
              <button type="button" class="btn-close" @click="removeFile"></button>
            </div>
            <div v-if="isImagePreview" class="mt-2">
              <img :src="imagePreviewUrl" class="img-thumbnail" style="max-height: 200px" />
            </div>
          </div>

          <div v-else-if="existingImageUrl && !filePreview" class="mt-2">
            <img :src="asset(existingImageUrl)" class="img-thumbnail" style="max-height: 200px" />
          </div>
        </div>
      </form>
    </template>

    <template #footer>
      <button type="button" class="btn btn-subtle" @click="handleClose">Cancelar</button>
      <button type="submit" class="btn btn-primary" @click="handleSubmit" :disabled="loading">
        Salvar
      </button>
    </template>
  </BaseModal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { asset } from '@/composables/useAsset';
import { IconFileCheck } from '@tabler/icons-vue';
import BaseInput from '@/components/common/BaseInput.vue';
import BaseModal from '@/components/common/BaseModal.vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  collection: {
    type: Object,
    default: null,
  },
  parentCollection: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'close', 'saved']);

const form = reactive({ name: '', imageFile: null, imagePreview: null });
const file = ref(null);
const filePreview = ref(null);
const imagePreviewUrl = ref(null);
const errors = ref({});

const existingImageUrl = computed(() => props.collection?.image_cover_url ?? null);

watch(
  () => props.collection,
  (collection) => {
    form.name = collection?.name ?? '';
    file.value = null;
    filePreview.value = null;
    imagePreviewUrl.value = null;
    errors.value = {};
  },
  { immediate: true },
);

const isEditing = computed(() => Boolean(props.collection?.id));
const isSubcategory = computed(() => Boolean(props.parentCollection?.id));

const modalTitle = computed(() => {
  if (isSubcategory.value) {
    return isEditing.value ? 'Editar subcategoria' : 'Criar nova subcategoria';
  }
  return isEditing.value ? 'Editar categoria' : 'Criar nova categoria';
});

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
    const allowedTypes = ['image/gif', 'image/jpeg', 'image/png', 'image/jpg'];

    if (selectedFile.size > maxSize) {
      errors.value.cover = 'Arquivo muito grande. Máximo 5MB';
      return;
    }

    if (!allowedTypes.includes(selectedFile.type)) {
      errors.value.cover = 'Formato não suportado. Use GIF, JPG ou PNG';
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

    delete errors.value.cover;
  }
};

const removeFile = () => {
  file.value = null;
  filePreview.value = null;
  imagePreviewUrl.value = null;
  const fileInput = document.getElementById('cover');
  if (fileInput) fileInput.value = '';
};

const validateForm = () => {
  errors.value = {};

  if (!form.name) {
    errors.value.name = 'Nome é obrigatório';
  }

  return Object.keys(errors.value).length === 0;
};

function handleClose() {
  emit('close');
}

async function handleSubmit() {
  if (!validateForm()) return;

  const formData = new FormData();

  formData.append('name', form.name?.trim());

  if (props.parentCollection?.id) formData.append('parent_id', props.parentCollection.id);

  if (file.value) formData.append('image_cover', file.value);

  emit('saved', formData, props.collection);
}
</script>
