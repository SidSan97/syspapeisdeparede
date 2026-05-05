<template>
  <section class="content">
    <Page title="Meus Favoritos" back-to="/colecao-arts">
      <div class="container py-4">
        <div v-if="images.length" class="mb-3 py-2 d-flex align-items-center gap-2 flex-wrap">
          <button
            type="button"
            class="btn btn-primary"
            :disabled="selectedIds.size === 0 || downloading"
            @click="downloadSelected"
            title="Baixar imagens selecionadas"
          >
            <IconDownload :size="18" />

            {{ downloading ? 'Baixando...' : 'Baixar selecionadas' }}
            <span v-if="selectedIds.size > 0">({{ selectedIds.size }})</span>
          </button>
          <button
            type="button"
            class="btn btn-outline-default btn-sm"
            :disabled="downloading"
            @click="selectAll"
            title="Marcar todas as fotos"
          >
            <IconChecks :size="18" />

            Marcar todas
          </button>
          <button
            type="button"
            class="btn btn-outline-default btn-sm"
            :disabled="selectedIds.size === 0 || downloading"
            @click="clearSelection"
            title="Desmarcar todas as fotos"
          >
            <IconX :size="18" />

            Desmarcar todas
          </button>
        </div>
        <div v-if="loading" class="text-center text-muted py-5">Carregando imagens...</div>
        <div v-else-if="!images.length" class="text-center text-muted py-5">
          Nenhuma imagem favoritada ainda.
        </div>
        <div v-else class="image-gallery">
          <figure
            v-for="image in images"
            :key="image.id"
            class="image-gallery__item"
            :class="{ 'is-selected': selectedIds.has(image.id) }"
          >
            <div class="form-check position-absolute top-0 start-0 m-2 z-2" @click.stop>
              <input
                type="checkbox"
                class="form-check-input"
                :value="image.id"
                :checked="selectedIds.has(image.id)"
                @change="toggleSelection(image.id)"
              />
            </div>
            <div class="image-gallery__image-wrapper">
              <img
                :src="image.url"
                :alt="image.name || image.path_name"
                :title="image.name || image.path_name"
                loading="lazy"
                @error="handleImageError($event)"
                @click="openModal(image)"
              />
              <button
                type="button"
                class="image-gallery__favorite-btn is-favorited"
                @click.stop="toggleFavorite(image)"
                title="Remover dos favoritos"
              >
                <IconHeart :size="18" />
              </button>
            </div>
            <figcaption v-if="image.name" class="image-gallery__caption">
              {{ image.name }}
            </figcaption>
          </figure>
        </div>

        <Teleport to="body">
          <div v-if="modalImage" class="image-modal" @click.self="closeModal">
            <div class="image-modal__content">
              <button type="button" class="image-modal__close" @click="closeModal">
                <IconX :size="18" />
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
import { IconChecks, IconDownload, IconHeart, IconX } from '@tabler/icons-vue';
import Page from '@/components/page/Page.vue';
import {
  useFavoriteImagesService,
  DEFAULT_COVER,
} from '@/modules/collection-favorites-images/services/favoriteImages';

const {
  fetchFavoriteImages: fetchFavoriteImagesApi,
  toggleFavorite: toggleFavoriteApi,
  downloadImages,
} = useFavoriteImagesService();

const loading = ref(false);
const images = ref([]);
const modalImage = ref(null);
const selectedIds = ref(new Set());
const downloading = ref(false);

const toggleSelection = (id) => {
  const next = new Set(selectedIds.value);
  if (next.has(id)) {
    next.delete(id);
  } else {
    next.add(id);
  }
  selectedIds.value = next;
};

const selectAll = () => {
  selectedIds.value = new Set(images.value.map((img) => img.id));
};

const clearSelection = () => {
  selectedIds.value = new Set();
};

const fetchFavoriteImages = async () => {
  loading.value = true;
  try {
    images.value = await fetchFavoriteImagesApi();
  } catch {
    images.value = [];
    window.Swal.fire({
      title: 'Erro!',
      text: 'Não foi possível carregar suas imagens favoritas.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
  } finally {
    loading.value = false;
  }
};

const toggleFavorite = async (image) => {
  try {
    const isFavorited = await toggleFavoriteApi(image.id);
    if (!isFavorited) {
      images.value = images.value.filter((img) => img.id !== image.id);
    }
  } catch {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Não foi possível remover o favorito. Tente novamente.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
  }
};

const downloadSelected = async () => {
  const ids = Array.from(selectedIds.value);
  if (!ids.length) return;

  const toDownload = images.value.filter((img) => ids.includes(img.id));
  if (!toDownload.length) return;

  downloading.value = true;
  try {
    await downloadImages(toDownload);
    clearSelection();
  } catch {
    window.Swal.fire({
      title: 'Erro!',
      text: 'Não foi possível baixar as imagens. Tente novamente.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
  } finally {
    downloading.value = false;
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

onMounted(() => {
  fetchFavoriteImages();
  document.title = 'Meus Favoritos';
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

.image-gallery__item.is-selected {
  border-color: var(--bs-primary);
  box-shadow: 0 0 0 2px rgba(var(--bs-primary-rgb), 0.3);
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

.image-gallery__caption {
  margin: 0;
  padding: 0.75rem;
  font-size: 0.9rem;
  color: var(--bs-body-color);
  text-align: center;
  background: var(--bs-body-bg);
  border-top: 1px solid var(--bs-border-color);
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

.form-check-input {
  width: 25px;
  height: 25px;
}
</style>
