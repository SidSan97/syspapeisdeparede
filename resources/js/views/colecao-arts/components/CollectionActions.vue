<script setup>
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import { onMounted, ref, useTemplateRef, computed } from 'vue';

const emit = defineEmits(['saved']);
const auth = useAuthStore();
const isAdmin = computed(() => auth.hasPermission('manage collections'));

const availableCollections = ref([]);
const selectedRootCategoryIds = ref([]);
const subcategoriesByRoot = ref({});
const loadingSubcategoriesForRoot = ref({});
const selectedSubcategoryIds = ref([]);
const addModalRef = useTemplateRef('addModalRef');
const fileInputRef = useTemplateRef('fileInputRef');
const addModal = ref(null);
const saving = ref(false);
const formData = ref({
  name: '',
  image: null,
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

  if (!selectedSubcategoryIds.value.length) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Por favor, selecione ao menos uma subcategoria.',
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
    const formDataToSend = new FormData();
    selectedSubcategoryIds.value.forEach((id) => {
      formDataToSend.append('collection_category_ids[]', id);
    });
    formDataToSend.append('images[]', formData.value.image);
    formDataToSend.append('names[]', formData.value.name.trim());

    const res = await axios.post('v1/collection-images', formDataToSend, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    const body = res?.data ?? {};

    if (body?.success === false) {
      throw new Error(body?.message ?? 'Erro ao enviar imagens.');
    }

    window.Swal.fire({
      title: 'Imagem adicionada!',
      text: 'Imagem adicionada com sucesso nas subcategorias selecionadas!',
      confirmButtonText: 'Entendi!',
    });
    addModal.value?.hide();

    emit('saved');
  } catch (error) {
    console.log(error);
    window.Swal.fire({
      title: 'Erro!',
      text: 'Não foi possível adicionar a imagem.',
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

const fetchSubcategoriesForRoot = async (rootId) => {
  if (!rootId) {
    return;
  }

  loadingSubcategoriesForRoot.value = {
    ...loadingSubcategoriesForRoot.value,
    [rootId]: true,
  };

  try {
    const { data } = await axios.get(`v1/collection-categories/children/${rootId}`);
    const payload = data?.data ?? data ?? [];
    const items = Array.isArray(payload)
      ? payload.map((item) => ({
          id: Number(item.id ?? 0),
          name: (item.name ?? '').toString(),
        }))
      : [];

    subcategoriesByRoot.value = {
      ...subcategoriesByRoot.value,
      [rootId]: items,
    };
  } catch (error) {
    subcategoriesByRoot.value = {
      ...subcategoriesByRoot.value,
      [rootId]: [],
    };
  } finally {
    loadingSubcategoriesForRoot.value = {
      ...loadingSubcategoriesForRoot.value,
      [rootId]: false,
    };
  }
};

const toggleRootCategory = async (rootId) => {
  const current = [...selectedRootCategoryIds.value];
  const index = current.indexOf(rootId);

  if (index >= 0) {
    current.splice(index, 1);
    selectedRootCategoryIds.value = current;

    const subcategories = subcategoriesByRoot.value[rootId] || [];
    if (subcategories.length) {
      const idsToRemove = subcategories.map((s) => s.id);
      selectedSubcategoryIds.value = selectedSubcategoryIds.value.filter(
        (id) => !idsToRemove.includes(id),
      );
    }
    return;
  }

  current.push(rootId);
  selectedRootCategoryIds.value = current;

  if (!subcategoriesByRoot.value[rootId]) {
    await fetchSubcategoriesForRoot(rootId);
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
    };
    selectedRootCategoryIds.value = [];
    selectedSubcategoryIds.value = [];
    subcategoriesByRoot.value = {};
    loadingSubcategoriesForRoot.value = {};
    selectedFileName.value = '';
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

              <!-- Categorias raiz -->
              <div class="mb-3">
                <label class="form-label">Categorias</label>
                <div class="d-flex flex-wrap gap-2">
                  <button
                    v-for="collection in availableCollections"
                    :key="collection.id"
                    type="button"
                    class="btn btn-sm"
                    :class="
                      selectedRootCategoryIds.includes(collection.id)
                        ? 'btn-primary'
                        : 'btn-outline-secondary'
                    "
                    @click="toggleRootCategory(collection.id)"
                  >
                    {{ collection.name }}
                  </button>
                </div>
              </div>

              <!-- Subcategorias por categoria selecionada -->
              <div class="mb-3">
                <label class="form-label">Subcategorias <span class="text-danger">*</span></label>

                <div v-if="!selectedRootCategoryIds.length" class="text-muted small">
                  Selecione pelo menos uma categoria para ver as subcategorias.
                </div>

                <div
                  v-for="rootId in selectedRootCategoryIds"
                  :key="`root-${rootId}`"
                  class="mb-2"
                >
                  <div class="fw-semibold mb-1">
                    {{
                      availableCollections.find((c) => c.id === rootId)?.name ||
                      'Categoria'
                    }}
                  </div>

                  <div
                    v-if="loadingSubcategoriesForRoot[rootId]"
                    class="d-flex align-items-center gap-2 text-muted small"
                  >
                    <span
                      class="spinner-border spinner-border-sm"
                      role="status"
                      aria-hidden="true"
                    ></span>
                    <span>Carregando subcategorias...</span>
                  </div>

                  <div v-else>
                    <div
                      v-if="(subcategoriesByRoot[rootId] || []).length"
                      class="d-flex flex-wrap gap-2"
                    >
                      <button
                        v-for="subcategory in subcategoriesByRoot[rootId]"
                        :key="subcategory.id"
                        type="button"
                        class="btn btn-sm"
                        :class="
                          selectedSubcategoryIds.includes(subcategory.id)
                            ? 'btn-primary'
                            : 'btn-outline-secondary'
                        "
                        @click="
                          selectedSubcategoryIds = selectedSubcategoryIds.includes(
                            subcategory.id,
                          )
                            ? selectedSubcategoryIds.filter((id) => id !== subcategory.id)
                            : [...selectedSubcategoryIds, subcategory.id]
                        "
                      >
                        {{ subcategory.name }}
                      </button>
                    </div>
                    <div v-else class="text-muted small">
                      Esta categoria não possui subcategorias.
                    </div>
                  </div>
                </div>

                <small
                  v-if="selectedSubcategoryIds.length"
                  class="form-text text-muted"
                >
                  A imagem será adicionada em todas as subcategorias selecionadas.
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
