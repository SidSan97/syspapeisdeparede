<template>
  <section class="content">
    <Page title="Tiny ERP" subtitle="Configure produtos associados à plataforma." back-to="/settings">
      <div class="card">
        <div class="card-body">
          <form @submit.prevent="handleSubmit">
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label for="gtin" class="form-label">
                  GTIN
                  <i
                    class="fa fa-question-circle text-muted ms-1"
                    style="font-size: 0.875rem; cursor: help;"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="Código GTIN do produto no Tiny ERP"
                  ></i>
                </label>
                <input
                  id="gtin"
                  v-model.trim="form.gtin"
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': form.errors.has('gtin') }"
                  placeholder="Ex: 7891234567890"
                />
                <has-error :form="form" field="gtin"></has-error>
              </div>
              <div class="col-12 col-md-6">
                <label for="cep" class="form-label">
                  CEP
                  <i
                    class="fa fa-question-circle text-muted ms-1"
                    style="font-size: 0.875rem; cursor: help;"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="CEP do remetente dos pedidos para cálculo de frete e entrega"
                  ></i>
                </label>
                <input
                  id="cep"
                  v-model.trim="form.cep"
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': form.errors.has('cep') }"
                  placeholder="Ex: 01310-100"
                  maxlength="9"
                  @input="formatCep"
                />
                <has-error :form="form" field="cep"></has-error>
              </div>
              <div class="col-12 col-md-6">
                <label for="ncm" class="form-label">
                  NCM
                  <i
                    class="fa fa-question-circle text-muted ms-1"
                    style="font-size: 0.875rem; cursor: help;"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="NCM do produto no Tiny ERP. Obrigatório para emissão de nota fiscal."
                  ></i>
                </label>
                <input
                  id="ncm"
                  v-model.trim="form.ncm"
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': form.errors.has('ncm') }"
                  placeholder="Ex: 4814.20.00"
                  maxlength="10"
                />
                <has-error :form="form" field="ncm"></has-error>
              </div>
            </div>

            <hr class="my-4">

            <div class="d-flex flex-wrap gap-3 align-items-center justify-content-end">
              <button type="submit" class="btn btn-primary" :disabled="isSaving">
                <span v-if="isSaving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                {{ isSaving ? 'Salvando...' : 'Salvar configurações' }}
              </button>
              <router-link :to="{ name: 'SettingsHome' }" class="btn btn-subtle">
                Cancelar
              </router-link>
            </div>
          </form>
        </div>
      </div>
    </Page>
  </section>
</template>

<script setup>
import { reactive, ref, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Page from '@/components/page/Page.vue';
import Form from 'vform';

const router = useRouter();
const Toast = window.Toast;

const form = reactive(
  new Form({
    gtin: '',
    cep: '',
    ncm: '',
  })
);

const isSaving = ref(false);
const isLoading = ref(false);

const formatCep = (event) => {
  let value = event.target.value.replace(/\D/g, '');
  if (value.length > 5) {
    value = value.substring(0, 5) + '-' + value.substring(5, 8);
  }
  form.cep = value;
};

const formatCepForDisplay = (cep) => {
  if (!cep) return '';
  const cleaned = cep.replace(/\D/g, '');
  if (cleaned.length === 8) {
    return cleaned.substring(0, 5) + '-' + cleaned.substring(5, 8);
  }
  return cep;
};

const loadSettings = async () => {
  isLoading.value = true;
  try {
    const { data } = await axios.get('v1/tiny-erp/settings');
    form.gtin = data.data?.gtin || '';
    form.cep = formatCepForDisplay(data.data?.cep || '');
    form.ncm = data.data?.ncm || '';
  } catch (error) {
    console.error('Erro ao carregar configurações:', error);

    form.gtin = '';
    form.cep = '';
    form.ncm = '';
  } finally {
    isLoading.value = false;
  }
};

const handleSubmit = async () => {
  if (isSaving.value) {
    return;
  }

  try {
    isSaving.value = true;

    if (form.cep) {
      form.cep = form.cep.replace(/\D/g, '');
    }

    if (form.ncm) {
      form.ncm = form.ncm.replace(/\D/g, '');
    }

    const response = await form.post('v1/tiny-erp/settings');

    Toast.fire({
      icon: 'success',
      title: response.data.message || 'Configurações salvas com sucesso!',
    });

    if (form.cep) {
      form.cep = formatCepForDisplay(form.cep);
    }

    await loadSettings();
  } catch (error) {
    Toast.fire({
      icon: 'error',
      title: error.response?.data?.message || 'Erro ao salvar configurações. Tente novamente.',
    });
  } finally {
    isSaving.value = false;
  }
};

onMounted(async () => {
  document.title = 'Tiny ERP - Configurações';
  await loadSettings();
  
  // Inicializar tooltips do Bootstrap
  await nextTick();
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  if (typeof bootstrap !== 'undefined') {
    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
  }
});
</script>

<style scoped>
.card {
  border: 1px solid var(--bs-border-color);
  border-radius: 1rem;
}
</style>

