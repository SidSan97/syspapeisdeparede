import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

const STORAGE_KEY = 'collection-favorites';

export const useCollectionFavoritesStore = defineStore('collectionFavorites', () => {
  const favorites = ref(load());

  const favoriteIds = computed(() => new Set(favorites.value.map((f) => f.id)));

  const isFavorited = (id) => favoriteIds.value.has(id);

  const toggle = (image) => {
    if (isFavorited(image.id)) {
      favorites.value = favorites.value.filter((f) => f.id !== image.id);
    } else {
      favorites.value = [...favorites.value, pick(image)];
    }
    persist(favorites.value);
  };

  const remove = (id) => {
    favorites.value = favorites.value.filter((f) => f.id !== id);
    persist(favorites.value);
  };

  return { favorites, favoriteIds, isFavorited, toggle, remove };
});

function pick(image) {
  return {
    id: image.id,
    url: image.url,
    name: image.name ?? '',
    path_name: image.path_name ?? '',
  };
}

function load() {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]');
  } catch {
    return [];
  }
}

function persist(items) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
}
