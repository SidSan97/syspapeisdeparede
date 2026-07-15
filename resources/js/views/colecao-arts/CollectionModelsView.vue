<template>
  <section class="content">
    <Page title="Coleção Arts">
      <template #extra>
        <Suspense>
          <CollectionActions @saved="loadData" />
        </Suspense>
      </template>

      <!-- Loading -->
      <div v-if="loading" class="row g-3">
        <div v-for="i in 6" :key="i" class="col-12 col-sm-6 col-md-4">
          <CollectionCardSkeleton />
        </div>
      </div>

      <!-- Empty -->
      <div v-else-if="!hasCollections" class="text-center text-muted py-5">
        <p class="mb-0">Nenhuma coleção disponível no momento.</p>
      </div>

      <!-- Content -->
      <div v-else>
        <!-- Search -->
        <div class="row my-4">
          <div class="col-md-3">
            <div class="input-group input-group-prefix">
              <input
                type="text"
                class="form-control"
                placeholder="Pesquisar coleções..."
                v-model.trim="searchTerm"
              />

              <span class="input-group-text">
                <IconSearch :size="18" />
              </span>
            </div>
          </div>
        </div>

        <!-- Empty Search -->
        <EmptyState v-if="!filteredCollections.length" :icon="IconSearch" class="mt-4">
          Nenhum resultado encontrado para
          <strong v-if="debouncedTerm"> "{{ debouncedTerm }}" </strong>
          <span v-else> os filtros atuais. </span>
        </EmptyState>

        <!-- Grid -->
        <template v-else>
          <div class="row">
            <div
              v-for="collection in displayedCollections"
              :key="collection.id"
              v-memo="[collection.id]"
              class="col-12 col-sm-6 col-md-4"
            >
              <CollectionCard
                role="button"
                tabindex="0"
                :src="asset(collection.image_cover_url)"
                :title="collection.name"
                @click="handleCollectionClick(collection)"
                @keydown.enter="handleCollectionClick(collection)"
                @keydown.space.prevent="handleCollectionClick(collection)"
              />
            </div>
          </div>

          <!-- Infinite scroll sentinel -->
          <div ref="sentinelRef" aria-hidden="true" />
        </template>
      </div>
    </Page>
  </section>
</template>

<script setup>
import { computed, defineAsyncComponent, ref, watch } from 'vue';

import { useRoute, useRouter } from 'vue-router';

import { useDebounceFn, useIntersectionObserver } from '@vueuse/core';

import { asset } from '@/composables/useAsset';
import { useCollectionCategoryStore } from '@/stores/collectionCategoryStore';

import EmptyState from '@/components/empty-state/EmptyState.vue';
import Page from '@/components/page/Page.vue';
import CollectionCard from './components/CollectionCard.vue';
import CollectionCardSkeleton from './components/CollectionCardSkeleton.vue';

import { IconSearch } from '@tabler/icons-vue';

const CollectionActions = defineAsyncComponent({
  loader: () => import('./components/CollectionActions.vue'),
  delay: 200,
});

const PAGE_SIZE = 12;

const router = useRouter();
const route = useRoute();

const store = useCollectionCategoryStore();

const searchTerm = ref('');
const debouncedTerm = ref('');
const displayLimit = ref(PAGE_SIZE);
const sentinelRef = ref(null);

const updateSearch = useDebounceFn((value) => {
  debouncedTerm.value = value.toLowerCase();
}, 300);

watch(searchTerm, updateSearch);

const parentId = computed(() => Number(route.params.id) || null);

const collections = computed(() => {
  if (!parentId.value) {
    return store.getRootCategories();
  }

  return store.getChildrenByParentId(parentId.value);
});

const filteredCollections = computed(() => {
  const term = debouncedTerm.value;

  if (!term) return collections.value;

  return collections.value.filter((collection) => collection.name.toLowerCase().includes(term));
});

const displayedCollections = computed(() => filteredCollections.value.slice(0, displayLimit.value));

const hasMore = computed(() => displayLimit.value < filteredCollections.value.length);

const hasCollections = computed(() => collections.value.length > 0);

const loading = computed(() => store.loadingList);

// Reset pagination when filter or route changes
watch([debouncedTerm, parentId], () => {
  displayLimit.value = PAGE_SIZE;
});

useIntersectionObserver(sentinelRef, ([{ isIntersecting }]) => {
  if (isIntersecting && hasMore.value) {
    displayLimit.value += PAGE_SIZE;
  }
});

const handleCollectionClick = (collection) => {
  if (!collection?.id) return;

  router.push({
    name: parentId.value ? 'SubcategoryImages' : 'CollectionSubcategories',
    params: { id: collection.id },
  });
};

const loadData = async () => {
  const id = parentId.value;

  if (!id) {
    await store.loadCategories();

    return;
  }

  // carrega somente item pai
  await store.loadCategory(id);
};

watch(parentId, loadData, {
  immediate: true,
});
</script>
