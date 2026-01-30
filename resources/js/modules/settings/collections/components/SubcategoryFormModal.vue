<template>
  <Teleport v-if="visible" to="body">
    <div>
      <div
        class="modal fade show d-block"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        aria-hidden="true"
        @click.self="handleClose"
      >
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ isEditing ? 'Editar subcategoria' : 'Nova subcategoria' }}</h5>
              <button type="button" class="btn-close" aria-label="Fechar" @click="handleClose"></button>
            </div>
            <form @submit.prevent="handleSubmit">
              <div class="modal-body">
                <div class="row g-3">
                  <div v-if="parentCollection" class="col-12">
                    <label class="form-label">Coleção</label>
                    <input
                      :value="parentCollection.name"
                      type="text"
                      class="form-control"
                      readonly
                      disabled
                    />
                  </div>
                  <div class="col-12">
                    <label for="subcategoryName" class="form-label">Nome da subcategoria</label>
                    <input
                      id="subcategoryName"
                      v-model.trim="form.name"
                      type="text"
                      class="form-control"
                      placeholder="Ex: Moderno"
                      maxlength="50"
                      required
                    />
                  </div>
                  <div class="col-12">
                    <label for="subcategoryImageCover" class="form-label">Imagem de capa</label>
                    <input
                      id="subcategoryImageCover"
                      ref="imageInputRef"
                      type="file"
                      class="form-control"
                      accept="image/*"
                      @change="handleImageChange"
                    />
                    <small class="text-muted">Formatos: JPG, PNG, GIF (máx. 5MB)</small>
                  </div>
                  <div v-if="form.imagePreview || currentImageUrl" class="col-12">
                    <label class="form-label">Preview</label>
                    <div class="position-relative d-inline-block">
                      <img
                        :src="form.imagePreview || currentImageUrl"
                        alt="Preview"
                        class="img-thumbnail"
                        style="max-width: 300px; max-height: 300px; object-fit: contain;"
                      />
                      <button
                        type="button"
                        class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2"
                        @click="removeImage"
                      >
                        <i class="fa fa-times"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-subtle" @click="handleClose">
                  Cancelar
                </button>
                <button type="submit" class="btn btn-primary" :disabled="isSaving">
                  <span v-if="isSaving" class="spinner-border spinner-border-sm me-2"></span>
                  {{ isEditing ? 'Salvar alterações' : 'Salvar subcategoria' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="modal-backdrop fade show"></div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { useCollectionService } from '../services/collectionService';

const props = defineProps({
  visible: { type: Boolean, default: false },
  subcategory: { type: Object, default: null },
  parentCollection: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);

const service = useCollectionService();
const form = reactive({ name: '', parent_id: null, imageFile: null, imagePreview: null });
const currentImageUrl = ref(null);
const imageInputRef = ref(null);
const isSaving = ref(false);

const isEditing = computed(() => Boolean(props.subcategory?.id));

function buildStorageUrl(path) {
  if (!path) return '';
  const base = window.location.origin.replace(/\/$/, '');
  return `${base}/storage/${path.replace(/^\//, '')}`;
}

function resolveImageUrl(url, path) {
  if (url && /^https?:\/\//i.test(url)) return url;
  if (url && url.startsWith('/')) {
    const base = window.location.origin.replace(/\/$/, '');
    return `${base}${url}`;
  }
  return buildStorageUrl(url || path || '');
}

function normalizeSubcategory(item = {}) {
  return {
    id: Number(item.id ?? 0),
    name: (item.name ?? '').toString(),
    parent_id: Number(item.parent_id ?? 0),
    image_cover: item.image_cover ?? null,
    image_cover_url: item.image_cover_url || (item.image_cover ? resolveImageUrl(null, item.image_cover) : null),
    images_count: Number(item.images_count ?? 0),
  };
}

function resetForm() {
  form.name = '';
  form.parent_id = null;
  form.imageFile = null;
  form.imagePreview = null;
  currentImageUrl.value = null;
  if (imageInputRef.value) imageInputRef.value.value = '';
}

function fillForm(s, parent) {
  if (!s) return;
  form.name = s.name ?? '';
  form.parent_id = s.parent_id ?? (parent?.id ?? null);
  form.imageFile = null;
  form.imagePreview = null;
  currentImageUrl.value = s.image_cover_url || null;
  if (imageInputRef.value) imageInputRef.value.value = '';
}

function onEscapeKey(event) {
  if (event.key === 'Escape') handleClose();
}

watch(() => props.visible, (v) => {
  if (v) {
    if (props.subcategory) fillForm(props.subcategory, props.parentCollection);
    else {
      resetForm();
      form.parent_id = props.parentCollection?.id ?? null;
    }
    document.addEventListener('keydown', onEscapeKey);
  } else {
    document.removeEventListener('keydown', onEscapeKey);
  }
});

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onEscapeKey);
});

function handleClose() {
  if (!isSaving.value) emit('close');
}

function handleImageChange(event) {
  const file = event.target.files?.[0];
  if (!file) {
    form.imageFile = null;
    form.imagePreview = null;
    return;
  }
  if (file.size > 5 * 1024 * 1024) {
    window.Swal.fire({ title: 'Erro!', text: 'A imagem não pode ser maior que 5MB.', icon: 'error', confirmButtonText: 'Entendi!' });
    event.target.value = '';
    form.imageFile = null;
    form.imagePreview = null;
    return;
  }
  if (!file.type.startsWith('image/')) {
    window.Swal.fire({ title: 'Erro!', text: 'Selecione um arquivo de imagem válido.', icon: 'error', confirmButtonText: 'Entendi!' });
    event.target.value = '';
    form.imageFile = null;
    form.imagePreview = null;
    return;
  }
  form.imageFile = file;
  currentImageUrl.value = null;
  const reader = new FileReader();
  reader.onload = (e) => { form.imagePreview = e.target.result; };
  reader.readAsDataURL(file);
}

function removeImage() {
  form.imageFile = null;
  form.imagePreview = null;
  currentImageUrl.value = null;
  if (imageInputRef.value) imageInputRef.value.value = '';
}

async function handleSubmit() {
  if (isSaving.value) return;
  const trimmed = form.name?.trim();
  if (!trimmed) {
    window.Swal.fire({ title: 'Erro!', text: 'Informe o nome da subcategoria.', icon: 'error', confirmButtonText: 'Entendi!' });
    return;
  }
  const parentId = form.parent_id ?? props.parentCollection?.id;
  if (!parentId) {
    window.Swal.fire({ title: 'Erro!', text: 'Coleção não informada.', icon: 'error', confirmButtonText: 'Entendi!' });
    return;
  }
  isSaving.value = true;
  try {
    const formData = new FormData();
    formData.append('name', trimmed);
    formData.append('parent_id', parentId);
    if (form.imageFile) formData.append('image_cover', form.imageFile);

    let raw;
    if (props.subcategory?.id) {
      raw = await service.updateSubcategory(props.subcategory.id, formData);
    } else {
      raw = await service.createSubcategory(formData);
    }
    const data = raw?.data ?? raw ?? {};
    const saved = normalizeSubcategory(data);
    emit('saved', saved);
    emit('close');
    window.Swal.fire({
      title: props.subcategory?.id ? 'Subcategoria atualizada!' : 'Subcategoria criada!',
      text: props.subcategory?.id ? 'Subcategoria atualizada com sucesso.' : 'Subcategoria criada com sucesso.',
      icon: 'success',
      confirmButtonText: 'Entendi!',
    });
  } catch (err) {
    const msg = err?.response?.data?.message ?? 'Não foi possível salvar a subcategoria.';
    window.Swal.fire({ title: 'Erro!', text: msg, icon: 'error', confirmButtonText: 'Entendi!' });
  } finally {
    isSaving.value = false;
  }
}
</script>
