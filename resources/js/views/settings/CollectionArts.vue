<template>
  <section class="content">
    <Page title="Categorias" subtitle="Edite categorias, subcategorias e imagens." back-to="/settings">
      <template #actions>
        <button type="button" class="btn btn-primary" @click="openCollectionModal()">
          <i class="fa fa-plus me-2"></i> Criar categoria
        </button>
      </template>

      <div class="shadow-sm">
          <CollectionList
            :collections="collections"
            :is-loading-collections="isLoadingCollections"
            :expanded-collections="expandedCollections"
            :collection-subcategories="collectionSubcategories"
            :loading-subcategories="loadingSubcategories"
            :selected-subcategory-id="selectedSubcategoryId"
            :deleting-collection-id="deletingCollectionId"
            :deleting-subcategory-id="deletingSubcategoryId"
            :format-count="formatCount"
            :toggle-collection="toggleCollection"
            :open-collection-modal="openCollectionModal"
            :open-subcategory-modal="openSubcategoryModal"
            :confirm-delete-collection="confirmDeleteCollection"
            :select-subcategory="selectSubcategory"
            :confirm-delete-subcategory="confirmDeleteSubcategory"
        />
      </div>

      <!-- Painel de Gerenciamento de Subcategoria -->
      <div v-if="selectedSubcategory" class="card mt-4 shadow-sm">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-0">Editar: {{ selectedSubcategory.name }}</h5>
              <small class="text-muted">Coleção: {{ getCollectionName(selectedSubcategory.parent_id) }}</small>
            </div>
            <div class="d-flex align-items-center gap-2">
              <button
                type="button"
                class="btn btn-sm btn-default"
                @click="openSubcategoryModal(selectedSubcategory, getCollectionById(selectedSubcategory.parent_id))"
              >
                 Editar
              </button>
              <button type="button" class="btn-close" @click="clearSubcategorySelection"></button>
            </div>
          </div>
        </div>
        <div class="card-body">
          <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
              <button
                class="nav-link"
                :class="{ active: activeTab === 'upload' }"
                type="button"
                @click="activeTab = 'upload'"
              >
                <i class="fa fa-upload me-2"></i> Upload de Imagens
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button
                class="nav-link"
                :class="{ active: activeTab === 'images' }"
                type="button"
                @click="activeTab = 'images'"
              >
                <i class="fa fa-images me-2"></i> Imagens ({{ currentImages.length }})
              </button>
            </li>
          </ul>

          <div class="tab-content">
            <!-- Tab: Upload de Imagens -->
            <div v-show="activeTab === 'upload'" class="tab-pane fade" :class="{ 'show active': activeTab === 'upload' }">
              <form @submit.prevent="handleUpload(fileInput)">
                <div class="mb-3">
                  <label for="catalogFiles" class="form-label">Selecione as imagens</label>
                  <input
                    id="catalogFiles"
                    ref="fileInput"
                    class="form-control"
                    type="file"
                    accept="image/*"
                    multiple
                    :disabled="isUploading"
                    @change="handleFileChange"
                  />
                  <small class="text-muted">Formatos: JPG, PNG, WEBP (máx. 5MB cada)</small>
                </div>

                <div v-if="selectedFiles.length" class="mb-4">
                  <hr>
                  <h6 class="mb-3">Nome das imagens</h6>
                  <div class="row g-3">
                    <div
                      v-for="fileItem in selectedFiles"
                      :key="fileItem.id"
                      class="col-12 col-md-6"
                    >
                      <label :for="`imageName_${fileItem.id}`" class="form-label">
                        Nome para: <small class="text-muted">{{ fileItem.file.name }}</small>
                      </label>
                      <input
                        :id="`imageName_${fileItem.id}`"
                        v-model.trim="fileItem.name"
                        type="text"
                        class="form-control"
                        placeholder="Digite o nome da imagem"
                        maxlength="100"
                        :disabled="isUploading"
                      />
                    </div>
                  </div>
                </div>

                <div class="d-flex gap-2">
                  <button
                    type="button"
                    class="btn btn-outline-secondary"
                    :disabled="isUploading"
                    @click="resetForm(fileInput)"
                  >
                    Limpar
                  </button>
                  <button
                    type="submit"
                    class="btn btn-primary"
                    :disabled="isUploading || !selectedFiles.length"
                  >
                    <span
                      v-if="isUploading"
                      class="spinner-border spinner-border-sm me-2"
                    ></span>
                    Enviar imagens
                  </button>
                </div>
              </form>
            </div>

            <!-- Tab: Visualizar Imagens -->
            <div v-show="activeTab === 'images'" class="tab-pane fade" :class="{ 'show active': activeTab === 'images' }">
              <div v-if="currentLoading" class="text-center text-muted py-4">
                <div class="spinner-border" role="status"></div>
              </div>
              <div v-else-if="!currentImages.length" class="text-center text-muted py-4">
                Nenhuma imagem cadastrada nesta subcategoria.
              </div>
              <div v-else class="row g-3">
                <div
                  v-for="image in currentImages"
                  :key="image.id"
                  class="col-6 col-md-4 col-lg-3"
                >
                  <div class="card">
                    <div class="position-relative">
                      <img
                        :src="image.url"
                        :alt="image.path_name"
                        class="card-img-top"
                        style="height: 200px; object-fit: cover;"
                      />
                      <button
                        type="button"
                        class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2"
                        :disabled="deletingImageId === image.id"
                        @click="confirmDeleteImage(image)"
                        title="Remover imagem"
                      >
                        <i class="fa fa-trash"></i>
                      </button>
                    </div>
                    <div class="card-body">
                      <p class="card-text small text-truncate mb-0">{{ image.name }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <CollectionFormModal
        :visible="showCollectionModal"
        :collection="collectionForEdit"
        @close="closeCollectionModal"
        @saved="onCollectionSaved"
      />
      <SubcategoryFormModal
        :visible="showSubcategoryModal"
        :subcategory="subcategoryForEdit"
        :parent-collection="parentCollectionForSubcategory"
        @close="closeSubcategoryModal"
        @saved="onSubcategorySaved"
      />
    </Page>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import Page from '@/components/page/Page.vue';
import CollectionList from '@/modules/settings/collections/components/CollectionList.vue';
import CollectionFormModal from '@/modules/settings/collections/components/CollectionFormModal.vue';
import SubcategoryFormModal from '@/modules/settings/collections/components/SubcategoryFormModal.vue';
import { useCollection } from '@/modules/settings/collections/composables/useCollection';

const fileInput = ref(null);

const {
  collections,
  isLoadingCollections,
  deletingCollectionId,
  showCollectionModal,
  collectionForEdit,
  collectionSubcategories,
  expandedCollections,
  loadingSubcategories,
  deletingSubcategoryId,
  showSubcategoryModal,
  subcategoryForEdit,
  parentCollectionForSubcategory,
  collectionImages,
  isLoadingImages,
  selectedSubcategoryId,
  activeTab,
  isUploading,
  deletingImageId,
  selectedFiles,
  selectedSubcategory,
  currentImages,
  currentLoading,
  formatCount,
  getCollectionName,
  getCollectionById,
  fetchCollections,
  toggleCollection,
  openCollectionModal,
  closeCollectionModal,
  onCollectionSaved,
  confirmDeleteCollection,
  openSubcategoryModal,
  closeSubcategoryModal,
  onSubcategorySaved,
  selectSubcategory,
  clearSubcategorySelection,
  confirmDeleteSubcategory,
  handleFileChange,
  resetForm,
  handleUpload,
  confirmDeleteImage,
} = useCollection();

onMounted(() => {
  document.title = 'Gerenciar Coleções';
  fetchCollections();
});
</script>
