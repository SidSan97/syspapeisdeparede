<template>
  <section class="content">
    <div class="container py-4">
      <header class="mb-4 text-center text-md-start">
        <h1 class="h3 mb-2 text-primary fw-semibold">Coleções</h1>
        <p class="text-muted mb-0">
          Navegue pelas coleções e visualize as imagens cadastradas.
        </p>
      </header>

      <div v-if="loadingCollections" class="text-center text-muted py-5">
        Carregando coleções...
      </div>
      <div v-else-if="!collections.length" class="text-center text-muted py-5">
        Nenhuma coleção disponível no momento.
      </div>
      <div v-else class="collection-cards">
        <article
          v-for="collection in collections"
          :key="collection.id"
          class="collection-card"
          @click="selectCollection(collection)"
        >
          <div class="collection-card__image-wrapper">
            <div class="collection-card__image" :style="getCollectionBackground(collection)"></div>
            <div class="collection-card__image-overlay"></div>
          </div>
          <div class="collection-card__info">
            <h2 class="collection-card__title">{{ collection.name }}</h2>
            <p class="collection-card__meta">
              {{ formatCount(collection.images_count) }}
            </p>
          </div>
        </article>
      </div>

      <section v-if="selectedCollection" class="selected-collection mt-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h3 class="h5 mb-1">{{ selectedCollection.name }}</h3>
            <p class="text-muted mb-0">
              {{ formatSubcategoriesCount(currentSubcategories.length) }}
            </p>
          </div>
          <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
            :disabled="loadingSubcategories"
            @click="clearSelection"
          >
            Voltar às coleções
          </button>
        </div>

        <div v-if="loadingSubcategories" class="text-center text-muted py-4">
          Carregando subcategorias...
        </div>
        <div v-else-if="!currentSubcategories.length" class="text-center text-muted py-4">
          Nenhuma subcategoria cadastrada nesta coleção.
        </div>
        <div v-else class="subcategories-grid">
          <div
            v-for="subcategory in currentSubcategories"
            :key="subcategory.id"
            class="subcategory-card"
            @click="viewSubcategoryImages(subcategory)"
          >
            <div class="subcategory-card__image-wrapper">
              <div
                class="subcategory-card__image"
                :style="getSubcategoryBackground(subcategory)"
              ></div>
              <div class="subcategory-card__image-overlay"></div>
            </div>
            <div class="subcategory-card__info">
              <h4 class="subcategory-card__title">{{ subcategory.name }}</h4>
              <p class="subcategory-card__meta">
                {{ formatCount(subcategory.images_count) }}
              </p>
            </div>
          </div>
        </div>
      </section>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { swalError } from '../../../utils/alerts';

const DEFAULT_COVER =
  'https://via.placeholder.com/600x400/adb5bd/212529?text=Sem+imagem';

const router = useRouter();

const collections = ref([]);
const loadingCollections = ref(false);
const loadingSubcategories = ref(false);
const selectedCollectionId = ref(null);
const selectedCollection = computed(() =>
  collections.value.find((collection) => collection.id === selectedCollectionId.value) ?? null
);
const collectionSubcategories = reactive({});

const currentSubcategories = computed(() => {
  if (!selectedCollectionId.value) {
    return [];
  }

  return collectionSubcategories[selectedCollectionId.value] ?? [];
});

const normalizeCollection = (item = {}) => {
  // Calcular total de imagens das subcategorias
  let totalImages = 0;
  if (item.subcategories && Array.isArray(item.subcategories)) {
    totalImages = item.subcategories.reduce((sum, sub) => sum + Number(sub.images_count ?? 0), 0);
  }

  return {
    id: Number(item.id ?? 0),
    name: (item.name ?? '').toString(),
    images_count: totalImages || Number(item.images_count ?? item.imagesCount ?? 0),
    cover: item.cover ?? item.preview ?? DEFAULT_COVER,
  };
};

const normalizeSubcategory = (item = {}) => {
  // Pegar a primeira imagem como cover
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

const formatCount = (count) => {
  const total = Number(count ?? 0);
  return total === 1 ? '1 imagem' : `${total} imagens`;
};

const handleImageError = (event) => {
  event.target.src = DEFAULT_COVER;
};

const getSubcategoryBackground = (subcategory) => {
  const cover = subcategory.cover && subcategory.cover !== DEFAULT_COVER ? subcategory.cover : DEFAULT_COVER;

  return {
    backgroundImage: `url("${cover}")`,
  };
};

const formatSubcategoriesCount = (count) => {
  const total = Number(count ?? 0);
  return total === 1 ? '1 subcategoria' : `${total} subcategorias`;
};

const fetchCollections = async () => {
  loadingCollections.value = true;
  try {
    const { data } = await axios.get('v1/collection-arts', {
      params: { per_page: 100 },
    });

    const payload = data?.data ?? data ?? {};
    const items = payload.items ?? payload ?? [];
    collections.value = Array.isArray(items)
      ? items.map(normalizeCollection).map((collection) => ({
          ...collection,
          cover:
            collection.cover && collection.cover !== DEFAULT_COVER
              ? buildStorageUrl(collection.cover)
              : DEFAULT_COVER,
        }))
      : [];
  } catch (error) {
    collections.value = [];
    swalError('Não foi possível carregar as coleções. Atualize a página e tente novamente.');
  } finally {
    loadingCollections.value = false;
  }
};

const fetchCollectionSubcategories = async (collectionId) => {
  if (!collectionId) {
    return;
  }

  loadingSubcategories.value = true;
  try {
    const { data } = await axios.get(`v1/collection-arts/${collectionId}`);
    const payload = data?.data ?? data ?? {};

    // Coletar subcategorias
    const subcategories = [];
    if (payload.subcategories && Array.isArray(payload.subcategories)) {
      subcategories.push(...payload.subcategories);
    }

    const normalized = subcategories.map(normalizeSubcategory);
    collectionSubcategories[collectionId] = normalized;

    // Atualizar cover da coleção com a primeira imagem da primeira subcategoria
    if (normalized.length > 0 && normalized[0].cover !== DEFAULT_COVER) {
      const index = collections.value.findIndex((item) => item.id === collectionId);
      if (index !== -1) {
        const updated = {
          ...collections.value[index],
          cover: normalized[0].cover,
        };
        collections.value.splice(index, 1, updated);
      }
    }
  } catch (error) {
    collectionSubcategories[collectionId] = [];
    swalError('Não foi possível carregar as subcategorias desta coleção.');
  } finally {
    loadingSubcategories.value = false;
  }
};

const selectCollection = async (collection) => {
  if (!collection?.id) {
    return;
  }

  selectedCollectionId.value = collection.id;

  if (!collectionSubcategories[collection.id]) {
    await fetchCollectionSubcategories(collection.id);
  }
};

const clearSelection = () => {
  selectedCollectionId.value = null;
};

const viewSubcategoryImages = (subcategory) => {
  if (!subcategory?.id) {
    return;
  }

  router.push(`/colecao-arts/subcategoria/${subcategory.id}`);
};

onMounted(() => {
  fetchCollections();
});
</script>

<style scoped>
.collection-cards {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
}

.collection-card {
  border-radius: 1.25rem;
  overflow: hidden;
  cursor: pointer;
  background: var(--bs-body-bg);
  box-shadow: 0 1.5rem 3rem rgba(15, 15, 15, 0.08);
  transition: transform 0.25s ease, box-shadow 0.25s ease;
  position: relative;
}

.collection-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 1.75rem 3.5rem rgba(15, 15, 15, 0.12);
}

.collection-card__image-wrapper {
  position: relative;
  padding-top: 66%;
  overflow: hidden;
}

.collection-card__image {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  transition: transform 0.3s ease, filter 0.3s ease;
}

.collection-card:hover .collection-card__image {
  transform: scale(1.05);
  filter: brightness(1.05);
}

.collection-card__image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(180deg, rgba(15, 23, 42, 0) 40%, rgba(15, 23, 42, 0.55) 100%);
}

.collection-card__info {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 1.25rem 1.5rem;
  color: #fff;
}

.collection-card__title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.collection-card__meta {
  margin: 0.35rem 0 0;
  font-size: 0.9rem;
  opacity: 0.85;
}

.selected-collection {
  border: 1px solid var(--bs-border-color);
  border-radius: 1.25rem;
  padding: 1.75rem;
  background: var(--bs-body-bg);
  box-shadow: 0 1rem 2.5rem rgba(15, 15, 15, 0.08);
}

.subcategories-grid {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
}

.subcategory-card {
  border-radius: 1.25rem;
  overflow: hidden;
  cursor: pointer;
  background: var(--bs-body-bg);
  box-shadow: 0 1.5rem 3rem rgba(15, 15, 15, 0.08);
  transition: transform 0.25s ease, box-shadow 0.25s ease;
  position: relative;
}

.subcategory-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 1.75rem 3.5rem rgba(15, 15, 15, 0.12);
}

.subcategory-card__image-wrapper {
  position: relative;
  padding-top: 66%;
  overflow: hidden;
}

.subcategory-card__image {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  transition: transform 0.3s ease, filter 0.3s ease;
}

.subcategory-card:hover .subcategory-card__image {
  transform: scale(1.05);
  filter: brightness(1.05);
}

.subcategory-card__image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(180deg, rgba(15, 23, 42, 0) 40%, rgba(15, 23, 42, 0.55) 100%);
}

.subcategory-card__info {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 1.25rem 1.5rem;
  color: #fff;
}

.subcategory-card__title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.subcategory-card__meta {
  margin: 0.35rem 0 0;
  font-size: 0.9rem;
  opacity: 0.85;
}
</style>

