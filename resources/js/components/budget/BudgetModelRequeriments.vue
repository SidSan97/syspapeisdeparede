<template>
  <div v-if="model && hasRequirements" class="border rounded p-3 bg-body-tertiary mt-2">
    <div class="text-muted small mb-2">Requisitos do modelo selecionado</div>

    <div v-if="requiresComment" class="mb-3">
      <label class="form-label">Descrição do modelo</label>
      <textarea
        v-model.trim="wall.comment_referring_model"
        class="form-control"
        rows="3"
        maxlength="500"
        :disabled="disabled"
      ></textarea>
    </div>

    <div v-if="requiresLink" class="mb-3">
      <label class="form-label">Link de referência</label>
      <input
        v-model.trim="wall.link_referring_model"
        type="url"
        class="form-control"
        placeholder="https://exemplo.com"
        :disabled="disabled"
      />
    </div>

    <div v-if="requiresFiles" class="mb-3">
      <label class="form-label">Arquivos de referência (um por linha)</label>
      <textarea
        :value="filesAsText"
        class="form-control"
        rows="3"
        placeholder="https://... ou caminho do arquivo"
        :disabled="disabled"
        @input="handleFilesInput($event.target.value)"
      ></textarea>
      <small class="text-muted">Você pode informar URLs/caminhos para referência da arte.</small>
    </div>

    <div v-if="requiresCollection" class="mb-2">
      <div class="mb-2">
        <label class="form-label">Coleção</label>
        <select
          v-model="selectedCollectionId"
          class="form-select"
          :disabled="disabled || loadingCollections"
          @change="onCollectionChange"
        >
          <option :value="null">Selecione uma coleção</option>
          <option v-for="item in collections" :key="item.id" :value="item.id">
            {{ item.name }}
          </option>
        </select>
      </div>

      <div v-if="loadingCollections" class="text-muted small">Carregando coleções...</div>
      <div v-else-if="collectionError" class="text-danger small">{{ collectionError }}</div>

      <div v-if="selectedCollectionId" class="mt-2">
        <div v-if="loadingImages" class="text-muted small">Carregando artes...</div>
        <div v-else-if="imagesError" class="text-danger small">{{ imagesError }}</div>
        <div v-else-if="!images.length" class="text-muted small">Nenhuma arte disponível.</div>
        <div v-else class="d-flex flex-wrap gap-2">
          <button
            v-for="image in images"
            :key="image.id"
            type="button"
            class="btn btn-sm"
            :class="wall.collection_referring_model == image.id ? 'btn-success' : 'btn-outline-secondary'"
            :disabled="disabled"
            @click="wall.collection_referring_model = String(image.id)"
          >
            {{ image.name || image.title || `Arte ${image.id}` }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useBudgetOrderService } from '@/modules/budgets/services/budgetOrderService';

const props = defineProps({
  wall: { type: Object, required: true },
  model: { type: Object, default: null },
  disabled: { type: Boolean, default: false },
});

const budgetOrderService = useBudgetOrderService();
const collections = ref([]);
const images = ref([]);
const selectedCollectionId = ref(null);
const loadingCollections = ref(false);
const loadingImages = ref(false);
const collectionError = ref('');
const imagesError = ref('');

const requiresComment = computed(() => Boolean(props.model?.requests?.comment));
const requiresLink = computed(() => Boolean(props.model?.requests?.link));
const requiresFiles = computed(() => Boolean(props.model?.requests?.file));
const requiresCollection = computed(() => Boolean(props.model?.requests?.collection));

const hasRequirements = computed(
  () => requiresComment.value || requiresLink.value || requiresFiles.value || requiresCollection.value
);

const filesAsText = computed(() => {
  const files = Array.isArray(props.wall?.files_referring_model) ? props.wall.files_referring_model : [];
  return files.join('\n');
});

watch(
  () => props.wall,
  (wall) => {
    if (wall) {
      if (!Array.isArray(wall.files_referring_model)) wall.files_referring_model = [];
      if (wall.comment_referring_model == null) wall.comment_referring_model = '';
      if (wall.link_referring_model == null) wall.link_referring_model = '';
      if (wall.collection_referring_model == null) wall.collection_referring_model = '';
    }
  },
  { immediate: true, deep: false }
);

watch(
  () => props.model?.id,
  async () => {
    if (requiresCollection.value) {
      await ensureCollectionsLoaded();
    } else {
      selectedCollectionId.value = null;
      images.value = [];
    }
  },
  { immediate: true }
);

function handleFilesInput(value) {
  const normalized = String(value || '')
    .split('\n')
    .map((item) => item.trim())
    .filter((item) => item.length > 0);
  props.wall.files_referring_model = normalized;
}

async function ensureCollectionsLoaded() {
  if (collections.value.length || loadingCollections.value) return;
  loadingCollections.value = true;
  collectionError.value = '';
  try {
    collections.value = await budgetOrderService.getCollectionCategories();
  } catch (error) {
    collectionError.value = error?.response?.data?.message || 'Erro ao carregar coleções.';
  } finally {
    loadingCollections.value = false;
  }
}

async function onCollectionChange() {
  images.value = [];
  imagesError.value = '';
  if (!selectedCollectionId.value) return;
  loadingImages.value = true;
  try {
    images.value = await budgetOrderService.getCollectionCategoryImages(selectedCollectionId.value);
  } catch (error) {
    imagesError.value = error?.response?.data?.message || 'Erro ao carregar artes da coleção.';
  } finally {
    loadingImages.value = false;
  }
}
</script>
