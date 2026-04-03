<template>
  <div v-if="blocks.length" class="wall-model-referring-fields mb-3" :class="{ 'wall-model-referring-fields--compact': compact }">
    <h3
      v-if="!compact"
      class="wall-model-referring-fields__title d-flex align-items-center gap-2 mb-2"
    >
      <i class="fa fa-paperclip"></i>
      Referências do modelo
    </h3>
    <div
      v-for="block in blocks"
      :key="block.key"
      class="wall-model-referring-fields__block"
    >
      <div class="text-muted small mb-1">{{ block.label }}</div>
      <template v-if="block.type === 'text'">
        <div class="p-2 rounded border wall-model-referring-fields__text">
          {{ block.text }}
        </div>
      </template>
      <template v-else-if="block.type === 'link'">
        <a
          :href="block.href"
          target="_blank"
          rel="noopener noreferrer"
          class="text-break"
        >{{ block.text }}</a>
      </template>
      <template v-else-if="block.type === 'files'">
        <div class="d-flex flex-wrap gap-2">
          <a
            v-for="(it, i) in block.items"
            :key="i"
            :href="it.url"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-sm btn-outline-secondary"
          >
            <i class="fa fa-file me-1"></i>
            {{ it.label }}
          </a>
        </div>
      </template>
      <template v-else-if="block.type === 'collection_image'">
        <div class="wall-model-referring-fields__collection-image p-2 rounded border">
          <template v-if="!collectionImageState(block.imageId) || collectionImageState(block.imageId).status === 'loading'">
            <span class="text-muted small">Carregando...</span>
          </template>
          <template v-else-if="collectionImageState(block.imageId).status === 'error'">
            <span class="text-danger small">{{ collectionImageState(block.imageId).message }}</span>
            <div class="text-muted small mt-1">ID: {{ block.imageId }}</div>
          </template>
          <template v-else>
            <div class="fw-medium">{{ collectionImageState(block.imageId).data?.name ?? '—' }}</div>
            
            <div v-if="collectionImageState(block.imageId).data?.url" class="mt-2 d-flex flex-column gap-2">
              <img
                :src="collectionImageState(block.imageId).data.url"
                :alt="collectionImageState(block.imageId).data?.name || 'Arte da coleção'"
                class="wall-model-referring-fields__collection-thumb rounded"
              />
              <a
                :href="collectionImageState(block.imageId).data.url"
                target="_blank"
                rel="noopener noreferrer"
                class="small"
              >Abrir imagem</a>
            </div>
          </template>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useWallService } from '@/services/wallService';
import { getWallModelReferringBlocks } from '@/utils/wallModelReferringContent';

const props = defineProps({
  wall: {
    type: Object,
    required: true,
  },
  compact: {
    type: Boolean,
    default: false,
  },
});

const { getCollectionImageById } = useWallService();

const blocks = computed(() => getWallModelReferringBlocks(props.wall));

/** @type {import('vue').Ref<Record<number, { status: string, data?: object, message?: string }>>} */
const collectionImagesById = ref({});

function collectionImageState(imageId) {
  return collectionImagesById.value[imageId];
}

async function loadCollectionImage(imageId) {
  if (imageId == null || imageId <= 0) {
    return;
  }
  const cur = collectionImagesById.value[imageId];
  if (cur?.status === 'loaded' || cur?.status === 'loading') {
    return;
  }

  collectionImagesById.value = {
    ...collectionImagesById.value,
    [imageId]: { status: 'loading' },
  };

  try {
    const data = await getCollectionImageById(imageId);
    collectionImagesById.value = {
      ...collectionImagesById.value,
      [imageId]: { status: 'loaded', data: data || null },
    };
  } catch (err) {
    collectionImagesById.value = {
      ...collectionImagesById.value,
      [imageId]: {
        status: 'error',
        message: err?.response?.data?.message || err?.message || 'Não foi possível carregar a arte.',
      },
    };
  }
}

watch(
  blocks,
  (list) => {
    for (const b of list) {
      if (b.type === 'collection_image') {
        loadCollectionImage(b.imageId);
      }
    }
  },
  { immediate: true },
);
</script>

<style lang="scss" scoped>
.wall-model-referring-fields {
  margin-bottom: 0;

  &--compact {
    .wall-model-referring-fields__block {
      margin-bottom: 0.75rem;

      &:last-child {
        margin-bottom: 0;
      }
    }
  }
}

.wall-model-referring-fields__title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--bs-body-color);
}

.wall-model-referring-fields__block {
  margin-bottom: 1rem;

  &:last-child {
    margin-bottom: 0;
  }
}

.wall-model-referring-fields__text {
  font-size: 0.875rem;
  line-height: 1.5;
  white-space: pre-wrap;
  word-break: break-word;
}

.wall-model-referring-fields__collection-image {
  background-color: var(--bs-body-bg, #fff);
}

.wall-model-referring-fields__collection-thumb {
  max-width: 100%;
  max-height: 160px;
  width: auto;
  height: auto;
  object-fit: contain;
  border: 1px solid var(--bs-border-color);
}
</style>
