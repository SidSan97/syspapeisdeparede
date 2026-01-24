<template>
  <section class="content">
    <div class="container py-4">
      <!-- Header -->
      <header class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-semibold">Coleção</h1>
        <div class="d-flex gap-2">
          <button class="btn btn-primary" type="button" @click="openAddModal">
            Adicionar
          </button>
          <button
            class="btn btn-default"
            type="button"
            @click="goToFavorites"
          >
            Meus favoritos
          </button>
          <div class="dropdown" v-if="isAdmin">
            <button
              class="btn btn-subtle"
              type="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              <i class="fa fa-ellipsis-v"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <RouterLink to="/settings/colecoes" class="dropdown-item">
                    Administração de Categorias
                </RouterLink>
              </li>
            </ul>
          </div>
        </div>
      </header>

      <!-- Loading State -->
      <div v-if="loadingCollections" class="text-center text-muted py-5">
        <div class="spinner-border" role="status">
          <span class="visually-hidden">Carregando...</span>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="!collections.length" class="text-center text-muted py-5">
        <p class="mb-0">Nenhuma coleção disponível no momento.</p>
      </div>

      <!-- Collections Grid -->
      <div v-else class="row g-3">
        <div
          v-for="collection in collections"
          :key="collection.id"
          class="col-12 col-sm-6 col-md-4 col-lg-3"
        >
          <div
            class="card border-0 shadow-sm h-100 collection-card"
            style="cursor: pointer;"
            @click="viewCollectionSubcategories(collection)"
          >
            <div class="position-relative" style="padding-top: 66.67%; overflow: hidden;">
              <div
                class="position-absolute top-0 start-0 w-100 h-100"
                :style="getCollectionBackground(collection)"
                style="background-size: cover; background-position: center; background-repeat: no-repeat;"
              ></div>
              <div
                class="position-absolute bottom-0 start-0 w-100 text-white p-3"
                style="background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.6) 100%);"
              >
                <h5 class="mb-0 fw-semibold">{{ collection.name }}</h5>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

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
                    style="cursor: pointer;"
                  >
                    Escolher Arquivo
                  </label>
                  <span class="text-muted">{{ selectedFileName || 'Nenhum arquivo escolhido' }}</span>
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

              <!-- Subcategoria (opcional) -->
              <div class="mb-3">
                <label for="collection-subcategory" class="form-label">Subcategoria (opcional)</label>
                <div v-if="loadingSubcategories" class="form-select d-flex align-items-center justify-content-center" style="min-height: 38px;">
                  <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                  <span>Carregando...</span>
                </div>
                <select
                  v-else
                  class="form-select"
                  id="collection-subcategory"
                  v-model="formData.subcategory_id"
                >
                  <option value="">Usar categoria selecionada</option>
                  <option
                    v-for="subcategory in availableSubcategories"
                    :key="subcategory.id"
                    :value="subcategory.id"
                  >
                    {{ subcategory.name }}
                  </option>
                </select>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary"
              data-bs-dismiss="modal"
            >
              Cancelar
            </button>
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
  </section>
</template>

<script setup>
import { onMounted, ref, useTemplateRef, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
// Alerts agora usam window.Swal.fire diretamente
import { useAuthStore } from '@/stores/auth';

const DEFAULT_COVER =
  'https://via.placeholder.com/600x400/adb5bd/212529?text=Sem+imagem';

const router = useRouter();

const collections = ref([]);
const loadingCollections = ref(true);
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
const auth = useAuthStore();
const isAdmin = computed(() => auth.isAdmin());
const normalizeCollection = (item = {}) => {
  let totalImages = 0;

  // Contar imagens diretas da categoria
  if (item.images && Array.isArray(item.images)) {
    totalImages += item.images.length;
  } else if (item.images_count) {
    totalImages += Number(item.images_count);
  }

  // Contar imagens dos filhos (children)
  if (item.children && Array.isArray(item.children) && item.children.length > 0) {
    totalImages += item.children.reduce((sum, child) => {
      const childImages = child.images_count ?? (child.images?.length ?? 0);
      return sum + Number(childImages);
    }, 0);
  }

  return {
    id: Number(item.id ?? 0),
    name: (item.name ?? '').toString(),
    image_cover_url: item.image_cover_url || DEFAULT_COVER,
    images_count: totalImages,
  };
};

const getCollectionBackground = (collection) => {
  const cover = collection.image_cover_url || DEFAULT_COVER;

  return {
    backgroundImage: `url("${cover}")`,
  };
};

const fetchCollections = async () => {
  loadingCollections.value = true;
  try {
    const { data } = await axios.get('v1/collection-categories', {
      params: { tree: true },
    });

    const payload = data?.data ?? data ?? {};
    const items = Array.isArray(payload) ? payload : (payload.items ?? []);

    // Normalizar as coleções (apenas categorias raiz)
    collections.value = Array.isArray(items)
      ? items.filter(item => !item.parent_id).map(normalizeCollection)
      : [];
  } catch (error) {
    collections.value = [];
    window.Swal.fire({
      title: 'Erro!',
      text: 'Não foi possível carregar as coleções. Atualize a página e tente novamente.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
  } finally {
    loadingCollections.value = false;
  }
};

const viewCollectionSubcategories = (collection) => {
  if (!collection?.id) {
    return;
  }

  router.push(`/colecao-arts/colecao/${collection.id}`);
};

const goToFavorites = () => {
  router.push('/colecao-arts/favoritos');
};

const fetchCollectionsForModal = async () => {
  try {
    const { data } = await axios.get('v1/collection-categories', {
      params: { tree: true },
    });
    const payload = data?.data ?? data ?? {};
    const items = Array.isArray(payload) ? payload : (payload.items ?? []);
    // Buscar todas as categorias (raiz e filhos) para o select
    const allCategories = [];
    const flattenCategories = (cats, parentName = '') => {
      cats.forEach(cat => {
        const displayName = parentName ? `${parentName} > ${cat.name}` : cat.name;
        allCategories.push({
          id: Number(cat.id ?? 0),
          name: displayName,
          parent_id: cat.parent_id,
        });
        if (cat.children && Array.isArray(cat.children)) {
          flattenCategories(cat.children, displayName);
        }
      });
    };
    flattenCategories(items.filter(item => !item.parent_id));
    availableCollections.value = allCategories;
  } catch (error) {
    availableCollections.value = [];
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
    const categoryId = Number(formData.value.subcategory_id || formData.value.collection_art_id);

    // Enviar imagem para collection_images usando o ID da categoria
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

onMounted(async () => {
  // Carregar categorias ao abrir a página
  await fetchCollectionsForModal();

  fetchCollections();
  document.title = 'Coleção';

  // Inicializar modal Bootstrap
  if (addModalRef.value) {
    addModal.value = new window.bootstrap.Modal(addModalRef.value);
  }
});
</script>

<style scoped>
.collection-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.collection-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
</style>
