<template>
  <section class="content">
    <div class="container py-4">
      <!-- Header -->
      <header class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-semibold">Coleção</h1>
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
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { swalError } from '../../../utils/alerts';

const DEFAULT_COVER =
  'https://via.placeholder.com/600x400/adb5bd/212529?text=Sem+imagem';

const router = useRouter();

const collections = ref([]);
const loadingCollections = ref(false);

const normalizeCollection = (item = {}) => {
  let totalImages = 0;
  let coverImage = DEFAULT_COVER;

  if (item.subcategories && Array.isArray(item.subcategories) && item.subcategories.length > 0) {
    // Calcular total de imagens
    totalImages = item.subcategories.reduce((sum, sub) => sum + Number(sub.images_count ?? 0), 0);

    // Buscar a primeira imagem da primeira subcategoria que tenha imagens
    for (const subcategory of item.subcategories) {
      if (subcategory.images && Array.isArray(subcategory.images) && subcategory.images.length > 0) {
        const firstImage = subcategory.images[0];
        // A API já retorna a URL completa
        if (firstImage.url) {
          coverImage = firstImage.url;
        } else if (firstImage.path_name || firstImage.pathName) {
          coverImage = buildStorageUrl(firstImage.path_name ?? firstImage.pathName);
        }
        break;
      }
    }
  }

  // Se não encontrou imagem nas subcategorias, tenta pegar das imagens diretas da coleção
  if (coverImage === DEFAULT_COVER && item.images && Array.isArray(item.images) && item.images.length > 0) {
    const firstImage = item.images[0];
    if (firstImage.url) {
      coverImage = firstImage.url;
    } else if (firstImage.path_name || firstImage.pathName) {
      coverImage = buildStorageUrl(firstImage.path_name ?? firstImage.pathName);
    }
  }

  return {
    id: Number(item.id ?? 0),
    name: (item.name ?? '').toString(),
    images_count: totalImages || Number(item.images_count ?? item.imagesCount ?? 0),
    cover: coverImage,
  };
};

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

const getCollectionBackground = (collection) => {
  const cover = collection.cover && collection.cover !== DEFAULT_COVER ? collection.cover : DEFAULT_COVER;

  return {
    backgroundImage: `url("${cover}")`,
  };
};

const fetchCollections = async () => {
  loadingCollections.value = true;
  try {
    const { data } = await axios.get('v1/collection-arts', {
      params: { per_page: 100 },
    });

    const payload = data?.data ?? data ?? {};
    const items = payload.items ?? payload ?? [];

    // Normalizar as coleções
    collections.value = Array.isArray(items) ? items.map(normalizeCollection) : [];

    // Buscar imagens para coleções que não têm cover
    const fetchPromises = collections.value.map(async (collection, index) => {
      if (collection.cover === DEFAULT_COVER) {
        try {
          const { data: detailData } = await axios.get(`v1/collection-arts/${collection.id}`);
          const detailPayload = detailData?.data ?? detailData ?? {};
          const normalized = normalizeCollection(detailPayload);
          if (normalized.cover !== DEFAULT_COVER) {
            collections.value[index].cover = normalized.cover;
          }
        } catch (error) {
          // Ignora erro e mantém DEFAULT_COVER
        }
      }
    });

    await Promise.all(fetchPromises);
  } catch (error) {
    collections.value = [];
    swalError('Não foi possível carregar as coleções. Atualize a página e tente novamente.');
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

onMounted(() => {
  fetchCollections();
  document.title = 'Coleção';
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
