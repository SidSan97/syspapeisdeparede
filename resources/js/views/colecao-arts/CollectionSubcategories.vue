<template>
  <section class="content">
    <div class="container py-4">
      <!-- Header -->
      <header class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-semibold">{{ collectionName || 'Coleção' }}</h1>
        <div class="d-flex gap-2">
          <button class="btn btn-primary" type="button">
            Adicionar
          </button>
          <button class="btn btn-outline-secondary" type="button">
            Meus favoritos
          </button>
          <div class="dropdown">
            <button
              class="btn btn-outline-secondary"
              type="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              <i class="fa fa-ellipsis-v"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="#">Opção 1</a></li>
              <li><a class="dropdown-item" href="#">Opção 2</a></li>
            </ul>
          </div>
        </div>
      </header>

      <!-- Loading State -->
      <div v-if="loading" class="text-center text-muted py-5">
        <div class="spinner-border" role="status">
          <span class="visually-hidden">Carregando...</span>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="!subcategories.length" class="text-center text-muted py-5">
        <p class="mb-0">Nenhuma subcategoria cadastrada nesta coleção.</p>
      </div>

      <!-- Subcategories Grid -->
      <div v-else class="row g-3">
        <div
          v-for="subcategory in subcategories"
          :key="subcategory.id"
          class="col-12 col-sm-6 col-md-4 col-lg-3"
        >
          <div
            class="card border-0 shadow-sm h-100 subcategory-card"
            style="cursor: pointer;"
            @click="viewSubcategoryImages(subcategory)"
          >
            <div class="position-relative" style="padding-top: 66.67%; overflow: hidden;">
              <div
                class="position-absolute top-0 start-0 w-100 h-100"
                :style="getSubcategoryBackground(subcategory)"
                style="background-size: cover; background-position: center; background-repeat: no-repeat;"
              ></div>
              <div
                class="position-absolute bottom-0 start-0 w-100 text-white p-3"
                style="background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.6) 100%);"
              >
                <h5 class="mb-0 fw-semibold">{{ subcategory.name }}</h5>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { swalError } from '../../../utils/alerts';

const DEFAULT_COVER =
  'https://via.placeholder.com/600x400/adb5bd/212529?text=Sem+imagem';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const subcategories = ref([]);
const collectionName = ref('');

const buildStorageUrl = (path) => {
  if (!path) {
    return DEFAULT_COVER;
  }

  if (/^https?:\/\//i.test(path)) {
    return path;
  }

  const baseUrl = window.location.origin.replace(/\/$/, '');
  return `${baseUrl}/storage/${String(path).replace(/^storage\//, '')}`;
};

const normalizeSubcategory = (item = {}) => {
  let cover = DEFAULT_COVER;
  if (item.images && Array.isArray(item.images) && item.images.length > 0) {
    const firstImage = item.images[0];
    cover = firstImage.url ?? buildStorageUrl(firstImage.path_name ?? firstImage.pathName ?? '');
  }

  return {
    id: Number(item.id ?? 0),
    name: (item.name ?? '').toString(),
    images_count: Number(item.images_count ?? 0),
    cover: cover,
  };
};

const getSubcategoryBackground = (subcategory) => {
  const cover = subcategory.cover && subcategory.cover !== DEFAULT_COVER ? subcategory.cover : DEFAULT_COVER;

  return {
    backgroundImage: `url("${cover}")`,
  };
};

const fetchSubcategories = async (collectionId) => {
  if (!collectionId) {
    return;
  }

  loading.value = true;
  try {
    const { data } = await axios.get(`v1/collection-arts/${collectionId}`);
    const payload = data?.data ?? data ?? {};

    collectionName.value = payload.name ?? 'Coleção';

    const subcategoriesList = [];
    if (payload.subcategories && Array.isArray(payload.subcategories)) {
      subcategoriesList.push(...payload.subcategories);
    }

    subcategories.value = subcategoriesList.map(normalizeSubcategory);
  } catch (error) {
    subcategories.value = [];
    swalError('Não foi possível carregar as subcategorias desta coleção.');
  } finally {
    loading.value = false;
  }
};

const viewSubcategoryImages = (subcategory) => {
  if (!subcategory?.id) {
    return;
  }

  router.push(`/colecao-arts/subcategoria/${subcategory.id}`);
};

onMounted(() => {
  const collectionId = route.params.id;
  if (collectionId) {
    fetchSubcategories(Number(collectionId));
    document.title = 'Subcategorias';
  } else {
    router.push('/colecao-arts');
  }
});
</script>

<style scoped>
.subcategory-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.subcategory-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
</style>

