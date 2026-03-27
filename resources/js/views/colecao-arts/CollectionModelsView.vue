<template>
  <section class="content">
    <Page title="Coleção Arts">
      <template #actions >
        <CollectionActions @saved="fetchCollections" />
      </template>

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

      <!-- Search + Collections Grid -->
      <div v-else>
        <div class="row mb-4">
          <div class="col-12 col-md-6 col-lg-4">
            <label for="search-model" class="form-label small text-muted mb-1">
              Buscar pelo nome da coleção
            </label>
            <input
              id="search-model"
              v-model.trim="searchTerm"
              type="text"
              class="form-control"
              placeholder="Digite o nome da coleção"
            />
          </div>
        </div>

        <div v-if="!filteredCollections.length" class="text-center text-muted py-5">
          <p class="mb-0">
            Nenhum resultado encontrado para
            <strong v-if="searchTerm">"{{ searchTerm }}"</strong>
            <span v-else>os filtros atuais.</span>
          </p>
        </div>

        <div v-else class="row">
          <div
            v-for="collection in filteredCollections"
            :key="collection.id"
            class="col-12 col-sm-6 col-md-4"
          >
            <CollectionCard
              @click="viewCollectionSubcategories(collection)"
              :src="collection.image_cover_url"
              :title="collection.name"
            />
          </div>
        </div>
      </div>
    </Page>
  </section>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
// Alerts agora usam window.Swal.fire diretamente
import { useAuthStore } from '@/stores/auth';
import CollectionActions from './components/CollectionActions.vue';
import Page from '@/components/page/Page.vue';
import CollectionCard from './components/CollectionCard.vue';

const DEFAULT_COVER = '/assets/img/no-image.jpg';

const router = useRouter();

const collections = ref([]);
const searchTerm = ref('');
const loadingCollections = ref(true);

const auth = useAuthStore();
const isAdmin = computed(() => auth.hasPermission('manage collections'));

const normalizeCollection = (item = {}) => {
  let totalImages = 0;

  // Contar imagens diretas da categoria
  if (item.images && Array.isArray(item.images)) {
    totalImages += item.images.length;
  } else if (item.images_count) {
    totalImages += Number(item.images_count);
  }

  // Contar imagens dos filhos (children)
  if (item.children && Array.isArray(item.children) && item.children.length > 0) {
    totalImages += item.children.reduce((sum, child) => {
      const childImages = child.images_count ?? child.images?.length ?? 0;
      return sum + Number(childImages);
    }, 0);
  }

  return {
    id: Number(item.id ?? 0),
    name: (item.name ?? '').toString(),
    image_cover_url: item.image_cover_url || DEFAULT_COVER,
    images_count: totalImages,
  };
};

const getCollectionBackground = (collection) => {
  const cover = collection.image_cover_url || DEFAULT_COVER;

  return cover;
};

const filteredCollections = computed(() => {
  const term = searchTerm.value.trim().toLowerCase();

  if (!term) {
    return collections.value;
  }

  return collections.value.filter((collection) =>
    collection.name.toLowerCase().includes(term),
  );
});

const fetchCollections = async () => {
  loadingCollections.value = true;
  try {
    const { data } = await axios.get('v1/collection-categories', {
      params: { tree: true },
    });

    const payload = data?.data ?? data ?? {};
    const items = Array.isArray(payload) ? payload : (payload.items ?? []);

    // Normalizar as coleções (apenas categorias raiz)
    collections.value = Array.isArray(items)
      ? items.filter((item) => !item.parent_id).map(normalizeCollection)
      : [];
  } catch (error) {
    collections.value = [];
    window.Swal.fire({
      title: 'Erro!',
      text: 'Não foi possível carregar as coleções. Atualize a página e tente novamente.',
      icon: 'error',
      confirmButtonText: 'Entendi!',
    });
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

onMounted(async () => {
  fetchCollections();

  document.title = 'Coleção';
});
</script>
