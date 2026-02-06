<template>
  <section class="content">
    <Page :title="subcategoryName || 'Imagens'" back-to="/colecao-arts">
      <template #actions>
        <CollectionActions />
      </template>

      <div class="container py-4">
        <div v-if="loading" class="text-center text-muted py-5">Carregando imagens...</div>
        <div v-else-if="!images.length" class="text-center text-muted py-5">
          Nenhuma imagem cadastrada nesta categoria.
        </div>
        <div v-else>
          <div class="row">
            <div v-for="image in images" :key="image.id" class="col-12 col-sm-6 col-md-4">
              <CollectionCard
                :src="image.url"
                :title="image.name || image.path_name"
                @on-cover-click="openModal(image)"
              >
                <template #actions>
                  <button
                    type="button"
                    class="image-gallery__favorite-btn"
                    :class="{ 'is-favorited': image.is_favorited }"
                    @click.stop="toggleFavorite(image)"
                    :title="
                      image.is_favorited ? 'Remover dos favoritos' : 'Adicionar aos favoritos'
                    "
                  >
                    <i class="fa fa-heart"></i>
                  </button>
                </template>
              </CollectionCard>
            </div>
          </div>
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
    </Page>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
// Alerts agora usam window.Swal.fire diretamente
import Page from '@/components/page/Page.vue';
import CollectionCard from './components/CollectionCard.vue';
import CollectionActions from './components/CollectionActions.vue';

const DEFAULT_COVER = 'https://via.placeholder.com/600x400/adb5bd/212529?text=Sem+imagem';

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
  is_favorited: image.is_favorited ?? false,
});

const fetchSubcategoryImages = async (categoryId) => {
  if (!categoryId) {
    return;
  }

  loading.value = true;
  try {
    const { data } = await axios.get(`v1/collection-categories/${categoryId}`);
    const payload = data?.data ?? data ?? {};

    subcategoryName.value = payload.name ?? '';

    if (payload.parent) {
      collectionName.value = payload.parent.name ?? '';
    }

    const imagesList = Array.isArray(payload.images) ? payload.images : [];
    const normalizedImages = imagesList.map(normalizeImage);

    // Check favorite status for each image
    await Promise.all(
      normalizedImages.map(async (image) => {
        try {
          const { data: favoriteData } = await axios.get(
            `v1/collection-images/${image.id}/check-favorite`,
          );
          image.is_favorited = favoriteData?.data?.is_favorited ?? false;
        } catch (error) {
          image.is_favorited = false;
        }
      }),
    );

    images.value = normalizedImages;
  } catch (error) {
    images.value = [];
    window.Swal.fire({
      title: 'Erro!',
      text: 'Não foi possível carregar as imagens desta categoria.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
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

const toggleFavorite = async (image) => {
  try {
    const { data } = await axios.post(`v1/collection-images/${image.id}/toggle-favorite`);
    image.is_favorited = data?.data?.is_favorited ?? false;
  } catch (error) {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Não foi possível atualizar o favorito. Tente novamente.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
  }
};

const goBack = () => {
  router.push('/colecao-arts');
};

onMounted(() => {
  const categoryId = route.params.id;
  if (categoryId) {
    fetchSubcategoryImages(Number(categoryId));
    document.title = 'Imagens da Categoria';
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

.image-gallery__image-wrapper {
  position: relative;
  width: 100%;
  height: 200px;
  overflow: hidden;
}

.image-gallery__item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
  display: block;
}

.image-gallery__item:hover img {
  transform: scale(1.05);
}

.image-gallery__favorite-btn {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  background: rgba(255, 255, 255, 0.9);
  border: none;
  border-radius: 50%;
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  z-index: 10;
  color: #6c757d;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.image-gallery__favorite-btn:hover {
  background: rgba(255, 255, 255, 1);
  transform: scale(1.1);
  color: #dc3545;
}

.image-gallery__favorite-btn.is-favorited {
  background: rgba(220, 53, 69, 0.9);
  color: #fff;
}

.image-gallery__favorite-btn.is-favorited:hover {
  background: rgba(220, 53, 69, 1);
  color: #fff;
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
