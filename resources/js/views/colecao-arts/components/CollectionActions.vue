<script setup>
import { useAuthStore } from '@/stores/auth';
import { onMounted, ref, useTemplateRef } from 'vue';

const { isAdmin } = useAuthStore();

const availableCollections = ref([]);
const availableSubcategories = ref([]);
const loadingSubcategories = ref(false);
const addModalRef = useTemplateRef('addModalRef');
const fileInputRef = useTemplateRef('fileInputRef');
const addModal = ref(null);
const saving = ref(false);
const formData = ref({
  name: '',
  image: null,
  collection_art_id: '',
  subcategory_id: '',
});
const selectedFileName = ref('');

const saveCollection = async () => {
  if (!formData.value.name.trim()) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Por favor, preencha o nome.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    return;
  }

  // Validar se há categoria selecionada
  if (!formData.value.collection_art_id) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Por favor, selecione uma categoria.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    return;
  }

  if (!formData.value.subcategory_id) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Por favor, selecione uma subcategoria.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    return;
  }

  if (!formData.value.image) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Por favor, selecione uma imagem.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
    return;
  }

  saving.value = true;
  try {
    const categoryId = Number(formData.value.subcategory_id);

    const formDataToSend = new FormData();
    formDataToSend.append('collection_category_id', categoryId);
    formDataToSend.append('images[]', formData.value.image);
    formDataToSend.append('names[]', formData.value.name.trim());

    await axios.post('v1/collection-images', formDataToSend, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    window.Swal.fire({
      title: 'Imagem adicionada!',
      text: 'Imagem adicionada com sucesso!',
      confirmButtonText: 'Entendi!',
    });
    addModal.value?.hide();

    // Recarregar a lista
    await fetchCollections();
  } catch (error) {
    const errorMessage =
      error.response?.data?.message ||
      error.response?.data?.error ||
      'Não foi possível adicionar a imagem.';
    window.Swal.fire({
      title: 'Erro!',
      text: errorMessage,
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
  } finally {
    saving.value = false;
  }
};

const handleImageChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    formData.value.image = file;
    selectedFileName.value = file.name;
  } else {
    formData.value.image = null;
    selectedFileName.value = '';
  }
};

const fetchSubcategoriesForCollection = async (categoryId) => {
  if (!categoryId) {
    availableSubcategories.value = [];
    loadingSubcategories.value = false;
    return;
  }
  loadingSubcategories.value = true;
  availableSubcategories.value = [];
  formData.value.subcategory_id = '';
  try {
    // Buscar filhos da categoria selecionada
    const { data } = await axios.get(`v1/collection-categories/children/${categoryId}`);
    const payload = data?.data ?? data ?? [];
    availableSubcategories.value = Array.isArray(payload)
      ? payload.map((item) => ({
          id: Number(item.id ?? 0),
          name: (item.name ?? '').toString(),
        }))
      : [];
  } catch (error) {
    availableSubcategories.value = [];
  } finally {
    loadingSubcategories.value = false;
  }
};

const fetchCollectionsForModal = async () => {
  try {
    const { data } = await axios.get('v1/collection-categories', {
      params: { tree: true },
    });
    const payload = data?.data ?? data ?? {};
    const items = Array.isArray(payload) ? payload : (payload.items ?? []);
    const roots = items.filter((item) => !item.parent_id);

    availableCollections.value = roots.map((cat) => ({
      id: Number(cat.id ?? 0),
      name: (cat.name ?? '').toString(),
      parent_id: cat.parent_id ?? null,
    }));
  } catch (error) {
    availableCollections.value = [];
  }
};

const openAddModal = () => {
  if (addModal.value) {
    formData.value = {
      name: '',
      image: null,
      collection_art_id: '',
      subcategory_id: '',
    };
    selectedFileName.value = '';
    availableSubcategories.value = [];
    loadingSubcategories.value = false;
    if (fileInputRef.value) {
      fileInputRef.value.value = '';
    }
    addModal.value.show();
  }
};

onMounted(async () => {
  await fetchCollectionsForModal();

  if (addModalRef.value) {
    addModal.value = new window.bootstrap.Modal(addModalRef.value);
  }
});
</script>

<template>
  <button v-if="isAdmin" class="btn btn-outline-default" type="button" @click="openAddModal">
    Adicionar
  </button>
  <RouterLink to="/colecao-arts/favoritos" class="btn btn-outline-default">
    Meus favoritos
  </RouterLink>
  <div class="dropdown" v-if="isAdmin">
    <button
      class="btn btn-outline-default"
      type="button"
      data-bs-toggle="dropdown"
      aria-expanded="false"
    >
      <i class="fa fa-ellipsis-v fa-fw"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-end">
      <RouterLink to="/settings/colecoes" class="dropdown-item">Categorias</RouterLink>
    </div>
  </div>

  <Teleport to="body">
    <!-- Modal Adicionar Coleção -->
    <div
      class="modal fade"
      id="add-collection-modal"
      tabindex="-1"
      aria-labelledby="add-collection-modal-label"
      aria-hidden="true"
      ref="addModalRef"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="add-collection-modal-label">Adicionar item</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveCollection">
              <!-- Nome -->
              <div class="mb-3">
                <label for="collection-name" class="form-label">Nome</label>
                <input
                  type="text"
                  class="form-control"
                  id="collection-name"
                  v-model="formData.name"
                  placeholder="Digite o nome"
                  required
                />
              </div>

              <!-- Imagem -->
              <div class="mb-3">
                <label for="collection-image" class="form-label">Imagem</label>
                <div class="d-flex align-items-center gap-2">
                  <input
                    type="file"
                    class="form-control d-none"
                    id="collection-image"
                    accept="image/*"
                    @change="handleImageChange"
                    ref="fileInputRef"
                  />
                  <label
                    for="collection-image"
                    class="btn btn-outline-secondary mb-0"
                    style="cursor: pointer"
                  >
                    Escolher Arquivo
                  </label>
                  <span class="text-muted">{{
                    selectedFileName || 'Nenhum arquivo escolhido'
                  }}</span>
                </div>
              </div>

              <!-- Categoria -->
              <div class="mb-3">
                <label for="collection-category" class="form-label">Categoria</label>
                <select
                  class="form-select"
                  id="collection-category"
                  v-model="formData.collection_art_id"
                  @change="fetchSubcategoriesForCollection(formData.collection_art_id)"
                >
                  <option value="">Selecionar categoria</option>
                  <option
                    v-for="collection in availableCollections"
                    :key="collection.id"
                    :value="collection.id"
                  >
                    {{ collection.name }}
                  </option>
                </select>
              </div>

              <!-- Subcategoria -->
              <div class="mb-3">
                <label for="collection-subcategory" class="form-label"
                  >Subcategoria <span class="text-danger">*</span></label
                >
                <div
                  v-if="loadingSubcategories"
                  class="form-select d-flex align-items-center justify-content-center"
                  style="min-height: 38px"
                >
                  <span
                    class="spinner-border spinner-border-sm me-2"
                    role="status"
                    aria-hidden="true"
                  ></span>
                  <span>Carregando...</span>
                </div>
                <select
                  v-else
                  class="form-select"
                  id="collection-subcategory"
                  v-model="formData.subcategory_id"
                  :disabled="!formData.collection_art_id || !availableSubcategories.length"
                  required
                >
                  <option value="">
                    {{
                      formData.collection_art_id && !availableSubcategories.length
                        ? 'Esta categoria não possui subcategorias'
                        : 'Selecionar subcategoria'
                    }}
                  </option>
                  <option
                    v-for="subcategory in availableSubcategories"
                    :key="subcategory.id"
                    :value="subcategory.id"
                  >
                    {{ subcategory.name }}
                  </option>
                </select>
                <small
                  v-if="
                    formData.collection_art_id &&
                    !availableSubcategories.length &&
                    !loadingSubcategories
                  "
                  class="form-text text-muted"
                >
                  Selecione outra categoria que possua subcategorias.
                </small>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-subtle" data-bs-dismiss="modal">Cancelar</button>
            <button
              type="button"
              class="btn btn-primary"
              @click="saveCollection"
              :disabled="saving"
            >
              {{ saving ? 'Salvando...' : 'Salvar' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
