<template>
  <section class="content">
    <Page
      title="Criar novo modelo"
      :back-to="{ name: 'settings.models.list' }"
      :breadcrumbs="routes"
    >
      <form @submit.prevent="createCollectionModel()">
        <div class="row">
          <div class="col-lg-6">
            <CollectionModelForm ref="formRef"></CollectionModelForm>
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn btn-primary me-2" :disabled="saving">Salvar</button>
        </div>
      </form>
    </Page>
  </section>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from '@/composables/useToast';

import Page from '@/components/page/Page.vue';
import CollectionModelForm from '@/components/collection-models/CollectionModelForm.vue';

import { collectionModelService } from '@/services/collectionModelService';

const toast = useToast();
const router = useRouter();

const formRef = ref();
const saving = ref(false);

const routes = [
  { path: '/settings', breadcrumbName: 'Configurações' },
  { path: '/settings/models', breadcrumbName: 'Modelos' },
  { path: '/settings/models/create', breadcrumbName: 'Criar' },
];

async function createCollectionModel() {
  try {
    saving.value = true;

    await collectionModelService.create(formRef.value.form.data());

    toast.success('Modelo criado com sucesso!');

    router.push({ name: 'settings.models.list' });
  } catch (error) {
    formRef.value.form.handleErrors(error);
    toast.error('Opa! Algo deu errado ao criar o modelo.');
  } finally {
    saving.value = false;
  }
}
</script>
