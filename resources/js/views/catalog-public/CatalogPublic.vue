<template>
  <div class="catalog-app">
    <!-- Navbar -->
    <nav class="navbar bg-white border-bottom shadow-sm py-3">
      <div class="container d-flex justify-content-between align-items-center">
        <span class="navbar-brand fw-bold fs-5 mb-0" style="cursor: pointer" @click="goToLevel(0)">
          {{ appName }}
        </span>
        <button
          class="btn btn-default d-flex align-items-center gap-2"
          @click="toggleShowFavorites"
        >
          <IconHeartFilled v-if="level === 3" :size="20" />
          <IconHeart v-else :size="20" />
          <span>{{ level === 3 ? 'Voltar ao catálogo' : 'Meus favoritos' }}</span>
        </button>
      </div>
    </nav>

    <div class="container py-4">
      <!-- Breadcrumb -->
      <nav v-if="level > 0 && level < 3" aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <a href="#" @click.prevent="goToLevel(0)">Coleções</a>
          </li>
          <li v-if="level >= 1" class="breadcrumb-item" :class="{ active: level === 1 }">
            <a v-if="level > 1" href="#" @click.prevent="goToLevel(1)">{{
              selectedCategory?.name
            }}</a>
            <span v-else>{{ selectedCategory?.name }}</span>
          </li>
          <li v-if="level === 2" class="breadcrumb-item active" aria-current="page">
            {{ selectedSubcategory?.name }}
          </li>
        </ol>
      </nav>

      <nav v-else-if="level === 3" aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <a href="#" @click.prevent="goToLevel(0)">Catálogo</a>
          </li>
          <li class="breadcrumb-item active" aria-current="page">Meus Favoritos</li>
        </ol>
      </nav>

      <!-- Header Search and Title -->
      <div class="row align-items-center mb-4 g-2">
        <div class="col">
          <h4 class="mb-0 fw-semibold text-dark">
            <span v-if="level === 0">Coleções</span>
            <span v-else-if="level === 1">{{ selectedCategory?.name }}</span>
            <span v-else-if="level === 2">{{ selectedSubcategory?.name }}</span>
            <span v-else-if="level === 3">Meus Favoritos</span>
          </h4>
        </div>

        <div class="col-12 col-md-auto">
          <div class="input-group input-group-prefix">
            <input
              type="text"
              class="form-control"
              :placeholder="searchPlaceholder"
              v-model.trim="searchTerm"
            />
            <span class="input-group-text"><IconSearch :size="18" /></span>
          </div>
        </div>
      </div>

      <!-- Skeleton loading -->
      <div v-if="loading" class="row g-3">
        <div v-for="i in 6" :key="i" class="col-12 col-sm-6 col-md-4 mb-4">
          <div class="card border-0 shadow-sm rounded-3">
            <div class="ratio ratio-4x3 placeholder-glow">
              <div class="placeholder bg-secondary rounded-top w-100 h-100"></div>
            </div>
            <div class="card-body">
              <span class="placeholder col-7 rounded"></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else-if="!filteredItems.length" class="text-center text-muted py-5">
        <IconSearch v-if="searchTerm" :size="64" class="opacity-25 mb-3" />
        <IconBox v-else :size="64" class="opacity-25 mb-3" />

        <h5 v-if="searchTerm" class="fw-normal">
          Nenhum resultado para <strong>"{{ searchTerm }}"</strong>.
        </h5>
        <h5 v-else-if="level === 0" class="fw-normal">Nenhuma coleção disponível no momento.</h5>
        <h5 v-else-if="level === 1" class="fw-normal">Nenhuma subcategoria encontrada.</h5>
        <h5 v-else-if="level === 2" class="fw-normal">
          Nenhuma imagem cadastrada nesta subcategoria.
        </h5>
        <h5 v-else-if="level === 3" class="fw-normal">Você ainda não salvou nenhum favorito.</h5>
      </div>

      <!-- Level 0/1: Category/subcategory grid -->
      <div v-else-if="level < 2" class="row">
        <div v-for="cat in filteredItems" :key="cat.id" class="col-12 col-sm-6 col-md-4">
          <CollectionCard
            :src="asset(cat.image_cover_url || defaultCover)"
            :title="cat.name"
            role="button"
            tabindex="0"
            @on-cover-click="level === 0 ? selectCategory(cat) : selectSubcategory(cat)"
            @keydown.enter="level === 0 ? selectCategory(cat) : selectSubcategory(cat)"
            @keydown.space.prevent="level === 0 ? selectCategory(cat) : selectSubcategory(cat)"
          />
        </div>
      </div>

      <!-- Level 2/3: Images grid -->
      <div v-else>
        <div class="row">
          <div v-for="img in filteredItems" :key="img.id" class="col-12 col-sm-6 col-md-4">
            <CollectionCard
              :src="asset(img.url || defaultCover)"
              :title="img.name || img.path_name || '—'"
              role="button"
              @on-cover-click="openModal(img)"
            >
              <template #actions>
                <div class="d-flex justify-content-between w-100">
                  <button
                    type="button"
                    class="btn btn-success d-flex align-items-center gap-1 shadow-sm"
                    @click.stop="shareWhatsApp(img)"
                    title="Compartilhar no WhatsApp"
                    style="pointer-events: auto; font-size: 0.875rem"
                  >
                    Compartilhar
                    <IconBrandWhatsapp :size="16" />
                  </button>

                  <button
                    type="button"
                    class="image-gallery__favorite-btn"
                    :class="{ 'is-favorited': isFavorited(img.id) }"
                    @click.stop="toggleFavorite(img)"
                    :title="
                      isFavorited(img.id) ? 'Remover dos favoritos' : 'Adicionar aos favoritos'
                    "
                    style="pointer-events: auto"
                  >
                    <IconHeartFilled v-if="isFavorited(img.id)" :size="18" />
                    <IconHeart v-else :size="18" />
                  </button>
                </div>
              </template>
            </CollectionCard>
          </div>
        </div>

        <!-- Pagination -->
        <div
          v-if="level === 2 && totalPages > 1"
          class="d-flex justify-content-center align-items-center mt-4 gap-2"
        >
          <button
            class="btn btn-outline-default"
            :disabled="currentPage === 1"
            @click="loadImages(currentPage - 1)"
          >
            <IconChevronLeft :size="18" />
          </button>
          <span class="text-muted small mx-2">{{ currentPage }} de {{ totalPages }}</span>
          <button
            class="btn btn-outline-default"
            :disabled="currentPage === totalPages"
            @click="loadImages(currentPage + 1)"
          >
            <IconChevronRight :size="18" />
          </button>
        </div>
      </div>
    </div>

    <!-- Image Modal -->
    <Teleport to="body">
      <div v-if="modalImage" class="image-modal" @click.self="closeModal">
        <div class="image-modal__content">
          <button type="button" class="image-modal__close" @click="closeModal">
            <IconX :size="20" />
          </button>
          <img
            :src="modalImage.url"
            :alt="modalImage.name || modalImage.path_name"
            @error="onImageError"
          />
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useStorage } from '@vueuse/core';
import CollectionCard from '@/views/colecao-arts/components/CollectionCard.vue';
import {
  IconSearch,
  IconHeart,
  IconHeartFilled,
  IconX,
  IconBrandWhatsapp,
  IconChevronLeft,
  IconChevronRight,
  IconBox,
} from '@tabler/icons-vue';
import { asset } from '@/composables/useAsset';

const appName = window.CatalogConfig?.appName || 'Catálogo';
const apiBaseUrl = window.CatalogConfig?.apiBaseUrl || '/api';
const defaultCover = '/assets/img/no-image.jpg';

// Favorites stored in Local Storage for unauthenticated/visitor users
const favorites = useStorage('public-catalog-favorites', []);

const isFavorited = (imgId) => {
  return favorites.value.some((f) => f.id === imgId);
};

const toggleFavorite = (img) => {
  if (isFavorited(img.id)) {
    favorites.value = favorites.value.filter((f) => f.id !== img.id);
  } else {
    // Save image with context
    favorites.value.push({
      ...img,
      categoryLabel: selectedCategory.value ? selectedCategory.value.name : '',
      subcategoryLabel: selectedSubcategory.value ? selectedSubcategory.value.name : '',
    });
  }
};

const toggleShowFavorites = () => {
  if (level.value === 3) {
    if (selectedSubcategory.value) {
      level.value = 2; // Volta para imagens da subcategoria atual
    } else if (selectedCategory.value) {
      goToLevel(1);
    } else {
      goToLevel(0);
    }
  } else {
    searchTerm.value = '';
    level.value = 3;
  }
};

// Navigation state
const level = ref(0); // 0=categories, 1=subcategories, 2=images, 3=favorites
const selectedCategory = ref(null);
const selectedSubcategory = ref(null);

// Data
const items = ref([]);
const loading = ref(false);
const searchTerm = ref('');
const modalImage = ref(null);
const currentPage = ref(1);
const totalPages = ref(1);

const searchPlaceholder = computed(() => {
  if (level.value === 0) return 'Buscar coleção...';
  if (level.value === 1) return 'Buscar subcategoria...';
  if (level.value === 3) return 'Buscar nos favoritos...';
  return 'Buscar imagem...';
});

const filteredItems = computed(() => {
  let sourceItems = level.value === 3 ? favorites.value : items.value;

  const term = searchTerm.value.toLowerCase();
  if (!term) return sourceItems;
  return sourceItems.filter((item) =>
    (item.name || item.path_name || '').toLowerCase().includes(term),
  );
});

// --- API ---

const apiFetch = async (url) => {
  const res = await fetch(url);
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
};

const loadRootCategories = async () => {
  loading.value = true;
  try {
    const data = await apiFetch(`${apiBaseUrl}/v1/collection-categories/children`);
    const payload = data?.data ?? data ?? [];
    items.value = Array.isArray(payload) ? payload : [];
  } catch {
    items.value = [];
  } finally {
    loading.value = false;
  }
};

const loadSubcategories = async (categoryId) => {
  loading.value = true;
  try {
    const data = await apiFetch(`${apiBaseUrl}/v1/collection-categories/children/${categoryId}`);
    const payload = data?.data ?? data ?? [];
    items.value = Array.isArray(payload) ? payload : [];
  } catch {
    items.value = [];
  } finally {
    loading.value = false;
  }
};

const loadImages = async (page = 1) => {
  if (!selectedSubcategory.value?.id) return;
  loading.value = true;
  currentPage.value = page;
  try {
    const url = `${apiBaseUrl}/public/catalog/items?categoria=${selectedSubcategory.value.id}&page=${page}`;
    const data = await apiFetch(url);
    items.value = data?.data ?? [];
    totalPages.value = data?.meta?.last_page ?? 1;
    currentPage.value = data?.meta?.current_page ?? page;
  } catch {
    items.value = [];
  } finally {
    loading.value = false;
  }
};

// --- Navigation ---

const selectCategory = async (cat) => {
  selectedCategory.value = cat;
  searchTerm.value = '';
  level.value = 1;
  await loadSubcategories(cat.id);
};

const selectSubcategory = async (sub) => {
  selectedSubcategory.value = sub;
  searchTerm.value = '';
  level.value = 2;
  await loadImages(1);
};

const goToLevel = async (targetLevel) => {
  searchTerm.value = '';

  if (targetLevel === 0) {
    level.value = 0;
    selectedCategory.value = null;
    selectedSubcategory.value = null;
    await loadRootCategories();
  } else if (targetLevel === 1) {
    level.value = 1;
    selectedSubcategory.value = null;
    await loadSubcategories(selectedCategory.value.id);
  }
};

// --- Modal ---
const openModal = (img) => {
  modalImage.value = img;
};
const closeModal = () => {
  modalImage.value = null;
};

// --- Helpers ---
const onImageError = (e) => {
  e.target.src = defaultCover;
};

const shareWhatsApp = (img) => {
  const categoryLabel =
    img.categoryLabel || selectedCategory.value?.name
      ? `${img.categoryLabel || selectedCategory.value?.name} - `
      : '';
  const subcategoryLabel =
    img.subcategoryLabel || selectedSubcategory.value?.name
      ? `${img.subcategoryLabel || selectedSubcategory.value?.name} | `
      : '';
  const name = img.name || img.path_name || 'Imagem';
  const text = `${categoryLabel}${subcategoryLabel}${name}\n${img.url || ''}`;

  window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
};

// --- Init ---
onMounted(async () => {
  const params = new URLSearchParams(window.location.search);
  const categoriaId = params.get('categoria');

  if (categoriaId) {
    // Deep link: abre direto nas imagens da subcategoria
    loading.value = true;
    try {
      const data = await apiFetch(`${apiBaseUrl}/v1/collection-categories/${categoriaId}`);
      const cat = data?.data ?? data ?? {};
      selectedSubcategory.value = { id: Number(categoriaId), name: cat.name || '' };

      if (cat.parent) {
        selectedCategory.value = { id: cat.parent.id, name: cat.parent.name || '' };
      }
    } catch {
      selectedSubcategory.value = { id: Number(categoriaId), name: '' };
    } finally {
      loading.value = false;
    }

    level.value = 2;
    await loadImages(1);
  } else {
    await loadRootCategories();
  }
});
</script>

<style>
.catalog-app {
  min-height: 100vh;
  background-color: var(--bs-body-bg, #f8f9fa);
}

.image-modal {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.8);
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
}

.image-modal__content img {
  max-width: 95vw;
  max-height: 90vh;
  width: auto;
  height: auto;
  object-fit: contain;
  border-radius: 0.5rem;
  display: block;
}

.image-modal__close {
  position: absolute;
  top: -1rem;
  right: -1rem;
  background: rgba(0, 0, 0, 0.8);
  color: #fff;
  border: none;
  border-radius: 50%;
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 1;
  font-size: 1rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
  transition: all 0.2s ease;
}

.image-modal__close:hover {
  background: rgba(220, 53, 69, 1);
  transform: scale(1.1);
}

.image-gallery__favorite-btn {
  background: rgba(255, 255, 255, 0.95);
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
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
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
}
</style>
