<template>
  <section class="content">
    <Page :title="collectionName || 'Subcategorias'" :back-to="{ name: 'CollectionModels' }">
      <template #actions v-if="isAdmin">
        <CollectionActions @saved="() => { fetchSubcategories(route.params.id); fetchCollections(); }" />
      </template>

      <div class="container py-4">
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
            class="col-12 col-sm-6 col-md-4"
          >
            <CollectionCard
              @click="viewSubcategoryImages(subcategory)"
              :src="subcategory.image_cover_url"
              :title="subcategory.name"
            />
          </div>
        </div>
      </div>
    </Page>
  </section>
</template>

<script setup>
import { onMounted, ref, useTemplateRef, computed } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import axios from 'axios';
import Page from '@/components/page/Page.vue';

// Alerts agora usam window.Swal.fire diretamente
import { useAuthStore } from '@/stores/auth';
import CollectionActions from './components/CollectionActions.vue';
import CollectionCard from './components/CollectionCard.vue';

const DEFAULT_COVER = '/assets/img/no-image.jpg';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const loading = ref(true);
const subcategories = ref([]);
const collectionName = ref('');
const collections = ref([]);
const isAdmin = computed(() => auth.hasPermission('manage collections'));
const normalizeSubcategory = (item = {}) => {
  return {
    id: Number(item.id ?? 0),
    name: (item.name ?? '').toString(),
    image_cover_url: item.image_cover_url || DEFAULT_COVER,
    images_count: Number(item.images_count ?? 0),
    parent_id: Number(item.parent_id ?? 0),
  };
};

const getSubcategoryBackground = (subcategory) => {
  const cover = subcategory.image_cover_url || DEFAULT_COVER;

  return {
    backgroundImage: `url("${cover}")`,
  };
};

const fetchSubcategories = async (categoryId) => {
  if (!categoryId) {
    return;
  }

  loading.value = true;
  try {
    // Buscar a categoria para obter o nome
    const { data: categoryData } = await axios.get(`v1/collection-categories/${categoryId}`);
    const categoryPayload = categoryData?.data ?? categoryData ?? {};
    collectionName.value = categoryPayload.name ?? 'Coleção';

    // Buscar os filhos (subcategorias)
    const { data } = await axios.get(`v1/collection-categories/children/${categoryId}`);
    const payload = data?.data ?? data ?? [];
    const subcategoriesList = Array.isArray(payload) ? payload : [];

    subcategories.value = subcategoriesList.map(normalizeSubcategory);
  } catch (error) {
    subcategories.value = [];
    window.Swal.fire({
      title: 'Erro!',
      text: 'Não foi possível carregar as subcategorias desta coleção.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
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

const fetchCollections = async () => {
  try {
    const { data } = await axios.get('v1/collection-categories', {
      params: { tree: true },
    });
    const payload = data?.data ?? data ?? {};
    const items = Array.isArray(payload) ? payload : (payload.items ?? []);
    // Filtrar apenas categorias raiz
    const rootCategories = items.filter((item) => !item.parent_id);
    collections.value = Array.isArray(rootCategories)
      ? rootCategories.map((item) => ({
          id: Number(item.id ?? 0),
          name: (item.name ?? '').toString(),
        }))
      : [];
  } catch (error) {
    collections.value = [];
  }
};

onMounted(async () => {
  // Carregar categorias ao abrir a página
  await fetchCollections();

  const categoryId = route.params.id;
  if (categoryId) {
    fetchSubcategories(Number(categoryId));
    document.title = 'Subcategorias';
  } else {
    router.push('/colecao-arts');
  }
});
</script>

<style scoped>
.subcategory-card {
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
  border-radius: 5px;
}

.subcategory-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.category-cover {
  overflow: hidden;
  border-radius: 8px;
}
</style>
