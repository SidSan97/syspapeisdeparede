<template>
  <section class="content">
    <Page title="Editar modelo" :back-to="{ name: 'settings.models.list' }" :breadcrumbs="routes">
      <form @submit.prevent="updateCollectionModel()">
        <div class="row">
          <div class="col-lg-6">
            <CollectionModelForm
              ref="formRef"
              :loading="collectionModelStore.loadingCollectionModelById"
            ></CollectionModelForm>
          </div>
        </div>
        <div class="mt-4">
          <button
            type="submit"
            class="btn btn-primary me-2"
            :disabled="saving || collectionModelStore.loadingCollectionModelById"
          >
            Salvar as alterações
          </button>
        </div>
      </form>
    </Page>
  </section>
</template>

<script setup>
import { ref, nextTick, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from '@/composables/useToast';

import Page from '@/components/page/Page.vue';
import CollectionModelForm from '@/components/collection-models/CollectionModelForm.vue';

import { useCollectionModelStore } from '@/stores/collectionModelStore';
import { collectionModelService } from '@/services/collectionModelService';

const formRef = ref(null);
const saving = ref(false);

const router = useRouter();
const route = useRoute();
const toast = useToast();
const collectionModelStore = useCollectionModelStore();

const routes = [
  { path: '/settings', breadcrumbName: 'Configurações' },
  { path: '/settings/models', breadcrumbName: 'Modelos' },
  { path: '#', breadcrumbName: 'Editar' },
];

async function fetchCollectionModel() {
  try {
    await collectionModelStore.loadCollectionModelById(route.params.id);

    await nextTick();

    if (!formRef.value) return;

    const collectionModel = collectionModelStore.currentCollectionModel;

    formRef.value.form.reset();
    formRef.value.form.fill({
      id: collectionModel.id,
      name: collectionModel.name,
      value: collectionModel.value,
      deadline: collectionModel.deadline,
      requests: {
        link: collectionModel.requests.link,
        comment: collectionModel.requests.comment,
        file: collectionModel.requests.file,
        collection: collectionModel.requests.collection,
        layout: collectionModel.requests.layout,
      },
    });
  } catch (error) {
    console.error(error);
    toast.error('Erro ao carregar modelo. Tente novamente.');
    router.push({ name: 'settings.models.list' });
  }
}

async function updateCollectionModel() {
  if (collectionModelStore.loadingCollectionModelById) return;

  try {
    saving.value = true;
    const collectionModelId = formRef.value.form.id;

    await collectionModelService.update(collectionModelId, formRef.value.form.data());

    toast.success('Modelo atualizado com sucesso');

    router.push({ name: 'settings.models.list' });
  } catch (error) {
    formRef.value.form.handleErrors(error);

    toast.error('Erro ao atualizar modelo. Tente novamente.');
  } finally {
    saving.value = false;
  }
}

watch(() => route.params.id, fetchCollectionModel, { immediate: true });
</script>
