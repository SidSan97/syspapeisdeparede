<template>
    <div class="shadow-sm">
        <div v-if="isLoadingCollections" class="text-center text-muted py-5">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>

        <div v-else-if="!collections.length" class="text-center text-muted py-5">
            <p class="mb-3">Nenhuma coleção cadastrada.</p>
            <button type="button" class="btn btn-primary" @click="openCollectionModal()">
              Criar primeira coleção
            </button>
        </div>

        <div v-else class="accordion" id="collectionsAccordion">
            <div
              v-for="collection in collections"
              :key="collection.id"
              class="accordion-item"
            >
              <h2 class="accordion-header">
                <button
                  class="accordion-button"
                  :class="{ collapsed: !expandedCollections.has(collection.id) }"
                  type="button"
                  data-bs-toggle="collapse"
                  :data-bs-target="`#collection-${collection.id}`"
                  @click="toggleCollection(collection.id)"
                >
                  <div class="d-flex justify-content-between align-items-center w-100 me-3">
                    <span class="fw-semibold">{{ collection.name }}</span>
                    <div class="dropdown" @click.stop>
                      <button
                        class="btn btn-subtle btn-sm"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                      >
                        <i class="fa fa-ellipsis-h"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                          <button
                            class="dropdown-item"
                            type="button"
                            @click="openSubcategoryModal(null, collection)"
                          >
                            Nova subcategoria
                          </button>
                        </li>
                        <li>
                          <button
                            class="dropdown-item"
                            type="button"
                            @click="openCollectionModal(collection)"
                          >
                            Editar
                          </button>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                          <button
                            class="dropdown-item text-danger"
                            type="button"
                            :disabled="deletingCollectionId === collection.id"
                            @click="confirmDeleteCollection(collection)"
                          >
                            Excluir
                          </button>
                        </li>
                      </ul>
                    </div>
                  </div>
                </button>
              </h2>
              <div
                :id="`collection-${collection.id}`"
                class="accordion-collapse collapse"
                :class="{ show: expandedCollections.has(collection.id) }"
                data-bs-parent="#collectionsAccordion"
              >
                <div class="accordion-body p-4">
                  <!-- Subcategorias da Coleção -->
                  <div v-if="loadingSubcategories[collection.id]" class="text-center text-muted py-3">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                  </div>
                  <div v-else-if="!collectionSubcategories[collection.id]?.length" class="text-center text-muted py-3">
                    Nenhuma subcategoria cadastrada.
                  </div>
                  <div v-else class="list-group">
                    <div
                      v-for="subcategory in collectionSubcategories[collection.id]"
                      :key="subcategory.id"
                      class="list-group-item"
                      :class="{ 'active': selectedSubcategoryId === subcategory.id }"
                      style="cursor: pointer;"
                      @click="selectSubcategory(subcategory)"
                    >
                      <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                          <h6 class="mb-1">{{ subcategory.name }}</h6>
                          <small class="text-muted">{{ formatCount(subcategory.images_count) }}</small>
                        </div>
                        <div class="d-flex gap-1">
                          <button
                            class="btn btn-sm btn-default"
                            type="button"
                            @click.stop="openSubcategoryModal(subcategory, collection)"
                            title="Editar subcategoria"
                          >
                            Editar
                          </button>
                          <button
                            class="btn btn-sm btn-danger"
                            type="button"
                            :disabled="deletingSubcategoryId === subcategory.id"
                            @click.stop="confirmDeleteSubcategory(subcategory)"
                            title="Excluir subcategoria"
                          >
                            Excluir
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
  collections: { type: Array, required: true },
  isLoadingCollections: { type: Boolean, default: false },
  expandedCollections: { type: Object, required: true },
  collectionSubcategories: { type: Object, required: true },
  loadingSubcategories: { type: Object, required: true },
  selectedSubcategoryId: { type: [Number, String], default: null },
  deletingCollectionId: { type: [Number, String], default: null },
  deletingSubcategoryId: { type: [Number, String], default: null },
  formatCount: { type: Function, required: true },
  toggleCollection: { type: Function, required: true },
  openCollectionModal: { type: Function, required: true },
  openSubcategoryModal: { type: Function, required: true },
  confirmDeleteCollection: { type: Function, required: true },
  selectSubcategory: { type: Function, required: true },
  confirmDeleteSubcategory: { type: Function, required: true },
});
</script>

<style scoped>
.accordion-button {
  padding: 0 15px;
  height: 50px;
}
</style>