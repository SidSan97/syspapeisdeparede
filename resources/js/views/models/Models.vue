<template>
  <section class="content">
    <Page title="Modelos" subtitle="Cadastre e edite aqui os modelos utilizados pelos orçamentos." back-to="/settings">
      <template #actions>
        <button
          class="btn btn-primary"
          type="button"
          @click="startCreating"
        >
          Criar modelo
        </button>
      </template>
      
      <div class="collection-models">
        <ModelForm
          :is-visible="isFormVisible"
          :is-editing="isEditing"
          :is-saving="isSaving"
          :form="form"
          @submit="handleSubmit"
          @cancel="cancelForm"
          @reset="resetForm"
        />

        <ModelsTable
          :models="models"
          :is-loading="isLoading"
          :has-models="hasModels"
          :total-models="totalModels"
          :deleting-id="deletingId"
          @edit="editModel"
          @delete="confirmDelete"
        />
      </div>
    </Page>
  </section>
</template>

<script setup>
import { onMounted } from 'vue';
import Page from '@/components/page/Page.vue';
import { useModels } from '@/modules/models/composables/useModels';
import ModelForm from '@/modules/models/components/ModelForm.vue';
import ModelsTable from '@/modules/models/components/ModelsTable.vue';

const {
    models,
    pagination,
    isFormVisible,
    isEditing,
    isLoading,
    isSaving,
    deletingId,
    form,
    totalModels,
    hasModels,
    fetchModels,
    startCreating,
    cancelForm,
    resetForm,
    editModel,
    handleSubmit,
    confirmDelete,
} = useModels();

onMounted(() => {
    fetchModels();
});
</script>

<style scoped>
.collection-models {
  width: 100%;
  padding-bottom: 3rem;
}
</style>

