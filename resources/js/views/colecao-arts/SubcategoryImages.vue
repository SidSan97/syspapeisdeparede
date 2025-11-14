<template>
  <section class="content">
    <div class="container py-4">
      <header class="mb-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
            @click="goBack"
          >
            <i class="fa fa-arrow-left me-2"></i>
            Voltar
          </button>
        </div>
        <h1 class="h3 mb-2 text-primary fw-semibold">{{ subcategoryName || 'Imagens' }}</h1>
        <p class="text-muted mb-0" v-if="collectionName">
          Coleção: {{ collectionName }}
        </p>
      </header>

      <div v-if="loading" class="text-center text-muted py-5">
        Carregando imagens...
      </div>
      <div v-else-if="!images.length" class="text-center text-muted py-5">
        Nenhuma imagem cadastrada nesta subcategoria.
      </div>
      <div v-else class="image-gallery">
        <figure
          v-for="image in images"
          :key="image.id"
          class="image-gallery__item"
        >
          <img
            :src="image.url"
            :alt="image.name || image.path_name"
            :title="image.name || image.path_name"
            loading="lazy"
            @error="handleImageError($event)"
            @click="openModal(image)"
          />
          <figcaption v-if="image.name" class="image-gallery__caption">
            {{ image.name }}
          </figcaption>
        </figure>
      </div>

      <Teleport to="body">
        <div v-if="modalImage" class="image-modal" @click.self="closeModal">
          <div class="image-modal__content">
            <button type="button" class="image-modal__close" @click="closeModal">
              <i class="fa fa-times"></i>
            </button>
            <img
              :src="modalImage.url"
              :alt="modalImage.name || modalImage.path_name"
              @error="handleImageError($event)"
            />
          </div>
        </div>
      </Teleport>
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
const images = ref([]);
const modalImage = ref(null);
const subcategoryName = ref('');
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

const resolveImageUrl = (url, pathName) => {
  if (url && /^https?:\/\//i.test(url)) {
    return url;
  }

  if (url && url.startsWith('/')) {
    const baseUrl = window.location.origin.replace(/\/$/, '');
    return `${baseUrl}${url}`;
  }

  return buildStorageUrl(url || pathName || '');
};

const normalizeImage = (image) => ({
  id: Number(image.id ?? 0),
  name: image.name ?? '',
  path_name: image.path_name ?? image.pathName ?? '',
  url: resolveImageUrl(image.url, image.path_name ?? image.pathName ?? ''),
});

const fetchSubcategoryImages = async (subcategoryId) => {
  if (!subcategoryId) {
    return;
  }

  loading.value = true;
  try {
    const { data } = await axios.get(`v1/collection-art-subcategories/${subcategoryId}`);
    const payload = data?.data ?? data ?? {};

    subcategoryName.value = payload.name ?? '';
    
    if (payload.collection_art) {
      collectionName.value = payload.collection_art.name ?? '';
    }

    const imagesList = Array.isArray(payload.images) ? payload.images : [];
    images.value = imagesList.map(normalizeImage);
  } catch (error) {
    images.value = [];
    swalError('Não foi possível carregar as imagens desta subcategoria.');
  } finally {
    loading.value = false;
  }
};

const handleImageError = (event) => {
  event.target.src = DEFAULT_COVER;
};

const openModal = (image) => {
  modalImage.value = image;
};

const closeModal = () => {
  modalImage.value = null;
};

const goBack = () => {
  router.push('/colecao-arts');
};

onMounted(() => {
  const subcategoryId = route.params.id;
  if (subcategoryId) {
    fetchSubcategoryImages(Number(subcategoryId));
    document.title = 'Imagens da Subcategoria';
  } else {
    goBack();
  }
});
</script>

<style scoped>
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
  display: flex;
  flex-direction: column;
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

.image-gallery__caption {
  margin: 0;
  padding: 0.75rem;
  font-size: 0.9rem;
  color: var(--bs-body-color);
  text-align: center;
  background: var(--bs-body-bg);
  border-top: 1px solid var(--bs-border-color);
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
  max-width: 95vw;
  max-height: 95vh;
  background: transparent;
  border-radius: 1.25rem;
  overflow: visible;
  box-shadow: 0 2rem 4rem rgba(0, 0, 0, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.image-modal__content img {
  max-width: 95vw;
  max-height: 95vh;
  width: auto;
  height: auto;
  object-fit: contain;
  border-radius: 0.5rem;
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
  z-index: 1;
}

.image-modal__close:hover {
  opacity: 0.85;
}
</style>

