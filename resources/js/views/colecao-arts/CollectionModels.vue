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
            <img
              :src="collection.cover"
              :alt="collection.name"
              class="collection-card__image"
              loading="lazy"
              @error="handleImageError($event)"
            />
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
              {{ formatCount(currentImages.length) }}
            </p>
          </div>
          <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
            :disabled="loadingImages"
            @click="clearSelection"
          >
            Voltar às coleções
          </button>
        </div>

        <div v-if="loadingImages" class="text-center text-muted py-4">
          Carregando imagens...
        </div>
        <div v-else-if="!currentImages.length" class="text-center text-muted py-4">
          Nenhuma imagem cadastrada nesta coleção.
        </div>
        <div v-else class="image-gallery">
          <figure
            v-for="image in currentImages"
            :key="image.id"
            class="image-gallery__item"
          >
            <img
              :src="image.url"
              :alt="image.path_name"
              loading="lazy"
              @error="handleImageError($event)"
              @click="openModal(image)"
            />
          </figure>
        </div>
      </section>

      <Teleport to="body">
        <div v-if="modalImage" class="image-modal" @click.self="closeModal">
          <div class="image-modal__content">
            <button type="button" class="image-modal__close" @click="closeModal">
              <i class="fa fa-times"></i>
            </button>
            <img :src="modalImage.url" :alt="modalImage.path_name" @error="handleImageError($event)" />
          </div>
        </div>
      </Teleport>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { swalError } from '../../../utils/alerts';

const DEFAULT_COVER =
  'https://via.placeholder.com/600x400/adb5bd/212529?text=Sem+imagem';

const collections = ref([]);
const loadingCollections = ref(false);
const loadingImages = ref(false);
const selectedCollectionId = ref(null);
const selectedCollection = computed(() =>
  collections.value.find((collection) => collection.id === selectedCollectionId.value) ?? null
);
const collectionImages = reactive({});
const modalImage = ref(null);

const currentImages = computed(() => {
  if (!selectedCollectionId.value) {
    return [];
  }

  return collectionImages[selectedCollectionId.value] ?? [];
});

const normalizeCollection = (item = {}) => ({
  id: Number(item.id ?? 0),
  name: (item.name ?? '').toString(),
  images_count: Number(item.images_count ?? item.imagesCount ?? 0),
  cover: item.cover ?? item.preview ?? DEFAULT_COVER,
});

const normalizeImages = (images = []) =>
  images.map((image) => ({
    id: Number(image.id ?? 0),
    path_name: image.path_name ?? image.pathName ?? '',
    url: image.url ?? buildStorageUrl(image.path_name ?? image.pathName ?? ''),
  }));

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

const formatCount = (count) => {
  const total = Number(count ?? 0);
  return total === 1 ? '1 imagem' : `${total} imagens`;
};

const handleImageError = (event) => {
  event.target.src = DEFAULT_COVER;
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

const fetchCollectionImages = async (collectionId) => {
  if (!collectionId) {
    return;
  }

  loadingImages.value = true;
  try {
    const { data } = await axios.get(`v1/collection-arts/${collectionId}`);
    const payload = data?.data ?? data ?? {};
    const images = Array.isArray(payload.images) ? payload.images : [];
    collectionImages[collectionId] = normalizeImages(images);
  } catch (error) {
    collectionImages[collectionId] = [];
    swalError('Não foi possível carregar as imagens desta coleção.');
  } finally {
    loadingImages.value = false;
  }
};

const selectCollection = async (collection) => {
  if (!collection?.id) {
    return;
  }

  selectedCollectionId.value = collection.id;

  if (!collectionImages[collection.id]) {
    await fetchCollectionImages(collection.id);
  }
};

const clearSelection = () => {
  selectedCollectionId.value = null;
  modalImage.value = null;
};

const openModal = (image) => {
  modalImage.value = image;
};

const closeModal = () => {
  modalImage.value = null;
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
  object-fit: cover;
  transition: transform 0.3s ease;
}

.collection-card:hover .collection-card__image {
  transform: scale(1.05);
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

.image-gallery {
  display: grid;
  gap: 1.25rem;
}

@media (min-width: 576px) {
  .image-gallery {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  }
}

.image-gallery__item {
  margin: 0;
  border-radius: 1rem;
  overflow: hidden;
  border: 1px solid var(--bs-border-color);
  position: relative;
  background: var(--bs-secondary-bg);
  cursor: zoom-in;
}

.image-gallery__item img {
  width: 100%;
  height: 200px;
  object-fit: cover;
  transition: transform 0.3s ease;
  display: block;
}

.image-gallery__item:hover img {
  transform: scale(1.05);
}

.image-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(15, 23, 42, 0.75);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  backdrop-filter: blur(4px);
}

.image-modal__content {
  position: relative;
  max-width: min(90vw, 960px);
  max-height: min(90vh, 700px);
  background: var(--bs-body-bg);
  border-radius: 1.25rem;
  overflow: hidden;
  box-shadow: 0 2rem 4rem rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
}

.image-modal__content img {
  max-width: 100%;
  max-height: 75vh;
  object-fit: contain;
}

.image-modal__caption {
  margin: 0;
  padding: 1rem 1.5rem;
  font-size: 0.95rem;
  color: var(--bs-secondary-color);
}

.image-modal__close {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  background: rgba(0, 0, 0, 0.6);
  color: #fff;
  border: none;
  border-radius: 999px;
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: opacity 0.2s ease;
}

.image-modal__close:hover {
  opacity: 0.85;
}
</style>

