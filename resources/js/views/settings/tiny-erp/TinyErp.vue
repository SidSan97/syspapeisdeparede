<template>
  <section class="content">
    <Page
      title="Tiny ERP"
      subtitle="Configure produtos associados à plataforma."
      :breadcrumbs="routes"
    >
      <form @submit.prevent="handleSubmit">
        <div class="col-md-6">
          <BaseInput
            id="gtin"
            v-model.trim="formData.gtin"
            label="GTIN"
            type="text"
            placeholder="Ex: 7891234567890"
            :class="{ 'is-invalid': errors.gtin }"
            form-group-class="mb-3"
          >
            <template #bottom>
              <div v-if="errors.gtin" class="invalid-feedback d-block">
                {{ errors.gtin }}
              </div>
              <small class="form-hint form-text text-muted">
                Código GTIN do produto no Tiny ERP.
              </small>
            </template>
          </BaseInput>

          <BaseInput
            id="cep"
            v-model="formData.cep"
            label="CEP"
            type="text"
            placeholder="Ex: 01310-100"
            maxlength="9"
            :class="{ 'is-invalid': errors.cep }"
            form-group-class="mb-3"
            @keyup="handleCepInput"
          >
            <template #bottom>
              <div v-if="errors.cep" class="invalid-feedback d-block">
                {{ errors.cep }}
              </div>
              <small class="form-hint form-text text-muted">
                CEP do remetente dos pedidos para cálculo de frete e entrega.
              </small>
            </template>
          </BaseInput>

          <BaseInput
            id="ncm"
            v-model.trim="formData.ncm"
            label="NCM"
            type="text"
            placeholder="Ex: 4814.20.00"
            maxlength="10"
            :class="{ 'is-invalid': errors.ncm }"
            form-group-class="mb-3"
          >
            <template #bottom>
              <div v-if="errors.ncm" class="invalid-feedback d-block">
                {{ errors.ncm }}
              </div>
              <small class="form-hint form-text text-muted">
                NCM do produto no Tiny ERP. Obrigatório para emissão de nota fiscal.
              </small>
            </template>
          </BaseInput>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
            <span
              v-if="isSubmitting"
              class="spinner-border spinner-border-sm me-2"
              role="status"
              aria-hidden="true"
            ></span>
            {{ isSubmitting ? 'Salvando...' : 'Salvar as alterações' }}
          </button>
        </div>
      </form>
    </Page>
  </section>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import Page from '@/components/page/Page.vue';
import BaseInput from '@/components/common/BaseInput.vue';
import { useToast } from '@/composables/useToast';
import { http } from '@/lib/http';

// Composables
const toast = useToast();

// Estado reativo
const formData = reactive({
  gtin: '',
  cep: '',
  ncm: '',
});

const errors = reactive({
  gtin: '',
  cep: '',
  ncm: '',
});

const isSubmitting = ref(false);
const isLoading = ref(false);

// Constantes
const routes = [
  { path: '/settings', breadcrumbName: 'Configurações' },
  { path: '/settings/tiny-erp', breadcrumbName: 'Tiny ERP' },
];

// Funções utilitárias
const formatCep = (value) => {
  let numbers = value.replace(/\D/g, '');
  if (numbers.length > 5) {
    numbers = `${numbers.slice(0, 5)}-${numbers.slice(5, 8)}`;
  }
  return numbers;
};

const clearErrors = () => {
  Object.keys(errors).forEach((key) => {
    errors[key] = '';
  });
};

// Validação
const validateForm = () => {
  clearErrors();
  let isValid = true;

  // Validação GTIN (8, 12, 13 ou 14 dígitos numéricos)
  if (formData.gtin && !/^\d{8}$|^\d{12}$|^\d{13}$|^\d{14}$/.test(formData.gtin)) {
    errors.gtin = 'GTIN deve conter 8, 12, 13 ou 14 dígitos numéricos';
    isValid = false;
  }

  // Validação CEP
  if (formData.cep && !/^\d{5}-\d{3}$/.test(formData.cep)) {
    errors.cep = 'CEP deve estar no formato 00000-000';
    isValid = false;
  }

  // Validação NCM (formato XX.XX.XX ou XXXXXXXX)
  if (formData.ncm) {
    const ncmClean = formData.ncm.replace(/\./g, '');
    if (!/^\d{8}$/.test(ncmClean)) {
      errors.ncm = 'NCM deve conter 8 dígitos';
      isValid = false;
    }
  }

  return isValid;
};

// Manipuladores de eventos
const handleCepInput = (event) => {
  const rawValue = event.target.value;
  formData.cep = formatCep(rawValue);
};

// API calls
const loadSettings = async () => {
  isLoading.value = true;

  try {
    const { data } = await http.get('v1/tiny-erp/settings');

    formData.gtin = data.data?.gtin || '';
    formData.cep = data.data?.cep || '';
    formData.ncm = data.data?.ncm || '';

    clearErrors();
  } catch (error) {
    console.error('Erro ao carregar configurações:', error);
    toast.error('Erro ao carregar configurações. Recarregue a página.');
  } finally {
    isLoading.value = false;
  }
};

const saveSettings = async () => {
  const payload = {
    gtin: formData.gtin,
    cep: formData.cep,
    ncm: formData.ncm,
  };

  const response = await http.post('v1/tiny-erp/settings', payload);
  return response.data;
};

const handleSubmit = async () => {
  if (isSubmitting.value) return;

  if (!validateForm()) {
    toast.warning('Por favor, corrija os erros no formulário');
    return;
  }

  isSubmitting.value = true;

  try {
    await saveSettings();
    toast.success('Configurações salvas com sucesso!');
  } catch (error) {
    console.error('Erro ao salvar:', error);

    // Tratamento de erro do backend
    if (error.response?.data?.errors) {
      const backendErrors = error.response.data.errors;
      Object.keys(backendErrors).forEach((field) => {
        if (errors.hasOwnProperty(field)) {
          errors[field] = backendErrors[field][0];
        }
      });
    }

    toast.error('Erro ao salvar configurações. Tente novamente.');
  } finally {
    isSubmitting.value = false;
  }
};

// Lifecycle
onMounted(async () => {
  await loadSettings();

  document.title = 'Tiny ERP - Configurações';
});
</script>
