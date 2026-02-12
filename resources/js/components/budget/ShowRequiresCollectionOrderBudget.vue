<template>
  <div v-if="requiresCollection" class="mb-4">
    <h6 class="fw-semibold mb-3">Selecione uma arte da coleção para a parede</h6>
    <div v-if="collectionLoading" class="alert alert-warning mb-0">
      Carregando coleções disponíveis...
    </div>
    <div v-else-if="collectionError" class="alert alert-danger mb-0">
      {{ collectionError }}
    </div>
    <div v-else-if="!collectionList.length" class="alert alert-info mb-0">
      Nenhuma coleção disponível. Entre em contato com o suporte para prosseguir.
    </div>
    <div v-else class="d-flex flex-column gap-3">
      <template
        v-for="wall in (walls || []).filter((w) => w.requiresCollection)"
        :key="wall.key"
      >
        <div v-if="wallSelections[wall.key]" class="collection-selection">
          <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
            <div>
              <div class="fw-semibold">{{ wall.roomName }}</div>
              <div class="text-muted small">{{ wall.wallName }}</div>
            </div>
            <div class="w-100 w-md-50">
              <label :for="`collection-select-${wall.key}`" class="form-label">
                Coleção
              </label>
              <select
                :id="`collection-select-${wall.key}`"
                class="form-select"
                v-model="wallSelections[wall.key].collectionId"
                :disabled="orderSubmitting || collectionLoading"
                @change="handleCollectionSelectionChange(wall.key)"
              >
                <option :value="null">Selecione uma coleção</option>
                <option
                  v-for="collection in collectionList"
                  :key="collection.id"
                  :value="collection.id"
                >
                  {{ collection.name }}
                </option>
              </select>
            </div>
          </div>

          <div v-if="wallSelections[wall.key].collectionId">
            <div
              v-if="getCollectionState(wallSelections[wall.key].collectionId).loading"
              class="text-muted small"
            >
              Carregando imagens...
            </div>
            <div
              v-else-if="getCollectionState(wallSelections[wall.key].collectionId).error"
              class="text-danger small"
            >
              {{ getCollectionState(wallSelections[wall.key].collectionId).error }}
            </div>
            <div
              v-else-if="!getCollectionState(wallSelections[wall.key].collectionId).items.length"
              class="text-muted small"
            >
              Nenhuma imagem disponível nesta coleção.
            </div>
            <div v-else>
              <label :for="`search-art-${wall.key}`" class="form-label small">
                Buscar arte pelo nome
              </label>
              <input
                :id="`search-art-${wall.key}`"
                type="text"
                class="form-control form-control-sm mb-2"
                placeholder="Digite para filtrar..."
                :value="wallSearchTerms[wall.key] ?? ''"
                :disabled="orderSubmitting"
                @input="setWallSearchTerm(wall.key, $event.target.value)"
              >
              <div
                v-if="!getFilteredCollectionItems(wall.key).length"
                class="text-muted small"
              >
                Nenhuma arte encontrada para essa busca.
              </div>
              <div v-else class="collection-images-grid">
                <button
                  v-for="image in getFilteredCollectionItems(wall.key)"
                  :key="image.id ?? `image-${wall.key}`"
                  type="button"
                  class="collection-image-button"
                  :class="{
                    selected: wallSelections[wall.key].imageId === image.id,
                  }"
                  @click="selectCollectionImage(wall.key, image.id)"
                  :disabled="orderSubmitting"
                >
                  <img
                    :src="image.url"
                    :alt="image.name ?? image.title"
                    class="collection-image-thumb"
                    @error="handleCollectionImageError"
                  >
                  <span class="collection-image-name text-truncate d-block w-100">
                    {{ image.name ?? image.title }}
                  </span>
                </button>
              </div>
            </div>
          </div>
          <div v-else class="text-muted small">
            Escolha uma coleção para visualizar as artes disponíveis.
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
defineProps({
  requiresCollection: { type: Boolean, default: false },
  walls: { type: Array, default: () => [] },
  collectionLoading: { type: Boolean, default: false },
  collectionError: { type: String, default: '' },
  collectionList: { type: Array, default: () => [] },
  wallSelections: { type: Object, default: () => ({}) },
  wallSearchTerms: { type: Object, default: () => ({}) },
  orderSubmitting: { type: Boolean, default: false },
  getCollectionState: { type: Function, default: null },
  handleCollectionSelectionChange: { type: Function, default: null },
  setWallSearchTerm: { type: Function, default: null },
  getFilteredCollectionItems: { type: Function, default: null },
  selectCollectionImage: { type: Function, default: null },
  handleCollectionImageError: { type: Function, default: null },
});
</script>

<style scoped>
.collection-selection {
  border: 1px solid var(--bs-border-color);
  border-radius: 0.75rem;
  padding: 1rem;
  background-color: var(--bs-body-bg);
  box-shadow: 0 0.5rem 1.25rem rgba(15, 15, 15, 0.06);
}

.collection-images-grid {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
}

.collection-image-button {
  border: 1px solid var(--bs-border-color);
  border-radius: 0.5rem;
  padding: 0.5rem;
  background-color: var(--bs-body-bg);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  align-items: center;
}

.collection-image-button:hover,
.collection-image-button:focus {
  border-color: var(--bs-primary);
  box-shadow: 0 0.5rem 1rem rgba(13, 110, 253, 0.15);
}

.collection-image-button.selected {
  border-color: var(--bs-success);
  box-shadow: 0 0.5rem 1rem rgba(25, 135, 84, 0.2);
}

.collection-image-thumb {
  width: 100%;
  height: 100px;
  object-fit: cover;
  border-radius: 0.35rem;
}

.collection-image-name {
  font-size: 0.8rem;
  text-align: center;
  color: var(--bs-body-color);
}
</style>